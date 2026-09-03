<?php
// eliminar_orador.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if (isset($_POST['orador_id'])) {
    $orador_id = $_POST['orador_id'];
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    try {
        $conn->beginTransaction();

        // 1. Eliminar arreglos (solicitudes) futuros asociados al orador
        $sql_arreglos = "DELETE FROM solicitudes WHERE orador_id = :orador_id AND fecha >= CURRENT_DATE";
        $stmt_arreglos = $conn->prepare($sql_arreglos);
        $stmt_arreglos->execute([':orador_id' => $orador_id]);

        // 2. Eliminar al orador (sus discursos se borran solos por el CASCADE en la BD)
        $sql_orador = "DELETE FROM oradores WHERE id = :orador_id";
        $stmt_orador = $conn->prepare($sql_orador);
        $stmt_orador->execute([':orador_id' => $orador_id]);

        $conn->commit();
        header("Location: oradores.php?msg=eliminado");
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error al eliminar: " . $e->getMessage());
    }
}
?>