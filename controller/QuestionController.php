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

        // Renderiza la vista intermedia con el nombre base "formReport"
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

        // Redirigimos, pasando el resultado en la URL para que showFormularioReporte lo lea
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




}