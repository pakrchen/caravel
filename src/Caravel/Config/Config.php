<?php

namespace Caravel\Config;

use Caravel\Console\App;

class Config
{
    /**
     * return stdClass by default
     */
    public static function get($name, $array = false)
    {
        $file = App::getAppRoot() . "/config/{$name}.php";

        if(!file_exists($file)) {
            throw new \RuntimeException("Config Not Found: [{$name}]");
        };

        $config = include $file;

        if(!is_array($config)) {
            throw new \RuntimeException("Invalid Config: [{$name}]");
        }

        return $array ? $config : (object)$config;
    }
}
