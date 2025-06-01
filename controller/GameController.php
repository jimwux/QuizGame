<?php

class GameController extends BaseController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function createGame() {
        if (!isset($_SESSION['id'])) {
            header("Location: login");
            exit;
        }

        $usuarioId = $_SESSION['id'];
        $partidaId = $this->model->createGame($usuarioId);

        if ($partidaId) {
            // Reiniciar estado de la partida en sesión al crear una nueva
            SesionController::reiniciarPartida();
            SesionController::guardarEstadoPartida([
                'partida_id' => $partidaId,
                'pregunta_actual_idx' => 0, // Índice de la pregunta actual (0-based)
                'respuestas_dadas' => [],
                'puntaje_acumulado' => 0,
                'juego_terminado' => false
            ]);
            $this->show($partidaId);
        } else {
            $this->view->render("lobby", ["errors" => "Error al crear la partida"]);
        }
    }

    public function show(int $id) {
        $partidaId = $id;
        $usuarioId = $_SESSION['id'];
        $datosPartida = $this->model->getGameById($partidaId);
        $estadoPartida = SesionController::obtenerEstadoPartida();

        // Validar que la partida pertenezca al usuario y que el estado de sesión coincida
        if (!$datosPartida || (int)$datosPartida['id_usuario'] !== (int)$usuarioId || !$estadoPartida || $estadoPartida['partida_id'] != $partidaId) {
            $this->view->render('lobby', ['mensaje' => 'Partida no encontrada o acceso denegado. Es posible que el estado de la partida se haya perdido.']);
            return;
        }

        // Si el juego ya está terminado en la sesión, redirigir a la pantalla final
        if ($estadoPartida['juego_terminado']) {
            $puntaje = $this->model->calcScore($partidaId);
            $this->view->render('finPartida', [
                'partida' => $datosPartida,
                'puntaje' => $puntaje,
                'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            ]);
            return;
        }

        // Obtener la siguiente pregunta pendiente (no respondida)
        $pregunta = $this->model->getNextQuestion($partidaId, $usuarioId); // Pasamos usuarioId para la exclusión de preguntas vistas

        if (!$pregunta) {
            // No quedan preguntas, fin de la partida
            $puntaje = $this->model->calcScore($partidaId);
            $this->model->saveGame($partidaId, $puntaje);

            // Actualizar estado de la partida en sesión
            $estadoPartida['juego_terminado'] = true;
            $estadoPartida['puntaje_acumulado'] = $puntaje;
            SesionController::guardarEstadoPartida($estadoPartida);

            $this->view->render('finPartida', [
                'partida' => $datosPartida,
                'puntaje' => $puntaje,
                'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            ]);
            return;
        }

        // Incrementar el contador de preguntas en la sesión (si es una nueva pregunta)
        if ($estadoPartida['pregunta_actual_idx'] == (count($estadoPartida['respuestas_dadas']))) {
            $estadoPartida['pregunta_actual_idx']++;
            SesionController::guardarEstadoPartida($estadoPartida);
        }

        // Preparar opciones para Mustache
        $opciones = [
            ['key' => 'a', 'texto' => $pregunta['opcion_a']],
            ['key' => 'b', 'texto' => $pregunta['opcion_b']],
            ['key' => 'c', 'texto' => $pregunta['opcion_c']],
            ['key' => 'd', 'texto' => $pregunta['opcion_d']],
        ];

        $datosParaVista = [
            'partida' => $datosPartida,
            'pregunta' => [
                'id' => $pregunta['id'],
                'texto' => $pregunta['texto'],
                'opciones' => $opciones,
            ],
            'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            'pregunta_numero_actual' => $estadoPartida['pregunta_actual_idx'],
            'puntaje_acumulado' => $estadoPartida['puntaje_acumulado'],
        ];

        //$htmlParaDebugging = $this->view->render("game.mustache", $datosParaVista); // El 'true' es para que render devuelva el string
        //error_log("HTML devuelto por GameController::show():\n" . $htmlParaDebugging);

        $this->view->render('game', $datosParaVista);
    }

    public function responderAjax() {
        header('Content-Type: application/json');

        $partidaId = $_POST['partidaId'] ?? null;
        $preguntaId = $_POST['preguntaId'] ?? null;
        $respuestaUsuario = $_POST['respuestaUsuario'] ?? null;
        $usuarioId = $_SESSION['id'] ?? null; // Asumiendo que $_SESSION['id'] contiene el ID del usuario logueado

        // LOG 1: Verificar los datos de entrada
        error_log("responderAjax: Datos recibidos - Partida: $partidaId, Pregunta: $preguntaId, Respuesta Usuario: $respuestaUsuario, Usuario: $usuarioId");

        $estadoPartida = SesionController::obtenerEstadoPartida();

        // LOG 2: Verificar el estado de la partida de la sesión
        error_log("responderAjax: Estado de partida en sesión - " . json_encode($estadoPartida));


        // Validaciones básicas
        if (!$partidaId || !$preguntaId || !$respuestaUsuario || !$usuarioId || !$estadoPartida || ($estadoPartida && $estadoPartida['partida_id'] != $partidaId)) {
            // Añadir logs para cada condición que falla
            if (!$partidaId) echo json_encode("responderAjax FALLO: partidaId es nulo/vacío.");
            if (!$preguntaId) echo json_encode("responderAjax FALLO: preguntaId es nulo/vacío.");
            if (!$respuestaUsuario) echo json_encode("responderAjax FALLO: respuestaUsuario es nulo/vacío.");
            if (!$usuarioId) echo json_encode("responderAjax FALLO: usuarioId es nulo/vacío.");
            if (!$estadoPartida) echo json_encode("responderAjax FALLO: estadoPartida es nulo/vacío.");
            if ($estadoPartida && $estadoPartida['partida_id'] != $partidaId) echo json_encode("responderAjax FALLO: ID de partida en sesión no coincide (" . $estadoPartida['partida_id'] . " vs " . $partidaId . ").");
            error_log("responderAjax: Fallo en validación de datos de entrada o sesión no sincronizada.");
            echo json_encode(['error' => 'Datos inválidos o sesión no sincronizada.', 'juego_terminado' => true, 'siguiente_url' => '/lobby']);
            exit;
        }

        // Validar la respuesta
        // LOG 3: Antes de llamar a validateAnswer
        error_log("responderAjax: Llamando a validateAnswer para Pregunta $preguntaId con Respuesta '$respuestaUsuario'");
        $esCorrecta = $this->model->validateAnswer($preguntaId, $respuestaUsuario);
        // LOG 4: Resultado de la validación
        error_log("responderAjax: Resultado de validateAnswer: " . ($esCorrecta ? 'Correcta' : 'Incorrecta'));

        $this->model->saveAnswer($partidaId, $preguntaId, $respuestaUsuario, $esCorrecta);
        error_log("responderAjax: Respuesta guardada en DB.");

        // Actualizar el estado de la partida en sesión
        $estadoPartida['respuestas_dadas'][] = [
            'pregunta_id' => $preguntaId,
            'respuesta_elegida' => $respuestaUsuario,
            'correcta' => $esCorrecta
        ];
        error_log("responderAjax: Respuesta añadida a respuestas_dadas en sesión.");


        // Calcular puntaje acumulado dinámicamente o al final si prefieres
        $estadoPartida['puntaje_acumulado'] = $this->model->calcScore($partidaId);
        error_log("responderAjax: Puntaje acumulado después de esta respuesta: " . $estadoPartida['puntaje_acumulado']);


        // Guardar la pregunta como "vista" para el usuario
        $this->model->markQuestionAsSeen($usuarioId, $preguntaId);
        error_log("responderAjax: Pregunta $preguntaId marcada como vista para usuario $usuarioId.");


        SesionController::guardarEstadoPartida($estadoPartida);
        error_log("responderAjax: Estado de partida actualizado y guardado en sesión.");


        $datosRespuesta = [];
        $datosRespuesta['fue_correcta'] = $esCorrecta;
        $datosRespuesta['puntaje_actual'] = $estadoPartida['puntaje_acumulado'];
        $datosRespuesta['pregunta_actual_numero'] = $estadoPartida['pregunta_actual_idx'];
        // LOG 5: Estado inicial de juego_terminado
        $datosRespuesta['juego_terminado'] = false; // Asumimos que no termina a menos que se indique lo contrario
        $datosRespuesta['siguiente_url'] = ""; // Vacío por defecto
        $datosRespuesta['mensaje_feedback'] = "";


        if (!$esCorrecta) {
            // Si es incorrecta, el juego termina. Preparamos la URL final.
            $this->model->saveGame($partidaId, $estadoPartida['puntaje_acumulado']);
            $estadoPartida['juego_terminado'] = true;
            SesionController::guardarEstadoPartida($estadoPartida);

            $datosRespuesta['juego_terminado'] = true;
            $datosRespuesta['siguiente_url'] = "/QuizGame/gameResult?id=$partidaId"; // Asegúrate de que esta URL sea correcta para tu proyecto
            $datosRespuesta['mensaje_feedback'] = "¡Incorrecto! El juego ha terminado.";
            error_log("responderAjax: Juego terminado por respuesta incorrecta.");
        } else {
            // Si es correcta, el juego sigue.
            $siguientePregunta = $this->model->getNextQuestion($partidaId, $usuarioId);
            error_log("responderAjax: Siguiente pregunta obtenida: " . ($siguientePregunta ? json_encode($siguientePregunta) : 'NULL'));

            if (!$siguientePregunta) {
                // No hay más preguntas, fin de la partida
                $this->model->saveGame($partidaId, $estadoPartida['puntaje_acumulado']);
                $estadoPartida['juego_terminado'] = true;
                SesionController::guardarEstadoPartida($estadoPartida);

                $datosRespuesta['juego_terminado'] = true;
                $datosRespuesta['siguiente_url'] = "/QuizGame/gameResult?id=$partidaId"; // Asegúrate de que esta URL sea correcta para tu proyecto
                $datosRespuesta['mensaje_feedback'] = "¡Correcto! ¡Has completado todas las preguntas!";
                error_log("responderAjax: Juego terminado por no haber más preguntas.");
            } else {
                $datosRespuesta['juego_terminado'] = false;
                $datosRespuesta['siguiente_url'] = "/QuizGame/index.php?controller=game&method=show&id=$partidaId"; // Asegúrate de que esta URL sea correcta
                $datosRespuesta['mensaje_feedback'] = "¡Correcto!";
                error_log("responderAjax: Juego continúa. Siguiente pregunta disponible.");
                // Incrementar el índice de la pregunta actual
                $estadoPartida['pregunta_actual_idx'] = ($estadoPartida['pregunta_actual_idx'] ?? 0) + 1;
                SesionController::guardarEstadoPartida($estadoPartida); // Guardar el índice actualizado
            }
        }

        // LOG 6: Respuesta JSON final
        error_log("responderAjax: Enviando respuesta JSON: " . json_encode($datosRespuesta));

        echo json_encode($datosRespuesta);
        exit; // Asegura que no se imprima nada más
    }
}