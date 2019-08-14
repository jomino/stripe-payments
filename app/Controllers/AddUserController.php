<?php

namespace App\Controllers;

class AddUserController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $ip = $this->session->get(\Util\StripeUtility::SESSION_REMOTE);
        if(false === $request->getAttribute(\App\Parameters::SECURITY['status'])){
            $this->logger->info('['.$ip.'] ADMIN_COOKIE_TIMEOUT -> REFRESH_LOGIN_NEEDED');
            return $response->withRedirect($this->router->pathFor('home'));
        }else{
            $this->logger->info('['.$ip.'] ADDUSER_PAGE_OPENED');
            return $this->view->render($response, 'Home/adduser.html.twig');
        }
    }
}
