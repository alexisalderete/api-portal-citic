<?php
// Configuración de la base de datos
$db_host = '15.235.12.99';
$db_name = 'citicpycom_prueba_portal';
$db_user = 'citicpycom_alexis';
$db_pass = 'MbXKtNTasqw';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Primero obtenemos todos los docentes con sus CIs
    $query = "SELECT docentes_id, docentes_ci FROM docentes";
    $stmt = $db->query($query);
    $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preparar la consulta SQL para insertar usuarios
    $insertSql = "INSERT INTO usuarios (usuarios_nombre, usuarios_clave, usuarios_tipo, docentes_id) 
                 VALUES (:username, :password, 'profesor', :docentes_id)";

    $insertStmt = $db->prepare($insertSql);

    $contador = 0;
    
    foreach ($docentes as $docente) {
        // El nombre de usuario y la contraseña son el CI del docente
        $username = $docente['docentes_ci'];
        $password = $docente['docentes_ci'];
        
        // Encriptar la contraseña
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Vincular parámetros y ejecutar
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':password', $hashedPassword);
        $insertStmt->bindParam(':docentes_id', $docente['docentes_id']);
        
        if($insertStmt->execute()) {
            $contador++;
        }
    }

    echo "Se crearon exitosamente $contador usuarios profesores";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>