<?php
class UserController {
    private $user;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new UserModel($db);
    }

    public function get_users() {
        $users = $this->user->get_all_users_model();

        if($users->rowCount() > 0) {
            $user_arr = array();
            $user_arr['data'] = array();

            while($row = $users->fetch(PDO::FETCH_ASSOC)) {
                $user_item = array(
                    "id" => $row['usuarios_id'],
                    "username" => $row['usuarios_nombre'],
                    "password" => $row['usuarios_clave']
                );
                array_push($user_arr['data'], $user_item);
            }

            http_response_code(200);
            echo json_encode($user_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No se encontraron usuarios."));
        }
    }



    public function get_user_by_id() {
        try {
            // Validar el token
            $tokenData = AuthController::validateToken($this->db);
            
            // Debug: Registrar que el token fue validado
            error_log("Token validado para user_id: " . $tokenData->user_id);
            
            // Obtener usuario
            $userResult = $this->user->get_user_by_id_model($tokenData->user_id);
            
            if($userResult->rowCount() > 0) {
                $row = $userResult->fetch(PDO::FETCH_ASSOC);
                
                $userData = [
                    "id" => $row['usuarios_id'],
                    "username" => $row['usuarios_nombre'],
                    "name" => $row['usuarios_nombre'],
                    "tipo" => $row['usuarios_tipo']
                    //"email" => $row['usuarios_email'] ?? '',
                    //"createdAt" => $row['fecha_creacion'] ?? ''
                ];

                http_response_code(200);
                echo json_encode($userData);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Usuario no encontrado"]);
            }
        } catch (Exception $e) {
            error_log("Error en get_user_by_id: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Error al obtener perfil: " . $e->getMessage()]);
        }
    }

}