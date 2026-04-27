<?php
// chequear_notificaciones.php
session_start();
require_once 'conexion/conexion.php';

// Si no hay sesión, respondemos vacío
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['total_pendientes' => 0, 'nueva_alerta' => false]);
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// Obtener ID de la congregación actual
$stmt = $conn->prepare("SELECT id FROM congregaciones WHERE usuario_id = ? LIMIT 1");
$stmt->execute([$usuario_id]);
$mi_cong_id = $stmt->fetchColumn();

if (!$mi_cong_id) {
    echo json_encode(['total_pendientes' => 0, 'nueva_alerta' => false]);
    exit();
}

// 1. Contar total de pendientes (Para mantener el globito rojo del menú actualizado)
$sql_pendientes = "SELECT COUNT(*) FROM solicitudes s
                   INNER JOIN oradores o ON s.orador_id = o.id
                   WHERE o.congregacion_id = ? 
                   AND s.congregacion_solicitante_id != ?
                   AND s.estado = 'Pendiente'
                   AND s.fecha >= CURDATE()";
$stmt_pend = $conn->prepare($sql_pendientes);
$stmt_pend->execute([$mi_cong_id, $mi_cong_id]);
$total_pendientes = $stmt_pend->fetchColumn();

// 2. Buscar UNA notificación nueva que no hayas leído (notificado = 0)
$sql_notif = "SELECT s.id, s.estado, s.fecha, o.nombre, o.apellido, c.nombre as cong_destino
              FROM solicitudes s
              INNER JOIN oradores o ON s.orador_id = o.id
              INNER JOIN congregaciones c ON s.congregacion_solicitante_id = c.id
              WHERE o.congregacion_id = ? 
              AND s.congregacion_solicitante_id != ?
              AND s.notificado = 0
              AND s.fecha >= CURDATE()
              LIMIT 1";
$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->execute([$mi_cong_id, $mi_cong_id]);
$alerta = $stmt_notif->fetch(PDO::FETCH_ASSOC);

$respuesta = [
    'total_pendientes' => $total_pendientes,
    'nueva_alerta' => false
];

// Si encontró algo nuevo, preparamos el mensaje para el celular
if ($alerta) {
    $fecha_formateada = date("d/m/Y", strtotime($alerta['fecha']));
    $nombre_orador = $alerta['nombre'] . " " . $alerta['apellido'];
    
    if ($alerta['estado'] == 'Rechazado') {
        $respuesta['titulo'] = "❌ ¡Arreglo Cancelado!";
        $respuesta['mensaje'] = "El discurso de $nombre_orador para el $fecha_formateada en " . $alerta['cong_destino'] . " ha sido cancelado.";
    } else {
        $respuesta['titulo'] = "🔔 ¡Nueva Solicitud!";
        $respuesta['mensaje'] = "La congregación " . $alerta['cong_destino'] . " solicita a $nombre_orador para el $fecha_formateada.";
    }

    $respuesta['nueva_alerta'] = true;

    // La marcamos como leída para que el teléfono no suene dos veces por lo mismo
    $conn->prepare("UPDATE solicitudes SET notificado = 1 WHERE id = ?")->execute([$alerta['id']]);
}

header('Content-Type: application/json');
echo json_encode($respuesta);
?>