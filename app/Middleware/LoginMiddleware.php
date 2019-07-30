<?php

namespace App\Middleware;

class LoginMiddleware
{

    public function __invoke($request, $response, $next){
        $hash = hash('sha256', \App\Parameters::SECURITY['login'].'-'.\App\Parameters::SECURITY['secret']);
        $cookie = \Util\Tools::cookieGetValue(\Dflydev\FigCookies\FigRequestCookies::get($request, \App\Parameters::SECURITY['cookie'], 'none'));
        $request = $request->withAttribute(\App\Parameters::SECURITY['status'], $cookie==$hash);
        return $next($request, $response);
    }

}