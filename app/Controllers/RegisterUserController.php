<?php

namespace App\Controllers;

use \App\Models\User;

class RegisterUserController extends \Core\Controller
{
    private $errors = [];

    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $user_id = (int) $args['id']??0;
        if(empty($token) || $user_id==0){
            $this->logger->info('['.$ip.'] REGISTER_USER_BAD_REQUEST -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert('BAD_REQUEST'))->withStatus(403);
        }
        if($user=$this->validateUser($token,$user_id)){
            $datas = [ 'agence' => $user->name, 'email' => $user->email ];
            if($request->isGet()){
                $template_name = $route_name = 'register';
                $datas = array_merge( $datas, [
                    'generated_link' => $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor($route_name,[
                        'id' => $user->id,
                        'token' => '?'.$user->uuid
                    ])
                ]);
                $this->logger->info('['.$ip.'] REGISTER_NEWUSER_OPENED -> USER_ID: '.$user->id);
            }else{
                if(false === $request->getAttribute('csrf_status')){
                    $this->logger->info('['.$ip.'] REGISTER_NEWUSER_CSRF_REJECTED -> EXIT_WITH_403');
                    return $response->write($this->getSecurityAlert('CSRF_REJECTED'))->withStatus(403);
                }else{
                    if($this->register($user,$request->getParsedBody())){
                        $template_name = 'registered';
                        $webhook_url = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor( 'webhook', [
                            'token' => $user->uuid
                        ]);
                        $this->setupWebhook($user,$webhook_url);
                        $this->logger->info('['.$ip.'] REGISTER_NEWUSER_PROCEEDED -> USER_ID: '.$user->id);
                    }
                }
            }
        }
        if(sizeof($this->errors)>0){
            $errors = $this->getErrors();
            $this->logger->info('['.$ip.'] REGISTER_NEWUSER_ERROR -> WITH_ERRORS: '.$errors);
            return $response->write($this->getSecurityAlert($errors));
        }else{
            return $this->view->render($response, sprintf('Home/%s.html.twig',$template_name), $datas);
        }
    }

    private function validateUser($token,$user_id)
    {
        try{
            $user = User::findOrFail($user_id);
            if($user->uuid == $token){
                $dt_max = \Carbon\Carbon::now()->subDays(10);
                $dt_reg = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $user->updated_at);
                switch(true){
                    case ($dt_reg->timestamp-$dt_max->timestamp)<0:
                        $this->errors[] = 'La date de validité est dépassée';
                        return null;
                    case $user->active==1:
                        $this->errors[] = 'Votre compte est dèjà actif';
                        return null;
                    default:
                        return $user;
                }
            }else{
                $this->errors[] = 'Ceci n\'est pas un lien valide';
                return null;
            }
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            $this->errors[] = 'Vous n\'êtes pas un utilisateur enregistrer chez nous';
            return null;
        }
    }

    private function register($user,$args)
    {

        $pkey = $args['pkey'];
        $skey = $args['skey'];

        if(!empty($pkey) && !empty($skey)){
            if(preg_match('#^pk_#',$pkey) && preg_match('#^sk_#',$skey)){
                $user->pkey = $pkey;
                $user->skey = $skey;
                $user->active = 1;
                $user->save();
                return true;
            }else{
                $this->errors[] = 'L\'une et/ou l\'autre des clefs que vous avez fournies ne sont pas des clefs valides';
            }
        }else{
            $this->errors[] = 'L\'une et/ou l\'autre des clefs sont manquantes';
        }
        return false;

    }

    private function setupWebhook($user,$url)
    {
        $key = $user->skey;
        $response = \Util\StripeUtility::createWebhook($key,$url);
        if(isset($response->status) && $response->status==\Util\StripeUtility::WEBHOOK_STATUS_ENABLED){
            $user->wkey = $response->secret;
            $user->save();
            return true;
        }else{
            $this->errors[] = 'Nous sommes dans l\'impossibilité de vous relié à la plateforme stripe-webhook';
        }
        return false;
    }

    private function getErrors()
    {
        $error_str = '';
        $errors = $this->errors;
        array_map(function($error) use(&$error_str){
            $error_str .= $error."\n";
        },$errors);
        return $error_str;
    }

    private function getSecurityAlert($errors='')
    {
        $alert = '<h4 class="result mid-red">Alerte de sécurité  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></h4>';
        $message = 'Il nous est impossible de valider votre demande.<br>';
        $message .= 'Cela peut arriver dans les cas suivants:<br>';
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;Une tentative de ré-utilisation d\'un formulaire.<br>';
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;Un autre problème d\'ordre technique.<br>';
        if(!empty($errors)){
            $message .= '&nbsp;&nbsp;&nbsp;&nbsp;<span class="bold mid-red">';
            $message .= $errors;
            $message .= '</span><br>';
        }
        $message .= 'Vous pouvez contacter nos services à l\'adresse <a href="mailto:info@ipefix.com">info@ipefix.com</a>';
        $content = $this->view->fetch('Home/paymess.html.twig',[
            'alert' => $alert,
            'message' => $message
        ]);
        return $content;
    }
}
