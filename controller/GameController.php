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
        session_start();
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

        // Obtener la siguiente pregunta pendiente (no respondida)
        $pregunta = $this->model->getNextQuestion($partidaId);

        if (!$pregunta) {
            // No quedan preguntas, fin de la partida
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
            'usuario' => $_SESSION['usuario'],
        ];

        $this->view->render('juego', $datosParaVista);
    }

    // AMOLDAR A LA NECESIDAD, ES DE EJEMPLO
    public function response () {
        session_start();

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


}