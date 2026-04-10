<?php
// eliminar_solicitud_rechazada.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id']) || !isset($_GET['fecha'])) {
    header("Location: calendario_arreglos.php");
    exit();
}

$id_solicitud = intval($_GET['id']);
$fecha_volver = $_GET['fecha'];

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

try {
    // Solo borramos si el estado es Rechazado (por seguridad)
    $sql = "DELETE FROM solicitudes WHERE id = :id AND estado = 'Rechazado'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id_solicitud]);

    // Redirigimos al buscador con la fecha lista para pedir otro hermano
    header("Location: buscar_arreglos.php?fecha=" . $fecha_volver);
    exit();

} catch(PDOException $e) {
    echo "Error al limpiar el arreglo: " . $e->getMessage();
}
?>