<?php

namespace Caravel\Http;

class Request
{
    public static function getClientIp($trustProxy = true)
    {
        if ($trustProxy && self::getHttpClientIp()) {
            $ip = self::getHttpClientIp();
        } elseif ($trustProxy && self::getHttpXForwardedFor()) {
            $ip = self::getHttpXForwardedFor();
        } else {
            $ip = self::getRemoteAddr();
        }

        list($clientIp) = explode(',', $ip);

        return trim($clientIp);
    }

    public static function getHttpClientIp()
    {
        if (($ip = getenv("HTTP_CLIENT_IP")) && strcasecmp($ip, 'unknown')) {
            return $ip;
        } else {
            return "";
        }
    }

    public static function getHttpXForwardedFor()
    {
        if (($ip = getenv("HTTP_X_FORWARDED_FOR")) && strcasecmp($ip, 'unknown')) {
            return $ip;
        } else {
            return "";
        }
    }

    public static function getRemoteAddr()
    {
        if (($ip = getenv("REMOTE_ADDR")) && strcasecmp($ip, 'unknown')) {
            return $ip;
        } else {
            return "";
        }
    }
}
