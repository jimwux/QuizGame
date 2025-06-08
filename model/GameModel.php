<?php

class GameModel
{
    private $database;

    public function __construct(Database $database) // Asegúrate de que se inyecte la clase Database
    {
        $this->database = $database;
    }

    /**
     * Crea una nueva partida en la base de datos.
     */
    public function createGame(int $usuarioId): ?int
    {
        $fecha = date('Y-m-d H:i:s');
        $sql = "INSERT INTO partida (id_usuario, fecha, puntaje, finalizada) VALUES (?, ?, 0, 0)";
        $success = $this->database->execute($sql, "is", [$usuarioId, $fecha]);
        return $success ? $this->database->getLastInsertId() : null;
    }

    /**
     * Obtiene los detalles de una partida por su ID.
     */
    public function getGameById(int $partidaId): ?array
    {
        $sql = "SELECT * FROM partida WHERE id = ? LIMIT 1";
        $result = $this->database->query($sql, "i", [$partidaId]);
        return $result[0] ?? null;
    }

    /**
     * Registra una pregunta en la tabla partida_pregunta para la partida actual.
     * Solo inserta si la pregunta no ha sido registrada previamente para esta partida.
     */
    public function recordQuestionInGame(int $partidaId, int $idPregunta, int $idRespuestaCorrecta): void
    {
        $sqlCheck = "SELECT COUNT(*) FROM partida_pregunta WHERE id_partida = ? AND id_pregunta = ?";
        $count = $this->database->query($sqlCheck, "ii", [$partidaId, $idPregunta])[0]['COUNT(*)'] ?? 0;

        if ($count == 0) { // Solo insertar si es una pregunta nueva para este juego
            $sql = "INSERT INTO partida_pregunta (id_partida, id_pregunta, id_respuesta, respondida_correctamente, orden_pregunta) VALUES (?, ?, ?, NULL, NULL)";
            $this->database->execute($sql, "iii", [$partidaId, $idPregunta, $idRespuestaCorrecta]);
        }
    }

    /**
     * Obtiene la última pregunta no respondida asociada a una partida.
     */
    public function getUnansweredQuestionForGame(int $partidaId): ?array
    {
        $query = "SELECT p.*, pp.id_respuesta AS id_respuesta_correcta
                  FROM pregunta p
                  JOIN partida_pregunta pp ON pp.id_pregunta = p.id
                  WHERE pp.id_partida = ? AND pp.respondida_correctamente IS NULL
                  ORDER BY pp.id DESC LIMIT 1";
        $result = $this->database->query($query, "i", [$partidaId]);
        return $result[0] ?? null;
    }

    /**
     * Verifica si un usuario es "nuevo" (ha respondido menos de 10 preguntas).
     */
    public function verificarSiEsUnUsuarioNuevo(int $userId): int
    {
        $query = "SELECT COUNT(*) AS totalRespondidas FROM pregunta_usuario WHERE id_usuario = ?";
        $result = $this->database->query($query, "i", [$userId]);
        return $result[0]['totalRespondidas'] ?? 0;
    }

    /**
     * Obtiene la información de una pregunta, incluyendo cuántas veces ha sido mostrada y respondida correctamente.
     */
    public function verificarSiEsUnaPreguntaNueva(int $idPregunta): ?array
    {
        $query = "SELECT veces_mostrada, veces_respondida_correctamente FROM pregunta WHERE id = ?";
        $result = $this->database->query($query, "i", [$idPregunta]);
        return $result[0] ?? null;
    }

    /**
     * Obtiene una pregunta aleatoria de la base de datos.
     */
    public function obtenerUnaPreguntaAleatoria(): ?array
    {
        $query = "SELECT * FROM pregunta ORDER BY RAND() LIMIT 1";
        $result = $this->database->query($query); // No necesita parámetros
        return $result[0] ?? null;
    }

    /**
     * Verifica si una pregunta ya fue respondida por el usuario.
     */
    public function verificarQueNoSeaUnaPreguntaRespondidaPorElUsuarioPreviamente(int $idPregunta, int $idUsuario): bool
    {
        $query = "SELECT 1 FROM pregunta_usuario WHERE id_usuario = ? AND id_pregunta = ? LIMIT 1";
        $result = $this->database->query($query, "ii", [$idUsuario, $idPregunta]);
        return !empty($result);
    }

    /**
     * Calcula la dificultad de una pregunta basada en su rendimiento.
     */
    public function calcularDificultadPregunta(int $idPregunta): string
    {
        $info = $this->verificarSiEsUnaPreguntaNueva($idPregunta);
        if (!$info || $info['veces_mostrada'] < 10) {
            return "media"; // Por defecto si se ha mostrado pocas veces
        }

        $promedio = $info['veces_respondida_correctamente'] / $info['veces_mostrada'];
        if ($promedio > 0.7) {
            return "facil";
        } elseif ($promedio < 0.3) {
            return "dificil";
        }
        return "media";
    }

    /**
     * Calcula la dificultad del usuario basada en su ratio de respuestas correctas.
     */
    public function calcularDificultadUsuario(int $idUsuario): string
    {
        $query = "SELECT COUNT(*) as totalRespondidas, SUM(CASE WHEN es_correcta = 1 THEN 1 ELSE 0 END) AS totalCorrectas FROM pregunta_usuario WHERE id_usuario = ?";
        $result = $this->database->query($query, "i", [$idUsuario]);
        $data = $result[0] ?? ['totalRespondidas' => 0, 'totalCorrectas' => 0];

        if ($data["totalRespondidas"] == 0) {
            return "media";
        }

        $promedio = $data['totalCorrectas'] / $data['totalRespondidas'];
        if ($promedio > 0.7) {
            return "dificil"; // Un usuario que responde bien, se le da dificultad difícil
        } elseif ($promedio < 0.3) {
            return "facil"; // Un usuario que responde mal, se le da dificultad fácil
        }
        return "media";
    }

    /**
     * Obtiene una pregunta que el usuario aún no ha respondido.
     */
    public function obtenerPreguntaNoRepetida(int $userId): ?array
    {
        $query = "SELECT p.*
                  FROM pregunta p
                  WHERE p.id NOT IN (
                      SELECT pu.id_pregunta
                      FROM pregunta_usuario pu
                      WHERE pu.id_usuario = ?
                  )
                  ORDER BY RAND() LIMIT 1";
        $result = $this->database->query($query, "i", [$userId]);
        return $result[0] ?? null;
    }

    /**
     * Incrementa el contador de veces que una pregunta ha sido mostrada.
     */
    public function incrementarVecesMostradas(int $idPregunta): void
    {
        $sql = "UPDATE pregunta SET veces_mostrada = veces_mostrada + 1 WHERE id = ?";
        $this->database->execute($sql, "i", [$idPregunta]);
    }

    /**
     * Selecciona una pregunta para el usuario basándose en su dificultad y el historial.
     */
    public function getQuestionForUser(int $userId, int $partidaId): ?array
    {
        $totalRespuestasUsuario = $this->verificarSiEsUnUsuarioNuevo($userId);
        $esUsuarioNuevo = $totalRespuestasUsuario < 10;
        $dificultadDeseadaUsuario = "media";

        if (!$esUsuarioNuevo) {
            $dificultadDeseadaUsuario = $this->calcularDificultadUsuario($userId);
        }

        $question = null;
        $maxAttempts = 50; // Limitar los intentos para encontrar una pregunta
        $attempts = 0;

        do {
            $question = $this->obtenerUnaPreguntaAleatoria();
            if (!$question) {
                return null; // No hay preguntas disponibles
            }

            $idPregunta = $question['id'];
            $vecesMostradaInfo = $this->verificarSiEsUnaPreguntaNueva($idPregunta);
            $vecesMostrada = $vecesMostradaInfo['veces_mostrada'] ?? 0;
            $yaRespondida = $this->verificarQueNoSeaUnaPreguntaRespondidaPorElUsuarioPreviamente($idPregunta, $userId);

            $dificultadPreguntaActual = "media";
            if ($vecesMostrada >= 10) {
                $dificultadPreguntaActual = $this->calcularDificultadPregunta($idPregunta);
            }

            $cumpleDificultad = ($esUsuarioNuevo && $dificultadPreguntaActual == "media") ||
                (!$esUsuarioNuevo && $dificultadPreguntaActual == $dificultadDeseadaUsuario);

            $attempts++;
        } while (($yaRespondida || !$cumpleDificultad) && $attempts < $maxAttempts);

        // Si después de los intentos no se encuentra una que cumpla los criterios,
        // o si el usuario ya respondió todas las preguntas que cumplen ese criterio,
        // buscar una pregunta que no haya sido respondida por el usuario, sin importar la dificultad.
        if ($question === null || $yaRespondida || !$cumpleDificultad) {
            $question = $this->obtenerPreguntaNoRepetida($userId);
            if (!$question) {
                return null; // El usuario respondió todas las preguntas disponibles
            }
        }

        // Incrementar veces_mostrada para la pregunta que se va a mostrar
        $this->incrementarVecesMostradas($question['id']);

        $idCategoria = $question["id_categoria"];
        $infoCategory = $this->getCategory($idCategoria);
        $answers = $this->getAnswers($question["id"]);

        return ["question" => $question, "answers" => $answers, "category" => $infoCategory];
    }

    /**
     * Obtiene la información de una categoría específica.
     */
    public function getCategory(int $idCategory): ?array
    {
        $query = "SELECT * FROM categoria WHERE id = ?";
        $result = $this->database->query($query, "i", [$idCategory]);
        return $result[0] ?? null;
    }

    /**
     * Obtiene las respuestas para una pregunta específica.
     */
    public function getAnswers(int $idQuestion): array
    {
        $query = "SELECT * FROM respuesta WHERE id_pregunta = ?";
        return $this->database->query($query, "i", [$idQuestion]);
    }

    /**
     * Verifica la respuesta del usuario y actualiza el estado del juego.
     * @param array $infoAnswer Debe contener 'idQuestion', 'es_correcta' (0 o 1), 'idAnswerSelected'.
     * @return bool True si la respuesta fue correcta, false en caso contrario.
     */
    public function verifyQuestionAndAdvance(array $infoAnswer, int $userId, int $partidaId): bool
    {
        $idQuestion = $infoAnswer["idQuestion"];
        $esCorrecta = $infoAnswer["es_correcta"]; // 0 o 1 o "timeout"
        $idAnswerSelected = $infoAnswer["idAnswerSelected"];

        // Marcar la pregunta en partida_pregunta como respondida (correcta o incorrecta)
        // Si es "timeout", se considera incorrecta (0).
        $correctlyAnsweredFlag = ($esCorrecta === "timeout") ? 0 : (int)$esCorrecta;
        $sql = "UPDATE partida_pregunta SET respondida_correctamente = ? WHERE id_partida = ? AND id_pregunta = ?";
        $this->database->execute($sql, "iii", [$correctlyAnsweredFlag, $partidaId, $idQuestion]);

        // Registrar la respuesta del usuario en la tabla 'pregunta_usuario'
        $sqlInsertUserAnswer = "INSERT INTO pregunta_usuario (id_usuario, id_pregunta, id_respuesta, es_correcta) VALUES (?, ?, ?, ?)";
        $this->database->execute($sqlInsertUserAnswer, "iiii", [$userId, $idQuestion, $idAnswerSelected, $correctlyAnsweredFlag]);

        // Si la respuesta fue correcta, actualizar el contador de respuestas correctas de la pregunta y el puntaje de la partida
        if ($correctlyAnsweredFlag === 1) {
            $sqlUpdateQuestionStats = "UPDATE pregunta SET veces_respondida_correctamente = veces_respondida_correctamente + 1 WHERE id = ?";
            $this->database->execute($sqlUpdateQuestionStats, "i", [$idQuestion]);

            $sqlUpdateScore = "UPDATE partida SET puntaje = puntaje + 1 WHERE id = ?";
            $this->database->execute($sqlUpdateScore, "i", [$partidaId]);
            return true; // Respuesta correcta
        }

        return false; // Respuesta incorrecta o timeout
    }

    /**
     * Obtiene el puntaje actual de una partida.
     */
    public function getScore(int $partidaId): int
    {
        $sql = "SELECT puntaje FROM partida WHERE id = ? LIMIT 1";
        $result = $this->database->query($sql, "i", [$partidaId]);
        return $result[0]['puntaje'] ?? 0;
    }

    /**
     * Finaliza la partida y guarda el puntaje final.
     */
    public function saveGame(int $partidaId, int $puntaje): void
    {
        $sql = "UPDATE partida SET puntaje = ?, finalizada = 1 WHERE id = ?";
        $this->database->execute($sql, "ii", [$puntaje, $partidaId]);
    }

    /**
     * Guarda un resumen de la partida en la tabla resumen_partida.
     */
    public function guardarResumenPartida(
        int $idPartida,
        int $idUsuario
    ): void {
        $cantidadCorrectas = $this->getScore($idPartida);
        // Podrías obtener la cantidad de intentos de partida_pregunta WHERE id_partida = $idPartida
        $cantidadIntentos = $this->database->query("SELECT COUNT(*) AS total FROM partida_pregunta WHERE id_partida = ?", "i", [$idPartida])[0]['total'] ?? 0;

        // Estos valores deberían ser dinámicos si se quieren capturar
        $idCategoria = null;
        $idDificultad = null;
        $tiempoPromedioRespuesta = null; // Necesitarías lógica para calcular esto

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
        ";
        // Aquí debes manejar NULLs correctamente con bind_param.
        // Si una variable es NULL, se pasa como NULL.
        // El tipo 'i' para int y 's' para string, etc.
        $this->database->execute(
            $sql,
            "iiiiiiis", // Tipos de los parámetros
            [
                $idPartida,
                $idUsuario,
                $cantidadCorrectas,
                $cantidadIntentos,
                $idCategoria, // MySQL convierte NULLs directamente
                $idDificultad, // MySQL convierte NULLs directamente
                $cantidadCorrectas, // Puntaje es lo mismo que cantidad_correctas en este caso
                $tiempoPromedioRespuesta // MySQL convierte NULLs directamente
            ]
        );
    }

    /**
     * Obtiene el resumen de una partida.
     */
    public function getResumenPartida(int $idPartida, int $idUsuario): ?array
    {
        $sql = "SELECT * FROM resumen_partida WHERE id_partida = ? AND id_usuario = ? LIMIT 1";
        $result = $this->database->query($sql, "ii", [$idPartida, $idUsuario]);
        return $result[0] ?? null;
    }
}
