<?php

namespace Caravel\Log;

class Log
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

        if (empty(self::$file)) {
            throw new \RuntimeException("log file absent");
        } elseif (!file_exists(dirname(self::$file))) {
            throw new \RuntimeException("folder not exists: [" . dirname(self::$file) . "]");
        }

        $log = implode("\t", array(date("Y-m-d H:i:s"), $method, $message, json_encode($addition)));

        self::to($log, self::$file);
    }

    public static function to($log, $file)
    {
        error_log($log . "\n", 3, $file);
    }

    public static function useFile($file)
    {
        self::$file = $file;
    }
}
