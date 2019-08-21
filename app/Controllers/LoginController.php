<?php

namespace App\Controllers;

class LoginController extends \Core\Controller  
{
    public function __invoke($request, $response, $args)
    {
        $login = $request->getParsedBodyParam('login');
        $ip = $request->getServerParam('REMOTE_ADDR');
        if($login==\App\Parameters::SECURITY['login']){
            $pass_phrase = \App\Parameters::SECURITY['login'].'-'.\App\Parameters::SECURITY['secret'];
            $response = \Dflydev\FigCookies\FigResponseCookies::set($response, \Dflydev\FigCookies\SetCookie::create(\App\Parameters::SECURITY['cookie'])
                ->withPath('/')
                ->withValue(hash('sha256', $pass_phrase))
                ->withMaxAge(30*60)
                ->withDomain($request->getUri()->getHost())
                ->withSecure(true)
                ->withHttpOnly(true)
            );
            $this->logger->info('['.$ip.'] ADMIN_LOGIN_SUCCESS');
            return $response->withRedirect($this->router->pathFor('adduser'), 301);
        }
        $this->logger->info('['.$ip.'] ADMIN_LOGIN_ERROR');
        return $response->withRedirect($this->router->pathFor('home'), 301);
    }
}