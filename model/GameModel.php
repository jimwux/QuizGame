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

        $sql = "INSERT INTO partida (id_usuario, fecha, puntaje, finalizada) VALUES ($usuarioId, '$fecha', 0, 0)";
        $this->database->execute($sql);

        $conn = $this->database->getConnection();
        $idPartida = $conn->insert_id;
        $_SESSION['partidaId'] = $idPartida;
        return $conn->insert_id ?? null;
    }


    public function getGameById(int $partidaId, $pregunta): ?array
    {
        $sql = "SELECT * FROM partida WHERE id = $partidaId LIMIT 1";
        $result = $this->database->query($sql);

        $idPregunta = $pregunta["question"]["id"];
        $idRespuesta = null;

        foreach ($pregunta["answers"] as $answer) {
            if ($answer["es_correcta"] == 1) {
                $idRespuesta = $answer["id"];
                break;
            }
        }

        $sql2 = "INSERT INTO partida_pregunta (id_partida, id_pregunta, id_respuesta, respondida_correctamente, orden_pregunta) VALUES (?,?,? ,null, null)";
        $stmt2 = $this->database->getConnection()->prepare($sql2);
        $stmt2->bind_param("iii", $partidaId, $idPregunta, $idRespuesta);
        $stmt2->execute();

        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public function obtenerUltimaPregunta($usuarioId)
    {
        $query = "SELECT p.* FROM pregunta p
                    JOIN partida_pregunta pp ON pp.id_pregunta = p.id
                    JOIN partida pa ON pa.id = pp.id_pregunta 
                    WHERE pa.id_usuario = ? AND pp.respondida_correctamente IS NULL
                    ORDER BY pp.id DESC LIMIT 2";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all();

        return $result[1];
    }

    // Este metodo por ahora no se usa
    public function verifyQuestionRepeat($userId, $questionId)  // Verifica si una pregunta ya fue respondida por el usuario
    {
        $query = "SELECT 1 
                    FROM partida_pregunta pp
                    JOIN partida pa ON pa.id = pp.id_partida
                    WHERE pa.id_usuario = ?
                    AND pp.id_pregunta = ? LIMIT 1";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("ii", $userId, $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $isRepeated = $result->fetch_assoc() != null;
        $stmt->close();
        return $isRepeated;
    }

    public function getNumberOfRowsInPregunta() // Obtiene la CANTIDAD de preguntas que hay en la BD
    {
        $numberOfQuestions = "SELECT COUNT(*) AS total FROM pregunta";
        $queryPrepare = $this->database->getConnection()->prepare($numberOfQuestions);
        $queryPrepare->execute();
        $result = $queryPrepare->get_result()->fetch_assoc();
        $queryPrepare->close();
        return $result ? (int)$result['total'] : 0;
    }

    public function getRandomQuestionFromAll() // Obtiene una pregunta random (no se usa por ahora)
    {
        $cantidad = $this->getNumberOfRowsInPregunta();
        if ($cantidad == 0) {
            return null;
        }

        $numberRandom = rand(0, $cantidad - 1);

        $query = "SELECT * FROM pregunta LIMIT 1 OFFSET ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $numberRandom);
        $stmt->execute();
        $result = $stmt->get_result();
        $question = $result->fetch_assoc();
        $stmt->close();
        return $question;
    }

    public function getUserCorrectRatio($userId)  // Retorna el valor de dificultad por usuario
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

        // Condicional para saber si es usuario nuevo (o boludo y se equivoco en todas), le ponemos dificultad media
        if ($result['total_respuestas'] == 0) {
            return 0.5;
        }
        return ($result['respuestas_correctas'] * 1.0) / $result['total_respuestas'];
    }

    // 1ro: Si es un usuario nuevo mostrar las primeras 10 preguntas de nivel medio
    // 2do: Si la pregunta se mostro menos de 10 veces es de nivel medio por default
    // 3ro: La dficultad de la pregunta se saca  veces_mostrada / veces_respondida_correctamente
    // 4to: Retornar una pregunta
    // 5to: En caso de que la pregunta que se retorno previamente a ese usuario, volver a obtener otra pregunta que nunca le toco
    // 6to: Si el usuario ya respondio todas las preguntas mandar al lobby

    public function verificarSiEsUnUsuarioNuevo($userId)
    {
        // Si tiene menos de 10 preguntas respondidas es un usuario nuevo
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

    public function obtenerUnaPreguntaAleatoria()
    {
        $query = "SELECT * FROM pregunta ORDER BY RAND() LIMIT 1";
        $stmt = $this->database->getConnection()->prepare($query);

        // Verifica si la preparación fue exitosa
        if ($stmt === false) {
            // Manejar el error de preparación, por ejemplo:
            error_log("Error al preparar la consulta en obtenerUnaPreguntaAleatoria: " . $this->database->getConnection()->error);
            return null;
        }

        $stmt->execute(); // Ejecuta la consulta

        // Verifica si la ejecución fue exitosa
        if ($stmt->errno) { // $stmt->errno es 0 si no hay error
            error_log("Error al ejecutar la consulta en obtenerUnaPreguntaAleatoria: " . $stmt->error);
            $stmt->close();
            return null;
        }

        $result = $stmt->get_result(); // Ahora $stmt es un objeto mysqli_stmt, puedes llamar a get_result()
        $question = $result->fetch_assoc();
        $stmt->close();

        return $question; // Asegúrate de retornar la pregunta
    }

    public function verificarQueNoSeaUnaPreguntaRespondidaPorElUsuarioPreviamente($idPregunta, $idUsuario)
    {

        $query = "SELECT * FROM pregunta_usuario pu 
         JOIN pregunta p ON p.id = pu.id_pregunta  WHERE id_usuario = ? AND p.id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("ii", $idUsuario, $idPregunta);
        $stmt->execute();
        $result = $stmt->get_result();
        $fueRespondida = $result->fetch_assoc() != null;
        $stmt->close();
        return $fueRespondida;
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
        if ($result['veces_mostrada'] > 10) {
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

        // Condicion para que no divida por cero y lanze error
        if ($result["totalRespondidas"] == 0) {
            return "media";;
        }

        $promedio = $result['totalCorrectas'] / $result['totalRespondidas'];
        $dificultad = "media";
        if ($promedio > 0.7) {
            $dificultad = "dificil";
        } else if ($promedio < 0.3) {
            $dificultad = "facil";
        }

        return $dificultad;
    }

    public function obtenerPreguntaNoRepetira($userId)
    {
        $query = "
            SELECT p.*
            FROM pregunta p
            WHERE p.id NOT IN (
                SELECT pu.id_pregunta
                FROM pregunta_usuario pu
                WHERE pu.id_usuario = ?
            )
            ORDER BY RAND()
            LIMIT 1
        ";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $question = $result->fetch_assoc();
        $stmt->close();
        return $question;
    }

    public function incrementarVecesMostradas($idPregunta)
    {
        $query = "UPDATE pregunta SET veces_mostrada = veces_mostrada + 1 WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $stmt->close();
    }


    public function getQuestionForUser($userId) // Obtiene una pregunta que no fue respondida por el usuario
    {
        $cantidadPreguntasRespondidas = $this->verificarSiEsUnUsuarioNuevo($userId);

        $totalRespuestasUsuario = $this->verificarSiEsUnUsuarioNuevo($userId); // Usamos tu método ajustado
        $esUsuarioNuevo = $totalRespuestasUsuario < 10;
        $dificultadDeseadaUsuario = "media"; // Default para usuarios nuevos

        if (!$esUsuarioNuevo) {
            $dificultadDeseadaUsuario = $this->calcularDificultadUsuario($userId);
        }

        $question = null;
        $maxAttempts = 50;
        $attempts = 0;

        do {
            $question = $this->obtenerUnaPreguntaAleatoria(); // Tu método
            if (!$question) {
                return null;
            }

            $idPregunta = $question['id'];
            $vecesMostrada = $this->verificarSiEsUnaPreguntaNueva($idPregunta)['veces_mostrada'] ?? 0; // Tu método
            $yaRespondida = $this->verificarQueNoSeaUnaPreguntaRespondidaPorElUsuarioPreviamente($idPregunta, $userId); // Tu método ajustado

            $dificultadPreguntaActual = "media";
            if ($vecesMostrada >= 10) {
                $dificultadPreguntaActual = $this->calcularDificultadPregunta($idPregunta); // Tu método
            }

            $cumpleDificultad = ($esUsuarioNuevo && $dificultadPreguntaActual == "media") ||
                (!$esUsuarioNuevo && $dificultadPreguntaActual == $dificultadDeseadaUsuario);


            $attempts++;
        } while (($yaRespondida || !$cumpleDificultad) && $attempts < $maxAttempts);


        // Si después de los intentos, no encontramos una pregunta que cumpla la dificultad y que no haya sido respondida
        if ($question === null || $yaRespondida || !$cumpleDificultad) {
            // Último intento: Buscar cualquier pregunta NO RESPONDIDA, sin importar la dificultad
            $question = $this->obtenerPreguntaNoRepetira($userId); // Necesitarías este método nuevo, o ajustar el tuyo
            if (!$question) {
                return null; // El usuario respondió todas las preguntas
            }
            // Incrementar veces_mostrada para esta pregunta, ya que será la que se retorne
            $this->incrementarVecesMostradas($question['id']); // <--- ESTA ES LA LÍNEA 304
        } else {
            // Incrementar veces_mostrada para esta pregunta, ya que será la que se retorne
            $this->incrementarVecesMostradas($question['id']);
        }


        $idCategoria = $question["id_categoria"];
        $infoCategory = $this->getCategory($idCategoria);

        $idQuestion = $question["id"];
        $answers = $this->getAnswers($idQuestion);


        $infoQuestionComplete = ["question" => $question, "answers" => $answers, "category" => $infoCategory];

        return $infoQuestionComplete;
    }

    public function getCategory($idCategory) // Obtiene la informacion de una categoria especifica
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

    public function getAnswers($idQuestion) // Obtiene las respuestas de una pregunta
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

    public function verificarSiSeRecargoLaPagina($partidaId)
    {
        $sql2 = "SELECT pp.respondida_correctamente
                FROM partida_pregunta pp
                JOIN pregunta p ON p.id = pp.id_pregunta
                WHERE pp.id_partida = ? ORDER BY pp.id DESC LIMIT 1";
//        $sql2 = "UPDATE partida_pregunta pp
//                JOIN partida p ON p.id = pp.id_partida
//                 SET (respondida_correctamente) VALUES ($esCorrecta)
//                 WHERE id = $idPartida AND pp.respondida_correctamente IS NULL";
        $stmt2 = $this->database->getConnection()->prepare($sql2);
        $stmt2->bind_param("i", $partidaId);
        $stmt2->execute();
        $resultado = $stmt2->get_result();
        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc()["respondida_correctamente"]; // Devuelve el valor
        } else {
            return null; // Si no encuentra resultados
        }
    }

    public function verifyQuestionCorrect($infoAnswer, $userId, $partidaId)
    {
        $idQuestion = $infoAnswer["idQuestion"];
        $esCorrecta = $infoAnswer["es_correcta"];
        $idPartida = SesionController::obtenerEstadoPartida();

        if ($esCorrecta === "timeout") {
            return null;
        }

        $sql2 = "UPDATE partida_pregunta pp
                JOIN partida p ON p.id = pp.id_partida
                 SET (respondida_correctamente) VALUES ($esCorrecta)
                 WHERE pp.id_partida = $idPartida AND pp.respondida_correctamente IS NULL";
        $stmt2 = $this->database->getConnection()->prepare($sql2);
        $stmt2->bind_param("i", $partidaId);
        $stmt2->execute();

        // 1. Aumentar veces mostrada
        $query = "UPDATE pregunta SET veces_mostrada = veces_mostrada + 1 WHERE id = $idQuestion";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->execute();
        $stmt->close();

        // 2. Obtener id de la respuesta (¿por qué no usar la que el usuario seleccionó?)
        $query3 = "SELECT * FROM respuesta WHERE id_pregunta = ?";
        $stmt3 = $this->database->getConnection()->prepare($query3);
        $stmt3->bind_param("i", $idQuestion);
        $stmt3->execute();
        $result = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();

        $idAnswer = $result["id"];

        // 3. Registrar que el usuario respondió esta pregunta
        $query4 = "INSERT INTO pregunta_usuario (id_usuario, id_pregunta, id_respuesta, es_correcta) VALUES (?, ?, ?, ?)";
        $stmt4 = $this->database->getConnection()->prepare($query4);
        $stmt4->bind_param("iiii", $userId, $idQuestion, $idAnswer, $esCorrecta);
        $stmt4->execute();
        $stmt4->close();

        if ($esCorrecta == 1) {
            // 4. Aumentar respuestas correctas
            $query2 = "UPDATE pregunta SET veces_respondida_correctamente = veces_respondida_correctamente + 1 WHERE id = $idQuestion";
            $stmt2 = $this->database->getConnection()->prepare($query2);
            $stmt2->execute();
            $stmt2->close();

            // 5. Actualizar puntaje en la partida
            $query5 = "UPDATE partida SET puntaje = puntaje + 1 WHERE id = ?";
            $stmt5 = $this->database->getConnection()->prepare($query5);
            $stmt5->bind_param("i", $partidaId);
            $stmt5->execute();
            $stmt5->close();

            return $this->getQuestionForUser($userId);
        }

        // (opcional) retornar nueva pregunta
        return null;
    }

    public function getScore($partidaIdEntrada)
    {
        $partidaId = intval($partidaIdEntrada);

        $sql = "SELECT puntaje FROM partida WHERE id = $partidaId LIMIT 1";
        $res = $this->database->query($sql);

        if (count($res) > 0) {
            return (int)$res[0]['puntaje'];
        }

        return 0; // Si no existe la partida, devuelve 0 o lo que consideres
    }

    public function saveGame($partidaIdEntrada, int $puntaje): void
    { // guarda el puntaje y el id de la partida
        $partidaId = intval($partidaIdEntrada);

        $sql = "
            UPDATE partida 
            SET puntaje = $puntaje, finalizada = 1 
            WHERE id = $partidaId
        ";
        $this->database->execute($sql);
    }

    public function guardarResumenPartida(
        int $idPartida,
        int $idUsuario
    ): void
    {
        $cantidadCorrectas = $this->getScore($idPartida);
        $cantidadIntentos = 1;

        $idCategoria = null;
        $idDificultad = null;
        $puntaje = $this->getScore($idPartida);
        $tiempoPromedioRespuesta = null;

        // Asegurarse de que los nulls vayan como NULL en SQL (sin comillas)
        $idCategoriaSQL = is_null($idCategoria) ? "NULL" : $idCategoria;
        $idDificultadSQL = is_null($idDificultad) ? "NULL" : $idDificultad;
        $tiempoPromedioRespuestaSQL = is_null($tiempoPromedioRespuesta) ? "NULL" : $tiempoPromedioRespuesta;

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
                $idPartida,
                $idUsuario,
                $cantidadCorrectas,
                $cantidadIntentos,
                $idCategoriaSQL,
                $idDificultadSQL,
                $puntaje,
                $tiempoPromedioRespuestaSQL,
                NOW()
            )
        ";

        $this->database->execute($sql);
    }

    public function getResumenPartida(
        int $idPartida,
        int $idUsuario
    )
    {
        $sql = "SELECT * FROM resumen_partida WHERE id_partida = $idPartida AND id_usuario = $idUsuario LIMIT 1";
        return $res = $this->database->query($sql);
    }
}