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

    protected function sanitizeNulls($data) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->sanitizeNulls($v);
            }
            return $data;
        } else {
            return is_null($data) ? '' : $data;
        }
    }

}