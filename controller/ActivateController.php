<?php

require_once 'model/UserModel.php';
class ActivateController
{
    private $userModel;

    public function __construct($database)
    {
        $this->userModel = new UserModel($database);
    }
    
    public function activarCuenta(string $token): void {
        $estado = $this->userModel->activarUsuarioPorToken($token);

        switch ($estado) {
            case 'activado':
                $mensaje = "¡Cuenta activada correctamente! Ya podés iniciar sesión.";
                break;
            case 'ya_activado':
                $mensaje = "Esta cuenta ya fue activada previamente.";
                break;
            case 'token_invalido':
            default:
                $mensaje = "El token es inválido o expiró.";
                break;
        }

        require_once 'libs/Render.php';
        $renderer = new Render();
        $renderer->render('activate', ['mensaje' => $mensaje]);
        
    }
}