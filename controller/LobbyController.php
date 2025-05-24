<?php

class LobbyController extends BaseController
{
    private $view;

    public function __construct($view)
    {
        $this->view = $view;
    }

    // Validar formularios, peticiones HTTP, redirecciones y comunicar al modelo

    public function show()
    {
        $this->view->render("lobby", []);
    }


}