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
// Usamos YEARWEEK() que agrupa los días por semana (Lunes a Domingo)
// -------------------------------------------------------------------------
$sql_cupo = "SELECT COUNT(DISTINCT s.orador_id) FROM solicitudes s
             INNER JOIN oradores o ON s.orador_id = o.id
             WHERE o.congregacion_id = :target_id
             AND s.estado IN ('Aprobado', 'Pendiente')
             AND YEARWEEK(s.fecha, 1) = YEARWEEK(:fecha, 1)";
$stmt_cupo = $conn->prepare($sql_cupo);
$stmt_cupo->execute([':target_id' => $congregacion_objetivo_id, ':fecha' => $fecha_buscada]);
$oradores_fuera_este_finde = $stmt_cupo->fetchColumn();

// Si ya hay 2 oradores fuera, activamos el bloqueo
$bloqueo_regla_5 = ($oradores_fuera_este_finde >= 2);
$lista_oradores = [];

// Solo si NO está bloqueada por la Regla 5, buscamos a los oradores
if (!$bloqueo_regla_5) {
    
    // -------------------------------------------------------------------------
    // CONSULTA MAESTRA (Aplica Reglas 2, 3 y 4)
    // -------------------------------------------------------------------------
    $sql_oradores = "
        SELECT o.id AS orador_id, o.nombre, o.apellido, o.espiritualidad,
               d.numero_discurso
        FROM oradores o
        INNER JOIN discursos d ON o.id = d.orador_id
        WHERE o.congregacion_id = :target_id
        AND o.estado = 'Activo'

        -- 🟠 REGLA 2: El ORADOR no ha dado discurso en mi congregación en 90 días (6 meses)
        AND o.id NOT IN (
            SELECT orador_id FROM solicitudes 
            WHERE congregacion_solicitante_id = :mi_cong_id 
            AND estado IN ('Aprobado', 'Pendiente')
            AND ABS(DATEDIFF(fecha, :fecha)) <= 180
        )

        -- 🟠 REGLA 3: EL NÚMERO DE BOSQUEJO no se ha dado en mi congregación en 90 días (6 meses)
        AND d.numero_discurso NOT IN (
            SELECT numero_discurso FROM solicitudes 
            WHERE congregacion_solicitante_id = :mi_cong_id 
            AND estado IN ('Aprobado', 'Pendiente')
            AND ABS(DATEDIFF(fecha, :fecha)) <= 180
        )

        -- 🔴 REGLA 4: Un orador no puede salir más de 1 vez al mes (Mismo mes y año)
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

    // Agrupar los resultados
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
        $lista_oradores[$oid]['discursos'][] = $row['numero_discurso'];
    }
}

$fecha_formateada = date("d/m/Y", strtotime($fecha_buscada));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Oradores Disponibles</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tarjeta-orador { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .tarjeta-orador h3 { margin-top: 0; color: #2c3e50; margin-bottom: 5px; }
        .etiqueta-espir { display: inline-block; background-color: #ecf0f1; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; color: #7f8c8d; margin-bottom: 10px; }
        .form-solicitud { display: flex; gap: 10px; align-items: center; margin-top: 10px; background-color: #f9f9f9; padding: 10px; border-radius: 6px; }
        .select-discurso { padding: 8px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1; }
        .btn-pedir { background-color: #27ae60; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-pedir:hover { background-color: #219653; }
        .alerta-bloqueo { background-color: #fdeded; border-left: 5px solid #e74c3c; padding: 20px; border-radius: 4px; text-align: center; margin-top: 20px;}
    </style>
</head>
<body style="background-color: #f4f6f7;">
    <header style="background-color: #2c3e50;">
        <h1>Oradores Disponibles</h1>
        <p>De: <strong><?php echo htmlspecialchars($cong_objetivo['nombre']); ?></strong> | Para el: <strong><?php echo $fecha_formateada; ?></strong></p>
        <p><a href="buscar_arreglos.php?fecha=<?php echo $fecha_buscada; ?>" style="color: white; text-decoration: underline;">Volver a Congregaciones</a></p>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container" style="max-width: 800px; margin: 0 auto;">

            <?php if ($bloqueo_regla_5): ?>
                <div class="alerta-bloqueo">
                    <h2 style="color: #c0392b; margin-top: 0;">🛑 Límite de Congregación Alcanzado</h2>
                    <p style="font-size: 1.1em; color: #333;">La congregación <strong><?php echo htmlspecialchars($cong_objetivo['nombre']); ?></strong> ya tiene a <strong>2 oradores</strong> programados para salir este fin de semana.</p>
                    <p style="color: #666; font-size: 0.9em;">Por normas organizativas, no se pueden solicitar más hermanos de esta congregación para la misma fecha.</p>
                    <br>
                    <a href="buscar_arreglos.php?fecha=<?php echo $fecha_buscada; ?>" class="btn-ver" style="background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">Buscar en otra congregación</a>
                </div>

            <?php else: ?>
                <div style="background-color: #e8f4f8; border-left: 4px solid #3498db; padding: 10px 15px; margin-bottom: 20px; font-size: 0.9em; color: #2c3e50;">
                    ℹ️ <strong>Filtros Activos:</strong> Se han ocultado hermanos/bosquejos que visitaron tu congregación en los últimos 3 meses, y hermanos que ya tienen otra asignación este mes.
                </div>

                <?php if (count($lista_oradores) > 0): ?>
                    <?php foreach ($lista_oradores as $id => $orador): ?>
                        <div class="tarjeta-orador">
                            <h3><?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></h3>
                            <span class="etiqueta-espir"><?php echo htmlspecialchars($orador['espiritualidad']); ?></span>
                            
                            <form action="procesar_solicitud.php" method="POST" class="form-solicitud">
                                <input type="hidden" name="orador_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="fecha" value="<?php echo $fecha_buscada; ?>">
                                <input type="hidden" name="mi_cong_id" value="<?php echo $mi_cong_id; ?>">
                                <input type="hidden" name="hora" value="09:00:00">
                                
                                <label for="discurso_<?php echo $id; ?>" style="font-weight: bold; font-size: 0.9em;">Elegir Bosquejo:</label>
                                <select name="numero_discurso" id="discurso_<?php echo $id; ?>" class="select-discurso" required>
                                    <option value="" disabled selected>-- Seleccione un bosquejo --</option>
                                    <?php foreach ($orador['discursos'] as $num): ?>
                                        <option value="<?php echo $num; ?>">Bosquejo Nº <?php echo $num; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <button type="submit" class="btn-pedir">Agendar Orador</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="mensaje-vacio" style="text-align: center; padding: 40px; background-color: white; border-radius: 8px; border: 1px dashed #ccc;">
                        <h2 style="color: #e74c3c;">Sin disponibilidad</h2>
                        <p>No hay oradores en esta congregación que cumplan con la regla de los 3 meses o que tengan el mes libre.</p>
                        <a href="buscar_arreglos.php?fecha=<?php echo $fecha_buscada; ?>" class="btn-ver" style="display: inline-block; margin-top: 15px; background-color: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Buscar en otra congregación</a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>
</body>
</html>