<?php
// oradores.php
session_start();
require_once 'conexion/conexion.php';

// Validar seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Obtener el ID de la congregación de este usuario
$sql_cong = "SELECT id, nombre FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1";
$stmt_cong = $conn->prepare($sql_cong);
$stmt_cong->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt_cong->execute();
$congregacion = $stmt_cong->fetch(PDO::FETCH_ASSOC);

if (!$congregacion) {
    die("Error: No se encontró el perfil de la congregación.");
}

$congregacion_id = $congregacion['id'];

// 2. Obtener la lista de oradores de esta congregación
$sql_oradores = "SELECT * FROM oradores WHERE congregacion_id = :congregacion_id ORDER BY nombre ASC";
$stmt_oradores = $conn->prepare($sql_oradores);
$stmt_oradores->bindParam(':congregacion_id', $congregacion_id, PDO::PARAM_INT);
$stmt_oradores->execute();
$lista_oradores = $stmt_oradores->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Oradores - <?php echo htmlspecialchars($congregacion['nombre']); ?></title>
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
        <h1>Gestión de Oradores</h1>
        <p><a href="dashboard.php" style="color: white; text-decoration: underline;">Volver al Panel</a> | <a href="logout.php" style="color: #ffcccc;">Cerrar Sesión</a></p>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container">
            <h2>Registrar Nuevo Orador</h2>
            <form action="guardar_orador.php" method="POST" style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <input type="hidden" name="congregacion_id" value="<?php echo $congregacion_id; ?>">

                <div class="input-group">
                    <label for="nombre">Nombres:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="input-group">
                    <label for="apellido">Apellidos:</label>
                    <input type="text" id="apellido" name="apellido" required>
                </div>

                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="espiritualidad">Espiritualidad:</label>
                    <select id="espiritualidad" name="espiritualidad" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="Anciano">Anciano</option>
                        <option value="Siervo Ministerial">Siervo Ministerial</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="telefono">Teléfono (WhatsApp):</label>
                    <input type="text" id="telefono" name="telefono" placeholder="Ej: 04141234567" required>
                </div>

                <div class="input-group" style="grid-column: 1 / -1; margin-top: 10px;">
                    <label style="font-size: 1.1em; font-weight: bold; margin-bottom: 10px; display: block; color: #2c3e50;">
                        Bosquejos de discursos públicos
                    </label>
                    <div class="caja-discursos">
                        <?php for ($i = 1; $i <= 194; $i++): ?>
                            <label>
                                <input type="checkbox" name="discursos_seleccionados[]" value="<?php echo $i; ?>"> 
                                <?php echo $i; ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <button type="submit" class="btn-aprobar" style="grid-column: 1 / -1; padding: 12px; margin-top: 10px;">Guardar Orador y Discursos</button>
            </form>

            <hr style="margin: 30px 0; border: 0; border-top: 2px dashed #ccc;">

            <h2>Lista de Oradores</h2>
            <?php if (count($lista_oradores) > 0): ?>
                <table class="tabla-admin" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Nombre y Apellido</th>
                            <th>Nombramiento</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_oradores as $orador): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></strong></td>
                                <td><?php echo htmlspecialchars($orador['espiritualidad']); ?></td>
                                <td>
                                    <span style="color: <?php echo $orador['estado'] == 'Activo' ? 'green' : 'red'; ?>;">
                                        <?php echo htmlspecialchars($orador['estado']); ?>
                                    </span>
                                </td>
                                <td class="acciones-celda">
                                    <a href="editar_orador.php?id=<?php echo $orador['id']; ?>" class="btn-aprobar" style="background-color: #3498db; text-decoration: none; padding: 6px 10px; font-size: 0.9em; margin-right: 5px;">Editar</a>
                                    
                                    <a href="ver_discursos.php?orador_id=<?php echo $orador['id']; ?>" class="btn-aprobar" style="background-color: #f39c12; text-decoration: none; padding: 6px 10px; font-size: 0.9em;">Ver Temas/Subir Archivos</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="mensaje-vacio">
                    <p>Aún no has registrado ningún orador en tu congregación.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>