<?php
// vistas/coord_panel.php

// Los headers de caché ya se manejan en el archivo principal (dashboard.php)
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");
require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Verificamos si el usuario ya registró los datos de su congregación
$sql = "SELECT * FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. NUEVO: Calculamos las notificaciones directamente aquí para que no falle
$total_notificaciones = 0;
if ($perfil) {
    $mi_cong_id = $perfil['id'];
    $sql_notif = "SELECT COUNT(*) FROM solicitudes s
                  INNER JOIN oradores o ON s.orador_id = o.id
                  WHERE o.congregacion_id = :mi_id 
                  AND s.congregacion_solicitante_id != :mi_id
                  AND s.estado = 'Pendiente'
                  AND s.fecha >= CURDATE()";

    $stmt_notif = $conn->prepare($sql_notif);
    $stmt_notif->execute([':mi_id' => $mi_cong_id]);
    $total_notificaciones = $stmt_notif->fetchColumn();
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    /* Estilos inyectados para la vista del panel */
    .admin-container {
        max-width: 1000px;
        margin: 0 auto;
        font-family: 'Segoe UI', Tahoma, sans-serif;
        color: #333;
    }

    /* ESTILOS DEL FORMULARIO DE INICIO */
    .form-bienvenida {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .form-bienvenida h2 {
        margin-top: 0;
        color: #2c3e50;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 10px;
    }

    .input-group label {
        display: block;
        font-weight: bold;
        color: #34495e;
        margin-bottom: 5px;
        font-size: 0.9em;
    }

    .input-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #bdc3c7;
        border-radius: 6px;
        font-size: 1em;
        box-sizing: border-box;
        transition: border 0.3s;
    }

    .input-group input:focus {
        border-color: #3498db;
        outline: none;
    }

    /* TARJETA DE RESUMEN (CUANDO YA HAY PERFIL) */
    .perfil-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
        border-left: 5px solid #3498db;
    }

    .perfil-info h2 {
        margin: 0 0 10px 0;
        color: #2c3e50;
    }

    .perfil-info p {
        margin: 5px 0;
        color: #555;
    }

    .btn-editar {
        background: #7f8c8d;
        color: white;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        transition: background 0.3s;
    }

    .btn-editar:hover {
        background: #606b6b;
    }

    /* GRID DE MÓDULOS */
    .modulos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 20px;
    }

    /* TARJETAS DE ACCESO (BOTONES) */
    .modulo-card {
        background: white;
        border-radius: 12px;
        padding: 25px 20px;
        text-align: center;
        text-decoration: none;
        color: #2c3e50;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        position: relative;
    }

    .modulo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border-color: #ddd;
    }

    .modulo-icon {
        font-size: 2.5em;
        margin-bottom: 5px;
    }

    .modulo-title {
        font-weight: bold;
        font-size: 1.1em;
        margin: 0;
    }

    /* Colores Hover */
    .card-maestro:hover {
        border-bottom: 4px solid #27ae60;
    }

    .card-solicitudes:hover {
        border-bottom: 4px solid #f39c12;
    }

    .card-oradores:hover {
        border-bottom: 4px solid #3498db;
    }

    .card-hospitalidad:hover {
        border-bottom: 4px solid #e74c3c;
    }

    .card-calendario:hover {
        border-bottom: 4px solid #9b59b6;
    }

    .card-directorio:hover {
        border-bottom: 4px solid #f1c40f;
    }

    /* GLOBO DE NOTIFICACIÓN FLOTANTE */
    .badge-flotante {
        position: absolute;
        top: -10px;
        right: -10px;
        background-color: #e74c3c;
        color: white;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9em;
        font-weight: bold;
        border: 3px solid white;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        z-index: 10;
        animation: latido 2s infinite;
    }

    @keyframes latido {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .btn-guardar {
        background: #27ae60;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-guardar:hover {
        background: #219150;
    }

    /* Ajuste para el z-index del mapa para que no tape otros elementos */
    #map {
        z-index: 1;
    }

    @media (max-width: 768px) {
        .perfil-card {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
    }
</style>

<div class="admin-container">

    <?php if (!$perfil): ?>
        <div class="form-bienvenida">
            <h2>Completa el Perfil de tu Congregación</h2>
            <p style="color: #7f8c8d; margin-bottom: 25px;">Antes de poder gestionar oradores o arreglos, necesitamos los
                datos de tu congregación y de contacto.</p>

            <form action="guardar_perfil.php" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label for="nombre">Nombre de la Congregación:</label>
                        <input type="text" id="nombre" name="nombre" required placeholder="Ej: Centro, Barinas">
                    </div>

                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label for="ubicacion">Ubicación (Dirección en texto):</label>
                        <input type="text" id="ubicacion" name="ubicacion_texto" required
                            placeholder="Ej: Av. Principal con calle 2">
                    </div>

                    <div style="grid-column: 1 / -1; margin-top: 10px;">
                        <label
                            style="font-weight: bold; color: #2c3e50; font-size: 1.1em; border-bottom: 2px solid #eee; padding-bottom: 5px; display: block; margin-bottom: 10px;">
                            📍 Ubicación en el Mapa
                        </label>
                        <p style="font-size: 0.9em; color: #7f8c8d; margin-top: -5px; margin-bottom: 15px;">
                            Haz clic en el mapa o arrastra el marcador rojo para fijar la ubicación exacta del Salón del
                            Reino.
                        </p>

                        <div id="map"
                            style="height: 300px; border-radius: 8px; border: 2px solid #bdc3c7; margin-bottom: 15px;">
                        </div>

                        <div style="display: flex; gap: 20px;">
                            <div class="input-group" style="flex: 1;">
                                <label for="latitud">Latitud (Automática):</label>
                                <input type="text" id="latitud" name="latitud" value="8.622600" readonly
                                    style="background: #f8f9fa; color: #2980b9; font-family: monospace; font-weight: bold;">
                            </div>
                            <div class="input-group" style="flex: 1;">
                                <label for="longitud">Longitud (Automática):</label>
                                <input type="text" id="longitud" name="longitud" value="-70.203900" readonly
                                    style="background: #f8f9fa; color: #2980b9; font-family: monospace; font-weight: bold;">
                            </div>
                        </div>
                    </div>
                    <h3
                        style="grid-column: 1 / -1; margin-top: 15px; border-bottom: 1px solid #ecf0f1; padding-bottom: 5px; color: #2c3e50;">
                        Datos del Coordinador</h3>

                    <div class="input-group">
                        <label for="coord_nombre">Nombres:</label>
                        <input type="text" id="coord_nombre" name="coord_nombre" required>
                    </div>

                    <div class="input-group">
                        <label for="coord_apellido">Apellidos:</label>
                        <input type="text" id="coord_apellido" name="coord_apellido" required>
                    </div>

                    <div class="input-group">
                        <label for="coord_telefono">Teléfono (WhatsApp):</label>
                        <input type="text" id="coord_telefono" name="coord_telefono" required>
                    </div>

                    <div class="input-group">
                        <label for="coord_correo">Correo Electrónico:</label>
                        <input type="email" id="coord_correo" name="coord_correo" required>
                    </div>
                </div>

                <button type="submit" class="btn-guardar" style="width: 100%; margin-top: 25px; font-size: 1.1em;">💾
                    Guardar Perfil de Congregación</button>
            </form>
        </div>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Centrado en Barinas por defecto
                var map = L.map('map').setView([8.6226, -70.2039], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                var marker = L.marker([8.6226, -70.2039], { draggable: true }).addTo(map);

                function updateCoords(lat, lng) {
                    document.getElementById('latitud').value = lat.toFixed(6);
                    document.getElementById('longitud').value = lng.toFixed(6);
                }

                map.on('click', function (e) {
                    marker.setLatLng(e.latlng);
                    updateCoords(e.latlng.lat, e.latlng.lng);
                });

                marker.on('dragend', function (e) {
                    var pos = marker.getLatLng();
                    updateCoords(pos.lat, pos.lng);
                });

                // Asegurar que el mapa cargue del tamaño correcto
                setTimeout(function () { map.invalidateSize(); }, 400);
            });
        </script>

    <?php else: ?>
        <div class="welcome-card"
            style="display: flex; gap: 15px; align-items: center; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; border-left: 5px solid #3498db;">
            <a href="editar_perfil.php"
                style="background: #7f8c8d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: 0.3s;">
                ✏️ Editar Perfil
            </a>
            <a href="seguridad.php"
                style="background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: 0.3s;">
                🔒 Seguridad
            </a>
        </div>
        <h3 style="color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px;">Módulos de
            Gestión</h3>

        <div class="modulos-grid">

            <a href="control_arreglos.php" class="modulo-card card-maestro">
                <div class="modulo-icon">📅</div>
                <h3 class="modulo-title">Panel Maestro</h3>
            </a>

            <a href="solicitudes_recibidas.php" class="modulo-card card-solicitudes">
                <?php if ($total_notificaciones > 0): ?>
                    <span class="badge-flotante"><?php echo $total_notificaciones; ?></span>
                <?php endif; ?>
                <div class="modulo-icon">🔔</div>
                <h3 class="modulo-title">Solicitudes Recibidas</h3>
            </a>

            <a href="oradores.php" class="modulo-card card-oradores">
                <div class="modulo-icon">🗣️</div>
                <h3 class="modulo-title">Mis Oradores</h3>
            </a>

            <a href="gestionar_hogares.php" class="modulo-card card-hospitalidad">
                <div class="modulo-icon">🏠</div>
                <h3 class="modulo-title">Hospitalidad</h3>
            </a>

            <a href="calendario_arreglos.php" class="modulo-card card-calendario">
                <div class="modulo-icon">🔍</div>
                <h3 class="modulo-title">Buscar Arreglos</h3>
            </a>
            <a href="intercambiar_fechas.php" class="modulo-card" style="border-bottom: 4px solid #e67e22;">
                <div class="modulo-icon">😎</div>
                <h3 class="modulo-title">Intercambio de Arreglos</h3>
            </a>

            <a href="directorio_congregaciones.php" class="modulo-card card-directorio">
                <div class="modulo-icon">📖</div>
                <h3 class="modulo-title">Directorio</h3>
            </a>

            <a href="cambiar_horario.php" class="modulo-card" style="border-bottom: 4px solid #e67e22;">
                <div class="modulo-icon">🔄</div>
                <h3 class="modulo-title">Cambio de Horario</h3>
            </a>

            <a href="lista_discursos.php" style="text-decoration: none; color: inherit;">
                <div class="modulo-card"
                    style="background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #eee; transition: 0.3s;"
                    onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 15px rgba(0,0,0,0.1)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.05)';">
                    <div style="font-size: 3em; margin-bottom: 10px;">📋</div>
                    <h3 style="margin: 0; font-size: 1.1em; color: #2c3e50;">Lista de Discursos</h3>
                </div>
            </a>

        </div>
    <?php endif; ?>
</div>

<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#2c3e50">
<link rel="apple-touch-icon" href="icono-192.png">

<script>
    // 1. Instalar la PWA (Service Worker) para que el teléfono la vea como App
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('PWA Lista'))
                .catch(err => console.log('Error PWA', err));
        });
    }

    // 2. Pedir permiso al teléfono/PC para lanzar notificaciones al entrar al Panel
    document.addEventListener('DOMContentLoaded', () => {
        if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    });

    // 3. Motor que revisa la Base de Datos cada 30 segundos en busca de nuevas solicitudes o cancelaciones
    setInterval(verificarNuevasSolicitudes, 30000); 

    function verificarNuevasSolicitudes() {
        fetch('chequear_notificaciones.php')
            .then(response => response.json())
            .then(data => {
                
                // 1. Actualizar el circulito rojo de notificaciones
                let badge = document.querySelector('.badge-flotante');
                if (badge) {
                    if (data.total_pendientes > 0) {
                        badge.textContent = data.total_pendientes;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
                
                // 2. Si el servidor dice que hay una alarma nueva (Solicitud o Cancelación), hacemos vibrar el teléfono
                if (data.nueva_alerta) {
                    lanzarNotificacionLocal(data.titulo, data.mensaje);
                    
                    // Si estás justo en la pantalla de solicitudes, recarga para que veas el cambio
                    if (window.location.pathname.includes('solicitudes_recibidas.php')) {
                        setTimeout(() => { window.location.reload(); }, 2500);
                    }
                }
            })
            .catch(error => console.error('Error revisando notificaciones:', error));
    }

    // Función que hace sonar/vibrar el celular con el mensaje
    function lanzarNotificacionLocal(titulo, mensaje) {
        if (Notification.permission === 'granted') {
            navigator.serviceWorker.ready.then(function(registro) {
                registro.showNotification(titulo, {
                    body: mensaje,
                    icon: 'icono-192.png',
                    badge: 'icono-192.png',
                    vibrate: [200, 100, 200]
                });
            });
        }
    }
</script>