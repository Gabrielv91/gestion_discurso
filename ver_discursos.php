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

// 2. Obtener los discursos unidos al Catálogo para ver el TEMA
$sql_discursos = "
    SELECT d.*, cat.tema 
    FROM discursos d 
    LEFT JOIN catalogo_discursos cat ON d.numero_discurso = cat.numero 
    WHERE d.orador_id = :orador_id 
    ORDER BY d.numero_discurso ASC
";
$stmt_discursos = $conn->prepare($sql_discursos);
$stmt_discursos->execute([':orador_id' => $orador_id]);
$discursos = $stmt_discursos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recursos de <?php echo htmlspecialchars($orador['nombre']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.6em; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; }
        
        .container { max-width: 1000px; margin: 30px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .tabla-recursos { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla-recursos th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #2c3e50; font-size: 0.9em; }
        .tabla-recursos td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        .tema-txt { font-weight: bold; color: #2980b9; display: block; margin-bottom: 3px; }
        .num-txt { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; font-weight: bold; color: #555; }
        
        .badge-file { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.75em; font-weight: bold; display: inline-block; }
        .btn-save { background: #27ae60; color: white; border: none; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 1.1em; cursor: pointer; width: 100%; margin-top: 25px; transition: 0.3s; }
        .btn-save:hover { background: #219150; box-shadow: 0 4px 8px rgba(39,174,96,0.3); }
        
        select, input[type="file"] { padding: 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; width: 100%; box-sizing: border-box; }
        .eliminar-box { margin-top: 5px; font-size: 0.8em; color: #e74c3c; cursor: pointer; display: flex; align-items: center; gap: 4px; }
    </style>
</head>
<body>

    <header class="header">
        <h1>Multimedia y Canciones</h1>
        <p style="margin: 5px 0 0 0;">Orador: <strong><?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></strong></p>
        <p><a href="oradores.php">⬅ Volver a la Lista</a></p>
    </header>

    <div class="container">
        <h2 style="margin-top: 0; color: #2c3e50;">Asignación Masiva</h2>
        <p style="color: #7f8c8d; font-size: 0.95em;">Completa la información de cada tema. Al terminar, haz clic en el botón verde inferior para guardar todos los cambios simultáneamente.</p>

        <?php if (count($discursos) > 0): ?>
            <form action="actualizar_discurso.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="orador_id" value="<?php echo $orador_id; ?>">
                
                <table class="tabla-recursos">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Bosquejo y Tema</th>
                            <th style="width: 20%;">Cántico Inicial</th>
                            <th style="width: 35%;">Recurso Multimedia (.ZIP / .RAR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discursos as $d): ?>
                            <tr>
                                <td>
                                    <span class="num-txt">N° <?php echo $d['numero_discurso']; ?></span><br>
                                    <span class="tema-txt"><?php echo htmlspecialchars($d['tema'] ?? 'Sin título en catálogo'); ?></span>
                                </td>
                                
                                <td>
                                    <select name="cancion[<?php echo $d['id']; ?>]">
                                        <option value="">-- Sin canción --</option>
                                        <?php for($i = 1; $i <= 162; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($d['cancion'] == $i) ? 'selected' : ''; ?>>
                                                Canción <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                
                                <td>
                                    <?php if (!empty($d['ruta_archivo'])): ?>
                                        <div class="badge-file">✅ Archivo en el servidor</div>
                                        <label class="eliminar-box">
                                            <input type="checkbox" name="eliminar_archivo[<?php echo $d['id']; ?>]" value="1"> Eliminar archivo actual
                                        </label>
                                        <p style="font-size: 0.75em; color: #7f8c8d; margin: 4px 0;">Sube uno nuevo para reemplazarlo:</p>
                                    <?php endif; ?>
                                    
                                    <input type="file" name="archivo_multimedia_<?php echo $d['id']; ?>" accept=".zip,.rar">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" class="btn-save">💾 Guardar Todos los Cambios</button>
            </form>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                <p>Este orador aún no tiene bosquejos registrados en su perfil.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>