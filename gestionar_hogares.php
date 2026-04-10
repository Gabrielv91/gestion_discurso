<?php
// gestionar_hogares.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

$sql_mi_cong = "SELECT id, nombre FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);
$mi_cong_id = $mi_cong['id'];

// ACCIÓN: Agregar o EDITAR
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $nombre = trim($_POST['nombre_familia']);
    $alm = isset($_POST['almuerzo']) ? 1 : 0;
    $hosp = isset($_POST['hospedaje']) ? 1 : 0;
    
    if ($_POST['accion'] == 'agregar') {
        $sql = "INSERT INTO hogares (congregacion_id, nombre_familia, ofrece_almuerzo, ofrece_hospedaje) VALUES (?, ?, ?, ?)";
        $conn->prepare($sql)->execute([$mi_cong_id, $nombre, $alm, $hosp]);
    } elseif ($_POST['accion'] == 'editar') {
        $id = intval($_POST['hogar_id']);
        $sql = "UPDATE hogares SET nombre_familia = ?, ofrece_almuerzo = ?, ofrece_hospedaje = ? WHERE id = ? AND congregacion_id = ?";
        $conn->prepare($sql)->execute([$nombre, $alm, $hosp, $id, $mi_cong_id]);
    }
    header("Location: gestionar_hogares.php");
    exit();
}

// ACCIÓN: Eliminar
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conn->prepare("DELETE FROM hogares WHERE id = ? AND congregacion_id = ?")->execute([$id, $mi_cong_id]);
    header("Location: gestionar_hogares.php");
    exit();
}

$hogares = $conn->prepare("SELECT * FROM hogares WHERE congregacion_id = ? ORDER BY nombre_familia ASC");
$hogares->execute([$mi_cong_id]);
$lista = $hogares->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Hogares</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos Generales y Tarjeta */
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .admin-card { max-width: 850px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        /* Cabecera */
        .header-title { display: flex; align-items: center; gap: 10px; color: #2c3e50; margin-top: 0; border-bottom: 2px solid #ecf0f1; padding-bottom: 15px; margin-bottom: 25px; }
        .nav-links { margin-bottom: 20px; }
        .nav-link { display: inline-block; background: #ecf0f1; color: #34495e; padding: 8px 15px; border-radius: 20px; text-decoration: none; font-size: 0.9em; font-weight: bold; transition: all 0.2s; margin-right: 10px; }
        .nav-link:hover { background: #d5dbdb; }

        /* Formulario de Agregar */
        .form-agregar { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; margin-bottom: 30px; }
        .input-text { padding: 10px 15px; border: 1px solid #ced4da; border-radius: 6px; font-size: 1em; flex-grow: 1; min-width: 200px; outline: none; }
        .input-text:focus { border-color: #3498db; box-shadow: 0 0 0 2px rgba(52,152,219,0.2); }
        .checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; color: #495057; font-weight: 500; user-select: none; }
        .checkbox-label input { width: 18px; height: 18px; cursor: pointer; accent-color: #27ae60; }
        
        /* Botones */
        .btn { border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.9em; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
        .btn-add { background: #27ae60; color: white; }
        .btn-add:hover { background: #219653; }
        .btn-edit { background: #3498db; color: white; padding: 6px 12px; font-size: 0.85em; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete { background: #fff; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; font-size: 0.85em; }
        .btn-delete:hover { background: #e74c3c; color: white; }

        /* Tabla Estilizada */
        .table-hogares { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; }
        .table-hogares th { background: #2c3e50; color: white; padding: 15px; text-align: left; font-weight: 600; }
        .table-hogares th:first-child { border-top-left-radius: 8px; }
        .table-hogares th:last-child { border-top-right-radius: 8px; text-align: center; }
        .table-hogares td { padding: 12px 15px; border-bottom: 1px solid #edf2f7; vertical-align: middle; }
        .table-hogares tr:last-child td { border-bottom: none; }
        .table-hogares tr:hover td { background-color: #f8fafc; }
        
        /* Input invisible para editar nombre */
        .input-inline { width: 100%; border: 1px solid transparent; background: transparent; padding: 5px; font-size: 1em; font-weight: 600; color: #2c3e50; border-radius: 4px; outline: none; transition: 0.2s; }
        .input-inline:hover, .input-inline:focus { border-color: #cbd5e1; background: white; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); }
        
        .td-center { text-align: center; }
        .acciones-wrapper { display: flex; justify-content: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="admin-card">
        
        <div class="nav-links">
            <a href="control_arreglos.php" class="nav-link">⬅️ Volver a Arreglos</a>
            <a href="dashboard.php" class="nav-link">🏠 Volver al Panel</a>
        </div>

        <h2 class="header-title">🍽️ Gestión de Hospitalidad</h2>

        <form method="POST" class="form-agregar">
            <input type="hidden" name="accion" value="agregar">
            
            <input type="text" name="nombre_familia" class="input-text" placeholder="Ej: Familia Colmenarez" required>
            
            <label class="checkbox-label">
                <input type="checkbox" name="almuerzo" checked> 🍽️ Almuerzo
            </label>
            
            <label class="checkbox-label">
                <input type="checkbox" name="hospedaje" checked> 🛏️ Hospedaje
            </label>
            
            <button type="submit" class="btn btn-add">➕ Agregar Familia</button>
        </form>

        <table class="table-hogares">
            <thead>
                <tr>
                    <th>Nombre de la Familia</th>
                    <th class="td-center">Almuerzo</th>
                    <th class="td-center">Hospedaje</th>
                    <th class="td-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $h): ?>
                <tr>
                    <form method="POST" style="display: contents;">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="hogar_id" value="<?php echo $h['id']; ?>">
                        
                        <td>
                            <input type="text" name="nombre_familia" class="input-inline" value="<?php echo htmlspecialchars($h['nombre_familia']); ?>" required title="Haz clic para editar">
                        </td>
                        
                        <td class="td-center">
                            <input type="checkbox" name="almuerzo" <?php echo $h['ofrece_almuerzo'] ? 'checked' : ''; ?> style="width:18px; height:18px; cursor:pointer; accent-color: #2c3e50;">
                        </td>
                        
                        <td class="td-center">
                            <input type="checkbox" name="hospedaje" <?php echo $h['ofrece_hospedaje'] ? 'checked' : ''; ?> style="width:18px; height:18px; cursor:pointer; accent-color: #2c3e50;">
                        </td>
                        
                        <td class="td-center">
                            <div class="acciones-wrapper">
                                <button type="submit" class="btn btn-edit" title="Guardar Cambios">💾 Guardar</button>
                                <a href="?eliminar=<?php echo $h['id']; ?>" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar esta familia?')" title="Eliminar Familia">🗑️ Borrar</a>
                            </div>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
                <?php if(count($lista) == 0): ?>
                <tr>
                    <td colspan="4" class="td-center" style="padding: 30px; color: #7f8c8d;">No hay familias registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>