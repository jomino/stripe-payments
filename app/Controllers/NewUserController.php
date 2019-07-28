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
            //return $this->view->render($response, 'Home/newuser-fail.html.twig', $datas);
            return $response->withStatus(498);
        }else{

            $token = \Util\UuidGenerator::v4();

            if($user=$this->saveNewUser($token,$agence,$email)){
                $uri = $request->getUri();
                $generated_link = $uri->getScheme().'://'.rtrim($uri->getHost(),'/').$this->router->pathFor('register',[
                    'id' => $user->id,
                    'token' => '?'.$token
                ]);
                $datas['generated_link'] = $generated_link;
                $sended = $this->sendUserMail($generated_link,$user);
                if(is_string($sended)){
                    $datas['error'] = 'Impossible d\'envoyer l\'e-mail à l\'adresse '.$user->email;
                    $datas['error'] .= '<br>Conserver le lien pour une utilisation ultérieur.';
                    $datas['error'] .= '<br>'.$sended;
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

        $mailer = new \Util\PHPMailer();
        return $mailer->send($user->email,$_subject,$_content);

    }

}
