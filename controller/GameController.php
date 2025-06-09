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

            SesionController::reiniciarPartida();
            SesionController::guardarEstadoPartida([
                'partida_id' => $partidaId,
                'pregunta_actual_id' => null,
                'respuestas_dadas' => [],
                'puntaje_acumulado' => 0,
                'juego_terminado' => false
            ]);

            header("Location: /QuizGame/game/show");
            exit;
        } else {
            $this->view->render("lobby", ["errors" => "Error al crear la partida"]);
        }
    }

    public function show($partidaId = null)
    {
        $this->validateSession();
        $usuarioId = $_SESSION['id'];
        $estadoPartida = SesionController::obtenerEstadoPartida();



        if (is_null($partidaId) && isset($estadoPartida['partida_id'])) {
            $partidaId = $estadoPartida['partida_id'];
        } elseif (is_null($partidaId)) {

            $this->view->render('lobby', ['mensaje' => 'No hay partida activa. Inicia una nueva.']);
            return;
        }

        $datosPartida = $this->model->getGameById($partidaId);

        if (!$datosPartida || (int)$datosPartida['id_usuario'] !== (int)$usuarioId) {
            $this->view->render('lobby', ['mensaje' => 'Partida no encontrada o acceso denegado']);
            return;
        }


        $preguntaActualIdEnSesion = $estadoPartida['pregunta_actual_id'] ?? null;
        $pregunta = null;

        if ($preguntaActualIdEnSesion) {

            $preguntaYaRespondida = $this->model->verificarPreguntaRespondidaEnPartida($partidaId, $preguntaActualIdEnSesion);

            if (!$preguntaYaRespondida) {

                $pregunta = $this->model->buscarPreguntaYRespuestasPorId($preguntaActualIdEnSesion);

                if ($pregunta) {
                    $this->model->marcarPreguntaComoActual($partidaId, $preguntaActualIdEnSesion);
                }
            }
        }


        if (!$pregunta) {
            $pregunta = $this->model->getQuestionForUser($usuarioId, $partidaId);

            if (!$pregunta) {

                $this->model->saveGame($partidaId, $estadoPartida['puntaje_acumulado']);
                $this->model->guardarResumenPartida($partidaId, $usuarioId);
                $this->view->render('finPartida', [
                    'partida' => $datosPartida,
                    'puntaje' => $estadoPartida['puntaje_acumulado'],
                    'usuario' => $_SESSION['username'],
                ]);
                return;
            }


            SesionController::actualizarEstadoPartida('pregunta_actual_id', $pregunta['question']['id']);

            $this->model->marcarPreguntaComoActual($partidaId, $pregunta['question']['id']);
        }

        $puntaje = $estadoPartida['puntaje_acumulado'];

        $datosParaVista = [
            'partida' => $datosPartida,
            'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            'puntaje' => $puntaje,
            'datos' => $pregunta
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

            $this->view->render('lobby', ['mensaje' => 'Error: Datos de la respuesta incompletos.']);
            return;
        }


        $this->model->desmarcarPreguntaActual($partidaId, $idQuestion);

        $respuestaProcesada = $this->model->procesarRespuesta(
            $idQuestion,
            $idRespuestaSeleccionada,
            $userId,
            $partidaId
        );

        if ($respuestaProcesada['correcta']) {

            SesionController::actualizarEstadoPartida('puntaje_acumulado', $estadoPartida['puntaje_acumulado'] + 1);
            $this->show($partidaId);
        } else {

            $puntajeFinal = SesionController::obtenerEstadoPartida()['puntaje_acumulado'];
            $this->model->saveGame($partidaId, $puntajeFinal);
            $this->model->guardarResumenPartida($partidaId, $userId);
            $data["game"] = $this->model->getResumenPartida($partidaId, $userId);

            SesionController::reiniciarPartida();
            $this->view->render('finPartida', $data);
        }
    }
}
