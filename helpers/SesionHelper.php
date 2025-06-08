<?php

// session_start(); // Asegúrate de que session_start() se llame al inicio de tu aplicación

class SesionController
{
    /**
     * Guarda el estado general de la partida en la sesión.
     */
    public static function guardarEstadoPartida(array $estado): void
    {
        $_SESSION['juego_activo'] = $estado;
    }

    /**
     * Obtiene el estado general de la partida de la sesión.
     */
    public static function obtenerEstadoPartida(): ?array
    {
        return $_SESSION['juego_activo'] ?? null;
    }

    /**
     * Limpia completamente el estado de la partida de la sesión.
     */
    public static function reiniciarPartida(): void
    {
        unset($_SESSION['juego_activo']);
    }

    /**
     * Guarda el ID de la pregunta actual y el tiempo de inicio en la sesión para una partida específica.
     */
    public static function guardarEstadoPreguntaActual(int $partidaId, int $preguntaId): void
    {
        if (!isset($_SESSION['juego_activo'][$partidaId])) {
            $_SESSION['juego_activo'][$partidaId] = [];
        }
        $_SESSION['juego_activo'][$partidaId]['current_question_id'] = $preguntaId;
        $_SESSION['juego_activo'][$partidaId]['question_start_time'] = time(); // Timestamp de inicio
    }

    /**
     * Obtiene el estado de la pregunta actual para una partida específica.
     */
    public static function obtenerEstadoPreguntaActual(int $partidaId): ?array
    {
        return $_SESSION['juego_activo'][$partidaId] ?? null;
    }

    /**
     * Limpia el estado de la pregunta actual de la sesión para una partida específica.
     */
    public static function limpiarEstadoPreguntaActual(int $partidaId): void
    {
        if (isset($_SESSION['juego_activo'][$partidaId]['current_question_id'])) {
            unset($_SESSION['juego_activo'][$partidaId]['current_question_id']);
        }
        if (isset($_SESSION['juego_activo'][$partidaId]['question_start_time'])) {
            unset($_SESSION['juego_activo'][$partidaId]['question_start_time']);
        }
        // Opcional: Si no hay más datos para esta partida, puedes limpiar la entrada completa
        if (empty($_SESSION['juego_activo'][$partidaId])) {
            unset($_SESSION['juego_activo'][$partidaId]);
        }
    }

    /**
     * Actualiza solo el tiempo de inicio de la pregunta actual.
     */
    public static function actualizarTiempoInicioPregunta(int $partidaId): void
    {
        if (isset($_SESSION['juego_activo'][$partidaId])) {
            $_SESSION['juego_activo'][$partidaId]['question_start_time'] = time();
        }
    }
}
