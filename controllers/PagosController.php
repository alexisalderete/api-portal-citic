<?php
class PagosController {
    private $pagos;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->pagos = new PagosModel($db);
    }

    public function get_pagos_by_user() {
        // Validar token y obtener datos del usuario
        $authData = AuthController::validateToken($this->db);
        
        // Si es estudiante, obtener sus pagos
        if ($authData->tipo === 'estudiante') {
            if (empty($authData->inscripciones_id)) {
                http_response_code(404);
                echo json_encode(["message" => "Estudiante no tiene inscripción asociada"]);
                return;
            }

            $pagos_arr = array();
            $pagos_arr['data'] = array();

            // Si es un array de inscripciones (múltiples cursos)
            if (is_array($authData->inscripciones_id)) {
                foreach ($authData->inscripciones_id as $inscripcion_id) {
                    $pago = $this->pagos->get_pagos_by_inscripcion($inscripcion_id);
                    if ($pago) {
                        $pago['inscripciones_id'] = $inscripcion_id; // Agregar ID de inscripción
                        array_push($pagos_arr['data'], $pago);
                    }
                }
            } 
            // Si es solo una inscripción (valor único)
            else {
                $pago = $this->pagos->get_pagos_by_inscripcion($authData->inscripciones_id);
                if ($pago) {
                    $pago['inscripciones_id'] = $authData->inscripciones_id; // Agregar ID de inscripción
                    array_push($pagos_arr['data'], $pago);
                }
            }

            if (empty($pagos_arr['data'])) {
                http_response_code(404);
                echo json_encode(["message" => "No se encontraron pagos para las inscripciones"]);
                return;
            }

            http_response_code(200);
            echo json_encode($pagos_arr);
            return;
            
            // $pagos = $this->pagos->get_pagos_by_inscripcion($authData->inscripciones_id);

            // if (!$pagos) {
            //     http_response_code(404);
            //     echo json_encode(["message" => "No se encontraron pagos para esta inscripción"]);
            //     return;
            // }

            // $pagos_arr = array();
            // $pagos_arr['data'] = array();

            // http_response_code(200);
            // echo json_encode([
            //     "success" => true,
            //     "data" => $pagos
            // ]);
            // return;
        }
        // Si es admin, obtener todos los pagos con paginación
        else {
            http_response_code(403);
            echo json_encode(["message" => "Acceso no autorizado"]);
            return;
            // $params = [
            //     'page' => $_GET['page'] ?? 1,
            //     'perPage' => $_GET['perPage'] ?? 10,
            //     'search' => $_GET['search'] ?? '',
            //     'sortBy' => $_GET['sortBy'] ?? 'estudiante',
            //     'sortDir' => $_GET['sortDir'] ?? 'ASC'
            // ];
            
            // $pagos = $this->pagos->get_all_pagos($params);

            // http_response_code(200);
            // echo json_encode([
            //     'success' => true,
            //     'data' => $pagos['data'],
            //     'total' => $pagos['total'],
            //     'page' => (int)$params['page'],
            //     'perPage' => (int)$params['perPage']
            // ]);
        }
    }
}