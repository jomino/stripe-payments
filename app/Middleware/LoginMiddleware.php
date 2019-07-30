<?php

namespace App\Middleware;

use App\Parameters;

use Dflydev\FigCookies\FigRequestCookies;

class LoginMiddleware
{

    public function __invoke($request, $response, $next){
        $hash = hash('sha256', Parameters::SECURITY['login'].'-'.Parameters::SECURITY['secret']);
        $cookie = Tools::cookieGetValue(FigRequestCookies::get($request, Parameters::SECURITY['cookie'], 'none'));
        $request = $request->withAttribute(Parameters::SECURITY['status'], $cookie==$hash);
        return $next($request, $response);
    }

}