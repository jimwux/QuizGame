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


}