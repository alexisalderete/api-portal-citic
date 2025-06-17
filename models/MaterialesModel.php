<?php
class MaterialesModel {
    private $db;
    private $table = 'materiales';

    public $materiales_id;
    public $materiales_nombre;
    public $materiales_descripcion;
    public $materiales_url;
    public $cursos_id;

    public function __construct($db) {
        $this->db = $db;
    }


    // Crear materiales (registro)
    public function create_materiales_model($materiales_nombre, $materiales_descripcion, $materiales_url, $materiales_tipo = 'material') {
        $sql = 'INSERT INTO '.$this->table.' 
        SET materiales_nombre = :materiales_nombre, 
        materiales_descripcion = :materiales_descripcion, 
        materiales_url = :materiales_url,
        materiales_tipo = :materiales_tipo';
        $result = $this->db->prepare($sql);
        
        // Asignar a propiedades de clase
        $this->materiales_nombre = $materiales_nombre;
        $this->materiales_descripcion = $materiales_descripcion;
        $this->materiales_url = $materiales_url;
        $this->materiales_tipo = $materiales_tipo;
        
        // Limpiar datos (solo si no son null)
        $this->materiales_nombre = $this->materiales_nombre !== null ? htmlspecialchars(strip_tags($this->materiales_nombre)) : null;
        $this->materiales_descripcion = $this->materiales_descripcion !== null ? htmlspecialchars(strip_tags($this->materiales_descripcion)) : null;
        $this->materiales_url = $this->materiales_url !== null ? htmlspecialchars(strip_tags($this->materiales_url)) : null;
        $this->materiales_tipo = $this->materiales_tipo !== null ? htmlspecialchars(strip_tags($this->materiales_tipo)) : null;
        
        // Vincular parámetros
        $result->bindParam(':materiales_nombre', $this->materiales_nombre);
        $result->bindParam(':materiales_descripcion', $this->materiales_descripcion);
        $result->bindParam(':materiales_url', $this->materiales_url);
        $result->bindParam(':materiales_tipo', $this->materiales_tipo);
    
        if($result->execute()) {
            return true;
        }
    
        return false;
    }
    

    public function create_materiales_cursos_model($materiales_id, $cursos_id) {
        $sql = 'INSERT INTO materiales_cursos
        SET materiales_id = :materiales_id, 
        cursos_id = :cursos_id';
        $result = $this->db->prepare($sql);
        
        // Asignar a propiedades de clase
        $this->materiales_id = $materiales_id;
        $this->cursos_id = $cursos_id;
        
        // Limpiar datos (solo si no son null)
        $this->materiales_id = $this->materiales_id !== null ? htmlspecialchars(strip_tags($this->materiales_id)) : null;
        $this->cursos_id = $this->cursos_id !== null ? htmlspecialchars(strip_tags($this->cursos_id)) : null;
        
        // Vincular parámetros
        $result->bindParam(':materiales_id', $this->materiales_id);
        $result->bindParam(':cursos_id', $this->cursos_id);
    
        if($result->execute()) {
            return true;
        }
        return false;
    }

    public function get_cursos_by_nombre_model($nombre) {
        $sql = 'SELECT * FROM cursos
        WHERE cursos_nombre = :nombre';
        $result = $this->db->prepare($sql);
        $result->bindParam(':nombre', $nombre);

        if($result->execute()) {
            return $result;
        }
        return false;
    }


    public function update_materiales_model($materiales_id) {
        $sql = 'UPDATE '.$this->table.' 
        SET materiales_nombre = :materiales_nombre,
            materiales_descripcion = :materiales_descripcion,
            materiales_url = :materiales_url,
            materiales_tipo = :materiales_tipo
        WHERE materiales_id = :materiales_id';
        $result = $this->db->prepare($sql);
        // Limpiar datos
        $this->materiales_nombre = htmlspecialchars(strip_tags($this->materiales_nombre));
        $this->materiales_descripcion = htmlspecialchars(strip_tags($this->materiales_descripcion));
        $this->materiales_url = htmlspecialchars(strip_tags($this->materiales_url));
        $this->materiales_tipo = htmlspecialchars(strip_tags($this->materiales_tipo));
    
        // Vincular parámetros
        $result->bindParam(':materiales_nombre', $this->materiales_nombre);
        $result->bindParam(':materiales_descripcion', $this->materiales_descripcion);
        $result->bindParam(':materiales_url', $this->materiales_url);
        $result->bindParam(':materiales_tipo', $this->materiales_tipo);
        $result->bindParam(':materiales_id', $materiales_id);
    
        if($result->execute()) {
            return true;
        }
        return false;
    }

    public function update_materiales_cursos_model($materiales_id, $cursos_id) {
        $sql = 'UPDATE materiales_cursos 
        SET cursos_id = :cursos_id
        WHERE materiales_id = :materiales_id';
        $result = $this->db->prepare($sql);
        $result->bindParam(':materiales_id', $materiales_id);
        $result->bindParam(':cursos_id', $cursos_id);
        if($result->execute()) {
            return true;
        }
        return false;
    }

    public function delete_materiales_model($materiales_id) {
        $sql = 'DELETE FROM '.$this->table.' 
        WHERE materiales_id = :materiales_id';
        $result = $this->db->prepare($sql);
        $result->bindParam(':materiales_id', $materiales_id);
        if($result->execute()) {
            return $result;
        }
        return false;
    }


    public function get_all_materiales_model($params = []) {
        // Parámetros de paginación
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['perPage']) ? (int)$params['perPage'] : 5;
        $offset = ($page - 1) * $perPage;
      
        // Parámetros de búsqueda
        $search = isset($params['search']) ? $params['search'] : '';
        
        // Parámetros de ordenamiento
        $sortBy = isset($params['sortBy']) ? $params['sortBy'] : 'materiales.materiales_created_at';
        $sortDir = isset($params['sortDir']) && strtoupper($params['sortDir']) === 'DESC' ? 'DESC' : 'ASC';
      
        // Consulta base
        $sql = "SELECT 
                mc.materiales_cursos_id,
                m.materiales_id,
                mc.cursos_id,
                m.materiales_nombre,
                m.materiales_descripcion,
                m.materiales_url,
                m.materiales_tipo,
                c.cursos_nombre
            FROM materiales m
            INNER JOIN (
                SELECT materiales_id, MIN(materiales_cursos_id) as min_id
                FROM materiales_cursos
                GROUP BY materiales_id
            ) as unique_mc ON m.materiales_id = unique_mc.materiales_id
            INNER JOIN materiales_cursos mc ON unique_mc.min_id = mc.materiales_cursos_id
            INNER JOIN cursos c ON mc.cursos_id = c.cursos_id";
      
        // Añadir condiciones de búsqueda si hay término
        if (!empty($search)) {
          $sql .= " WHERE m.materiales_nombre LIKE :search 
                    OR c.cursos_nombre LIKE :search";
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
        $countSql = "SELECT COUNT(*) as total FROM materiales m
                     INNER JOIN (
                        SELECT materiales_id, MIN(materiales_cursos_id) as min_id
                        FROM materiales_cursos
                        GROUP BY materiales_id
                    ) as unique_mc ON m.materiales_id = unique_mc.materiales_id
                    INNER JOIN materiales_cursos mc ON unique_mc.min_id = mc.materiales_cursos_id
                    INNER JOIN cursos c ON mc.cursos_id = c.cursos_id";
      
        if (!empty($search)) {
          $countSql .= " WHERE m.materiales_nombre LIKE :search 
                         OR c.cursos_nombre LIKE :search";
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


    public function get_materiales_by_curso_model($cursos_id, $tipo = null) {
        $sql = 'SELECT m.*, c.cursos_nombre 
                FROM materiales m
                INNER JOIN materiales_cursos mc ON m.materiales_id = mc.materiales_id
                INNER JOIN cursos c ON mc.cursos_id = c.cursos_id
                WHERE mc.cursos_id = :cursos_id';
        
        if ($tipo !== null && in_array($tipo, ['material', 'tarea'])) {
            $sql .= ' AND m.materiales_tipo = :tipo';
        }
                
        $result = $this->db->prepare($sql);
        $result->bindParam(':cursos_id', $cursos_id);
        
        if ($tipo !== null && in_array($tipo, ['material', 'tarea'])) {
            $result->bindParam(':tipo', $tipo);
        }
        
        $result->execute();
        return $result;
    }

    // public function get_materiales_by_curso_model($curso) {
    //     $sql = 'SELECT * FROM materiales
    //     INNER JOIN materiales_cursos ON materiales.materiales_id = materiales_cursos.materiales_id
    //     INNER JOIN cursos ON materiales_cursos.cursos_id = cursos.cursos_id
    //     WHERE cursos.cursos_nombre = :Curso';
    //     $result = $this->db->prepare($sql);
    //     $curso = "%" . $curso . "%";
    //     $result->bindParam(':Curso', $curso);
    //     $result->execute();
    //     return $result;
    // }

    public function get_materiales_by_id_model($materiales_id) {
        $sql = 'SELECT materiales.materiales_id,
        materiales_cursos.cursos_id,
        materiales.materiales_nombre,
        materiales.materiales_descripcion,
        materiales.materiales_url
        FROM ' . $this->table . '
        INNER JOIN materiales_cursos ON materiales.materiales_id = materiales_cursos.materiales_id
        WHERE materiales_id = ? LIMIT 0,1';
        $result = $this->db->prepare($sql);
        $result->bindParam(1, $materiales_id);
        $result->execute();
        return $result;
    }

    public function get_cursos_model() {
        # mostrar todos los cursos sin repetir
        $sql = 'SELECT MIN(cursos_id) as cursos_id, cursos_nombre 
            FROM cursos 
            GROUP BY cursos_nombre; ';
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result;
    }
}