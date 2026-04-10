<?php
// ver_discursos.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['orador_id'])) {
    header("Location: oradores.php");
    exit();
}

$orador_id = intval($_GET['orador_id']);
$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// 1. Obtener datos del orador
$sql_orador = "SELECT nombre, apellido FROM oradores WHERE id = :id AND congregacion_id = (SELECT id FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1)";
$stmt_orador = $conn->prepare($sql_orador);
$stmt_orador->execute([':id' => $orador_id, ':usuario_id' => $_SESSION['usuario_id']]);
$orador = $stmt_orador->fetch(PDO::FETCH_ASSOC);

if (!$orador) {
    die("Orador no encontrado o no pertenece a tu congregación.");
}

// 2. Obtener los discursos de este orador
$sql_discursos = "SELECT * FROM discursos WHERE orador_id = :orador_id ORDER BY numero_discurso ASC";
$stmt_discursos = $conn->prepare($sql_discursos);
$stmt_discursos->execute([':orador_id' => $orador_id]);
$discursos = $stmt_discursos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discursos de <?php echo htmlspecialchars($orador['nombre']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Temas de: <?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></h1>
        <p><a href="oradores.php" style="color: white; text-decoration: underline;">Volver a Oradores</a></p>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container" style="max-width: 1000px;">
            <h2>Asignar Canciones y Archivos Masivamente</h2>
            <p style="margin-bottom: 20px; color: #666;">Selecciona las canciones, sube los archivos .ZIP/.RAR o marca las casillas para eliminar. Al final, presiona el botón para <strong>guardar todo a la vez</strong>.</p>

            <?php if (count($discursos) > 0): ?>
                <form action="actualizar_discurso.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="orador_id" value="<?php echo $orador_id; ?>">
                    
                    <table class="tabla-admin">
                        <thead>
                            <tr>
                                <th>Bosquejo</th>
                                <th>Canción</th>
                                <th>Archivo (.ZIP / .RAR)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discursos as $discurso): ?>
                                <tr>
                                    <td><strong>N° <?php echo htmlspecialchars($discurso['numero_discurso']); ?></strong></td>
                                    
                                    <td>
                                        <select name="cancion[<?php echo $discurso['id']; ?>]" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                            <option value="">Sin canción</option>
                                            <?php for($i = 1; $i <= 162; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($discurso['cancion'] == $i) ? 'selected' : ''; ?>>
                                                    Canción <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                    
                                    <td>
                                        <?php if (!empty($discurso['ruta_archivo'])): ?>
                                            <p style="font-size: 0.85em; color: green; margin-bottom: 2px;">✓ Paquete subido</p>
                                            <label style="font-size: 0.8em; color: #e74c3c; display: block; margin-bottom: 8px;">
                                                <input type="checkbox" name="eliminar_archivo[<?php echo $discurso['id']; ?>]" value="1"> Eliminar paquete
                                            </label>
                                        <?php endif; ?>
                                        
                                        <input type="file" name="archivo_multimedia_<?php echo $discurso['id']; ?>" accept=".zip,.rar,application/zip,application/x-rar-compressed" style="font-size: 0.85em; max-width: 200px;">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <button type="submit" class="btn-aprobar" style="margin-top: 20px; width: 100%; padding: 15px; font-size: 1.1em; background-color: #27ae60;">Guardar Todos los Cambios</button>
                </form>
            <?php else: ?>
                <div class="mensaje-vacio">
                    <p>Este orador aún no tiene discursos asignados.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>