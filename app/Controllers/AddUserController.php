<?php

namespace App\Controllers;

class AddUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $secret = \App\Parameters::SECURITY['secret'];
        $parsedBody = $request->getParsedBody();
        $skey = $parsedBody['skey'];
        if($secret!=$skey){
            return $response->withStatus(403);
        }
        return $this->view->render($response, 'Home/adduser.html.twig');
    }
}
