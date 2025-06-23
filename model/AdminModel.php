<?php

class AdminModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    private function calcularRangoFecha($filtro) {
        switch ($filtro) {
            case 'dia':
                return date('Y-m-d 00:00:00');
            case 'semana':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'mes':
                return date('Y-m-d 00:00:00', strtotime('-1 month'));
            case 'año':
                return date('Y-m-d 00:00:00', strtotime('-1 year'));
            default:
                return '1970-01-01 00:00:00'; // Cuando no hay filtro
        }
    }

    public function obtenerUsuariosPorPais($filtroFecha) {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);

        $sql = "SELECT pais, COUNT(*) AS cantidad
                FROM usuarios
                WHERE fecha_creacion >= ? AND rol = 'jugador'
                GROUP BY pais";

        return $this->database->query($sql, [$fechaInicio]);
    }

    public function obtenerUsuariosPorSexo($filtroFecha) {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);

        $sql = "SELECT sexo, COUNT(*) AS cantidad
            FROM usuarios
            WHERE fecha_creacion >= ? AND rol = 'jugador'
            GROUP BY sexo";

        return $this->database->query($sql, [$fechaInicio]);
    }

    public function obtenerUsuariosPorGrupoEtario($filtroFecha) {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);

        $sql = "SELECT
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, CONCAT(año_nacimiento, '-01-01'), CURDATE()) < 18 THEN 'Menores'
                    WHEN TIMESTAMPDIFF(YEAR, CONCAT(año_nacimiento, '-01-01'), CURDATE()) >= 60 THEN 'Jubilados'
                    ELSE 'Adultos'
                END AS grupo_etario,
                COUNT(*) AS cantidad
            FROM usuarios
            WHERE fecha_creacion >= ? AND rol = 'jugador'
            GROUP BY grupo_etario";

        return $this->database->query($sql, [$fechaInicio]);
    }

    public function getUserByUsername($username)
    {
        $query = $this->database->getConnection()->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $query->bind_param("s", $username);
        $query->execute();
        return $query->get_result()->fetch_assoc();

    }

    public function obtenerCantidadUsuarios($filtroFecha)
    {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);
        $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN fecha_creacion >= ? THEN 1 ELSE 0 END) AS filtrado
            FROM usuarios
            WHERE rol = 'jugador'";
        $query = $this->database->getConnection()->prepare($sql);
        $query->bind_param("s", $fechaInicio);
        $query->execute();
        return $query->get_result()->fetch_assoc();
    }

    public function obtenerCantidadPartidas($filtroFecha)
    {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);
        $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN fecha >= ? THEN 1 ELSE 0 END) AS filtrado
            FROM partida";
        $query = $this->database->getConnection()->prepare($sql);
        $query->bind_param("s", $fechaInicio);
        $query->execute();
        return $query->get_result()->fetch_assoc();
    }

    public function obtenerCantidadPreguntas($filtroFecha)
    {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);
        $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN fecha_creacion >= ? THEN 1 ELSE 0 END) AS filtrado
            FROM pregunta
            WHERE estado like 'activa'";
        $query = $this->database->getConnection()->prepare($sql);
        $query->bind_param("s", $fechaInicio);
        $query->execute();
        return $query->get_result()->fetch_assoc();
    }

    public function obtenerCantidadPreguntasCreadas($filtroFecha)
    {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);
        $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN fecha_creacion >= ? THEN 1 ELSE 0 END) AS filtrado
            FROM pregunta";
        $query = $this->database->getConnection()->prepare($sql);
        $query->bind_param("s", $fechaInicio);
        $query->execute();
        return $query->get_result()->fetch_assoc();
    }
}