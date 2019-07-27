<?php

namespace App\Controllers;

use \App\Models\User;

class NewUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        if(false === $request->getAttribute('csrf_status')){
            return $this->view->render($response, 'Home/newuser-fail.html.twig',[
                'email' => 'email@example.com'
            ]);
        }else{
            $uri = $request->getUri();
            $parsedBody = $request->getParsedBody();
            $agence = $parsedBody['agence'];
            $email = $parsedBody['email'];
            $token = \Util\UuidGenerator::v4();
            if($user_id=$this->saveNewUser($token,$agence,$email)){
                $register_link = $uri->getScheme().'://'.rtrim($uri->getHost(),'/').$this->router->pathFor('register',[
                    'id' => $user_id,
                    'token' => '?'.$token
                ]);
                return $this->view->render($response, 'Home/newuser.html.twig',[
                    'agence' => $agence,
                    'email' => $email,
                    'generated_link' => $register_link
                ]);
            }else{
                return $this->view->render($response, 'Home/newuser.html.twig',[
                    'agence' => $agence,
                    'email' => $email,
                    'generated_link' => 'unknow'
                ]);
            }
        }
    }

    private function saveNewUser($token,$agence,$email)
    {

        $user = new User();
        $user->name = $agence;
        $user->email = $email;
        $user->uuid = $token;

        $user->save();

        return $user->id;
    }
}
