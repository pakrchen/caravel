<?php

namespace Caravel\Log;

use Caravel\Http\Request;

/**
 * https://tools.ietf.org/html/rfc5424
 * Numerical Code    Severity
 *              0    Emergency: system is unusable
 *              1    Alert: action must be taken immediately
 *              2    Critical: critical conditions
 *              3    Error: error conditions
 *              4    Warning: warning conditions
 *              5    Notice: normal but significant condition
 *              6    Informational: informational messages
 *              7    Debug: debug-level messages
 */
class Log
{
    const LEVEL_DEBUG     = "DEBUG";
    const LEVEL_INFO      = "INFO";
    const LEVEL_NOTICE    = "NOTICE";
    const LEVEL_WARNING   = "WARNING";
    const LEVEL_ERROR     = "ERROR";
    const LEVEL_CRITICAL  = "CRITICAL";
    const LEVEL_ALERT     = "ALERT";
    const LEVEL_EMERGENCY = "EMERGENCY";

    protected $file;
    protected $logId;

    private static $instance = null;

    private function __construct()
    {
        $this->logId = $this->generateLogId();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Log();
        }

        return self::$instance;
    }

    public static function debug($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_DEBUG, $addition);
    }

    public static function info($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_INFO, $addition);
    }

    public static function notice($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_NOTICE, $addition);
    }

    public static function warning($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_WARNING, $addition);
    }

    public static function error($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_ERROR, $addition);
    }

    public static function critical($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_CRITICAL, $addition);
    }

    public static function alert($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_ALERT, $addition);
    }

    public static function emergency($message, array $addition = array())
    {
        $instance = self::getInstance();
        $instance->write($message, self::LEVEL_EMERGENCY, $addition);
    }

    public static function exception(\Exception $e)
    {
        $instance = self::getInstance();

        $trace = $e->getTrace();
        $class = $trace[0]['class'];
        $type = $trace[0]['type'];
        $function = $trace[0]['function'];
        $method = $class . $type . $function;

        $instance->write($e->getMessage(), self::LEVEL_ERROR, $trace);
    }

    /**
     * [Timestamp] [Log ID] [IP address] [Severity Level] [Source] [Message Text] [Additional Information]
     */
    protected function write($message, $level, array $addition = array())
    {
        // 0: function write; 1: function debug|info|notice...; 2: function of caller
        $traceLevel = 2;

        $trace = debug_backtrace();
        $class = empty($trace[$traceLevel]["class"]) ? "" : $trace[$traceLevel]["class"];
        $type = empty($trace[$traceLevel]["type"]) ? "" : $trace[$traceLevel]["type"];
        $function = empty($trace[$traceLevel]["function"]) ? "" : $trace[$traceLevel]["function"];
        $method = $class . $type . $function;

        if (empty($this->file)) {
            throw new \RuntimeException("Log File Absent");
        } elseif (!file_exists(dirname($this->file))) {
            throw new \RuntimeException("Folder Not Exists: [" . dirname($this->file) . "]");
        }

        $time     = $this->bracket(date("Y-m-d H:i:s"));
        $logId    = $this->bracket($this->logId);
        $ip       = $this->bracket(Request::getClientIp(true));
        $level    = $this->bracket($level);
        $source   = $this->bracket($method);
        $message  = $this->bracket($message);
        $addition = json_encode($addition);

        $log = implode(" ", array($time, $logId, $ip, $level, $source, $message, $addition));

        self::to($log, $this->file);
    }

    public static function to($message, $file)
    {
        error_log($message . "\n", 3, $file);
    }

    public static function useFile($file)
    {
        $instance = self::getInstance();
        $instance->file = $file;
    }

    protected function generateLogId()
    {
        return ((microtime(true) * 100000) & 0x7FFFFFFF);
    }

    protected function bracket($message)
    {
        return "[{$message}]";
    }
}
