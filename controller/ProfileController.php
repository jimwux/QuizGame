<?php

require_once __DIR__ . '/../libs/QRGenerator.php';
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
        $nombreUsuario = $this->getUsuarioPorVista();
        $data["user"] = $this->model->getUserByUsername($nombreUsuario);
        if($data["user"]){
            $data["partidas"] = $this->model->getGamesResultByUsername($nombreUsuario);

               $urlPerfil = "http://localhost/QuizGame/profile?username=$nombreUsuario";
             //  $rutaQR = "public/qrs/qr_$nombreUsuario.png";
                $rutaQR = __DIR__ . '/../public/qrs/qr_'.$nombreUsuario.'.png';
            // Generar QR
               QRGenerator::generarQR($urlPerfil, $rutaQR);
               $data["qr_imagen"] = $rutaQR;

            $this->view->render("profile", $data);

        }else{
            $this->showError($this->view,'El perfil solicitado no existe.', 'El perfil que busca no existe o ha sido borrado.');
            }}
        public function getUsuarioPorVista()
    {
        return (!empty($_GET['username']) ? $_GET['username'] : $_SESSION["username"]);
    }



}