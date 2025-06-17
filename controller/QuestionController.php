<?php

class QuestionController extends BaseController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }




    public function showFormularioReporte()
    {
        $status = $_GET['status'] ?? 'DEFAULT';
        $mensaje = '';

        switch ($status) {
            case 'SUCCESS':
                $mensaje = "¡Pregunta reportada con éxito! Gracias por tu colaboración.";
                break;
            case 'ALREADY_REPORTED':
                $mensaje = "Error: Ya has reportado esta pregunta anteriormente.";
                break;
            case 'EMPTY_REASON':
                $mensaje = "Error: El motivo del reporte no puede estar vacío.";
                break;
            default:
                $mensaje = "Error: No se pudo procesar tu reporte en este momento.";
                break;
        }

        $data['mensaje'] = $mensaje;
        $data['url_partida'] = '/QuizGame/game/mostrarPregunta';

        SessionController::actualizarEstadoPartida('pausa_timestamp', time());

        $this->view->render("formReport", $data);
    }

    /**
     * Recibe los datos del formulario, procesa el reporte y redirige a la página de estado.
     */
    public function enviarReporte()
    {
        $idUsuario = $_SESSION['id'] ?? null;
        $idPregunta = $_POST['idPregunta'] ?? null;
        $motivo = $_POST['motivo'] ?? null;

        $resultado = $this->model->guardarReporte($idUsuario, $idPregunta, $motivo);


        $this->redirectTo('question/showFormularioReporte?status=' . $resultado);
    }

    public function showSuggested()
    {
        $preguntasSugeridas = $this->model->obtenerPreguntasSugeridas();
        $this->view->render('questionsSuggested', ["pregunta" => $preguntasSugeridas]);
    }

    public function aprobarPregunta()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && isset($_POST['id_pregunta'])) {
            $accion = $_POST['accion'];
            $idPregunta = $_POST['id_pregunta'];
            $mensaje = "";
            $esExito = false;

            if ($accion == "aprobar") {
                if ($this->model->aprobarPreguntaSugerida($idPregunta)) {
                    $mensaje = "Pregunta aprobada correctamente :D";
                    $esExito = true;
                } else {
                    $mensaje = "Error al aprobar la pregunta D:";
                }
            } else if ($accion == "rechazar") {
                if ($this->model->rechazarPreguntaSugerida($idPregunta)) {
                    $mensaje = "Pregunta rechazada correctamente.";
                    $esExito = true;
                } else {
                    $mensaje = "Error al rechazar la pregunta";
                }
            }


            header('Content-Type: application/json');
            echo json_encode([
                'success' => $esExito,
                'mensaje' => $mensaje,
                'esExito' => $esExito,
                'esError' => !$esExito
            ]);
            exit();
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'mensaje' => 'Peticionj invalida',
                'esExito' => false,
                'esError' => true
            ]);
            exit();
        }
    }

    public function formularioSugerirPregunta()
    {
        $categorias = $this->model->obtenerCategorias(); // Ya lo usás en otras vistas
        $this->view->render("suggestQuestion", ["categorias" => $categorias]);
    }

    public function enviarSugerencia()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $campos = [
                "texto" => "Enunciado",
                "opcionA" => "Opción A",
                "opcionB" => "Opción B",
                "opcionC" => "Opción C",
                "opcionD" => "Opción D",
                "correcta" => "Opción correcta",
                "categoria" => "Categoría"
            ];

            $errores = [];

            foreach ($campos as $campo => $nombreAmigable) {
                if (empty($_POST[$campo])) {
                    $errores[] = "El campo <strong>$nombreAmigable</strong> es obligatorio.";

                }
            }

            if (!empty($errores)) {
                $form = $_POST;

                // Marcar radio buttons
                $form["checkedA"] = ($form["correcta"] ?? '') === "A";
                $form["checkedB"] = ($form["correcta"] ?? '') === "B";
                $form["checkedC"] = ($form["correcta"] ?? '') === "C";
                $form["checkedD"] = ($form["correcta"] ?? '') === "D";

                // Marcar opción seleccionada
                $categorias = $this->model->obtenerCategorias();
                $formCategoria = $_POST["categoria"] ?? null;
                $categoriasMarcadas = array_map(function ($cat) use ($formCategoria) {
                    $cat["selected"] = $cat["id"] == $formCategoria;
                    return $cat;
                }, $categorias);

                $this->view->render("suggestQuestion", [
                    "errores" => $errores,
                    "formulario" => $form,
                    "categorias" => $categoriasMarcadas
                ]);
                return;
            }

            // Datos válidos
            $dataPregunta = [
                "id_usuario" => $_SESSION["id"],
                "texto" => $_POST["texto"],
                "id_categoria" => $_POST["categoria"]
            ];

            $respuestas = [
                ["texto" => $_POST["opcionA"], "es_correcta" => ($_POST["correcta"] === "A")],
                ["texto" => $_POST["opcionB"], "es_correcta" => ($_POST["correcta"] === "B")],
                ["texto" => $_POST["opcionC"], "es_correcta" => ($_POST["correcta"] === "C")],
                ["texto" => $_POST["opcionD"], "es_correcta" => ($_POST["correcta"] === "D")],
            ];

            $exito = $this->model->guardarSugerencia($dataPregunta, $respuestas);

            $_SESSION["alerta"] = $exito
                ? "¡Tu sugerencia fue enviada con éxito!"
                : "Hubo un error al guardar tu sugerencia.";

            $this->redirectTo('lobby');
        }
    }
    ######################################################################## MI PARTE

    public function all()
    {
        $preguntas = $this->model->obtenerTodas();

        $alerta = $_SESSION["alerta"] ?? null;
        unset($_SESSION["alerta"]); // Eliminás el mensaje para que no se repita

        $this->view->render('listQuestion', [
            "preguntas" => $preguntas,
            "alerta" => $alerta
        ]);
    }

    public function create()#formularioCrearPregunta
    {
        $categorias = $this->model->obtenerCategorias();
        $this->view->render("formQuestion", [
            "accion" => "/QuizGame/question/guardarPregunta",
            "boton" => "Crear",
            "categorias" => $categorias
        ]);
    }

    public function guardarPregunta()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Validaciones (similar a enviarSugerencia, pero para creación/edición directa)
            $campos = [
                "texto" => "Enunciado",
                "opcionA" => "Opción A",
                "opcionB" => "Opción B",
                "opcionC" => "Opción C",
                "opcionD" => "Opción D",
                "correcta" => "Opción correcta",
                "categoria" => "Categoría"
            ];

            $errores = [];
            foreach ($campos as $campo => $nombreAmigable) {
                if (empty($_POST[$campo]) && $campo !== "id") { // 'id' no es obligatorio en el POST para crear
                    $errores[] = "El campo <strong>$nombreAmigable</strong> es obligatorio.";
                }
            }

            if (!empty($errores)) {
                $form = $_POST;
                $form["checkedA"] = ($form["correcta"] ?? '') === "A";
                $form["checkedB"] = ($form["correcta"] ?? '') === "B";
                $form["checkedC"] = ($form["correcta"] ?? '') === "C";
                $form["checkedD"] = ($form["correcta"] ?? '') === "D";

                $categorias = $this->model->obtenerCategorias();
                $formCategoria = $_POST["categoria"] ?? null;
                $categoriasMarcadas = array_map(function ($cat) use ($formCategoria) {
                    $cat["selected"] = $cat["id"] == $formCategoria;
                    return $cat;
                }, $categorias);


                $this->view->render("formQuestion", [
                    "errores" => $errores,
                    "formulario" => $form,
                    "categorias" => $categoriasMarcadas,
                    "accion" => "/QuizGame/question/guardarPregunta",
                    "boton" => "Crear"
                ]);
                return;
            }

            $dataPregunta = [
                "texto" => $_POST["texto"],
                "id_categoria" => $_POST["categoria"],
                "id_creador" => $_SESSION["id"] ?? 1, // Asumo un ID de usuario por defecto si no hay sesión
                "estado" => 'activa' // Las preguntas creadas por admin son activas por defecto
            ];

            $respuestas = [
                ["texto" => $_POST["opcionA"], "es_correcta" => ($_POST["correcta"] === "A")],
                ["texto" => $_POST["opcionB"], "es_correcta" => ($_POST["correcta"] === "B")],
                ["texto" => $_POST["opcionC"], "es_correcta" => ($_POST["correcta"] === "C")],
                ["texto" => $_POST["opcionD"], "es_correcta" => ($_POST["correcta"] === "D")],
            ];

            $exito = $this->model->crearPregunta($dataPregunta, $respuestas);

            $_SESSION["alerta"] = $exito
                ? "¡Pregunta creada con éxito!"
                : "Hubo un error al crear la pregunta.";

            $this->redirectTo('/question/all'); // Redirige al listado de preguntas
        }
    }

    public function edit()#formularioEditarPregunta
    {
        
        $id = $_GET['id'] ?? null;
        $pregunta = $this->model->obtenerPreguntaPorId($id);

        if (!$pregunta) {
            $_SESSION["alerta"] = "Pregunta no encontrada.";
            $this->redirectTo('/question/all');
            return;
        }

        $categorias = $this->model->obtenerCategorias();
        $categoriasMarcadas = array_map(function ($cat) use ($pregunta) {
            $cat["selected"] = $cat["id"] == $pregunta["id_categoria"];
            return $cat;
        }, $categorias);

        $dificultades = $this->model->obtenerDificultades(); // Necesitas este método
        $dificultadesMarcadas = array_map(function ($dif) use ($pregunta) {
            $dif["selected"] = $dif["id"] == $pregunta["id_dificultad"];
            return $dif;
        }, $dificultades);

        // Marcar radio buttons para la respuesta correcta
        $pregunta["checkedA"] = ($pregunta["correcta"] ?? '') === "A";
        $pregunta["checkedB"] = ($pregunta["correcta"] ?? '') === "B";
        $pregunta["checkedC"] = ($pregunta["correcta"] ?? '') === "C";
        $pregunta["checkedD"] = ($pregunta["correcta"] ?? '') === "D";


        $this->view->render("formQuestion", [
            "accion" => "/Quizgame/question/guardarEdicionPregunta",
            "boton" => "Actualizar",
            "pregunta" => $pregunta,
            "categorias" => $categoriasMarcadas
        ]);
    }

    public function guardarEdicionPregunta()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id_pregunta"])) {
            $idPregunta = $_POST["id_pregunta"];

            // Validaciones (similar a guardarPregunta)
            $campos = [
                "texto" => "Enunciado",
                "opcionA" => "Opción A",
                "opcionB" => "Opción B",
                "opcionC" => "Opción C",
                "opcionD" => "Opción D",
                "correcta" => "Opción correcta",
                "categoria" => "Categoría"
            ];

            $errores = [];
            foreach ($campos as $campo => $nombreAmigable) {
                if (empty($_POST[$campo])) {
                    $errores[] = "El campo <strong>$nombreAmigable</strong> es obligatorio.";
                }
            }

            if (!empty($errores)) {
                // Si hay errores, renderizar el formulario con los datos y errores
                $form = $_POST;
                $form["checkedA"] = ($form["correcta"] ?? '') === "A";
                $form["checkedB"] = ($form["correcta"] ?? '') === "B";
                $form["checkedC"] = ($form["correcta"] ?? '') === "C";
                $form["checkedD"] = ($form["correcta"] ?? '') === "D";

                $categorias = $this->model->obtenerCategorias();
                $formCategoria = $_POST["categoria"] ?? null;
                $categoriasMarcadas = array_map(function ($cat) use ($formCategoria) {
                    $cat["selected"] = $cat["id"] == $formCategoria;
                    return $cat;
                }, $categorias);

                $this->view->render("formQuestion", [
                    "errores" => $errores,
                    "formulario" => $form,
                    "pregunta" => ["id" => $idPregunta], // Se necesita el ID para que el formulario sepa qué editar
                    "categorias" => $categoriasMarcadas,
                    "accion" => "/QuizGame/question/guardarEdicionPregunta",
                    "boton" => "Actualizar"
                ]);
                return;
            }

            $dataPregunta = [
                "texto" => $_POST["texto"],
                "id_categoria" => $_POST["categoria"]
            ];

            $respuestas = [
                ["texto" => $_POST["opcionA"], "es_correcta" => ($_POST["correcta"] === "A")],
                ["texto" => $_POST["opcionB"], "es_correcta" => ($_POST["correcta"] === "B")],
                ["texto" => $_POST["opcionC"], "es_correcta" => ($_POST["correcta"] === "C")],
                ["texto" => $_POST["opcionD"], "es_correcta" => ($_POST["correcta"] === "D")],
            ];

            $exito = $this->model->editarPregunta($idPregunta, $dataPregunta, $respuestas);

            $_SESSION["alerta"] = $exito
                ? "¡Pregunta actualizada con éxito!"
                : "Hubo un error al actualizar la pregunta.";

            $this->redirectTo('/question/all');
        } else {
            $_SESSION["alerta"] = "Solicitud inválida para actualizar la pregunta.";
            $this->redirectTo('/question/all');
        }
    }

    public function delete()#eliminarPregunta
    {
        
        $id = $_GET['id'] ?? null;
        if ($this->model->eliminarPregunta($id)) {
            $_SESSION["alerta"] = "Pregunta eliminada correctamente.";
        } else {
            $_SESSION["alerta"] = "Error al eliminar la pregunta.";
        }
        $this->redirectTo('/question/all');
    }

    public function reported()
    {
        $data['alerta'] = null;
        $status = $_GET['status'] ?? null;

        if ($status) {
            switch ($status) {
                case 'approved':
                    $data['alerta'] = '¡El reporte ha sido marcado como revisado con éxito!';
                    break;
                case 'rejected':
                    $data['alerta'] = 'El reporte ha sido rechazado correctamente.';
                    break;
                case 'error':
                    $data['alerta'] = 'Hubo un error al procesar la solicitud.';
                    break;
            }
        }

        $data['preguntas'] = $this->model->obtenerPreguntasReportadas();

        $this->view->render('questionsReported', $data);
    }

    public function approveReport()
    {
        $reporte_id = $_POST['reporte_id'] ?? null;
        if ($reporte_id) {
            $this->model->aprobarReporte($reporte_id);
            $this->redirectTo('question/reported?status=approved');
        } else {
            $this->redirectTo('question/reported?status=error');
        }
    }

    public function rejectReport()
    {
        $reporte_id = $_POST['reporte_id'] ?? null;
        if ($reporte_id) {
            $this->model->rechazarReporte($reporte_id);
            $this->redirectTo('question/reported?status=rejected');
        } else {
            $this->redirectTo('question/reported?status=error');
        }
    }
}