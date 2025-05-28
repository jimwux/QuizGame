<?php

class GameModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function createGame(int $usuarioId): ?int {
        $fecha = date('Y-m-d H:i:s');
        $usuarioId = (int)$usuarioId;

        $sql = "INSERT INTO partida (id_usuario, fecha, puntaje, finalizada) VALUES ($usuarioId, '$fecha', 0, 0)";
        $this->database->execute($sql);

        $conn = $this->database->getConnection();
        return $conn->insert_id ?? null;
    }


    public function getGameById(int $partidaId): ?array {
        $sql = "SELECT * FROM partida WHERE id = $partidaId LIMIT 1";
        $result = $this->database->query($sql);

        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public function getGamesResultByUser($usuarioId) {
        $usuarioId = (int) $usuarioId;
        $sql = "SELECT rp.*, 
            c.nombre AS nombre_categoria, c.color AS color_categoria
            FROM resumen_partida rp
            JOIN categoria c ON rp.id_categoria = c.id
            WHERE rp.id_usuario = 1
            ORDER BY fecha_partida DESC
            LIMIT 4;";

        return $this->database->query($sql);
    }




}