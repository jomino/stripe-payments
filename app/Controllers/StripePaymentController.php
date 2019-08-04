<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        return $this->view->render($response, 'Home/paystart.html.twig');
    }
}
