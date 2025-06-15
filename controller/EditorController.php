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
        $rol = $_SESSION["usuario_rol"] ?? null;

        if ($rol !== 'editor') {
            header("Location: lobby/show");
            exit;
        }

        $data = [
            "usuario" => $this->model->getUserByUsername($usuario),
        ];

        $this->view->render('editorDashboard', $data);
    }
}
