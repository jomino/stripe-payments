<?php

namespace Core;

class Dependencies
{
    public function __construct($app)
    {

        $container = $app->getContainer();

        // View
        $container['view'] = function ($container) {
            $twig = new \Slim\Views\Twig($container->settings['view']['path'], $container->settings['view']['twig']);
            $twig->addExtension(new \Slim\Views\TwigExtension($container->router, $container->request->getUri()));
            $twig->addExtension(new \Util\TranslatorExtension($container->trans));
            $twig->addExtension(new \Twig_Extension_Debug());
            return $twig;
        };

        // Logger
        $logger = new \Monolog\Logger($container->settings['logger']['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($container->settings['logger']['path'], $container->settings['logger']['level']));

        $container['logger'] = function ($container) use($logger) {
            return $logger;
        };

        // DB
        $capsule = new \Illuminate\Database\Capsule\Manager;
        $capsule->addConnection($container->settings['db']['env']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $container['db'] = function ($container) use($capsule) {
            return $capsule;
        };

        $container['trans'] = function ($container) use($app) {
            $loader = new \Illuminate\Translation\FileLoader(new \Illuminate\Filesystem\Filesystem(), $container->settings['localisation']['path']);
            $translator = new \Illuminate\Translation\Translator($loader, $app->language);
            return $translator;
        };

    }
}
