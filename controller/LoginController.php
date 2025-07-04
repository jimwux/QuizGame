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
            $this->redirectTo('lobby');
        }

    }

    public function logout(): void {
        $_SESSION = [];
        session_destroy();
        $this->redirectTo('login');
    }
}