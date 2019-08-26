<?php

namespace App\Controllers;

use \App\Models\Client;

class NewClientController extends \Core\Controller
{

    private $errors = [];

    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');

        $datas = $request->getParsedBody();

        $name = $datas['name'];
        $email = $datas['email'];
        $pwd = $datas['pwd'];

        unset($datas['pwd']);

        if(false === $request->getAttribute('csrf_status')){
            $this->logger->info('['.$ip.'] ADDCLIENT_CSRF_REJECTED -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert())->withStatus(403);
        }elseif(empty($name) || empty($email) || empty($pwd)){
            $this->logger->info('['.$ip.'] ADDCLIENT_EMPTY_FIELDS_REJECTION');
            return $response->write($this->getSecurityAlert('Des champs sont manquants ou incomplets'))->withStatus(403);
        }else{

            $token = \Util\UuidGenerator::v4();

            if($this->validateClient($email)){
                if($client=$this->createNewClient($token,$name,$email,$pwd)){
                    $uri = $request->getUri();
                    $datas['send_at'] = (\Carbon\Carbon::now())->format('H:i:s');
                    $register_link = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('validate',[
                        'id' => $client->id,
                        'token' => '?'.$client->uuid
                    ]);
                    if($this->sendClientMail($register_link,$datas)){
                        $this->logger->info('['.$ip.'] ADDCLIENT_SUCCESS_EMAIL -> '.$email);
                        $this->logger->info('['.$ip.'] ADDCLIENT_SUCCESS_EMAIL -> REGISTER_URL:'.$register_link);
                    }else{
                        $client->delete();
                    }
                }
            }

            if(sizeof($this->errors)>0){
                $errors = $this->getErrors();
                $this->logger->info('['.$ip.'] ADDCLIENT_CREATE_ERROR -> WITH_ERRORS: '.$errors);
                $datas['error'] = $errors;
            }

            return $this->view->render($response, 'Home/newclient.html.twig', $datas);

        }
    }

    private function validateClient($email)
    {
        try{
            $client = Client::where('email',$email)->firstOrFail();
            $this->errors[] = 'Vous êtes déjà enregistrer avec cet e-mail';
            return false;
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return true;
        }
    }

    private function createNewClient($token,$name,$email,$pwd)
    {
        try{
            $client = new Client();
            $client->name = $name;
            $client->email = $email;
            $client->pwd = hash('sha256', $pwd);
            $client->uuid = $token;
            $client->save();
            return $client;
        }catch(\Exception $e){
            $this->errors[] = 'Impossible d\'écrire dans la base de donnée';
            return null;
        }
    }

    private function sendClientMail($link,$datas)
    {
        $_tpl = 'Email/email-newclient.html.twig';
        $_subject = 'Inscription au service Stripe-Payments d\'Ipefix';
        
        $_content = $this->view->fetch( $_tpl, [
            'name' => $datas['name'],
            'send_at' => $datas['send_at'],
        ]);

        $mailer = new \Util\PhpMailer();
        $sended = $mailer->send($datas['email'],$_subject,$_content);

        if(is_string($sended)){
            $error = 'Impossible d\'envoyer l\'e-mail à l\'adresse '.$datas['email']." \n";
            $error .= 'Erreur: '.$sended." \n";
            $this->errors[] = $error;
            return false;
        }

        return true;
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

    private function getSecurityAlert($alert='')
    {
        $alert = '<h4 class="result mid-red">'.(!empty($alert) ? $alert:'Alert de sécurité').'  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></h4>';
        $message = 'Il nous est impossible de valider votre demande.<br>';
        $message .= 'Cela peut arriver dans les cas suivants:<br>';
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;Une tentative de ré-utilisation d\'un formulaire.<br>';
        $message .= '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;Un autre problème d\'ordre technique.<br>';
        $message .= 'Vous pouvez contacter nos services à l\'adresse <a href="mailto:info@ipefix.com">info@ipefix.com</a>';
        $content = $this->view->fetch('Home/paymess.html.twig',[
            'alert' => $alert,
            'message' => $message
        ]);
        return $content;
    }

}
