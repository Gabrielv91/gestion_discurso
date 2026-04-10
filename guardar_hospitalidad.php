<?php
// guardar_hospitalidad.php
session_start();
require_once 'conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    
    $solicitud_id = intval($_POST['solicitud_id']);
    
    // Si no eligen nada, lo mandamos como NULL (vacío) a la base de datos
    $almuerzo_id = !empty($_POST['almuerzo_id']) ? intval($_POST['almuerzo_id']) : null;
    $hospedaje_id = !empty($_POST['hospedaje_id']) ? intval($_POST['hospedaje_id']) : null;

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    try {
        $sql = "UPDATE solicitudes SET hogar_almuerzo_id = :almuerzo, hogar_hospedaje_id = :hospedaje WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':almuerzo' => $almuerzo_id,
            ':hospedaje' => $hospedaje_id,
            ':id' => $solicitud_id
        ]);
        
        // Lo devolvemos al panel con un mensaje de éxito
        header("Location: control_arreglos.php?mensaje=hospitalidad_guardada");
        exit();
        
    } catch (PDOException $e) {
        die("Error al guardar hospitalidad: " . $e->getMessage());
    }
} else {
    header("Location: control_arreglos.php");
    exit();
}
?>