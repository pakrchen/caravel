<?php

namespace Caravel\Http;

use Caravel\Console\App;

class View extends Response
{
    public static function make($view, array $params = array())
    {
        $body = self::get($view, $params);

        $headers = array(
            "Content-Type: text/html",
        );

        return new Response($headers, $body);
    }

    public static function get($view, array $params = array())
    {
        $file = self::find($view);

        extract($params);

        include($file);

        $content = ob_get_contents();
        ob_clean();

        return $content;
    }

    public static function find($view)
    {
        $view = str_replace(".", "/", $view);
        $file = App::getAppRoot() . "/views/{$view}.php";
        if (!file_exists($file)) {
            throw new \RuntimeException("view not found: [{$file}]");
        }

        return $file;
    }
}
