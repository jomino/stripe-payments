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
            // $user_id = $this->saveNewUser($token);
            $register_link = $uri->getScheme().'://'.$uri->getHost().'/'.$this->router->pathFor('register',[
                'id' => '1', // $user_id
                'token' => '?'.$token
            ]);
            return $this->view->render($response, 'Home/newuser.html.twig',[
                'agence' => $agence,
                'email' => $email,
                'generated_link' => $register_link
            ]);
        }
    }

    private function saveNewUser($token){
        // todo
    }
}
