<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $this->session->set('referrer',$token);
        return $this->view->render($response, 'Home/paystart.html.twig');
    }
}
