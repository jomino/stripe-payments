<?php

namespace Util;

class AcceptLanguage
{
    public function __construct($context)
    {
        $this->app = $context;
    }

    public function __invoke($request, $response, $next){
        $language = '';
        if ($request->hasHeader('Accept-Language')) {
            $accept_language = $request->getHeader('Accept-Language');
            $language = substr($accept_language[0],0,2);
        }
        $this->app->language = $language;
        return $next($request, $response);
    }

}