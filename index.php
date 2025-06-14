<?php
session_start();

// Obtener controller y method o aplicar valores por defecto
$controller = $_GET["controller"] ?? "lobby";
$method = $_GET["method"] ?? "show";
$acceso = strtolower("$controller/$method");

// Cargar configuración
$config = parse_ini_file("configuration/config.ini", true);
$basePath = $config["app"]["base_path"];

$rutasEditor = $config["roles"]["rutas_editor"] ?? [];
$controladoresPrivados = $config["roles"]["controladores_privados"] ?? [];

// Estado de sesión
$rol = $_SESSION['usuario_rol'] ?? null;
$logueado = isset($_SESSION['id']);

// Validar acceso por controlador
if (in_array(strtolower($controller), array_map('strtolower', $controladoresPrivados)) && !$logueado) {
    header("Location: {$basePath}login");
    exit;
}

// Validar acceso para editores
if (in_array($acceso, array_map('strtolower', $rutasEditor)) && $rol !== 'editor') {
    header("Location: {$basePath}lobby");
    exit;
}

// Ruteo
require_once("Configuration.php");
$configuration = new Configuration();
$router = $configuration->getRouter();

$router->go($controller, $method, $_GET['token'] ?? null);
