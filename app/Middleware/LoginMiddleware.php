<?php

namespace App\Middleware;

class LoginMiddleware
{

    public $container;

    public function __construct($app)
    {
        $this->container = $app->getContainer();
    }

    public function __invoke($request, $response, $next){
        $session = $this->container->get('session');
        $hash = hash('sha256', $session->get(\Util\StripeUtility::SESSION_LOGIN).'-'.\App\Parameters::SECURITY['secret']);
        $cookie = \Util\Tools::cookieGetValue(\Dflydev\FigCookies\FigRequestCookies::get($request, \App\Parameters::SECURITY['cookie'], 'none'));
        $request = $request->withAttribute(\App\Parameters::SECURITY['status'], $cookie==$hash);
        return $next($request, $response);
    }

}