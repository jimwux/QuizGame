<?php

class UserModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }
    public function getUserById($id)
    {

        return $user = $this->database->query("SELECT * FROM USUARIO WHERE id = '$id'");

    }
    // Lógica de negocio y acceso a la BD (consultas)

}