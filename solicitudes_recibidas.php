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
// Se agregaron c.dia_reunion y c.hora_reunion para extraer el horario de la congregación solicitante
$sql = "SELECT s.id, s.fecha, s.hora, s.estado, o.nombre AS orador_nom, o.apellido AS orador_ape, o.telefono, 
               s.numero_discurso, c.nombre AS cong_solicitante, c.coord_telefono, c.dia_reunion, c.hora_reunion
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
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.6em; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; }

        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        
        .intro-text { background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 5px solid #3498db; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .intro-text h2 { margin-top: 0; color: #2c3e50; font-size: 1.3em; margin-bottom: 5px; }
        .intro-text p { color: #7f8c8d; margin-bottom: 0; font-size: 0.95em; }

        /* =========================================
           DISEÑO DE LISTA (TABLA COMPACTA)
           ========================================= */
        .tabla-lista {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .tabla-lista th {
            background: #2c3e50;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-size: 0.95em;
        }
        .tabla-lista td {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            font-size: 0.95em;
            color: #2c3e50;
        }
        .tabla-lista tr:hover { background: #f9f9f9; }

        /* Badges de Estado */
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
            display: inline-block;
            text-align: center;
        }
        .b-pend { background: #fcf3cf; color: #b9770e; }
        .b-apro { background: #d5f5e3; color: #1e8449; }
        .b-rech { background: #fadbd8; color: #c0392b; }

        /* Botones Compactos */
        .acciones-flex { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-lista {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85em;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-w { background: #25D366; color: white; }
        .btn-w:hover { background: #20b858; }
        .btn-a { background: #27ae60; color: white; }
        .btn-a:hover { background: #219150; }
        .btn-r { background: #e74c3c; color: white; }
        .btn-r:hover { background: #c0392b; }
        .btn-d { background: #95a5a6; color: white; }
        .btn-d:hover { background: #7f8c8d; }

        /* =========================================
           ADAPTACIÓN A TELÉFONO (LISTA ANGOSTA)
           ========================================= */
        @media (max-width: 768px) {
            .tabla-lista thead { display: none; }
            .tabla-lista, .tabla-lista tbody, .tabla-lista tr, .tabla-lista td { 
                display: block; width: 100%; box-sizing: border-box; 
            }
            
            .tabla-lista tr {
                display: grid;
                grid-template-areas:
                    "fecha estado"
                    "orador orador"
                    "tema cong"
                    "acciones acciones";
                gap: 4px;
                padding: 12px;
                border: 1px solid #ddd;
                margin-bottom: 12px;
                border-radius: 8px;
                background: white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            }
            
            .tabla-lista td { padding: 0; border: none; }
            
            .m-fecha { grid-area: fecha; color: #7f8c8d; font-size: 0.9em; display: flex; align-items: center; gap: 5px; }
            .m-fecha br { display: none; }
            
            .m-estado { grid-area: estado; text-align: right; }
            .m-orador { grid-area: orador; font-size: 1.15em; color: #2c3e50; padding: 4px 0 !important; }
            .m-tema { grid-area: tema; font-size: 0.9em; color: #555; }
            .m-cong { grid-area: cong; font-size: 0.9em; color: #555; text-align: right; }
            
            .m-acciones { grid-area: acciones; margin-top: 8px; border-top: 1px dashed #eee; padding-top: 10px !important; }
            
            .acciones-flex { display: flex; flex-direction: column; gap: 8px; }
            .btn-lista { width: 100%; padding: 12px; font-size: 0.95em; }
            
            .duo-btn { display: flex; gap: 8px; width: 100%; }
            .duo-btn .btn-lista { flex: 1; margin: 0; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Solicitudes Recibidas</h1>
        <p style="margin: 5px 0 0 0;"><a href="dashboard.php">⬅ Volver al Panel</a></p>
    </header>

    <main class="container">
        <div class="intro-text">
            <h2>Peticiones de otras congregaciones</h2>
            <p>Hermanos que te han solicitado para dar discursos fuera (Eventos próximos).</p>
        </div>

        <?php if (count($solicitudes) > 0): ?>
            <table class="tabla-lista">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Orador</th>
                        <th>Tema</th>
                        <th>Congregación</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $s): 
                        
                        // Formateo común de fecha
                        $fecha_formateada = date("d/m/Y", strtotime($s['fecha']));
                        
                        // INYECCIÓN DINÁMICA DEL DÍA Y HORA DEL PERFIL DE LA CONGREGACIÓN SOLICITANTE
                        $dia_reunion_sol = !empty($s['dia_reunion']) ? $s['dia_reunion'] . " " : "";
                        $hora_reunion_sol = !empty($s['hora_reunion']) ? date("h:i A", strtotime($s['hora_reunion'])) : date("h:i A", strtotime($s['hora']));
                        
                        // Limpieza del número de WhatsApp
                        $numero_wa = preg_replace('/[^0-9]/', '', $s['telefono']); 
                        if (strlen($numero_wa) > 0 && substr($numero_wa, 0, 2) != '58') {
                            $numero_wa = (substr($numero_wa, 0, 1) == '0') ? '58' . substr($numero_wa, 1) : '58' . $numero_wa;
                        }

                        // Textos WhatsApp Integrando el día y la hora real
                        $texto_wa_consulta = "✋ ¡Hola, hermano " . trim($s['orador_nom']) . "!\nLa congregación *" . trim($s['cong_solicitante']) . "* nos envió una solicitud para que usted dé el discurso B-" . $s['numero_discurso'] . ".\n\n📅 *Fecha:* " . $dia_reunion_sol . $fecha_formateada . "\n⏰ *Hora:* " . $hora_reunion_sol . "\n\n¿Estaría usted disponible en esa fecha para que yo confirme la solicitud en el sistema?";
                        $enlace_wa_consulta = "https://api.whatsapp.com/send?phone=" . $numero_wa . "&text=" . urlencode($texto_wa_consulta);

                        $texto_wa_aprobado = "✋ ¡Hola, hermano " . trim($s['orador_nom']) . "!\nLe confirmamos oficialmente su discurso público en la congregación *" . trim($s['cong_solicitante']) . "*.\n\n📅 *Fecha:* " . $dia_reunion_sol . $fecha_formateada . "\n⏰ *Hora:* " . $hora_reunion_sol . "\n📖 *Bosquejo:* B-" . $s['numero_discurso'] . "\n\n¡Que Jehová bendiga sus esfuerzos!";
                        $enlace_wa_aprobado = "https://api.whatsapp.com/send?phone=" . $numero_wa . "&text=" . urlencode($texto_wa_aprobado);
                        
                        // Determinación de clase de color para estado
                        $clase_estado = 'b-pend';
                        if ($s['estado'] == 'Aprobado') $clase_estado = 'b-apro';
                        if ($s['estado'] == 'Rechazado') $clase_estado = 'b-rech';
                    ?>
                        <tr>
                            <td class="m-fecha">
                                <strong><?php echo $fecha_formateada; ?></strong>
                                <br><small>⏰ <?php echo $hora_reunion_sol; ?></small>
                            </td>
                            <td class="m-orador"><strong><?php echo htmlspecialchars($s['orador_nom'] . " " . $s['orador_ape']); ?></strong></td>
                            <td class="m-tema">B-<?php echo htmlspecialchars($s['numero_discurso']); ?></td>
                            <td class="m-cong"><?php echo htmlspecialchars($s['cong_solicitante']); ?></td>
                            <td class="m-estado">
                                <span class="badge <?php echo $clase_estado; ?>"><?php echo htmlspecialchars($s['estado']); ?></span>
                            </td>
                            <td class="m-acciones">
                                <div class="acciones-flex">
                                    <?php if ($s['estado'] == 'Pendiente'): ?>
                                        <a href="<?php echo $enlace_wa_consulta; ?>" target="_blank" class="btn-lista btn-w">💬 1. Preguntar</a>
                                        <div class="duo-btn">
                                            <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Aprobado" class="btn-lista btn-a">✅ Aprobar</a>
                                            <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Rechazado" class="btn-lista btn-r" onclick="return confirm('¿Rechazar esta solicitud?');">❌ Rechazar</a>
                                        </div>

                                    <?php elseif ($s['estado'] == 'Aprobado'): ?>
                                        <a href="<?php echo $enlace_wa_aprobado; ?>" target="_blank" class="btn-lista btn-w">📲 Enviar Confirmación</a>
                                        <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Pendiente" class="btn-lista btn-d" onclick="return confirm('¿Seguro que deseas anular esta aprobación y devolverla a Pendiente?');">↩️ Deshacer</a>

                                    <?php elseif ($s['estado'] == 'Rechazado'): ?>
                                        <a href="procesar_respuesta_solicitud.php?id=<?php echo $s['id']; ?>&accion=Pendiente" class="btn-lista btn-d" onclick="return confirm('¿Seguro que deseas anular este rechazo y devolverlo a Pendiente?');">↩️ Deshacer a Pendiente</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="background: white; border: 2px dashed #bdc3c7; padding: 40px 20px; text-align: center; border-radius: 8px; color: #7f8c8d;">
                <h3 style="margin-top: 0; font-size: 1.3em; color: #2c3e50;">Todo está al día</h3>
                <p style="margin-bottom: 0;">No tienes solicitudes pendientes ni futuras de otras congregaciones por ahora.</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Funciones en segundo plano para notificaciones push
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                Notification.requestPermission();
            }
        });

        // Verificador de nuevas notificaciones (El Radar)
        setInterval(verificarNuevasSolicitudes, 30000); 
        let cantidadPendientesAnterior = <?php echo $pendientes_actuales; ?>;

        function verificarNuevasSolicitudes() {
           fetch('chequear_notificaciones.php?nocache=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    let nuevasPendientes = parseInt(data.total_pendientes);
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
                        // Refresca la vista para ver el nuevo bloque de la lista
                        setTimeout(() => { window.location.reload(); }, 2000);
                    }
                    cantidadPendientesAnterior = nuevasPendientes;
                })
                .catch(error => console.error('Error revisando notificaciones:', error));
        }
    </script>
</body>
</html>