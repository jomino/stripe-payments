<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function choice($request, $response, $args)
    {
        return $this->view->render($response, 'Home/payselect.html.twig');
    }
}
