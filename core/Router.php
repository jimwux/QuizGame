<?php

class Router
{
    private $defaultController;
    private $defaultMethod;
    private $configuration;

    public function __construct($defaultController, $defaultMethod, $configuration)
    {
        $this->defaultController = $defaultController;
        $this->defaultMethod = $defaultMethod;
        $this->configuration = $configuration;
    }

    public function go($controllerName, $methodName, $param = null)
    {
        $controller = $this->getControllerFrom($controllerName);
        $this->executeMethodFromController($controller, $methodName, $param);
    }

    private function getControllerFrom($controllerName)
    {
        $controllerName = 'get' . ucfirst($controllerName) . 'Controller';
        $validController = method_exists($this->configuration, $controllerName) ? $controllerName : $this->defaultController;
        return call_user_func([$this->configuration, $validController]);
    }

    private function executeMethodFromController($controller, $method, $param = null)
    {
        $validMethod = method_exists($controller, $method) ? $method : $this->defaultMethod;

        // Verificamos si el método espera parámetros
        $ref = new ReflectionMethod($controller, $validMethod);
        $paramCount = $ref->getNumberOfParameters();

        if ($paramCount > 0) {
            $controller->$validMethod($param);
        } else {
            $controller->$validMethod();
        }
    }
}