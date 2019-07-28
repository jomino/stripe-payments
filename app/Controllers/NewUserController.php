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
                if(!$this->sendUserMail($generated_link,$user)){
                    //todo: push error message
                    $datas['error'] = 'Impossible d\'envoyer l\'e-mail à l\'adresse '.$email;
                    $datas['error'] .= '<br>Conserver le lien pour une utilisation ultérieur.';
                }
            }else{
                $datas['error'] = 'Impossible d\'écrire dans la base de donnée.';
                $datas['error'] .= '<br>Contactez Olivier ...';
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
        //todo: send mail
        return true;
    }

}
