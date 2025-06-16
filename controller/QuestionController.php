<?php

class QuestionController extends BaseController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function showFormularioReporte($idPregunta){
        {

            $sql = "SELECT * FROM pregunta WHERE id = ?";
            $pregunta = $this->database->query($sql, [$idPregunta]);

            $nombreUsuario = $_SESSION['username'];

            $data["user"] = $nombreUsuario;
            $data["pregunta"] = $pregunta[0] ?? null;

            if ($data["pregunta"]){
                $this->view->render("formReport", $data);

            }else{
                $this->showError($this->view,'Error al buscar.', 'La pregunta o el usuario que solicitó no existen o han sido borrados.');
            }
        }


        }

    public function enviarReporte(){
       // $this->model->guardarReporte(''$idUsuario,$idPregunta,$_POST['motivo']);
    }

}