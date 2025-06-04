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
        $data["user"] = $this->model->getUserByUsername((!empty($_GET['username']) ? $_GET['username'] : $_SESSION["username"]));
        if($data["user"]){
            $data["partidas"] = $this->model->getGamesResultByUser($_SESSION['id']);
            $this->view->render("profile", $data);

        }else{
            $error['error'] = ['tituloMensajeError' => 'El perfil solicitado no existe.', 'mensajeError' => 'El perfil que busca no existe o ha sido borrado.'];
            $this->view->render("error", $error);
        }


    }

}