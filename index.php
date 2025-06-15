<?php
session_start();

// Obtener controller y method o aplicar valores por defecto
$controller = !empty($_GET["controller"]) ? $_GET["controller"] : "lobby";
$method = !empty($_GET["method"]) ? $_GET["method"] : "show";
$acceso = strtolower("$controller/$method");

// Cargar configuración
$config = parse_ini_file("configuration/config.ini", true);
$basePath = $config["app"]["base_path"];

$rutasEditor = $config["roles"]["rutas_editor"] ?? [];
$controladoresPrivados = $config["roles"]["controladores_privados"] ?? [];

$rol = $_SESSION['usuario_rol'] ?? null;
$logueado = isset($_SESSION['id']);

require_once("Configuration.php");
$configuration = new Configuration();
$router = $configuration->getRouter();

// Validar si el controlador y metodo existen
$controllerInstance = $router->getControllerInstance($controller);

if (!$controllerInstance || !method_exists($controllerInstance, $method)) {
    // Ruta inválida → usar fallback a lobby/show
    $controller = "lobby";
    $method = "show";
    $acceso = "lobby/show"; // Necesario para que la validación funcione
}

// Validar acceso por controlador
if (in_array(strtolower($controller), array_map('strtolower', $controladoresPrivados)) && !$logueado) {
    header("Location: {$basePath}login/show");
    exit;
}

// Validar acceso para editores
if (in_array($acceso, array_map('strtolower', $rutasEditor)) && $rol !== 'editor') {
    header("Location: {$basePath}lobby/show");
    exit;
}

// Ruteo
$router->go($controller, $method, $_GET['token'] ?? null);
