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
        $nombreUsuario = $this->getUsuarioPorVista();
        $data["user"] = $this->model->getUserByUsername($nombreUsuario);
        if($data["user"]){

            $rol = $_SESSION["usuario_rol"] ?? 'jugador';
            $data["es_jugador"] = ($rol === "jugador");

            if ($data["es_jugador"]) {
                $data["partidas"] = $this->model->getGamesResultByUsername($nombreUsuario);
            }

            $data = $this->sanitizeNulls($data);
            $this->view->render("profile", $data);

        }else{
            $this->showError($this->view,'El perfil solicitado no existe.', 'El perfil que busca no existe o ha sido borrado.');
            }
    }

        public function getUsuarioPorVista()
    {
        return (!empty($_GET['username']) ? $_GET['username'] : $_SESSION["username"]);
    }

    public function generateQR () {
        if (isset($_GET['username'])) {
            $nombreUsuario = $_GET['username'];
            $urlPerfil = "http://localhost/QuizGame/profile?username=" . urlencode($nombreUsuario);

            header('Content-Type: image/png');
            QRGenerator::generarQR($urlPerfil, false);
            exit;
        } else {
            http_response_code(400);
            echo "Falta el parámetro username";
        }
    }

}