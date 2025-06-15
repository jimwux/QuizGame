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



}