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

    public function createGame() { // GUARDA EN LA TABLA partida LOS DATOS DE LA PARTIDA
        $this->validateSession();

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

    public function show($partidaId) {
        $usuarioId = $_SESSION['id'];
        $datosPartida = $this->model->getGameById($partidaId);
        $estadoPartida = SesionController::obtenerEstadoPartida();

        if (!$datosPartida || (int)$datosPartida['id_usuario'] !== (int)$usuarioId) {
            $this->view->render('lobby', ['mensaje' => 'Partida no encontrada o acceso denegado']);
            return;
        }

        // Obtener pregunta
        $pregunta = $this->model->getQuestionForUser($usuarioId);
        $puntaje = $this->model->getScore($partidaId);

        if (!$pregunta) {
            // No quedan preguntas, ya respondio todas, fin de la partida
            // Mostrar detalles de la partida en finPartidaView.mustache (crearlo)
            $this->model->saveGame($partidaId, $puntaje);
            
            $this->model->guardarResumenPartida($partidaId,$usuarioId);

            $this->view->render('finPartida', [
                'partida' => $datosPartida,
                'puntaje' => $puntaje,
                'usuario' => $_SESSION['usuario'],
            ]);
            return;
        }
        $datosParaVista = [
            'partida' => $datosPartida,
            'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            'pregunta_numero_actual' => $estadoPartida['pregunta_actual_idx'],
            'puntaje' => $puntaje,
            'datos' => $pregunta
        ];

        $this->view->render('game', $datosParaVista);
    }

    public function getNextQuestion() // ESTE METODO SE LLAMA CUANDO EL USUARIO SELECCIONA UNA OPCION DE LA PREGUNTA
    {

        $estadoPartida = SesionController::obtenerEstadoPartida();
        $partidaId = $estadoPartida['partida_id'] ?? null;
        $userId = $_SESSION["id"] ?? null;

        $verificarRespuesta = $this->model->verifyQuestionCorrect($_POST, $userId ,$partidaId);

        if ($verificarRespuesta) {
            $this->show($partidaId);
        } else {
            // Fin de la partida
            $puntaje = $this->model->getScore($partidaId);
            $this->model->saveGame($partidaId, $puntaje);//si no me equivoco guarda sobre la carpeta partida
            $this->model->guardarResumenPartida($partidaId,$userId);
            $data["game"] = $this->model->getResumenPartida($partidaId,$userId);

            $this->view->render('finPartida',$data);
        }

    }


}