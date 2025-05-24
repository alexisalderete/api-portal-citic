<?php

require __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;


class AuthController {
    private $user;
    private $db;
    private $secretKey = "clave_secreta_super_segura"; //clave segura

    public function __construct($db) {
        $this->db = $db;
        $this->user = new UserModel($db);
    }

    public function register() {
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"));

        // Validar datos
        if(
            !empty($data->username) &&
            !empty($data->password)
        ) {
            $this->user->username = $data->username;
            $this->user->password = $data->password;
            $this->user->tipo = $data->tipo;

            // Verificar si el usuario ya existe
            if($this->user->login()) {
                http_response_code(400);
                echo json_encode(array("message" => "El usuario ya está en uso."));
                return;
            }

            // Crear usuario
            if($this->user->create_user_model()) {
                http_response_code(201);
                echo json_encode(array("message" => "Usuario registrado con éxito."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo registrar el usuario."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos."));
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if(!empty($data->username) && !empty($data->password)) {
            $this->user->username = $data->username;
            $username_exists = $this->user->login();

            if($username_exists && password_verify($data->password, $this->user->password)) {


                // Configurar el payload del token
                /*$payload = [
                    "iss" => "localhost", // Emisor localhost citicpy.com
                    "aud" => "localhost", // Audiencia localhost citicpy.com
                    "iat" => time(), // Tiempo de emisión
                    "exp" => time() + 3600, // Expira en 1 hora
                    "data" => [
                        "user_id" => $this->user->id,
                        "username" => $this->user->username
                    ]
                ];*/

                $payload = [
                    "iss" => "citicpy.com", // Emisor localhost citicpy.com
                    "aud" => "citicpy.com", // Audiencia localhost citicpy.com
                    "iat" => time(), // Tiempo de emisión
                    "exp" => time() + 3600, // Expira en 1 hora
                    "data" => [
                        "user_id" => $this->user->id,
                        "username" => $this->user->username,
                        "tipo" => $this->user->tipo
                    ]
                ];

                // Generar el token JWT
                $jwt = JWT::encode($payload, $this->secretKey, 'HS256');


                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Login exitoso.",
                    "token" => $jwt,
                    "username" => $this->user->username,
                    "user_id" => $this->user->id,
                    "tipo" => $this->user->tipo
                ));
                return;
            }
        }

        // Respuesta de error
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Login fallido. Credenciales incorrectas."
        ]);
        return;
    }



    public static function validateToken($db) {
        // Método mejorado para leer el token de diferentes formas
        $token = null;
        
        // 1. Intentar desde headers HTTP (funciona en la mayoría de servidores)
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        // 2. Intentar desde headers REDIRECT (alternativa para algunos hosts)
        elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        // 3. Intentar desde getallheaders() si está disponible
        elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        // Log para depuración (revisa los logs de error de PHP)
        error_log("Token recibido: " . ($token ? 'SI' : 'NO'));
        error_log("Headers disponibles: " . print_r($_SERVER, true));
        
        if (!$token) {
            http_response_code(401);
            echo json_encode(["message" => "Token de autorización no proporcionado."]);
            exit;
        }
        
        try {
            $secretKey = "clave_secreta_super_segura";
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            return $decoded->data;
        } catch (Exception $e) {
            error_log("Error validando token: " . $e->getMessage());
            http_response_code(401);
            echo json_encode(["message" => "Token inválido: " . $e->getMessage()]);
            exit;
        }
    }


}