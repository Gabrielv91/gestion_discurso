<?php
// solicitudes_recibidas.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Obtener mi ID de congregación
$sql_mi_cong = "SELECT id FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt = $conn->prepare($sql_mi_cong);
$stmt->execute([':uid' => $usuario_id]);
$mi_cong_id = $stmt->fetchColumn();

// 2. Buscar solicitudes para MIS oradores hechas por OTROS (Solo futuras o de hoy)
$sql = "SELECT s.id, s.fecha, s.hora, s.estado, o.nombre AS orador_nom, o.apellido AS orador_ape, o.telefono, 
               s.numero_discurso, c.nombre AS cong_solicitante, c.coord_telefono
        FROM solicitudes s
        INNER JOIN oradores o ON s.orador_id = o.id
        INNER JOIN congregaciones c ON s.congregacion_solicitante_id = c.id
        WHERE o.congregacion_id = :mi_id 
        AND s.congregacion_solicitante_id != :mi_id
        AND s.fecha >= CURDATE()
        ORDER BY s.fecha ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([':mi_id' => $mi_cong_id]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes Recibidas</title>
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* --- ADAPTACIÓN MÓVIL --- */
        @media (max-width: 768px) {
            header { padding: 15px; }
            h1 { font-size: 1.5em; margin-bottom: 10px; }
            main { padding: 10px !important; }
            
            /* Ajustamos el contenedor para ganar espacio en la pantalla */
            .admin-container { padding: 15px 10px; margin: 0; width: 100%; box-sizing: border-box; }
            
            /* Aseguramos que la tabla se pueda deslizar bien con el dedo */
            div[style*="overflow-x: auto"] {
                padding-bottom: 10px;
                -webkit-overflow-scrolling: touch;
            }

            /* Apilamos los botones de acción para que sean anchos y fáciles de tocar */
            td div[style*="display: flex"] {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 8px !important;
            }
            
            td div[style*="display: flex"] a {
                width: 100% !important;
                box-sizing: border-box !important;
                padding: 10px !important;
                font-size: 0.95em !important;
                text-align: center !important;
                border-radius: 6px !important;
            }
        }
    </style>
    </head>
<body>
    <header>
        <h1>Solicitudes Recibidas</h1>
        <p><a href="dashboard.php" style="color: white; text-decoration: underline;">Volver al Panel</a></p>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container">
            <h2>Peticiones de otras congregaciones</h2>
            <p>Aquí aparecen los hermanos que te han solicitado para dar discursos fuera (Eventos próximos).</p>

            <?php if (count($solicitudes) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="tabla-admin" style="min-width: 600px;">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Orador</th>
                                <th>Tema</th>
                                <th>Solicita</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $s): 
                                
                               // --- PREPARAR EL MENSAJE DE WHATSAPP ---
                                $fecha_formateada = date("d/m/Y", strtotime($s['fecha']));
                                $hora_formateada = date("H:i", strtotime($s['hora']));
                                
                                $texto_wa = "✋ ¡Hola, hermano " . trim($s['orador_nom']) . "!\n";
                                $texto_wa .= "Le confirmamos su discurso público en la congregación *" . trim($s['cong_solicitante']) . "*.\n\n";
                                $texto_wa .= "📅 *Fecha:* " . $fecha_formateada . "\n";
                                $texto_wa .= "⏰ *Hora:* " . $hora_formateada . "\n";
                                $texto_wa .= "📖 *Bosquejo:* B-" . $s['numero_discurso'] . "\n\n";
                                $texto_wa .= "¡Que Jehová bendiga sus esfuerzos!";
                                
                                // NUEVO: Limpiamos el número para que WhatsApp no dé error y armamos el enlace directo
                                $numero_wa = preg_replace('/[^0-9]/', '', $s['telefono']); 
                                
                                // Si en tu país usan un código específico y no lo escriben (ej. 58 para Venezuela), 
                                // puedes forzarlo así: Si el número no empieza por 58, se lo agregas.
                                if (strlen($numero_wa) > 0 && substr($numero_wa, 0, 2) != '58') {
                                    // Asumiendo código de país +58, ajusta el '58' según sea necesario.
                                    // Si los hermanos ya ponen +58, esto no hace nada. Si ponen 0414..., cambia el 0 por 58
                                    if (substr($numero_wa, 0, 1) == '0') {
                                        $numero_wa = '58' . substr($numero_wa, 1);
                                    } else {
                                        $numero_wa = '58' . $numero_wa;
                                    }
                                }

                                $enlace_wa = "https://api.whatsapp.com/send?phone=" . $numero_wa . "&text=" . urlencode($texto_wa);
                                // ---------------------------------------
                            ?>
                                <tr>
                                    <td><?php echo $fecha_formateada . " <br> " . $hora_formateada; ?></td>
                                    <td><strong><?php echo htmlspecialchars($s['orador_nom'] . " " . $s['orador_ape']); ?></strong></td>
                                    <td>B-<?php echo htmlspecialchars($s['numero_discurso']); ?></td>
                                    <td><?php echo htmlspecialchars($s['cong_solicitante']); ?></td>
                                    <td>
                                        <span style="font-weight: bold; color: <?php echo ($s['estado'] == 'Pendiente') ? '#f39c12' : ($s['estado'] == 'Aprobado' ? '#27ae60' : '#e74c3c'); ?>;">
                                            <?php echo htmlspecialchars($s['estado']); ?>
                                        </span>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <?php if ($s['estado'] == 'Pendiente'): ?>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Aprobado" 
                                                   class="btn-aprobar" 
                                                   style="text-decoration: none; text-align: center; margin: 0;">Aprobar</a>
                                                   
                                                <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Rechazado" 
                                                   class="btn-rechazar" 
                                                   style="text-decoration: none; text-align: center; margin: 0;" 
                                                   onclick="return confirm('¿Rechazar esta solicitud?');">Rechazar</a>
                                            </div>
                                        <?php elseif ($s['estado'] == 'Aprobado'): ?>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Pendiente" 
                                                   class="btn-rechazar" 
                                                   style="text-decoration: none; text-align: center; margin: 0; background-color: #95a5a6;" 
                                                   onclick="return confirm('¿Seguro que deseas anular esta aprobación y devolverla a Pendiente?');">Deshacer</a>
                                                
                                                <a href="<?php echo $enlace_wa; ?>" 
                                                   target="_blank" 
                                                   class="btn-aprobar" 
                                                   style="text-decoration: none; text-align: center; margin: 0; background-color: #25D366; color: white;">
                                                   📲 Notificar
                                                </a>
                                            </div>
                                        <?php elseif ($s['estado'] == 'Rechazado'): ?>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Pendiente" 
                                                   class="btn-rechazar" 
                                                   style="text-decoration: none; text-align: center; margin: 0; background-color: #95a5a6;" 
                                                   onclick="return confirm('¿Seguro que deseas anular este rechazo y devolverlo a Pendiente?');">Deshacer</a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="mensaje-vacio"><p>No tienes solicitudes pendientes ni futuras de otras congregaciones por ahora.</p></div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>