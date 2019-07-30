<?php

namespace App\Controllers;

use App\Parameters;

use Carbon\Carbon;

use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\FigRequestCookies;

class LoginController extends \Core\Controller  
{
    public function __invoke($request, $response, $args)
    {
        $parsedBody = $request->getParsedBody();
        $cookie = FigRequestCookies::get($request, Parameters::SECURITY['cookie'], 'none');
        $pass_phrase = Parameters::SECURITY['login'].'-'.Parameters::SECURITY['secret'];
        $hash = hash('sha256', $pass_phrase);
        $this->logger->info('cookie name: '.Parameters::SECURITY['cookie']);
        $this->logger->info('cookie value: '.$cookie);
        $this->logger->info('login value: '.$parsedBody['login']);
        if($cookie=='none'){
            if($parsedBody['login']==Parameters::SECURITY['login']){
                $dtc = Carbon::now()->add(15,'minutes');
                $response = FigResponseCookies::set($response, SetCookie::create(Parameters::SECURITY['cookie'])
                    ->withPath('/')
                    ->withValue($hash)
                    ->withExpires($dtc->toRfc7231String())
                    ->withDomain($request->getUri()->getHost())
                );
            }else{
                return $response->withStatus(403);
            }
        }else{
            if($cookie!=$hash){
                return $response->withStatus(403);
            }
        }
        return $response->withRedirect($this->router->pathFor('adduser'), 301);
    }
}