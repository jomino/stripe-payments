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
            return $this->view->render($response, 'Home/newuser.html.twig',[
                'email' => 'email@example.com',
                'generated_link' => 'uuid4...'
            ]);
        }
    }
}
