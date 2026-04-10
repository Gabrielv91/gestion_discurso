<?php
// vistas/admin_panel.php
require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// 1. Buscamos a los coordinadores que están PENDIENTES
$sql_pendientes = "SELECT id, codigo_usuario, fecha_registro FROM usuarios WHERE rol = 'Coordinador' AND estado = 'Pendiente' ORDER BY fecha_registro ASC";
$stmt_pendientes = $conn->prepare($sql_pendientes);
$stmt_pendientes->execute();
$pendientes = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);

// 2. Buscamos a los coordinadores que ya están APROBADOS
$sql_aprobados = "SELECT id, codigo_usuario, fecha_registro FROM usuarios WHERE rol = 'Coordinador' AND estado = 'Aprobado' ORDER BY fecha_registro DESC";
$stmt_aprobados = $conn->prepare($sql_aprobados);
$stmt_aprobados->execute();
$aprobados = $stmt_aprobados->fetchAll(PDO::FETCH_ASSOC);

// 3. Buscamos a los coordinadores que están SUSPENDIDOS
$sql_suspendidos = "SELECT id, codigo_usuario, fecha_registro FROM usuarios WHERE rol = 'Coordinador' AND estado = 'Suspendido' ORDER BY fecha_registro DESC";
$stmt_suspendidos = $conn->prepare($sql_suspendidos);
$stmt_suspendidos->execute();
$suspendidos = $stmt_suspendidos->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-container">
    <h2>Solicitudes Pendientes de Aprobación</h2>
    <p>Las siguientes congregaciones se han registrado y esperan acceso al sistema:</p>

    <?php if (count($pendientes) > 0): ?>
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Código de Congregación</th>
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendientes as $usuario): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($usuario['codigo_usuario']); ?></strong></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($usuario['fecha_registro'])); ?></td>
                        <td class="acciones-celda">
                            <form action="procesar_estado.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="accion" value="Aprobado">
                                <button type="submit" class="btn-aprobar">Aprobar</button>
                            </form>

                            <form action="procesar_estado.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="accion" value="Suspendido">
                                <button type="submit" class="btn-rechazar" onclick="return confirm('¿Estás seguro de rechazar esta solicitud?');">Rechazar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="mensaje-vacio">
            <p>No hay solicitudes pendientes en este momento.</p>
        </div>
    <?php endif; ?>

    <hr style="margin: 40px 0; border: 0; border-top: 2px dashed #ccc;">

    <h2>Congregaciones Activas (Aprobadas)</h2>
    <p>Listado de congregaciones que actualmente tienen acceso al sistema:</p>

    <?php if (count($aprobados) > 0): ?>
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Código de Congregación</th>
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aprobados as $usuario): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($usuario['codigo_usuario']); ?></strong></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($usuario['fecha_registro'])); ?></td>
                        <td class="acciones-celda">
                            <form action="procesar_estado.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="accion" value="Suspendido">
                                <button type="submit" class="btn-rechazar" onclick="return confirm('¿Estás seguro de suspender a esta congregación? Perderá el acceso al sistema.');">Suspender Acceso</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="mensaje-vacio" style="border-left-color: #3498db; background-color: #ebf5fb;">
            <p>No hay congregaciones activas en este momento.</p>
        </div>
    <?php endif; ?>

    <hr style="margin: 40px 0; border: 0; border-top: 2px dashed #ccc;">

    <h2 style="color: #c0392b;">Congregaciones Suspendidas</h2>
    <p>Listado de congregaciones que tienen el acceso bloqueado:</p>

    <?php if (count($suspendidos) > 0): ?>
        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Código de Congregación</th>
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suspendidos as $usuario): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($usuario['codigo_usuario']); ?></strong></td>
                        <td><?php echo date("d/m/Y H:i", strtotime($usuario['fecha_registro'])); ?></td>
                        <td class="acciones-celda">
                            <form action="procesar_estado.php" method="POST" style="display:inline-block;">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="accion" value="Aprobado">
                                <button type="submit" class="btn-aprobar" onclick="return confirm('¿Estás seguro de devolverle el acceso a esta congregación?');">Quitar Suspensión</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="mensaje-vacio" style="border-left-color: #e74c3c; background-color: #fadbd8;">
            <p>No hay congregaciones suspendidas.</p>
        </div>
    <?php endif; ?>
</div>