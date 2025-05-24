<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once("Configuration.php");
$configuration = new Configuration();
$router = $configuration->getRouter();

$controller = $_GET["controller"] ?? null;
$method = $_GET["method"] ?? null;

$router->go($controller, $method);