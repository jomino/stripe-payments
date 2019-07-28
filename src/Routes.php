<?php

namespace Core;

class Routes
{
    public function __construct($app)
    {

        $container = $app->getContainer();

        $app->get('/', \App\Controllers\HomeController::class);

        $app->group( '', function($app){
            $app->get('/adduser', \App\Controllers\AddUserController::class)->setName('adduser');
            $app->post('/newuser', \App\Controllers\NewUserController::class)->setName('newuser');
            $app->map(['GET','POST'], '/register/{id:[0-9]+}/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\RegisterUserController::class)->setName('register');
        })->add($container->get('csrf'));
        
        $app->post('/webhook/{token:\??[0-9a-zA-Z-]*}', \App\Controllers\StripeWebhookController::class)->setName('webhook');

        $paths = [
          'js' => 'text/javascript',
          'css' => 'text/css',
          'images' => FILEINFO_MIME_TYPE
        ];

        $app->get('/{path:' . implode('|', array_keys($paths)) . '}/{file:[^/]+}',
            function ($request, $response, $args) use ($paths) {
                $assets = $this->get('settings')['assets'];
                $resource = $assets['path'] . '/' . $args['path'] . '/' . $args['file'];
                if (!is_file($resource)) {
                    $notFoundHandler = $this->get('notFoundHandler');
                    return $notFoundHandler($request, $response);
                }
                return $response->write(file_get_contents($resource))
                    ->withHeader('Content-Type', $paths[$args['path']]);
            }
        );

    }
}
