<?php

namespace Util;

class Tools
{

    public static function cookieGetValue($cookie)
    {
        return strtr($cookie,strpos($cookie,'='));
    }

}