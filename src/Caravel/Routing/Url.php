<?php

namespace Caravel\Routing;

use Caravel\Console\App;

class Url
{
    /**
     * $path /namespace/controller/action
     */
    public static function parsePath($path)
    {
        $lastDsPos = strripos($path, DIRECTORY_SEPARATOR);
        if ($lastDsPos == false) {
            $controller = $action = "";
        } else {
            $controllerPath = substr($path, 1, $lastDsPos - 1);
            $actionPath = substr($path, $lastDsPos + 1);

            $controller = self::formatController($controllerPath);
            $action = self::formatAction($actionPath);
        }

        return array($controller, $action);
    }

    /**
     * $controller {/namespace/controller}/action
     */
    public static function formatController($controller)
    {
        $controller = self::camelizeController($controller);

        return $controller . "Controller";
    }

    /**
     * $action /namespace/controller/{action}
     */
    public static function formatAction($action)
    {
        $action = self::camelizeAction($action);

        return $action . "Action";
    }

    /**
     * support namespace, transfer namespace/controller to Namespace\Controller
     */
    public static function camelizeController($string)
    {
        $string = str_ireplace("/", " ", strtolower($string));
        $string = str_ireplace(" ", "\\", ucwords($string));
        $string = str_ireplace("_", " ", $string);
        $string = str_ireplace(" ", "", ucwords($string));

        return $string;
    }

    /**
     * support "-", "_", and case insensitive
     */
    public static function camelizeAction($string)
    {
        $string = str_ireplace("-", "_", strtolower($string));
        $string = str_ireplace("_", " ", $string);
        $string = str_ireplace(" ", "", lcfirst(ucwords($string)));

        return $string;
    }
}
