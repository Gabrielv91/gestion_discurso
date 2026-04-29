<?php
// generar_pdf_anuncios.php
session_start(); // ¡Clave! Arrancar la sesión para saber quién está conectado
require_once 'conexion/conexion.php';

// Si no hay sesión, pa' fuera
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado. Inicie sesión primero.");
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. OBTENER EL ID DE LA CONGREGACIÓN Y SU NOMBRE
$sql_mi_cong = "SELECT id, nombre FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);

if (!$mi_cong) {
    die("Error: No se encontró la congregación asociada a este usuario.");
}

$mi_cong_id = $mi_cong['id'];
$nombre_mi_cong = $mi_cong['nombre']; // Usaremos esto para el título del PDF

date_default_timezone_set('America/Caracas');
$fecha_hoy = date('Y-m-d');

// CAPTURAMOS LOS FILTROS
$fecha_desde = isset($_GET['desde']) && !empty($_GET['desde']) ? $_GET['desde'] : $fecha_hoy;
$fecha_hasta = isset($_GET['hasta']) && !empty($_GET['hasta']) ? $_GET['hasta'] : '';

// 1. CONSULTA: ENTRADAS (Visitas con Hospitalidad)
$sql_ent = "
    SELECT s.fecha, s.hora, s.numero_discurso,
           o.nombre AS orador, o.apellido,
           c_origen.nombre AS cong_origen,
           d.cancion, cat.tema AS tema_oficial,
           h_alm.nombre_familia AS fam_almuerzo,
           h_hosp.nombre_familia AS fam_hospedaje
    FROM solicitudes s
    INNER JOIN oradores o ON s.orador_id = o.id
    INNER JOIN congregaciones c_origen ON o.congregacion_id = c_origen.id
    LEFT JOIN discursos d ON s.orador_id = d.orador_id AND s.numero_discurso = d.numero_discurso
    LEFT JOIN catalogo_discursos cat ON s.numero_discurso = cat.numero
    LEFT JOIN hogares h_alm ON s.hogar_almuerzo_id = h_alm.id
    LEFT JOIN hogares h_hosp ON s.hogar_hospedaje_id = h_hosp.id
    WHERE s.congregacion_solicitante_id = :mi_id 
    AND s.fecha >= :desde " . ($fecha_hasta ? "AND s.fecha <= :hasta " : "") . "
    AND s.estado = 'Aprobado'
    ORDER BY s.fecha ASC
";
$stmt_ent = $conn->prepare($sql_ent);
$params = [':mi_id' => $mi_cong_id, ':desde' => $fecha_desde];
if($fecha_hasta) $params[':hasta'] = $fecha_hasta;
$stmt_ent->execute($params);
$entradas = $stmt_ent->fetchAll(PDO::FETCH_ASSOC);

// 2. CONSULTA: SALIDAS
$sql_sal = "
    SELECT s.fecha, o.nombre AS orador, o.apellido,
           c_dest.nombre AS cong_destino,
           cat.tema AS tema_oficial, s.numero_discurso
    FROM solicitudes s
    INNER JOIN oradores o ON s.orador_id = o.id
    INNER JOIN congregaciones c_dest ON s.congregacion_solicitante_id = c_dest.id
    LEFT JOIN catalogo_discursos cat ON s.numero_discurso = cat.numero
    WHERE o.congregacion_id = :mi_id 
    AND s.congregacion_solicitante_id != :mi_id
    AND s.fecha >= :desde " . ($fecha_hasta ? "AND s.fecha <= :hasta " : "") . "
    AND s.estado = 'Aprobado'
    ORDER BY s.fecha ASC
";
$stmt_sal = $conn->prepare($sql_sal);
$stmt_sal->execute($params);
$salidas = $stmt_sal->fetchAll(PDO::FETCH_ASSOC);

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programa de Discursos - <?php echo htmlspecialchars($nombre_mi_cong); ?></title>
    <style>
        @page { size: letter; margin: 10mm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: white; color: #333; font-size: 10pt; }
        
        .header { text-align: center; border-bottom: 3px solid #2980b9; margin-bottom: 15px; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2c3e50; font-size: 1.6em; text-transform: uppercase; }
        
        .main-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 15px; }
        
        .column-title { background: #2c3e50; color: white; padding: 6px; text-align: center; font-weight: bold; border-radius: 4px; margin-bottom: 10px; font-size: 0.9em; text-transform: uppercase; }
        
        /* TARJETAS COMPACTAS */
        .card { border: 1px solid #ddd; border-radius: 6px; margin-bottom: 8px; padding: 8px; position: relative; }
        .fecha-line { display: flex; justify-content: space-between; font-weight: bold; color: #e74c3c; border-bottom: 1px solid #eee; margin-bottom: 5px; font-size: 0.85em; }
        
        .tema-txt { font-weight: bold; color: #2980b9; margin: 3px 0; line-height: 1.2; font-size: 0.95em; }
        .orador-txt { font-weight: bold; margin-bottom: 2px; }
        .info-secundaria { font-size: 0.8em; color: #666; font-style: italic; }
        
        /* HOSPITALIDAD COMPACTA */
        .hosp-box { margin-top: 5px; background: #f9f9f9; padding: 4px 8px; border-radius: 4px; border-left: 3px solid #27ae60; font-size: 0.8em; display: flex; gap: 10px; }
        .hosp-item { display: flex; align-items: center; gap: 4px; }

        .no-print { text-align: center; padding: 10px; margin-bottom: 10px; background: #eee; border-radius: 5px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" style="padding: 10px 20px; cursor:pointer;">🖨️ IMPRIMIR CARTELERA</button>
</div>

<div class="header">
    <h1>Programa de Reuniones del Fin de Semana</h1>
    <p style="margin:0; font-weight:bold; color: #7f8c8d;">Congregación <?php echo htmlspecialchars($nombre_mi_cong); ?></p>
</div>

<div class="main-grid">
    <div>
        <div class="column-title">📥 Visitas en Nuestro Salón</div>
        <?php foreach($entradas as $e): ?>
        <div class="card">
            <div class="fecha-line">
                <span>DOMINGO <?php echo date("d/m", strtotime($e['fecha'])); ?></span>
                <span><?php echo date("h:i A", strtotime($e['hora'])); ?></span>
            </div>
            <div class="tema-txt">"<?php echo $e['tema_oficial'] ? $e['tema_oficial'] : 'Bosquejo N° '.$e['numero_discurso']; ?>"</div>
            <div class="orador-txt"><?php echo htmlspecialchars($e['orador'] . " " . $e['apellido']); ?></div>
            <div class="info-secundaria">Cong. <?php echo htmlspecialchars($e['cong_origen']); ?> • 🎵 Cántico <?php echo $e['cancion']; ?></div>
            
            <div class="hosp-box">
                <div class="hosp-item">🍽️ <b>Comida:</b> <?php echo $e['fam_almuerzo'] ? $e['fam_almuerzo'] : '---'; ?></div>
                <div class="hosp-item">🏠 <b>Hospedaje:</b> <?php echo $e['fam_hospedaje'] ? $e['fam_hospedaje'] : 'No'; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div>
        <div class="column-title">📤 Hermanos que Enviamos</div>
        <?php foreach($salidas as $s): ?>
        <div class="card" style="border-left: 4px solid #3498db;">
            <div class="fecha-line" style="color: #3498db;">
                <span>DOMINGO <?php echo date("d/m", strtotime($s['fecha'])); ?></span>
            </div>
            <div class="orador-txt"><?php echo htmlspecialchars($s['orador'] . " " . $s['apellido']); ?></div>
            <div class="info-secundaria" style="margin-bottom:4px;">Hacia: <?php echo htmlspecialchars($s['cong_destino']); ?></div>
            <div style="font-size: 0.75em; border-top: 1px solid #f0f0f0; padding-top:4px;">
                <b>Tema:</b> <?php echo $s['tema_oficial'] ? $s['tema_oficial'] : 'Bosquejo '.$s['numero_discurso']; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>