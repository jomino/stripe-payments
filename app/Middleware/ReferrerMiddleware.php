<?php

namespace App\Middleware;

class ReferrerMiddleware
{

    public function __invoke($request, $response, $next){
        $session = $this->container->get('session');
        if(!$session->exists('referrer')){
            $referrer = $request->getHeaderLine('Referer');
            if(empty($referrer)){
                $referrer = $request->getServerParam('HTTP_REFERER', '');
            }
            $referrer = preg_replace('#^(?:http[s]?://)?([a-z0-9\-._~%]+)(?:/?.*)$#i','$1',$referrer);
            $session->set('referrer',$referrer);
        }else{
            $referrer = $session->get('referrer');
        }
        $request = $request->withAttribute('referrer',$referrer);
        return $next($request, $response);
    }

}