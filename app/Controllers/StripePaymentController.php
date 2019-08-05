<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function start($request, $response, $args)
    {
        $uri = $request->getUri();
        $token = (string) ltrim($uri->getQuery(),'?');
        $amount = $args['amount'];
        if(empty($token) || strlen($token)<2){ $token = ltrim($args['token'],'?'); }
        $this->setSessionVar('referrer',$token);
        $this->setSessionVar('amount',$amount);
        return $this->view->render($response, 'Home/paystart.html.twig');
    }

    public function identify($request, $response, $args)
    {
        $payment_type = $request->getParsedBodyParam('payment-type');
        $this->setSessionVar('payment_type',$payment_type);
        $notFoundHandler = $this->notFoundHandler;
        return $notFoundHandler($request, $response);
    }

    public function source($request, $response, $args)
    {
        $notFoundHandler = $this->notFoundHandler;
        return $notFoundHandler($request, $response);
    }

    public function result($request, $response, $args)
    {
        $notFoundHandler = $this->notFoundHandler;
        return $notFoundHandler($request, $response);
    }

    private function setSessionVar($name,$value)
    {
        if($this->session->exists($name)){
            $this->session->delete($name);
        }
        $this->session->set($name,$value);
    }
}
