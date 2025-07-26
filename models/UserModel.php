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
    public $docentes_id;
    public function __construct($db) {
        $this->db = $db;
    }
    // para hacer login
    public function login() {
        $sql = 'SELECT usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, inscripciones_id, docentes_id FROM ' . $this->table . ' WHERE usuarios_nombre = ? LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $this->username = htmlspecialchars(strip_tags($this->username));
        $result->bindParam(1, $this->username);
        $result->execute();
        if($result->rowCount() == 1) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if($row['usuarios_tipo'] === 'estudiante') {

                $estudiantes_sql = 'SELECT usuarios.usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, usuarios_inscripciones.inscripciones_id, docentes_id FROM usuarios
                left join usuarios_inscripciones on usuarios.usuarios_id = usuarios_inscripciones.usuarios_id WHERE usuarios_nombre = ?';
                $estudiantes_result = $this->db->prepare($estudiantes_sql);
                $estudiantes_result->bindParam(1, $this->username);
                $estudiantes_result->execute();

                $row_estudiantes = $estudiantes_result->fetchAll(PDO::FETCH_ASSOC);
                $this->id = $row_estudiantes[0]['usuarios_id'];
                $this->username = $row_estudiantes[0]['usuarios_nombre'];
                $this->password = $row_estudiantes[0]['usuarios_clave'];
                $this->tipo = $row_estudiantes[0]['usuarios_tipo'];

                // Crear un array para almacenar todos los IDs de inscripción
                $inscripciones_ids = array();
                foreach ($row_estudiantes as $estudiante) {
                    if (!empty($estudiante['inscripciones_id'])) {
                        $inscripciones_ids[] = $estudiante['inscripciones_id'];
                    }
                }
                // Asignar el array de IDs o null si no hay inscripciones
                $this->inscripciones_id = !empty($inscripciones_ids) ? $inscripciones_ids : null;
                
                return true;
            }
            else {
                $this->id = $row['usuarios_id'];
                $this->username = $row['usuarios_nombre'];
                $this->password = $row['usuarios_clave'];
                $this->tipo = $row['usuarios_tipo'];
                $this->inscripciones_id = $row['inscripciones_id'];
                $this->docentes_id = $row['docentes_id'];
                return true;
            }
        }

        return false;
    }

    // Crear usuario (registro)
    public function create_user_model() {
        $sql = 'INSERT INTO '.$this->table.' SET usuarios_nombre = :username, usuarios_clave = :password, usuarios_tipo = :tipo, inscripciones_id = :inscripciones_id, docentes_id = :docentes_id';
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
        $result->bindParam(':inscripciones_id', $this->inscripciones_id);
        $result->bindParam(':docentes_id', $this->docentes_id);

        if($result->execute()) {
            return true;
        }

        return false;
    }

    public function get_all_users_model() {
        $sql = 'SELECT usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, inscripciones_id, docentes_id FROM ' . $this->table;
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result;
    }

    public function get_user_by_id_model($id) {
        $sql = 'SELECT usuarios_id, usuarios_nombre, usuarios_clave, usuarios_tipo, inscripciones_id, docentes_id FROM ' . $this->table . ' WHERE usuarios_id = ? LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $result->bindParam(1, $id);
        $result->execute();
        return $result;
    }

    public function update_username_model($user_id, $new_username) {
        $sql = 'UPDATE ' . $this->table . ' SET usuarios_nombre = :username WHERE usuarios_id = :id';
        $result = $this->db->prepare($sql);
        
        $this->username = htmlspecialchars(strip_tags($new_username));
        
        $result->bindParam(':username', $this->username);
        $result->bindParam(':id', $user_id);
        
        return $result->execute();
    }

    public function update_password_model($user_id, $new_password) {
        $sql = 'UPDATE ' . $this->table . ' SET usuarios_clave = :password WHERE usuarios_id = :id';
        $result = $this->db->prepare($sql);
        
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        $result->bindParam(':password', $hashed_password);
        $result->bindParam(':id', $user_id);
        
        return $result->execute();
    }

    public function verify_current_password($user_id, $current_password) {
        $sql = 'SELECT usuarios_clave FROM ' . $this->table . ' WHERE usuarios_id = :id LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $result->bindParam(':id', $user_id);
        $result->execute();
        
        if($result->rowCount() == 1) {
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return password_verify($current_password, $row['usuarios_clave']);
        }
    
        return false;
    }

    


}