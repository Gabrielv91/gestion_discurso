<?php
// editar_perfil.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// Obtener los datos actuales
$sql = "SELECT * FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$perfil) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Congregación</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Editar Datos de la Congregación</h1>
        <p><a href="dashboard.php" style="color: white; text-decoration: underline;">Volver al Panel</a></p>
    </header>

    <main class="admin-container" style="margin-top: 20px;">
        <form action="actualizar_perfil.php" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                
                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="nombre">Nombre de la Congregación:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($perfil['nombre']); ?>" required>
                </div>

                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="ubicacion">Ubicación (Dirección en texto):</label>
                    <input type="text" id="ubicacion" name="ubicacion_texto" value="<?php echo htmlspecialchars($perfil['ubicacion_texto']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="latitud">Latitud:</label>
                    <input type="number" step="any" id="latitud" name="latitud" value="<?php echo htmlspecialchars($perfil['latitud']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="longitud">Longitud:</label>
                    <input type="number" step="any" id="longitud" name="longitud" value="<?php echo htmlspecialchars($perfil['longitud']); ?>" required>
                </div>

                <h3 style="grid-column: 1 / -1; margin-top: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Datos del Coordinador</h3>

                <div class="input-group">
                    <label for="coord_nombre">Nombres:</label>
                    <input type="text" id="coord_nombre" name="coord_nombre" value="<?php echo htmlspecialchars($perfil['coord_nombre']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="coord_apellido">Apellidos:</label>
                    <input type="text" id="coord_apellido" name="coord_apellido" value="<?php echo htmlspecialchars($perfil['coord_apellido']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="coord_telefono">Teléfono:</label>
                    <input type="text" id="coord_telefono" name="coord_telefono" value="<?php echo htmlspecialchars($perfil['coord_telefono']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="coord_correo">Correo Electrónico:</label>
                    <input type="email" id="coord_correo" name="coord_correo" value="<?php echo htmlspecialchars($perfil['coord_correo']); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn-aprobar" style="width: 100%; margin-top: 20px; padding: 12px; font-size: 1.1em;">Guardar Cambios</button>
        </form>
    </main>
</body>
</html>