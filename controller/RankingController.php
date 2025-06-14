<?php

#Consultar el modelo y enviar datos a la vista.
class RankingController extends BaseController
{    
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    public function show()
    {
        $ranking = $this->model->obtenerRankingUsuariosTotal();

        # Se añaden las posiciones manualmente, aunque deberia estar en la vista creo xd
        foreach ($ranking as $i => &$usuario) { 
            $usuario['posicion'] = $i + 1;
        }

        $this->view->render('ranking', ['ranking' => $ranking]);

    }

}