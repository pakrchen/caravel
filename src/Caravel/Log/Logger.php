<?php
namespace Caravel\Log;

class Logger
{
    protected static $file;

    public static function exception(\Exception $e)
    {
        $trace = $e->getTrace();
        $class = $trace[0]['class'];
        $type = $trace[0]['type'];
        $function = $trace[0]['function'];
        $method = $class . $type . $function;

        self::error($e->getMessage(), $method);
    }

    public static function error($message, $method = "", array $addition = array())
    {
        if (empty($method)) {
            $trace = debug_backtrace();
            $class = empty($trace[1]["class"]) ? "" : $trace[1]["class"];
            $type = empty($trace[1]["type"]) ? "" : $trace[1]["type"];
            $function = empty($trace[1]["function"]) ? "" : $trace[1]["function"];
            $method = $class . $type . $function;
        }

        $fileName = __FUNCTION__ . ".log";

        $log = implode("\t", array(date("Y-m-d H:i:s"), $method, $message, json_encode($addition)));

        self::to($log, $fileName);
    }

    public static function to($log, $fileName)
    {
        $filePath = self::$file . "/" . $fileName;

        error_log($log . "\n", 3, $filePath);
    }

    public static function useFile($file)
    {
        self::$file = $file;
    }
}
