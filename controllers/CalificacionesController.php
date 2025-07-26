<?php
class CalificacionesController {
    private $calificaciones;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->calificaciones = new CalificacionesModel($db);
    }

    public function create_calificaciones() {
        $authData = AuthController::validateToken($this->db);
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar que los datos requeridos estén presentes
        if (empty($data['inscripciones_id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo inscripciones_id es requerido."));
            return;
        }

        if (!isset($data['calificaciones_primer'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo calificaciones_primer es requerido."));
            return;
        }

        // Validar tipos
        if (!is_numeric($data['inscripciones_id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "inscripciones_id debe ser numérico."));
            return;
        }

        //validar si ya existe calificacion para esa inscripcion
        $calificacion = $this->calificaciones->get_calificaciones_by_inscripcion_model($data['inscripciones_id']);
        if ($calificacion->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(array("message" => "Ya existe una calificación de este estudiante en esta categoría."));
            return;
        }

        // Si es profesor, verificar que la inscripción pertenezca a uno de sus cursos
        if ($authData->tipo === 'profesor') {
            $inscripcionModel = new InscripcionesModel($this->db);
            $curso = $inscripcionModel->get_curso_docente_by_inscripcion($data['inscripciones_id']);
            
            if (!$curso || $curso['docentes_id'] != $authData->docentes_id) {
                http_response_code(403);
                echo json_encode(array("message" => "No tienes permiso para agregar calificaciones en este curso."));
                return;
            }
        }

        // El segundo semestre puede ser null
        $calificaciones_segundo = isset($data['calificaciones_segundo']) ? $data['calificaciones_segundo'] : null;

        // Procesar la creación
        $created = $this->calificaciones->create_calificaciones_model(
            (int)$data['inscripciones_id'],
            $data['calificaciones_primer'],
            $calificaciones_segundo
        );

        if ($created) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "Calificación creada exitosamente.",
                "data" => array(
                    "inscripciones_id" => $data['inscripciones_id'],
                    "calificaciones_primer" => $data['calificaciones_primer'],
                    "calificaciones_segundo" => $calificaciones_segundo
                )
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear la calificación."));
        }
    }

    public function update_calificaciones() {
        $authData = AuthController::validateToken($this->db);
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar datos
        if(empty($data['id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El ID de la calificación es requerido."));
            return;
        }

        if(!isset($data['calificaciones_primer'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo calificaciones_primer es requerido."));
            return;
        }
        $calificacion = $this->calificaciones->get_calificaciones_by_id_model($data['id']);
        if ($calificacion->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Calificación no encontrada."));
            return;
        }

        if(!isset($data['inscripciones_id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "inscripciones_id es requerido."));
            return;
        }

        // Si es profesor, verificar que la calificación pertenezca a uno de sus cursos
        if ($authData->tipo === 'profesor') {
            $calificacionData = $calificacion->fetch(PDO::FETCH_ASSOC);
            $inscripcionModel = new InscripcionesModel($this->db);
            $curso = $inscripcionModel->get_curso_docente_by_inscripcion($calificacionData['inscripciones_id']);
            
            if (!$curso || $curso['docentes_id'] != $authData->docentes_id) {
                http_response_code(403);
                echo json_encode(array("message" => "No tienes permiso para modificar esta calificación."));
                return;
            }
            if($calificacionData['inscripciones_id'] != $data['inscripciones_id']) {
                http_response_code(403);
                echo json_encode(array("message" => "No tienes permiso para modificar esta calificación."));
                return;
            }
        }

        // Asegurarse de que calificaciones_segundo sea null si viene vacío
        $calificaciones_segundo = isset($data['calificaciones_segundo']) && $data['calificaciones_segundo'] !== '' 
        ? $data['calificaciones_segundo'] 
        : null;

        $this->calificaciones->inscripciones_id = $data['inscripciones_id'];
        $this->calificaciones->calificaciones_primer = $data['calificaciones_primer'];
        $this->calificaciones->calificaciones_segundo = $calificaciones_segundo;

        if($this->calificaciones->update_calificaciones_model($data['id'])) {
            http_response_code(200);
            echo json_encode(array("message" => "Calificación actualizada exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar la calificación."));
        }
    }

    public function delete_calificaciones() {
        // Obtener datos del POST
        $authData = AuthController::validateToken($this->db);
        $data = json_decode(file_get_contents("php://input"), true);

        if($authData->tipo !== 'admin') {
            http_response_code(403);
            echo json_encode(array("message" => "No tienes permiso para eliminar esta calificación."));
            return;
        } else {
            if(empty($data['id'])) {
                http_response_code(400);
                echo json_encode(array("message" => "El ID de la calificación es requerido."));
                return;
            }

            $calificacion = $this->calificaciones->get_calificaciones_by_id_model($data['id']);
            if ($calificacion->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(array("message" => "Calificación no encontrada."));
                return;
            }
    
            if($this->calificaciones->delete_calificaciones_model($data['id'])) {
                http_response_code(200);
                echo json_encode(array("message" => "Calificación eliminada exitosamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo eliminar la calificación."));
            }
        }
    }

    public function get_all_calificaciones() {
        // Obtener todos los parámetros de la solicitud
        $params = [
            'page' => $_GET['page'] ?? 1,
            'perPage' => $_GET['perPage'] ?? 5,
            'search' => $_GET['search'] ?? '',
            'sortBy' => $_GET['sortBy'] ?? 'estudiantes.estudiantes_nombre',
            'sortDir' => $_GET['sortDir'] ?? 'ASC'
        ];

        // Pasar el término de búsqueda al modelo
        $calificaciones = $this->calificaciones->get_all_calificaciones_model($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $calificaciones['data'],
            'total' => $calificaciones['total']
        ]);
    }


    public function get_calificaciones_by_user() {
        // Validar token y obtener datos del usuario
        $authData = AuthController::validateToken($this->db);
        
        // Si es estudiante, obtener sus calificaciones
        if ($authData->tipo === 'estudiante') {
            if (empty($authData->inscripciones_id)) {
                http_response_code(404);
                echo json_encode(["message" => "Estudiante no tiene inscripción asociada"]);
                return;
            }
            
            // $calificaciones = $this->calificaciones->get_calificaciones_by_inscripcion_model($authData->inscripciones_id);

            // if ($calificaciones->rowCount() === 0) {
            //     http_response_code(200);
            //     echo json_encode([
            //         "success" => true,
            //         "data" => []
            //     ]);
            //     return;
            // }
            // Formatear respuesta para estudiante
            $calificaciones_arr = array();
            $calificaciones_arr['data'] = array();

            // Si es un array de inscripciones (múltiples cursos)
            if (is_array($authData->inscripciones_id)) {
                foreach ($authData->inscripciones_id as $inscripcion_id) {
                    $calificaciones = $this->calificaciones->get_calificaciones_by_inscripcion_model($inscripcion_id);
                    
                    while($row = $calificaciones->fetch(PDO::FETCH_ASSOC)) {
                        $calificaciones_item = array(
                            "calificaciones_id" => $row['calificaciones_id'],
                            "cursos_nombre" => $row['cursos_nombre'],
                            "sedes_ciudad" => $row['sedes_ciudad'],
                            "calificaciones_primer" => $row['calificaciones_primer'],
                            "calificaciones_segundo" => $row['calificaciones_segundo']
                        );
                        array_push($calificaciones_arr['data'], $calificaciones_item);
                    }
                }
            } 
            // Si es solo una inscripción (valor único)
            else {
                $calificaciones = $this->calificaciones->get_calificaciones_by_inscripcion_model($authData->inscripciones_id);
                
                while($row = $calificaciones->fetch(PDO::FETCH_ASSOC)) {
                    $calificaciones_item = array(
                        "calificaciones_id" => $row['calificaciones_id'],
                        "cursos_nombre" => $row['cursos_nombre'],
                        "sedes_ciudad" => $row['sedes_ciudad'],
                        "calificaciones_primer" => $row['calificaciones_primer'],
                        "calificaciones_segundo" => $row['calificaciones_segundo']
                    );
                    array_push($calificaciones_arr['data'], $calificaciones_item);
                }
            }

            http_response_code(200);
            echo json_encode($calificaciones_arr);
            return;
            
        }
        // Si es profesor, obtener todas las calificaciones

        elseif ($authData->tipo === 'profesor') {
            $this->calificaciones->docentes_id = $authData->docentes_id;
            $calificaciones = $this->calificaciones->get_calificaciones_by_docente_model();

            $params = [
                'page' => $_GET['page'] ?? 1,
                'perPage' => $_GET['perPage'] ?? 5,
                'search' => $_GET['search'] ?? '',
                'sortBy' => $_GET['sortBy'] ?? 'estudiantes.estudiantes_nombre',
                'sortDir' => $_GET['sortDir'] ?? 'ASC'
            ];

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $calificaciones['data'],
                'total' => $calificaciones['total']
            ]);
        }
        // Si es admin, obtener todas las calificaciones
        else {
            $params = [
                'page' => $_GET['page'] ?? 1,
                'perPage' => $_GET['perPage'] ?? 5,
                'search' => $_GET['search'] ?? '',
                'sortBy' => $_GET['sortBy'] ?? 'estudiantes.estudiantes_nombre',
                'sortDir' => $_GET['sortDir'] ?? 'ASC'
            ];
            $calificaciones = $this->calificaciones->get_all_calificaciones_model($params);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $calificaciones['data'],
                'total' => $calificaciones['total']
            ]);
        }
    
        // Formatear respuesta
        // if(isset($calificaciones['data'])) {
        //     // Para consultas paginadas (admin/profesor)
        //     http_response_code(200);
        //     echo json_encode($calificaciones);
        // } 
        // elseif($calificaciones->rowCount() > 0) {
        //     // Para consultas de estudiante
        //     $calificaciones_arr = array();
        //     $calificaciones_arr['data'] = array();
    
        //     while($row = $calificaciones->fetch(PDO::FETCH_ASSOC)) {
        //         $calificaciones_item = array(
        //             "id" => $row['calificaciones_id'],
        //             "curso" => $row['cursos_nombre'],
        //             "calificaciones_primer" => $row['calificaciones_primer'],
        //             "calificaciones_segundo" => $row['calificaciones_segundo']
        //         );
        //         array_push($calificaciones_arr['data'], $calificaciones_item);
        //     }
    
        //     http_response_code(200);
        //     echo json_encode($calificaciones_arr);
        // } else {
        //     http_response_code(404);
        //     echo json_encode(array("message" => "No se encontraron calificaciones."));
        // }
    }
    
    public function get_calificaciones_by_id() {
        // Obtener el término de búsqueda del parámetro GET
        $id = $_GET['id'] ?? '';    

        // Pasar el término de búsqueda al modelo
        $calificaciones = $this->calificaciones->get_calificaciones_by_id_model($id);

        if($calificaciones->rowCount() > 0) {
            $calificaciones_arr = array();
            $calificaciones_arr['data'] = array();

            while($row = $calificaciones->fetch(PDO::FETCH_ASSOC)) {
                $calificaciones_item = array(
                    "id" => $row['calificaciones_id'],
                    "inscripciones_id" => $row['inscripciones_id'],
                    "calificaciones_primer" => $row['calificaciones_primer'],
                    "calificaciones_segundo" => $row['calificaciones_segundo']
                );
                array_push($calificaciones_arr['data'], $calificaciones_item);
            }

            http_response_code(200);
            echo json_encode($calificaciones_arr);
        }
    }





}