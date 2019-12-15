<?php

namespace Util;

class Tools
{

    public static function cookieGetValue($cookie)
    {
        $key_value = explode('=',$cookie);
        return $key_value[1];
    }

    public static function queryGetValues($qs)
    {
        $values = [];
        if(preg_match('#^.*[%][0-9][a-z]#i',$qs)){
            $qs = urldecode($qs);
        }
        parse_str($qs, $values);
        return $values;
    }

}