<?php
// temas_especiales.php
session_start();
require_once 'conexion/conexion.php';

// 1. Validar que la persona haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}


// NOTA: Revisa cómo se llama exactamente tu rol de admin en la base de datos.
// Si tu rol se llama distinto (ej: 'Admin' o 'SuperAdmin'), cámbialo en la línea de abajo.
if ($_SESSION['rol'] != 'Administrador' && $_SESSION['rol'] != 'Admin') {
    // Si es un coordinador normal, lo mandamos de vuelta a su panel
    header("Location: dashboard.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$mensaje = '';

// PROCESAR ACTUALIZACIÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $temas = $_POST['temas']; // Array con los números de discurso como clave y el nuevo título como valor
    
    $sql_update = "UPDATE catalogo_discursos SET tema = :tema WHERE numero = :numero";
    $stmt_update = $conn->prepare($sql_update);
    
    $exito = true;
    foreach ($temas as $numero => $nuevo_tema) {
        if (!$stmt_update->execute([':tema' => trim($nuevo_tema), ':numero' => $numero])) {
            $exito = false;
        }
    }
    
    if ($exito) {
        $mensaje = "<div class='alerta-exito'>✅ Temas anuales actualizados correctamente en todo el sistema.</div>";
    } else {
        $mensaje = "<div class='alerta-error'>❌ Hubo un error al guardar los temas.</div>";
    }
}

// OBTENER LOS TEMAS ESPECIALES ACTUALES (Números 500 en adelante)
$sql = "SELECT numero, tema FROM catalogo_discursos WHERE numero >= 500 ORDER BY numero ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$especiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si por alguna razón no existen, los creamos por defecto para que no de error
if (count($especiales) == 0) {
    $conn->query("INSERT IGNORE INTO catalogo_discursos (numero, tema) VALUES (501, 'Discurso Especial (Actualizar Tema)'), (502, 'Conmemoración (Actualizar Tema)')");
    header("Refresh:0"); // Recarga la página para mostrarlos
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Anual - Temas Especiales</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; }
        .header p { margin: 5px 0 0 0; color: #bdc3c7; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; }
        .header a:hover { text-decoration: underline; }

        .container { max-width: 700px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .form-group { margin-bottom: 25px; background: #fdf2e9; border-left: 4px solid #e67e22; padding: 15px; border-radius: 0 6px 6px 0; }
        .form-group label { display: block; font-weight: bold; color: #d35400; margin-bottom: 8px; font-size: 1.1em; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; font-size: 1em; background: #fff; transition: 0.3s; }
        .form-group input:focus { border-color: #e67e22; outline: none; box-shadow: 0 0 5px rgba(230,126,34,0.3); }
        
        .btn-guardar { background: #d35400; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; width: 100%; transition: 0.3s; }
        .btn-guardar:hover { background: #ba4a00; }
        
        .alerta-exito { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;}
        .alerta-error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;}
    </style>
</head>
<body>

    <header class="header">
        <h1>🌟 Configuración de Campañas</h1>
        <p>Actualiza los temas para los eventos especiales del año en curso</p>
        <p><a href="dashboard.php">⬅ Volver al Panel Maestro</a></p>
    </header>

    <div class="container">
        <?php echo $mensaje; ?>
        
        <p style="color: #7f8c8d; margin-bottom: 25px; line-height: 1.5;">Cuando la sucursal anuncie el nuevo bosquejo para el discurso especial, escribe el título aquí. Esto actualizará automáticamente el nombre en la lista de bosquejos de todos tus oradores.</p>

        <form action="" method="POST">
            <?php foreach ($especiales as $especial): ?>
                <?php 
                    // Ponerle una etiqueta amigable dependiendo del número
                    $etiqueta = "Tema del Evento N° " . $especial['numero'];
                    if ($especial['numero'] == 501) $etiqueta = "Título del Discurso Especial (Campaña)";
                    if ($especial['numero'] == 502) $etiqueta = "Título del Discurso de Conmemoración";
                ?>
                <div class="form-group">
                    <label><?php echo $etiqueta; ?>:</label>
                    <input type="text" name="temas[<?php echo $especial['numero']; ?>]" value="<?php echo htmlspecialchars($especial['tema']); ?>" required>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-guardar">💾 Guardar Títulos de este Año</button>
        </form>
    </div>

</body>
</html>