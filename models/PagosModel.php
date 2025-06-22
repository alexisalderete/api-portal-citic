<?php
class PagosModel {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    public function get_pagos_by_inscripcion($inscripciones_id) {
        $sql = "SELECT 
                    e.estudiantes_dni,
                    CONCAT(e.estudiantes_nombre, ' ', e.estudiantes_apellido) AS estudiante,
                    c.cursos_nombre AS curso,
                    s.sedes_ciudad AS sede,
                    
                    -- Conceptos regulares
                    -- Matrícula
                    COALESCE(
                        SUM(CASE WHEN rp.concepto_pago = 'Matrícula' THEN rp.monto_pagado ELSE 0 END),
                        'X'
                    ) AS Matrícula,

                    -- Libro
                    COALESCE(
                        SUM(CASE WHEN rp.concepto_pago = 'Libro' THEN rp.monto_pagado ELSE 0 END),
                        'X'
                    ) AS Libro,
                    
                    -- Cuotas
                    CASE 
                        WHEN SUM(CASE WHEN (rp.concepto_pago = 'Cuota' OR rp.concepto_pago LIKE '%Mora%') AND rp.mes_cuota IS NOT NULL THEN 1 ELSE 0 END) > 0 
                        THEN GROUP_CONCAT(
                                DISTINCT 
                                CASE 
                                    WHEN rp.concepto_pago = 'Cuota' AND rp.mes_cuota IS NOT NULL 
                                    THEN CONCAT(rp.mes_cuota, ' (', rp.monto_pagado, ')')
                                    WHEN rp.concepto_pago LIKE '(Mora%' AND rp.mes_cuota IS NOT NULL 
                                    THEN CONCAT(rp.mes_cuota, ' incluye mora (', rp.monto_pagado, ')')
                                    ELSE ''
                                END

                                ORDER BY 
                            CASE rp.mes_cuota
                            WHEN 'Enero' THEN 1
                            WHEN 'Febrero' THEN 2
                            WHEN 'Marzo' THEN 3
                            WHEN 'Abril' THEN 4
                            WHEN 'Mayo' THEN 5
                            WHEN 'Junio' THEN 6
                            WHEN 'Julio' THEN 7
                            WHEN 'Agosto' THEN 8
                            WHEN 'Septiembre' THEN 9
                            WHEN 'Octubre' THEN 10
                            WHEN 'Noviembre' THEN 11
                            WHEN 'Diciembre' THEN 12
                            ELSE 99 -- Por si hay valores inesperados
                        END

                                SEPARATOR ', '
                            )
                        ELSE 'Pendiente' 
                    END AS Cuotas,

                    COALESCE(
                        SUM(CASE WHEN (rp.concepto_pago = 'Cuota' OR rp.concepto_pago LIKE '(Mora%') THEN rp.monto_pagado ELSE 0 END),
                        0
                    ) AS Total_Cuotas,
        
                    -- Productos pagados (extraídos directamente de registro_pagos)
                    GROUP_CONCAT(
                        DISTINCT 
                        CASE 
                            WHEN rp.concepto_pago NOT IN ('Matrícula', 'Libro') 
                                AND rp.concepto_pago NOT LIKE 'Cuota%'
                                AND rp.concepto_pago NOT LIKE '(Mora%' 
                                AND rp.concepto_pago IS NOT NULL
                                AND rp.concepto_pago != ''
                            THEN CONCAT(rp.concepto_pago, ' (', rp.monto_pagado, ')')
                            ELSE ''
                        END

                        SEPARATOR ', '
                    ) AS Productos_Pagados,

                    COALESCE(
                        SUM(CASE WHEN (rp.concepto_pago NOT IN ('Matrícula', 'Libro') 
                                AND rp.concepto_pago NOT LIKE 'Cuota%' 
                                AND rp.concepto_pago NOT LIKE '(Mora%' 
                                AND rp.concepto_pago IS NOT NULL
                                AND rp.concepto_pago != '') THEN rp.monto_pagado ELSE 0 END),
                        0
                    ) AS Total_otros_conceptos,

                    -- Estado
                    CASE 
                        WHEN MAX(CASE WHEN rp.concepto_pago = 'Matrícula' THEN 1 ELSE 0 END) = 0 THEN 'Matrícula pendiente'
                        WHEN MAX(CASE WHEN rp.concepto_pago = 'Libro' THEN 1 ELSE 0 END) = 0 THEN 'Libro pendiente'
                        WHEN SUM(CASE WHEN rp.concepto_pago = 'Cuota' OR rp.concepto_pago LIKE '(Mora%' THEN 1 ELSE 0 END) = 0 THEN 'Cuotas pendientes'
                        ELSE 'Al día'
                    END AS Estado,

                    -- Totales
                    COALESCE(SUM(rp.monto_pagado), 0) AS total_pagado
                FROM 
                    estudiantes e
                INNER JOIN 
                    inscripciones i ON e.estudiantes_id = i.estudiantes_id
                INNER JOIN 
                    cursos c ON i.cursos_id = c.cursos_id
                INNER JOIN 
                    cursos_sedes cs ON c.cursos_id = cs.cursos_id
                INNER JOIN 
                    sedes s ON cs.sedes_id = s.sedes_id
                LEFT JOIN 
                    registro_pagos rp ON e.estudiantes_id = rp.estudiante_id 
                                    AND c.cursos_id = rp.curso_id 
                                    AND s.sedes_id = rp.sede_id
                WHERE 
                    i.inscripciones_id = :inscripciones_id
                GROUP BY 
                    e.estudiantes_id, c.cursos_id, s.sedes_id";

        $result = $this->db->prepare($sql);
        $result->bindParam(':inscripciones_id', $inscripciones_id);
        $result->execute();
        
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function get_all_pagos($params = []) {
        // Parámetros de paginación
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['perPage']) ? (int)$params['perPage'] : 10;
        $offset = ($page - 1) * $perPage;
      
        // Parámetros de búsqueda
        $search = isset($params['search']) ? $params['search'] : '';
        
        // Parámetros de ordenamiento
        $sortBy = isset($params['sortBy']) ? $params['sortBy'] : 'estudiantes.estudiantes_nombre';
        $sortDir = isset($params['sortDir']) && strtoupper($params['sortDir']) === 'DESC' ? 'DESC' : 'ASC';
      
        // Consulta base
        $sql = "SELECT 
                    e.estudiantes_dni,
                    CONCAT(e.estudiantes_nombre, ' ', e.estudiantes_apellido) AS estudiante,
                    c.cursos_nombre AS curso,
                    s.sedes_ciudad AS sede,
                    COALESCE(SUM(rp.monto_pagado), 0) AS total_pagado,
                    MAX(i.inscripciones_fecha) AS fecha_inscripcion
                FROM 
                    estudiantes e
                INNER JOIN 
                    inscripciones i ON e.estudiantes_id = i.estudiantes_id
                INNER JOIN 
                    cursos c ON i.cursos_id = c.cursos_id
                INNER JOIN 
                    cursos_sedes cs ON c.cursos_id = cs.cursos_id
                INNER JOIN 
                    sedes s ON cs.sedes_id = s.sedes_id
                LEFT JOIN 
                    registro_pagos rp ON e.estudiantes_id = rp.estudiante_id 
                                    AND c.cursos_id = rp.curso_id 
                                    AND s.sedes_id = rp.sede_id";
      
        // Añadir condiciones de búsqueda si hay término
        if (!empty($search)) {
            $sql .= " WHERE e.estudiantes_nombre LIKE :search 
                      OR e.estudiantes_apellido LIKE :search 
                      OR c.cursos_nombre LIKE :search
                      OR s.sedes_ciudad LIKE :search";
        }
      
        // Agrupar por estudiante y curso
        $sql .= " GROUP BY e.estudiantes_id, c.cursos_id, s.sedes_id";
      
        // Añadir ordenamiento
        $sql .= " ORDER BY $sortBy $sortDir";
      
        // Añadir límites para paginación
        $sql .= " LIMIT :offset, :perPage";
      
        $result = $this->db->prepare($sql);
      
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
        $countSql = "SELECT COUNT(DISTINCT CONCAT(e.estudiantes_id, '-', c.cursos_id, '-', s.sedes_id)) as total 
                     FROM estudiantes e
                     INNER JOIN inscripciones i ON e.estudiantes_id = i.estudiantes_id
                     INNER JOIN cursos c ON i.cursos_id = c.cursos_id
                     INNER JOIN cursos_sedes cs ON c.cursos_id = cs.cursos_id
                     INNER JOIN sedes s ON cs.sedes_id = s.sedes_id";
      
        if (!empty($search)) {
            $countSql .= " WHERE e.estudiantes_nombre LIKE :search 
                           OR e.estudiantes_apellido LIKE :search 
                           OR c.cursos_nombre LIKE :search
                           OR s.sedes_ciudad LIKE :search";
        }
      
        $countResult = $this->db->prepare($countSql);
        
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
}