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

    public function logout(): void {
        session_start();
        session_destroy();
        header('Location: index.php?controller=login&method=mostrarFormularioLogin');
        exit();
    }
}