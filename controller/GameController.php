<?php

class GameController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function show()
    {

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION["id"] ?? null;
        $question = $this->model->getQuestionForUser($userId);

        $this->view->render("game", ["datos" => $question]);
    }

    public function getNextQuestion()
    {

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION["id"] ?? null;

        $verificarRespuesta = $this->model->verifyQuestionCorrect($_POST, $userId);

        if ($verificarRespuesta) {

//            $question = $this->model->getQuestionForUser($userId);

            $this->view->render("game", ["datos" => $verificarRespuesta]);
        } else {
//            $this->view->render("lobby", ["errores" => "Respuesta incorrecta"]);
            header("location: /QuizGame/lobby");
        }

    }


}