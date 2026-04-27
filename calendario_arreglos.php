<?php
// calendario_arreglos.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Arreglos - Calendario</title>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        
        .header { background: #2c3e50; color: white; padding: 15px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.4em; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.9em; display: inline-block; margin-top: 5px; }
        .header a:hover { text-decoration: underline; }

        /* CONTENEDOR ULTRA COMPACTO */
        #calendar-container {
            max-width: 950px;
            margin: 15px auto;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .leyenda {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px;
            background: #fdfdfd;
            border: 1px solid #eee;
            border-radius: 8px;
            font-size: 0.8em;
            color: #555;
        }
        .leyenda span { display: flex; align-items: center; gap: 4px; font-weight: 500; background: white; padding: 3px 8px; border-radius: 20px; border: 1px solid #f4f4f4;}

        /* MAGIA CSS PARA APLASTAR LA CUADRÍCULA */
        .fc-theme-standard th { background: #f8f9fa; padding: 4px 0; color: #2c3e50; font-weight: bold; text-transform: uppercase; font-size: 0.75em; border-color: #eee; }
        .fc-theme-standard td { border-color: #eee; }
        
        /* Forzamos a que los cuadros de los días sean bajitos */
        .fc-theme-standard .fc-daygrid-day-frame { min-height: 55px !important; } 
        
        .fc-header-toolbar { margin-bottom: 0.8em !important; }
        .fc-toolbar-title { color: #2c3e50; font-weight: bold; font-size: 1.2em !important; text-transform: capitalize; }
        
        /* Botones más chiquitos */
        .fc .fc-button-primary { background-color: #3498db; border-color: #3498db; font-weight: bold; padding: 3px 8px !important; font-size: 0.85em !important;}
        .fc .fc-button-primary:hover { background-color: #2980b9; border-color: #2980b9; }

        .fc-daygrid-day-number { color: #34495e; font-weight: bold; padding: 2px 4px !important; font-size: 0.8em; }
        .fc-day-past { background-color: #fcfcfc; }
        .fc-day-future:hover, .fc-day-today:hover { background-color: #f1f8ff; cursor: pointer; }
        .fc-day-today { background-color: #fff9e6 !important; }

        /* EVENTOS MÁS FINOS */
        .fc-event {
            cursor: pointer;
            padding: 2px 3px;
            border-radius: 4px;
            border: none !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            margin-bottom: 1px !important;
        }
        .fc-event:hover { transform: translateY(-1px); box-shadow: 0 3px 6px rgba(0,0,0,0.15); filter: brightness(0.95); }
        .fc-event-title { font-weight: bold !important; font-size: 0.7em; white-space: normal; line-height: 1.1; text-align: center; }

        /* ESTILOS DE LAS ALERTAS DE RESPUESTA */
        .alerta-mensaje {
            max-width: 950px; 
            margin: 15px auto; 
            padding: 15px; 
            border-radius: 8px; 
            text-align: center; 
            font-weight: bold; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .alerta-verde { background-color: #d4edda; color: #155724; border-left: 5px solid #28a745; }
        .alerta-naranja { background-color: #fff3cd; color: #856404; border-left: 5px solid #ffc107; }
        .alerta-roja { background-color: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; }

    </style>
</head>

<body>
    <header class="header">
        <h1>🔍 Buscar Arreglos</h1>
        <p style="color: #bdc3c7; margin: 2px 0 0 0; font-size: 0.9em;">Haz clic en cualquier fin de semana libre para solicitar un orador</p>
        <a href="dashboard.php">⬅ Volver al Panel Maestro</a>
    </header>

    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] == 'solicitud_enviada'): ?>
        <div class="alerta-mensaje alerta-verde">
            ✅ Solicitud enviada correctamente en la fecha seleccionada.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] == 'solicitud_actualizada'): ?>
        <div class="alerta-mensaje alerta-naranja">
            🔄 Arreglo reemplazado: El orador anterior fue sustituido por el nuevo.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'bloqueado_verde'): ?>
        <div class="alerta-mensaje alerta-roja">
            🔒 ¡Acción denegada! No se guardó el arreglo porque ya tenías a un hermano Confirmado (Verde) en esa fecha. Debes cancelarlo primero.
        </div>
    <?php endif; ?>
    <div id="calendar-container">
        <div class="leyenda">
            <span><strong style="color: #f39c12; font-size: 1.2em;">●</strong> Solicitud Pendiente</span>
            <span><strong style="color: #27ae60; font-size: 1.2em;">●</strong> Arreglo Confirmado</span>
            <span><strong style="color: #e74c3c; font-size: 1.2em;">●</strong> Cancelado/Rechazado</span>
            <span><strong style="color: #3498db; font-size: 1.2em;">●</strong> Día Disponible</span>
        </div>
        <div id='calendar'></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var today = new Date().toISOString().split('T')[0];

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            firstDay: 1,
            contentHeight: 420,
            aspectRatio: 1.8,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            events: 'obtener_eventos.php',
            
            dateClick: function(info) {
                var clickedDate = info.dateStr;
                var dayOfWeek = new Date(clickedDate + "T00:00:00").getDay();

                if (clickedDate < today) {
                    alert("No puedes gestionar arreglos en fechas pasadas.");
                    return;
                }

                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    alert("Los discursos públicos se programan únicamente los Sábados o Domingos.");
                    return;
                }

                var existingEvents = calendar.getEvents().filter(function(event) {
                    return event.startStr.split('T')[0] === clickedDate; 
                });

                // ========================================================
                // LÓGICA DE COLORES (LA MÁQUINA DE ESTADOS DEL CALENDARIO)
                // ========================================================
                if (existingEvents.length > 0) {
                    var event = existingEvents[0];
                    var estado = event.extendedProps.estado; 
                    var es_local = event.extendedProps.es_local;

                    // REGLA 1 (VERDE): Bloqueo total (A menos que sea de tu congre y quieras borrarlo)
                    if (estado === 'Aprobado') {
                        if (es_local) {
                            if (confirm("Tienes a un hermano de TU congregación agendado. \n¿Deseas cancelar su salida y dejar el día libre para buscar otro orador visitante?")) {
                                window.location.href = "eliminar_arreglo_local.php?id=" + event.id + "&fecha=" + clickedDate;
                            }
                            return;
                        } else {
                            alert("⚠️ Este arreglo ya está APROBADO (Verde) por otra congregación.\n\nEl arreglo ya está hecho y no se puede modificar desde aquí. La congregación de origen del orador debe cancelar la salida primero para que el día quede libre.");
                            return;
                        }
                    } 
                    
                    // REGLA 2 (NARANJA): Avisa que lo va a pisar.
                    if (estado === 'Pendiente') {
                        if (!confirm("⏳ Tienes una solicitud PENDIENTE (Naranja) en esta fecha. \n\nSi continúas y seleccionas a un nuevo orador, el hermano actual será reemplazado. ¿Deseas continuar?")) {
                            return;
                        }
                    }

                    // REGLA 3 (ROJO): Avisa que lo va a pisar o eliminar.
                    if (estado === 'Rechazado') {
                        if (!confirm("❌ Este arreglo quedó CANCELADO/RECHAZADO (Rojo). \n\nSi continúas y seleccionas a un nuevo orador, se sobrescribirá este registro. ¿Deseas buscar otro hermano?")) {
                            return;
                        }
                    }
                    
                } else {
                    // REGLA 4 (VACÍO): Día libre
                    if(!confirm("📅 El " + clickedDate + " está libre. \n¿Deseas buscar un orador para este día?")) return;
                }

                // Si todo está bien y confirmaste, te envía a buscar al hermano
                window.location.href = "buscar_arreglos.php?fecha=" + clickedDate;
            }
        });
        
        calendar.render();
    });
    </script>
</body>
</html>