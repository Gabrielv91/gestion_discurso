<?php
session_start();
require_once 'conexion/conexion.php';

// Si no hay sesión iniciada, devolvemos 0
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['total_pendientes' => 0]);
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Obtener mi ID de congregación
$stmt = $conn->prepare("SELECT id FROM congregaciones WHERE usuario_id = ? LIMIT 1");
$stmt->execute([$usuario_id]);
$mi_cong_id = $stmt->fetchColumn();

// 2. Contar cuántas solicitudes PENDIENTES tengo como orador (Hermanos míos pedidos por otros)
$sql = "SELECT COUNT(*) FROM solicitudes s
        INNER JOIN oradores o ON s.orador_id = o.id
        WHERE o.congregacion_id = ? 
        AND s.congregacion_solicitante_id != ?
        AND s.estado = 'Pendiente'
        AND s.fecha >= CURDATE()";

$stmt = $conn->prepare($sql);
$stmt->execute([$mi_cong_id, $mi_cong_id]);
$total = $stmt->fetchColumn();

// Devolver la respuesta en formato JSON para que el Javascript la lea
header('Content-Type: application/json');
echo json_encode(['total_pendientes' => $total]);
?>