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

    public function createGame()
    {
        $this->validateSession();

        $usuarioId = $_SESSION['id'];
        $partidaId = $this->model->createGame($usuarioId);

        if ($partidaId) {
            // Reiniciar estado de la partida en sesión al crear una nueva
            SesionController::reiniciarPartida();
            SesionController::guardarEstadoPartida([
                'partida_id' => $partidaId,
                'pregunta_actual_id' => null, // Inicialmente no hay pregunta asignada
                'respuestas_dadas' => [],
                'puntaje_acumulado' => 0,
                'juego_terminado' => false
            ]);
            header("Location: /QuizGame/game/show");
            exit();
            //$this->show($partidaId); -> Este metodo es muy culiao, este hacia que me rompiera todo el codigo
        } else {
            $this->view->render("lobby", ["errors" => "Error al crear la partida"]);
        }
    }

    public function show($partidaId = null)
    {
        $this->validateSession();
        $usuarioId = $_SESSION['id'];
        $estadoPartida = SesionController::obtenerEstadoPartida();

        // Si se llama show sin partidaId (ej. desde el lobby), o si el ID de partida en sesión no coincide,
        // usar el de la sesión. Esto es crucial para manejar recargas.
        if (is_null($partidaId) && isset($estadoPartida['partida_id'])) {
            $partidaId = $estadoPartida['partida_id'];
        } elseif (is_null($partidaId)) {
            // No hay partida en sesión ni se pasó por parámetro, redirigir a crear partida o lobby
            $this->view->render('lobby', ['mensaje' => 'No hay partida activa. Inicia una nueva.']);
            return;
        }

        $datosPartida = $this->model->getGameById($partidaId);

        if (!$datosPartida || (int)$datosPartida['id_usuario'] !== (int)$usuarioId) {
            $this->view->render('lobby', ['mensaje' => 'Partida no encontrada o acceso denegado']);
            return;
        }

        // 1. Intentar recuperar la pregunta actual si la partida se recargó y no fue respondida
        $preguntaActualIdEnSesion = $estadoPartida['pregunta_actual_id'] ?? null;
        $pregunta = null;

        if ($preguntaActualIdEnSesion) {
            // Verificar si la pregunta actual en sesión ya fue respondida en la BD para esta partida
            $preguntaYaRespondida = $this->model->verificarPreguntaRespondidaEnPartida($partidaId, $preguntaActualIdEnSesion);

            if (!$preguntaYaRespondida) {
                // Si la pregunta no ha sido respondida, se la volvemos a mostrar
                $pregunta = $this->model->buscarPreguntaYRespuestasPorId($preguntaActualIdEnSesion);
                // Si la pregunta fue encontrada, la marcamos como actual de nuevo, por si se perdió el estado 'actual' por alguna razón
                if ($pregunta) {
                    $this->model->marcarPreguntaComoActual($partidaId, $preguntaActualIdEnSesion);
                }
            }
        }

        // 2. Si no hay pregunta en curso (o la anterior ya fue respondida), obtener una nueva
        if (!$pregunta) {
            $pregunta = $this->model->getQuestionForUser($usuarioId, $partidaId);

            if (!$pregunta) {
                // No quedan preguntas, fin de la partida
                $this->model->saveGame($partidaId, $estadoPartida['puntaje_acumulado']);
                $this->model->guardarResumenPartida($partidaId, $usuarioId);
                $this->view->render('finPartida', [
                    'partida' => $datosPartida,
                    'puntaje' => $estadoPartida['puntaje_acumulado'],
                    'usuario' => $_SESSION['username'], // Usar 'username' para la vista
                ]);
                return;
            }

            // Guardar el ID de la nueva pregunta en la sesión para persistencia
            SesionController::actualizarEstadoPartida('pregunta_actual_id', $pregunta['question']['id']);
            // Marcar la pregunta como 'actual' en partida_pregunta
            $this->model->marcarPreguntaComoActual($partidaId, $pregunta['question']['id']);
        }

        $puntaje = $estadoPartida['puntaje_acumulado']; // Usar el puntaje de la sesión

        $datosParaVista = [
            'partida' => $datosPartida,
            'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            'puntaje' => $puntaje,
            'datos' => $pregunta // Contiene question, answers, category
        ];

        $this->view->render('game', $datosParaVista);
    }

    public function getNextQuestion()
    {
        $this->validateSession();
        $estadoPartida = SesionController::obtenerEstadoPartida();
        $partidaId = $estadoPartida['partida_id'] ?? null;
        $userId = $_SESSION["id"] ?? null;
        $idQuestion = $_POST["idQuestion"] ?? null;
        $idRespuestaSeleccionada = $_POST["id_respuesta_seleccionada"] ?? null;

        if (is_null($partidaId) || is_null($userId) || is_null($idQuestion) || is_null($idRespuestaSeleccionada)) {
            // Manejar error, datos incompletos
            $this->view->render('lobby', ['mensaje' => 'Error: Datos de la respuesta incompletos.']);
            return;
        }

        // Desmarcar la pregunta como 'actual' antes de procesar la respuesta
        $this->model->desmarcarPreguntaActual($partidaId, $idQuestion);

        $respuestaProcesada = $this->model->procesarRespuesta(
            $idQuestion,
            $idRespuestaSeleccionada,
            $userId,
            $partidaId
        );

        if ($respuestaProcesada['correcta']) {
            // Actualizar puntaje en la sesión
            SesionController::actualizarEstadoPartida('puntaje_acumulado', $estadoPartida['puntaje_acumulado'] + 1);
            $this->show($partidaId);
        } else {
            // Fin de la partida si la respuesta fue incorrecta o timeout
            $puntajeFinal = SesionController::obtenerEstadoPartida()['puntaje_acumulado'];
            $this->model->saveGame($partidaId, $puntajeFinal);
            $this->model->guardarResumenPartida($partidaId, $userId);
            $data["game"] = $this->model->getResumenPartida($partidaId, $userId);

            SesionController::reiniciarPartida(); // Limpiar el estado de la partida
            $this->view->render('finPartida', $data);
        }
    }
}
