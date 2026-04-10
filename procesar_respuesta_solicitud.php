<?php
// procesar_respuesta_solicitud.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id']) || !isset($_GET['accion'])) {
    header("Location: dashboard.php");
    exit();
}

$id_solicitud = intval($_GET['id']);
$nueva_accion = $_GET['accion']; // 'Aprobado' o 'Rechazado'

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

try {
    $sql = "UPDATE solicitudes SET estado = :estado WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':estado' => $nueva_accion,
        ':id' => $id_solicitud
    ]);

    echo "<script>
            alert('Solicitud " . $nueva_accion . " correctamente.');
            window.location.href = 'solicitudes_recibidas.php';
          </script>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}