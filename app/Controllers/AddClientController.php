<?php

namespace App\Controllers;

class AddClientController extends \Core\Controller
{
    public function __invoke($request, $response, $args)
    {
        $ip = $request->getServerParam('REMOTE_ADDR');
        $this->logger->info('['.$ip.'] ADDCLIENT_PAGE_OPENED');
        return $this->view->render($response, 'Home/addclient.html.twig');
    }
}
