<?php

namespace Caravel\Console;

use Caravel\Routing\ClassLoader;
use Caravel\Routing\Url;
use Caravel\Http\Response;
use Caravel\Config\Config;

class App
{
    protected static $controller;
    protected static $action;

    const ROOT = __DIR__;

    // a closure that would be called when an exception is thrown without a catch
    protected static $errorHandler;

    // a clouser that would be called before dispatch
    protected static $filter;

    public function run()
    {
        $this->autoload();

        // import what you've defined
        require_once self::getAppRoot() . "/custom.php";

        try {
            $this->parse();

            ob_start();
            $this->render($this->dispatch());
            ob_end_flush();
        } catch (\Exception $e) {
            call_user_func(self::$errorHandler, $e);
        }
    }

    public function autoload(array $paths = array())
    {
        $classLoader = new ClassLoader($paths);
        $classLoader->register();
    }

    /**
     * parse url to get controller and action
     */
    protected function parse()
    {
        $url = parse_url($_SERVER["REQUEST_URI"]);

        if (false != ($lastDsPos = strripos($url["path"], DIRECTORY_SEPARATOR))) {
            list(self::$controller, self::$action) = Url::parsePath($url["path"]);
        } elseif ($url["path"] !== DIRECTORY_SEPARATOR) {
            throw new \BadMethodCallException("url cannot parse out action: [{$url["path"]}]", 100);
        }
    }

    protected function dispatch()
    {
        if (!empty(self::$filter)) {
            $response = call_user_func(self::$filter);
            if (!empty($response)) {
                return $response;
            }
        }

        if (!class_exists(self::$controller)) {
            throw new \BadMethodCallException("controller not found: [" . self::$controller . "]", 100);
        }

        if (!method_exists(self::$controller, self::$action)) {
            throw new \BadMethodCallException("method not found: [" . self::$action . "]", 100);
        }

        return call_user_func(array(new self::$controller, self::$action));
    }

    public function render($response)
    {
        if ($response instanceof Response) {
            $response->respond();
        } else {
            echo $response;
        }
    }

    /**
     * Argument passed into must be an instance of Closure, not Caravel\Closure
     */
    public static function error(\Closure $callback)
    {
        self::$errorHandler = $callback;
    }

    /**
     * filter injection, run before dispatch
     */
    public static function filter(\Closure $filter)
    {
        self::$filter = $filter;
    }

    /**
     * ~/vendor/pakrchen/caravel/src/Caravel/Console/App.php
     */
    public static function getAppRoot()
    {
        return realpath(self::ROOT . "/../../../../../../app");
    }

    /**
     * Define default controller and action
     */
    public static function homepage($path)
    {
        list(self::$controller, self::$action) = Url::parsePath($path);
    }

    /**
     * We can make some frequently-used Classes look shorter
     */
    public static function alias(array $aliasClasses)
    {
        foreach ($aliasClasses as $alias => $original) {
            class_alias($original, $alias);
        }
    }

    public static function getController()
    {
        return self::$controller;
    }

    public static function getAction()
    {
        return self::$action;
    }
}
