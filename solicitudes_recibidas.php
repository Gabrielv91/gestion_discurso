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

// Contamos cuántas están pendientes actualmente para el radar de notificaciones
$pendientes_actuales = 0;
foreach($solicitudes as $s) {
    if($s['estado'] == 'Pendiente') $pendientes_actuales++;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes Recibidas</title>
    <link rel="stylesheet" href="css/style.css">
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2c3e50">
    <link rel="apple-touch-icon" href="icono-192.png">
    
    <style>
        /* --- ADAPTACIÓN MÓVIL Y ESTILOS DE BOTONES --- */
        .btn-consulta {
            background-color: #2ecc71; /* Verde más claro para diferenciarlo */
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 6px;
            padding: 8px 12px;
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
            transition: 0.3s;
        }
        .btn-consulta:hover { background-color: #27ae60; }

        @media (max-width: 768px) {
            header { padding: 15px; }
            h1 { font-size: 1.5em; margin-bottom: 10px; }
            main { padding: 10px !important; }
            
            .admin-container { padding: 15px 10px; margin: 0; width: 100%; box-sizing: border-box; }
            
            div[style*="overflow-x: auto"] {
                padding-bottom: 10px;
                -webkit-overflow-scrolling: touch;
            }

            /* Apilamos los botones de acción para que sean anchos y fáciles de tocar */
            td div.acciones-grupo {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 8px !important;
            }
            
            td div.acciones-grupo a {
                width: 100% !important;
                box-sizing: border-box !important;
                padding: 10px !important;
                font-size: 0.95em !important;
                text-align: center !important;
                border-radius: 6px !important;
                margin: 0 !important;
            }

            .botones-decision {
                display: flex;
                flex-direction: row !important;
                gap: 8px;
                width: 100%;
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
                                
                                // Formateo común
                                $fecha_formateada = date("d/m/Y", strtotime($s['fecha']));
                                $hora_formateada = date("H:i", strtotime($s['hora']));
                                
                                // Limpieza del número de WhatsApp
                                $numero_wa = preg_replace('/[^0-9]/', '', $s['telefono']); 
                                if (strlen($numero_wa) > 0 && substr($numero_wa, 0, 2) != '58') {
                                    if (substr($numero_wa, 0, 1) == '0') {
                                        $numero_wa = '58' . substr($numero_wa, 1);
                                    } else {
                                        $numero_wa = '58' . $numero_wa;
                                    }
                                }

                                // ---------------------------------------------------------
                                // 1. TEXTO PARA CUANDO ESTÁ PENDIENTE (Preguntar disponibilidad)
                                // ---------------------------------------------------------
                                $texto_wa_consulta = "✋ ¡Hola, hermano " . trim($s['orador_nom']) . "!\n";
                                $texto_wa_consulta .= "La congregación *" . trim($s['cong_solicitante']) . "* nos envió una solicitud para que usted dé el discurso B-" . $s['numero_discurso'] . ".\n\n";
                                $texto_wa_consulta .= "📅 *Fecha:* " . $fecha_formateada . "\n";
                                $texto_wa_consulta .= "⏰ *Hora:* " . $hora_formateada . "\n\n";
                                $texto_wa_consulta .= "¿Estaría usted disponible en esa fecha para que yo confirme la solicitud en el sistema?";
                                
                                $enlace_wa_consulta = "https://api.whatsapp.com/send?phone=" . $numero_wa . "&text=" . urlencode($texto_wa_consulta);

                                // ---------------------------------------------------------
                                // 2. TEXTO PARA CUANDO YA ESTÁ APROBADO (Confirmación Final)
                                // ---------------------------------------------------------
                                $texto_wa_aprobado = "✋ ¡Hola, hermano " . trim($s['orador_nom']) . "!\n";
                                $texto_wa_aprobado .= "Le confirmamos oficialmente su discurso público en la congregación *" . trim($s['cong_solicitante']) . "*.\n\n";
                                $texto_wa_aprobado .= "📅 *Fecha:* " . $fecha_formateada . "\n";
                                $texto_wa_aprobado .= "⏰ *Hora:* " . $hora_formateada . "\n";
                                $texto_wa_aprobado .= "📖 *Bosquejo:* B-" . $s['numero_discurso'] . "\n\n";
                                $texto_wa_aprobado .= "¡Que Jehová bendiga sus esfuerzos!";
                                
                                $enlace_wa_aprobado = "https://api.whatsapp.com/send?phone=" . $numero_wa . "&text=" . urlencode($texto_wa_aprobado);
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
                                            <div class="acciones-grupo">
                                                <a href="<?php echo $enlace_wa_consulta; ?>" target="_blank" class="btn-consulta">
                                                    💬 1. Preguntar a Orador
                                                </a>
                                                
                                                <div class="botones-decision" style="display: flex; gap: 8px;">
                                                    <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Aprobado" 
                                                       class="btn-aprobar" 
                                                       style="flex: 1; text-decoration: none; text-align: center; margin: 0;">✅ Aprobar</a>
                                                       
                                                    <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Rechazado" 
                                                       class="btn-rechazar" 
                                                       style="flex: 1; text-decoration: none; text-align: center; margin: 0;" 
                                                       onclick="return confirm('¿Rechazar esta solicitud?');">❌ Rechazar</a>
                                                </div>
                                            </div>

                                        <?php elseif ($s['estado'] == 'Aprobado'): ?>
                                            <div class="acciones-grupo" style="display: flex; gap: 8px; align-items: center;">
                                                <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Pendiente" 
                                                   class="btn-rechazar" 
                                                   style="text-decoration: none; text-align: center; margin: 0; background-color: #95a5a6;" 
                                                   onclick="return confirm('¿Seguro que deseas anular esta aprobación y devolverla a Pendiente?');">Deshacer</a>
                                                
                                                <a href="<?php echo $enlace_wa_aprobado; ?>" 
                                                   target="_blank" 
                                                   class="btn-aprobar" 
                                                   style="text-decoration: none; text-align: center; margin: 0; background-color: #25D366; color: white;">
                                                   📲 Enviar Confirmación
                                                </a>
                                            </div>

                                        <?php elseif ($s['estado'] == 'Rechazado'): ?>
                                            <div class="acciones-grupo" style="display: flex; gap: 8px; align-items: center;">
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

    <script>
        // 1. Registra el motor en segundo plano
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }

        // 2. Pide permiso al teléfono para vibrar y mostrar alertas
        document.addEventListener('DOMContentLoaded', () => {
            if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
        });

        // 3. El Radar: Pregunta a la base de datos cada 30 segundos
        setInterval(verificarNuevasSolicitudes, 30000); 
        let cantidadPendientesAnterior = <?php echo $pendientes_actuales; ?>;

        function verificarNuevasSolicitudes() {
            fetch('chequear_notificaciones.php')
                .then(response => response.json())
                .then(data => {
                    let nuevasPendientes = parseInt(data.total_pendientes);
                    
                    // Si entra una solicitud nueva, Lanza la notificación del celular
                    if (nuevasPendientes > cantidadPendientesAnterior && nuevasPendientes > 0) {
                        if (Notification.permission === 'granted') {
                            navigator.serviceWorker.ready.then(function(registro) {
                                registro.showNotification("¡Nueva Solicitud de Discurso!", {
                                    body: "Una congregación acaba de solicitar a uno de tus oradores.",
                                    icon: 'icono-192.png',
                                    badge: 'icono-192.png',
                                    vibrate: [200, 100, 200]
                                });
                            });
                        }
                        // Actualiza la página automáticamente para mostrar la nueva fila
                        setTimeout(() => { window.location.reload(); }, 2000);
                    }
                    
                    cantidadPendientesAnterior = nuevasPendientes;
                })
                .catch(error => console.error('Error revisando notificaciones:', error));
        }
    </script>
</body>
</html>