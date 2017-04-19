<?php

namespace Caravel\Http;

class Input
{
    public static function all()
    {
        return $_REQUEST;
    }

    public static function get($key, $default = null)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }

    public static function has($key)
    {
        return isset($_REQUEST[$key]);
    }

    public static function only(array $keys)
    {
        $input = array();
        foreach ($keys as $key) {
            $input[$key] = isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
        }

        return $input;
    }

    public static function except(array $keys)
    {
        $input = array();
        foreach ($_REQUEST as $key => $value) {
            if (!in_array($key, $keys)) {
                $input[$key] = $value;
            }
        }

        return $input;
    }
}
