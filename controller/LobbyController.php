<?php

class LobbyController extends BaseController
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
        $usuarioId = $_SESSION['id'];
        $partidas = $this->model->getGamesResultByUser($usuarioId);

        $data = [
            'partidas' => $partidas,
        ];

        $this->view->render('lobby', $data);
    }



}