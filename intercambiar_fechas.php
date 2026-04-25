<?php
// intercambiar_fechas.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// Obtener mi ID de congregación
$stmt_mi = $conn->prepare("SELECT id FROM congregaciones WHERE usuario_id = ?");
$stmt_mi->execute([$usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_id = $mi_cong['id'];

$mensaje = '';

// Procesar el intercambio si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['discurso_1']) && isset($_POST['discurso_2'])) {
    $id_1 = $_POST['discurso_1'];
    $id_2 = $_POST['discurso_2'];

    if ($id_1 == $id_2) {
        $mensaje = "<div class='alerta-error'>❌ No puedes seleccionar el mismo discurso en ambas casillas.</div>";
    } else {
        // Obtener las fechas actuales de ambos discursos
        $stmt_fechas = $conn->prepare("SELECT id, fecha FROM solicitudes WHERE id IN (?, ?) AND congregacion_solicitante_id = ?");
        $stmt_fechas->execute([$id_1, $id_2, $mi_id]);
        $discursos = $stmt_fechas->fetchAll(PDO::FETCH_ASSOC);

        if (count($discursos) == 2) {
            // Identificar qué fecha es de quién
            $fecha_1 = ($discursos[0]['id'] == $id_1) ? $discursos[0]['fecha'] : $discursos[1]['fecha'];
            $fecha_2 = ($discursos[0]['id'] == $id_2) ? $discursos[0]['fecha'] : $discursos[1]['fecha'];

            // Hacer el intercambio (Update cruzado)
            $conn->beginTransaction();
            try {
                $conn->prepare("UPDATE solicitudes SET fecha = ? WHERE id = ?")->execute([$fecha_2, $id_1]);
                $conn->prepare("UPDATE solicitudes SET fecha = ? WHERE id = ?")->execute([$fecha_1, $id_2]);
                $conn->commit();
                $mensaje = "<div class='alerta-exito'>✅ ¡Fechas intercambiadas con éxito! Revisa tu calendario.</div>";
            } catch (Exception $e) {
                $conn->rollBack();
                $mensaje = "<div class='alerta-error'>❌ Error al intercambiar: " . $e->getMessage() . "</div>";
            }
        } else {
            $mensaje = "<div class='alerta-error'>❌ Error: Uno de los discursos no es válido.</div>";
        }
    }
}

// Obtener todos los discursos futuros aprobados/confirmados para llenar las listas
$sql_futuros = "
    SELECT s.id, s.fecha, o.nombre AS orador_nombre, c.nombre AS cong_nombre 
    FROM solicitudes s
    LEFT JOIN oradores o ON s.orador_id = o.id
    LEFT JOIN congregaciones c ON o.congregacion_id = c.id
    WHERE s.congregacion_solicitante_id = ? 
    AND s.fecha >= CURDATE()
    AND s.estado NOT IN ('Cancelado', 'Cancelada', 'Rechazado', 'Rechazada')
    ORDER BY s.fecha ASC
";
$stmt_futuros = $conn->prepare($sql_futuros);
$stmt_futuros->execute([$mi_id]);
$lista_discursos = $stmt_futuros->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intercambiar Fechas | Gestión de Discursos</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #ecf0f1; margin: 0; padding-bottom: 40px; color: #333;}
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header a { color: #bdc3c7; text-decoration: underline; font-size: 0.9em; }
        .container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #9b59b6; }
        
        label { display: block; font-weight: bold; color: #34495e; margin-bottom: 8px; }
        select { width: 100%; padding: 12px; border: 2px solid #bdc3c7; border-radius: 8px; font-size: 1em; box-sizing: border-box; margin-bottom: 20px; background: #fdfdfd; cursor: pointer; transition: 0.3s;}
        select:focus { border-color: #9b59b6; outline: none; }
        
        .icon-swap { text-align: center; font-size: 2em; color: #9b59b6; margin: -10px 0 10px 0; }
        
        .btn-submit { background: #9b59b6; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 1.1em; transition: 0.3s; margin-top: 10px;}
        .btn-submit:hover { background: #8e44ad; }
        
        .alerta-exito { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; border: 1px solid #c3e6cb;}
        .alerta-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; border: 1px solid #f5c6cb;}
        
        .info-box { background: #e8f4f8; padding: 15px; border-radius: 8px; border-left: 4px solid #3498db; margin-bottom: 25px; font-size: 0.9em; color: #2c3e50;}
    </style>
</head>
<body>

    <header class="header">
        <h1 style="margin: 0 0 10px 0;">🔄 Intercambiar Discursos</h1>
        <a href="dashboard.php">⬅ Volver al Panel Maestro</a>
    </header>

    <div class="container">
        <?php echo $mensaje; ?>

        <div class="card">
            <div class="info-box">
                <strong>¿Un orador no puede venir pero mandará a otro en su lugar?</strong> <br>
                Selecciona a los dos hermanos que van a intercambiar fechas. El sistema cruzará sus días automáticamente en el calendario.
            </div>

            <form action="" method="POST">
                
                <label>Orador A (El que cede su fecha):</label>
                <select name="discurso_1" required>
                    <option value="">-- Selecciona un discurso programado --</option>
                    <?php foreach($lista_discursos as $d): ?>
                        <option value="<?php echo $d['id']; ?>">
                            📅 <?php echo $d['fecha']; ?> - 🗣️ <?php echo htmlspecialchars($d['orador_nombre']); ?> (<?php echo htmlspecialchars($d['cong_nombre']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="icon-swap">⇅</div>

                <label>Orador B (El que toma el lugar del primero):</label>
                <select name="discurso_2" required>
                    <option value="">-- Selecciona un discurso programado --</option>
                    <?php foreach($lista_discursos as $d): ?>
                        <option value="<?php echo $d['id']; ?>">
                            📅 <?php echo $d['fecha']; ?> - 🗣️ <?php echo htmlspecialchars($d['orador_nombre']); ?> (<?php echo htmlspecialchars($d['cong_nombre']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-submit">Realizar Intercambio</button>
            </form>
        </div>
    </div>

</body>
</html>