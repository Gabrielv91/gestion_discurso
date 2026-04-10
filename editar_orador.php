<?php
// editar_orador.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: oradores.php");
    exit();
}

$orador_id = intval($_GET['id']);
$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// 1. Obtener datos del orador (Asegúrate de que la consulta traiga todo, incluyendo 'telefono')
$sql_orador = "SELECT * FROM oradores WHERE id = :id AND congregacion_id = (SELECT id FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1)";
$stmt_orador = $conn->prepare($sql_orador);
$stmt_orador->execute([':id' => $orador_id, ':usuario_id' => $_SESSION['usuario_id']]);
$orador = $stmt_orador->fetch(PDO::FETCH_ASSOC);

if (!$orador) {
    die("Orador no encontrado o no tienes permisos para editarlo.");
}

// 2. Obtener los discursos actuales de este orador (CORREGIDO: apuntando a 'orador_discursos')
$sql_discursos = "SELECT numero_discurso FROM discursos WHERE orador_id = :orador_id";
$stmt_discursos = $conn->prepare($sql_discursos);
$stmt_discursos->execute([':orador_id' => $orador_id]);
$discursos_actuales = $stmt_discursos->fetchAll(PDO::FETCH_COLUMN); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Orador</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .caja-discursos {
            border: 1px solid #ccc; 
            padding: 15px; 
            border-radius: 4px;
            background-color: #fcfcfc;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); 
            gap: 10px 5px; 
            max-height: 250px; 
            overflow-y: auto;
        }
        .caja-discursos label {
            display: flex;
            align-items: center;
            font-size: 0.9em;
            cursor: pointer;
            color: #333;
        }
        .caja-discursos input[type="checkbox"] {
            margin-right: 4px;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <h1>Editar Orador: <?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></h1>
        <p><a href="oradores.php" style="color: white; text-decoration: underline;">Volver a Oradores</a></p>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container">
            <form action="actualizar_orador.php" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="hidden" name="orador_id" value="<?php echo $orador['id']; ?>">

                <div class="input-group">
                    <label for="nombre">Nombres:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($orador['nombre']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="apellido">Apellidos:</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($orador['apellido']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="espiritualidad">Espiritualidad:</label>
                    <select id="espiritualidad" name="espiritualidad" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="Anciano" <?php echo ($orador['espiritualidad'] == 'Anciano') ? 'selected' : ''; ?>>Anciano</option>
                        <option value="Siervo Ministerial" <?php echo ($orador['espiritualidad'] == 'Siervo Ministerial') ? 'selected' : ''; ?>>Siervo Ministerial</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label for="telefono">Teléfono (WhatsApp):</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($orador['telefono'] ?? ''); ?>" placeholder="Ej: 04141234567" required>
                </div>
                
                <div class="input-group">
                    <label for="estado">Estado en el sistema:</label>
                    <select id="estado" name="estado" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="Activo" <?php echo ($orador['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo (Disponible para salir)</option>
                        <option value="Inactivo" <?php echo ($orador['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo (Suspendido temporalmente)</option>
                    </select>
                </div>

                <div class="input-group" style="grid-column: 1 / -1; margin-top: 10px;">
                    <label style="font-size: 1.1em; font-weight: bold; margin-bottom: 10px; display: block; color: #2c3e50;">
                        Modificar Bosquejos Preparados
                    </label>
                    <div class="caja-discursos">
                        <?php for ($i = 1; $i <= 194; $i++): ?>
                            <?php 
                            // Verificamos si este número está en la base de datos para marcarlo
                            $marcado = in_array($i, $discursos_actuales) ? 'checked' : ''; 
                            ?>
                            <label>
                                <input type="checkbox" name="discursos_seleccionados[]" value="<?php echo $i; ?>" <?php echo $marcado; ?>> 
                                <?php echo $i; ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <button type="submit" class="btn-aprobar" style="grid-column: 1 / -1; padding: 12px; margin-top: 10px;">Actualizar Orador</button>
            </form>
        </div>
    </main>
</body>
</html>