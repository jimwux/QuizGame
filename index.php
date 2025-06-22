<?php
session_start();

// Obtener controlador y metodo, o usar por defecto
$controller = !empty($_GET["controller"]) ? $_GET["controller"] : "lobby";
$method = !empty($_GET["method"]) ? $_GET["method"] : "show";
$acceso = strtolower("$controller/$method");

// Cargar configuración
$config = parse_ini_file("configuration/config.ini", true);
$basePath = $config["app"]["base_path"];

$controladoresPrivados = $config["roles"]["controladores_privados"] ?? [];
$controladoresAdmin = $config["roles"]["controladores_admin"] ?? [];
$rutasEditor = $config["roles"]["rutas_editor"] ?? [];
$rutasJugador = $config["roles"]["rutas_jugador"] ?? [];

$rol = $_SESSION['usuario_rol'] ?? null;
$logueado = isset($_SESSION['id']); // id guardado en login()

require_once("Configuration.php");
$configuration = new Configuration();
$router = $configuration->getRouter();
$controllerInstance = $router->getControllerInstance($controller);

// Si el controlador o metodo no existen → fallback a lobby
if (!$controllerInstance || !method_exists($controllerInstance, $method)) {
    $controller = "lobby";
    $method = "show";
    $acceso = "lobby/show";
}

// Validar sesión para controladores privados
if (in_array(strtolower($controller), array_map('strtolower', $controladoresPrivados)) && !$logueado) {
    header("Location: {$basePath}/login/show");
    exit;
}


// Si es admin y entra a /lobby → redirigir a su panel
if ($rol === 'admin' && strtolower($controller) === 'lobby') {
    header("Location: {$basePath}admin/show");
    exit;
}

// Validar controladores exclusivos para administradores
if (in_array(strtolower($controller), array_map('strtolower', $controladoresAdmin)) && $rol !== 'admin') {
    header("Location: {$basePath}lobby/show");
    exit;
}

// Si es editor y entra a /lobby → redirigir a su panel
if ($rol === 'editor' && strtolower($controller) === 'lobby') {
    header("Location: {$basePath}editor/show");
    exit;
}

// Validar acceso a rutas exclusivas para editores
if (in_array($acceso, array_map('strtolower', $rutasEditor)) && $rol !== 'editor') {
    header("Location: {$basePath}lobby/show");
    exit;
}

// Validar acceso a rutas exclusivas para jugadores
if (in_array($acceso, array_map('strtolower', $rutasJugador)) && $rol !== 'jugador') {
    header("Location: {$basePath}profile/show");
    exit;
}

// Ejecutar la ruta solicitada
$router->go($controller, $method, $_GET['token'] ?? null);
