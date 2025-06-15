<?php

class QuestionModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
public function guardarReporte($idUsuario,$idPregunta,$motivo)
{
    if ($idUsuario !== null && $idPregunta !== null && $motivo !== null) {
        $sql = "INSERT INTO reporte_pregunta 
                    (id_pregunta, id_usuario, motivo, fecha_reporte, estado) 
                    VALUES (?, ?, ?, NOW(), false)";

        if ($this->database->execute($sql, [$idPregunta, $idUsuario, $motivo]))
            return true;
            }
            return false;
}
}