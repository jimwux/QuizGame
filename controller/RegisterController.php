<?php

class RegisterController extends BaseController
{
    private $model;
    private $view;

    public function __construct($model, $view)
    {
        $this->model = $model;
        $this->view = $view;
    }

    // Validar formularios, peticiones HTTP, redirecciones y comunicar al modelo

    public function showRegisterForm()
    {
        $this->view->render("register", []);
    }

    public function processRegisterForm()
    {
        $errors = [];

        if (empty($_POST['fullName']) || empty($_POST['username']) || empty($_POST['email'])) {
            $errors[] = "Todos los campos son obligatorios.";
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El correo electrónico no tiene un formato válido.";
        }

        if ($_POST['password'] !== $_POST['confirmPassword']) {
            $errors[] = "Las contraseñas no coinciden.";
        }

        // Verificar si el usuario ya existe
        if ($this->model->usernameExists($_POST['username'])) {
            $errors[] = "El nombre de usuario ya está en uso.";
        }

        // Verificar si el email ya está en uso
        if ($this->model->emailExists($_POST['email'])) {
            $errors[] = "El correo electrónico ya está en uso.";
        }

        if (!empty($errors)) {
            $this->view->render("register", [
                'errors' => $errors,
            ]);
            return;
        }

        // Hashear contraseña
        $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Subir foto (opcional)
        $photo = '';
        if (!empty($_FILES['profilePhoto']['name'])) {
            $photo = basename($_FILES['profilePhoto']['name']);
            move_uploaded_file($_FILES['profilePhoto']['tmp_name'], "public/uploads/" . $photo);
        }

        // Generar token único para activación
        $token = bin2hex(random_bytes(16));

        // Guardar usuario
        $result = $this->model->createUser([
            'fullName' => $_POST['fullName'],
            'birthYear' => $_POST['birthYear'],
            'gender' => $_POST['gender'],
            'country' => $_POST['country'],
            'city' => $_POST['city'],
            'email' => $_POST['email'],
            'username' => $_POST['username'],
            'password' => $hashedPassword,
            'photo' => $photo,
            'token' => $token
        ]);

        if ($result !== true) {
            $errors[] = "Error al registrar usuario: " . $result;
            $this->view->render("register", [
                'errors' => $errors,
            ]);
            return;
        }

        // Mostrar vista con link de activación
        $this->view->render("registerSuccess", [
            'username' => $_POST['username'],
            'token' => $token
        ]);
    }


}