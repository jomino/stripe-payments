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
        $key_value = explode('&',$qs);
        for ($i=0; $i < sizeof($key_value); $i++) {
            $value =  explode('=',$key_value);
            $values[$value[0]] = urldecode($value[1]);
        }
        return $values;
    }

}