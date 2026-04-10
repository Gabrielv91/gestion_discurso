<?php
// dashboard.php
session_start();

// Si no hay una sesión activa, lo regresamos al login por seguridad
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$rol_usuario = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Panel de Control - <?php echo $rol_usuario; ?></h1>
        <p>Bienvenido, <?php echo $_SESSION['codigo_usuario']; ?> | <a href="logout.php" style="color: #ffcccc;">Cerrar Sesión</a></p>
    </header>

    <main style="padding: 20px;">
        <?php
        // Mostramos contenido diferente según el rol
        if ($rol_usuario == 'Administrador') {
            
            // Incluimos la vista del administrador
            include 'vistas/admin_panel.php';
            
        } else if ($rol_usuario == 'Coordinador') {
            
            // ¡AQUÍ ESTÁ LA MAGIA! Incluimos la vista del coordinador
            include 'vistas/coord_panel.php';
            
        }
        ?>
    </main>
</body>
</html>