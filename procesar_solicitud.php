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
        // 1. AVERIGUAMOS DE DÓNDE ES EL ORADOR ANTES DE GUARDARLO
        $sql_verificar = "SELECT congregacion_id FROM oradores WHERE id = :orador_id";
        $stmt_ver = $conn->prepare($sql_verificar);
        $stmt_ver->execute([':orador_id' => $orador_id]);
        $orador = $stmt_ver->fetch(PDO::FETCH_ASSOC);

        // 2. LÓGICA DE AUTO-APROBACIÓN
        if ($orador && $orador['congregacion_id'] == $mi_cong_id) {
            // El orador es de MI congregación: Se aprueba solo.
            $estado = 'Aprobado'; 
        } else {
            // El orador es un invitado: Se queda esperando que el otro coordinador apruebe.
            $estado = 'Pendiente';
        }

        // 3. Insertamos la solicitud con el estado correcto
        $sql = "INSERT INTO solicitudes (congregacion_solicitante_id, orador_id, numero_discurso, fecha, hora, estado) 
                VALUES (:congregacion_solicitante_id, :orador_id, :numero_discurso, :fecha, :hora, :estado)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':congregacion_solicitante_id' => $mi_cong_id,
            ':orador_id' => $orador_id,
            ':numero_discurso' => $numero_discurso,
            ':fecha' => $fecha,
            ':hora' => $hora,
            ':estado' => $estado
        ]);

        header("Location: calendario_arreglos.php?mensaje=solicitud_enviada");
        exit();

    } catch (PDOException $e) {
        die("Error de Base de Datos al guardar la solicitud: " . $e->getMessage());
    }

} else {
    header("Location: calendario_arreglos.php");
    exit();
}
?>