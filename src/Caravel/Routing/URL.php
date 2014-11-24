<?php

namespace Caravel\Routing;

use Caravel\Console\App;

class URL
{
    /***** action to url start *****/

    public static function route($route, array $params = array())
    {
        list($controller, $action) = Route::routeToAction($route);
        $controller = substr($controller, 0, strlen($controller) - strlen('Controller'));
        $action = substr($action, 0, strlen($action) - strlen('Action'));

        $path = "/" . str_ireplace('\\', '/', $controller) . '/' . $action;
        $query = http_build_query($params);

        $url = $path . (empty($query) ? '' : ('?' . $query));

        return $url;
    }

    /***** action to url end *****/

    /***** url to action start *****/

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
        $string = str_ireplace("/", " ", $string);
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
        $string = str_ireplace("-", "_", $string);
        $string = str_ireplace("_", " ", $string);
        $string = str_ireplace(" ", "", lcfirst(ucwords($string)));

        return $string;
    }

    /***** url to action end *****/
}
