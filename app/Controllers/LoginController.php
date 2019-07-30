<?php

namespace App\Controllers;

class LoginController extends \Core\Controller  
{
    public function __invoke($request, $response, $args)
    {
        $parsedBody = $request->getParsedBody();
        if($parsedBody['login']==\App\Parameters::SECURITY['login']){
            $pass_phrase = \App\Parameters::SECURITY['login'].'-'.\App\Parameters::SECURITY['secret'];
            $hash = hash('sha256', $pass_phrase);
            $response = \Dflydev\FigCookies\FigResponseCookies::set($response, \Dflydev\FigCookies\SetCookie::create(\App\Parameters::SECURITY['cookie'])
                ->withPath('/')
                ->withValue($hash)
                ->withMaxAge(30*60)
                ->withDomain($request->getUri()->getHost())
                ->withSecure(true)
                ->withHttpOnly(true)
            );
            return $response->withRedirect($this->router->pathFor('adduser'), 301);
        }else{
            return $response->withRedirect($this->router->pathFor('home'), 301);
        }
    }
}