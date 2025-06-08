<?php

#Crear función para obtener todos los usuarios con su puntaje total, ordenados de mayor a menor.
class RankingModel
{
        private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    public function obtenerRankingUsuariosTotal() {
        
        $db = $this->database->getConnection();

        $sql = "
            SELECT u.id, u.usuario, u.foto_perfil, u.pais, SUM(rp.puntaje) AS puntaje_total
            FROM usuarios u
            JOIN resumen_partida rp ON u.id = rp.id_usuario
            GROUP BY u.id, u.usuario, u.foto_perfil, u.pais
            ORDER BY puntaje_total DESC
        ";

        $result = $db->query($sql);
        $usuarios = [];

        while ($fila = $result->fetch_assoc()) {
            $usuarios[] = $fila;
        }

        return $usuarios;
    }
}