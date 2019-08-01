<?php

namespace App\Middleware;

class StripeMiddleware
{
    public function __construct($context)
    {
        $this->app = $context;
    }

    public function __invoke($request, $response, $next){
        $logger = $this->app->getContainer()->get('logger');
        \Stripe\Stripe::setLogger($logger);
        $stripe_signature = $request->getHeaderLine('stripe-signature');
        $request = $request->withAttribute('stripe_signature',$stripe_signature);
        return $next($request, $response);
    }

}