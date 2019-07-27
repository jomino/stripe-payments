<?php

namespace Core;

class Routes
{
    public function __construct($app)
    {

        $container = $app->getContainer();

        $app->get('/', \App\Controllers\HomeController::class);
        $app->get('/adduser', \App\Controllers\AddUserController::class)->setName('adduser');

        $app->group( '', function($app){
            $app->post('/newuser', \App\Controllers\NewUserController::class)->setName('newuser');
        })->add($container->get('csrf'));

        $paths = [
          'js' => 'text/javascript',
          'css' => 'text/css',
          'images' => FILEINFO_MIME_TYPE
        ];

        $app->get('/{path:' . implode('|', array_keys($paths)) . '}/{file:[^/]+}',
            function ($request, $response, $args) use ($paths) {
                $log = $this->get('logger');
                $resource = '../assets/' . $args['path'] . '/' . $args['file'];
                $log->info('Ask for: '.$resource);
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
