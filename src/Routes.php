<?php

namespace Core;

class Routes
{
    public function __construct($app)
    {

        $container = $app->getContainer();

        $app->get('/', \App\Controllers\HomeController::class)->setName('home');

        $app->post('/login', \App\Controllers\LoginController::class)->setName('login');

        $app->group( '', function($app){
            $app->get('/adduser', \App\Controllers\AddUserController::class)->setName('adduser');
            $app->post('/newuser', \App\Controllers\NewUserController::class)->setName('newuser');
            $app->map(['GET','POST'],'/register/{id:[0-9]+}/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\RegisterUserController::class)->setName('register');
        })->add($container->get('csrf'))->add(new \App\Middleware\LoginMiddleware());
        
        $app->post('/1/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\StripeWebhookController::class)->setName('webhook');

        $app->post('/0/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class)->setName('payment');

        $app->get('/{path:js|css|fonts|images}/{file:[^/]+}', \App\Controllers\AssetsController::class);

        $app->group( '/payment', function($app){
            $app->get('/choice', \App\Controllers\StripePaymentController::class.':choice')->setName('payment-choice');
        })->add(new \App\Middleware\ReferrerMiddleware($app));
        
        $app->get('/infos', function($request, $response, $args){
            /* ob_start();
            phpinfo();
            $content = ob_get_contents();
            ob_end_flush(); */
            $content = $request->getAttribute('referrer');  
            return $response->write('Referer: '.$content);
        });

    }
}
