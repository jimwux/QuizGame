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
    // Validar formularios, peticiones HTTP, redirecciones y comunicar al modelo
    public function show()
    {
        $this->validateSession();
        $data["user"] = $this->model->getUserByUsername(($_SESSION['username']));
        $data["partidas"] = $this->model->getGamesResultByUser($_SESSION['id']);
        $this->view->render("profile", $data);
    }

}