<?php
class InscripcionesController {
    private $inscripciones;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->inscripciones = new InscripcionesModel($db);
    }

    public function search_inscripciones() {
        // Obtener el término de búsqueda del parámetro GET
        $searchTerm = $_GET['search'] ?? '';

        // Pasar el término de búsqueda al modelo
        $inscripciones = $this->inscripciones->search_inscripciones_model($searchTerm);

        if($inscripciones->rowCount() > 0) {
            $inscripciones_arr = array();
            $inscripciones_arr['data'] = array();

            while($row = $inscripciones->fetch(PDO::FETCH_ASSOC)) {
                $inscripciones_item = array(
                    "inscripciones_id" => $row['inscripciones_id'],
                    "estudiantes_dni" => $row['estudiantes_dni'],
                    "estudiantes_nombre" => $row['estudiantes_nombre'],
                    "estudiantes_apellido" => $row['estudiantes_apellido'],
                    "cursos_nombre" => $row['cursos_nombre'],
                    "sedes_ciudad" => $row['sedes_ciudad']
                );
                array_push($inscripciones_arr['data'], $inscripciones_item);
            }

            http_response_code(200);
            echo json_encode($inscripciones_arr);
        } else {
            http_response_code(200);
            echo json_encode(array("data" => []));
        }
    }

}