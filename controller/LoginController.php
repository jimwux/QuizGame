<?php

class LoginController extends BaseController
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
        $this->view->render("login", ["errors" => []]);
    }

    public function processLogin()
    {
        $username = $_POST["username"] ?? null;
        $password = $_POST["password"] ?? null;

        $validationData = $this->model->validateLogin($username, $password);

        if (!empty($validationData)) {
            return $this->view->render("login", ["errors" => $validationData]);
        } else {
            $this->model->login($username);
            header("location: /QuizGame/lobby");
            exit;
        }

    }

    public function logout(): void {
        session_start();
        session_destroy();
        header('Location: /QuizGame/login');
        exit();
    }
}