<?php
// vistas/admin_panel.php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// 1. Buscamos a los coordinadores por estados (Incluyendo el teléfono)
$sql_pendientes = "SELECT id, codigo_usuario, fecha_registro, telefono FROM usuarios WHERE rol = 'Coordinador' AND estado = 'Pendiente' ORDER BY fecha_registro ASC";
$stmt_pendientes = $conn->prepare($sql_pendientes);
$stmt_pendientes->execute();
$pendientes = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);

$sql_aprobados = "SELECT id, codigo_usuario, fecha_registro FROM usuarios WHERE rol = 'Coordinador' AND estado = 'Aprobado' ORDER BY fecha_registro DESC";
$stmt_aprobados = $conn->prepare($sql_aprobados);
$stmt_aprobados->execute();
$aprobados = $stmt_aprobados->fetchAll(PDO::FETCH_ASSOC);

$sql_suspendidos = "SELECT id, codigo_usuario, fecha_registro FROM usuarios WHERE rol = 'Coordinador' AND estado = 'Suspendido' ORDER BY fecha_registro DESC";
$stmt_suspendidos = $conn->prepare($sql_suspendidos);
$stmt_suspendidos->execute();
$suspendidos = $stmt_suspendidos->fetchAll(PDO::FETCH_ASSOC);

// Totales para las tarjetas de resumen
$cant_pendientes = count($pendientes);
$cant_aprobados = count($aprobados);
$cant_suspendidos = count($suspendidos);
?>

<style>
    .admin-container {
        max-width: 1100px;
        margin: 0 auto;
        font-family: 'Segoe UI', sans-serif;
    }

    /* TARJETAS DE RESUMEN (ESTADÍSTICAS) */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        text-align: center;
        border-bottom: 4px solid #ddd;
    }

    .stat-card h3 {
        margin: 0;
        font-size: 0.9em;
        color: #7f8c8d;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stat-card .number {
        font-size: 2.2em;
        font-weight: bold;
        margin: 10px 0;
        color: #2c3e50;
    }

    .stat-pendiente {
        border-color: #f39c12;
    }

    .stat-activo {
        border-color: #27ae60;
    }

    .stat-suspendido {
        border-color: #e74c3c;
    }

    /* SECCIONES */
    .admin-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        padding: 25px;
        margin-bottom: 30px;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #f4f7f6;
        padding-bottom: 10px;
    }

    .section-header h2 {
        margin: 0;
        font-size: 1.4em;
        color: #2c3e50;
    }

    .badge-count {
        background: #eee;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: bold;
    }

    /* TABLAS ESTILIZADAS */
    .tabla-moderna {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .tabla-moderna th {
        text-align: left;
        padding: 12px;
        color: #7f8c8d;
        font-size: 0.85em;
        text-transform: uppercase;
        border-bottom: 2px solid #eee;
    }

    .tabla-moderna td {
        padding: 15px 12px;
        border-bottom: 1px solid #f9f9f9;
        color: #34495e;
        vertical-align: middle;
    }

    .tabla-moderna tr:last-child td {
        border-bottom: none;
    }

    .cong-code {
        background: #f0f3f4;
        padding: 5px 10px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 1.1em;
        color: #2c3e50;
    }

    /* BOTONES */
    .btn-adm {
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        font-size: 0.85em;
        transition: 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        text-decoration: none;
        box-sizing: border-box;
    }

    .btn-approve {
        background: #27ae60;
        color: white;
    }

    .btn-approve:hover {
        background: #219150;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(39, 174, 96, 0.3);
    }

    .btn-suspend {
        background: #e74c3c;
        color: white;
    }

    .btn-suspend:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
    }

    .btn-reactivate {
        background: #3498db;
        color: white;
    }

    .btn-reactivate:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }

    .btn-whatsapp {
        background-color: #25D366;
        color: white;
    }

    .btn-whatsapp:hover {
        background-color: #1ebe57;
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(37, 211, 102, 0.3);
    }

    .empty-state {
        text-align: center;
        padding: 30px;
        color: #bdc3c7;
        font-style: italic;
    }

    /* TARJETA DE CONFIGURACIÓN GLOBAL */
    .config-card {
        display: flex;
        align-items: center;
        gap: 15px;
        background: white;
        padding: 20px;
        border-radius: 8px;
        text-decoration: none;
        border: 1px solid #ddd;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        transition: 0.3s;
    }

    .config-card:hover {
        transform: translateY(-3px);
        border-color: #8e44ad;
        box-shadow: 0 5px 15px rgba(142, 68, 173, 0.1);
    }
</style>

<div class="admin-container">

    <div class="stats-grid">
        <div class="stat-card stat-pendiente">
            <h3>Pendientes</h3>
            <div class="number"><?php echo $cant_pendientes; ?></div>
        </div>
        <div class="stat-card stat-activo">
            <h3>Activas</h3>
            <div class="number"><?php echo $cant_aprobados; ?></div>
        </div>
        <div class="stat-card stat-suspendido">
            <h3>Suspendidas</h3>
            <div class="number"><?php echo $cant_suspendidos; ?></div>
        </div>
    </div>

    <div class="admin-section" style="border-left: 5px solid #8e44ad; background: #fdfafec9;">
        <div class="section-header">
            <h2 style="color: #8e44ad;">⚙️ Configuración Global</h2>
        </div>
        <p style="color: #7f8c8d; font-size: 0.9em; margin-top: -10px; margin-bottom: 20px;">Estos ajustes afectan a
            todas las congregaciones dentro de la plataforma.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
            <a href="temas_especiales.php" class="config-card">
                <div style="font-size: 2.5em;">🌟</div>
                <div>
                    <h3 style="margin: 0; color: #2c3e50; font-size: 1.1em;">Temas Anuales Especiales</h3>
                    <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 0.85em;">Actualiza los títulos de la Campaña
                        Especial y la Conmemoración para este año.</p>
                </div>
            </a>
        </div>
        <div class="config-card" style="display: flex; flex-direction: column; align-items: stretch; padding: 15px;">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <div style="font-size: 2.5em;">🔑</div>
                <div>
                    <h3 style="margin: 0; color: #2c3e50; font-size: 1.1em;">Reseteo de Contraseña</h3>
                    <p style="margin: 3px 0 0 0; color: #7f8c8d; font-size: 0.8em;">Usa esto si un hermano te contacta
                        por WhatsApp porque olvidó sus respuestas.</p>
                </div>
            </div>

            <form action="admin_reset_clave.php" method="POST" style="display: flex; gap: 10px; margin-top: 5px;">
                <input type="text" name="codigo_congregacion" placeholder="Código (Ej: CONG-1)" required
                    style="flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="submit"
                    onclick="return confirm('Esto cambiará la contraseña de esta congregación a: 123456. ¿Proceder?');"
                    style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Resetear
                    a 123456</button>
            </form>
        </div>
    </div>

    <div class="admin-section">
        <div class="section-header">
            <h2>⏳ Solicitudes de Registro</h2>
            <span class="badge-count"><?php echo $cant_pendientes; ?></span>
        </div>

        <?php if ($cant_pendientes > 0): ?>
            <table class="tabla-moderna">
                <thead>
                    <tr>
                        <th>Congregación</th>
                        <th>Fecha de Registro</th>
                        <th style="text-align: right;">Acciones de Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendientes as $u): ?>
                        <?php
                        // 1. Limpiamos el número (dejamos solo los dígitos)
                        $numero_limpio = preg_replace('/[^0-9]/', '', $u['telefono'] ?? '');

                        // 2. Formato para Venezuela (+58). Si el número empieza con '0', lo cambiamos.
                        if (substr($numero_limpio, 0, 1) === '0') {
                            $numero_limpio = '58' . substr($numero_limpio, 1);
                        }

                        // 3. Preparamos el mensaje automático
                        $mensaje = "Hola hermano, te escribo de la plataforma de Gestión de Discursos. He recibido tu solicitud de registro para la congregación *" . htmlspecialchars($u['codigo_usuario']) . "*. Para mantener la seguridad del sistema, ¿podrías confirmarme tu identidad y de qué circuito nos escribes?";
                        $enlace_wa = "https://wa.me/" . $numero_limpio . "?text=" . urlencode($mensaje);
                        ?>
                        <tr>
                            <td><span class="cong-code"><?php echo htmlspecialchars($u['codigo_usuario']); ?></span></td>
                            <td><?php echo date("d M, Y | H:i", strtotime($u['fecha_registro'])); ?></td>
                            <td style="text-align: right; display: flex; justify-content: flex-end; gap: 8px;">

                                <?php if (!empty($numero_limpio) && strlen($numero_limpio) >= 10): ?>
                                    <a href="<?php echo $enlace_wa; ?>" target="_blank" class="btn-adm btn-whatsapp"
                                        title="Verificar identidad por WhatsApp">
                                        💬 Verificar
                                    </a>
                                <?php else: ?>
                                    <span
                                        style="font-size: 0.8em; color: #e74c3c; display: flex; align-items: center; margin-right: 10px;">⚠️
                                        Sin número</span>
                                <?php endif; ?>

                                <form action="procesar_estado.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="accion" value="Aprobado">
                                    <button type="submit" class="btn-adm btn-approve">✔ Aprobar</button>
                                </form>

                                <form action="procesar_estado.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="accion" value="Suspendido">
                                    <button type="submit" class="btn-adm btn-suspend"
                                        onclick="return confirm('¿Rechazar solicitud?');">✖ Rechazar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No hay solicitudes nuevas por revisar.</div>
        <?php endif; ?>
    </div>

    <div class="admin-section" style="border-left: 5px solid #27ae60;">
        <div class="section-header">
            <h2 style="color: #27ae60;">✅ Congregaciones en el Sistema</h2>
            <span class="badge-count"><?php echo $cant_aprobados; ?></span>
        </div>

        <?php if ($cant_aprobados > 0): ?>
            <table class="tabla-moderna">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Fecha Acceso</th>
                        <th style="text-align: right;">Gestión</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aprobados as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['codigo_usuario']); ?></strong></td>
                            <td><?php echo date("d/m/Y", strtotime($u['fecha_registro'])); ?></td>
                            <td style="text-align: right;">
                                <form action="procesar_estado.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="accion" value="Suspendido">
                                    <button type="submit" class="btn-adm btn-suspend"
                                        onclick="return confirm('¿Suspender acceso?');">🚫 Suspender Acceso</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">No hay congregaciones activas todavía.</div>
        <?php endif; ?>
    </div>

    <?php if ($cant_suspendidos > 0): ?>
        <div class="admin-section" style="background: #fdfefe; border-left: 5px solid #e74c3c;">
            <div class="section-header">
                <h2 style="color: #e74c3c;">⛔ Acceso Denegado</h2>
                <span class="badge-count"><?php echo $cant_suspendidos; ?></span>
            </div>

            <table class="tabla-moderna">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Restauración</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suspendidos as $u): ?>
                        <tr>
                            <td><strike><?php echo htmlspecialchars($u['codigo_usuario']); ?></strike></td>
                            <td><span style="color: #e74c3c; font-weight: bold; font-size: 0.8em;">SUSPENDIDO</span></td>
                            <td style="text-align: right;">
                                <form action="procesar_estado.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="usuario_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="accion" value="Aprobado">
                                    <button type="submit" class="btn-adm btn-reactivate"
                                        onclick="return confirm('¿Restaurar acceso?');">🔄 Reactivar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>