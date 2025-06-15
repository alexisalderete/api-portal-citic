<?php
class UserModel {
    private $db;
    private $table = 'usuarios';

    public $id;
    public $username;
    public $password;
    public $tipo;
    public $created_at;
    public $inscripciones_id;

    public function __construct($db) {
        $this->db = $db;
    }
    // para hacer login
    public function login() {
        $sql = 'SELECT usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, inscripciones_id FROM ' . $this->table . ' WHERE usuarios_nombre = ? LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $this->username = htmlspecialchars(strip_tags($this->username));
        $result->bindParam(1, $this->username);
        $result->execute();
        if($result->rowCount() == 1) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['usuarios_id'];
            $this->username = $row['usuarios_nombre'];
            $this->password = $row['usuarios_clave'];
            $this->tipo = $row['usuarios_tipo'];
            $this->inscripciones_id = $row['inscripciones_id'];
            return true;
        }

        return false;
    }

    // Crear usuario (registro)
    public function create_user_model() {
        $sql = 'INSERT INTO '.$this->table.' SET usuarios_nombre = :username, usuarios_clave = :password, usuarios_tipo = :tipo';
        $result = $this->db->prepare($sql);
        // Limpiar datos
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));

        // Hash de la contraseña
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        // Vincular parámetros
        $result->bindParam(':username', $this->username);
        $result->bindParam(':password', $this->password);
        $result->bindParam(':tipo', $this->tipo);

        if($result->execute()) {
            return true;
        }

        return false;
    }

    public function get_all_users_model() {
        $sql = 'SELECT usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, inscripciones_id FROM ' . $this->table;
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result;
    }

    public function get_user_by_id_model($id) {
        $sql = 'SELECT usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, inscripciones_id FROM ' . $this->table . ' WHERE usuarios_id = ? LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $result->bindParam(1, $id);
        $result->execute();
        return $result;
    }


}