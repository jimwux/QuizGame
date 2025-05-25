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
        if(!isset($_SESSION['username'])){
            $this->view->render("login");
            print_r($_SESSION);
        }else{
            $data["user"] = $this->model->getUserByUsername(($_SESSION['username']));
            $this->view->render("profile", $data);
        }

    }

}