<?php

namespace App\Controllers;

use \App\Models\User;

class RegisterUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $token = (string) ltrim($request->getQuery(),'?');
        if(empty($token) || strlen($token)<2){
            $token = ltrim($args['token'],'?');
        }
        return $this->view->render($response, 'Home/register.html.twig',[
            'agence' => 'agence',
            'email' => 'email',
            'token' => $token
        ]);
    }
}
