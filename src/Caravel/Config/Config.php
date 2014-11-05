<?php

namespace Caravel\Config;

use Caravel\Console\App;

class Config
{
    public static function get($name)
    {
        $file = App::getAppRoot() . "/config/{$name}.php";

        if(!file_exists($file)) {
            throw new \RuntimeException("config not found: [{$name}]");
        };

        $config = include $file;

        if(!is_array($config)) {
            throw new \RuntimeException("invalid config: [{$name}]");
        }

        return (object)$config;
    }
}
