<?php
    session_start();

    // Obtener controlador y metodo, o usar por defecto
    $controller = !empty($_GET["controller"]) ? $_GET["controller"] : "lobby";
    $method = !empty($_GET["method"]) ? $_GET["method"] : "show";
    $acceso = strtolower("$controller/$method");

    // Cargar configuración
    $config = parse_ini_file("configuration/config.ini", true);
    $basePath = $config["app"]["base_path"];

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

    // Validaciones de acceso
    $validator = new AccesoValidator($config);
    $validator->validar($controller, $method);

    // Ejecutar la ruta solicitada
    $router->go($controller, $method, $_GET['token'] ?? null);
