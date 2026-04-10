<?php
// vistas/coord_panel.php
require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Verificamos si el usuario ya registró los datos de su congregación
$sql = "SELECT * FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$perfil = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. NUEVO: Calculamos las notificaciones directamente aquí para que no falle
$total_notificaciones = 0;
if ($perfil) {
    $mi_cong_id = $perfil['id'];
    $sql_notif = "SELECT COUNT(*) FROM solicitudes s
                  INNER JOIN oradores o ON s.orador_id = o.id
                  WHERE o.congregacion_id = :mi_id 
                  AND s.congregacion_solicitante_id != :mi_id
                  AND s.estado = 'Pendiente'
                  AND s.fecha >= CURDATE()";
    
    $stmt_notif = $conn->prepare($sql_notif);
    $stmt_notif->execute([':mi_id' => $mi_cong_id]);
    $total_notificaciones = $stmt_notif->fetchColumn();
}
?>

<div class="admin-container">
    <?php if (!$perfil): ?>
        <h2>Completa el Perfil de tu Congregación</h2>
        <p>Antes de poder gestionar oradores o arreglos, necesitamos los datos de tu congregación y de contacto.</p>

        <form action="guardar_perfil.php" method="POST" style="margin-top: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="nombre">Nombre de la Congregación:</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Centro, Barinas">
                </div>

                <div class="input-group" style="grid-column: 1 / -1;">
                    <label for="ubicacion">Ubicación (Dirección en texto):</label>
                    <input type="text" id="ubicacion" name="ubicacion_texto" required>
                </div>

                <div class="input-group">
                    <label for="latitud">Latitud (Coordenada):</label>
                    <input type="number" step="any" id="latitud" name="latitud" required placeholder="Ej: 8.6226">
                </div>

                <div class="input-group">
                    <label for="longitud">Longitud (Coordenada):</label>
                    <input type="number" step="any" id="longitud" name="longitud" required placeholder="Ej: -70.2074">
                </div>
                
                <p style="grid-column: 1 / -1; font-size: 0.85em; color: #666; margin-top: -10px;">
                    * Por ahora ingresaremos las coordenadas manualmente. Más adelante podríamos integrar un mapa para seleccionar la ubicación con un clic.
                </p>

                <h3 style="grid-column: 1 / -1; margin-top: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Datos del Coordinador</h3>

                <div class="input-group">
                    <label for="coord_nombre">Nombres:</label>
                    <input type="text" id="coord_nombre" name="coord_nombre" required>
                </div>

                <div class="input-group">
                    <label for="coord_apellido">Apellidos:</label>
                    <input type="text" id="coord_apellido" name="coord_apellido" required>
                </div>

                <div class="input-group">
                    <label for="coord_telefono">Teléfono:</label>
                    <input type="text" id="coord_telefono" name="coord_telefono" required>
                </div>

                <div class="input-group">
                    <label for="coord_correo">Correo Electrónico:</label>
                    <input type="email" id="coord_correo" name="coord_correo" required>
                </div>
            </div>

            <button type="submit" class="btn-aprobar" style="width: 100%; margin-top: 20px; padding: 12px; font-size: 1.1em;">Guardar Perfil de Congregación</button>
        </form>

    <?php else: ?>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Resumen de: <?php echo htmlspecialchars($perfil['nombre']); ?></h2>
            <a href="editar_perfil.php" class="btn-aprobar" style="background-color: #7f8c8d; text-decoration: none; padding: 6px 12px; font-size: 0.9em;">Editar Perfil</a>
        </div>
        
        <p style="margin-top: 10px;"><strong>Dirección:</strong> <?php echo htmlspecialchars($perfil['ubicacion_texto']); ?></p>
        <p><strong>Coordinador:</strong> <?php echo htmlspecialchars($perfil['coord_nombre'] . " " . $perfil['coord_apellido']); ?> (Tel: <?php echo htmlspecialchars($perfil['coord_telefono']); ?>)</p>
        
        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="oradores.php" class="btn-aprobar" style="text-decoration: none; text-align: center; background-color: #3498db; padding: 10px 20px;">Gestionar mis Oradores</a>
            
            <a href="gestionar_hogares.php" class="btn-aprobar" style="text-decoration: none; text-align: center; background-color: #fd06068a; padding: 10px 20px; font-weight: bold;">📋 Gestionar Hospitalidad</a>
            
            <a href="calendario_arreglos.php" class="btn-aprobar" style="text-decoration: none; text-align: center; background-color: #9b59b6; padding: 10px 20px;">Buscar Arreglos</a>
            
            <a href="control_arreglos.php" class="btn-aprobar" style="text-decoration: none; text-align: center; background-color: #27ae60; padding: 10px 20px; font-weight: bold;">📋 Panel Maestro de Arreglos</a>
            
            <a href="solicitudes_recibidas.php" class="btn-aprobar" style="position: relative; text-decoration: none; text-align: center; background-color: #f39c12; padding: 10px 20px;">
                🔔 Solicitudes Recibidas
                <?php if ($total_notificaciones > 0): ?>
                    <span style="position: absolute; top: -10px; right: -10px; background-color: #e74c3c; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.85em; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <?php echo $total_notificaciones; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    <?php endif; ?>
</div>