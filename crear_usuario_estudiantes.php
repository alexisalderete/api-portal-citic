<?php
// Configuración de la base de datos
$db_host = '15.235.12.99';
$db_name = 'citicpycom_sistema4';
$db_user = 'citicpycom_alexis';
$db_pass = 'MbXKtNTasqw';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Iniciar transacción
    $db->beginTransaction();

    // 1. Obtenemos estudiantes con inscripciones activas y DNI válido
    $queryEstudiantes = "SELECT DISTINCT e.estudiantes_id, e.estudiantes_dni
                        FROM estudiantes e
                        JOIN inscripciones i ON e.estudiantes_id = i.estudiantes_id
                        WHERE i.inscripciones_estado = 'Activo'
                        AND e.estudiantes_dni IS NOT NULL
                        AND e.estudiantes_dni != ''";
    $estudiantes = $db->query($queryEstudiantes)->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtenemos todas las inscripciones activas agrupadas por estudiante
    $queryInscripciones = "SELECT i.estudiantes_id, i.inscripciones_id
                          FROM inscripciones i
                          WHERE i.inscripciones_estado = 'Activo'";
    $inscripcionesData = $db->query($queryInscripciones)->fetchAll(PDO::FETCH_ASSOC);

    // Organizamos las inscripciones por estudiante_id
    $inscripcionesPorEstudiante = [];
    foreach ($inscripcionesData as $inscripcion) {
        $inscripcionesPorEstudiante[$inscripcion['estudiantes_id']][] = $inscripcion['inscripciones_id'];
    }

    // 3. Verificamos qué usuarios ya existen para no duplicarlos
    $queryUsuariosExistentes = "SELECT usuarios_nombre FROM usuarios";
    $usuariosExistentes = $db->query($queryUsuariosExistentes)->fetchAll(PDO::FETCH_COLUMN);

    // Preparar las consultas SQL
    $insertUsuarioSql = "INSERT INTO usuarios (usuarios_nombre, usuarios_clave, usuarios_tipo, usuarios_fecha_creacion) 
                        VALUES (:username, :password, 'estudiante', NOW())";
    
    $insertRelacionSql = "INSERT INTO usuarios_inscripciones (usuarios_id, inscripciones_id) 
                         VALUES (:usuarios_id, :inscripciones_id)";
    
    $selectUsuarioSql = "SELECT usuarios_id FROM usuarios WHERE usuarios_nombre = :username";

    $insertUsuarioStmt = $db->prepare($insertUsuarioSql);
    $insertRelacionStmt = $db->prepare($insertRelacionSql);
    $selectUsuarioStmt = $db->prepare($selectUsuarioSql);

    $contadorUsuarios = 0;
    $contadorRelaciones = 0;
    $omitidos = 0;
    $usuariosExistentesEncontrados = 0;
    
    foreach ($estudiantes as $estudiante) {
        $dni = $estudiante['estudiantes_dni'];
        $estudiante_id = $estudiante['estudiantes_id'];

        // Verificar si el usuario ya existe
        $selectUsuarioStmt->bindParam(':username', $dni);
        $selectUsuarioStmt->execute();
        $usuarioExistente = $selectUsuarioStmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioExistente) {
            $usuarios_id = $usuarioExistente['usuarios_id'];
            $usuariosExistentesEncontrados++;
        } else {
            // Crear nuevo usuario si no existe
            $hashedPassword = password_hash($dni, PASSWORD_BCRYPT);
            
            $insertUsuarioStmt->bindParam(':username', $dni);
            $insertUsuarioStmt->bindParam(':password', $hashedPassword);
            
            if($insertUsuarioStmt->execute()) {
                $usuarios_id = $db->lastInsertId();
                $contadorUsuarios++;
            } else {
                $db->rollBack();
                die("Error al crear el usuario para el estudiante DNI: $dni");
            }
        }

        // Crear relaciones para todas las inscripciones activas del estudiante
        if(isset($inscripcionesPorEstudiante[$estudiante_id])) {
            foreach($inscripcionesPorEstudiante[$estudiante_id] as $inscripcion_id) {
                // Verificar si la relación ya existe
                $queryCheckRelacion = "SELECT 1 FROM usuarios_inscripciones 
                                     WHERE usuarios_id = :usuarios_id 
                                     AND inscripciones_id = :inscripciones_id";
                $checkStmt = $db->prepare($queryCheckRelacion);
                $checkStmt->bindParam(':usuarios_id', $usuarios_id);
                $checkStmt->bindParam(':inscripciones_id', $inscripcion_id);
                $checkStmt->execute();
                
                if(!$checkStmt->fetch()) {
                    // Solo crear la relación si no existe
                    $insertRelacionStmt->bindParam(':usuarios_id', $usuarios_id);
                    $insertRelacionStmt->bindParam(':inscripciones_id', $inscripcion_id);
                    
                    if($insertRelacionStmt->execute()) {
                        $contadorRelaciones++;
                    } else {
                        $db->rollBack();
                        die("Error al crear la relación usuario-inscripción para DNI: $dni");
                    }
                }
            }
        }
    }

    // Confirmar todas las inserciones
    $db->commit();
    
    echo "<h3>Resultado del proceso:</h3>";
    echo "<ul>";
    echo "<li>Usuarios nuevos creados: $contadorUsuarios</li>";
    echo "<li>Usuarios existentes encontrados: $usuariosExistentesEncontrados</li>";
    echo "<li>Relaciones creadas en usuarios_inscripciones: $contadorRelaciones</li>";
    if($omitidos > 0) {
        echo "<li>Estudiantes omitidos (DNI vacío): $omitidos</li>";
    }
    echo "</ul>";

} catch(PDOException $e) {
    if($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<div style='color:red;'><strong>Error:</strong> " . $e->getMessage() . "</div>";
}
?>