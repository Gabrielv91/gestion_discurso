<?php
// eliminar_arreglo_local.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id']) || !isset($_GET['fecha'])) {
    header("Location: calendario_arreglos.php");
    exit();
}

$id_solicitud = intval($_GET['id']);
$fecha_volver = $_GET['fecha'];
$usuario_id = $_SESSION['usuario_id'];

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

try {
    // 1. Obtener el ID de tu congregación por seguridad
    $sql_mi_cong = "SELECT id FROM congregaciones WHERE usuario_id = :uid";
    $stmt = $conn->prepare($sql_mi_cong);
    $stmt->execute([':uid' => $usuario_id]);
    $mi_cong_id = $stmt->fetchColumn();

    // 2. Borrar la solicitud (solo si tú fuiste quien la solicitó)
    $sql = "DELETE FROM solicitudes WHERE id = :id AND congregacion_solicitante_id = :mi_cong";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id_solicitud, ':mi_cong' => $mi_cong_id]);

    // 3. Volver al buscador con el día ya libre
    header("Location: buscar_arreglos.php?fecha=" . $fecha_volver);
    exit();

} catch(PDOException $e) {
    echo "Error al limpiar el arreglo: " . $e->getMessage();
}
?>