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

    /**
     * Inicia una nueva partida. Si había una anterior en curso, se pisa.
     */
    public function iniciarPartida(): void
    {
        $usuarioId = $_SESSION['id'];

        // Si ya existe una partida previa, finalizarla antes de iniciar una nueva
        $estadoPartida = SessionController::obtenerEstadoPartida();
        if (isset($estadoPartida['partida_id']) && $estadoPartida['partida_id'] != null) {
            // Finalizamos la partida anterior si existe
            $this->finalizarPartida($estadoPartida['partida_id'], $estadoPartida['puntaje']);
        }

        // Crea nueva partida
        $partidaId = $this->model->crearPartida($usuarioId);
        if (!$partidaId) {
            $this->view->render("lobby", ["errors" => "Error al crear la partida"]);
            return;
        }

        // Limpia los datos de la partida previa y guarda el estado inicial de la nueva
        SessionController::limpiarDatosPartida();
        SessionController::guardarEstadoPartida([
            'partida_id' => $partidaId,
            'pregunta_actual_id' => null,
            'respuestas_dadas' => [],
            'puntaje' => 0,
            'inicio_pregunta_timestamp' => null,
            'juego_terminado' => false,
            'categoria_actual' => null
        ]);


        $this->redirectTo('game/seleccionarCategoria');
    }

    /**
     * Selecciona una categoría random de las que tenemos en la base de datos.
     */
    public function seleccionarCategoria(): void
    {
        $categorias = $this->model->obtenerCategoriasDisponibles();

        if (empty($categorias)) {
            $this->view->render('lobby', ['mensaje' => 'No hay categorías disponibles']);
            return;
        }

        $categoriaRandom = $categorias[array_rand($categorias)];
        SessionController::actualizarEstadoPartida('categoria_actual', $categoriaRandom['id']);

        $this->view->render('category', ['categoria' => $categoriaRandom]);
    }

    /**
     * Muestra la pregunta actual o entrega una nueva si no hay una en curso.
     * Filtra las preguntas de la categoría específica
     */
    public function mostrarPregunta(): void
    {
        $estado = SessionController::obtenerEstadoPartida();
        $usuarioId = $_SESSION['id'];
        $partidaId = $estado['partida_id'] ?? null; // Obtiene el id de la partida que actualmente está en sesión

        if (isset($estado['pausa_timestamp']) && isset($estado['inicio_pregunta_timestamp'])) {
            $inicio = $estado['inicio_pregunta_timestamp'];
            $pausa = $estado['pausa_timestamp'];

            // Sumamos el tiempo pausado al inicio
            $duracionPausa = time() - $pausa;
            $estado['inicio_pregunta_timestamp'] += $duracionPausa;

            SessionController::actualizarEstadoPartida('inicio_pregunta_timestamp', $estado['inicio_pregunta_timestamp']);
            SessionController::eliminarClaveEstadoPartida('pausa_timestamp');
        }


        if (!$partidaId) {
            $this->view->render('lobby', ['mensaje' => 'No hay partida activa.']);
            return;
        }

        // Se trae la categoría aleatoria que guardamos anteriormente en sesión
        $categoriaId = $estado['categoria_actual'] ?? null;
        if (!$categoriaId) {
            $this->redirectTo('seleccionarCategoria');
        }

        // Si hay una pregunta en curso no respondida, volver a mostrarla
        if ($estado['pregunta_actual_id']) {
            // Calcular si aún está dentro del tiempo disponible
            $tiempoRestante = $this->calcularTiempoRestante($estado);

            if ($tiempoRestante > 0) {
                // Si el tiempo no se agotó, renderizar la pregunta actual
                $pregunta = $this->model->obtenerPreguntaPorId($estado['pregunta_actual_id']);
                $this->renderizarPregunta($pregunta, $estado);
                return;
            }
        }

        // Si no entró en la condición anterior, obtener nueva pregunta
        $pregunta = $this->model->obtenerPreguntaParaUsuario($usuarioId, $categoriaId);

        // Si no encuentra pregunta para el usuario, se fija si es debido a que ya
        // respondió todas las que le correspondian. De ser así, limpia la tabla
        if (!$pregunta) {
            if ($this->model->usuarioRespondioTodas($usuarioId, $categoriaId)) {
                $this->model->resetearPreguntasRespondidas($usuarioId, $categoriaId);
                $pregunta = $this->model->obtenerPreguntaParaUsuario($usuarioId, $categoriaId);
            }
        }

        // Si incluso así no encuentra preguntas (lo cual no debería suceder ya que el paso anterior
        // siempre traería preguntas de la dificultad del usuario) finaliza la partida
        if (!$pregunta) {
            $this->finalizarPartida($partidaId, $estado['puntaje']);
            return;
        }

        // Guarda los datos de la nueva pregunta en sesión
        SessionController::actualizarEstadoPartida('pregunta_actual_id', $pregunta['id']);
        SessionController::actualizarEstadoPartida('inicio_pregunta_timestamp', time());

        // Redirige a la vista con la pregunta y sus respuestas, donde también está el contador
        $this->renderizarPregunta($pregunta, $estado);

    }


    /**
     * Se ejecuta cuando se responde una pregunta.
     */
    public function responderPregunta(): void
    {
        $estado = SessionController::obtenerEstadoPartida();

        // Valida si existe una pregunta en curso
        $preguntaId = $estado['pregunta_actual_id'];
        if (!$preguntaId) {
            $this->view->render('error', ['mensaje' => 'No hay pregunta activa.']);
            return;
        }

        // Calcular el tiempo restante
        $tiempoRestante = $this->calcularTiempoRestante($estado);

        // Obtener la pregunta y su dificultad
        $pregunta = $this->model->obtenerPreguntaPorId($preguntaId);
        $dificultad = $pregunta['id_dificultad'];  // Asumiendo que 'id_dificultad' es un número que representa la dificultad

        // Comprueba si la respuesta seleccionada es correcta
        $respuestaElegida = $_POST['respuesta'] ?? null;
        $correcta = $this->model->validarRespuesta($preguntaId, $respuestaElegida);

        // Registrar la respuesta en la tabla correspondiente
        $partidaId = $estado['partida_id'];
        $this->model->registrarRespuestaEnPartida($partidaId, $preguntaId, $respuestaElegida, $correcta);

        // Multiplicamos la dificultad por el tiempo restante (tiempo en segundos)
        $puntaje = $dificultad * $tiempoRestante;

        // Si es correcta, actualizar puntaje
        if ($correcta) {
            SessionController::incrementarPuntaje($puntaje);
            $estado['puntaje'] += $puntaje;
        } else {
            // Si es incorrecta, se finaliza la partida pero aún se muestra la vista con el puntaje
            $this->finalizarPartida($estado['partida_id'], $estado['puntaje']);
        }

        // Registrar respuesta del usuario
        $usuarioId = $_SESSION['id'];
        $this->model->marcarPreguntaComoRespondidaPorUsuario(
            $usuarioId,
            $preguntaId,
            $respuestaElegida,
            $correcta ? 1 : 0
        );

        // Obtener datos para la vista
        $respuestas = $this->model->obtenerOpcionesRespuesta($preguntaId);
        $respuestaCorrecta = $this->model->obtenerRespuestaCorrecta($preguntaId);
        $categoria = $this->model->obtenerCategoriaPorId($pregunta['id_categoria']);

        // Preparar flags para Mustache
        foreach ($respuestas as &$r) {
            $r['esSeleccionadaYCorrecta'] = ($r['id'] == $respuestaElegida && $r['id'] == $respuestaCorrecta['id']);
            $r['esSeleccionadaYIncorrecta'] = ($r['id'] == $respuestaElegida && $r['id'] != $respuestaCorrecta['id']);
            $r['esCorrectaNoSeleccionada'] = ($r['id'] != $respuestaElegida && $r['id'] == $respuestaCorrecta['id']);
        }

        // Limpiar estado de la pregunta en sesión
        SessionController::actualizarEstadoPartida('pregunta_actual_id', null);
        SessionController::actualizarEstadoPartida('inicio_pregunta_timestamp', null);

        // Renderizar vista con el puntaje actualizado
        $this->view->render('answer', [
            'question' => $pregunta,
            'answers' => $respuestas,
            'correcta' => $correcta,
            'puntaje' => $estado['puntaje'], // El puntaje actualizado después de responder
            'usuario' => ['nombre' => $_SESSION['username']],
            'partida' => ['id' => $estado['partida_id']],
            'partidaFinalizada' => !$correcta, // Si la respuesta fue incorrecta, la partida está finalizada
            'category' => $categoria
        ]);
    }



    /**
     * Calcula cuántos segundos quedan para responder la pregunta.
     */
    private function calcularTiempoRestante($estado)
    {
        $tiempoInicio = $estado['inicio_pregunta_timestamp'];

        if (!$tiempoInicio) {
            return 30;  // Tiempo inicial de 30 segundos
        }

        $transcurrido = time() - $tiempoInicio;
        return max(0, 30 - $transcurrido);
    }

    /**
     * Finaliza la partida.
     */
    private function finalizarPartida($partidaId, $puntaje): void
    {
        $usuarioId = $_SESSION['id'];
        $this->model->guardarPartidaFinalizada($partidaId, $puntaje);
        $this->model->guardarResumenPartida($partidaId, $usuarioId, $puntaje);
        SessionController::limpiarDatosPartida();

    }

    /**
     * @param $pregunta
     * @param array $estado
     * @return void
     */
    public function renderizarPregunta($pregunta, array $estado): void
    {
        $this->view->render('question', [
            'datos' => [
                'question' => ['id' => $pregunta['id'], 'texto' => $pregunta['texto']],
                'answers' => $this->model->obtenerOpcionesRespuesta($pregunta['id']),
                'category' => $this->model->obtenerCategoriaPorId($pregunta['id_categoria']),
            ],
            'puntaje' => $estado['puntaje'],
            'usuario' => ['nombre' => $_SESSION['username']],
            'partida' => ['id' => $estado['partida_id']],
            'tiempo_restante' => $this->calcularTiempoRestante($estado) // usa la función para calcular
        ]);
    }


}
