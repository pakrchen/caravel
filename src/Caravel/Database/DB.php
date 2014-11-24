<?php

namespace Caravel\Database;

use Caravel\Config\Config;

class DB
{
    protected static $connections = array();

    protected $store;
    protected $fetchMode;

    public static function connection($name = null)
    {
        $name = $name ?: self::getDefaultConnection();

        // If we haven't created this connection, we'll create it based on the config
        // provided in the application. Once we've created the connections we will
        // set the "fetch mode" for PDO which determines the query return types.
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = self::makeConnection($name);
        }

        return self::$connections[$name];
    }

    public static function reconnect($name = null)
    {
        $name = $name ?: self::getDefaultConnection();

        self::disconnect($name);

        return self::connection($name);
    }

    public static function disconnect($name = null)
    {
        $name = $name ?: self::getDefaultConnection();

        unset(self::$connections[$name]);
    }

    public static function makeConnection($name)
    {
        $config = Config::get('database');

        if (!isset($config->connections[$name])) {
            throw new \RuntimeException("Config Not Found: [{$name}]");
        }

        $params = $config->connections[$name];

        $require = array("host", "port","database", "username", "password");

        foreach ($require as $value) {
            if (!array_key_exists($value, $params)) {
                throw new \RuntimeException("Parameter Absent: [{$value}]");
            }
        }

        $store = self::getStore($params);

        $connection = new Connection($store);

        if (isset($config->fetch)) {
            $connection->setFetchMode($config->fetch);
        } else {
            throw new \RuntimeException("Fetch Mode Not Defined");
        }

        return $connection;
    }

    public static function getStore(array $params)
    {
        $dsn = "mysql:host={$params['host']};port={$params['port']};dbname={$params['database']}";
        $username = $params['username'];
        $password = $params['password'];
        $options  = isset($params['options']) && is_array($params['options']) ? $params['options'] : array();

        $dbh = new \PDO($dsn, $username, $password, $options);

        return $dbh;
    }

    public static function getDefaultConnection()
    {
        return Config::get('database')->default;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array(array(self::connection(), $method), $parameters);
    }
}
