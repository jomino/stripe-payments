<?php

/*
 *
 * @dev jomino2017
 * 
 */

namespace Core;

class Middleware
{
    public function __construct($app)
    {
        $app->add(new \Util\AcceptLanguage($app));
        $app->add(new \App\Middleware\StripeMiddleware($app));
        $app->add(new \App\Middleware\ReferrerMiddleware());
    }

}