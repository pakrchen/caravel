<?php
namespace Caravel\Console;

use Caravel\Routing\ClassLoader;
use Caravel\Http\Response;
use Caravel\Config\Config;

class Application
{
    protected static $controller;
    protected static $action;

    const ROOT = __DIR__;

    // a closure that would be called when an exception is thrown without a catch
    protected static $errorHandler;

    protected static $filter;

    // you can define default controller and action in custom.php
    protected static $defaultController;
    protected static $defaultAction;

    public function run()
    {
        $this->autoload();

        // import what you've defined
        require_once self::getAppRoot() . "/custom.php";

        $this->parse();

        try {
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

    protected function parse()
    {
        $url = parse_url($_SERVER["REQUEST_URI"]);
        if ($url["path"] === "/") {
            if (empty(self::$defaultController) || empty(self::$defaultAction)) {
                throw new \Exception("default controller or action is not set");
            }

            self::$controller = self::$defaultController;
            self::$action     = self::$defaultAction;
        } elseif (false != ($lastDsPos = strripos($url["path"], DIRECTORY_SEPARATOR))) {
            $controller = substr($url["path"], 1, $lastDsPos - 1);
            $action = substr($url["path"], $lastDsPos + 1);

            self::$controller = $this->formatController($controller);
            self::$action     = $this->formatAction($action);
        } else {
            return Response::status(404);
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
            throw new \BadMethodCallException("class not found: {self::$controller}", 100);
        }
        if (!method_exists(self::$controller, self::$action)) {
            throw new \BadMethodCallException("method not found: {self::$controller}::{self::$action}", 100);
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

    protected function formatController($controller)
    {
        if (!empty($controller)) {
            $controller = $this->camelizeController($controller);
        } else {
            throw new \BadMethodCallException("controller absent", 100);
        }

        return $controller . "Controller";
    }

    protected function formatAction($action)
    {
        if (!empty($action)) {
            $action = $this->camelizeAction($action);
        } else {
            throw new \BadMethodCallException("action absent", 100);
        }

        return $action . "Action";
    }

    /**
     * support namespace, transfer profile/app to Profile\App
     */
    protected function camelizeController($string)
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
    protected function camelizeAction($string)
    {
        $string = str_ireplace("-", "_", strtolower($string));
        $string = str_ireplace("_", " ", $string);
        $string = str_ireplace(" ", "", lcfirst(ucwords($string)));
        return $string;
    }

    /**
     * Argument passed into must be an instance of Closure, not Caravel\Closure
     */
    public static function error(\Closure $callback)
    {
        self::$errorHandler = $callback;
    }

    public static function filter(\Closure $filter)
    {
        self::$filter = $filter;
    }

    public static function getAppRoot()
    {
        return self::ROOT . "/../../../../../app";
    }

    /**
     * Define default controller and action
     */
    public static function defaultControllerAction($controller, $action)
    {
        self::$defaultController = $controller;
        self::$defaultAction = $action;
    }

    /**
     * We can make some frequently-used Classes look shorter
     */
    public static function alias(array $aliasClasses)
    {
        foreach ($aliasClasses as $k => $v) {
            class_alias($k, $v);
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
