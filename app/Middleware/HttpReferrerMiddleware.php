<?php

namespace App\Middleware;

class HttpReferrerMiddleware
{

    public $container;

    public function __construct($app)
    {
        $this->container = $app->getContainer();
    }

    public function __invoke($request, $response, $next){
        $session = $this->container->get('session');
        if(!$session->exists(\Util\StripeUtility::SESSION_DOMAIN)){
            $domain = $request->getHeaderLine('Referer');
            if(empty($domain)){
                $domain = $request->getServerParam('HTTP_REFERER', '');
            }
            $domain = preg_replace('#^(?:http[s]?://)?([a-z0-9\-._~%]+)(?:/?.*)$#i','$1',$domain);
            $session->set(\Util\StripeUtility::SESSION_DOMAIN,$domain);
        }
        return $next($request, $response);
    }

}