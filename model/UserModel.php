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
        $latitud = $db->real_escape_string($data['latitude']);
        $longitud = $db->real_escape_string($data['longitude']);
        $mail = $db->real_escape_string($data['email']);
        $usuario = $db->real_escape_string($data['username']);
        $password = $db->real_escape_string($data['password']);
        $fotoPerfil = $db->real_escape_string($data['photo']);
        $tokenValidacion = $db->real_escape_string($data['token']);

        $sql = "INSERT INTO usuarios 
        (nombre_completo, año_nacimiento, sexo, pais, ciudad, latitud, longitud, mail, usuario, password, foto_perfil, token_validacion, activo) 
        VALUES 
        ('$nombreCompleto', $anioNacimiento, '$sexo', '$pais', '$ciudad', '$latitud', '$longitud', '$mail', '$usuario', '$password', '$fotoPerfil', '$tokenValidacion', 0)";

        if (!$db->query($sql)) {
            return $db->error;  // Devuelve mensaje de error
        }

        return true;  // Inserción exitosa
    }

    public function activarUsuarioPorToken(string $token): string
    {
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

    public function getUserById($id)
    {
        $query = $this->database->prepare("SELECT * FROM usuario WHERE id = :id LIMIT 1");
        $query->bindValue(":id", $id);
        $query->execute();
        return $query->fetch();
    }

    public function getUserByUsername($username)
    {
        $query = $this->database->getConnection()->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
        $query->bind_param("s", $username);
        $query->execute();
        return $query->get_result()->fetch_assoc();

    }

    public function validateLogin($username, $password): array
    {

        $errorsInputsEmptys = [];

        if (!$username) {
            $errorsInputsEmptys[] = "El nombre de usuario es obligatorio";
        }

        if (!$password) {
            $errorsInputsEmptys[] = "La password es obligatoria";
        }

        if (!empty($errorsInputsEmptys)) {
            return $errorsInputsEmptys;
        }

        $usuario = $this->getUserByUsername($username);

        $errors = [];

        $usernameDB = $usuario['usuario'] ?? [];
        $passwordHash = $usuario['password'] ?? "";
        $active = $usuario['activo'] ?? [];

        if (!password_verify($password, $passwordHash) || $usernameDB != $username) {
            $errors[] = "Credenciales invalidas";
        }

        if (!$this->verifyAccountActive($active) && !empty($usernameDB)) {
            $errors[] = "Su cuenta no esta activada";
            return $errors;
        }

        return $errors;
    }

    public function verifyAccountActive($active): bool
    {
        $isActive = false;
        if ($active == 1) {
            $isActive = true;
        }

        return $isActive;
    }

    public function login($username): bool
    {
        $usuario = $this->getUserByUsername($username);

        $_SESSION["id"] = $usuario['id'];
        $_SESSION["username"] = $usuario['usuario'];
        $_SESSION["nombre"] = $usuario['nombre_completo'];
        $_SESSION["usuario_rol"] = $usuario["rol"];

        return true;
    }

    public function getGamesResultByUser($usuarioId)
    {
        $usuarioId = (int)$usuarioId;
        $sql = "SELECT rp.*, 
                c.nombre AS nombre_categoria, c.color AS color_categoria
                FROM resumen_partida rp
                LEFT JOIN categoria c ON rp.id_categoria = c.id
                WHERE rp.id_usuario = $usuarioId
                ORDER BY fecha_partida DESC
                LIMIT 4";

        return $this->database->query($sql);
    }
    public function getGamesResultByUsername($username)
    {
        $usuario = $this->getUserByUsername($username);

        $usuarioId = (int) $usuario['id'];
        $sql = "SELECT rp.*, 
                c.nombre AS nombre_categoria, c.color AS color_categoria
                FROM resumen_partida rp
                LEFT JOIN categoria c ON rp.id_categoria = c.id
                WHERE rp.id_usuario = $usuarioId
                ORDER BY fecha_partida DESC
                LIMIT 4";

        return $this->database->query($sql);
    }

}