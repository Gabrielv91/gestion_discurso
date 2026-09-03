<?php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php"); exit();
}

if (isset($_POST['orador_id']) && isset($_POST['accion'])) {
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($_POST['accion'] === 'aceptar') {
        // Pasa a ser oficialmente de ellos y limpia el estado pendiente
        $sql = "UPDATE oradores SET congregacion_id = congregacion_transferencia_id, congregacion_transferencia_id = NULL WHERE id = :orador_id";
        $msg = "transferencia_aceptada";
    } else {
        // Se rechaza, por lo que vuelve a ser de la congregación original
        $sql = "UPDATE oradores SET congregacion_transferencia_id = NULL WHERE id = :orador_id";
        $msg = "transferencia_rechazada";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':orador_id' => $_POST['orador_id']]);

    header("Location: oradores.php?msg=" . $msg);
}
?>