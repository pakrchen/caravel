<?php

namespace Caravel\Http;

use Caravel\Console\App;

class View
{
    public static function make($view, array $params = array())
    {
        $body = self::get($view, $params);

        $headers = array(
            "Content-Type: text/html",
        );

        return new Response($body, $headers);
    }

    public static function get($view, array $params = array())
    {
        $file = self::path($view);

        extract($params);

        include($file);

        $content = ob_get_contents();
        ob_clean();

        return $content;
    }

    public static function path($view)
    {
        $view = str_replace(".", "/", $view);
        $file = App::getAppRoot() . "/views/{$view}.php";
        if (!file_exists($file)) {
            throw new \RuntimeException("View Not Found: [{$file}]");
        }

        return $file;
    }
}
