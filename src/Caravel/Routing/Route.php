<?php

namespace Caravel\Routing;

use Caravel\Console\App;

class Route
{
    public static function currentRouteAction()
    {
        $controller = App::getController();
        $action = App::getAction();

        return "{$controller}@{$action}";
    }

    public static function routeToAction($route)
    {
        $atPos = stripos($route, '@');
        $controller = substr($route, 0, $atPos);
        $action = substr($route, $atPos + 1);

        return array($controller, $action);
    }

    public static function actionToRoute($controller, $action)
    {
        return $controller . '@' . $action;
    }

    public static function same($routeA, $routeB)
    {
        return strtolower($routeA) == strtolower($routeB);
    }
}
