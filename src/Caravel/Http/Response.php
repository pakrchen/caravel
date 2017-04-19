<?php

namespace Caravel\Http;

use Caravel\Console\App;

class Response
{
    protected $headers = array();
    protected $body;
    protected $statusCode;

    public function __construct($body = "", array $headers = array(), $statusCode = null)
    {
        $this->headers = $headers;
        $this->body = $body;
        $this->statusCode = $statusCode;
    }

    public function respond()
    {
        if (!empty($this->statusCode)) {
            $statusCodes = self::statusCodes();
            if (array_key_exists($this->statusCode, $statusCodes)) {
                $protocol = !empty($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : "HTTP/1.1";
                $header = "{$protocol} {$this->statusCode} {$statusCodes[$this->statusCode]}";
                header($header);
            }
        }

        if (!empty($this->headers)) {
            foreach ($this->headers as $header) {
                header($header);
            }
        }

        echo $this->body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public static function json(array $response, $callback = null)
    {
        if (!empty($callback)) {
            $callback = htmlentities(strval($callback));
            $body = $callback . "(" . json_encode($response) . ")";
        } else {
            $body = json_encode($response);
        }

        $headers = array(
            "Content-Type: text/javascript",
        );

        return new Response($body, $headers);
    }

    public static function redirect($url, $statusCode = 302)
    {
        $headers = array(
            "Location: {$url}",
        );

        return new Response("", $headers, $statusCode);
    }

    public static function status($statusCode = 200)
    {
        return new Response("", array(), $statusCode);
    }

    public static function file($filename)
    {
        $headers = array(
            "Content-Type: application/force-download",
            "Content-Disposition: attachment; filename=\"" . basename($filename) . "\"",
        );

        $body = file_get_contents($filename);

        return new Response($body, $headers);
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = intval($statusCode);
    }

    /**
     * HTTP Status Code Definitions
     * part of Hypertext Transfer Protocol -- HTTP/1.1
     * RFC 2616 Fielding, et al.
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public static function statusCodes()
    {
        return array(
            // Informational 1xx
            "100" => "Continue",
            "101" => "Switching Protocols",

            // Successful 2xx
            "200" => "OK",
            "201" => "Created",
            "202" => "Accepted",
            "203" => "Non-Authoritative Information",
            "204" => "No Content",
            "205" => "Reset Content",
            "206" => "Partial Content",

            // Redirection 3xx
            "300" => "Multiple Choices",
            "301" => "Moved Permanently",
            "302" => "Found",
            "303" => "See Other",
            "304" => "Not Modified",
            "305" => "Use Proxy",
            "306" => "(Unused)", // The 306 status code was used in a previous version of the specification, is no longer used, and the code is reserved.
            "307" => "Temporary Redirect",

            // Client Error 4xx
            "400" => "Bad Request",
            "401" => "Unauthorized",
            "402" => "Payment Required",
            "403" => "Forbidden",
            "404" => "Not Found",
            "405" => "Method Not Allowed",
            "406" => "Not Acceptable",
            "407" => "Proxy Authentication Required",
            "408" => "Request Timeout",
            "409" => "Conflict",
            "410" => "Gone",
            "411" => "Length Required",
            "412" => "Precondition Failed",
            "413" => "Request Entity Too Large",
            "414" => "Request-URI Too Long",
            "415" => "Unsupported Media Type",
            "416" => "Requested Range Not Satisfiable",
            "417" => "Expectation Failed",

            // Server Error 5xx
            "500" => "Internal Server Error",
            "501" => "Not Implemented",
            "502" => "Bad Gateway",
            "503" => "Service Unavailable",
            "504" => "Gateway Timeout",
            "505" => "HTTP Version Not Supported",
        );
    }
}
