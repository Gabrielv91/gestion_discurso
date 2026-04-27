<?php
// procesar_solicitud.php
session_start();
require_once 'conexion/conexion.php';

// Verificamos que los datos vengan por POST de forma segura y haya una sesión activa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    // Recibimos los datos del formulario
    $orador_id = intval($_POST['orador_id']);
    $fecha = $_POST['fecha'];
    $mi_cong_id = intval($_POST['mi_cong_id']);
    $hora = $_POST['hora'];
    $numero_discurso = intval($_POST['numero_discurso']);
    
    try {
        // 1. AVERIGUAMOS DE DÓNDE ES EL ORADOR PARA SABER SI SE AUTO-APRUEBA
        $sql_verificar = "SELECT congregacion_id FROM oradores WHERE id = :orador_id";
        $stmt_ver = $conn->prepare($sql_verificar);
        $stmt_ver->execute([':orador_id' => $orador_id]);
        $orador = $stmt_ver->fetch(PDO::FETCH_ASSOC);

        // LÓGICA DE AUTO-APROBACIÓN
        if ($orador && $orador['congregacion_id'] == $mi_cong_id) {
            // El orador es de MI congregación: Se aprueba solo (Verde).
            $estado_nuevo = 'Aprobado'; 
        } else {
            // El orador es un invitado: Se queda esperando (Naranja).
            $estado_nuevo = 'Pendiente';
        }

        // -------------------------------------------------------------------
        // 2. MÁQUINA DE ESTADOS: REVISAMOS SI YA HABÍA ALGO EN ESA FECHA
        // -------------------------------------------------------------------
        $sql_check_fecha = "SELECT id, estado FROM solicitudes WHERE congregacion_solicitante_id = ? AND fecha = ?";
        $stmt_check = $conn->prepare($sql_check_fecha);
        $stmt_check->execute([$mi_cong_id, $fecha]);
        $solicitud_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($solicitud_existente) {
            $estado_actual = $solicitud_existente['estado'];
            $id_solicitud_vieja = $solicitud_existente['id'];

            if ($estado_actual === 'Aprobado') {
                // REGLA 1 (VERDE): Ya hay un arreglo confirmado. BLOQUEO TOTAL.
                // Lo mandamos al calendario con un error para que no haga desastres.
                header("Location: calendario_arreglos.php?error=bloqueado_verde");
                exit();
            } else {
                // REGLA 2 (NARANJA O ROJO): Pendiente o Cancelado. LO SOBREESCRIBIMOS.
                $sql_update = "UPDATE solicitudes 
                               SET orador_id = :orador_id, 
                                   numero_discurso = :numero_discurso, 
                                   hora = :hora, 
                                   estado = :estado 
                               WHERE id = :id_viejo";
                
                $stmt_upd = $conn->prepare($sql_update);
                $stmt_upd->execute([
                    ':orador_id' => $orador_id,
                    ':numero_discurso' => $numero_discurso,
                    ':hora' => $hora,
                    ':estado' => $estado_nuevo,
                    ':id_viejo' => $id_solicitud_vieja
                ]);

                header("Location: calendario_arreglos.php?mensaje=solicitud_actualizada");
                exit();
            }
        } else {
            // REGLA 3 (VACÍO): La fecha estaba libre. INSERTAMOS NORMAL.
            $sql = "INSERT INTO solicitudes (congregacion_solicitante_id, orador_id, numero_discurso, fecha, hora, estado) 
                    VALUES (:congregacion_solicitante_id, :orador_id, :numero_discurso, :fecha, :hora, :estado)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':congregacion_solicitante_id' => $mi_cong_id,
                ':orador_id' => $orador_id,
                ':numero_discurso' => $numero_discurso,
                ':fecha' => $fecha,
                ':hora' => $hora,
                ':estado' => $estado_nuevo
            ]);

            header("Location: calendario_arreglos.php?mensaje=solicitud_enviada");
            exit();
        }

    } catch (PDOException $e) {
        die("Error de Base de Datos al guardar la solicitud: " . $e->getMessage());
    }

} else {
    header("Location: calendario_arreglos.php");
    exit();
}
?>