<?php

namespace Caravel\Console;

use Caravel\Routing\ClassLoader;
use Caravel\Routing\URL;
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
    protected static $before;

    // a clouser that would be called after dispatch
    protected static $after;

    public function __construct()
    {
        // import what you've defined
        require_once self::getAppRoot() . "/custom.php";
    }

    public function run()
    {
        ob_start();
        try {
            $this->parse();

            $this->render($this->dispatch());
        } catch (\Exception $e) {
            call_user_func(self::$errorHandler, $e, $this);
        }
        ob_end_flush();
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
            list(self::$controller, self::$action) = URL::parsePath($url["path"]);
        } elseif ($url["path"] !== DIRECTORY_SEPARATOR) {
            throw new \BadMethodCallException("Url Cannot Parse Out Action: [{$url["path"]}]", 100);
        }
    }

    protected function dispatch()
    {
        // before dispatch
        if (!empty(self::$before) && ($response = call_user_func(self::$before))) {
            return $response;
        }

        // dispatch
        if (!class_exists(self::$controller)) {
            throw new \BadMethodCallException("Controller Not Found: [" . self::$controller . "]", 100);
        }

        if (!method_exists(self::$controller, self::$action)) {
            throw new \BadMethodCallException("Method Not Found: [" . self::$action . "]", 100);
        }

        $response = call_user_func(array(new self::$controller, self::$action));

        // after dispatch
        if (!empty(self::$after)) {
            $response = call_user_func(self::$after, $response) ?: $response;
        }

        return $response;
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

    public static function getErrorHandler()
    {
        return self::$errorHandler;
    }

    /**
     * before injection, run before dispatch
     */
    public static function before(\Closure $before)
    {
        self::$before = $before;
    }

    /**
     * after injection, run after dispatch
     */
    public static function after(\Closure $after)
    {
        self::$after = $after;
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
        list(self::$controller, self::$action) = URL::parsePath($path);
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
