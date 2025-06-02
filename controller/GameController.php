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
//        session_start();
        if (!isset($_SESSION['id'])) {
            header("Location: login");
            exit;
        }

        $usuarioId = $_SESSION['id'];
        $partidaId = $this->model->createGame($usuarioId);

        if ($partidaId) {
            $this->show($partidaId);
        } else {
            $this->view->render("lobby", ["errors" => "Error al crear la partida"]);
        }
    }

    public function show($partidaId) {
        $usuarioId = $_SESSION['id'];
        $datosPartida = $this->model->getGameById($partidaId);

        if (!$datosPartida || (int)$datosPartida['id_usuario'] !== (int)$usuarioId) {
            $this->view->render('lobby', ['mensaje' => 'Partida no encontrada o acceso denegado']);
            return;
        }

        // Obtener pregunta
        $pregunta = $this->model->getQuestionForUser($usuarioId);

        if (!$pregunta) {
            // No quedan preguntas, ya respondio todas, fin de la partida
            // Mostrar detalles de la partida en finPartidaView.mustache (crearlo)
            $puntaje = $this->model->calcScore($partidaId);
            $this->model->saveGame($partidaId, $puntaje);

            $this->view->render('finPartida', [
                'partida' => $datosPartida,
                'puntaje' => $puntaje,
                'usuario' => $_SESSION['usuario'],
            ]);
            return;
        }

        // Preparar opciones para Mustache
//        $opciones = [
//            ['key' => 'a', 'texto' => $pregunta['opcion_a']],
//            ['key' => 'b', 'texto' => $pregunta['opcion_b']],
//            ['key' => 'c', 'texto' => $pregunta['opcion_c']],
//            ['key' => 'd', 'texto' => $pregunta['opcion_d']],
//        ];
//
//        $datosParaVista = [
//            'partida' => $datosPartida,
//            'pregunta' => [
//                'id' => $pregunta['id'],
//                'texto' => $pregunta['texto'],
//                'opciones' => $opciones,
//            ],
//            'usuario' => $_SESSION['usuario'],
//        ];

        $this->view->render("game", ["datos" => $pregunta]);
    }

    // AMOLDAR A LA NECESIDAD, ES DE EJEMPLO
    public function response () { // POR EL MOMENTO ESTE METODO NO SE USA
//        session_start();

        if (!isset($_SESSION['usuario']['id'])) {
            header('Location: /login');
            exit;
        }

        $usuarioId = $_SESSION['usuario']['id'];
        $partidaId = $_POST['partidaId'] ?? null;
        $preguntaId = $_POST['preguntaId'] ?? null;
        $respuestaUsuario = $_POST['respuestaUsuario'] ?? null;

        if (!$partidaId || !$preguntaId || !$respuestaUsuario) {
            // Podés manejar error aquí
            header("Location: show?id=$partidaId");
            exit;
        }

        $datosPartida = $this->model->getGameById($partidaId);

        if (!$datosPartida || $datosPartida['usuario_id'] != $usuarioId) {
            header('Location: /login');
            exit;
        }

        // Validar respuesta
        $esCorrecta = $this->model->validateAnswer($preguntaId, $respuestaUsuario);

        // Guardar respuesta
        $this->model->saveAnswer($partidaId, $preguntaId, $respuestaUsuario, $esCorrecta);

        if (!$esCorrecta) {
            // Fin de la partida
            $puntaje = $this->model->calcScore($partidaId);
            $this->model->saveGame($partidaId, $puntaje);

            header("Location: /gameResult?id=$partidaId");
            exit;
        }

        // Si fue correcta, mostrar siguiente pregunta
        header("Location: show?id=$partidaId");
        exit;
    }

    public function getNextQuestion() // ESTE METODO SE LLAMA CUANDO EL USUARIO SELECCIONA UNA OPCION DE LA PREGUNTA
    {

//        if (session_status() == PHP_SESSION_NONE) {
//            session_start();
//        }

        $userId = $_SESSION["id"] ?? null;

        $verificarRespuesta = $this->model->verifyQuestionCorrect($_POST, $userId);

        if ($verificarRespuesta) {

            $this->view->render("game", ["datos" => $verificarRespuesta]);
        } else {
//            $this->view->render("lobby", ["errores" => "Respuesta incorrecta"]);
            header("location: /QuizGame/lobby");
        }

    }


}