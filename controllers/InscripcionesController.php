<?php
class InscripcionesController {
    private $inscripciones;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->inscripciones = new InscripcionesModel($db);
    }

    public function search_inscripciones() {
        $authData = AuthController::validateToken($this->db);
        $searchTerm = $_GET['search'] ?? '';
        
        try {

            if ($authData->tipo === 'admin') {
                $inscripciones = $this->inscripciones->search_inscripciones_model($searchTerm);
                
                $inscripciones_arr = array();
                $inscripciones_arr['data'] = array();
            
                $count = 0;
                while($row = $inscripciones->fetch(PDO::FETCH_ASSOC)) {
                    $count++;
                    $inscripciones_item = array(
                        "inscripciones_id" => $row['inscripciones_id'],
                        "estudiantes_id" => $row['estudiantes_id'],
                        "estudiantes_dni" => $row['estudiantes_dni'],
                        "estudiantes_nombre" => $row['estudiantes_nombre'],
                        "estudiantes_apellido" => $row['estudiantes_apellido'],
                        "cursos_id" => $row['cursos_id'],
                        "cursos_nombre" => $row['cursos_nombre'],
                        "sedes_ciudad" => $row['sedes_ciudad']
                    );
                    array_push($inscripciones_arr['data'], $inscripciones_item);
                }
            
                if ($count === 0) {
                    $inscripciones_arr['message'] = "No se encontraron resultados";
                }
                
                http_response_code(200);
                echo json_encode($inscripciones_arr, JSON_UNESCAPED_UNICODE);

            } elseif ($authData->tipo === 'profesor') {
                $inscripciones = $this->inscripciones->search_inscripciones_by_docente_model($searchTerm, $authData->docentes_id);
                
                $inscripciones_arr = array();
                $inscripciones_arr['data'] = array();
                
                $count = 0;
                while($row = $inscripciones->fetch(PDO::FETCH_ASSOC)) {
                    $count++;
                    $inscripciones_item = array(
                        "inscripciones_id" => $row['inscripciones_id'],
                        "estudiantes_id" => $row['estudiantes_id'],
                        "estudiantes_dni" => $row['estudiantes_dni'],
                        "estudiantes_nombre" => $row['estudiantes_nombre'],
                        "estudiantes_apellido" => $row['estudiantes_apellido'],
                        "cursos_id" => $row['cursos_id'],
                        "cursos_nombre" => $row['cursos_nombre'],
                        "sedes_ciudad" => $row['sedes_ciudad']
                    );
                    array_push($inscripciones_arr['data'], $inscripciones_item);
                }
                
                if ($count === 0) {
                    $inscripciones_arr['message'] = "No se encontraron resultados";
                }
                
                http_response_code(200);
                echo json_encode($inscripciones_arr, JSON_UNESCAPED_UNICODE);
            }
            else {
                http_response_code(403);
                echo json_encode(array("message" => "No tienes permiso para buscar a estudiantes inscritos."));
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array(
                "error" => "Error en el servidor",
                "message" => $e->getMessage(),
                "data" => [],
                "searchTerm" => $searchTerm // Para debugging
            ));
        }
    }

}