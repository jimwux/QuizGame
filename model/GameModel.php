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
        return $conn->insert_id ?? null;
    }


    public function getGameById(int $partidaId): ?array
    {
        $sql = "SELECT * FROM partida WHERE id = $partidaId LIMIT 1";
        $result = $this->database->query($sql);

        if (count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    public function getGamesResultByUser($usuarioId)
    {
        $usuarioId = (int)$usuarioId;
        $sql = "SELECT rp.*, 
            c.nombre AS nombre_categoria, c.color AS color_categoria
            FROM resumen_partida rp
            JOIN categoria c ON rp.id_categoria = c.id
            WHERE rp.id_usuario = 1
            ORDER BY fecha_partida DESC
            LIMIT 4;";

        return $this->database->query($sql);
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


    public function getQuestionForUser($userId) // Obtiene una pregunta que no fue respondida por el usuario
    {
        $userRatio = $this->getUserCorrectRatio($userId);
        $defaultDifficulty = "Media";

        if ($userRatio > 0.7) {
            $defaultDifficulty = "Dificil";
        } elseif ($userRatio < 0.3) {
            $defaultDifficulty = "Facil";
        }

        // Query para obtener una pregunta que coincida con al dificultad del usuario
        $query = "SELECT
                    p.*,
                    CASE
                        WHEN p.veces_mostrada = 0 THEN 'Media'
                        WHEN (p.veces_respondida_correctamente * 1.0 / p.veces_mostrada) > 0.7 THEN 'Facil'
                        WHEN (p.veces_respondida_correctamente * 1.0 / p.veces_mostrada) < 0.3 THEN 'Dificil'
                        ELSE 'Media'
                    END AS dificultad_global
                  FROM
                      pregunta p
                    WHERE
                        p.id NOT IN (
                            SELECT pu.id_pregunta
                            FROM pregunta_usuario pu
                            WHERE pu.id_usuario = ?
                        )
                    AND (
                        CASE
                            WHEN p.veces_mostrada = 0 THEN 'Media'
                            WHEN (p.veces_respondida_correctamente * 1.0 / p.veces_mostrada) > 0.7 THEN 'Facil'
                            WHEN (p.veces_respondida_correctamente * 1.0 / p.veces_mostrada) < 0.3 THEN 'Dificil'
                        ELSE 'Media'
                        END
                    ) = ?
                ORDER BY RAND()
                LIMIT 1";

        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->bind_param("is", $userId, $defaultDifficulty);
        $stmt->execute();
        $result = $stmt->get_result();
        $question = $result->fetch_assoc();
        $stmt->close();

        // En caso de que no se encuentre una pregunta acorde a LA DIFICULTAD del usuario se busca una pregunta que NO haya respondido
        if (!$question) {
            $query = "SELECT
                        p.*,
                        CASE
                            WHEN p.veces_mostrada = 0 THEN 'Media'
                            WHEN (p.veces_respondida_correctamente * 1.0 / p.veces_mostrada) > 0.7 THEN 'Facil'
                            WHEN (p.veces_respondida_correctamente * 1.0 / p.veces_mostrada) < 0.3 THEN 'Dificil'
                            ELSE 'Media'
                        END AS dificultad_global
                      FROM
                          pregunta p
                        WHERE
                            p.id NOT IN (
                                SELECT pu.id_pregunta
                                FROM pregunta_usuario pu
                                WHERE pu.id_usuario = ?
                            )
                    ORDER BY RAND()
                    LIMIT 1";

            $stmt = $this->database->getConnection()->prepare($query);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $question = $result->fetch_assoc();
            $stmt->close();
        }

        // Si el usuario respondio todas las preguntas deberia retornar un mensaje que se acabaron las preguntas
        if (!$question) {
            return null;
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

    public function verifyQuestionCorrect($infoAnswer, $userId) // Verifica y retorna una nueva pregunta
    {

        $idQuestion = $infoAnswer["idQuestion"];
        $esCorrecta = $infoAnswer["es_correcta"];

        // 1ro: Actualizar la BD -> aumentar la cantidad de veces VISTA + 1 en la pregunta para futuras estadisticas
        $query = "UPDATE pregunta SET veces_mostrada = veces_mostrada + 1 WHERE id = $idQuestion";
        $stmt = $this->database->getConnection()->prepare($query);
        $stmt->execute();
        $stmt->close();

        // 2do: Para obtener el id de la respuesta hago una consulta
        $query3 = "SELECT * FROM respuesta WHERE id_pregunta = ?";
        $stmt3 = $this->database->getConnection()->prepare($query3);
        $stmt3->bind_param("i", $idQuestion);
        $stmt3->execute();
        $result = $stmt3->get_result()->fetch_assoc();

        $idAnswer = $result["id"];

        // 3ro: Guardar la pregunta que ya respondio asi no se repita a futuro
        $query4 = "INSERT INTO pregunta_usuario (id_usuario, id_pregunta, id_respuesta, es_correcta) VALUES (?, ? ,? ,? )";
        $stmt4 = $this->database->getConnection()->prepare($query4);
        $stmt4->bind_param("iiii", $userId, $idQuestion, $idAnswer, $esCorrecta);
        $stmt4->execute();
        $stmt4->close();

        if ($esCorrecta == 1) {
            // 4to: Actualizar la DB -> aumentar la cantidad de veces CORRECTAMENTE respondida + 1
            $query2 = "UPDATE pregunta SET veces_respondida_correctamente = veces_respondida_correctamente + 1 WHERE id = $idQuestion";
            $stmt2 = $this->database->getConnection()->prepare($query2);
            $stmt2->execute();
            $stmt2->close();

            // 5to: Como respondio correctamente la pregunta le debemos devolver otra que no se haya repetido previamente
            return $this->getQuestionForUser($userId);
        }

        // Aca se puede retornar un mensaje de que la respuesta que elijio esta mal
        return null;


    }

}