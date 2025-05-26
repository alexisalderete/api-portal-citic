<?php
class MaterialesController {
    private $materiales;
    private $db;
 
    public function __construct($db) {
        $this->db = $db;
        $this->materiales = new MaterialesModel($db);
    }

    public function create_materiales_controller() {
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar que los datos requeridos estén presentes
        if (!isset($data['materiales_nombre'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_nombre es requerido."));
            return;
        }

        if (!isset($data['materiales_descripcion'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_descripcion es requerido."));
            return;
        }

        if (!isset($data['materiales_url'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_url es requerido."));
            return;
        }

        // Procesar la creación
        $created = $this->materiales->create_materiales_model(
            $data['materiales_nombre'],
            $data['materiales_descripcion'],
            $data['materiales_url']
        );

        if ($created) {
            http_response_code(201);
            echo json_encode(array(
                "message" => "Material creado exitosamente.",
                "data" => array(
                    "materiales_nombre" => $data['materiales_nombre'],
                    "materiales_descripcion" => $data['materiales_descripcion'],
                    "materiales_url" => $data['materiales_url']
                )
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error al crear el material."));
        }
    }

    public function create_materiales_cursos_controller(){
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar que los datos requeridos estén presentes
        if (!isset($data['materiales_id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_id es requerido."));
            return;
        }

        if (!isset($data['cursos_nombre'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo cursos_nombre es requerido."));
            return;
        }

        if (!is_numeric($data['materiales_id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "materiales_id debe ser numérico."));
            return;
        }

        $cursos = $this->materiales->get_cursos_by_nombre_model($data['cursos_nombre']);

        if($cursos->rowCount() == 0) {
            http_response_code(400);
            echo json_encode(array("message" => "El curso no existe."));
            return;
        }

        #en caso de que el haya mas cursos con el mismo nombre, haz un foreach
        $cursos = $cursos->fetchAll(PDO::FETCH_ASSOC);

        foreach($cursos as $curso) {
            $data['cursos_id'] = $curso['cursos_id'];

            $created = $this->materiales->create_materiales_cursos_model(
                $data['materiales_id'],
                $data['cursos_id']
            );

            if ($created) {
                http_response_code(201);
                echo json_encode(array(
                    "message" => "Material agregado exitosamente.",
                    "data" => array(
                        "materiales_id" => $data['materiales_id'],
                        "cursos_id" => $data['cursos_id']
                    )
                ));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error al agregar el material."));
            }
        }

    }

    public function update_materiales() {
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"), true);

        // Validar datos
        if(empty($data['id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El ID del material es requerido."));
            return;
        }

        if(!isset($data['materiales_nombre'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_nombre es requerido."));
            return;
        }

        if(!isset($data['materiales_descripcion'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_descripcion es requerido."));
            return;
        }

        if(!isset($data['materiales_url'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El campo materiales_url es requerido."));
            return;
        }

        $this->materiales->materiales_nombre = $data['materiales_nombre'];
        $this->materiales->materiales_descripcion = $data['materiales_descripcion'];
        $this->materiales->materiales_url = $data['materiales_url'];

        if($this->materiales->update_materiales_model($data['id'])) {
            http_response_code(200);
            echo json_encode(array("message" => "Material actualizado exitosamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar el material."));
        }
    }

    public function delete_materiales() {
        // Obtener datos del POST
        $data = json_decode(file_get_contents("php://input"), true);

        if(empty($data['id'])) {
            http_response_code(400);
            echo json_encode(array("message" => "El ID del material es requerido."));
            return;
        }

        if($this->materiales->delete_materiales_model($data['id'])) {
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
        'sortBy' => $_GET['sortBy'] ?? 'estudiantes.estudiantes_nombre',
        'sortDir' => $_GET['sortDir'] ?? 'ASC'
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