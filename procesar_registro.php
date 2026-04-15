<?php
// procesar_registro.php
require_once 'conexion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir y limpiar los datos
    $codigo = trim($_POST['codigo_usuario']);
    
    // CAPTURAMOS EL TELÉFONO (Nuevo)
    $telefono = trim($_POST['telefono']); 
    
    // Encriptamos la contraseña inmediatamente por seguridad
    $password_hash = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    
    $preg1 = trim($_POST['preg_seguridad_1']);
    $resp1 = trim($_POST['resp_seguridad_1']);
    $preg2 = trim($_POST['preg_seguridad_2']);
    $resp2 = trim($_POST['resp_seguridad_2']);
    $preg3 = trim($_POST['preg_seguridad_3']);
    $resp3 = trim($_POST['resp_seguridad_3']);

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            // 1. Verificar si el código de congregación ya existe
            $check_sql = "SELECT id FROM usuarios WHERE codigo_usuario = :codigo LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':codigo', $codigo);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                echo "<script>alert('Error: Este código de congregación ya está registrado.'); window.location.href='registro.php';</script>";
                exit();
            }

            // 2. Insertar el nuevo usuario incluyendo el campo 'telefono'
            // IMPORTANTE: Asegúrate de haber ejecutado el ALTER TABLE en tu DB antes de probar
            $sql = "INSERT INTO usuarios (codigo_usuario, telefono, password, preg_seguridad_1, resp_seguridad_1, preg_seguridad_2, resp_seguridad_2, preg_seguridad_3, resp_seguridad_3) 
                    VALUES (:codigo, :telefono, :password, :preg1, :resp1, :preg2, :resp2, :preg3, :resp3)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':telefono', $telefono); // Enlazamos el teléfono
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':preg1', $preg1);
            $stmt->bindParam(':resp1', $resp1);
            $stmt->bindParam(':preg2', $preg2);
            $stmt->bindParam(':resp2', $resp2);
            $stmt->bindParam(':preg3', $preg3);
            $stmt->bindParam(':resp3', $resp3);

            if ($stmt->execute()) {
                echo "<script>alert('Registro exitoso. Tu cuenta está en estado Pendiente hasta que un Administrador la apruebe por WhatsApp.'); window.location.href='index.php';</script>";
            } else {
                echo "<script>alert('Hubo un error al registrar la cuenta. Intenta de nuevo.'); window.location.href='registro.php';</script>";
            }

        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: registro.php");
    exit();
}
?>