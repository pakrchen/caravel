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

    public function insert($table, array $fields)
    {
        $keys = array_keys($fields);
        $values = array_values($fields);

        $replace = function() { return '?'; };

        $marks = array_map($replace, $keys);

        $key = implode(',', $keys);
        $mark = implode(',', $marks);

        $sql = "INSERT INTO {$table} ($key) VALUES ($mark)";

        return $this->query($sql, $values);
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
        $whereValues = array_values($fields);

        $replace = function ($key) { return $key . "=?"; };

        $set = implode(',', array_map($replace, $fieldsKeys));
        $where = implode(' AND ', array_map($replace, $whereKeys));

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        return $sth->query($sql, array_merge($fieldsValues, $whereValues));
    }

    public function select($sql, array $params = array())
    {
        $sth = $this->store->prepare($sql);
        $sth->execute($params);
        $result = $sth->fetchAll($this->fetchMode);

        return $result;
    }

    public function selectOne($sql, array $params = array())
    {
        $result = $this->select($sql, $params);

        return empty($result) || !is_array($result) ? null : $result[0];
    }

    public function selectAll($table)
    {
        $sql = "select * from {$table}";
        $result = $this->select($sql);

        return $result;
    }

    public function query($sql, array $params = array())
    {
        $sth = $this->store->prepare($sql);

        return $sth->execute($params);
    }

    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }
}
