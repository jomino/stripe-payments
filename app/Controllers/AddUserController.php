<?php

namespace App\Controllers;

class AddUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        return $this->view->render($response, 'Home/adduser.html.twig');
    }
}
