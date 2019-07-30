<?php

namespace App\Controllers;

class AddUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        if(false === $request->getAttribute(\App\Parameters::SECURITY['status'])){
            return $response->withRedirect($this->router->pathFor('home'));
        }else{
            return $this->view->render($response, 'Home/adduser.html.twig');
        }
    }
}
