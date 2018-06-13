<?php

namespace Core;

class Controller
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($id)
    {
        return $this->container->$id;
    }
}
