<?php

namespace App\Controllers;

class LoginController extends \Core\Controller  
{
    public function __invoke($request, $response, $args)
    {
        $parsedBody = $request->getParsedBody();
        $cookie = \Util\Tools::cookieGetValue(\Dflydev\FigCookies\FigRequestCookies::get($request, \App\Parameters::SECURITY['cookie'], 'none'));
        $pass_phrase = \App\Parameters::SECURITY['login'].'-'.\App\Parameters::SECURITY['secret'];
        $hash = hash('sha256', $pass_phrase);
        if($cookie=='none'){
            if($parsedBody['login']==\App\Parameters::SECURITY['login']){
                $dtc = \Carbon\Carbon::now(new \DateTimeZone('Europe/Brussels'));
                $dtc->addMinutes(30);
                $response = \Dflydev\FigCookies\FigResponseCookies::set($response, \Dflydev\FigCookies\SetCookie::create(\App\Parameters::SECURITY['cookie'])
                    ->withPath('/')
                    ->withValue($hash)
                    ->withExpires($dtc->toCookieString())
                    ->withDomain($request->getUri()->getHost())
                );
            }else{
                return $response->withStatus(403);
            }
        }else{
            if($cookie!=$hash){
                return $response->withStatus(403);
            }
        }
        return $response->withRedirect($this->router->pathFor('adduser'), 301);
    }
}