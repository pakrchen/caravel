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
}
