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

    // El tercer parámetro $params ahora es el array $_GET completo
    public function go($controllerName, $methodName, $params = [])
    {
        $controller = $this->getControllerFrom($controllerName);
        $this->executeMethodFromController($controller, $methodName, $params);
    }

    private function getControllerFrom($controllerName)
    {
        $controllerName = 'get' . ucfirst($controllerName) . 'Controller';
        $validController = method_exists($this->configuration, $controllerName) ? $controllerName : $this->defaultController;
        return call_user_func([$this->configuration, $validController]);
    }

    private function executeMethodFromController($controller, $method, $params = [])
    {
        $validMethod = method_exists($controller, $method) ? $method : $this->defaultMethod;

        $ref = new ReflectionMethod($controller, $validMethod);
        $methodParameters = $ref->getParameters(); // Obtiene todos los objetos ReflectionParameter

        $args = [];
        foreach ($methodParameters as $param) {
            $paramName = $param->getName(); // Nombre del parámetro (ej: 'partidaId', 'token')

            if (isset($params[$paramName])) {
                // Si el parámetro existe en el array $_GET, lo usamos
                $args[] = $params[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                // Si el parámetro tiene un valor por defecto, lo usamos
                $args[] = $param->getDefaultValue();
            } else {
                // Si el parámetro es obligatorio y no está en $_GET ni tiene valor por defecto
                // Esto es un error, el método espera algo que no se le dio
                // Puedes lanzar una excepción o manejarlo como un error 400 Bad Request
                error_log("Error: Parámetro '" . $paramName . "' requerido para " . get_class($controller) . "::" . $validMethod . " no encontrado en la URL.");
                // Opcional: renderizar una página de error o redirigir
                $controller->renderer->render('error', ['message' => 'Parámetro requerido no encontrado.']);
                return; // Detener la ejecución
            }
        }

        // Llama al método con los argumentos construidos dinámicamente
        call_user_func_array([$controller, $validMethod], $args);
    }
}