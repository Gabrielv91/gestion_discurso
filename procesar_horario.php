<?php
// procesar_horario.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: dashboard.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

$nuevo_dia = $_POST['dia'];
$nueva_hora = $_POST['hora'];
$ajustar_futuros = isset($_POST['ajustar_futuros']) ? true : false;

$stmt_mi = $conn->prepare("SELECT id, nombre FROM congregaciones WHERE usuario_id = ?");
$stmt_mi->execute([$usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_id = $mi_cong['id'];

$sql_update = "UPDATE congregaciones SET dia_reunion = ?, hora_reunion = ? WHERE id = ?";
$conn->prepare($sql_update)->execute([$nuevo_dia, $nueva_hora, $mi_id]);

// Agruparemos los avisos por Coordinador
$avisos_agrupados = [];
$oradores_locales = [];

if ($ajustar_futuros) {
    $mapa_dias = ['Sábado' => 6, 'Domingo' => 7];
    $numero_nuevo_dia = $mapa_dias[$nuevo_dia];

    $sql_futuros = "
        SELECT s.id, s.fecha, o.nombre AS orador_nombre, c2.id AS cong_id, c2.coord_nombre, c2.coord_telefono, c2.nombre AS cong_orador
        FROM solicitudes s
        LEFT JOIN oradores o ON s.orador_id = o.id
        LEFT JOIN congregaciones c2 ON o.congregacion_id = c2.id
        WHERE s.congregacion_solicitante_id = ? 
        AND s.fecha >= CURDATE()
        AND s.estado NOT IN ('Cancelado', 'Cancelada', 'Rechazado', 'Rechazada')
    ";
    $stmt_futuros = $conn->prepare($sql_futuros);
    $stmt_futuros->execute([$mi_id]);
    $arreglos = $stmt_futuros->fetchAll(PDO::FETCH_ASSOC);

    foreach ($arreglos as $arr) {
        $fecha_vieja = $arr['fecha'];
        $numero_dia_viejo = date('N', strtotime($fecha_vieja)); 
        $diferencia = $numero_nuevo_dia - $numero_dia_viejo;
        
        $nueva_fecha = ($diferencia != 0) ? date('Y-m-d', strtotime("$diferencia days", strtotime($fecha_vieja))) : $fecha_vieja;
        
        if ($diferencia != 0) {
            $conn->prepare("UPDATE solicitudes SET fecha = ? WHERE id = ?")->execute([$nueva_fecha, $arr['id']]);
        }

        if ($arr['cong_id'] == $mi_id) {
            $oradores_locales[] = ['nombre' => $arr['orador_nombre'], 'antes' => $fecha_vieja, 'ahora' => $nueva_fecha];
        } else {
            // AGRUPACIÓN: Usamos el ID de la congregación como llave
            $id_c = $arr['cong_id'];
            if (!isset($avisos_agrupados[$id_c])) {
                $avisos_agrupados[$id_c] = [
                    'coord' => $arr['coord_nombre'] ?: 'Coordinador',
                    'telefono' => $arr['coord_telefono'],
                    'congregacion' => $arr['cong_orador'],
                    'cambios' => []
                ];
            }
            $avisos_agrupados[$id_c]['cambios'][] = "- *{$arr['orador_nombre']}*: Pasa del $fecha_vieja al $nueva_fecha";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario Actualizado</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 850px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: #27ae60; text-align: center; margin-top: 0;}
        .seccion-titulo { color: #2c3e50; font-size: 1.3em; margin-top: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        /* Tarjeta Agrupada */
        .card-agrupada { background: #fff; border: 1px solid #d1d8e0; border-radius: 10px; margin-bottom: 20px; overflow: hidden; border-left: 6px solid #3498db; }
        .card-agrupada-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #eee; }
        .card-agrupada-body { padding: 15px; }
        
        .lista-cambios { background: #f1f2f6; padding: 10px 15px; border-radius: 6px; margin: 10px 0; font-family: monospace; font-size: 0.95em; }
        
        .btn-wa { display: block; background: #25D366; color: white; text-decoration: none; padding: 12px; border-radius: 6px; text-align: center; font-weight: bold; transition: 0.3s; }
        .btn-wa:hover { background: #1ebe57; }
        
        .btn-volver { display: block; text-align: center; background: #34495e; color: white; padding: 15px; border-radius: 6px; text-decoration: none; margin-top: 30px; font-weight: bold;}
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ ¡Cambio de Horario Aplicado!</h1>
        <p style="text-align: center; color: #555;">Nuevo horario: <strong><?php echo "$nuevo_dia a las " . date("h:i A", strtotime($nueva_hora)); ?></strong></p>

        <?php if (count($avisos_agrupados) > 0): ?>
            <h2 class="seccion-titulo">📲 Avisar a Coordinadores Visitantes</h2>
            <?php foreach ($avisos_agrupados as $aviso): ?>
                <div class="card-agrupada">
                    <div class="card-agrupada-header">
                        <strong>📍 Congregación: <?php echo htmlspecialchars($aviso['congregacion']); ?></strong><br>
                        <small>👤 Coordinador: <?php echo htmlspecialchars($aviso['coord']); ?></small>
                    </div>
                    <div class="card-agrupada-body">
                        <p style="margin: 0 0 10px 0; font-size: 0.9em; color: #666;">Se notificará el cambio de los siguientes oradores en un solo mensaje:</p>
                        <div class="lista-cambios">
                            <?php echo implode("<br>", $aviso['cambios']); ?>
                        </div>
                        <?php 
                            $hora_f = date("h:i A", strtotime($nueva_hora));
                            $texto_wa = "¡Hola hermano {$aviso['coord']}! 👋 Te escribo de la congregación {$mi_cong['nombre']}. Por nuestro cambio de horario anual a los *$nuevo_dia* a las *$hora_f*, los siguientes discursos han sido reprogramados:\n\n" . str_replace("<br>", "\n", implode("\n", $aviso['cambios'])) . "\n\n¿Me podrías confirmar si los hermanos siguen disponibles? ¡Gracias!";
                            $num = preg_replace('/[^0-9]/', '', $aviso['telefono']);
                            $num = (substr($num,0,1)=='0') ? '58'.substr($num,1) : $num;
                        ?>
                        <a href="https://api.whatsapp.com/send?phone=<?php echo $num; ?>&text=<?php echo urlencode($texto_wa); ?>" target="_blank" class="btn-wa">
                            Enviar Reporte de Cambios por WhatsApp
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($oradores_locales) > 0): ?>
            <h2 class="seccion-titulo">🏠 Oradores Locales (Avisar en el Salón)</h2>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                <?php foreach ($oradores_locales as $ol): ?>
                    <p style="margin: 5px 0; font-size: 0.95em; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                        🎙️ <strong><?php echo htmlspecialchars($ol['nombre']); ?></strong>: <?php echo $ol['antes']; ?> ➡️ <strong><?php echo $ol['ahora']; ?></strong>
                    </p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="btn-volver">⬅ Volver al Panel Maestro</a>
    </div>
</body>
</html>