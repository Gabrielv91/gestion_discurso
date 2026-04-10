<?php
// crear_solicitud_arreglo.php
session_start();
require_once 'conexion/conexion.php';

$orador_id = intval($_GET['orador_id']);
$fecha_predefinida = isset($_GET['fecha']) ? $_GET['fecha'] : '';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// Datos del orador
$sql = "SELECT o.*, c.nombre as cong_nombre FROM oradores o INNER JOIN congregaciones c ON o.congregacion_id = c.id WHERE o.id = :id";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $orador_id]);
$orador = $stmt->fetch();

// Sus discursos
$sql_d = "SELECT numero_discurso FROM discursos WHERE orador_id = :id";
$stmt_d = $conn->prepare($sql_d);
$stmt_d->execute([':id' => $orador_id]);
$discursos = $stmt_d->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Solicitud</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header style="background-color: #8e44ad;">
        <h1>Solicitar a <?php echo $orador['nombre']; ?></h1>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container" style="max-width: 500px;">
            <form action="guardar_solicitud_arreglo.php" method="POST">
                <input type="hidden" name="orador_id" value="<?php echo $orador_id; ?>">

                <label>Tema:</label>
                <select name="numero_discurso" required style="width: 100%; margin-bottom: 15px; padding: 8px;">
                    <?php foreach($discursos as $d): ?>
                        <option value="<?php echo $d['numero_discurso']; ?>">Bosquejo N° <?php echo $d['numero_discurso']; ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Fecha (Sábados/Domingos):</label>
                <input type="date" name="fecha" id="fecha" value="<?php echo $fecha_predefinida; ?>" min="<?php echo date('Y-m-d'); ?>" required style="width: 100%; margin-bottom: 15px; padding: 8px;">

                <label>Hora:</label>
                <input type="time" name="hora" required style="width: 100%; margin-bottom: 15px; padding: 8px;">

                <button type="submit" style="width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Confirmar Arreglo
                </button>
            </form>
        </div>
    </main>

    <script>
        document.getElementById('fecha').addEventListener('change', function() {
            const date = new Date(this.value + 'T00:00:00');
            const day = date.getDay(); // 0 Domingo, 6 Sábado
            if (day !== 0 && day !== 6) {
                alert("Por favor selecciona un sábado o domingo.");
                this.value = '';
            }
        });
    </script>
</body>
</html>     