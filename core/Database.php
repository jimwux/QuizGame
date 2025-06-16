<?php

class Database
{

    private $conn;

    function __construct($servername, $username, $dbname, $password)
    {
        $this->conn = new Mysqli($servername, $username, $password, $dbname) or die("Error de conexion " . mysqli_connect_error());
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar SQL: " . $this->conn->error . "\nSQL: $sql");
        }

        if ($params) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function queryOne($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            die("Error preparando: " . $this->conn->error);
        }

        if ($params) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            die("Error ejecutando: " . $stmt->error);
        }

        $stmt->close();
    }


    function __destruct()
    {
        $this->conn->close();
    }

    public function lastInsertId()
    {
        return $this->conn->insert_id;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function beginTransaction()
    {
        $this->conn->begin_transaction();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function rollBack()
    {
        $this->conn->rollBack();
    }

}