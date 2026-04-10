<?php
// control_salidas.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Identificar tu congregación para filtrar solo tus oradores que salen
$sql_mi_cong = "SELECT id, nombre FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_cong_id = $mi_cong['id'];

// 2. Consulta para el seguimiento de salidas (tus oradores yendo a otros salones)
$sql_seguimiento = "
    SELECT s.id AS solicitud_id, s.fecha, s.hora, s.numero_discurso, s.estado,
           o.nombre AS orador_nombre, o.apellido AS orador_apellido, o.telefono,
           c.nombre AS destino_nombre, c.coord_nombre AS coord_destino, c.coord_telefono
    FROM solicitudes s
    INNER JOIN oradores o ON s.orador_id = o.id
    INNER JOIN congregaciones c ON s.congregacion_solicitante_id = c.id
    WHERE o.congregacion_id = :mi_id 
    AND s.congregacion_solicitante_id != :mi_id
    AND s.fecha >= CURDATE()
    ORDER BY s.fecha ASC
";

$stmt_seg = $conn->prepare($sql_seguimiento);
$stmt_seg->execute([':mi_id' => $mi_cong_id]);
$salidas = $stmt_seg->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento de Salidas - <?php echo htmlspecialchars($mi_cong['nombre']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .card-salida { border: 1px solid #ddd; border-left: 5px solid #3498db; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #fff; }
        .info-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .fecha-badge { background: #2c3e50; color: #fff; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
        .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 0.85em; font-weight: bold; }
        .pill-Aprobado { background: #d4edda; color: #155724; }
        .pill-Pendiente { background: #fff3cd; color: #856404; }
        .btn-wa { background: #25D366; color: white; padding: 8px 12px; text-decoration: none; border-radius: 5px; font-size: 0.9em; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body style="background: #f8f9fa;">
    <header style="background: #2c3e50; color: white; padding: 15px; text-align: center;">
        <h1>Control de Salidas (Exportación)</h1>
        <p>Hermanos de <strong><?php echo htmlspecialchars($mi_cong['nombre']); ?></strong> asignados a otras congregaciones</p>
        <a href="dashboard.php" style="color: #ecf0f1;">Volver al Panel</a>
    </header>

    <main style="padding: 20px; max-width: 900px; margin: 0 auto;">
        <?php if (count($salidas) > 0): ?>
            <?php foreach ($salidas as $s): ?>
                <div class="card-salida" style="border-left-color: <?php echo ($s['estado'] == 'Aprobado') ? '#2ecc71' : '#f1c40f'; ?>">
                    <div class="info-header">
                        <div>
                            <span class="fecha-badge"><?php echo date("d/m/Y", strtotime($s['fecha'])); ?></span>
                            <span class="status-pill pill-<?php echo $s['estado']; ?>"><?php echo $s['estado']; ?></span>
                        </div>
                        <div style="text-align: right;">
                            <strong>Bosquejo N° <?php echo $s['numero_discurso']; ?></strong><br>
                            <small><?php echo $s['hora']; ?></small>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
                        <div>
                            <p style="margin: 0;"><strong>Orador:</strong><br><?php echo htmlspecialchars($s['orador_nombre'] . " " . $s['orador_apellido']); ?></p>
                            <?php 
                                $msg_hermano = "Hola hermano " . $s['orador_nombre'] . ", le recuerdo su salida este domingo a la congregación " . $s['destino_nombre'] . ".";
                                $url_wa_hermano = "https://wa.me/" . preg_replace('/[^0-9]/', '', $s['telefono']) . "?text=" . urlencode($msg_hermano);
                            ?>
                            <a href="<?php echo $url_wa_hermano; ?>" target="_blank" class="btn-wa">📲 Recordar al Hermano</a>
                        </div>
                        <div>
                            <p style="margin: 0;"><strong>Destino:</strong><br>Cong. <?php echo htmlspecialchars($s['destino_nombre']); ?></p>
                            <p style="margin: 5px 0 0 0; font-size: 0.85em; color: #666;">Coord: <?php echo htmlspecialchars($s['coord_destino']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 10px; border: 1px dashed #ccc;">
                <p>No tienes salidas programadas en los próximos días.</p>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>