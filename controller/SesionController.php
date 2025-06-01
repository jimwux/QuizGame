<?php
#-----------------------------------------------------------------------------
class SesionController {
    public static function guardarEstadoPartida($estado) {
        $_SESSION['estado_partida'] = $estado;
    }

    public static function obtenerEstadoPartida() {
        return $_SESSION['estado_partida'] ?? null;
    }

    public static function reiniciarPartida() {
        unset($_SESSION['estado_partida']);
    }
}
#-----------------------------------------------------------------------------

/*
$estado = [
    'pregunta_actual' => 1,
    'respuestas_dadas' => [],
    'puntaje' => 0
];
SesionController::guardarEstadoPartida($estado);
*/

/*
$estado = SesionController::obtenerEstadoPartida();
*/

/*
SesionController::reiniciarPartida();
*/