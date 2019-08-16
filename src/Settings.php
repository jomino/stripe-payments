<?php

namespace Core;

class Settings
{
    private $settings;

    public function __construct()
    {
        $this->settings = [
            'settings' => [
                'displayErrorDetails' => \App\Parameters::SYSTEM['debug'],
                'addContentLengthHeader' => false,
                'view' => [
                    'twig' => [
                        'debug' => \App\Parameters::SYSTEM['debug']
                    ],
                    'path' => __DIR__ . '/../app/Views'
                ],
                'localisation' => [
                    'path' => __DIR__ . '/../app/Localisations'
                ],
                'logger' => [
                    'name' => 'APPLICATION',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/application.log',
                    'level' => \Monolog\Logger::DEBUG
                ],
                'db' => [
                    'env' => \App\Parameters::DATABASE
                ],
                'assets' => [
                    'path' => __DIR__ . '/../assets'
                ]
            ]
        ];
    }

    public function load()
    {
        return $this->settings;
    }
}