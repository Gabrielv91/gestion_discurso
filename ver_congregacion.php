<?php
// ver_congregacion.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id']) || !isset($_GET['fecha'])) {
    header("Location: calendario_arreglos.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

$usuario_id = $_SESSION['usuario_id'];
$congregacion_objetivo_id = intval($_GET['id']);
$fecha_buscada = $_GET['fecha'];

// 1. Obtener mi ID de congregación
$sql_mi_cong = "SELECT id FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_cong_id = $mi_cong['id'];

// 2. Obtener los datos de la congregación que estamos visitando
$sql_objetivo = "SELECT nombre FROM congregaciones WHERE id = :id LIMIT 1";
$stmt_obj = $conn->prepare($sql_objetivo);
$stmt_obj->execute([':id' => $congregacion_objetivo_id]);
$cong_objetivo = $stmt_obj->fetch(PDO::FETCH_ASSOC);

if (!$cong_objetivo) {
    die("La congregación seleccionada no existe.");
}

// -------------------------------------------------------------------------
// 🔴 REGLA 5: Verificamos si esta congregación ya prestó 2 oradores este fin de semana
// -------------------------------------------------------------------------
$sql_cupo = "SELECT COUNT(DISTINCT s.orador_id) FROM solicitudes s
             INNER JOIN oradores o ON s.orador_id = o.id
             WHERE o.congregacion_id = :target_id
             AND s.estado IN ('Aprobado', 'Pendiente')
             AND YEARWEEK(s.fecha, 1) = YEARWEEK(:fecha, 1)";
$stmt_cupo = $conn->prepare($sql_cupo);
$stmt_cupo->execute([':target_id' => $congregacion_objetivo_id, ':fecha' => $fecha_buscada]);
$oradores_fuera_este_finde = $stmt_cupo->fetchColumn();

$bloqueo_regla_5 = ($oradores_fuera_este_finde >= 2);
$lista_oradores = [];

// Solo si NO está bloqueada por la Regla 5, buscamos a los oradores
if (!$bloqueo_regla_5) {
    
    // -------------------------------------------------------------------------
    // CONSULTA MAESTRA (Con Títulos de Catálogo)
    // -------------------------------------------------------------------------
    $sql_oradores = "
        SELECT o.id AS orador_id, o.nombre, o.apellido, o.espiritualidad,
               d.numero_discurso, cat.tema
        FROM oradores o
        INNER JOIN discursos d ON o.id = d.orador_id
        LEFT JOIN catalogo_discursos cat ON d.numero_discurso = cat.numero
        WHERE o.congregacion_id = :target_id
        AND o.estado = 'Activo'

        -- Regla 2: El ORADOR no ha visitado en 6 meses
        AND o.id NOT IN (
            SELECT orador_id FROM solicitudes 
            WHERE congregacion_solicitante_id = :mi_cong_id 
            AND estado IN ('Aprobado', 'Pendiente')
            AND ABS(DATEDIFF(fecha, :fecha)) <= 180
        )

        -- Regla 3: El BOSQUEJO no se ha dado en 6 meses
        AND d.numero_discurso NOT IN (
            SELECT numero_discurso FROM solicitudes 
            WHERE congregacion_solicitante_id = :mi_cong_id 
            AND estado IN ('Aprobado', 'Pendiente')
            AND ABS(DATEDIFF(fecha, :fecha)) <= 180
        )

        -- Regla 4: No puede salir más de 1 vez al mes
        AND o.id NOT IN (
            SELECT orador_id FROM solicitudes 
            WHERE estado IN ('Aprobado', 'Pendiente')
            AND MONTH(fecha) = MONTH(:fecha)
            AND YEAR(fecha) = YEAR(:fecha)
        )
        ORDER BY o.nombre ASC, d.numero_discurso ASC
    ";

    $stmt_oradores = $conn->prepare($sql_oradores);
    $stmt_oradores->execute([
        ':target_id' => $congregacion_objetivo_id,
        ':mi_cong_id' => $mi_cong_id,
        ':fecha' => $fecha_buscada
    ]);
    $resultados = $stmt_oradores->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar los resultados manteniendo el tema del discurso
    foreach ($resultados as $row) {
        $oid = $row['orador_id'];
        if (!isset($lista_oradores[$oid])) {
            $lista_oradores[$oid] = [
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'espiritualidad' => $row['espiritualidad'],
                'discursos' => []
            ];
        }
        $lista_oradores[$oid]['discursos'][] = [
            'numero' => $row['numero_discurso'],
            'tema' => $row['tema'] ?? 'Sin título'
        ];
    }
}

$fecha_formateada = date("d/m/Y", strtotime($fecha_buscada));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar a <?php echo htmlspecialchars($cong_objetivo['nombre']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.6em; }
        .header p { margin: 5px 0 10px 0; color: #bdc3c7; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.95em; }
        .header a:hover { text-decoration: underline; }

        .container { max-width: 800px; margin: 30px auto; padding: 0 15px; }

        .tarjeta-orador { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eaeaea; transition: transform 0.2s; }
        .tarjeta-orador:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        
        .orador-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 15px; }
        .orador-header h3 { margin: 0; color: #2c3e50; font-size: 1.3em; }
        .etiqueta-espir { background: #e8f4f8; color: #2980b9; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; border: 1px solid #d6eaf8; }
        
        .form-solicitud { display: flex; gap: 15px; align-items: stretch; background: #fdfdfd; padding: 15px; border-radius: 8px; border: 1px dashed #ccc; }
        .select-discurso { flex-grow: 1; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; font-size: 0.95em; background: #fff; outline: none; }
        .select-discurso:focus { border-color: #3498db; box-shadow: 0 0 5px rgba(52, 152, 219, 0.3); }
        
        .btn-pedir { background: #27ae60; color: white; border: none; padding: 0 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1em; transition: 0.3s; white-space: nowrap; }
        .btn-pedir:hover { background: #219150; }

        .alerta-bloqueo { background: #fdeded; border-left: 5px solid #e74c3c; padding: 25px; border-radius: 8px; text-align: center; margin-top: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .btn-ver { background: #3498db; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold; transition: 0.3s; }
        .btn-ver:hover { background: #2980b9; }
        
        .info-box { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px; border-radius: 4px; font-size: 0.9em; color: #2c3e50; line-height: 1.5; }
    </style>
</head>
<body>

    <header class="header">
        <h1>Buscar Orador</h1>
        <p>Congregación: <strong><?php echo htmlspecialchars($cong_objetivo['nombre']); ?></strong> | Fecha: <strong><?php echo $fecha_formateada; ?></strong></p>
        <a href="buscar_arreglos.php?fecha=<?php echo $fecha_buscada; ?>">⬅ Volver al Listado de Congregaciones</a>
    </header>

    <div class="container">

        <?php if ($bloqueo_regla_5): ?>
            <div class="alerta-bloqueo">
                <h2 style="color: #c0392b; margin-top: 0;">🛑 Límite Alcanzado</h2>
                <p style="font-size: 1.1em; color: #333; margin-bottom: 5px;">La congregación <strong><?php echo htmlspecialchars($cong_objetivo['nombre']); ?></strong> ya tiene a <strong>2 oradores</strong> comprometidos para este fin de semana.</p>
                <p style="color: #7f8c8d; font-size: 0.9em;">Por normas organizativas, no se pueden solicitar más hermanos de esta lista para la fecha seleccionada.</p>
                <br>
                <a href="buscar_arreglos.php?fecha=<?php echo $fecha_buscada; ?>" class="btn-ver">Buscar en otra congregación</a>
            </div>

        <?php else: ?>
            <div class="info-box">
                <strong>ℹ️ Filtros Inteligentes Activos:</strong><br>
                El sistema ha ocultado automáticamente a los hermanos que ya te visitaron o que ya dieron sus bosquejos en tu congregación durante los últimos 6 meses. Tampoco verás a los hermanos que ya tienen otra salida programada para este mes.
            </div>

            <?php if (count($lista_oradores) > 0): ?>
                <?php foreach ($lista_oradores as $id => $orador): ?>
                    <div class="tarjeta-orador">
                        <div class="orador-header">
                            <h3>👤 <?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></h3>
                            <span class="etiqueta-espir"><?php echo htmlspecialchars($orador['espiritualidad']); ?></span>
                        </div>
                        
                        <form action="procesar_solicitud.php" method="POST" class="form-solicitud">
                            <input type="hidden" name="orador_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="fecha" value="<?php echo $fecha_buscada; ?>">
                            <input type="hidden" name="mi_cong_id" value="<?php echo $mi_cong_id; ?>">
                            <input type="hidden" name="hora" value="09:00:00">
                            
                            <select name="numero_discurso" class="select-discurso" required>
                                <option value="" disabled selected>-- Seleccione un bosquejo preparado --</option>
                                <?php foreach ($orador['discursos'] as $d): ?>
                                    <?php 
                                        $num = $d['numero'];
                                        $tema = htmlspecialchars($d['tema']);
                                        
                                        // Formateo visual para discursos especiales
                                        if ($num == 501) {
                                            $texto = "🌟 ESPECIAL: " . $tema;
                                            $estilo = "font-weight: bold; color: #d35400;";
                                        } elseif ($num == 502) {
                                            $texto = "🍷 CONMEMORACIÓN: " . $tema;
                                            $estilo = "font-weight: bold; color: #8e44ad;";
                                        } else {
                                            $texto = "Nº " . $num . " - " . $tema;
                                            $estilo = "";
                                        }
                                    ?>
                                    <option value="<?php echo $num; ?>" style="<?php echo $estilo; ?>"><?php echo $texto; ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn-pedir">✉️ Enviar Solicitud</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 20px; background: white; border-radius: 10px; border: 1px dashed #bdc3c7;">
                    <h2 style="color: #e74c3c; margin-top: 0;">No hay oradores disponibles</h2>
                    <p style="color: #7f8c8d;">Todos los hermanos de esta congregación están restringidos por las reglas de tiempo (6 meses) o ya tienen asignaciones este mes.</p>
                    <a href="buscar_arreglos.php?fecha=<?php echo $fecha_buscada; ?>" class="btn-ver" style="margin-top: 15px;">Intentar en otra congregación</a>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</body>
</html>