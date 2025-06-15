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

        if (!empty($_SESSION["alerta"])) {
            $data["alerta"] = $_SESSION["alerta"];
            unset($_SESSION["alerta"]);
        }

        $this->view->render('lobby', $data);
    }



}