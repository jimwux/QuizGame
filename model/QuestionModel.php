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
    if (!$this->alreadyReported($idUsuario, $idPregunta) && isset($motivo) && trim($motivo) !== '') {
        $sql = "INSERT INTO reporte_pregunta 
                    (id_pregunta, id_usuario, motivo, fecha_reporte, estado) 
                    VALUES (?, ?, ?, NOW(), false)";
        if ($this->database->execute($sql, [$idPregunta, $idUsuario, $motivo]))
            return true;
            }
            return false;
}
    public function alreadyReported($idUsuario,$idPregunta)
    {
        if($idUsuario !== null && $idPregunta !== null){
            $sql = "SELECT * FROM reporte_pregunta WHERE id_usuario = ? and id_pregunta = ?";
        }else{
            return true;
        }

        if ($this->database->query($sql, [$idUsuario, $idPregunta])) {return true;}
        return false;
    }
 }