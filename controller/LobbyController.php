<?php

class LobbyController extends BaseController
{
    private $view;

    public function __construct($view)
    {
        $this->view = $view;
    }

    public function show()
    {
        session_start();
        if (!isset($_SESSION['id'])) {
            header('Location: /QuizGame/login/show');
            exit;
        }

        $this->view->render("lobby", []);
    }


}