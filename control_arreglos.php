<?php
// control_arreglos.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

function formatearTelefonoWA($numero)
{
    $limpio = preg_replace('/[^0-9]/', '', $numero);
    if (substr($limpio, 0, 1) === '0') {
        return '58' . substr($limpio, 1);
    } elseif (strlen($limpio) == 10 && substr($limpio, 0, 2) !== '58') {
        return '58' . $limpio;
    }
    return $limpio;
}

// 1. Obtener datos de la congregación incluyendo día y hora
$sql_mi_cong = "SELECT id, nombre, ubicacion_texto, latitud, longitud, dia_reunion, hora_reunion FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_cong_id = $mi_cong['id'];

// ==========================================
// MOTOR: REGLAS DE COLORES (MÁQUINA DE ESTADOS)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'nueva_solicitud') {
    $nuevo_orador_id = $_POST['orador_id'];
    $nuevo_discurso = $_POST['numero_discurso'];
    $nueva_fecha = $_POST['fecha'];
    $nueva_hora = $_POST['hora'];
    
    // 1. Buscamos si ya hay ALGO en esa fecha para nuestra congregación
    $stmt_check_fecha = $conn->prepare("SELECT id, estado FROM solicitudes WHERE congregacion_solicitante_id = ? AND fecha = ?");
    $stmt_check_fecha->execute([$mi_cong_id, $nueva_fecha]);
    $solicitud_existente = $stmt_check_fecha->fetch(PDO::FETCH_ASSOC);

    // 2. Determinamos el estado inicial del NUEVO hermano
    $stmt_check_orador = $conn->prepare("SELECT congregacion_id FROM oradores WHERE id = ?");
    $stmt_check_orador->execute([$nuevo_orador_id]);
    $orador_data = $stmt_check_orador->fetch(PDO::FETCH_ASSOC);
    $nuevo_estado = ($orador_data['congregacion_id'] == $mi_cong_id) ? 'Aprobado' : 'Pendiente';

    // 3. Aplicamos tu Lógica de Colores
    if ($solicitud_existente) {
        $estado_actual = $solicitud_existente['estado'];
        $id_solicitud_vieja = $solicitud_existente['id'];

        if ($estado_actual === 'Aprobado') {
            header("Location: control_arreglos.php?error=bloqueado_verde");
            exit();
        } else {
            $sql_update = "UPDATE solicitudes SET orador_id = ?, numero_discurso = ?, hora = ?, estado = ? WHERE id = ?";
            $stmt_upd = $conn->prepare($sql_update);
            $stmt_upd->execute([$nuevo_orador_id, $nuevo_discurso, $nueva_hora, $nuevo_estado, $id_solicitud_vieja]);
            
            header("Location: control_arreglos.php?exito=actualizado");
            exit();
        }
    } else {
        $sql_insert = "INSERT INTO solicitudes (congregacion_solicitante_id, orador_id, numero_discurso, fecha, hora, estado) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_ins = $conn->prepare($sql_insert);
        $stmt_ins->execute([$mi_cong_id, $nuevo_orador_id, $nuevo_discurso, $nueva_fecha, $nueva_hora, $nuevo_estado]);
        
        header("Location: control_arreglos.php?exito=nuevo");
        exit();
    }
}
// ==========================================

// CONSULTAS DE HOSPITALIDAD
$sql_alm = "SELECT id, nombre_familia FROM hogares WHERE congregacion_id = :mi_id AND ofrece_almuerzo = 1 ORDER BY nombre_familia ASC";
$stmt_alm = $conn->prepare($sql_alm);
$stmt_alm->execute([':mi_id' => $mi_cong_id]);
$hogares_almuerzo = $stmt_alm->fetchAll(PDO::FETCH_ASSOC);

$sql_hosp = "SELECT id, nombre_familia FROM hogares WHERE congregacion_id = :mi_id AND ofrece_hospedaje = 1 ORDER BY nombre_familia ASC";
$stmt_hosp = $conn->prepare($sql_hosp);
$stmt_hosp->execute([':mi_id' => $mi_cong_id]);
$hogares_hospedaje = $stmt_hosp->fetchAll(PDO::FETCH_ASSOC);

// MOTOR DE BÚSQUEDA POR FECHAS
date_default_timezone_set('America/Caracas');
$fecha_hoy = date('Y-m-d');

$fecha_desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : $fecha_hoy;
$fecha_hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : '';

$condicion_fecha = "s.fecha >= :desde";
$params_ent = [':mi_id' => $mi_cong_id, ':desde' => $fecha_desde];
$params_sal = [':mi_id' => $mi_cong_id, ':desde' => $fecha_desde];

if ($fecha_hasta != '') {
    $condicion_fecha .= " AND s.fecha <= :hasta";
    $params_ent[':hasta'] = $fecha_hasta;
    $params_sal[':hasta'] = $fecha_hasta;
}

// BLOQUE 1: ENTRADAS
$sql_entradas = "
    SELECT s.id, s.fecha, s.hora, s.numero_discurso, s.estado,
           s.hogar_almuerzo_id, s.hogar_hospedaje_id,
           o.congregacion_id AS orador_cong_id,
           o.nombre AS orador_nombre, o.apellido AS orador_apellido, o.telefono,
           c.nombre AS cong_origen, c.coord_nombre, c.coord_telefono,
           d.ruta_archivo, d.cancion
    FROM solicitudes s
    INNER JOIN oradores o ON s.orador_id = o.id
    INNER JOIN congregaciones c ON o.congregacion_id = c.id
    LEFT JOIN discursos d ON s.orador_id = d.orador_id AND s.numero_discurso = d.numero_discurso
    WHERE s.congregacion_solicitante_id = :mi_id 
    AND {$condicion_fecha}
    ORDER BY s.fecha ASC
";
$stmt_ent = $conn->prepare($sql_entradas);
$stmt_ent->execute($params_ent);
$entradas = $stmt_ent->fetchAll(PDO::FETCH_ASSOC);

// BLOQUE 2: SALIDAS (Ahora incluye el día y hora de la congregación de destino)
$sql_salidas = "
    SELECT s.id, s.fecha, s.hora, s.numero_discurso, s.estado,
           o.nombre AS orador_nombre, o.apellido AS orador_apellido, o.telefono,
           c.nombre AS cong_destino, c.coord_nombre, c.dia_reunion, c.hora_reunion,
           d.ruta_archivo, d.cancion
    FROM solicitudes s
    INNER JOIN oradores o ON s.orador_id = o.id
    INNER JOIN congregaciones c ON s.congregacion_solicitante_id = c.id
    LEFT JOIN discursos d ON s.orador_id = d.orador_id AND s.numero_discurso = d.numero_discurso
    WHERE o.congregacion_id = :mi_id 
    AND s.congregacion_solicitante_id != :mi_id
    AND {$condicion_fecha}
    ORDER BY s.fecha ASC
";
$stmt_sal = $conn->prepare($sql_salidas);
$stmt_sal->execute($params_sal);
$salidas = $stmt_sal->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Maestro de Arreglos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .panel-grid { display: grid; grid-template-columns: 1fr; gap: 30px; margin-top: 20px; }
        @media(min-width: 800px) { .panel-grid { grid-template-columns: 1fr 1fr; } }
        .seccion-titulo { padding: 15px; color: white; border-radius: 8px 8px 0 0; font-size: 1.2em; text-align: center; margin: 0; }
        .bg-entradas { background-color: #27ae60; }
        .bg-salidas { background-color: #2980b9; }
        .seccion-cuerpo { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); }
        .card-arreglo { border: 1px solid #eee; border-left: 5px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 6px; background: #fafafa; }
        .card-aprobado { border-left-color: #2ecc71; }
        .card-pendiente { border-left-color: #f1c40f; }
        .badge-estado { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .badge-Aprobado { background: #d4edda; color: #155724; }
        .badge-Pendiente { background: #fff3cd; color: #856404; }
        .btn-wa { color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85em; display: inline-block; margin-top: 10px; font-weight: bold; }
        .btn-verde { background: #25D366; }
        .btn-naranja { background: #f39c12; }
        .btn-descarga { background: #9b59b6; }
        .filtro-bar { background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); border: 1px solid #ddd; flex-wrap: wrap; justify-content: center; }
        .filtro-bar input[type="date"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: #2c3e50; font-family: inherit; }
        .btn-filtro { background: #34495e; color: white; border: none; padding: 9px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.95em; transition: 0.2s; }
        .btn-filtro:hover { background: #2c3e50; }
        .btn-limpiar { background: #ecf0f1; color: #7f8c8d; text-decoration: none; padding: 9px 15px; border-radius: 4px; font-size: 0.9em; border: 1px solid #ccc; transition: 0.2s; font-weight: bold; }
        .btn-limpiar:hover { background: #e0e6ed; color: #2c3e50; }
        .bosquejo-info { display: flex; align-items: center; gap: 10px; margin: 5px 0; flex-wrap: wrap; }
        .dist-center { max-width: 1200px; margin: 20px auto; background: white; padding: 15px; border-radius: 8px; border: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .qr-popover { position: absolute; background: white; border: 2px solid #34495e; padding: 15px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); z-index: 100; text-align: center; }

        @media (max-width: 768px) {
            .header a { display: block !important; margin: 10px auto !important; padding: 12px !important; background-color: rgba(255, 255, 255, 0.15) !important; border-radius: 8px !important; width: 90% !important; box-sizing: border-box !important; text-decoration: none !important; text-align: center; }
            .filtro-bar { flex-direction: column; align-items: stretch; }
            .filtro-bar div { display: flex; justify-content: space-between; width: 100%; }
            .filtro-bar .btn-filtro, .filtro-bar .btn-limpiar, .filtro-bar a[target="_blank"] { width: 100%; text-align: center; box-sizing: border-box; margin: 5px 0 0 0 !important; }
            .dist-center { flex-direction: column; text-align: center; }
            .dist-center div { justify-content: center; flex-wrap: wrap; width: 100%; }
            .dist-center button { width: 100%; margin-bottom: 5px; }
        }
    </style>
</head>

<body style="background: #ecf0f1;">
    <header class="header" style="background: #2c3e50; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Panel Maestro de Arreglos</h1>
        <p style="margin: 5px 0 0 0;">Congregación: <strong><?php echo htmlspecialchars($mi_cong['nombre']); ?></strong></p>
        <p style="margin-top: 10px;">
            <a href="dashboard.php" style="color: #bdc3c7; text-decoration: underline; margin-right:15px;">Volver al Panel</a>
            <a href="gestionar_hogares.php" style="color: #bdc3c7; text-decoration: underline;">🏠 Gestionar Hospitalidad</a>
            <a href="directorio_congregaciones.php" style="color: #f1c40f; text-decoration: none; font-weight: bold; padding: 5px 10px; border: 1px solid #f1c40f; border-radius: 4px;">📖 Directorio y Agendas</a>
        </p>
    </header>

    <main style="padding: 20px; max-width: 1200px; margin: 0 auto;">

        <div class="dist-center">
            <div style="display: flex; gap: 10px; align-items: center;">
                <button onclick="toggleQR()" style="background: #8e44ad; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold;">🔳 Ver Código QR</button>
                <button onclick="copiarEnlaceServicio()" style="background: #34495e; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: bold;">🔗 Copiar Enlace Servicio</button>
                <span id="aviso-copiado" style="display: none; color: #27ae60; font-weight: bold; font-size: 0.9em;">¡Copiado!</span>
            </div>
            <div id="qr-area" class="qr-popover" style="display: none;">
                <h4 style="margin: 0 0 10px 0;">Acceso Departamentos</h4>
                <img id="qr-img" src="" alt="QR Code">
                <p style="font-size: 0.8em; color: #666; margin: 10px 0;">Escanea para ver el programa<br>y descargar archivos.</p>
                <button onclick="window.print()" style="font-size: 0.7em; cursor: pointer; padding: 5px 10px;">Imprimir</button>
                <button onclick="toggleQR()" style="font-size: 0.7em; cursor: pointer; padding: 5px 10px; margin-left: 5px;">Cerrar</button>
            </div>
            <div style="font-size: 0.85em; color: #7f8c8d;">
                Comparte este link con Sonido y Hospitalidad.
            </div>
        </div>

        <script>
            function getServiceURL() {
                const idCong = <?php echo $mi_cong_id; ?>;
                const urlBase = window.location.origin + window.location.pathname.replace('control_arreglos.php', 'vista_servicio.php');
                return urlBase + '?cong_id=' + idCong;
            }
            function toggleQR() {
                const area = document.getElementById('qr-area');
                const img = document.getElementById('qr-img');
                if (area.style.display === 'none') {
                    const url = getServiceURL();
                    const qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" + encodeURIComponent(url);
                    img.src = qrApiUrl;
                    area.style.display = 'block';
                } else {
                    area.style.display = 'none';
                }
            }
            function copiarEnlaceServicio() {
                const url = getServiceURL();
                navigator.clipboard.writeText(url).then(() => {
                    const aviso = document.getElementById('aviso-copiado');
                    aviso.style.display = 'inline';
                    setTimeout(() => { aviso.style.display = 'none'; }, 2000);
                });
            }
        </script>

        <form method="GET" class="filtro-bar">
            <label style="font-weight: bold; color: #2c3e50; font-size: 1.1em; margin-right: 10px;">📅 Filtrar Fechas:</label>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="desde" style="font-size: 0.9em; color: #555; font-weight: bold;">Desde</label>
                <input type="date" id="desde" name="desde" value="<?php echo $fecha_desde; ?>">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="hasta" style="font-size: 0.9em; color: #555; font-weight: bold;">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="<?php echo $fecha_hasta; ?>">
            </div>
            <button type="submit" class="btn-filtro">🔍 Buscar</button>
            <?php if (isset($_GET['desde']) || isset($_GET['hasta'])): ?>
                <a href="control_arreglos.php" class="btn-limpiar">✖ Limpiar</a>
            <?php endif; ?>
            <a href="generar_pdf_anuncios.php?desde=<?php echo $fecha_desde; ?>&hasta=<?php echo $fecha_hasta; ?>"
                target="_blank"
                style="background: #e67e22; color: white; text-decoration: none; padding: 9px 20px; border-radius: 4px; font-weight: bold; margin-left: auto; box-shadow: 0 2px 4px rgba(230,126,34,0.3);">
                🖨️ Generar PDF Cartelera
            </a>
        </form>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'bloqueado_verde'): ?>
            <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; box-shadow: 0 4px 6px rgba(231, 76, 60, 0.3);">
                🔒 ¡Acción Denegada! Ya hay un arreglo APROBADO (Verde) para esta fecha. Debes cancelarlo primero.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['exito']) && $_GET['exito'] == 'actualizado'): ?>
            <div style="background: #f39c12; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
                🔄 ¡Arreglo actualizado! El orador anterior fue reemplazado por el nuevo correctamente.
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['exito']) && $_GET['exito'] == 'nuevo'): ?>
            <div style="background: #27ae60; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold;">
                ✅ ¡Solicitud guardada correctamente en un día libre!
            </div>
        <?php endif; ?>

        <div class="panel-grid">
            <div>
                <h2 class="seccion-titulo bg-entradas">📥 Programa en mi Salón</h2>
                <div class="seccion-cuerpo">
                    <?php if (count($entradas) > 0): ?>
                        <?php foreach ($entradas as $e): ?>
                            <?php $es_local = ($e['orador_cong_id'] == $mi_cong_id); ?>

                            <div class="card-arreglo card-<?php echo strtolower($e['estado']); ?>"
                                style="<?php echo $es_local ? 'background-color: #f0fdf4; border: 1px solid #c3e6cb; border-left: 5px solid #28a745;' : ''; ?>">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <strong><?php echo date("d/m/Y", strtotime($e['fecha'])); ?></strong>
                                    <span class="badge-estado badge-<?php echo $e['estado']; ?>"><?php echo $e['estado']; ?></span>
                                </div>
                                <p style="margin: 0 0 5px 0; font-size: 1.1em; color: #2c3e50;">
                                    <strong><?php echo htmlspecialchars($e['orador_nombre'] . " " . $e['orador_apellido']); ?></strong>
                                </p>
                                <p style="margin: 0; font-size: 0.9em; color: #555;">
                                    <?php echo $es_local ? '🏠 Orador Local' : 'De: Cong. ' . htmlspecialchars($e['cong_origen']); ?>
                                </p>
                                <div class="bosquejo-info">
                                    <span style="font-size: 0.9em;">Bosquejo N° <?php echo $e['numero_discurso']; ?></span>
                                    <?php if (!empty($e['cancion'])): ?>
                                        <span style="font-size: 0.85em; color: #8e44ad; background: #f4ecf7; padding: 2px 8px; border-radius: 12px; font-weight: bold;">🎵 Cant. <?php echo $e['cancion']; ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($e['ruta_archivo'])): ?>
                                        <a href="<?php echo htmlspecialchars($e['ruta_archivo']); ?>" download class="btn-wa btn-descarga">📦 Bajar RAR</a>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$es_local): ?>
                                    <?php if ($e['estado'] == 'Pendiente'): ?>
                                        <?php if (!empty($e['coord_telefono'])):
                                            $num_c = formatearTelefonoWA($e['coord_telefono']);
                                            $txt_c = rawurlencode("Saludos hermano " . $e['coord_nombre'] . ", le escribo de " . $mi_cong['nombre'] . ". Tenemos una solicitud PENDIENTE para el hermano " . $e['orador_nombre'] . " el " . date("d/m", strtotime($e['fecha'])) . ". ¿Podría aprobarla?");
                                            ?>
                                            <a href="https://api.whatsapp.com/send?phone=<?php echo $num_c; ?>&text=<?php echo $txt_c; ?>" target="_blank" class="btn-wa btn-naranja">📲 Preguntar Coordinador</a>
                                        <?php endif; ?>

                                    <?php elseif ($e['estado'] == 'Aprobado'): ?>
                                        <?php if (!empty($e['telefono'])):
                                            $num_o = formatearTelefonoWA($e['telefono']);
                                            $link_gps = "https://www.google.com/maps?q=" . $mi_cong['latitud'] . "," . $mi_cong['longitud'];
                                            $txt_cancion = !empty($e['cancion']) ? " y la canción que selecciono usted es la N° " . $e['cancion'] . " para el cántico Inicial" : "";
                                            
                                            // INYECCIÓN DINÁMICA DE DÍA Y HORA DEL PERFIL LOCAL
                                            $dia_reunion_txt = !empty($mi_cong['dia_reunion']) ? $mi_cong['dia_reunion'] . " " : "";
                                            $hora_reunion_txt = !empty($mi_cong['hora_reunion']) ? date("h:i A", strtotime($mi_cong['hora_reunion'])) : date("h:i A", strtotime($e['hora']));

                                            $txt_o = rawurlencode("Hola hermano " . $e['orador_nombre'] . ".\n\nLo esperamos en " . $mi_cong['nombre'] . " el " . $dia_reunion_txt . date("d/m", strtotime($e['fecha'])) . " a las " . $hora_reunion_txt . ".\n\nTendrá el bosquejo N° " . $e['numero_discurso'] . $txt_cancion . ".\n\n📍 Dirección: " . $mi_cong['ubicacion_texto'] . "\n🌍 GPS: " . $link_gps . "\n\n¿Usará imágenes? ¿Necesitará hospedaje o comida?");
                                            ?>
                                            <a href="https://api.whatsapp.com/send?phone=<?php echo $num_o; ?>&text=<?php echo $txt_o; ?>" target="_blank" class="btn-wa btn-verde">📲 Escribir al Invitado</a>
                                        <?php endif; ?>

                                        <div style="background-color: #e8f4f8; padding: 12px; border-radius: 6px; margin-top: 15px; border: 1px solid #bce8f1;">
                                            <form action="guardar_hospitalidad.php" method="POST" style="display:flex; flex-direction:column; gap:8px;">
                                                <input type="hidden" name="solicitud_id" value="<?php echo $e['id']; ?>">
                                                <div style="display:flex; justify-content:space-between; align-items:center; font-size: 0.9em;">
                                                    <label>Almuerzo:</label>
                                                    <select name="almuerzo_id" style="width: 65%;">
                                                        <option value="">-- Sin asignar --</option>
                                                        <?php foreach ($hogares_almuerzo as $h): ?>
                                                            <option value="<?php echo $h['id']; ?>" <?php echo ($e['hogar_almuerzo_id'] == $h['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($h['nombre_familia']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div style="display:flex; justify-content:space-between; align-items:center; font-size: 0.9em;">
                                                    <label>Hospedaje:</label>
                                                    <select name="hospedaje_id" style="width: 65%;">
                                                        <option value="">-- Sin asignar --</option>
                                                        <?php foreach ($hogares_hospedaje as $h): ?>
                                                            <option value="<?php echo $h['id']; ?>" <?php echo ($e['hogar_hospedaje_id'] == $h['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($h['nombre_familia']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" style="background:#3498db; color:white; border:none; padding:6px; border-radius:4px; cursor:pointer;">💾 Guardar / Actualizar</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; padding:20px; color:#666;">Sin arreglos en estas fechas.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <h2 class="seccion-titulo bg-salidas">📤 Hermanos que Enviamos</h2>
                <div class="seccion-cuerpo">
                    <?php if (count($salidas) > 0): ?>
                        <?php foreach ($salidas as $s): ?>
                            <div class="card-arreglo card-<?php echo strtolower($s['estado']); ?>">
                                <div style="display: flex; justify-content: space-between;">
                                    <strong><?php echo date("d/m/Y", strtotime($s['fecha'])); ?></strong>
                                    <span class="badge-estado badge-<?php echo $s['estado']; ?>"><?php echo $s['estado']; ?></span>
                                </div>
                                <p style="margin: 10px 0 5px 0;">
                                    <strong><?php echo htmlspecialchars($s['orador_nombre'] . " " . $s['orador_apellido']); ?></strong>
                                </p>
                                <p style="margin: 0; font-size: 0.9em;">Hacia: <?php echo htmlspecialchars($s['cong_destino']); ?></p>
                                <div class="bosquejo-info">
                                    <span>Bosquejo N° <?php echo $s['numero_discurso']; ?></span>
                                    <?php if (!empty($s['cancion'])): ?><span style="color:#8e44ad; font-weight:bold;">🎵 Cant. <?php echo $s['cancion']; ?></span><?php endif; ?>
                                </div>
                                <?php if (!empty($s['telefono'])):
                                    $num_s = formatearTelefonoWA($s['telefono']);
                                    
                                    // INYECCIÓN DINÁMICA DE DÍA Y HORA DE LA CONGREGACIÓN DESTINO
                                    $dia_reunion_dest = !empty($s['dia_reunion']) ? $s['dia_reunion'] . " " : "";
                                    $hora_reunion_dest = !empty($s['hora_reunion']) ? date("h:i A", strtotime($s['hora_reunion'])) : date("h:i A", strtotime($s['hora']));

                                    $txt_s = rawurlencode("Hermano " . $s['orador_nombre'] . ", le recuerdo su salida el " . $dia_reunion_dest . date("d/m", strtotime($s['fecha'])) . " a las " . $hora_reunion_dest . " a la congregación " . $s['cong_destino'] . ". Bosquejo N° " . $s['numero_discurso'] . ($s['cancion'] ? " con canción " . $s['cancion'] : "") . ".");
                                    ?>
                                    <a href="https://api.whatsapp.com/send?phone=<?php echo $num_s; ?>&text=<?php echo $txt_s; ?>" target="_blank" class="btn-wa btn-verde">📲 Recordar Salida</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; padding:20px; color:#666;">No hay salidas programadas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>

</html>