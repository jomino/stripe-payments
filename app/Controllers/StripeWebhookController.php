<?php

namespace App\Controllers;

class StripeWebhookController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        return $response->withJson([ 'status' => 'success' ])->withStatus(200);
    }
}
