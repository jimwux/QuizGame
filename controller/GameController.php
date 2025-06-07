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
    { // GUARDA EN LA TABLA partida LOS DATOS DE LA PARTIDA
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

    private $tiempoMaximo = 30;

    private function getTiempoRestante()
    {
        if (!isset($_SESSION['tiempo_inicio_pregunta'])) {
            return $this->tiempoMaximo; // Si no hay temporizador, devuelve el máximo
        }
        $tiempoTranscurrido = time() - $_SESSION['tiempo_inicio_pregunta'];
        $tiempoRestante = $this->tiempoMaximo - $tiempoTranscurrido;
        return max(0, $tiempoRestante); // Asegura que no sea negativo
    }


    public function show($partidaId)
    {
        $usuarioId = $_SESSION['id'];

        // Obtener pregunta
        $pregunta = $this->model->getQuestionForUser($usuarioId);
        $puntaje = $this->model->getScore($partidaId);
        $idPregunta = $pregunta["question"]["id"];

        $vriable = $this->model->verificarSiSeRecargoLaPagina($partidaId);

        if($vriable != null){
            echo "esta mal";
        } else{
        echo "esta bien";}

        exit;
        $datosPartida = $this->model->getGameById($partidaId, $pregunta);
        $estadoPartida = SesionController::obtenerEstadoPartida();

        if (!$datosPartida || (int)$datosPartida['id_usuario'] !== (int)$usuarioId) {
            $this->view->render('lobby', ['mensaje' => 'Partida no encontrada o acceso denegado']);
            return;
        }

        $tiempo = $this->getTiempoRestante();

        if (!$pregunta) {
            // No quedan preguntas, ya respondio todas, fin de la partida
            // Mostrar detalles de la partida en finPartidaView.mustache (crearlo)
            $this->model->saveGame($partidaId, $puntaje);

            $this->model->guardarResumenPartida($partidaId, $usuarioId);

            $this->view->render('finPartida', [
                'partida' => $datosPartida,
                'puntaje' => $puntaje,
                'usuario' => $_SESSION['usuario'],
                'tiempo' => $tiempo
            ]);

            return;
        }


        $datosParaVista = [
            'partida' => $datosPartida,
            'usuario' => ['id' => $_SESSION['id'], 'nombre' => $_SESSION['username']],
            'pregunta_numero_actual' => $estadoPartida['pregunta_actual_idx'],
            'puntaje' => $puntaje,
            'datos' => $pregunta,
            "tiempo" => ["tiempo" => $tiempo]
        ];

        $this->view->render('game', $datosParaVista);
    }


    public function getNextQuestion() // ESTE METODO SE LLAMA CUANDO EL USUARIO SELECCIONA UNA OPCION DE LA PREGUNTA
    {
        $valor = $_GET["badRequest"];


        if ($valor == 1) {
            echo "se recargo la pagina";
            $userId = $_SESSION["id"] ?? null;
            $ultimaPregunta = $this->model->obtenerUltimaPregunta($userId);

            $this->view->render('game', ["datos" => ["question" => $ultimaPregunta]]);
            return;
        }

        $estadoPartida = SesionController::obtenerEstadoPartida();
        $partidaId = $estadoPartida['partida_id'] ?? null;
        $usuarioId = $_SESSION['id'];

        $verificarRespuesta = $this->model->verifyQuestionCorrect($_POST, $usuarioId, $partidaId);

        if ($verificarRespuesta) {
            $this->show($partidaId);
        } else {
            // Fin de la partida
            $puntaje = $this->model->getScore($partidaId);
            $this->model->saveGame($partidaId, $puntaje);//si no me equivoco guarda sobre la carpeta partida
            $this->model->guardarResumenPartida($partidaId, $partidaId);
            $data["game"] = $this->model->getResumenPartida($partidaId, $partidaId);

            $this->view->render('finPartida', $data);
        }

    }


}

/*
 * Tabla:
 *      PARTIDA_PREGUNTA
 *                      -> Si en el campo respondida_correctamente es NULL: (recargo la pagina o fue hacia atras o hacia adelante)
 *                      -> Caso que sea null se debe retornar la misma pregunta hasta que responda
 *                      -> El tiempo debera seguir corriendo en la misma partida (No empezar otra vez en 30s) (Tiempo guardado en session)
 *
 */