<?php

class RegisterController extends BaseController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    // Validar formularios, peticiones HTTP, redirecciones y comunicar al modelo


}