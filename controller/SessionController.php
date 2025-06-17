<?php

class SessionController
{
    public static function iniciarSesion()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function guardarEstadoPartida(array $estado)
    {
        self::iniciarSesion();
        $_SESSION['estado_partida'] = $estado;
    }

    public static function obtenerEstadoPartida(): array
    {
        self::iniciarSesion();
        return $_SESSION['estado_partida'] ?? [
            'partida_id' => null,
            'pregunta_actual_id' => null,
            'respuestas_dadas' => [],
            'puntaje_acumulado' => 0,
            'juego_terminado' => false,
            'categoria_actual' => null
        ];
    }

    public static function actualizarEstadoPartida(string $clave, $valor)
    {
        self::iniciarSesion();
        if (isset($_SESSION['estado_partida'])) {
            $_SESSION['estado_partida'][$clave] = $valor;
        }
    }

    public static function limpiarDatosPartida()
    {
        self::iniciarSesion();
        unset($_SESSION['estado_partida']);
    }

    public static function incrementarPuntaje($puntajeIncremento)
    {
        self::iniciarSesion();
        if (!isset($_SESSION['estado_partida']['puntaje'])) {
            $_SESSION['estado_partida']['puntaje'] = 0;
        }
        // Sumar el puntaje incrementado
        $_SESSION['estado_partida']['puntaje'] += $puntajeIncremento;
    }

    public static function eliminarClaveEstadoPartida($clave)
    {
        if (isset($_SESSION['estado_partida'][$clave])) {
            unset($_SESSION['estado_partida'][$clave]);
        }
    }


    // Puedes añadir más métodos para la sesión de usuario
    public static function iniciarSesionUsuario($id, $username)
    {
        self::iniciarSesion();
        $_SESSION['id'] = $id;
        $_SESSION['username'] = $username;
    }

    public static function cerrarSesionUsuario()
    {
        self::iniciarSesion();
        session_unset();
        session_destroy();
    }

    public static function estaLogueado(): bool
    {
        self::iniciarSesion();
        return isset($_SESSION['id']);
    }
}
