<?php

namespace Core;

class Dependencies
{
    public function __construct($app)
    {

        $container = $app->getContainer();

        // session
        $container['session'] = function ($container) {
            return new \SlimSession\Helper;
        };

        // CSRF protection
        $container['csrf'] = function ($container) {
            $guard = new \Slim\Csrf\Guard();
            $guard->setFailureCallable(function ($request, $response, $next) {
                $request = $request->withAttribute("csrf_status", false);
                return $next($request, $response);
            });
            return $guard;
        };

        // Language
        $container['language'] = function ($container) use($app) {
            return $app->language;
        };

        // Translation
        $container['trans'] = function ($container) use($app) {
            $loader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem(), $container->settings['localisation']['path']);
            $translator = new \Illuminate\Translation\Translator($loader, $app->language);
            return $translator;
        };

        // View
        $container['view'] = function ($container) {
            $twig = new \Slim\Views\Twig($container->settings['view']['path'], $container->settings['view']['twig']);
            $twig->addExtension(new \Slim\Views\TwigExtension($container->router, $container->request->getUri()));
            $twig->addExtension(new \Util\TranslatorExtension($container->trans));
            $twig->addExtension(new \Util\CsrfExtension($container->csrf));
            $twig->addExtension(new \Util\SessionExtension($container->session));
            $twig->addExtension(new \Twig_Extension_Debug());
            return $twig;
        };

        // Logger
        $container['logger'] = function ($container) {
            $logger = new \Monolog\Logger($container->settings['logger']['name']);
            $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($container->settings['logger']['path'], $container->settings['logger']['level']));
            return $logger;
        };

        // DB
        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection($container->settings['db']['env']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        $container['db'] = function ($container) use($capsule) {
            return $capsule->getConnection()->query();
        };

    }
}
