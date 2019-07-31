<?php

namespace App\Controllers;

use \App\Models\User;

class NewUserController extends \Core\Controller
{

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
            return $response->withStatus(498);
        }elseif(false === $request->getAttribute(\App\Parameters::SECURITY['status'])){
            return $response->withRedirect($this->router->pathFor('home'));
        }else{

            $token = \Util\UuidGenerator::v4();

            if($user=$this->saveNewUser($token,$agence,$email)){
                $uri = $request->getUri();
                $generated_link = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('register',[
                    'id' => $user->id,
                    'token' => '?'.$user->uuid
                ]);
                $sended = $this->sendUserMail($generated_link,$user);
                if(is_string($sended)){
                    $datas['error'] = 'Impossible d\'envoyer l\'e-mail à l\'adresse '.$email;
                    $datas['error'] .= '<br>'.$sended;
                    $user->delete();
                }else{
                    $datas['generated_link'] = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('payment',[
                        'token' => '?'.$user->uuid
                    ]);
                }
            }else{
                $datas['error'] = 'Impossible d\'écrire dans la base de donnée.';
                $datas['error'] .= '<br>Contactez votre administrateur ...';
            }

            return $this->view->render($response, 'Home/newuser.html.twig', $datas);

        }
    }

    private function saveNewUser($token,$agence,$email)
    {
        try{
            //todo verify for duplicate e-mail & name
            $user = new User();
            $user->name = $agence;
            $user->email = $email;
            $user->uuid = $token;
            $user->save();
            return $user;
        }catch(\Exception $e){
            return null;
        }
    }

    private function sendUserMail($link,$user)
    {
        $_tpl = 'Email/email-inlined.html.twig';
        $_subject = 'Inscription au service Stripe-Payments d\'Ipefix';
        
        $_content = $this->view->fetch( $_tpl, [
            'agence' => $user->name,
            'link' => $link,
        ]);

        $mailer = new \Util\PhpMailer();
        return $mailer->send($user->email,$_subject,$_content);

    }

}
