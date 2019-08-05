<?php

namespace Core;

class Routes
{
    public function __construct($app)
    {

        $container = $app->getContainer();

        // Login
        $app->get('/', \App\Controllers\HomeController::class)->setName('home');
        $app->post('/login', \App\Controllers\LoginController::class)->setName('login');

        // Assets
        $app->get('/{path:js|css|fonts|images}/{file:[^/]+}', \App\Controllers\AssetsController::class);

        // Admin
        $app->group( '', function($app){
            $app->get('/adduser', \App\Controllers\AddUserController::class)->setName('adduser');
            $app->post('/newuser', \App\Controllers\NewUserController::class)->setName('newuser');
            $app->map(['GET','POST'],'/register/{id:[0-9]+}/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\RegisterUserController::class)->setName('register');
        })->add($container->get('csrf'))->add(new \App\Middleware\LoginMiddleware());
        
        // Webhook
        $app->post('/1/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\StripeWebhookController::class)->setName('webhook');

        // Payments
        $app->group( '', function($app){
            $app->get('/{token:[0-9a-zA-Z-]*}/{amount:[0-9]*}', \App\Controllers\StripePaymentController::class.':start')->setName('payment_start');
            $app->post('/identify', \App\Controllers\StripePaymentController::class.':identify')->setName('payment_identify');
            $app->post('/source', \App\Controllers\StripePaymentController::class.':source')->setName('payment_source');
            $app->get('/result/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class.':result')->setName('payment_result');
        })->add($container->get('csrf'));
        // Infos/Debug
        $app->get('/infos', function($request, $response, $args){
            ob_start();
            phpinfo();
            $content = ob_get_contents();
            ob_end_flush();
        });

    }
}
