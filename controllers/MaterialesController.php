<?php
class MaterialesController {
    private $materiales;
    private $db;
 
    public function __construct($db) {
        $this->db = $db;
        $this->materiales = new MaterialesModel($db);
    }

// En create_materiales_controller
    public function create_materiales_controller() {
        $data = json_decode(file_get_contents("php://input"), true);

        // Validaciones requeridas
        $required_fields = ['materiales_nombre', 'materiales_descripcion', 'materiales_url', 'cursos_nombre'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                echo json_encode(array("message" => "El campo $field es requerido."));
                return;
            }
        }

        // Tipo de material (por defecto 'material')
        $materiales_tipo = isset($data['materiales_tipo']) && in_array($data['materiales_tipo'], ['material', 'tarea']) 
            ? $data['materiales_tipo'] 
            : 'material';

        // Crear el material
        $created = $this->materiales->create_materiales_model(
            $data['materiales_nombre'],
            $data['materiales_descripcion'],
            $data['materiales_url'],
            $materiales_tipo
        );

        if (!$created) {
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear el material."));
            return;
        }

        // Obtener ID del material creado
        $material_id = $this->db->lastInsertId();

        // Buscar y asociar cursos
        $cursos = $this->materiales->get_cursos_by_nombre_model($data['cursos_nombre']);
        if($cursos->rowCount() == 0) {
            http_response_code(400);
            echo json_encode(array("message" => "El curso no existe."));
            return;
        }

        $cursos = $cursos->fetchAll(PDO::FETCH_ASSOC);
        foreach($cursos as $curso) {
            $this->materiales->create_materiales_cursos_model($material_id, $curso['cursos_id']);
        }

        http_response_code(201);
        echo json_encode(array(
            "message" => "Material creado exitosamente.",
            "data" => array(
                "materiales_id" => $material_id,
                "materiales_nombre" => $data['materiales_nombre'],
                "materiales_descripcion" => $data['materiales_descripcion'],
                "materiales_url" => $data['materiales_url'],
                "materiales_tipo" => $materiales_tipo,
                "cursos_nombre" => $data['cursos_nombre']
            )
        ));
    }

    public function get_cursos() {
        $cursos = $this->materiales->get_cursos_model();
        if($cursos->rowCount() > 0) {
            $cursos_arr = array();
            $cursos_arr['data'] = array();

            while($row = $cursos->fetch(PDO::FETCH_ASSOC)) {
                $curso_item = array(
                    "cursos_id" => $row['cursos_id'],
                    "cursos_nombre" => $row['cursos_nombre']
                );
                array_push($cursos_arr['data'], $curso_item);
            }

            http_response_code(200);
            echo json_encode($cursos_arr);
        }
    }

    public function update_materiales_controller() {
        $this->db->beginTransaction();
        $data = json_decode(file_get_contents("php://input"), true);
    
        // Validaciones requeridas
        $required_fields = ['materiales_id', 'materiales_nombre', 'materiales_descripcion', 'materiales_url', 'cursos_nombre'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                echo json_encode(array("message" => "El campo $field es requerido."));
                return;
            }
        }
    
        // Tipo de material (mantener el existente si no se proporciona)
        $materiales_tipo = isset($data['materiales_tipo']) && in_array($data['materiales_tipo'], ['material', 'tarea']) 
            ? $data['materiales_tipo'] 
            : null;
    
        // Asignar propiedades
        $this->materiales->materiales_nombre = $data['materiales_nombre'];
        $this->materiales->materiales_descripcion = $data['materiales_descripcion'];
        $this->materiales->materiales_url = $data['materiales_url'];
        if ($materiales_tipo !== null) {
            $this->materiales->materiales_tipo = $materiales_tipo;
        }
    
        // Actualizar material
        if(!$this->materiales->update_materiales_model($data['materiales_id'])) {
            $this->db->rollBack();
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar el material."));
            return;
        }
    
        // Buscar curso por nombre
        $cursos = $this->materiales->get_cursos_by_nombre_model($data['cursos_nombre']);
        if($cursos->rowCount() == 0) {
            $this->db->rollBack();
            http_response_code(400);
            echo json_encode(array("message" => "El curso no existe."));
            return;
        }
    
        // Actualizar relación con cursos (eliminar antiguas y crear nuevas)
        $this->materiales->delete_materiales_cursos_model($data['materiales_id']);
        $cursos = $cursos->fetchAll(PDO::FETCH_ASSOC);
        foreach($cursos as $curso) {
            $this->materiales->create_materiales_cursos_model($data['materiales_id'], $curso['cursos_id']);
        }
    
        $this->db->commit();
        http_response_code(200);
        echo json_encode(array("message" => "Material actualizado exitosamente."));
    }

    public function delete_materiales() {
        // Obtener datos del POST
        $materiales_id = $_GET['id'] ?? '';

        if(empty($materiales_id)) {
            http_response_code(400);
            echo json_encode(array("message" => "El ID del material es requerido."));
            return;
        }

        $deleted = $this->materiales->delete_materiales_model($materiales_id);

        if($deleted->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(array("message" => "Material eliminado exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar el material."));
        }
    }


    public function get_all_materiales() {
         // Obtener todos los parámetros de la solicitud
        $params = [
            'page' => $_GET['page'] ?? 1,
            'perPage' => $_GET['perPage'] ?? 5,
            'search' => $_GET['search'] ?? '',
            'sortBy' => $_GET['sortBy'] ?? 'materiales.materiales_created_at',
            'sortDir' => $_GET['sortDir'] ?? 'DESC'
        ];

        // Pasar el término de búsqueda al modelo
        $materiales = $this->materiales->get_all_materiales_model($params);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $materiales['data'],
            'total' => $materiales['total']
        ]);
    }


    public function get_materiales_by_estudiante() {
        // Validar token
        $authData = AuthController::validateToken($this->db);
        
        // Solo para estudiantes
        if ($authData->tipo !== 'estudiante') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso no autorizado"]);
            return;
        }
        
        if (empty($authData->inscripciones_id)) {
            http_response_code(404);
            echo json_encode(["message" => "Estudiante no tiene inscripción asociada"]);
            return;
        }
        
        // Obtener parámetros opcionales
        $tipo = $_GET['tipo'] ?? null; // 'material' o 'tarea'
        
        // Obtener el curso del estudiante
        $inscripcionModel = new InscripcionesModel($this->db);
        $curso = $inscripcionModel->get_curso_by_inscripcion($authData->inscripciones_id);
        
        if (!$curso) {
            http_response_code(404);
            echo json_encode(["message" => "No se encontró el curso del estudiante"]);
            return;
        }
        
        // Obtener materiales del curso (filtrados por tipo si se especifica)
        $materiales = $this->materiales->get_materiales_by_curso_model($curso['cursos_id'], $tipo);
        
        // Formatear respuesta
        $materiales_arr = array();
        $materiales_arr['data'] = array();
    
        while($row = $materiales->fetch(PDO::FETCH_ASSOC)) {
            $materiales_item = array(
                "materiales_id" => $row['materiales_id'],
                "materiales_nombre" => $row['materiales_nombre'],
                "materiales_descripcion" => $row['materiales_descripcion'],
                "materiales_url" => $row['materiales_url'],
                "materiales_tipo" => $row['materiales_tipo'],
                "cursos_nombre" => $row['cursos_nombre']
            );
            array_push($materiales_arr['data'], $materiales_item);
        }
    
        http_response_code(200);
        echo json_encode($materiales_arr);
    }

    public function get_materiales_by_id() {
        // Obtener el término de búsqueda del parámetro GET
        $id = $_GET['id'] ?? '';

        // Pasar el término de búsqueda al modelo
        $materiales = $this->materiales->get_materiales_by_id_model($id);

        if($materiales->rowCount() > 0) {
            $materiales_arr = array();
            $materiales_arr['data'] = array();

            while($row = $materiales->fetch(PDO::FETCH_ASSOC)) {
                $materiales_item = array(
                    "id" => $row['materiales_id'],
                    "cursos_id" => $row['cursos_id'],
                    "materiales_nombre" => $row['materiales_nombre'],
                    "materiales_descripcion" => $row['materiales_descripcion'],
                    "materiales_url" => $row['materiales_url']
                );
                array_push($materiales_arr['data'], $materiales_item);
            }

            http_response_code(200);
            echo json_encode($materiales_arr);
        }
    }





}