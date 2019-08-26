<?php

namespace App\Controllers;

class HomeController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $cookie = \Util\Tools::cookieGetValue(\Dflydev\FigCookies\FigRequestCookies::get($request, \App\Parameters::SECURITY['cookie'], 'none'));
        if($this->session->exists(\Util\StripeUtility::SESSION_LOGIN)){
            $pass_phrase = $this->session->get(\Util\StripeUtility::SESSION_LOGIN).'-'.\App\Parameters::SECURITY['secret'];
        }
        if(!empty($pass_phrase) && $cookie==hash('sha256', $pass_phrase)){
            $this->logger->info('['.$ip.'] ADMIN_ALREADY_LOGGED');
            return $response->withRedirect($this->router->pathFor('adduser'));
        }else{
            $this->logger->info('['.$ip.'] ADMIN_LOGIN_FAILED');
            if($cookie!='none'){ $response = \Dflydev\FigCookies\FigResponseCookies::remove($response, \App\Parameters::SECURITY['cookie']); }
            return $this->view->render($response, 'Home/login.html.twig');
        }
    }
}
