<?php
class CalificacionesController {
    private $calificaciones;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->calificaciones = new CalificacionesModel($db);
    }

    public function create_calificaciones() {
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

        $this->calificaciones->inscripciones_id = $data['inscripciones_id'];
        $this->calificaciones->calificaciones_primer = $data['calificaciones_primer'];
        $this->calificaciones->calificaciones_segundo = $data['calificaciones_segundo'] ?? null;

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
        $data = json_decode(file_get_contents("php://input"), true);

        if(empty($data['id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El ID de la calificación es requerido."));
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