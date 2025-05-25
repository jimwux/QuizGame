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

    // Validar formularios, peticiones HTTP, redirecciones y comunicar al modelo
    public function show()
    {
        $this->view->render("login", ["errors" => []]);
    }

    public function processLogin()
    {
        $username = $_POST["username"] ?? null;
        $password = $_POST["password"] ?? null;

        $validationData = $this->model->validateLogin($username, $password);
//        echo "<pre>";
//        var_dump($validationData);
//        echo "</pre>";

        if (!empty($validationData)) {
            return $this->view->render("login", ["errors" => $validationData]);
        } else {
            $this->model->login($username);
            header("location: /QuizGame/lobby");
//            $this->view->render("lobby");
            exit;
        }

//        echo "empty" . empty($validationData) . "<br>";
//        echo "isset" . isset($validationData);
//        exit;


    }

}