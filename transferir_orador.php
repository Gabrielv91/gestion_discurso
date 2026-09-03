<?php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php"); exit();
}

if (isset($_POST['orador_id']) && isset($_POST['nueva_congregacion_id'])) {
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    $sql = "UPDATE oradores SET congregacion_transferencia_id = :nueva_cong WHERE id = :orador_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nueva_cong' => $_POST['nueva_congregacion_id'], 
        ':orador_id' => $_POST['orador_id']
    ]);

    header("Location: oradores.php?msg=transferencia_enviada");
}
?>