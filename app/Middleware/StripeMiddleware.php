<?php

namespace Middleware;

class StripeMiddleware
{
    public function __construct($context)
    {
        $this->app = $context;
    }

    public function __invoke($request, $response, $next){
        \Stripe\Stripe::setApiKey(\App\Parameters::STRIPE['sk']);
        return $next($request, $response);
    }

}