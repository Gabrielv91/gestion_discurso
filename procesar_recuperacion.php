<?php
// procesar_recuperacion.php
session_start();
require_once 'conexion/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = trim($_POST['codigo_usuario']);
    
    // Convertimos las respuestas a minúsculas para que no haya errores por mayúsculas
    $resp1 = strtolower(trim($_POST['resp_seguridad_1']));
    $resp2 = strtolower(trim($_POST['resp_seguridad_2']));
    $resp3 = strtolower(trim($_POST['resp_seguridad_3']));
    
    $nueva_password = trim($_POST['nueva_password']);

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            // 1. Buscar a la congregación por su código
            $sql = "SELECT id, resp_seguridad_1, resp_seguridad_2, resp_seguridad_3 FROM usuarios WHERE codigo_usuario = :codigo LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                // 2. Validar respuestas (también en minúsculas)
                $bd_resp1 = strtolower(trim($usuario['resp_seguridad_1']));
                $bd_resp2 = strtolower(trim($usuario['resp_seguridad_2']));
                $bd_resp3 = strtolower(trim($usuario['resp_seguridad_3']));

                if ($resp1 === $bd_resp1 && $resp2 === $bd_resp2 && $resp3 === $bd_resp3) {
                    
                    // 3. RESPUESTAS CORRECTAS -> Encriptar y guardar nueva contraseña
                    $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

                    $update_sql = "UPDATE usuarios SET password = :password WHERE id = :id";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':password', $password_hash);
                    $update_stmt->bindParam(':id', $usuario['id']);

                    if ($update_stmt->execute()) {
                        echo "<script>alert('¡Éxito! Tu contraseña ha sido actualizada. Ya puedes iniciar sesión.'); window.location.href='index.php';</script>";
                    } else {
                        echo "<script>alert('Hubo un error al guardar tu nueva contraseña. Intenta de nuevo.'); window.location.href='recuperar.php';</script>";
                    }
                } else {
                    // RESPUESTAS INCORRECTAS
                    echo "<script>alert('❌ Las respuestas de seguridad son incorrectas.\\\\n\\\\nSi no las recuerdas, haz clic en el botón verde de Soporte por WhatsApp para contactar al Administrador.'); window.location.href='recuperar.php';</script>";
                }
            } else {
                // CÓDIGO NO EXISTE
                echo "<script>alert('⚠️ No se encontró ninguna congregación con el código: $codigo'); window.location.href='recuperar.php';</script>";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: recuperar.php");
    exit();
}
?>