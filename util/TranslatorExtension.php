<?php

namespace Util;

use Illuminate\Translation\Translator;

class TranslatorExtension extends \Twig_Extension
{

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getName()
    {
        return 'slim_translator';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('trans', array($this->translator, 'trans')),
            new \Twig_SimpleFilter('transChoice', array($this->translator, 'transChoice')),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('trans', array($this->translator, 'trans')),
            new \Twig_SimpleFunction('transChoice', array($this->translator, 'transChoice')),
        ];
    }
    
}