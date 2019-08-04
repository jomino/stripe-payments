<?php

namespace Util;

class Tools
{

    public static function cookieGetValue($cookie)
    {
        $key_value = explode('=',$cookie);
        return $key_value[1];
    }

}