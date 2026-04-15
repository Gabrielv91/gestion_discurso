<?php
// admin_reset_clave.php
session_start();
require_once 'conexion/conexion.php';

// Validar que solo el Administrador pueda hacer esto
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] != 'Administrador' && $_SESSION['rol'] != 'Admin')) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['codigo_congregacion'])) {
    $codigo = trim($_POST['codigo_congregacion']);
    $clave_temporal = "123456";
    $password_hash = password_hash($clave_temporal, PASSWORD_DEFAULT);

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    $sql = "UPDATE usuarios SET password = :password WHERE codigo_usuario = :codigo";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':password' => $password_hash, ':codigo' => $codigo]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('✅ Contraseña reseteada con éxito. \\n\\nDile al hermano que inicie sesión con el código: $codigo y la clave: 123456'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Error: No se encontró ninguna congregación con el código $codigo.'); window.location.href='dashboard.php';</script>";
    }
}
?>