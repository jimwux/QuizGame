<?php

if (!defined('BASE_PATH')) {
    $config = parse_ini_file(__DIR__ . '/../configuration/config.ini', true);
    define('BASE_PATH', $config['app']['base_path']);
}

class BaseController
{

    protected function redirectTo($url)
    {
        header("Location: " . BASE_PATH . $url);
        exit();
    }
    protected function  showError($view, $tituloError, $mensajeError){
        $error['error'] = ['tituloMensajeError' => $tituloError, 'mensajeError' => $mensajeError];
        $view->render("error", $error);
    }


}