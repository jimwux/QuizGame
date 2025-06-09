<?php

class GameModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function createGame(int $usuarioId): ?int
    {
        $fecha = date('Y-m-d H:i:s');
        $usuarioId = (int)$usuarioId;

        $sql = "INSERT INTO partida (id_usuario, fecha, puntaje, finalizada) VALUES (?, ?, 0, 0)";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bind_param('is', $usuarioId, $fecha);
        $stmt->execute();
        $partidaId = $this->database->getConnection()->insert_id;
        $stmt->close();

        return $partidaId ?? null;
    }

    public function buscarPreguntaPorId($id)
    {
        $query = "SELECT * FROM pregunta WHERE id = ? LIMIT 1";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function buscarPreguntaYRespuestasPorId($idPregunta)
    {
        $question = $this->buscarPreguntaPorId($idPregunta);
        if (!$question) {
            return null;
        }

        $idCategoria = $question["id_categoria"];
        $infoCategory = $this->getCategory($idCategoria);
        $answers = $this->getAnswers($idPregunta);

        return ["question" => $question, "answers" => $answers, "category" => $infoCategory];
    }

    public function verificarPreguntaRespondidaEnPartida($partidaId, $preguntaId)
    {
        $query = "SELECT respondida_correctamente FROM partida_pregunta WHERE id_partida = ? AND id_pregunta = ? AND estado_pregunta = 'respondida'";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param('ii', $partidaId, $preguntaId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        // Retorna true si ya fue respondida en la partida
        return $result !== null;
    }

    public function marcarPreguntaComoActual($partidaId, $idPregunta)
    {
        // Insertar o actualizar el estado de la pregunta a 'actual' para la partida.
        // Esto es crucial para la recuperación de la partida.
        $query = "INSERT INTO partida_pregunta (id_partida, id_pregunta, estado_pregunta) VALUES (?, ?, 'actual')
                  ON DUPLICATE KEY UPDATE estado_pregunta = 'actual'";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param('ii', $partidaId, $idPregunta);
        $stmt->execute();
        $stmt->close();
    }

    public function desmarcarPreguntaActual($partidaId, $idPregunta)
    {
        // Cambia el estado de la pregunta de 'actual' a 'respondida' después de que se responde
        $query = "UPDATE partida_pregunta SET estado_pregunta = 'respondida' WHERE id_partida = ? AND id_pregunta = ? AND estado_pregunta = 'actual'";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param('ii', $partidaId, $idPregunta);
        $stmt->execute();
        $stmt->close();
    }

    public function getGameById(int $partidaId): ?array
    {
        $sql = "SELECT * FROM partida WHERE id = ? LIMIT 1";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bind_param('i', $partidaId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function getNumberOfRowsInPregunta()
    {
        $numberOfQuestions = "SELECT COUNT(*) AS total FROM pregunta WHERE estado = 'activa'"; // Solo preguntas activas
        $queryPrepare = $this->database->getConnection()->prepare($numberOfQuestions);
        $queryPrepare->execute();
        $result = $queryPrepare->get_result()->fetch_assoc();
        $queryPrepare->close();
        return $result ? (int)$result['total'] : 0;
    }

    public function getUserCorrectRatio($userId)
    {
        $query = "SELECT
                    SUM(CASE WHEN es_correcta = 1 THEN 1 ELSE 0 END) AS respuestas_correctas,
                    COUNT(*) AS total_respuestas
                  FROM
                    pregunta_usuario
                  WHERE
                    id_usuario = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result['total_respuestas'] == 0) {
            return 0.5; // Usuario nuevo o sin respuestas
        }
        return ($result['respuestas_correctas'] * 1.0) / $result['total_respuestas'];
    }

    public function verificarSiEsUnUsuarioNuevo($userId)
    {
        $query = "SELECT COUNT(pu.id_usuario) as totalRespondidas FROM pregunta_usuario pu WHERE pu.id_usuario = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['totalRespondidas'];
    }

    public function verificarSiEsUnaPreguntaNueva($idPregunta)
    {
        $query = "SELECT veces_mostrada FROM pregunta WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function obtenerUnaPreguntaAleatoriaPorDificultad($dificultad, $userId, $partidaId)
    {
        // Asegúrate de que la pregunta no haya sido respondida por el usuario en ninguna partida
        // Y que no haya sido mostrada en la partida actual
        $query = "
            SELECT p.*
            FROM pregunta p
            LEFT JOIN pregunta_usuario pu ON p.id = pu.id_pregunta AND pu.id_usuario = ?
            LEFT JOIN partida_pregunta ppa ON p.id = ppa.id_pregunta AND ppa.id_partida = ?
            WHERE p.estado = 'activa'
            AND pu.id_pregunta IS NULL -- Que no haya sido respondida por el usuario antes
            AND ppa.id_pregunta IS NULL -- Que no haya sido mostrada en esta partida actual
            AND (
                (p.veces_mostrada < 10 AND 'media' = ?) OR
                (p.veces_mostrada >= 10 AND ? = (
                    CASE
                        WHEN (p.veces_respondida_correctamente / p.veces_mostrada) > 0.7 THEN 'facil'
                        WHEN (p.veces_respondida_correctamente / p.veces_mostrada) < 0.3 THEN 'dificil'
                        ELSE 'media'
                    END
                ))
            )
            ORDER BY RAND()
            LIMIT 1
        ";

        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("iiss", $userId, $partidaId, $dificultad, $dificultad);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function obtenerPreguntaNoRepetidaEnPartida($userId, $partidaId)
    {
        // Obtiene una pregunta que nunca ha sido respondida por el usuario
        // y que no haya sido mostrada en la partida actual
        $query = "
            SELECT p.*
            FROM pregunta p
            LEFT JOIN pregunta_usuario pu ON p.id = pu.id_pregunta AND pu.id_usuario = ?
            LEFT JOIN partida_pregunta ppa ON p.id = ppa.id_pregunta AND ppa.id_partida = ?
            WHERE p.estado = 'activa'
            AND pu.id_pregunta IS NULL -- Que no haya sido respondida por el usuario antes
            AND ppa.id_pregunta IS NULL -- Que no haya sido mostrada en esta partida actual
            ORDER BY RAND()
            LIMIT 1
        ";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("ii", $userId, $partidaId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function calcularDificultadPregunta($idPregunta)
    {
        $query = "SELECT veces_mostrada, veces_respondida_correctamente FROM pregunta WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $dificultad = "media";
        if (isset($result['veces_mostrada']) && $result['veces_mostrada'] > 10 && $result['veces_mostrada'] > 0) {
            $promedio = $result['veces_respondida_correctamente'] / $result['veces_mostrada'];

            if ($promedio > 0.7) {
                $dificultad = "facil";
            } else if ($promedio < 0.3) {
                $dificultad = "dificil";
            }
        }

        return $dificultad;
    }

    public function calcularDificultadUsuario($idUsuario)
    {
        $query = "SELECT COUNT(*) as totalRespondidas, SUM(CASE WHEN es_correcta = 1 THEN 1 ELSE 0 END) AS totalCorrectas FROM pregunta_usuario WHERE id_usuario = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result["totalRespondidas"] == 0) {
            return "media";
        }

        $promedio = $result['totalCorrectas'] / $result['totalRespondidas'];
        $dificultad = "media";
        if ($promedio > 0.7) {
            $dificultad = "facil"; // Un usuario que responde muchas bien es "facil" para el juego (se le dan preguntas dificiles)
        } else if ($promedio < 0.3) {
            $dificultad = "dificil"; // Un usuario que responde pocas bien es "dificil" para el juego (se le dan preguntas faciles)
        }

        return $dificultad;
    }

    public function incrementarVecesMostradas($idPregunta)
    {
        $query = "UPDATE pregunta SET veces_mostrada = veces_mostrada + 1 WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $stmt->close();
    }

    public function getQuestionForUser($userId, $partidaId)
    {
        $totalRespuestasUsuario = $this->verificarSiEsUnUsuarioNuevo($userId);
        $esUsuarioNuevo = $totalRespuestasUsuario < 10;
        $dificultadDeseadaUsuario = "media";

        if (!$esUsuarioNuevo) {
            $dificultadDeseadaUsuario = $this->calcularDificultadUsuario($userId);
        }

        $question = null;
        $maxAttempts = 50;
        $attempts = 0;

        do {
            // Intentar obtener una pregunta que no haya sido respondida por el usuario en esta partida
            // y que coincida con la dificultad deseada del usuario/pregunta.
            $question = $this->obtenerUnaPreguntaAleatoriaPorDificultad($dificultadDeseadaUsuario, $userId, $partidaId);

            $attempts++;
        } while (!$question && $attempts < $maxAttempts);


        // Si después de los intentos, no encontramos una pregunta que cumpla la dificultad
        // o no encontramos ninguna pregunta no respondida en esta partida, buscar cualquier pregunta NO RESPONDIDA
        if (!$question) {
            $question = $this->obtenerPreguntaNoRepetidaEnPartida($userId, $partidaId);
            if (!$question) {
                return null; // El usuario respondió todas las preguntas disponibles
            }
        }

        // Registrar la pregunta como "mostrada" en la partida
        $this->marcarPreguntaMostradaEnPartida($partidaId, $question['id']);
        // Incrementar el contador global de veces mostradas para la pregunta
        $this->incrementarVecesMostradas($question['id']);

        $idCategoria = $question["id_categoria"];
        $infoCategory = $this->getCategory($idCategoria);
        $answers = $this->getAnswers($question["id"]);

        $infoQuestionComplete = ["question" => $question, "answers" => $answers, "category" => $infoCategory];

        return $infoQuestionComplete;
    }

    public function marcarPreguntaMostradaEnPartida($partidaId, $idPregunta)
    {
        // Insertar la pregunta en partida_pregunta cuando es mostrada por primera vez en esta partida
        // Usamos ON DUPLICATE KEY UPDATE para asegurarnos de que la entrada exista y se actualice si es necesario,
        // aunque el estado 'actual' será manejado por marcarPreguntaComoActual
        $query = "INSERT INTO partida_pregunta (id_partida, id_pregunta, estado_pregunta) VALUES (?, ?, 'mostrada')
                  ON DUPLICATE KEY UPDATE estado_pregunta = VALUES(estado_pregunta)";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param('ii', $partidaId, $idPregunta);
        $stmt->execute();
        $stmt->close();
    }

    public function getCategory($idCategory)
    {
        $query = "SELECT * FROM categoria WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idCategory);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        $stmt->close();
        return $category;
    }

    public function getAnswers($idQuestion)
    {
        $query = "SELECT * FROM respuesta WHERE id_pregunta = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idQuestion);
        $stmt->execute();
        $result = $stmt->get_result();

        $answers = [];
        while ($row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
        $stmt->close();
        return $answers;
    }

    public function verificarSiRespuestaEsCorrecta($idRespuesta)
    {
        $query = "SELECT es_correcta FROM respuesta WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idRespuesta);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? (bool)$result['es_correcta'] : false; // Asegurarse de retornar un booleano
    }

    public function procesarRespuesta($idQuestion, $idRespuestaSeleccionada, $userId, $partidaId)
    {
        $esCorrecta = $this->verificarSiRespuestaEsCorrecta($idRespuestaSeleccionada);

        // Registrar la respuesta en partida_pregunta
        // El estado 'actual' de la pregunta se desmarcó antes de llamar a procesarRespuesta.
        // Aquí actualizamos la entrada existente para marcarla como 'respondida'.
        $query = "UPDATE partida_pregunta SET respondida_correctamente = ?, id_respuesta = ?, estado_pregunta = 'respondida' WHERE id_partida = ? AND id_pregunta = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("iiii", $esCorrecta, $idRespuestaSeleccionada, $partidaId, $idQuestion);
        $stmt->execute();
        $stmt->close();

        // Registrar que el usuario respondió esta pregunta en la tabla pregunta_usuario
        $queryInsertUserQuestion = "INSERT INTO pregunta_usuario (id_usuario, id_pregunta, id_respuesta, es_correcta) VALUES (?, ?, ?, ?)
                                    ON DUPLICATE KEY UPDATE id_respuesta = VALUES(id_respuesta), es_correcta = VALUES(es_correcta)";
        $stmtInsertUserQuestion = $this->database->getConnection()->prepare($queryInsertUserQuestion);
        $stmtInsertUserQuestion->bind_param("iiii", $userId, $idQuestion, $idRespuestaSeleccionada, $esCorrecta);
        $stmtInsertUserQuestion->execute();
        $stmtInsertUserQuestion->close();

        if ($esCorrecta) {
            // Aumentar respuestas correctas en la tabla 'pregunta'
            $queryUpdateCorrect = "UPDATE pregunta SET veces_respondida_correctamente = veces_respondida_correctamente + 1 WHERE id = ?";
            $stmtUpdateCorrect = $this->database->getConnection()->prepare($queryUpdateCorrect);
            $stmtUpdateCorrect->bind_param("i", $idQuestion);
            $stmtUpdateCorrect->execute();
            $stmtUpdateCorrect->close();

            return ['correcta' => true];
        }

        return ['correcta' => false];
    }

    public function getScore($partidaIdEntrada)
    {
        $partidaId = intval($partidaIdEntrada);
        $sql = "SELECT puntaje FROM partida WHERE id = ? LIMIT 1";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bind_param('i', $partidaId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ? (int)$res['puntaje'] : 0;
    }

    public function saveGame($partidaIdEntrada, int $puntaje): void
    {
        $partidaId = intval($partidaIdEntrada);
        $sql = "UPDATE partida SET puntaje = ?, finalizada = 1 WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bind_param('ii', $puntaje, $partidaId);
        $stmt->execute();
        $stmt->close();
    }

    public function guardarResumenPartida(
        int $idPartida,
        int $idUsuario
    ): void {
        $cantidadCorrectas = $this->getScore($idPartida);
        // Calcula la cantidad de intentos contando las preguntas en partida_pregunta para esta partida
        $queryCantidadIntentos = "SELECT COUNT(*) as total_intentos FROM partida_pregunta WHERE id_partida = ?";
        $stmtIntentos = $this->database->getConnection()->prepare($queryCantidadIntentos);
        $stmtIntentos->bind_param('i', $idPartida);
        $stmtIntentos->execute();
        $cantidadIntentos = $stmtIntentos->get_result()->fetch_assoc()['total_intentos'] ?? 0;
        $stmtIntentos->close();

        // Puedes implementar lógica para determinar id_categoria y id_dificultad si se necesitan
        // por ejemplo, la categoría más respondida, o la dificultad promedio de las preguntas en la partida.
        // Por ahora, se mantendrán como NULL si no se calculan.
        $idCategoria = null;
        $idDificultad = null;

        $puntaje = $cantidadCorrectas; // Ya es el puntaje final
        $tiempoPromedioRespuesta = null; // Si implementas el seguimiento del tiempo

        $sql = "
            INSERT INTO resumen_partida (
                id_partida,
                id_usuario,
                cantidad_correctas,
                cantidad_intentos,
                id_categoria,
                id_dificultad,
                puntaje,
                tiempo_promedio_respuesta,
                fecha_partida
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
            ON DUPLICATE KEY UPDATE
                cantidad_correctas = VALUES(cantidad_correctas),
                cantidad_intentos = VALUES(cantidad_intentos),
                id_categoria = VALUES(id_categoria),
                id_dificultad = VALUES(id_dificultad),
                puntaje = VALUES(puntaje),
                tiempo_promedio_respuesta = VALUES(tiempo_promedio_respuesta),
                fecha_partida = VALUES(fecha_partida)
        ";

        $stmt = $this->database->getConnection()->prepare($sql);
        // 's' para string si $tiempoPromedioRespuesta es VARCHAR o TEXT, 'd' para DECIMAL o FLOAT
        $stmt->bind_param(
            "iiiiiiid",
            $idPartida,
            $idUsuario,
            $cantidadCorrectas,
            $cantidadIntentos,
            $idCategoria,
            $idDificultad,
            $puntaje,
            $tiempoPromedioRespuesta
        );
        $stmt->execute();
        $stmt->close();
    }

    public function getResumenPartida(
        int $idPartida,
        int $idUsuario
    ) {
        $sql = "SELECT rp.*, u.usuario as nombre_usuario
                FROM resumen_partida rp
                JOIN usuarios u ON rp.id_usuario = u.id
                WHERE rp.id_partida = ? AND rp.id_usuario = ? LIMIT 1";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bind_param('ii', $idPartida, $idUsuario);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }
}
