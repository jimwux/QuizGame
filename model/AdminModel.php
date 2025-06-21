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
                WHERE fecha_creacion >= ?
                GROUP BY pais";

        return $this->database->query($sql, [$fechaInicio]);
    }

    public function obtenerUsuariosPorSexo($filtroFecha) {
        $fechaInicio = $this->calcularRangoFecha($filtroFecha);

        $sql = "SELECT sexo, COUNT(*) AS cantidad
            FROM usuarios
            WHERE fecha_creacion >= ?
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
            WHERE fecha_creacion >= ?
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


}