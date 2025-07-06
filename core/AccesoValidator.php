<?php

class AccesoValidator
{
    private $config;
    private $basePath;
    private $rol;
    private $logueado;

    public function __construct($config)
    {
        $this->config = $config;
        $this->basePath = $config["app"]["base_path"];
        $this->rol = $_SESSION['usuario_rol'] ?? null;
        $this->logueado = isset($_SESSION['id']);
    }

    public function validar($controller, $method)
    {
        $acceso = strtolower("$controller/$method");

        $this->impedirAccesoALoginYRegistro($controller, $method);
        $this->validarSesionParaPrivados($controller);
        $this->redirigirLobbySegunRol($controller);
        $this->validarSoloAdmin($controller);
        $this->validarSoloEditor($acceso);
        $this->validarSoloJugador($acceso);
    }

    private function impedirAccesoALoginYRegistro($controller, $method)
    {
        if (
            in_array(strtolower($controller), ['login', 'registro']) &&
            strtolower($method) !== 'logout' &&
            $this->logueado
        ) {
            header("Location: {$this->basePath}lobby/show");
            exit;
        }
    }

    private function validarSesionParaPrivados($controller)
    {
        $controladoresPrivados = $this->config["roles"]["controladores_privados"] ?? [];
        if (in_array(strtolower($controller), array_map('strtolower', $controladoresPrivados)) && !$this->logueado) {
            header("Location: {$this->basePath}login/show");
            exit;
        }
    }

    private function redirigirLobbySegunRol($controller)
    {
        $controller = strtolower($controller);
        if ($controller === 'lobby') {
            if ($this->rol === 'admin') {
                header("Location: {$this->basePath}admin/show");
                exit;
            }
            if ($this->rol === 'editor') {
                header("Location: {$this->basePath}editor/show");
                exit;
            }
        }
    }

    private function validarSoloAdmin($controller)
    {
        $controladoresAdmin = $this->config["roles"]["controladores_admin"] ?? [];
        if (in_array(strtolower($controller), array_map('strtolower', $controladoresAdmin)) && $this->rol !== 'admin') {
            header("Location: {$this->basePath}lobby/show");
            exit;
        }
    }

    private function validarSoloEditor($acceso)
    {
        $rutasEditor = $this->config["roles"]["rutas_editor"] ?? [];
        if (in_array(strtolower($acceso), array_map('strtolower', $rutasEditor)) && $this->rol !== 'editor') {
            header("Location: {$this->basePath}lobby/show");
            exit;
        }
    }

    private function validarSoloJugador($acceso)
    {
        $rutasJugador = $this->config["roles"]["rutas_jugador"] ?? [];
        if (in_array(strtolower($acceso), array_map('strtolower', $rutasJugador)) && $this->rol !== 'jugador') {
            header("Location: {$this->basePath}lobby/show");
            exit;
        }
    }
}