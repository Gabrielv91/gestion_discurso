<?php
// actualizar_seguridad.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// Recibir datos del formulario
$pass_actual = $_POST['pass_actual'];
$nueva_pass  = $_POST['nueva_pass'];
$conf_pass   = $_POST['conf_pass'];

$resp_1 = $_POST['respuesta_1'] ?? '';
$resp_2 = $_POST['respuesta_2'] ?? '';
$resp_3 = $_POST['respuesta_3'] ?? '';

// 1. Validar contraseña actual (Seguridad obligatoria)
$stmt = $conn->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($pass_actual, $usuario['password'])) {
    die("<script>
            alert('❌ ERROR: La contraseña actual es incorrecta. Por seguridad, no se aplicaron los cambios.');
            window.history.back();
         </script>");
}

// 2. Actualizar Contraseña (solo si el usuario escribió una nueva)
if (!empty($nueva_pass)) {
    if ($nueva_pass !== $conf_pass) {
        die("<script>
                alert('❌ ERROR: Las nuevas contraseñas no coinciden.');
                window.history.back();
             </script>");
    }
    
    $pass_hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
    $stmt_upd_pass = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    $stmt_upd_pass->execute([$pass_hash, $usuario_id]);
}

// 3. Actualizar Respuestas de Seguridad (Alineado a las columnas de tu BD)
// Se limpian espacios y se pasan a minúsculas para evitar errores futuros al recuperar
if (!empty($resp_1)) {
    $r1_limpia = strtolower(trim($resp_1));
    $conn->prepare("UPDATE usuarios SET resp_seguridad_1 = ? WHERE id = ?")->execute([$r1_limpia, $usuario_id]);
}
if (!empty($resp_2)) {
    $r2_limpia = strtolower(trim($resp_2));
    $conn->prepare("UPDATE usuarios SET resp_seguridad_2 = ? WHERE id = ?")->execute([$r2_limpia, $usuario_id]);
}
if (!empty($resp_3)) {
    $r3_limpia = strtolower(trim($resp_3));
    $conn->prepare("UPDATE usuarios SET resp_seguridad_3 = ? WHERE id = ?")->execute([$r3_limpia, $usuario_id]);
}

// 4. Éxito
echo "<script>
        alert('✅ ¡Datos de seguridad actualizados correctamente!');
        window.location.href = 'editar_perfil.php';
      </script>";
?>