<?php

require_once("Configuration.php");
session_start();
$configuration = new Configuration();
$router = $configuration->getRouter();

$controller = $_GET["controller"] ?? null;
$method = $_GET["method"] ?? null;

$router->go($controller, $method, $_GET ?? null);
