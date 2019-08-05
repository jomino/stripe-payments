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
        $app->group( '/0/{token:\??[0-9a-zA-Z-]*}', function($app){
            $app->get('', \App\Controllers\StripePaymentController::class)->setName('payment-start');
            $app->post('', \App\Controllers\StripePaymentController::class)->setName('payment-source');
        });
        
        // Infos/Debug
        $app->get('/infos', function($request, $response, $args){
            ob_start();
            phpinfo();
            $content = ob_get_contents();
            ob_end_flush();
        });

    }
}
