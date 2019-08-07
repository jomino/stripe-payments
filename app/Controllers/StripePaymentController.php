<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function start($request, $response, $args)
    {
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        $amount = $args['amount'];
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $this->setSessionVar(\Util\StripeUtility::SESSION_REFERRER,$token);
        $this->setSessionVar(\Util\StripeUtility::SESSION_AMOUNT,$amount);
        return $this->view->render($response, 'Home/paystart.html.twig');
    }

    public function identify($request, $response, $args)
    {
        $payment_type = $request->getParsedBodyParam('payment-type');
        $this->setSessionVar(\Util\StripeUtility::SESSION_METHOD,$payment_type);
        return $this->view->render($response, 'Home/payidentify.html.twig');
    }

    public function source($request, $response, $args)
    {
        $name = $request->getParsedBodyParam('name');
        $email = $request->getParsedBodyParam('email');
        if($user=$this->getUser()){
            $type = $this->session->get(\Util\StripeUtility::SESSION_METHOD);
            $amount = $this->session->get(\Util\StripeUtility::SESSION_AMOUNT);
            $currency = \Util\StripeUtility::DEFAULT_CURRENCY;
            $ret_url = $this->getReturnUrl($request->getUri(),$user->uuid);
            $options = $this->getSourceOptions($type);
            if($source=\Util\StripeUtility::createSource($user->skey,$type,$amount,$currency,$email,$name,$ret_url,$options)){
                $redir_url = $source->redirect->url;
                $src_id = $source->id;
                $src_status = $source->status==\Util\StripeUtility::STATUS_PENDING ? \Util\StripeUtility::STATUS_PENDING : \Util\StripeUtility::STATUS_FAILED;
                if($this->saveNewEvent($src_status,$user->uuid,$name,$email,$amount,$src_id)){
                    return $this->view->render($response, 'Home/payredir.html.twig',[
                        'redir_url' => $redir_url
                    ]);
                }else{
                    $this->logger->info('['.self::class.']cannot save new event');
                }
            }else{
                $this->logger->info('['.self::class.']cannot create source');
            }
        }else{
            $this->logger->info('['.self::class.']cannot read user datas');
        }
    }

    public function result($request, $response, $args)
    {
        $notFoundHandler = $this->notFoundHandler;
        return $notFoundHandler($request, $response);
    }

    private function setSessionVar($name,$value)
    {
        if($this->session->exists($name)){
            $this->session->delete($name);
        }
        $this->session->set($name,$value);
    }

    private function getUser()
    {
        $token = $this->session->get(\Util\StripeUtility::SESSION_REFERRER);
        try{
            $user = \App\Models\User::where('uuid',$token)->first();
            return $user;
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return null;
        }
    }

    private function getReturnUrl($uri,$uuid)
    {
        $route_name = 'payment_result';
        return $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor($route_name,[
            'token' => $uuid
        ]);
    }

    private function getSourceOptions($bank)
    {
        switch($bank){
            case \Util\StripeUtility::METHOD_BANCONTACT:
                return [
                    \Util\StripeUtility::METHOD_BANCONTACT => [
                        'preferred_language' => $this->language
                    ]
                ];
            case \Util\StripeUtility::METHOD_SOFORT:
                return [
                    \Util\StripeUtility::METHOD_SOFORT => [
                        'country' => \Util\StripeUtility::DEFAULT_COUNTRY,
                        'preferred_language' => $this->language
                    ]
                ];
            case \Util\StripeUtility::METHOD_IDEAL:
                return [
                    \Util\StripeUtility::METHOD_IDEAL => [
                        'bank' => \Util\StripeUtility::DEFAULT_IDEAL_BANK
                    ]
                ];
        }
    }

    private function saveNewEvent($status,$uuid,$name,$email,$amount,$skey)
    {
        try{
            $event = new Event();
            $event->status = $status;
            $event->uuid = $uuid;
            $event->name = $name;
            $event->email = $email;
            $event->amount = $amount;
            $event->skey = $skey;
            $event->save();
            return true;
        }catch(\Exception $e){
            return false;
        }
    }
}
