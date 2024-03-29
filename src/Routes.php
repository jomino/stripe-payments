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
        
        $app->get('/validate/{id:[0-9]+}/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\RegisterClientController::class)->setName('validate');

        // Assets
        $app->get('/{path:js|css|fonts|images}/{file:[^/]+}', \App\Controllers\AssetsController::class);

        // Admin
        $app->group( '', function($app){
            $app->get('/addclient', \App\Controllers\AddClientController::class)->setName('addclient');
            $app->get('/adduser', \App\Controllers\AddUserController::class)->setName('adduser');
            $app->post('/newclient', \App\Controllers\NewClientController::class)->setName('newclient');
            $app->post('/newuser', \App\Controllers\NewUserController::class)->setName('newuser');
            $app->map(['GET','POST'],'/register/{id:[0-9]+}/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\RegisterUserController::class)->setName('register');
        })->add($container->get('csrf'))->add(new \App\Middleware\LoginMiddleware($app));
        
        // Webhook
        $app->post('/1/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripeWebhookController::class)->setName('webhook');

        // Payments
        $app->group( '', function($app){
            $app->get('/{token:[0-9a-zA-Z-]*}/{amount:[0-9]*}/{product:[0-9a-zA-Z-_]+}', \App\Controllers\StripePaymentController::class.':start')->setName('payment_start');
            $app->post('/identify', \App\Controllers\StripePaymentController::class.':identify')->setName('payment_identify');
            $app->post('/source', \App\Controllers\StripePaymentController::class.':source')->setName('payment_source');
            $app->post('/charge', \App\Controllers\StripePaymentController::class.':charge')->setName('payment_charge');
        })->add($container->get('csrf'))->add(new \App\Middleware\HttpReferrerMiddleware($app));
        
        $app->get('/result/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class.':result')->setName('payment_result');
        $app->get('/check/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class.':check')->setName('payment_check');
        $app->get('/print/{token:[0-9a-zA-Z-]*}', \App\Controllers\StripePaymentController::class.':print')->setName('payment_print');
        
        // Infos
        $app->get('/infos', function($request, $response, $args){
            ob_start();
            phpinfo();
            $content = ob_get_contents();
            ob_end_flush();
            return $response->write($content);
            /* $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response); */
        });
        
        // Debug
        $app->get('/debug', function($request, $response, $args){
            $notFoundHandler = $this->notFoundHandler;
            return $notFoundHandler($request, $response);
        });

    }
}
