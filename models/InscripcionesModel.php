<?php
class InscripcionesModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }
    public function search_inscripciones_model($searchTerm) {
        $sql = 'SELECT * FROM inscripciones
                INNER JOIN estudiantes ON inscripciones.estudiantes_id = estudiantes.estudiantes_id
                INNER JOIN cursos ON inscripciones.cursos_id = cursos.cursos_id
                INNER JOIN cursos_sedes ON cursos.cursos_id = cursos_sedes.cursos_id
                INNER JOIN sedes ON cursos_sedes.sedes_id = sedes.sedes_id';
        if (!empty($searchTerm)) {
            $sql .= " WHERE inscripciones.inscripciones_codigo LIKE :search 
                      OR estudiantes.estudiantes_nombre LIKE :search
                      OR estudiantes.estudiantes_apellido LIKE :search
                      OR estudiantes.estudiantes_dni LIKE :search";
        }
        $result = $this->db->prepare($sql);

        if (!empty($searchTerm)) {
            $searchTerm = "%" . $searchTerm . "%";
            $result->bindParam(':search', $searchTerm);
        }
        $result->execute();
        return $result;
    }


}