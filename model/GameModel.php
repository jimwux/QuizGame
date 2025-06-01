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



    # ----------------------------------------------------------------------------------

    public function getNextQuestion(int $partidaId, int $usuarioId): ?array {
        $sql = "
            SELECT p.*,
                r1.texto AS opcion_a, r2.texto AS opcion_b, r3.texto AS opcion_c, r4.texto AS opcion_d
            FROM pregunta p
            JOIN respuesta r1 ON r1.id_pregunta = p.id AND r1.letra = 'a'
            JOIN respuesta r2 ON r2.id_pregunta = p.id AND r2.letra = 'b'
            JOIN respuesta r3 ON r3.id_pregunta = p.id AND r3.letra = 'c'
            JOIN respuesta r4 ON r4.id_pregunta = p.id AND r4.letra = 'd'
            WHERE p.estado = 'activa'
            AND p.id NOT IN (
                SELECT id_pregunta
                FROM partida_pregunta
                WHERE id_partida = $partidaId
            )
            AND p.id NOT IN (
                SELECT id_pregunta
                FROM pregunta_vista
                WHERE id_usuario = $usuarioId
            )
            ORDER BY RAND()
            LIMIT 1;
        ";

        $res = $this->database->query($sql);
        return $res[0] ?? null;
    }

    public function validateAnswer(int $preguntaId, string $respuestaUsuarioLetra): bool {
        // Obtener la respuesta correcta para la preguntaId
        $sql = "
            SELECT r.letra
            FROM respuesta r
            JOIN pregunta p ON r.id_pregunta = p.id
            WHERE p.id = $preguntaId AND r.es_correcta = TRUE;
        ";
        $result = $this->database->query($sql);

        // Asegúrate de que $result contenga algo y de que la clave 'letra' exista
        if (empty($result) || !isset($result[0]['letra'])) {
            // Esto podría indicar que la pregunta no tiene una respuesta marcada como correcta en la DB,
            // o que la consulta falló. Considera loggear esto.
            error_log("No se encontró respuesta correcta para la pregunta ID: $preguntaId");
            return false; // Si no hay respuesta correcta, la respuesta del usuario no puede ser correcta.
        }

        $respuestaCorrectaLetra = $result[0]['letra'];

        // Comparar la respuesta del usuario con la respuesta correcta
        // Usar strtolower() para hacer la comparación insensible a mayúsculas/minúsculas
        return strtolower($respuestaUsuarioLetra) === strtolower($respuestaCorrectaLetra);
    }

    public function saveAnswer(int $partidaId, int $preguntaId, string $respuestaUsuario, bool $esCorrecta): void {
        // Buscar ID de la respuesta seleccionada por texto
        $sql = "
            SELECT id 
            FROM respuesta 
            WHERE id_pregunta = $preguntaId 
            AND texto = '" . $this->database->escape($respuestaUsuario) . "' 
            LIMIT 1;
        ";
        $res = $this->database->query($sql);
        $respuestaId = $res[0]['id'] ?? 'NULL';

        // Calcular orden (cantidad de preguntas ya respondidas + 1)
        $ordenRes = $this->database->query("SELECT COUNT(*) as total FROM partida_pregunta WHERE id_partida = $partidaId");
        $orden = ($ordenRes[0]['total'] ?? 0) + 1;

        // Insertar en partida_pregunta
        $sql = "
            INSERT INTO partida_pregunta (id_partida, id_pregunta, id_respuesta, respondida_correctamente, orden_pregunta)
            VALUES ($partidaId, $preguntaId, $respuestaId, " . ($esCorrecta ? '1' : '0') . ", $orden);
        ";
        $this->database->execute($sql);
    }


    
    //Marca una pregunta como vista para un usuario en la tabla 'pregunta_vista'.
    
    public function markQuestionAsSeen(int $usuarioId, int $preguntaId): void
    {
        // Primero, verifica si la pregunta ya ha sido vista por este usuario para evitar duplicados
        $sqlCheck = "SELECT COUNT(*) AS count FROM pregunta_vista WHERE id_usuario = $usuarioId AND id_pregunta = $preguntaId";
        $resultCheck = $this->database->query($sqlCheck);

        if ($resultCheck[0]['count'] == 0) { // Si no ha sido vista, insértala
            $sqlInsert = "
                INSERT INTO pregunta_vista (id_usuario, id_pregunta, fecha_vista)
                VALUES ($usuarioId, $preguntaId, NOW());
            ";
            $this->database->execute($sqlInsert);
        }
        // Si ya ha sido vista, no hacemos nada (podrías actualizar la fecha_vista si lo prefieres)
    }

    public function calcScore(int $partidaId): int {
        // Ejemplo simple: 10 puntos por respuesta correcta
        $sql = "
            SELECT COUNT(*) as correctas 
            FROM partida_pregunta 
            WHERE id_partida = $partidaId AND respondida_correctamente = 1
        ";
        $res = $this->database->query($sql);
        $correctas = $res[0]['correctas'] ?? 0;

        return $correctas * 10;
    }


    public function saveGame(int $partidaId, int $puntaje): void {
        $sql = "
            UPDATE partida 
            SET puntaje = $puntaje, finalizada = 1 
            WHERE id = $partidaId
        ";
        $this->database->execute($sql);
    }

    # ----------------------------------------------------------------------------------
}