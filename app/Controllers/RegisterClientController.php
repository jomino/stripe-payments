<?php

namespace App\Controllers;

use \App\Models\Client;

class RegisterClientController extends \Core\Controller
{
    private $errors = [];

    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $client_id = (int) $args['id']??0;
        if(empty($token) || $client_id==0){
            $this->logger->info('['.$ip.'] REGISTER_CLIENT_BAD_REQUEST -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert('BAD_REQUEST'))->withStatus(403);
        }
        if($client=$this->validateClient($token,$client_id)){
            $datas = [ 'name' => $client->name, 'email' => $client->email ];
            $client->active = 1;
            $client->save();
        }
        if(sizeof($this->errors)>0){
            $errors = $this->getErrors();
            $this->logger->info('['.$ip.'] REGISTER_NEWCLIENT_ERROR -> WITH_ERRORS: '.$errors);
            return $response->write($this->getSecurityAlert($errors));
        }else{
            $this->logger->info('['.$ip.'] REGISTER_NEWCLIENT_SUCCEED -> CLIENT_ID: '.$client->id);
            return $this->view->render($response, 'Home/validate.html.twig', $datas);
        }
    }

    private function validateClient($token,$client_id)
    {
        try{
            $client = Client::findOrFail($client_id);
            if($client->uuid == $token){
                $dt_max = \Carbon\Carbon::now()->subHour();
                $dt_reg = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $client->updated_at);
                switch(true){
                    case ($dt_reg->timestamp-$dt_max->timestamp)<0:
                        $this->errors[] = 'La date de validité est dépassée';
                        return null;
                    case $client->active==1:
                        $this->errors[] = 'Votre compte est dèjà actif';
                        return null;
                    default:
                        return $client;
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
