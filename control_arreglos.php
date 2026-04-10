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

function formatearTelefonoWA($numero) {
    $limpio = preg_replace('/[^0-9]/', '', $numero);
    if (substr($limpio, 0, 1) === '0') { return '58' . substr($limpio, 1); } 
    elseif (strlen($limpio) == 10 && substr($limpio, 0, 2) !== '58') { return '58' . $limpio; }
    return $limpio;
}

$sql_mi_cong = "SELECT id, nombre, ubicacion_texto FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_cong_id = $mi_cong['id'];

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

// BLOQUE 1: ENTRADAS (Incluye d.cancion)
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

// BLOQUE 2: SALIDAS (Incluye d.cancion)
$sql_salidas = "
    SELECT s.id, s.fecha, s.hora, s.numero_discurso, s.estado,
           o.nombre AS orador_nombre, o.apellido AS orador_apellido, o.telefono,
           c.nombre AS cong_destino, c.coord_nombre,
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
        .seccion-cuerpo { background: white; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card-arreglo { border: 1px solid #eee; border-left: 5px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 6px; background: #fafafa; }
        .card-aprobado { border-left-color: #2ecc71; }
        .card-pendiente { border-left-color: #f1c40f; }
        .badge-estado { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; }
        .badge-Aprobado { background: #d4edda; color: #155724; }
        .badge-Pendiente { background: #fff3cd; color: #856404; }
        .btn-wa { color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 0.85em; display: inline-block; margin-top: 10px; font-weight: bold;}
        .btn-verde { background: #25D366; }
        .btn-naranja { background: #f39c12; }
        .btn-descarga { background: #9b59b6; }
        
        .filtro-bar { background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #ddd; flex-wrap: wrap; justify-content: center; }
        .filtro-bar input[type="date"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; color: #2c3e50; font-family: inherit; }
        .btn-filtro { background: #34495e; color: white; border: none; padding: 9px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.95em; transition: 0.2s;}
        .btn-filtro:hover { background: #2c3e50; }
        .btn-limpiar { background: #ecf0f1; color: #7f8c8d; text-decoration: none; padding: 9px 15px; border-radius: 4px; font-size: 0.9em; border: 1px solid #ccc; transition: 0.2s; font-weight: bold; }
        .btn-limpiar:hover { background: #e0e6ed; color: #2c3e50; }
        
        .bosquejo-info { display: flex; align-items: center; gap: 10px; margin: 5px 0; flex-wrap: wrap; }
    </style>
</head>
<body style="background: #ecf0f1;">
    <header style="background: #2c3e50; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0;">Panel Maestro de Arreglos</h1>
        <p style="margin: 5px 0 0 0;">Congregación: <strong><?php echo htmlspecialchars($mi_cong['nombre']); ?></strong></p>
        <p style="margin-top: 10px;">
            <a href="dashboard.php" style="color: #bdc3c7; text-decoration: underline; margin-right:15px;">Volver al Panel</a>
            <a href="gestionar_hogares.php" style="color: #bdc3c7; text-decoration: underline;">🏠 Gestionar Hospitalidad</a>
        </p>
    </header>

    <main style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        
        <?php if(isset($_GET['mensaje']) && $_GET['mensaje'] == 'hospitalidad_guardada'): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; text-align: center; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                ✅ Arreglo de hospitalidad actualizado.
            </div>
        <?php endif; ?>

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
        </form>

        <div class="panel-grid">
            
            <div>
                <h2 class="seccion-titulo bg-entradas">📥 Programa en mi Salón</h2>
                <div class="seccion-cuerpo">
                    <?php if (count($entradas) > 0): ?>
                        <?php foreach ($entradas as $e): ?>
                            <?php $es_local = ($e['orador_cong_id'] == $mi_cong_id); ?>
                            
                            <div class="card-arreglo card-<?php echo strtolower($e['estado']); ?>" style="<?php echo $es_local ? 'background-color: #f0fdf4; border: 1px solid #c3e6cb; border-left: 5px solid #28a745;' : ''; ?>">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <strong><?php echo date("d/m/Y", strtotime($e['fecha'])); ?></strong>
                                    <span class="badge-estado badge-<?php echo $e['estado']; ?>"><?php echo $e['estado']; ?></span>
                                </div>
                                <p style="margin: 0 0 5px 0; font-size: 1.1em; color: #2c3e50;"><strong><?php echo htmlspecialchars($e['orador_nombre'] . " " . $e['orador_apellido']); ?></strong></p>
                                
                                <?php if ($es_local): ?>
                                    <p style="margin: 0; font-size: 0.85em; color: #27ae60; font-weight: bold;">🏠 Orador Local</p>
                                <?php else: ?>
                                    <p style="margin: 0; font-size: 0.9em; color: #555;">De: Cong. <?php echo htmlspecialchars($e['cong_origen']); ?></p>
                                <?php endif; ?>

                                <div class="bosquejo-info">
                                    <span style="font-size: 0.9em;">Bosquejo N° <?php echo $e['numero_discurso']; ?></span>
                                    
                                    <?php if (!empty($e['cancion'])): ?>
                                        <span style="font-size: 0.85em; color: #8e44ad; background: #f4ecf7; padding: 2px 8px; border-radius: 12px; font-weight: bold;">🎵 Canción <?php echo $e['cancion']; ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($e['ruta_archivo'])): ?>
                                        <a href="<?php echo htmlspecialchars($e['ruta_archivo']); ?>" download class="btn-wa btn-descarga">📦 Descargar Paquete</a>
                                    <?php else: ?>
                                        <span style="font-size: 0.8em; color: #e74c3c; background: #fadbd8; padding: 2px 6px; border-radius: 4px;">Sin paquete adjunto</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!$es_local): ?>
                                    <?php if ($e['estado'] == 'Pendiente'): ?>
                                        <?php if (!empty($e['coord_telefono'])): ?>
                                            <?php 
                                                $numero_seguro = formatearTelefonoWA($e['coord_telefono']);
                                                $msg_coord = "Saludos hermano " . $e['coord_nombre'] . ", le escribo de la congregación " . $mi_cong['nombre'] . ". Tenemos una solicitud PENDIENTE para el hermano " . $e['orador_nombre'] . " el día " . date("d/m", strtotime($e['fecha'])) . ". ¿Será posible que apruebe el arreglo?";
                                                $url_wa_coord = "https://api.whatsapp.com/send?phone=" . $numero_seguro . "&text=" . rawurlencode($msg_coord);
                                            ?>
                                            <a href="<?php echo $url_wa_coord; ?>" target="_blank" class="btn-wa btn-naranja">📲 Preguntar al Coordinador</a>
                                        <?php endif; ?>

                                    <?php elseif ($e['estado'] == 'Aprobado'): ?>
                                        <?php if (!empty($e['telefono'])): ?>
                                            <?php 
                                                $numero_seguro = formatearTelefonoWA($e['telefono']);
                                                $hora_formato = date("h:i A", strtotime($e['hora']));
                                                
                                                // LÓGICA DE CANCIÓN PARA EL MENSAJE
                                                $texto_cancion_wa = !empty($e['cancion']) ? " y la canción que selecciono usted es la N° " . $e['cancion'] . " para el cántico Inicial" : "";
                                                
                                                $msg_invitado = "Hola hermano " . $e['orador_nombre'] . ".\n\nLo esperamos con alegría en la congregación " . $mi_cong['nombre'] . " este " . date("d/m", strtotime($e['fecha'])) . " a las " . $hora_formato . ".\n\nTendrá a su cargo el bosquejo N° " . $e['numero_discurso'] . $texto_cancion_wa . ".\n\n📍 La dirección de nuestro salón es: " . $mi_cong['ubicacion_texto'] . "\n\nAdicionalmente quería consultarle: ¿Va a utilizar las imágenes que su coordinador seleccionó para el discurso? Y para poder organizar todo, ¿necesitará hospedaje o arreglo de comida?";
                                                
                                                $url_wa_invitado = "https://api.whatsapp.com/send?phone=" . $numero_seguro . "&text=" . rawurlencode($msg_invitado);
                                            ?>
                                            <a href="<?php echo $url_wa_invitado; ?>" target="_blank" class="btn-wa btn-verde">📲 Escribir al Invitado</a>
                                        <?php endif; ?>

                                        <div style="background-color: #e8f4f8; padding: 12px; border-radius: 6px; margin-top: 15px; border: 1px solid #bce8f1;">
                                            <h4 style="margin: 0 0 10px 0; color: #31708f; font-size: 0.95em;">🍽️ Arreglo de Hospitalidad</h4>
                                            
                                            <form action="guardar_hospitalidad.php" method="POST" style="display:flex; flex-direction:column; gap:8px;">
                                                <input type="hidden" name="solicitud_id" value="<?php echo $e['id']; ?>">

                                                <div style="display:flex; justify-content:space-between; align-items:center; font-size: 0.9em;">
                                                    <label style="color:#333;">Almuerzo:</label>
                                                    <select name="almuerzo_id" style="padding:4px; border-radius:4px; border:1px solid #ccc; width: 65%;">
                                                        <option value="">-- Sin asignar --</option>
                                                        <?php foreach ($hogares_almuerzo as $h): ?>
                                                            <option value="<?php echo $h['id']; ?>" <?php echo ($e['hogar_almuerzo_id'] == $h['id']) ? 'selected' : ''; ?>>
                                                                Fam. <?php echo htmlspecialchars($h['nombre_familia']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div style="display:flex; justify-content:space-between; align-items:center; font-size: 0.9em;">
                                                    <label style="color:#333;">Hospedaje:</label>
                                                    <select name="hospedaje_id" style="padding:4px; border-radius:4px; border:1px solid #ccc; width: 65%;">
                                                        <option value="">-- Sin asignar --</option>
                                                        <?php foreach ($hogares_hospedaje as $h): ?>
                                                            <option value="<?php echo $h['id']; ?>" <?php echo ($e['hogar_hospedaje_id'] == $h['id']) ? 'selected' : ''; ?>>
                                                                Fam. <?php echo htmlspecialchars($h['nombre_familia']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <button type="submit" style="background-color: #3498db; color: white; border: none; padding: 6px; border-radius: 4px; cursor: pointer; margin-top: 5px; font-weight: bold; font-size: 0.85em;">💾 Guardar / Actualizar</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; background: white; border-radius: 8px; border: 1px dashed #ccc;">
                            <p style="color: #7f8c8d; margin: 0;">No se encontraron arreglos en este rango de fechas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <h2 class="seccion-titulo bg-salidas">📤 Hermanos que Enviamos</h2>
                <div class="seccion-cuerpo">
                    <?php if (count($salidas) > 0): ?>
                        <?php foreach ($salidas as $s): ?>
                            <div class="card-arreglo card-<?php echo strtolower($s['estado']); ?>">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <strong><?php echo date("d/m/Y", strtotime($s['fecha'])); ?></strong>
                                    <span class="badge-estado badge-<?php echo $s['estado']; ?>"><?php echo $s['estado']; ?></span>
                                </div>
                                <p style="margin: 0 0 5px 0; font-size: 1.1em; color: #2c3e50;"><strong><?php echo htmlspecialchars($s['orador_nombre'] . " " . $s['orador_apellido']); ?></strong></p>
                                <p style="margin: 0; font-size: 0.9em; color: #555;">Hacia: Cong. <?php echo htmlspecialchars($s['cong_destino']); ?></p>
                                
                                <div class="bosquejo-info">
                                    <span style="font-size: 0.9em;">Bosquejo N° <?php echo $s['numero_discurso']; ?></span>
                                    
                                    <?php if (!empty($s['cancion'])): ?>
                                        <span style="font-size: 0.85em; color: #8e44ad; background: #f4ecf7; padding: 2px 8px; border-radius: 12px; font-weight: bold;">🎵 Canción <?php echo $s['cancion']; ?></span>
                                    <?php endif; ?>

                                    <?php if (!empty($s['ruta_archivo'])): ?>
                                        <span style="font-size: 0.8em; color: #27ae60; background: #e8f8f5; padding: 2px 6px; border-radius: 4px;">✓ Paquete enviado</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($s['telefono'])): ?>
                                    <?php 
                                        $numero_seguro = formatearTelefonoWA($s['telefono']);
                                        
                                        // LÓGICA DE CANCIÓN PARA RECORDATORIO DE SALIDA
                                        $texto_cancion_salida = !empty($s['cancion']) ? " con la canción N° " . $s['cancion'] : "";
                                        
                                        $msg_sal = "Hermano " . $s['orador_nombre'] . ", le recuerdo su salida el " . date("d/m", strtotime($s['fecha'])) . " a la congregación " . $s['cong_destino'] . ".\n\nTiene asignado el bosquejo N° " . $s['numero_discurso'] . $texto_cancion_salida . ".";
                                        
                                        $url_wa_sal = "https://api.whatsapp.com/send?phone=" . $numero_seguro . "&text=" . rawurlencode($msg_sal);
                                    ?>
                                    <a href="<?php echo $url_wa_sal; ?>" target="_blank" class="btn-wa btn-verde">📲 Recordar Salida</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; background: white; border-radius: 8px; border: 1px dashed #ccc;">
                            <p style="color: #7f8c8d; margin: 0;">No hay salidas programadas en este rango de fechas.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>