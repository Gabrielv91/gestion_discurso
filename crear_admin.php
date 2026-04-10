<?php
// crear_admin.php
require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

if ($conn != null) {
    try {
        // Datos de nuestro administrador por defecto
        $codigo_admin = 'ADMIN-MASTER';
        $password_plana = 'Admin12345'; // Puedes cambiarla aquí antes de ejecutar el script
        $password_hash = password_hash($password_plana, PASSWORD_DEFAULT);
        
        // El administrador no necesita preguntas de seguridad reales, pero los campos son obligatorios en la BD
        $preg = 'N/A';
        $resp = 'N/A';
        $rol = 'Administrador';
        $estado = 'Aprobado'; // El admin nace aprobado

        // Verificar si ya existe
        $check_sql = "SELECT id FROM usuarios WHERE codigo_usuario = :codigo";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':codigo', $codigo_admin);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            echo "El administrador ya existe en la base de datos.";
        } else {
            $sql = "INSERT INTO usuarios (codigo_usuario, password, rol, estado, preg_seguridad_1, resp_seguridad_1, preg_seguridad_2, resp_seguridad_2, preg_seguridad_3, resp_seguridad_3) 
                    VALUES (:codigo, :password, :rol, :estado, :preg, :resp, :preg, :resp, :preg, :resp)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo', $codigo_admin);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':preg', $preg);
            $stmt->bindParam(':resp', $resp);

            if ($stmt->execute()) {
                echo "<h3>¡Administrador creado con éxito!</h3>";
                echo "<p>Código de usuario: <b>" . $codigo_admin . "</b></p>";
                echo "<p>Contraseña: <b>" . $password_plana . "</b></p>";
                echo "<p><a href='index.php'>Ir al Login</a></p>";
            } else {
                echo "Hubo un error al crear el administrador.";
            }
        }
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>