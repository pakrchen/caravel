<?php

namespace Caravel\Log;

use Caravel\Http\Response;

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

    public function debug($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_DEBUG, $addition);
    }

    public function info($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_INFO, $addition);
    }

    public function notice($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_NOTICE, $addition);
    }

    public function warning($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_WARNING, $addition);
    }

    public function error($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_ERROR, $addition);
    }

    public function critical($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_CRITICAL, $addition);
    }

    public function alert($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_ALERT, $addition);
    }

    public function emergency($message, array $addition = array())
    {
        $this->write($message, self::LEVEL_EMERGENCY, $addition);
    }

    public function exception(\Exception $e)
    {
        $trace = $e->getTrace();
        $class = $trace[0]['class'];
        $type = $trace[0]['type'];
        $function = $trace[0]['function'];
        $method = $class . $type . $function;

        $this->write($method . " " . $e->getMessage());
    }

    /**
     * [Timestamp] [IP address] [Log ID] [Severity Level] [Method] [Message Text] [Additional Information]
     */
    public function write($message, $level = self::LEVEL_ERROR, array $addition = array())
    {
        $trace = debug_backtrace();
        $class = empty($trace[1]["class"]) ? "" : $trace[1]["class"];
        $type = empty($trace[1]["type"]) ? "" : $trace[1]["type"];
        $function = empty($trace[1]["function"]) ? "" : $trace[1]["function"];
        $method = $class . $type . $function;

        if (empty($this->file)) {
            throw new \RuntimeException("Log File Absent");
        } elseif (!file_exists(dirname($this->file))) {
            throw new \RuntimeException("Folder Not Exists: [" . dirname($this->file) . "]");
        }

        $time     = $this->bracket(date("Y-m-d H:i:s"));
        $ip       = $this->bracket(Request::getClientIp(true));
        $logId    = $this->bracket($this->logId);
        $level    = $this->bracket($level);
        $method   = $this->bracket($method);
        $message  = $this->bracket($message);
        $addition = json_encode($addition);

        $log = implode(" ", array($time, $ip, $logId, $level, $method, $message, $addition));

        $this->to($log, $this->file);
    }

    public function to($message, $file)
    {
        error_log($message . "\n", 3, $file);
    }

    public function useFile($file)
    {
        $this->file = $file;
    }

    protected function generateLogId()
    {
        return uniqid();
    }

    protected function bracket($message)
    {
        return "[{$message}]";
    }

    /**
     * All public methods can be called statically.
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array(array(self::getInstance(), $method), $parameters);
    }
}
