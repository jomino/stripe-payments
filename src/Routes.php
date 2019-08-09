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
        $app->post('/1/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripeWebhookController::class)->setName('webhook');

        // Payments
        $app->group( '', function($app){
            $app->get('/{token:[0-9a-zA-Z-]*}/{amount:[0-9]*}/{product:[0-9a-zA-Z-_]+}', \App\Controllers\StripePaymentController::class.':start')->setName('payment_start');
            $app->post('/identify', \App\Controllers\StripePaymentController::class.':identify')->setName('payment_identify');
            $app->post('/source', \App\Controllers\StripePaymentController::class.':source')->setName('payment_source');
        })->add($container->get('csrf'));
        
        $app->get('/result/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class.':result')->setName('payment_result');
        $app->get('/check/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class.':check')->setName('payment_check');
        
        // Infos
        $app->get('/infos', function($request, $response, $args){
            /* ob_start();
            phpinfo();
            $content = ob_get_contents();
            ob_end_flush(); */
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response);
        });
        
        // Debug
        $app->get('/debug', function($request, $response, $args){
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response);
        });

    }
}
