<?php

require_once 'vendor/autoload.php'; // solo si usás Composer

class Render {
    
    public function render($template, $data = []) {
        $mustache = new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader(dirname(__DIR__) . '/view')
        ]);

        return $mustache->render($template, $data);
    }
}