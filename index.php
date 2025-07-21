<?php
/*header("Access-Control-Allow-Origin: *"); // http://localhost:5173
//header("Access-Control-Allow-Origin: https://citicpy.com"); // http://localhost:5173
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
//header("Access-Control-Allow-Credentials: true");*/


$allowedOrigins = [
    'https://www.citicpy.com',
    'http://localhost:5173',
    'https://citicpy.com',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: https://www.citicpy.com"); // default
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Manejar petición OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// Incluir archivos necesarios
require_once 'config/Database.php';
require_once 'models/UserModel.php';
require_once 'models/CalificacionesModel.php';
require_once 'models/InscripcionesModel.php';
require_once 'models/MaterialesModel.php';
require_once 'models/PagosModel.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/CalificacionesController.php';
require_once 'controllers/InscripcionesController.php';
require_once 'controllers/MaterialesController.php';
require_once 'controllers/PagosController.php';

// Conectar a la base de datos
$database = new Database();
$db = $database->connect();

// Crear instancia del controlador de autenticación
$authController = new AuthController($db);
$userController = new UserController($db);
$inscripcionesController = new InscripcionesController($db);
$calificacionesController = new CalificacionesController($db);
$materialesController = new MaterialesController($db);
$pagosController = new PagosController($db);

// Obtener el método de la solicitud
/*$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$base_path = '/api-portal/';
$request = str_replace($base_path, '', $request);*/

// Determinar la ruta basada en parámetros GET
$action = $_GET['action'] ?? '';

// Enrutamiento básico
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($action === 'profile') {
            $userController->get_user_by_id();
        } elseif ($action === 'users') {
            $userController->get_users();
        } elseif ($action === 'inscripciones') {
            $inscripcionesController->search_inscripciones();
        }
        elseif ($action === 'calificaciones') {
            $calificacionesController->get_calificaciones_by_user();
        } elseif ($action === 'materiales') {
            $materialesController->get_materiales_by_user();
        } elseif ($action === 'cursos') {
            $materialesController->get_cursos();
        } elseif ($action === 'pagos') {
            $pagosController->get_pagos_by_user();
        } elseif ($action === 'verify_token') {
            $authController->verifyToken();
        } elseif ($action === 'admin_endpoint') {
            // Validar token y rol de admin
            $authData = AuthController::validateToken($db, 'admin');
            $adminController->adminAction();
        } elseif ($action === 'logout') {
            $authController->logout();
        }
        
        else {
            http_response_code(404);
            echo json_encode(array("message" => "Endpoint GET no encontrado."));
        }
        break;
    case 'POST':
        if ($action === 'register') {
            $authController->register();
        } elseif ($action === 'login') {
            $authController->login();
        } elseif ($action === 'create_calificaciones') {
            $calificacionesController->create_calificaciones();
        } elseif ($action === 'create_materiales') {
            $materialesController->create_materiales_controller();
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Endpoint POST no encontrado."));
        }
        break;
    case 'PUT':
        if ($action === 'update_calificaciones') {
            $calificacionesController->update_calificaciones();
        } elseif ($action === 'update_materiales') {
            $materialesController->update_materiales_controller();
        } elseif ($action === 'change_username') {
            $authController->changeUsername();
        } elseif ($action === 'change_password') {
            $authController->changePassword();
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Endpoint PUT no encontrado."));
        }
        break;
    case 'DELETE':
        if ($action === 'delete_calificaciones') {
            $calificacionesController->delete_calificaciones();
        } elseif ($action === 'delete_materiales') {
            $materialesController->delete_materiales();
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Endpoint DELETE no encontrado."));
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido."));
        break;
}