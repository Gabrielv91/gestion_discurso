<?php
// chequear_notificaciones.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['total_pendientes' => 0, 'nueva_alerta' => false]);
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT id FROM congregaciones WHERE usuario_id = ? LIMIT 1");
$stmt->execute([$usuario_id]);
$mi_cong_id = $stmt->fetchColumn();

if (!$mi_cong_id) {
    echo json_encode(['total_pendientes' => 0, 'nueva_alerta' => false]);
    exit();
}

// 1. Contar total de pendientes
$sql_pendientes = "SELECT COUNT(*) FROM solicitudes s
                   INNER JOIN oradores o ON s.orador_id = o.id
                   WHERE o.congregacion_id = ? 
                   AND s.congregacion_solicitante_id != ?
                   AND s.estado = 'Pendiente'
                   AND s.fecha >= CURDATE()";
$stmt_pend = $conn->prepare($sql_pendientes);
$stmt_pend->execute([$mi_cong_id, $mi_cong_id]);
$total_pendientes = $stmt_pend->fetchColumn();

// 2. RADAR BIDIRECCIONAL (¡AHORA CON LOS PARÉNTESIS CORRECTOS!)
$sql_notif = "SELECT s.id, s.estado, s.fecha, o.nombre, o.apellido, s.notificado,
                     c_solicita.nombre as cong_solicitante, 
                     c_origen.nombre as cong_origen
              FROM solicitudes s
              INNER JOIN oradores o ON s.orador_id = o.id
              INNER JOIN congregaciones c_solicita ON s.congregacion_solicitante_id = c_solicita.id
              INNER JOIN congregaciones c_origen ON o.congregacion_id = c_origen.id
              WHERE 
                (
                    (o.congregacion_id = :mi_id_origen AND s.notificado = 0) 
                    OR 
                    (s.congregacion_solicitante_id = :mi_id_destino AND s.notificado = 2)
                )
              AND s.fecha >= CURDATE()
              LIMIT 1";

$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->execute([
    ':mi_id_origen' => $mi_cong_id, 
    ':mi_id_destino' => $mi_cong_id
]); 
$alerta = $stmt_notif->fetch(PDO::FETCH_ASSOC);

$respuesta = [
    'total_pendientes' => $total_pendientes,
    'nueva_alerta' => false
];

if ($alerta) {
    $fecha_formateada = date("d/m/Y", strtotime($alerta['fecha']));
    $nombre_orador = $alerta['nombre'] . " " . $alerta['apellido'];
    
    // ESCENARIO A: Yo soy el Dueño del orador
    if ($alerta['notificado'] == 0) {
        if ($alerta['estado'] == 'Rechazado') {
            $respuesta['titulo'] = "❌ ¡Arreglo Cancelado!";
            $respuesta['mensaje'] = "La congregación " . $alerta['cong_solicitante'] . " canceló el discurso de $nombre_orador para el $fecha_formateada.";
        } else {
            $respuesta['titulo'] = "🔔 ¡Nueva Solicitud!";
            $respuesta['mensaje'] = "La congregación " . $alerta['cong_solicitante'] . " solicita a $nombre_orador para el $fecha_formateada.";
        }
    } 
    // ESCENARIO B: Yo soy quien Solicitó al orador
    elseif ($alerta['notificado'] == 2) {
        if ($alerta['estado'] == 'Aprobado') {
            $respuesta['titulo'] = "✅ ¡Solicitud Aprobada!";
            $respuesta['mensaje'] = "La congregación " . $alerta['cong_origen'] . " aprobó la visita de $nombre_orador para el $fecha_formateada.";
        } elseif ($alerta['estado'] == 'Rechazado') {
            $respuesta['titulo'] = "❌ Arreglo Caído / Rechazado";
            $respuesta['mensaje'] = "La congregación " . $alerta['cong_origen'] . " no puede enviar a $nombre_orador para el $fecha_formateada.";
        } else {
            $respuesta['titulo'] = "⏳ Cambio de Estado";
            $respuesta['mensaje'] = "La solicitud de $nombre_orador ha vuelto a estado Pendiente.";
        }
    }

    $respuesta['nueva_alerta'] = true;

    // Apagamos la alarma
    $conn->prepare("UPDATE solicitudes SET notificado = 1 WHERE id = ?")->execute([$alerta['id']]);
}

header('Content-Type: application/json');
echo json_encode($respuesta);
?>