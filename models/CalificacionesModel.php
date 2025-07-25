<?php
class CalificacionesModel {
    private $db;
    private $table = 'calificaciones';

    public $id;
    public $inscripciones_id;
    public $calificaciones_primer;
    public $calificaciones_segundo;
    public $docentes_id;

    public function __construct($db) {
        $this->db = $db;
    }


    // Crear calificaciones (registro)
    public function create_calificaciones_model($inscripciones_id, $calificaciones_primer, $calificaciones_segundo = null) {
        $sql = 'INSERT INTO '.$this->table.' 
        SET inscripciones_id = :inscripciones_id, 
        calificaciones_primer = :calificaciones_primer, 
        calificaciones_segundo = :calificaciones_segundo';
        $result = $this->db->prepare($sql);
        
        // Asignar a propiedades de clase
        $this->inscripciones_id = $inscripciones_id;
        $this->calificaciones_primer = $calificaciones_primer;
        $this->calificaciones_segundo = $calificaciones_segundo;
        
        // Limpiar datos (solo si no son null)
        $this->inscripciones_id = $this->inscripciones_id !== null ? htmlspecialchars(strip_tags($this->inscripciones_id)) : null;
        $this->calificaciones_primer = $this->calificaciones_primer !== null ? htmlspecialchars(strip_tags($this->calificaciones_primer)) : null;
        $this->calificaciones_segundo = $this->calificaciones_segundo !== null ? htmlspecialchars(strip_tags($this->calificaciones_segundo)) : null;
        
        // Vincular parámetros
        $result->bindParam(':inscripciones_id', $this->inscripciones_id);
        $result->bindParam(':calificaciones_primer', $this->calificaciones_primer);
        $result->bindParam(':calificaciones_segundo', $this->calificaciones_segundo);
    
        if($result->execute()) {
            return true;
        }
    
        return false;
    }
    public function update_calificaciones_model($id) {
        $sql = 'UPDATE '.$this->table.' 
        SET inscripciones_id = :inscripciones_id,
            calificaciones_primer = :calificaciones_primer,
            calificaciones_segundo = :calificaciones_segundo
        WHERE calificaciones_id = :calificaciones_id';
        $result = $this->db->prepare($sql);
        // Limpiar datos
        $this->inscripciones_id = htmlspecialchars(strip_tags($this->inscripciones_id));
        $this->calificaciones_primer = htmlspecialchars(strip_tags($this->calificaciones_primer));
        $this->calificaciones_segundo = $this->calificaciones_segundo !== null 
        ? htmlspecialchars(strip_tags($this->calificaciones_segundo))
        : null;

        // Vincular parámetros
        $result->bindParam(':inscripciones_id', $this->inscripciones_id);
        $result->bindParam(':calificaciones_primer', $this->calificaciones_primer);
        $result->bindParam(':calificaciones_segundo', $this->calificaciones_segundo);
        $result->bindParam(':calificaciones_id', $id);

        if($result->execute()) {
            return true;
        }
        return false;
    }

    public function delete_calificaciones_model($id) {
        $sql = 'DELETE FROM '.$this->table.' 
        WHERE calificaciones_id = :calificaciones_id';
        $result = $this->db->prepare($sql);
        $result->bindParam(':calificaciones_id', $id);
        if($result->execute()) {
            return true;
        }
        return false;
    }


    public function get_all_calificaciones_model($params = []) {
        // Parámetros de paginación
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['perPage']) ? (int)$params['perPage'] : 5;
        $offset = ($page - 1) * $perPage;
      
        // Parámetros de búsqueda
        $search = isset($params['search']) ? $params['search'] : '';
        
        // Parámetros de ordenamiento
        $sortBy = isset($params['sortBy']) ? $params['sortBy'] : 'estudiantes.estudiantes_nombre';
        $sortDir = isset($params['sortDir']) && strtoupper($params['sortDir']) === 'DESC' ? 'DESC' : 'ASC';
      
        // Consulta base
        $sql = "SELECT 
                  calificaciones.calificaciones_id, 
                  calificaciones.inscripciones_id, 
                  calificaciones.calificaciones_primer, 
                  calificaciones.calificaciones_segundo,
                  estudiantes.estudiantes_nombre,
                  estudiantes.estudiantes_apellido,
                  cursos.cursos_nombre AS cursos_nombre,
                  sedes.sedes_ciudad
                FROM calificaciones
                INNER JOIN inscripciones ON calificaciones.inscripciones_id = inscripciones.inscripciones_id
                INNER JOIN estudiantes ON inscripciones.estudiantes_id = estudiantes.estudiantes_id
                INNER JOIN cursos ON inscripciones.cursos_id = cursos.cursos_id
                INNER JOIN cursos_sedes ON cursos.cursos_id = cursos_sedes.cursos_id
                INNER JOIN sedes ON cursos_sedes.sedes_id = sedes.sedes_id";
      
        // Añadir condiciones de búsqueda si hay término
        if (!empty($search)) {
          $sql .= " WHERE estudiantes.estudiantes_nombre LIKE :search 
                    OR estudiantes.estudiantes_apellido LIKE :search 
                    OR cursos.cursos_nombre LIKE :search
                    OR sedes.sedes_ciudad LIKE :search";
        }
      
        // Añadir ordenamiento
        $sql .= " ORDER BY $sortBy $sortDir";
      
        // Añadir límites para paginación
        $sql .= " LIMIT :offset, :perPage";
      
        $result = $this->db->prepare($sql);
      
        // Vincular parámetros de búsqueda si es necesario
        if (!empty($search)) {
          $searchTerm = "%$search%";
          $result->bindParam(':search', $searchTerm);
        }
      
        // Vincular parámetros de paginación
        $result->bindParam(':offset', $offset, PDO::PARAM_INT);
        $result->bindParam(':perPage', $perPage, PDO::PARAM_INT);
      
        $result->execute();
      
        // Obtener también el conteo total para paginación
        $countSql = "SELECT COUNT(*) as total FROM calificaciones
                     INNER JOIN inscripciones ON calificaciones.inscripciones_id = inscripciones.inscripciones_id
                     INNER JOIN estudiantes ON inscripciones.estudiantes_id = estudiantes.estudiantes_id
                     INNER JOIN cursos ON inscripciones.cursos_id = cursos.cursos_id
                     INNER JOIN cursos_sedes ON cursos.cursos_id = cursos_sedes.cursos_id
                     INNER JOIN sedes ON cursos_sedes.sedes_id = sedes.sedes_id";
      
        if (!empty($search)) {
          $countSql .= " WHERE estudiantes.estudiantes_nombre LIKE :search 
                         OR estudiantes.estudiantes_apellido LIKE :search 
                         OR cursos.cursos_nombre LIKE :search
                         OR sedes.sedes_ciudad LIKE :search";
        }
      
        $countResult = $this->db->prepare($countSql);
        
        if (!empty($search)) {
          $countResult->bindParam(':search', $searchTerm);
        }
      
        $countResult->execute();
        $total = $countResult->fetch(PDO::FETCH_ASSOC)['total'];
      
        return [
          'data' => $result->fetchAll(PDO::FETCH_ASSOC),
          'total' => $total
        ];
    }

    public function get_calificaciones_by_inscripcion_model($inscripciones_id) {
        $sql = 'SELECT c.calificaciones_id, c.inscripciones_id, 
                       c.calificaciones_primer, c.calificaciones_segundo,
                       cursos.cursos_nombre,
                       sedes.sedes_ciudad
                FROM calificaciones c
                INNER JOIN inscripciones i ON c.inscripciones_id = i.inscripciones_id
                INNER JOIN cursos ON i.cursos_id = cursos.cursos_id
                INNER JOIN cursos_sedes ON cursos.cursos_id = cursos_sedes.cursos_id
                INNER JOIN sedes ON cursos_sedes.sedes_id = sedes.sedes_id
                WHERE c.inscripciones_id = :inscripciones_id';
        
        $result = $this->db->prepare($sql);
        $result->bindParam(':inscripciones_id', $inscripciones_id);
        $result->execute();
        return $result;
    }

    public function get_calificaciones_by_docente_model() {
        // Parámetros de paginación
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['perPage'] ?? 5;
        $offset = ($page - 1) * $perPage;
        
        // Parámetros de búsqueda
        $search = $_GET['search'] ?? '';
        
        // Parámetros de ordenamiento
        $sortBy = $_GET['sortBy'] ?? 'estudiantes.estudiantes_nombre';
        $sortDir = isset($_GET['sortDir']) && strtoupper($_GET['sortDir']) === 'DESC' ? 'DESC' : 'ASC';
    
        // Consulta base con JOINs necesarios
        $sql = "SELECT 
                  calificaciones.calificaciones_id, 
                  calificaciones.inscripciones_id, 
                  calificaciones.calificaciones_primer, 
                  calificaciones.calificaciones_segundo,
                  estudiantes.estudiantes_nombre,
                  estudiantes.estudiantes_apellido,
                  cursos.cursos_nombre AS cursos_nombre,
                  sedes.sedes_ciudad
                FROM calificaciones
                INNER JOIN inscripciones ON calificaciones.inscripciones_id = inscripciones.inscripciones_id
                INNER JOIN estudiantes ON inscripciones.estudiantes_id = estudiantes.estudiantes_id
                INNER JOIN cursos ON inscripciones.cursos_id = cursos.cursos_id
                INNER JOIN cursos_sedes ON cursos.cursos_id = cursos_sedes.cursos_id
                INNER JOIN sedes ON cursos_sedes.sedes_id = sedes.sedes_id

                INNER JOIN cursos_docentes on cursos.cursos_id = cursos_docentes.cursos_id

                WHERE cursos_docentes.docentes_id = :docentes_id";
    
        // Añadir condiciones de búsqueda si hay término
        if (!empty($search)) {
            $sql .= " AND (estudiantes.estudiantes_nombre LIKE :search 
                      OR estudiantes.estudiantes_apellido LIKE :search 
                      OR cursos.cursos_nombre LIKE :search
                      OR sedes.sedes_ciudad LIKE :search)";
        }
    
        // Añadir ordenamiento
        $sql .= " ORDER BY $sortBy $sortDir";
    
        // Añadir límites para paginación
        $sql .= " LIMIT :offset, :perPage";
    
        $result = $this->db->prepare($sql);
        $result->bindParam(':docentes_id', $this->docentes_id, PDO::PARAM_INT);
    
        // Vincular parámetros de búsqueda si es necesario
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $result->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
    
        // Vincular parámetros de paginación
        $result->bindParam(':offset', $offset, PDO::PARAM_INT);
        $result->bindParam(':perPage', $perPage, PDO::PARAM_INT);
    
        $result->execute();
    
        // Obtener también el conteo total para paginación
        $countSql = "SELECT COUNT(*) as total FROM calificaciones
                     INNER JOIN inscripciones ON calificaciones.inscripciones_id = inscripciones.inscripciones_id
                     INNER JOIN estudiantes ON inscripciones.estudiantes_id = estudiantes.estudiantes_id
                     INNER JOIN cursos ON inscripciones.cursos_id = cursos.cursos_id
                     INNER JOIN cursos_sedes ON cursos.cursos_id = cursos_sedes.cursos_id
                     INNER JOIN sedes ON cursos_sedes.sedes_id = sedes.sedes_id
                     INNER JOIN cursos_docentes on cursos.cursos_id = cursos_docentes.cursos_id

                     WHERE cursos_docentes.docentes_id = :docentes_id";
    
        if (!empty($search)) {
            $countSql .= " AND (estudiantes.estudiantes_nombre LIKE :search 
                           OR estudiantes.estudiantes_apellido LIKE :search 
                           OR cursos.cursos_nombre LIKE :search
                           OR sedes.sedes_ciudad LIKE :search)";
        }
    
        $countResult = $this->db->prepare($countSql);
        $countResult->bindParam(':docentes_id', $this->docentes_id, PDO::PARAM_INT);
        
        if (!empty($search)) {
            $countResult->bindParam(':search', $searchTerm, PDO::PARAM_STR);
        }
    
        $countResult->execute();
        $total = $countResult->fetch(PDO::FETCH_ASSOC)['total'];
    
        return [
            'data' => $result->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }

    public function get_calificaciones_by_id_model($id) {
        $sql = 'SELECT calificaciones.calificaciones_id, calificaciones.inscripciones_id, calificaciones.calificaciones_primer, calificaciones.calificaciones_segundo FROM ' . $this->table . ' WHERE calificaciones_id = :id LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $result->bindParam(':id', $id);
        $result->execute();
        return $result;
    }


}