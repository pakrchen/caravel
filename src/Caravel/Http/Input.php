<?php

namespace Caravel\Http;

class Input extends Request
{
    public static function get($key, $default = null)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }
}
