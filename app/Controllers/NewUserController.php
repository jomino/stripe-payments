<?php

namespace App\Controllers;

use \App\Models\User;

class NewUserController extends \Core\Controller
{

    private $errors = [];

    public function __invoke($request, $response, $args)
    {

        $parsedBody = $request->getParsedBody();

        $agence = $parsedBody['agence'];
        $email = $parsedBody['email'];

        $datas = [
            'agence' => $agence,
            'email' => $email
        ];

        if(false === $request->getAttribute('csrf_status')){
            return $response->withStatus(403);
        }elseif(false === $request->getAttribute(\App\Parameters::SECURITY['status'])){
            return $response->withRedirect($this->router->pathFor('home'));
        }else{

            $token = \Util\UuidGenerator::v4();

            if($user=$this->createNewUser($token,$agence,$email)){
                $uri = $request->getUri();
                $register_link = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('register',[
                    'id' => $user->id,
                    'token' => '?'.$user->uuid
                ]);
                if($this->sendUserMail($register_link,$user)){
                    $datas['generated_link'] = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('payment_start',[
                        'token' => $user->uuid,
                        'amount' => '12300',
                        'product' => 'my-product-to-sell'
                    ]);
                }else{
                    $user->delete();
                }
            }

            if(sizeof($this->errors)>0){
                $datas['error'] = $this->getErrors();
            }

            return $this->view->render($response, 'Home/newuser.html.twig', $datas);

        }
    }

    private function createNewUser($token,$agence,$email)
    {
        if($this->validateUser($agence)){
            try{
                $user = new User();
                $user->name = $agence;
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

    private function validateUser($agence)
    {
        try{
            $count = User::where('name',$agence)->count();
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

}
