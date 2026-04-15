<?php
// vista_servicio.php
require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

$mi_cong_id = isset($_GET['cong_id']) ? intval($_GET['cong_id']) : 0;

if ($mi_cong_id === 0) {
    die("<h2 style='text-align:center; color:white; margin-top:50px;'>⚠️ Error: No se especificó congregación.</h2>");
}

$sql_cong = "SELECT nombre FROM congregaciones WHERE id = :id LIMIT 1";
$stmt_c = $conn->prepare($sql_cong);
$stmt_c->execute([':id' => $mi_cong_id]);
$cong_data = $stmt_c->fetch(PDO::FETCH_ASSOC);

date_default_timezone_set('America/Caracas');
$fecha_hoy = date('Y-m-d');

// ... (código anterior igual) ...

$sql = "
    SELECT s.fecha, s.hora, s.numero_discurso,
           o.nombre AS orador, o.apellido,
           c_origen.nombre AS cong_origen,
           d.cancion, d.ruta_archivo,
           cat.tema AS tema_oficial,
           h_alm.nombre_familia AS familia_almuerzo,
           h_hosp.nombre_familia AS familia_hospedaje
    FROM solicitudes s
    INNER JOIN oradores o ON s.orador_id = o.id
    INNER JOIN congregaciones c_origen ON o.congregacion_id = c_origen.id
    LEFT JOIN discursos d ON s.orador_id = d.orador_id AND s.numero_discurso = d.numero_discurso
    LEFT JOIN catalogo_discursos cat ON s.numero_discurso = cat.numero
    LEFT JOIN hogares h_alm ON s.hogar_almuerzo_id = h_alm.id
    LEFT JOIN hogares h_hosp ON s.hogar_hospedaje_id = h_hosp.id
    WHERE s.congregacion_solicitante_id = :mi_id 
    AND s.fecha >= :hoy
    AND s.estado = 'Aprobado'
    ORDER BY s.fecha ASC
    LIMIT 10
";
// ... (sigue el execute y el fetchAll) ...
$stmt = $conn->prepare($sql);
$stmt->execute([':mi_id' => $mi_cong_id, ':hoy' => $fecha_hoy]);
$arreglos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio - <?php echo htmlspecialchars($cong_data['nombre']); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            margin: 0;
            padding: 15px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #1e293b;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #38bdf8;
            margin: 0;
            font-size: 1.8em;
            text-transform: uppercase;
        }

        .card {
            background: #1e293b;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            border: 1px solid #334155;
        }

        .top-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #334155;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .fecha-tag {
            background: #ef4444;
            color: white;
            padding: 5px 12px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .hora-tag {
            color: #94a3b8;
            font-weight: 600;
        }

        .orador-nombre {
            font-size: 1.5em;
            color: #f1f5f9;
            margin: 0;
        }

        .cong-origen {
            color: #38bdf8;
            font-size: 0.9em;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* EL CAMBIO CLAVE: El Grid de Departamentos */
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .dept-card {
            background: #0f172a;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #334155;
        }

        .dept-title {
            color: #94a3b8;
            font-size: 0.75em;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dept-title i {
            color: #38bdf8;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 1.1em;
        }

        .label {
            color: #64748b;
            font-size: 0.9em;
        }

        .value {
            color: #f1f5f9;
            font-weight: bold;
        }

        .highlight {
            color: #fbbf24;
        }

        /* Color naranja para bosquejo y cántico */

        .btn-download {
            display: block;
            width: 100%;
            background: #334155;
            color: white;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: bold;
            border: 1px solid #475569;
            transition: 0.3s;
        }

        .btn-download:hover {
            background: #0284c7;
            border-color: #38bdf8;
        }

        @media (max-width: 600px) {
            .top-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($cong_data['nombre']); ?></h1>
            <small style="color: #64748b;">Panel de Información para Departamentos</small>
        </div>

        <?php if (count($arreglos) > 0): ?>
            <?php foreach ($arreglos as $a): ?>
                <div class="card">
                    <div class="top-info">
                        <span class="fecha-tag">📅 <?php echo date("d/m/Y", strtotime($a['fecha'])); ?></span>
                        <span class="hora-tag">⏰ <?php echo date("h:i A", strtotime($a['hora'])); ?></span>
                    </div>

                    <h2 class="orador-nombre">
                        <?php echo htmlspecialchars($a['orador'] . " " . $a['apellido']); ?>
                    </h2>

                    <div style="color: #38bdf8; font-size: 1.1em; font-weight: bold; margin: 8px 0; font-style: italic;">
                        "
                        <?php echo $a['tema_oficial'] ? htmlspecialchars($a['tema_oficial']) : 'Bosquejo N° ' . $a['numero_discurso']; ?>"
                    </div>

                    <div class="cong-origen">🚩 Proviene de:
                        <?php echo htmlspecialchars($a['cong_origen']); ?>
                    </div>

                    <div class="dept-grid">
                        <div class="dept-card">
                            <div class="dept-title">🔊 AUDIO Y VIDEO</div>
                            <div class="info-row">
                                <span class="label">Bosquejo:</span>
                                <span class="value highlight"><?php echo $a['numero_discurso']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Cántico:</span>
                                <span class="value highlight"><?php echo $a['cancion'] ? $a['cancion'] : '---'; ?></span>
                            </div>
                        </div>

                        <div class="dept-card">
                            <div class="dept-title">🍽️ HOSPITALIDAD</div>
                            <div class="info-row">
                                <span class="label">Almuerzo:</span>
                                <span
                                    class="value"><?php echo $a['familia_almuerzo'] ? "Fam. " . $a['familia_almuerzo'] : '---'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Hospedaje:</span>
                                <span
                                    class="value"><?php echo $a['familia_hospedaje'] ? "Fam. " . $a['familia_hospedaje'] : 'No requiere'; ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($a['ruta_archivo']): ?>
                        <a href="<?php echo htmlspecialchars($a['ruta_archivo']); ?>" download class="btn-download">📦 DESCARGAR
                            IMÁGENES (.RAR)</a>
                    <?php else: ?>
                        <div style="text-align:center; margin-top:15px; font-size:0.85em; color:#475569;">Este orador no requiere
                            recursos multimedia.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; color:#64748b; margin-top:100px;">No hay discursos aprobados próximamente.</p>
        <?php endif; ?>
    </div>

</body>

</html>