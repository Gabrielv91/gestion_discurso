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
    <title>Calendario de Arreglos</title>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        #calendar-container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .fc-header-toolbar {
            margin-bottom: 1.5em !important;
        }

        .fc-day-past {
            background-color: #f4f4f4;
            cursor: not-allowed;
        }

        .fc-event {
            cursor: pointer;
            padding: 2px;
            border-radius: 4px;
        }

        .leyenda {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
        .fc-event-title {
            font-weight: bold !important;
            font-size: 0.85em;
            white-space: normal;
        }
    </style>
</head>

<body>
    <header style="background-color: #2c3e50; padding: 15px; text-align: center; color: white;">
        <h1>Calendario de Arreglos</h1>
        <p><a href="dashboard.php" style="color: #3498db; text-decoration: none;">← Volver al Panel</a></p>
    </header>

    <div id="calendar-container">
        <div class="leyenda">
            <span><strong style="color: #f39c12;">●</strong> Pendiente</span>
            <span><strong style="color: #27ae60;">●</strong> Aprobado</span>
            <span><strong style="color: #e74c3c;">●</strong> Rechazado/Cancelado</span>
            <span><strong style="color: #3498db;">●</strong> Disponible (Sáb/Dom)</span>
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
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listWeek'
      },
      events: 'obtener_eventos.php',
      
      dateClick: function(info) {
        var clickedDate = info.dateStr;
        var dayOfWeek = new Date(clickedDate + "T00:00:00").getDay();

        // 1. Validar fecha pasada
        if (clickedDate < today) {
            alert("No puedes gestionar arreglos en fechas pasadas.");
            return;
        }

        // 2. Validar fin de semana (0: Domingo, 6: Sábado)
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            alert("Los discursos se programan solo Sábados o Domingos.");
            return;
        }

        // 3. Buscar si ya hay un evento en este día
        var existingEvents = calendar.getEvents().filter(function(event) {
            return event.startStr === clickedDate;
        });

        if (existingEvents.length > 0) {
            var event = existingEvents[0];
            
            // Recibimos las variables del PHP (obtener_eventos.php)
            var estado = event.extendedProps.estado; 
            var es_local = event.extendedProps.es_local;

            // Lógica para APROBADOS
            if (estado === 'Aprobado') {
                if (es_local) {
                    // Si es de mi congregación, me deja cancelarlo
                    if (confirm("Este es un hermano de TU congregación. ¿Deseas cancelar su salida y dejar el día libre para buscar otro orador?")) {
                        window.location.href = "eliminar_arreglo_local.php?id=" + event.id + "&fecha=" + clickedDate;
                    }
                    return;
                } else {
                    // Si es de otra congregación, me bloquea
                    alert("Este arreglo ya está APROBADO por otra congregación. No se puede modificar desde aquí. " +
                          "\n\nSi necesitas cambiarlo, la congregación de origen del orador debe cancelar la salida primero.");
                    return;
                }
            } 
            
            // Lógica para PENDIENTES
            if (estado === 'Pendiente') {
                if (!confirm("Tienes una solicitud PENDIENTE para este día. ¿Deseas reemplazarla por otro orador?")) {
                    return;
                }
                // Si acepta, el flujo sigue hacia buscar_arreglos.php
            }

            // Lógica para RECHAZADOS
            if (estado === 'Rechazado') {
                if (confirm("Este arreglo fue CANCELADO/RECHAZADO. ¿Deseas eliminarlo de tu calendario y buscar a otro orador para este día?")) {
                    window.location.href = "eliminar_solicitud_rechazada.php?id=" + event.id + "&fecha=" + clickedDate;
                }
                return;
            }
            
        } else {
            // Si el día está vacío
            if(!confirm("¿Deseas buscar un orador para el " + clickedDate + "?")) return;
        }

        // Redirigir al buscador con la fecha
        window.location.href = "buscar_arreglos.php?fecha=" + clickedDate;
      }
    });
    calendar.render();
  });
</script>
</body>

</html>