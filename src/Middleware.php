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
        $app->add(new \Slim\Middleware\Session([
            'name' => 'application_session',
            'autorefresh' => false,
            'lifetime' => '10 minutes'
        ]));

        $app->add(function($request, $response, $next){
            \Carbon\Carbon::setLocale('fr');
            return $next($request, $response);
        });

        $app->add(new \Util\AcceptLanguage($app));
        $app->add(new \App\Middleware\StripeMiddleware($app));

    }

}