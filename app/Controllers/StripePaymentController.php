<?php

namespace App\Controllers;

class StripePaymentController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $notFoundHandler = $this->get('notFoundHandler');
        return $notFoundHandler($request, $response);
    }
}
