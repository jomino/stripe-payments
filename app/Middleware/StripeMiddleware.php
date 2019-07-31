<?php

namespace Middleware;

class StripeMiddleware
{
    public function __construct($context)
    {
        $this->app = $context;
    }

    public function __invoke($request, $response, $next){
        $logger = $this->app->getContainer()->get('logger');
        \Stripe\Stripe::setLogger($logger);
        return $next($request, $response);
    }

}