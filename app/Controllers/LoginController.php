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
                $response = \Dflydev\FigCookies\FigResponseCookies::set($response, \Dflydev\FigCookies\SetCookie::create(\App\Parameters::SECURITY['cookie'])
                    ->withPath('/')
                    ->withValue($hash)
                    ->withMaxAge(30*60)
                    ->withDomain($request->getUri()->getHost())
                    ->withSecure(true)
                    ->withHttpOnly(true)
                );
            }else{
                $response = \Dflydev\FigCookies\FigResponseCookies::remove($response, \App\Parameters::SECURITY['cookie']);
                return $response->withStatus(403);
            }
        }else{
            if($cookie!=$hash){
                $response = \Dflydev\FigCookies\FigResponseCookies::remove($response, \App\Parameters::SECURITY['cookie']);
                return $response->withStatus(403);
            }
        }
        return $response->withRedirect($this->router->pathFor('adduser'), 301);
    }
}