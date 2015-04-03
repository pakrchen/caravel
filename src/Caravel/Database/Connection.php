<?php

namespace Caravel\Database;

class Connection
{
    protected $store;
    protected $fetchMode;

    public function __construct($store)
    {
        $this->store = $store;
    }

    /**
     * if there is auto_increment field  return last insert id,
     * else return query result (true / false)
     */
    public function insert($table, array $fields)
    {
        $keys = array_keys($fields);
        $values = array_values($fields);

        $replace = function() { return '?'; };

        $marks = array_map($replace, $keys);

        $key = implode(',', $keys);
        $mark = implode(',', $marks);

        $sql = "INSERT INTO {$table} ($key) VALUES ($mark)";

        $result = $this->query($sql, $values);

        $identity = $this->selectOne("SELECT @@IDENTITY as IDENTITY");
        if (empty($identity)) {
            return $result;
        } else {
            return $identity->IDENTITY;
        }
    }

    /**
     * 'WHERE' only support 'AND'
     */
    public function delete($table, array $where)
    {
        $keys = array_keys($where);
        $values = array_values($where);

        $replace = function ($key) { return $key . "=?"; };

        $where = implode(' AND ', array_map($replace, $keys));

        $sql = "DELETE FROM {$table} WHERE {$where}";

        return $this->query($sql, $values);
    }

    /**
     * 'WHERE' only support 'AND'
     */
    public function update($table, array $fields, array $where)
    {
        $fieldsKeys = array_keys($fields);
        $fieldsValues = array_values($fields);

        $whereKeys = array_keys($where);
        $whereValues = array_values($where);

        $replace = function ($key) { return $key . "=?"; };

        $set = implode(',', array_map($replace, $fieldsKeys));
        $where = implode(' AND ', array_map($replace, $whereKeys));

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        return $this->query($sql, array_merge($fieldsValues, $whereValues));
    }

    public function select($sql, array $params = array())
    {
        $sth = $this->store->prepare($sql);
        $sth->execute($params);

        $this->handleOperationError($sth);

        $result = $sth->fetchAll($this->fetchMode);

        return $result;
    }

    public function selectOne($sql, array $params = array())
    {
        $result = $this->select($sql, $params);

        return empty($result) || !is_array($result) ? null : $result[0];
    }

    public function all($table)
    {
        $sql = "select * from {$table}";
        $result = $this->select($sql);

        return $result;
    }

    /**
     * @return array(stdClass)
     */
    public function find($table, array $params)
    {
        $keys = array_keys($params);
        $values = array_values($params);

        $replace = function ($key) { return $key . "=?"; };
        $where = implode(' AND ', array_map($replace, $keys));

        $sql = "select * from {$table} where {$where}";

        return $this->select($sql, $values);
    }

    /**
     * @return stdClass
     */
    public function findOne($table, array $params)
    {
        $keys = array_keys($params);
        $values = array_values($params);

        $replace = function ($key) { return $key . "=?"; };
        $where = implode(' AND ', array_map($replace, $keys));

        $sql = "select * from {$table} where {$where}";

        return $this->selectOne($sql, $values);
    }

    public function query($sql, array $params = array())
    {
        $sth = $this->store->prepare($sql);

        $result = $sth->execute($params);

        $this->handleOperationError($sth);

        return $result;
    }

    /**
     * $error[0] SQLSTATE error code
     * $error[1] Driver specific error code
     * $error[2] Driver specific error message
     */
    public function handleOperationError($statementHandler)
    {
        $error = $statementHandler->errorInfo();
        if (!empty($error[2])) {
            throw new \RuntimeException("ERROR {$error[1]} ({$error[0]}): {$error[2]}");
        }
    }

    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }
}
