<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    /* UserModel.php: Función en modelo para obtener usuario por nombre y verificar que esté activo (activo=1). */

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