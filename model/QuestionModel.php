<?php

class QuestionModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function guardarSugerencia($datosPregunta, $respuestas)
    {
        try {
            $this->database->beginTransaction();

            // Insertar pregunta sugerida
            $sqlPregunta = "INSERT INTO sugerencia_pregunta (id_usuario, texto, id_categoria) VALUES (?, ?, ?)";
            $this->database->execute($sqlPregunta, [
                $datosPregunta["id_usuario"],
                $datosPregunta["texto"],
                $datosPregunta["id_categoria"]
            ]);

            $idSugerencia = $this->database->lastInsertId();

            // Insertar respuestas sugeridas
            $sqlRespuesta = "INSERT INTO sugerencia_respuesta (id_sugerencia, texto, es_correcta) VALUES (?, ?, ?)";
            foreach ($respuestas as $r) {
                $this->database->execute($sqlRespuesta, [
                    $idSugerencia,
                    $r["texto"],
                    $r["es_correcta"] ? 1 : 0
                ]);
            }

            $this->database->commit();
            return true;

        } catch (Exception $e) {
            $this->database->rollBack();
            return false;
        }
    }

    /**
     * Obtiene las categorías de la BD
     */
    public function obtenerCategorias()
    {
        $sql = "SELECT * FROM categoria";
        return $this->database->query($sql);
    }



    public function obtenerPreguntasSugeridas()
    {
        $sql = "
            SELECT
                sp.id AS id_pregunta,
                sp.texto,
                sp.opcionA,
                sp.opcionB,
                sp.opcionC,
                sp.opcionD,
                sp.fecha_sugerencia,
                u.nombre_completo AS nombre_usuario,
                c.nombre AS nombre_categoria,
                c.color AS categoria_color
            FROM
                sugerencia_pregunta AS sp
            JOIN
                usuarios AS u ON sp.id_usuario = u.id
            JOIN
                categoria AS c ON sp.id_categoria = c.id
            WHERE
                sp.estado = 'pendiente'
            ORDER BY
                sp.fecha_sugerencia DESC;
        ";

        $preguntasSugeridasCompletas = $this->database->query($sql);

        return $preguntasSugeridasCompletas;
    }

    public function aprobarPreguntaSugerida($idPregunta)
    {

        $conexion = $this->database->getConnection();

        $conexion->autocommit(FALSE);

        try {
            // Obtengo la pregunta sugerida de sugerencia_pregunta
            $sql = "SELECT * FROM sugerencia_pregunta WHERE id = ?";
            $stmt = $this->database->getConnection()->prepare($sql);
            $stmt->bind_param("i", $idPregunta);
            $stmt->execute();
            $preguntaAprobada = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            // Agrego la pregunta a la tabla pregunta
            $sql2 = "INSERT INTO pregunta (texto, id_categoria, id_creador, estado) VALUES (?, ?, ?,  'activa')";
            $stmt2 = $this->database->getConnection()->prepare($sql2);
            $stmt2->bind_param("sii", $preguntaAprobada["texto"], $preguntaAprobada["id_categoria"], $preguntaAprobada["id_usuario"]);
            $stmt2->execute();
            $stmt2->close();

            // Agrego las respuestas


            // Elimino la pregunta sugerida de la tabla sugerencia_pregunta
            $sql3 = "DELETE FROM sugerencia_pregunta WHERE id = ?";
            $stmt3 = $this->database->getConnection()->prepare($sql3);
            $stmt3->bind_param("i", $idPregunta);
            $stmt3->execute();
            $stmt3->close();

            $conexion->commit();
            $conexion->autocommit(TRUE);
            return true;
        } catch (Exception $e){
            $conexion->rollback();
            $conexion->autocommit(FALSE);
            echo "Error en la transaccion " . $e->getMessage();
            return false;
        }

    }

    public function rechazarPreguntaSugerida($idPregunta)
    {
        $sql = "DELETE FROM sugerencia_pregunta WHERE id = ?";
        $stmt = $this->database->getConnection()->prepare($sql);
        $stmt->bind_param("i", $idPregunta);
        $stmt->execute();
        $stmt->close();
    }


}