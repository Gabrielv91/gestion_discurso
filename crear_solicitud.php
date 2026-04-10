<?php
// crear_solicitud.php
session_start();
require_once 'conexion/conexion.php';

// 1. Verificación de seguridad: Solo coordinadores logueados pueden enviar su perfil
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// 2. Verificar si ya envió una solicitud antes para no duplicar
$check_sql = "SELECT id FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->execute([':uid' => $usuario_id]);

if ($check_stmt->rowCount() > 0) {
    echo "<script>alert('Ya tienes una solicitud de perfil enviada. Por favor, espera la aprobación del Administrador.'); window.location.href='dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Perfil de Congregación</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Configuración de Nueva Congregación</h1>
        <p>Bienvenido, <?php echo $_SESSION['codigo_usuario']; ?>. Completa los datos para activar tu cuenta.</p>
    </header>

    <main class="admin-container" style="margin-top: 20px; max-width: 600px;">
        <div class="mensaje-vacio" style="border-left-color: #3498db; margin-bottom: 20px;">
            <p><strong>Nota:</strong> Al enviar este formulario, tu perfil quedará en revisión. El administrador te dará acceso completo pronto.</p>
        </div>

        <form action="guardar_perfil.php" method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                
                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="nombre">Nombre de la Congregación:</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Centro, Barrancas">
                </div>

                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="ubicacion">Dirección Exacta (Texto):</label>
                    <input type="text" id="ubicacion" name="ubicacion_texto" required placeholder="Calle, Sector, Referencia">
                </div>

                <div class="input-group">
                    <label for="latitud">Latitud:</label>
                    <input type="number" step="any" id="latitud" name="latitud" required placeholder="8.6226">
                </div>

                <div class="input-group">
                    <label for="longitud">Longitud:</label>
                    <input type="number" step="any" id="longitud" name="longitud" required placeholder="-70.2074">
                </div>

                <h3 style="grid-column: 1 / -1; margin-top: 15px; border-bottom: 1px solid #eee;">Datos del Coordinador</h3>

                <div class="input-group">
                    <label for="coord_nombre">Nombre:</label>
                    <input type="text" name="coord_nombre" required>
                </div>

                <div class="input-group">
                    <label for="coord_apellido">Apellido:</label>
                    <input type="text" name="coord_apellido" required>
                </div>

                <div class="input-group">
                    <label for="coord_telefono">Teléfono (WhatsApp):</label>
                    <input type="text" name="coord_telefono" required placeholder="0414-1234567">
                </div>

                <div class="input-group">
                    <label for="coord_correo">Correo:</label>
                    <input type="email" name="coord_correo" required>
                </div>
            </div>

            <button type="submit" class="btn-ingresar" style="margin-top: 20px;">
                Enviar Solicitud de Activación
            </button>
        </form>
    </main>
</body>
</html>