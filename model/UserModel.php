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
    public function activarUsuarioPorToken(string $token): string {
        $db = $this->database->getConnection();
        
        if (empty($token)) {
        return 'token_invalido';
        }

        // Preparar la consulta
        $stmt = $db->prepare("SELECT id, activo FROM usuarios WHERE token_validacion = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();

        if (!$usuario) {
            return 'token_invalido';
        }

        if ((int)$usuario['activo'] === 1) {
            return 'ya_activado';
        }

        // Actualizar estado del usuario
        $update = $db->prepare("UPDATE usuarios SET activo = 1, token_validacion = NULL WHERE id = ?");
        $update->bind_param("i", $usuario['id']);
        $update->execute();

        return 'activado';
    }


}