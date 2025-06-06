<?php

require_once __DIR__ . '/phpqrcode/phpqrcode.php';

class QRGenerator {
    public static function generarQR($texto, $rutaArchivo) {
        if (!file_exists($rutaArchivo)) {
            QRcode::png($texto, $rutaArchivo);
        }
    }
}