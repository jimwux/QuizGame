<?php

class EditorController extends BaseController
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
        $usuario = $_SESSION["username"] ?? null;
        $data = [
            "usuario" => $this->model->getUserByUsername($usuario),
        ];

        $this->view->render('editorDashboard', $data);
    }
}
