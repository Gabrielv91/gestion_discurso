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

// 1. Obtener datos del orador
$sql_orador = "SELECT * FROM oradores WHERE id = :id AND congregacion_id = (SELECT id FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1)";
$stmt_orador = $conn->prepare($sql_orador);
$stmt_orador->execute([':id' => $orador_id, ':usuario_id' => $_SESSION['usuario_id']]);
$orador = $stmt_orador->fetch(PDO::FETCH_ASSOC);

if (!$orador) {
    die("Orador no encontrado o no tienes permisos para editarlo.");
}

// 2. Obtener los discursos actuales de este orador
$sql_discursos = "SELECT numero_discurso FROM discursos WHERE orador_id = :orador_id";
$stmt_discursos = $conn->prepare($sql_discursos);
$stmt_discursos->execute([':orador_id' => $orador_id]);
$discursos_actuales = $stmt_discursos->fetchAll(PDO::FETCH_COLUMN); 

// 3. Obtener el Catálogo de Discursos (Para los nombres de los Tooltips)
$sql_cat = "SELECT numero, tema FROM catalogo_discursos ORDER BY numero ASC";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->execute();
$catalogo = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Orador - <?php echo htmlspecialchars($orador['nombre']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.8em; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.9em; display: inline-block; margin-top: 5px; }
        .header a:hover { text-decoration: underline; }

        .container { max-width: 800px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .form-group label { display: block; font-weight: bold; color: #34495e; margin-bottom: 6px; font-size: 0.95em; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; font-size: 1em; background: #fdfdfd; transition: 0.3s; }
        .form-group input:focus, .form-group select:focus { border-color: #3498db; outline: none; background: #fff; }
        
        .full-width { grid-column: 1 / -1; }

       /* CUADRÍCULA DE NÚMEROS COMPACTA */
        .grid-numeros { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(55px, 1fr)); /* Cajas más estrechas */
            gap: 5px; /* Menos espacio entre cajas */
            max-height: 250px; 
            overflow-y: auto; 
            background: #f9f9f9; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
        }
        .item-numero { 
            display: flex; 
            flex-direction: row; /* Fuerza a que el cuadro y el número estén uno al lado del otro */
            align-items: center; 
            justify-content: center; 
            background: white; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            padding: 4px; /* Relleno mucho más pequeño */
            cursor: pointer; 
            font-size: 0.8em; /* Letra un poquito más pequeña */
            transition: 0.2s;
            margin: 0;
            color: #2c3e50;
            white-space: nowrap; /* Evita que el texto se parta en dos líneas */
        }
        .item-numero:hover { background: #ebf5fb; border-color: #3498db; }
        .item-numero input { margin: 0 4px 0 0; cursor: pointer; width: 13px; height: 13px; } /* Checkbox más pequeño */

        .seccion-especial { 
            background: #fdf2e9; 
            border: 1px solid #e67e22; 
            padding: 15px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
        }

        .btn-guardar { background: #27ae60; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1em; width: 100%; transition: 0.3s; margin-top: 10px; }
        .btn-guardar:hover { background: #219150; }
    </style>
</head>
<body>

    <header class="header">
        <h1>Editar Orador</h1>
        <p style="color: #bdc3c7; margin: 5px 0;">Actualizando datos de <strong><?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></strong></p>
        <a href="oradores.php">⬅ Cancelar y volver a la lista</a>
    </header>

    <div class="container">
        <form action="actualizar_orador.php" method="POST">
            <input type="hidden" name="orador_id" value="<?php echo $orador['id']; ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nombres:</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($orador['nombre']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Apellidos:</label>
                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($orador['apellido']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Nombramiento:</label>
                    <select name="espiritualidad" required>
                        <option value="Anciano" <?php echo ($orador['espiritualidad'] == 'Anciano') ? 'selected' : ''; ?>>Anciano</option>
                        <option value="Siervo Ministerial" <?php echo ($orador['espiritualidad'] == 'Siervo Ministerial') ? 'selected' : ''; ?>>Siervo Ministerial</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Teléfono (WhatsApp):</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($orador['telefono'] ?? ''); ?>" placeholder="Ej: 04141234567" required>
                </div>
                
                <div class="form-group full-width" style="border-bottom: 2px solid #ecf0f1; padding-bottom: 20px;">
                    <label>Estado en el sistema:</label>
                    <select name="estado" required>
                        <option value="Activo" <?php echo ($orador['estado'] == 'Activo') ? 'selected' : ''; ?>>✅ Activo (Disponible para dar discursos)</option>
                        <option value="Inactivo" <?php echo ($orador['estado'] == 'Inactivo') ? 'selected' : ''; ?>>❌ Inactivo (Suspendido temporalmente)</option>
                    </select>
                    <small style="color: #7f8c8d; display: block; margin-top: 5px;">Si lo marcas como Inactivo, no podrá ser agendado por otras congregaciones.</small>
                </div>

                <div class="form-group full-width">
                    <h3 style="color: #2c3e50; margin-top: 0; margin-bottom: 15px;">Modificar Bosquejos Preparados</h3>

                    <div class="seccion-especial">
                        <label style="font-weight:bold; color:#d35400; display:block; margin-bottom:10px;">🌟 Eventos Especiales:</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            <?php foreach ($catalogo as $t): ?>
                                <?php if($t['numero'] == 501 || $t['numero'] == 502): ?>
                                    <?php 
                                        $etiqueta = ($t['numero'] == 501) ? "Especial" : "Conmemoración";
                                        $marcado = in_array($t['numero'], $discursos_actuales) ? 'checked' : ''; 
                                    ?>
                                    <label class="item-numero" title="<?php echo htmlspecialchars($t['tema']); ?>" style="border-color:#e67e22; color:#d35400; font-weight:bold; padding: 8px 15px;">
                                        <input type="checkbox" name="discursos_seleccionados[]" value="<?php echo $t['numero']; ?>" <?php echo $marcado; ?>>
                                        <?php echo $etiqueta; ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <label style="font-weight:bold; display:block; margin-bottom:10px;">📖 Bosquejos Regulares:</label>
                    <div class="grid-numeros">
                        <?php foreach ($catalogo as $t): ?>
                            <?php if($t['numero'] < 500): ?>
                                <?php $marcado = in_array($t['numero'], $discursos_actuales) ? 'checked' : ''; ?>
                                <label class="item-numero" title="<?php echo htmlspecialchars($t['tema']); ?>">
                                    <input type="checkbox" name="discursos_seleccionados[]" value="<?php echo $t['numero']; ?>" <?php echo $marcado; ?>>
                                    <?php echo $t['numero']; ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-guardar">💾 Actualizar Datos del Orador</button>
        </form>
    </div>

</body>
</html>