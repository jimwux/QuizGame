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
    public function show(){
        $this->view->render("login");
    }

    public function processLogin(){
        $username = $_POST["username"];
        $password = $_POST["password"];

        if(empty($username) && empty($password)){
            $this->view->render("login", ["error", "El Usuario y la contraseña son obligatorios"]);
            return;
        }

        $userData = $this->model->login($username, $password);



    }

}