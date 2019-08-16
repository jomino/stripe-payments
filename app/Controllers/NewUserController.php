<?php

namespace App\Controllers;

use \App\Models\User;

class NewUserController extends \Core\Controller
{

    private $errors = [];

    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');

        $datas = $request->getParsedBody();

        $domain = $datas['domain'];
        $email = $datas['email'];

        if(false === $request->getAttribute('csrf_status')){
            $this->logger->info('['.$ip.'] ADDUSER_CSRF_REJECTED -> EXIT_WITH_403');
            return $response->write($this->getSecurityAlert())->withStatus(403);
        }elseif(false === $request->getAttribute(\App\Parameters::SECURITY['status'])){
            $this->logger->info('['.$ip.'] ADDUSER_TIMEOUT_REJECTED -> HOME_REDIR');
            return $response->withRedirect($this->router->pathFor('home'));
        }else{

            $token = \Util\UuidGenerator::v4();

            if($user=$this->createNewUser($token,$domain,$email)){
                $uri = $request->getUri();
                $register_link = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('register',[
                    'id' => $user->id,
                    'token' => '?'.$user->uuid
                ]);
                if($this->sendUserMail($register_link,$user)){
                    $this->logger->info('['.$ip.'] ADDUSER_SUCCESS_EMAIL -> '.$email);
                    $this->logger->info('['.$ip.'] ADDUSER_SUCCESS_EMAIL -> REGISTER_URL:'.$register_link);
                    $datas['generated_link'] = $uri->getScheme().'://'.$uri->getHost().'/'.$user->uuid.'/';
                }else{
                    $user->delete();
                }
            }

            if(sizeof($this->errors)>0){
                $errors = $this->getErrors();
                $this->logger->info('['.$ip.'] ADDUSER_CREATE_ERROR -> WITH_ERRORS: '.$errors);
                $datas['error'] = $errors;
            }

            return $this->view->render($response, 'Home/newuser.html.twig', $datas);

        }
    }

    private function createNewUser($token,$domain,$email)
    {
        if($this->validateUser($domain)){
            try{
                $user = new User();
                $user->name = $domain;
                $user->email = $email;
                $user->uuid = $token;
                $user->save();
                return $user;
            }catch(\Exception $e){
                $this->errors[] = 'Impossible d\'écrire dans la base de donnée';
                return null;
            }
        }else{
            $this->errors[] = 'Ce client est déjà inscrit';
            return null;
        }
    }

    private function validateUser($domain)
    {
        try{
            $count = User::where('name',$domain)->count();
            return $count==0;
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return false;
        }

    }

    private function sendUserMail($link,$user)
    {
        $_tpl = 'Email/email-newuser.html.twig';
        $_subject = 'Inscription au service Stripe-Payments d\'Ipefix';
        
        $_content = $this->view->fetch( $_tpl, [
            'agence' => $user->name,
            'link' => $link,
        ]);

        $mailer = new \Util\PhpMailer();
        $sended = $mailer->send($user->email,$_subject,$_content);

        if(is_string($sended)){
            $error = 'Impossible d\'envoyer l\'e-mail à l\'adresse '.$user->email." \n";
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

    private function getSecurityAlert()
    {
        $alert = '<h4 class="result mid-red">Alerte de sécurité  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span></h4>';
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
