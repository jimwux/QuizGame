<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function usernameExists($username)
    {
        $usernameEscaped = $this->database->getConnection()->real_escape_string($username);
        $sql = "SELECT id FROM usuarios WHERE usuario = '$usernameEscaped'";
        $result = $this->database->query($sql);

        if ($result === false) {
            return false;
        }
        return count($result) > 0;
    }

    public function emailExists($email)
    {
        $emailEscaped = $this->database->getConnection()->real_escape_string($email);
        $sql = "SELECT id FROM usuarios WHERE mail = '$emailEscaped'";
        $result = $this->database->query($sql);

        if ($result === false) {
            return false;
        }

        return count($result) > 0;
    }

    public function createUser($data)
    {
        $db = $this->database->getConnection();

        $nombreCompleto = $db->real_escape_string($data['fullName']);
        $anioNacimiento = (int)$data['birthYear'];
        $sexo = $db->real_escape_string($data['gender']);
        $pais = $db->real_escape_string($data['country']);
        $ciudad = $db->real_escape_string($data['city']);
        $mail = $db->real_escape_string($data['email']);
        $usuario = $db->real_escape_string($data['username']);
        $password = $db->real_escape_string($data['password']);
        $fotoPerfil = $db->real_escape_string($data['photo']);
        $tokenValidacion = $db->real_escape_string($data['token']);

        $sql = "INSERT INTO usuarios 
        (nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password, foto_perfil, token_validacion, activo) 
        VALUES 
        ('$nombreCompleto', $anioNacimiento, '$sexo', '$pais', '$ciudad', '$mail', '$usuario', '$password', '$fotoPerfil', '$tokenValidacion', 0)";

        if (!$db->query($sql)) {
            return $db->error;  // Devuelve mensaje de error
        }

        return true;  // Inserción exitosa
    }


    // Lógica de negocio y acceso a la BD (consultas)
    public function getUserById($id)
    {
        $query = $this->database->prepare("SELECT * FROM usuario WHERE id = :id LIMIT 1");
        $query->bindValue(":id", $id);
        $query->execute();
        return $query->fetch();
    }

    public function getUserByUsername($username)
    {
        $query = $this->database->prepare("SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1");
        $query->bindValue(":usuario", $username);
        $query->execute();
        return $query->fetch();

//        return $this->database->query("SELECT * FROM usuarios WHERE usuario = $username LIMIT 1")->fetch();
    }

    public function login($username, $password)
    {
        $usuario = $this->getUserByUsername($username);

        echo "<pre>";
        var_dump($usuario);
        echo "</pre>";
    }


}