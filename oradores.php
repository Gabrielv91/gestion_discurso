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

// 1. Obtener la congregación
$sql_cong = "SELECT id, nombre FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1";
$stmt_cong = $conn->prepare($sql_cong);
$stmt_cong->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt_cong->execute();
$congregacion = $stmt_cong->fetch(PDO::FETCH_ASSOC);

if (!$congregacion) {
    die("Error: No se encontró el perfil de la congregación.");
}
$congregacion_id = $congregacion['id'];

// 2. Obtener Oradores PROPIOS + Conteo de Discursos + Nombre de congregación si está en transferencia
$sql_oradores = "
    SELECT o.*, COUNT(d.id) as total_discursos, ct.nombre as nombre_cong_destino 
    FROM oradores o 
    LEFT JOIN discursos d ON o.id = d.orador_id 
    LEFT JOIN congregaciones ct ON o.congregacion_transferencia_id = ct.id
    WHERE o.congregacion_id = :cid 
    GROUP BY o.id 
    ORDER BY o.nombre ASC
";
$stmt_oradores = $conn->prepare($sql_oradores);
$stmt_oradores->execute([':cid' => $congregacion_id]);
$lista_oradores = $stmt_oradores->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener Oradores ENTRANTES (transferencias que otra congregación nos manda a nosotros)
$sql_entrantes = "
    SELECT o.*, c.nombre as nombre_cong_origen
    FROM oradores o
    INNER JOIN congregaciones c ON o.congregacion_id = c.id
    WHERE o.congregacion_transferencia_id = :cid
    ORDER BY o.nombre ASC
";
$stmt_entrantes = $conn->prepare($sql_entrantes);
$stmt_entrantes->execute([':cid' => $congregacion_id]);
$lista_entrantes = $stmt_entrantes->fetchAll(PDO::FETCH_ASSOC);

// 4. Obtener el Catálogo de Discursos
$sql_cat = "SELECT numero, tema FROM catalogo_discursos ORDER BY numero ASC";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->execute();
$catalogo = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

// 5. Obtener listado de otras congregaciones para la transferencia
$sql_todas = "SELECT id, nombre FROM congregaciones WHERE id != :cid ORDER BY nombre ASC";
$stmt_todas = $conn->prepare($sql_todas);
$stmt_todas->execute([':cid' => $congregacion_id]);
$otras_congregaciones = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Oradores - <?php echo htmlspecialchars($congregacion['nombre']); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.8em; }
        .header p { margin: 5px 0 0 0; color: #bdc3c7; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #ddd; padding-bottom: 15px; }
        .btn-nuevo { background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .grid-oradores { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .card-orador { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #ddd; display: flex; flex-direction: column; }
        .card-header { background: #34495e; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 20px; flex-grow: 1; }
        .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; color: #555; }
        .card-footer { background: #f9f9f9; padding: 15px; border-top: 1px solid #eee; display: flex; flex-wrap: wrap; gap: 10px; }
        .btn-accion { flex: 1; min-width: 45%; text-align: center; padding: 8px; border-radius: 4px; text-decoration: none; font-size: 0.9em; font-weight: bold; box-sizing: border-box; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 10px; width: 650px; max-width: 90%; max-height: 90vh; overflow-y: auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; color: #34495e; margin-bottom: 5px; font-size: 0.9em; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; box-sizing: border-box; }
        .grid-numeros { display: grid; grid-template-columns: repeat(auto-fill, minmax(58px, 1fr)); gap: 6px; max-height: 250px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .item-numero { display: flex; align-items: center; justify-content: center; background: white; border: 1px solid #ccc; border-radius: 4px; padding: 5px; cursor: pointer; font-size: 0.85em; }
        .seccion-especial { background: #fdf2e9; border: 1px solid #e67e22; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <header class="header">
        <h1>Gestión de Oradores Locales</h1>
        <p>Congregación <?php echo htmlspecialchars($congregacion['nombre']); ?> | <a href="dashboard.php">⬅ Volver al Panel</a></p>
    </header>

    <div class="container">
        
        <?php 
        // Sistema de mensajes
        if(isset($_GET['msg'])){
            $mensajes = [
                'eliminado' => ['#e74c3c', 'Orador eliminado correctamente.'],
                'transferencia_enviada' => ['#f39c12', 'Solicitud de transferencia enviada. En espera de confirmación.'],
                'transferencia_cancelada' => ['#95a5a6', 'La transferencia ha sido cancelada.'],
                'transferencia_aceptada' => ['#27ae60', 'Has aceptado al nuevo orador en tu congregación.'],
                'transferencia_rechazada' => ['#e74c3c', 'Has rechazado la transferencia.']
            ];
            $msg = $_GET['msg'];
            if(array_key_exists($msg, $mensajes)){
                echo "<div style='background: {$mensajes[$msg][0]}; color: white; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold;'>{$mensajes[$msg][1]}</div>";
            }
        }
        ?>

        <!-- TRANSFERENCIAS ENTRANTES -->
        <?php if(count($lista_entrantes) > 0): ?>
            <div style="background: #e8f8f5; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 2px solid #1abc9c;">
                <h3 style="margin-top:0; color: #16a085;">📥 Transferencias Pendientes por Aceptar</h3>
                <div class="grid-oradores">
                    <?php foreach ($lista_entrantes as $entrante): ?>
                        <div class="card-orador" style="border-color: #1abc9c;">
                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($entrante['nombre'] . ' ' . $entrante['apellido']); ?></h3>
                                <p style="margin-bottom:0;">Viene de congregación:<br><strong><?php echo htmlspecialchars($entrante['nombre_cong_origen']); ?></strong></p>
                            </div>
                            <div class="card-footer">
                                <form action="procesar_transferencia.php" method="POST" style="flex:1; display:flex; margin:0;">
                                    <input type="hidden" name="orador_id" value="<?php echo $entrante['id']; ?>">
                                    <input type="hidden" name="accion" value="aceptar">
                                    <button type="submit" class="btn-accion" style="background:#27ae60; color:white; border:none; cursor:pointer; width:100%;">Aceptar</button>
                                </form>
                                <form action="procesar_transferencia.php" method="POST" style="flex:1; display:flex; margin:0;">
                                    <input type="hidden" name="orador_id" value="<?php echo $entrante['id']; ?>">
                                    <input type="hidden" name="accion" value="rechazar">
                                    <button type="submit" class="btn-accion" style="background:#e74c3c; color:white; border:none; cursor:pointer; width:100%;">Rechazar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ORADORES PROPIOS -->
        <div class="action-bar">
            <h2>Hermanos de la Casa (<?php echo count($lista_oradores); ?>)</h2>
            <button class="btn-nuevo" onclick="abrirModal()">➕ Registrar Orador</button>
        </div>

        <div class="grid-oradores">
            <?php foreach ($lista_oradores as $orador): ?>
                <div class="card-orador">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($orador['nombre'] . ' ' . $orador['apellido']); ?></h3>
                        <span style="font-size: 0.8em; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px;">
                            <?php echo htmlspecialchars($orador['estado']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="info-row">🏅 <strong><?php echo htmlspecialchars($orador['espiritualidad']); ?></strong></div>
                        <div class="info-row">📱 <?php echo htmlspecialchars($orador['telefono']); ?></div>
                        <div class="info-row">📚 <strong><?php echo $orador['total_discursos']; ?> temas registrados</strong></div>
                        
                        <?php if(!empty($orador['congregacion_transferencia_id'])): ?>
                            <div style="background: #fcf3cf; padding: 10px; margin-top: 15px; border-radius: 6px; border: 1px dashed #f39c12; text-align: center; font-size: 0.9em; color: #d35400;">
                                ⏳ Esperando confirmación de:<br><strong><?php echo htmlspecialchars($orador['nombre_cong_destino']); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <?php if(!empty($orador['congregacion_transferencia_id'])): ?>
                            <!-- SI ESTÁ PENDIENTE DE TRANSFERENCIA: Solo botón de Cancelar -->
                            <form action="cancelar_transferencia.php" method="POST" style="flex: 1; margin: 0; display: flex;">
                                <input type="hidden" name="orador_id" value="<?php echo $orador['id']; ?>">
                                <button type="submit" class="btn-accion" style="background:#e67e22; color:white; border:none; cursor:pointer; width:100%;">Cancelar Transferencia</button>
                            </form>
                        <?php else: ?>
                            <!-- SI ES NORMAL: Botones estándar -->
                            <a href="editar_orador.php?id=<?php echo $orador['id']; ?>" class="btn-accion" style="background:#f1c40f; color:#333;">Editar</a>
                            <a href="ver_discursos.php?orador_id=<?php echo $orador['id']; ?>" class="btn-accion" style="background:#3498db; color:white;">Bosquejos</a>
                            <button onclick="abrirModalTransferir(<?php echo $orador['id']; ?>, '<?php echo addslashes($orador['nombre'] . ' ' . $orador['apellido']); ?>')" class="btn-accion" style="background:#9b59b6; color:white; border:none; cursor:pointer;">Transferir</button>
                            <form action="eliminar_orador.php" method="POST" style="flex: 1; min-width: 45%; margin: 0; display: flex;" onsubmit="return confirm('¿Estás seguro de eliminar a este orador y cancelar todos sus arreglos futuros?');">
                                <input type="hidden" name="orador_id" value="<?php echo $orador['id']; ?>">
                                <button type="submit" class="btn-accion" style="background:#e74c3c; color:white; border:none; cursor:pointer; width:100%;">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Registrar Orador -->
    <div id="modalOrador" class="modal">
        <div class="modal-content">
            <h2 style="margin-top:0;">Registrar Nuevo Orador</h2>
            <form action="guardar_orador.php" method="POST">
                <input type="hidden" name="congregacion_id" value="<?php echo $congregacion_id; ?>">
                
                <div class="form-grid">
                    <div class="form-group"><label>Nombres:</label><input type="text" name="nombre" required></div>
                    <div class="form-group"><label>Apellidos:</label><input type="text" name="apellido" required></div>
                    <div class="form-group">
                        <label>Nombramiento:</label>
                        <select name="espiritualidad"><option>Anciano</option><option>Siervo Ministerial</option></select>
                    </div>
                    <div class="form-group"><label>Teléfono:</label><input type="text" name="telefono"></div>
                </div>

                <div class="seccion-especial">
                    <label style="font-weight:bold; color:#d35400; display:block; margin-bottom:8px;">🌟 Eventos Especiales:</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php foreach ($catalogo as $t): ?>
                            <?php if($t['numero'] == 501 || $t['numero'] == 502): ?>
                                <?php $etiqueta = ($t['numero'] == 501) ? "Especial" : "Conmemoración"; ?>
                                <label class="item-numero" title="<?php echo htmlspecialchars($t['tema']); ?>" style="border-color:#e67e22; color:#d35400; font-weight:bold; padding: 6px 12px;">
                                    <input type="checkbox" name="discursos_seleccionados[]" value="<?php echo $t['numero']; ?>">
                                    <?php echo $etiqueta; ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <label style="font-weight:bold; display:block; margin-bottom:8px;">📖 Seleccione los Bosquejos Preparados:</label>
                <div class="grid-numeros">
                    <?php foreach ($catalogo as $t): ?>
                        <?php if($t['numero'] < 500): ?>
                            <label class="item-numero" title="<?php echo htmlspecialchars($t['tema']); ?>">
                                <input type="checkbox" name="discursos_seleccionados[]" value="<?php echo $t['numero']; ?>">
                                <?php echo $t['numero']; ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex; justify-content:space-between; margin-top:25px;">
                    <button type="button" onclick="cerrarModal()" style="padding:10px 20px; background:#e74c3c; color:white; border:none; border-radius:6px; cursor:pointer;">Cancelar</button>
                    <button type="submit" style="padding:10px 30px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">💾 Guardar Orador</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Transferir Orador -->
    <div id="modalTransferir" class="modal">
        <div class="modal-content" style="width: 400px;">
            <h2 style="margin-top:0;">Transferir Orador</h2>
            <p>Selecciona la nueva congregación para <strong id="nombreOradorTransferir"></strong>:</p>
            
            <form action="transferir_orador.php" method="POST">
                <input type="hidden" name="orador_id" id="inputOradorIdTransferir">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <select name="nueva_congregacion_id" required>
                        <option value="">-- Seleccione congregación --</option>
                        <?php foreach ($otras_congregaciones as $cong): ?>
                            <option value="<?php echo $cong['id']; ?>">
                                <?php echo htmlspecialchars($cong['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display:flex; justify-content:space-between;">
                    <button type="button" onclick="cerrarModalTransferir()" style="padding:10px 20px; background:#95a5a6; color:white; border:none; border-radius:6px; cursor:pointer;">Cancelar</button>
                    <button type="submit" style="padding:10px 20px; background:#9b59b6; color:white; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() { document.getElementById('modalOrador').style.display = 'flex'; }
        function cerrarModal() { document.getElementById('modalOrador').style.display = 'none'; }
        function abrirModalTransferir(id, nombre) {
            document.getElementById('inputOradorIdTransferir').value = id;
            document.getElementById('nombreOradorTransferir').innerText = nombre;
            document.getElementById('modalTransferir').style.display = 'flex';
        }
        function cerrarModalTransferir() {
            document.getElementById('modalTransferir').style.display = 'none';
        }
    </script>
</body>
</html>