<?php

class GameModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Guarda la partida en la BD.
     */
    public function crearPartida($usuarioId)
    {
        $sql = "INSERT INTO partida (id_usuario, fecha) VALUES (?, NOW())";
        $this->database->execute($sql, [$usuarioId]);
        return $this->database->getConnection()->insert_id;
    }

    /**
     * Actualiza los datos de la partida en la BD al finalizarla.
     */
    public function guardarPartidaFinalizada($partidaId, $puntaje): void
    {
        $sql = "UPDATE partida SET finalizada = 1, puntaje = ? WHERE id = ?";
        $this->database->execute($sql, [$puntaje, $partidaId]);
    }

    public function registrarRespuestaEnPartida($partidaId, $preguntaId, $respuestaId, $esCorrecta): void
    {
        $sql = "INSERT INTO partida_pregunta (id_partida, id_pregunta, id_respuesta, respondida_correctamente, estado_pregunta)
            VALUES (?, ?, ?, ?, 'respondida')
            ON DUPLICATE KEY UPDATE
                id_respuesta = VALUES(id_respuesta),
                respondida_correctamente = VALUES(respondida_correctamente),
                estado_pregunta = 'respondida'";
        $this->database->execute($sql, [$partidaId, $preguntaId, $respuestaId, $esCorrecta ? 1 : 0]);
    }

    /**
     * Guarda el registro de ResumenPartida
     */
    public function guardarResumenPartida($partidaId, $usuarioId, $puntaje)
    {
        // 1. Obtener la cantidad de intentos y respuestas correctas para esta partida
        $sqlStats = "SELECT 
                    COUNT(*) as cantidad_intentos,
                    SUM(respondida_correctamente) as cantidad_correctas
                 FROM partida_pregunta
                 WHERE id_partida = ?";
        $stats = $this->database->query($sqlStats, [$partidaId])[0];

        // 2. Obtener la(s) categoría(s) con más respuestas correctas
        $sqlCategoria = "SELECT p.id_categoria, COUNT(*) as cantidad
                    FROM partida_pregunta pp
                    INNER JOIN pregunta p ON pp.id_pregunta = p.id
                    WHERE pp.id_partida = ? AND pp.respondida_correctamente = 1
                    GROUP BY p.id_categoria
                    ORDER BY cantidad DESC";
        $categorias = $this->database->query($sqlCategoria, [$partidaId]);

        // 3. Determinar si hay una categoría claramente destacada (sin empate)
        $categoria = null;
        if (!empty($categorias)) {
            $maxCantidad = $categorias[0]['cantidad'];
            $categoriasMaximas = array_filter($categorias, function($cat) use ($maxCantidad) {
                return $cat['cantidad'] == $maxCantidad;
            });

            if (count($categoriasMaximas) === 1) {
                $categoria = $categoriasMaximas[0]['id_categoria'];
            }
        }

        // 4. Insertar en resumen_partida
        $sql = "INSERT INTO resumen_partida 
            (id_partida, id_usuario, cantidad_correctas, cantidad_intentos, id_categoria, puntaje, tiempo_promedio_respuesta, fecha_partida)
            VALUES (?, ?, ?, ?, ?, ?, NULL, NOW())
            ON DUPLICATE KEY UPDATE
                cantidad_correctas = VALUES(cantidad_correctas),
                cantidad_intentos = VALUES(cantidad_intentos),
                id_categoria = VALUES(id_categoria),
                puntaje = VALUES(puntaje),
                tiempo_promedio_respuesta = VALUES(tiempo_promedio_respuesta),
                fecha_partida = VALUES(fecha_partida)";
        $this->database->execute($sql, [
            $partidaId,
            $usuarioId,
            $stats['cantidad_correctas'] ?? 0,
            $stats['cantidad_intentos'] ?? 0,
            $categoria,
            $puntaje
        ]);
    }


    /**
     * Obtiene las categorías de la BD
     */
    public function obtenerCategoriasDisponibles()
    {
        $sql = "SELECT * FROM categoria";
        return $this->database->query($sql);
    }

    /**
     * Obtiene una categoría a partir de su ID
     */
    public function obtenerCategoriaPorId($categoriaId)
    {
        $sql = "SELECT id, nombre, color FROM categoria WHERE id = ?";
        return $this->database->queryOne($sql, [$categoriaId]);
    }

    /**
     * Obtiene una pregunta a partir de su ID
     */
    public function obtenerPreguntaPorId($id)
    {
        $sql = "SELECT * FROM pregunta WHERE id = ?";
        $pregunta = $this->database->query($sql, [$id]);
        return $pregunta[0] ?? null;
    }

    /**
     * Obtiene las opciones de una pregunta
     */
    public function obtenerOpcionesRespuesta($preguntaId)
    {
        $sql = "SELECT id, texto FROM respuesta WHERE id_pregunta = ?";
        return $this->database->query($sql, [$preguntaId]);
    }


    /**
     * Obtiene una pregunta para un usuario segun su categoría y dificultad
     */
    public function obtenerPreguntaParaUsuario($usuarioId, $categoriaId)
    {
        // 1. Obtener estadísticas del usuario: total de preguntas respondidas y cuántas fueron correctas
        $estadisticasUsuario = $this->database->query(
            "SELECT COUNT(*) as total, SUM(es_correcta) as correctas FROM pregunta_usuario WHERE id_usuario = ?",
            [$usuarioId]
        )[0] ?? ['total' => 0, 'correctas' => 0];

        $totalRespondidas = (int) $estadisticasUsuario['total'];
        $correctas = (int) $estadisticasUsuario['correctas'];

        // 2. Calcular dificultad según el desempeño del usuario
        // - Si respondió menos de 10 preguntas: media
        // - Si el porcentaje de aciertos es >= 70%: difícil
        // - Si el porcentaje de aciertos es <= 30%: fácil
        // - En otro caso: media
        if ($totalRespondidas < 10) {
            $dificultadId = 2; // media
        } else {
            $porcentaje = $correctas / $totalRespondidas;
            if ($porcentaje >= 0.7) {
                $dificultadId = 3; // dificil
            } elseif ($porcentaje <= 0.3) {
                $dificultadId = 1; // facil
            } else {
                $dificultadId = 2; // media
            }
        }

        // 3. Intentar obtener una pregunta de la categoría, con esa dificultad, que el usuario no haya respondido aún
        $pregunta = $this->database->query(
            "SELECT p.* FROM pregunta p
             WHERE p.id_categoria = ?
               AND p.id NOT IN (
                   SELECT id_pregunta FROM pregunta_usuario WHERE id_usuario = ?
               )
               AND p.id_dificultad = ?
             ORDER BY RAND() LIMIT 1",
            [$categoriaId, $usuarioId, $dificultadId]
        );

        // 4. Si no encuentra ninguna con esa dificultad, intenta con cualquier dificultad
        if (empty($pregunta)) {
            $pregunta = $this->database->query(
                "SELECT p.* FROM pregunta p
                 WHERE p.id_categoria = ?
                   AND p.id NOT IN (
                       SELECT id_pregunta FROM pregunta_usuario WHERE id_usuario = ?
                   )
                 ORDER BY RAND() LIMIT 1",
                [$categoriaId, $usuarioId]
            );
        }

        // 5. Devuelve la primera (o null si no hay disponibles)
        return $pregunta[0] ?? null;
    }

    /**
     * Obtiene una pregunta para un usuario segun su categoría y dificultad
     */
    public function validarRespuesta($preguntaId, $respuestaId): bool
    {
        $sql = "SELECT es_correcta FROM respuesta WHERE id_pregunta = ? AND id = ?";
        $resultado = $this->database->query($sql, [$preguntaId, $respuestaId]);
        return !empty($resultado) && $resultado[0]['es_correcta'] == 1;
    }

    public function obtenerRespuestaCorrecta($preguntaId)
    {
        $sql = "SELECT * FROM respuesta WHERE id_pregunta = ? AND es_correcta = 1";
        $resultado = $this->database->query($sql, [$preguntaId]);
        return $resultado[0] ?? null;
    }

    public function marcarPreguntaComoRespondidaPorUsuario($usuarioId, $preguntaId, $respuestaId, $esCorrecta): void
    {
        // 1. Registrar que el usuario respondió la pregunta
        $sql = "INSERT INTO pregunta_usuario (id_usuario, id_pregunta, id_respuesta, es_correcta)
                VALUES (?, ?, ?, ?)";
        $this->database->execute($sql, [$usuarioId, $preguntaId, $respuestaId, $esCorrecta]);

        // 2. Actualizar métricas de la pregunta
        $this->database->execute(
            "UPDATE pregunta
             SET veces_mostrada = veces_mostrada + 1,
                 veces_respondida_correctamente = veces_respondida_correctamente + ?
             WHERE id = ?",
            [$esCorrecta ? 1 : 0, $preguntaId]
        );

        // 3. Recalcular y actualizar dificultad solo si se ha mostrado al menos 10 veces
        $this->database->execute(
            "UPDATE pregunta
             SET id_dificultad = CASE
                 WHEN veces_mostrada < 10 THEN id_dificultad
                 WHEN veces_respondida_correctamente / veces_mostrada >= 0.7 THEN 1
                 WHEN veces_respondida_correctamente / veces_mostrada < 0.3 THEN 3
                 ELSE 2
             END
             WHERE id = ?",
            [$preguntaId]
        );
    }
    public function usuarioRespondioTodas($usuarioId, $categoriaId): bool
    {
        $total = $this->database->query(
            "SELECT COUNT(*) as total FROM pregunta WHERE id_categoria = ?",
            [$categoriaId]
        )[0]['total'] ?? 0;

        $respondidas = $this->database->query(
            "SELECT COUNT(*) as respondidas FROM pregunta_usuario
             INNER JOIN pregunta ON pregunta.id = pregunta_usuario.id_pregunta
             WHERE pregunta_usuario.id_usuario = ? AND pregunta.id_categoria = ?",
            [$usuarioId, $categoriaId]
        )[0]['respondidas'] ?? 0;

        return $respondidas >= $total && $total > 0;
    }

    public function resetearPreguntasRespondidas($usuarioId, $categoriaId): void
    {
        $sql = "DELETE pu FROM pregunta_usuario pu
                INNER JOIN pregunta p ON p.id = pu.id_pregunta
                WHERE pu.id_usuario = ? AND p.id_categoria = ?";
        $this->database->execute($sql, [$usuarioId, $categoriaId]);
    }


}
