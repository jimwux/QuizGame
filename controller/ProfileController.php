<?php

class ProfileController extends BaseController
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
        $data["user"] = $this->model->getUserById();
        $this->view->render("profile", $data);

    }
    // Validar formularios, peticiones HTTP, redirecciones y comunicar al modelo


}