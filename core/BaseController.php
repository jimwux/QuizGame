<?php

if (!defined('BASE_PATH')) {
    $config = parse_ini_file(__DIR__ . '/../configuration/config.ini', true); // Asegurate de que la ruta esté bien
    define('BASE_PATH', $config['app']['base_path']);
}

class BaseController
{

    protected function redirectTo($url)
    {
        header("Location: " . BASE_PATH . $url);
        exit();
    }

    public function validateSession() {
        if (!isset($_SESSION['id'])) {
            $this->redirectTo('login');
        }
    }

}