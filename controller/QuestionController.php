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

    public function show(){

        echo "<br>";
        echo "<br>";
        echo "<br>";
        echo "<br>";
        $preguntasSugeridas =$this->model->obtenerPreguntasSugeridas();
        echo "<pre>";
        var_dump($preguntasSugeridas);
        echo "</pre>";
        echo "\n\n\nnashe";

        $this->view->render('questionsSuggested', $preguntasSugeridas);
    }

    public function aprobarPregunta(){
        $this->view->render('preguntasSugeridas');

    }

    public function rechazarPregunta(){
        $this->view->render('preguntasSugeridas');
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