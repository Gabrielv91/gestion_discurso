<?php
// editar_perfil.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

$mensaje = '';

// 1. PROCESAR ACTUALIZACIÓN DE CONGREGACIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_perfil'])) {
    // Unimos nombres y apellidos para la base de datos
    $nombre_completo = trim($_POST['nombres'] . ' ' . $_POST['apellidos']);

    $sql_update = "UPDATE congregaciones SET 
                    nombre = :nombre,
                    ubicacion_texto = :ubicacion,
                    latitud = :latitud,
                    longitud = :longitud,
                    coord_nombre = :coord_nombre,
                    coord_telefono = :telefono,
                    correo = :correo
                   WHERE usuario_id = :uid";

    $stmt_up = $conn->prepare($sql_update);
    $exito = $stmt_up->execute([
        ':nombre' => $_POST['nombre'],
        ':ubicacion' => $_POST['ubicacion'],
        ':latitud' => $_POST['latitud'],
        ':longitud' => $_POST['longitud'],
        ':coord_nombre' => $nombre_completo,
        ':telefono' => $_POST['telefono'],
        ':correo' => $_POST['correo'],
        ':uid' => $usuario_id
    ]);

    if ($exito) {
        $mensaje = "<div class='alerta-exito'>✅ Datos de la congregación actualizados correctamente.</div>";
    } else {
        $mensaje = "<div class='alerta-error'>❌ Hubo un error al actualizar los datos.</div>";
    }
}

// 2. OBTENER DATOS ACTUALES
$sql = "SELECT * FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':uid' => $usuario_id]);
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

// Separar nombre y apellido para el formulario
$partes_nombre = explode(' ', $perfil['coord_nombre'] ?? '', 2);
$nombres_actual = $partes_nombre[0] ?? '';
$apellidos_actual = $partes_nombre[1] ?? '';

// Coordenadas por defecto si no hay registradas
$lat_defecto = !empty($perfil['latitud']) ? $perfil['latitud'] : '8.6226';
$lng_defecto = !empty($perfil['longitud']) ? $perfil['longitud'] : '-70.2144';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Congregación</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; padding-bottom: 40px;}
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header a { color: #bdc3c7; text-decoration: underline; font-size: 0.9em; }
        .container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        
        .caja-blanca { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); }
        
        .seccion-titulo { font-size: 1.2em; color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 8px; margin-bottom: 20px; margin-top: 30px; font-weight: bold; }
        .seccion-titulo:first-child { margin-top: 0; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: 1 / -1; }
        
        label { display: block; font-weight: bold; color: #34495e; margin-bottom: 5px; font-size: 0.9em; }
        input[type="text"], input[type="email"] { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; font-size: 1em; box-sizing: border-box; background: #fdfdfd; transition: border 0.3s; }
        input:focus { border-color: #3498db; outline: none; background: #fff; }
        
        .btn-submit { background: #2980b9; color: white; border: none; padding: 12px 20px; font-size: 1.1em; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; transition: 0.3s; margin-top: 20px; }
        .btn-submit:hover { background: #1f618d; }
        
        .btn-mapa { background: #27ae60; color: white; border: none; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; }
        .btn-mapa:hover { background: #219150; }
        
        .alerta-exito { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alerta-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        
        /* Modal Mapa */
        #modalMapa { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 600px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3); }
        #map { height: 400px; width: 100%; border-radius: 8px; border: 2px solid #bdc3c7; margin-bottom: 15px; }
    </style>
</head>

<body>

    <header class="header">
        <h1 style="margin: 0 0 10px 0;">Editar Perfil de la Congregación</h1>
        <a href="dashboard.php">⬅ Volver al Panel Maestro</a>
    </header>

    <div class="container">
        <?php echo $mensaje; ?>

        <div class="caja-blanca">
            <form method="POST" action="">
                <input type="hidden" name="form_perfil" value="1">

                <div class="seccion-titulo">🏢 Datos Generales</div>
                <div class="form-grid">
                    <div class="full-width">
                        <label>Nombre de la Congregación:</label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre'] ?? ''); ?>" required>
                    </div>
                    <div class="full-width">
                        <label>Ubicación (Dirección en texto):</label>
                        <input type="text" name="ubicacion" value="<?php echo htmlspecialchars($perfil['ubicacion_texto'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="seccion-titulo">📍 Coordenadas GPS</div>
                <div class="form-grid">
                    <div>
                        <label>Latitud:</label>
                        <input type="text" id="inputLat" name="latitud" value="<?php echo htmlspecialchars($perfil['latitud'] ?? ''); ?>" readonly style="background:#eee;">
                    </div>
                    <div>
                        <label>Longitud:</label>
                        <input type="text" id="inputLng" name="longitud" value="<?php echo htmlspecialchars($perfil['longitud'] ?? ''); ?>" readonly style="background:#eee;">
                    </div>
                    <div class="full-width">
                        <button type="button" class="btn-mapa" onclick="abrirMapa()">🗺️ Seleccionar ubicación en el Mapa</button>
                        <small style="color: #7f8c8d; display:block; margin-top:5px;">El sistema usará estas coordenadas para calcular la distancia con otras congregaciones.</small>
                    </div>
                </div>

                <div class="seccion-titulo">👤 Datos del Coordinador</div>
                <div class="form-grid">
                    <div>
                        <label>Nombres:</label>
                        <input type="text" name="nombres" value="<?php echo htmlspecialchars($nombres_actual); ?>" required>
                    </div>
                    <div>
                        <label>Apellidos:</label>
                        <input type="text" name="apellidos" value="<?php echo htmlspecialchars($apellidos_actual); ?>" required>
                    </div>
                    <div>
                        <label>Teléfono (WhatsApp):</label>
                        <input type="text" name="telefono" value="<?php echo htmlspecialchars($perfil['coord_telefono'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Correo Electrónico:</label>
                        <input type="email" name="correo" value="<?php echo htmlspecialchars($perfil['correo'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit">💾 Guardar Cambios de Perfil</button>
            </form>
        </div>

    </div>

    <div id="modalMapa">
        <div class="modal-content">
            <h3 style="margin-top:0; color:#2c3e50;">Arrastra el marcador a tu Salón del Reino</h3>
            <div id="map"></div>
            <div style="display: flex; justify-content: space-between; gap: 10px;">
                <button type="button" onclick="cerrarMapa()" style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; flex: 1;">✖ Cancelar</button>
                <button type="button" onclick="confirmarUbicacion()" style="padding: 10px 20px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; flex: 1; font-weight: bold;">✅ Confirmar Ubicación</button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;
        let tempLat = <?php echo $lat_defecto; ?>;
        let tempLng = <?php echo $lng_defecto; ?>;

        function abrirMapa() {
            document.getElementById('modalMapa').style.display = 'flex';
            if (map) {
                setTimeout(() => { map.invalidateSize(); }, 200);
                return;
            }
            setTimeout(() => {
                map = L.map('map').setView([tempLat, tempLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                marker = L.marker([tempLat, tempLng], { draggable: true }).addTo(map);

                marker.on('dragend', function (e) {
                    tempLat = marker.getLatLng().lat;
                    tempLng = marker.getLatLng().lng;
                });

                map.on('click', function (e) {
                    tempLat = e.latlng.lat;
                    tempLng = e.latlng.lng;
                    marker.setLatLng(e.latlng);
                });
            }, 200);
        }

        function cerrarMapa() { document.getElementById('modalMapa').style.display = 'none'; }
        
        function confirmarUbicacion() {
            document.getElementById('inputLat').value = tempLat.toFixed(8);
            document.getElementById('inputLng').value = tempLng.toFixed(8);
            cerrarMapa();
        }
    </script>
</body>
</html>