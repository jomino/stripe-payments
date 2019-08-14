<?php

namespace App\Controllers;

class HomeController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $cookie = \Util\Tools::cookieGetValue(\Dflydev\FigCookies\FigRequestCookies::get($request, \App\Parameters::SECURITY['cookie'], 'none'));
        $pass_phrase = \App\Parameters::SECURITY['login'].'-'.\App\Parameters::SECURITY['secret'];
        if($cookie!=hash('sha256', $pass_phrase)){
            $this->logger->info('['.$ip.'] ADMIN_LOGIN_OPENED');
            if($cookie!='none'){ $response = \Dflydev\FigCookies\FigResponseCookies::remove($response, \App\Parameters::SECURITY['cookie']); }
            return $this->view->render($response, 'Home/login.html.twig');
        }else{
            return $response->withRedirect($this->router->pathFor('adduser'), 301);
        }
    }
}
