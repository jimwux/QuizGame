<?php

class Database
{
    private $conn;

    function __construct($servername, $username, $dbname, $password)
    {
        // Asegúrate de que el orden de los parámetros de mysqli sea correcto:
        // new mysqli(host, username, password, dbname)
        $this->conn = new mysqli($servername, $username, $password, $dbname)
                      or die("Error de conexion " . mysqli_connect_error());
    }

    public function query($sql)
    {
        $result = $this->conn->query($sql);

        // Verifica si $result es válido antes de llamar a fetch_all
        if ($result === false) {
            // Manejar error en la consulta (ej. loguear el error, devolver un array vacío)
            error_log("Error en la consulta SQL: " . $this->conn->error . " | SQL: " . $sql);
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function execute($sql)
    {
        // Verifica si la ejecución fue exitosa
        $result = $this->conn->query($sql);
        if ($result === false) {
            error_log("Error al ejecutar SQL: " . $this->conn->error . " | SQL: " . $sql);
        }
        return $result; // Devuelve true/false en caso de éxito/error
    }

    // --- ¡AÑADIDO ESTE MÉTODO PARA SANITIZAR STRINGS! ---
    public function escape($string)
    {
        // mysqli_real_escape_string requiere un objeto mysqli y una cadena
        // Usa $this->conn para acceder a la conexión mysqli
        return $this->conn->real_escape_string($string);
    }

    function __destruct()
    {
        // Solo cierra la conexión si está abierta
        if ($this->conn && $this->conn->ping()) {
            $this->conn->close();
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }
}