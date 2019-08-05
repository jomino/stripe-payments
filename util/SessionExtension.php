<?php

namespace Util;

class SessionExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{

    /**
     * @var \SlimSession\Helper
     */
    protected $session;
    
    public function __construct(\SlimSession\Helper $session)
    {
        $this->session = $session;
    }

    public function getGlobals()
    {
        $session = $this->session;

        $session_vars = [];

        foreach ($session->getIterator() as $key=>$value) {
            $session_vars[$key] = $value;
        }
        
        return ['session' => $session_vars];
    }

    public function getName()
    {
        return 'slim/session';
    }

}