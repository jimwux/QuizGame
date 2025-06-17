<?php

class QuestionModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    public function guardarReporte($idUsuario, $idPregunta, $motivo)
    {

        if (!isset($motivo) || trim($motivo) === '') {
            return 'EMPTY_REASON';
        }
        if ($this->alreadyReported($idUsuario, $idPregunta)) {
            return 'ALREADY_REPORTED';
        }

        $sql = "INSERT INTO reporte_pregunta 
                    (id_pregunta, id_usuario, motivo, fecha_reporte, estado) 
                    VALUES (?, ?, ?, NOW(), 'pendiente')";

        try {
            // 1. Intentamos ejecutar la inserción.
            $this->database->execute($sql, [$idPregunta, $idUsuario, $motivo]);
            return 'SUCCESS';

        } catch (Exception $e) {

            error_log("Error al guardar reporte en la BD: " . $e->getMessage());
            return 'DB_ERROR';
        }
    }


    public function alreadyReported($idUsuario, $idPregunta)
    {
        if ($idUsuario === null || $idPregunta === null) {
            return true;
        }


        $sql = "SELECT id FROM reporte_pregunta WHERE id_usuario = ? AND id_pregunta = ?";
        $resultado = $this->database->query($sql, [$idUsuario, $idPregunta]);

        return !empty($resultado);
    }
    public function aprobarReporte($reporte_id)
    {

        $sql = "UPDATE reporte_pregunta SET estado = 'aprobada' WHERE id = ?";
        return $this->database->execute($sql, [$reporte_id]);
    }


    public function rechazarReporte($reporte_id)
    {

        $sql = "UPDATE reporte_pregunta SET estado = 'rechazada' WHERE id = ?";
        return $this->database->execute($sql, [$reporte_id]);
    }


    public function obtenerPreguntasReportadas()
    {

        $sql = "
            SELECT 
                rp.id AS reporte_id, 
                rp.motivo, 
                rp.fecha_reporte,
                p.texto AS pregunta_texto,
                u.nombre_completo AS usuario_nombre
            FROM 
                reporte_pregunta rp
            LEFT JOIN 
                pregunta p ON rp.id_pregunta = p.id
            LEFT JOIN 
                usuarios u ON rp.id_usuario = u.id
            WHERE 
                rp.estado = 'pendiente'
            ORDER BY 
                rp.fecha_reporte DESC;
        ";


        $reportesCrudos = $this->database->query($sql);


        $reportesProcesados = [];
        if (!empty($reportesCrudos)) {
            foreach ($reportesCrudos as $fila) {
                // Este bucle asegura que estamos creando un array 100% nativo de PHP,
                // eliminando cualquier particularidad del resultado de la base de datos.
                $reportesProcesados[] = $fila;
            }
        }
        return $reportesProcesados;

    }
    public function guardarSugerencia($datosPregunta, $respuestas)
    {
        try {
            $this->database->beginTransaction();

            // Insertar pregunta sugerida
            $sqlPregunta = "INSERT INTO sugerencia_pregunta (id_usuario, texto, id_categoria) VALUES (?, ?, ?)";
            $this->database->execute($sqlPregunta, [
                $datosPregunta["id_usuario"],
                $datosPregunta["texto"],
                $datosPregunta["id_categoria"]
            ]);

            $idSugerencia = $this->database->lastInsertId();

            // Insertar respuestas sugeridas
            $sqlRespuesta = "INSERT INTO sugerencia_respuesta (id_sugerencia, texto, es_correcta) VALUES (?, ?, ?)";
            foreach ($respuestas as $r) {
                $this->database->execute($sqlRespuesta, [
                    $idSugerencia,
                    $r["texto"],
                    $r["es_correcta"] ? 1 : 0
                ]);
            }

            $this->database->commit();
            return true;

        } catch (Exception $e) {
            $this->database->rollBack();
            return false;
        }
    }

    /**
     * Obtiene las categorías de la BD
     */
    public function obtenerCategorias()
    {
        $sql = "SELECT * FROM categoria";
        return $this->database->query($sql);
    }

    public function obtenerDificultades()
    {
        $sql = "SELECT * FROM dificultad";
        return $this->database->query($sql);
    }


    public function obtenerPreguntasSugeridas()
    {
        $sql = "
            SELECT
                sp.id AS id_pregunta,
                sp.texto,
                sp.fecha_sugerencia,
                u.nombre_completo AS nombre_usuario,
                c.nombre AS nombre_categoria,
                c.color AS categoria_color,
                sr.texto AS respuestas,
                sr.es_correcta AS es_correcta
            FROM
                sugerencia_pregunta AS sp
            JOIN
                usuarios AS u ON sp.id_usuario = u.id
            JOIN
                categoria AS c ON sp.id_categoria = c.id
            JOIN 
                sugerencia_respuesta AS sr ON sr.id_sugerencia = sp.id
            WHERE
                sp.estado = 'pendiente'
            ORDER BY
                sp.fecha_sugerencia DESC;
        ";

        $preguntasSugeridasCompletas = $this->database->query($sql);

        $resultado = [];

        foreach ($preguntasSugeridasCompletas as $fila) {
            $idPregunta = $fila["id_pregunta"];

            if (!isset($resultado[$idPregunta])) {
                $resultado[$idPregunta] = [
                    "id_pregunta" => $fila["id_pregunta"],
                    "texto" => $fila["texto"],
                    "fecha_sugerencia" => $fila["fecha_sugerencia"],
                    "nombre_usuario" => $fila["nombre_usuario"],
                    "nombre_categoria" => $fila["nombre_categoria"],
                    "color" => $fila["categoria_color"],
                    "respuestas" => [],
                ];
            }

            $resultado[$idPregunta]["respuestas"][] = [
                "texto" => $fila["respuestas"],
                "es_correcta" => $fila["es_correcta"],
            ];
        }

        return array_values($resultado);
    }

    public function aprobarPreguntaSugerida($idPregunta)
    {
        try {
            // Inicia la transacción
            $this->database->beginTransaction();

            // 1. Obtengo la pregunta sugerida de sugerencia_pregunta
            $sqlPreguntaSugerida = "SELECT texto, id_categoria, id_usuario FROM sugerencia_pregunta WHERE id = ?";
            $stmtPreguntaSugerida = $this->database->getConnection()->prepare($sqlPreguntaSugerida);
            if ($stmtPreguntaSugerida === false) {
                throw new Exception("Error al preparar la consulta de pregunta sugerida: " . $this->database->getConnection()->error);
            }
            $stmtPreguntaSugerida->bind_param("i", $idPregunta);
            $stmtPreguntaSugerida->execute();
            $preguntaAprobada = $stmtPreguntaSugerida->get_result()->fetch_assoc();
            $stmtPreguntaSugerida->close();

            // Si la pregunta sugerida no existe, lanzamos un error
            if (!$preguntaAprobada) {
                throw new Exception("La pregunta sugerida con ID " . $idPregunta . " no fue encontrada.");
            }

            // 2. Agrego la pregunta a la tabla 'pregunta'
            $sqlInsertPregunta = "INSERT INTO pregunta (texto, id_categoria, id_creador, estado) VALUES (?, ?, ?, 'activa')";
            $stmtInsertPregunta = $this->database->getConnection()->prepare($sqlInsertPregunta);
            if ($stmtInsertPregunta === false) {
                throw new Exception("Error al preparar la consulta de inserción de pregunta: " . $this->database->getConnection()->error);
            }
            $stmtInsertPregunta->bind_param("sii", $preguntaAprobada["texto"], $preguntaAprobada["id_categoria"], $preguntaAprobada["id_usuario"]);
            $stmtInsertPregunta->execute();
            // Obtener el ID de la pregunta recién insertada para las respuestas
            $newPreguntaId = $this->database->getConnection()->insert_id;
            $stmtInsertPregunta->close();

            // 3. Obtengo las respuestas sugeridas de sugerencia_respuesta
            $sqlRespuestasSugeridas = "SELECT texto, es_correcta FROM sugerencia_respuesta WHERE id_sugerencia = ?";
            $stmtRespuestasSugeridas = $this->database->getConnection()->prepare($sqlRespuestasSugeridas);
            if ($stmtRespuestasSugeridas === false) {
                throw new Exception("Error al preparar la consulta de respuestas sugeridas: " . $this->database->getConnection()->error);
            }
            $stmtRespuestasSugeridas->bind_param("i", $idPregunta);
            $stmtRespuestasSugeridas->execute();
            $respuestasAprobadas = $stmtRespuestasSugeridas->get_result(); // <-- Aquí obtienes el mysqli_result
            $stmtRespuestasSugeridas->close();

            // 4. Preparar la consulta SQL para insertar en la tabla 'respuesta'
            $insertRespuesta = "INSERT INTO respuesta (id_pregunta, texto, es_correcta) VALUES (?, ?, ?)";
            $insertStmt = $this->database->getConnection()->prepare($insertRespuesta);
            if ($insertStmt === false) {
                throw new Exception("Error al preparar la consulta de inserción de respuesta: " . $this->database->getConnection()->error);
            }

            // 5. Iterar sobre cada respuesta aprobada e insertarla en la tabla 'respuesta'
            if ($respuestasAprobadas->num_rows > 0) {
                while ($fila = $respuestasAprobadas->fetch_assoc()) { // <-- CORRECTO: fetch_assoc() sobre el resultado
                    $textoRespuesta = $fila["texto"];
                    $esCorrecta = (int)$fila["es_correcta"]; // Asegurarse de que es un entero (0 o 1)

                    $insertStmt->bind_param("isi", $newPreguntaId, $textoRespuesta, $esCorrecta);
                    if (!$insertStmt->execute()) {
                        throw new Exception("Error al insertar respuesta '" . htmlspecialchars($textoRespuesta) . "': " . $insertStmt->error);
                    }
                }
            }
            $insertStmt->close();


            // 6. Elimino la pregunta sugerida y sus respuestas de las tablas de sugerencia
            // Primero las respuestas sugeridas
            $sqlDeleteRespuestasSugeridas = "DELETE FROM sugerencia_respuesta WHERE id_sugerencia = ?";
            $stmtDeleteRespuestasSugeridas = $this->database->getConnection()->prepare($sqlDeleteRespuestasSugeridas);
            if ($stmtDeleteRespuestasSugeridas === false) {
                throw new Exception("Error al preparar la eliminación de respuestas sugeridas: " . $this->database->getConnection()->error);
            }
            $stmtDeleteRespuestasSugeridas->bind_param("i", $idPregunta);
            $stmtDeleteRespuestasSugeridas->execute();
            $stmtDeleteRespuestasSugeridas->close();

            // Luego la pregunta sugerida
            $sqlDeletePreguntaSugerida = "DELETE FROM sugerencia_pregunta WHERE id = ?";
            $stmtDeletePreguntaSugerida = $this->database->getConnection()->prepare($sqlDeletePreguntaSugerida);
            if ($stmtDeletePreguntaSugerida === false) {
                throw new Exception("Error al preparar la eliminación de pregunta sugerida: " . $this->database->getConnection()->error);
            }
            $stmtDeletePreguntaSugerida->bind_param("i", $idPregunta);
            $stmtDeletePreguntaSugerida->execute();
            $stmtDeletePreguntaSugerida->close();


            // Si todo ha ido bien, confirma la transacción
            $this->database->commit();
            return true;

        } catch (Exception $e) {
            // Si algo falla, revierte la transacción
            $this->database->rollBack();
            error_log("Error en aprobarPreguntaSugerida: " . $e->getMessage()); // Para depuración
            // Puedes pasar un mensaje más amigable al usuario o simplemente 'false'
            return false;
        }
    }

    public function rechazarPreguntaSugerida($idPregunta)
    {
        try {
            $this->database->beginTransaction();

            // Eliminar respuestas sugeridas primero para evitar problemas de clave foránea
            $sql2 = "DELETE FROM sugerencia_respuesta WHERE id_sugerencia = ?";
            $stmt2 = $this->database->getConnection()->prepare($sql2);
            if ($stmt2 === false) {
                throw new Exception("Error al preparar la eliminación de respuestas sugeridas (rechazar): " . $this->database->getConnection()->error);
            }
            $stmt2->bind_param("i", $idPregunta);
            $stmt2->execute();
            $stmt2->close();

            // Eliminar la pregunta sugerida
            $sql = "DELETE FROM sugerencia_pregunta WHERE id = ?";
            $stmt = $this->database->getConnection()->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error al preparar la eliminación de pregunta sugerida (rechazar): " . $this->database->getConnection()->error);
            }
            $stmt->bind_param("i", $idPregunta);
            $stmt->execute();
            $stmt->close();

            $this->database->commit();
            return true;
        } catch (Exception $e) {
            $this->database->rollBack();
            error_log("Error en rechazarPreguntaSugerida: " . $e->getMessage());
            return false;
        }
    }
    ######################################################### MI PARTE

    public function crearPregunta($datosPregunta, $respuestas)
    {
        try {
            $this->database->beginTransaction();

            // Insertar la pregunta en la tabla 'pregunta'
            $sqlPregunta = "INSERT INTO pregunta (texto, id_categoria, id_creador, estado) VALUES (?, ?, ?, ?)";

            $this->database->execute($sqlPregunta, [
                $datosPregunta["texto"],
                $datosPregunta["id_categoria"],
                $datosPregunta["id_creador"],
                $datosPregunta["estado"] ?? 'activa'
            ]);

            $idPregunta = $this->database->lastInsertId();

            // Insertar las respuestas en la tabla 'respuesta'
            $sqlRespuesta = "INSERT INTO respuesta (id_pregunta, texto, es_correcta) VALUES (?, ?, ?)";
            foreach ($respuestas as $r) {
                $this->database->execute($sqlRespuesta, [
                    $idPregunta,
                    $r["texto"],
                    $r["es_correcta"] ? 1 : 0
                ]);
            }

            $this->database->commit();
            return true;

        } catch (Exception $e) {
            $this->database->rollBack();
            error_log("Error al crear pregunta: " . $e->getMessage());
            return false;
        }
    }

    public function editarPregunta($idPregunta, $datosPregunta, $respuestas)
    {
        try {
            $this->database->beginTransaction();

            // Actualizar la pregunta en la tabla 'pregunta'
            $sqlPregunta = "UPDATE pregunta SET texto = ?, id_categoria = ?, id_dificultad = ? WHERE id = ?";
            $this->database->execute($sqlPregunta, [
                $datosPregunta["texto"],
                $datosPregunta["id_categoria"],
                $datosPregunta["id_dificultad"] ?? 2, // Se mantiene el valor por defecto si no se especifica
                $idPregunta
            ]);

            // Eliminar respuestas existentes para la pregunta (para luego reinsertar las nuevas)
            $sqlDeleteRespuestas = "DELETE FROM respuesta WHERE id_pregunta = ?";
            $this->database->execute($sqlDeleteRespuestas, [$idPregunta]);

            // Insertar las respuestas actualizadas
            $sqlInsertRespuesta = "INSERT INTO respuesta (id_pregunta, texto, es_correcta) VALUES (?, ?, ?)";
            foreach ($respuestas as $r) {
                $this->database->execute($sqlInsertRespuesta, [
                    $idPregunta,
                    $r["texto"],
                    $r["es_correcta"] ? 1 : 0
                ]);
            }

            $this->database->commit();
            return true;

        } catch (Exception $e) {
            $this->database->rollBack();
            error_log("Error al editar pregunta: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerPreguntaPorId($id)
    {
        // Obtener la pregunta
        $sqlPregunta = "SELECT p.id, p.texto, p.id_categoria, p.id_dificultad, c.nombre AS nombre_categoria
                        FROM pregunta p
                        JOIN categoria c ON p.id_categoria = c.id
                        WHERE p.id = ?";
        $pregunta = $this->database->query($sqlPregunta, [$id]);

        if (empty($pregunta)) {
            return null;
        }

        $pregunta = $pregunta[0]; // La consulta debería devolver solo una fila

        // Obtener las respuestas asociadas
        $sqlRespuestas = "SELECT texto, es_correcta FROM respuesta WHERE id_pregunta = ?";
        $respuestas = $this->database->query($sqlRespuestas, [$id]);

        // Formatear las respuestas para que sean fáciles de usar en el formulario (opcionA, opcionB, etc.)
        $opciones = [];
        $correcta = '';
        if (!empty($respuestas)) {
            foreach ($respuestas as $index => $r) {
                $opcionKey = chr(65 + $index); // A, B, C, D
                $opciones["opcion" . $opcionKey] = $r["texto"];
                if ($r["es_correcta"]) {
                    $correcta = $opcionKey;
                }
            }
        }

        // Combinar datos de pregunta y respuestas
        return array_merge($pregunta, $opciones, ["correcta" => $correcta]);
    }

    public function obtenerTodas()
    {
        $sql = "
            SELECT
                p.id AS id_pregunta,
                p.texto AS texto_pregunta,
                c.nombre AS nombre_categoria,
                c.color AS color_categoria,
                d.descripcion AS nombre_dificultad,
                GROUP_CONCAT(CONCAT(r.texto, '||', r.es_correcta) ORDER BY r.id SEPARATOR ';;') AS respuestas_json
            FROM
                pregunta AS p
            JOIN
                categoria AS c ON p.id_categoria = c.id
            JOIN
                dificultad AS d ON p.id_dificultad = d.id
            LEFT JOIN
                respuesta AS r ON r.id_pregunta = p.id
            WHERE
                p.estado = 'activa'
            GROUP BY
                p.id
            ORDER BY
                p.id DESC;
        ";

        $preguntasCompletas = $this->database->query($sql);

        $resultado = [];
        foreach ($preguntasCompletas as $fila) {
            $respuestasRaw = explode(';;', $fila['respuestas_json']);
            $respuestasFormateadas = [];
            foreach ($respuestasRaw as $respuesta) {
                list($texto, $esCorrecta) = explode('||', $respuesta);
                $respuestasFormateadas[] = [
                    'texto' => $texto,
                    'es_correcta' => (bool)$esCorrecta
                ];
            }

            $resultado[] = [
                'id_pregunta' => $fila['id_pregunta'],
                'texto_pregunta' => $fila['texto_pregunta'],
                'nombre_categoria' => $fila['nombre_categoria'],
                'color_categoria' => $fila['color_categoria'],
                'nombre_dificultad' => $fila['nombre_dificultad'],
                'respuestas' => $respuestasFormateadas,
            ];
        }
        return $resultado;
    }


    public function eliminarPregunta($id)
    {
        try {
            $this->database->beginTransaction();

            // Gracias a ON DELETE CASCADE, no es necesario borrar respuestas explícitamente
            $sqlDeletePregunta = "DELETE FROM pregunta WHERE id = ?";
            $this->database->execute($sqlDeletePregunta, [$id]);

            $this->database->commit();
            return true;

        } catch (Exception $e) {
            $this->database->rollBack();
            error_log("Error al eliminar pregunta: " . $e->getMessage());
            return false;
        }
    }


}