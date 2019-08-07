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
        if(!empty($name) && !empty($email)){
            if($user=$this->getUser()){
                if($source=$this->getSource($request,$user,$email,$name)){
                    $redir_url = $source->redirect->url;
                    return $this->view->render($response, 'Home/payredir.html.twig',[
                        'redir_url' => $redir_url
                    ]);
                }else{
                    $this->logger->info('['.self::class.']cannot read source datas');
                }
            }else{
                $this->logger->info('['.self::class.']cannot read user datas');
            }
        }else{
            $this->logger->info('['.self::class.']required client datas');
        }
    }

    public function result($request, $response, $args)
    {
        return $this->view->render($response, 'Home/payresult.html.twig');
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

    private function getCurrentEvent()
    {
        if($this->session->exists(\Util\StripeUtility::SESSION_TOKEN)){
            $s_token = $this->session->get(\Util\StripeUtility::SESSION_TOKEN);
            try{
                $event = \App\Models\Event::where('token',$s_token)->firstOrFail();
                return $event;
            }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return null;
            }
        }
        return null;
    }

    private function getSource($request,$user,$email,$name)
    {
        if($event=$this->getCurrentEvent()){
            $src_key = $event->skey;
            $api_key = $user->skey;
            $source = \Util\StripeUtility::retrieveSource($api_key,$src_key);
        }else{
            $s_token = \Util\UuidGenerator::v4();
            $type = $this->session->get(\Util\StripeUtility::SESSION_METHOD);
            $amount = $this->session->get(\Util\StripeUtility::SESSION_AMOUNT);
            $currency = \Util\StripeUtility::DEFAULT_CURRENCY;
            $ret_url = $this->getReturnUrl($request->getUri(),$s_token);
            $options = $this->getSourceOptions($type);
            $source = \Util\StripeUtility::createSource($user->skey,$type,$amount,$currency,$email,$name,$ret_url,$options);
            $src_id = $source->id;
            $src_status = $source->status==\Util\StripeUtility::STATUS_PENDING ? \Util\StripeUtility::STATUS_PENDING : \Util\StripeUtility::STATUS_FAILED;
            $this->saveNewEvent($src_status,$user->uuid,$name,$email,$amount,$src_id,$s_token);
        }
        return $source;
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

    private function saveNewEvent($status,$uuid,$name,$email,$amount,$skey,$s_token)
    {
        try{
            $event = new \App\Models\Event();
            $event->status = $status;
            $event->uuid = $uuid;
            $event->name = $name;
            $event->email = $email;
            $event->amount = $amount;
            $event->token = $s_token;
            $event->skey = $skey;
            $event->save();
            $this->setSessionVar(\Util\StripeUtility::SESSION_TOKEN,$s_token);
            return true;
        }catch(\Exception $e){
            return false;
        }
    }
}
