<?php
// actualizar_orador.php
session_start();
require_once 'conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    $orador_id = intval($_POST['orador_id']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $espiritualidad = $_POST['espiritualidad'];
    $telefono = trim($_POST['telefono']);
    $estado = $_POST['estado']; 
    
    $discursos = isset($_POST['discursos_seleccionados']) ? $_POST['discursos_seleccionados'] : [];

    try {
        if ($estado == 'Inactivo') {
            // AHORA SÍ: notificado = 2 avisa a la congregación solicitante que se cayó el arreglo
            $sql_dominio = "UPDATE solicitudes 
                            SET estado = 'Rechazado', notificado = 2 
                            WHERE orador_id = :oid 
                            AND fecha >= CURDATE() 
                            AND estado IN ('Aprobado', 'Pendiente')";
            
            $stmt_dom = $conn->prepare($sql_dominio);
            $stmt_dom->execute([':oid' => $orador_id]);
        }

        $sql_update = "UPDATE oradores 
                       SET nombre = :nom, apellido = :ape, espiritualidad = :espir, telefono = :tel, estado = :est 
                       WHERE id = :id";
        $stmt = $conn->prepare($sql_update);
        $stmt->execute([
            ':nom' => $nombre,
            ':ape' => $apellido,
            ':espir' => $espiritualidad,
            ':tel' => $telefono,
            ':est' => $estado,
            ':id' => $orador_id
        ]);

        $conn->prepare("DELETE FROM discursos WHERE orador_id = ?")->execute([$orador_id]);
        
        if (count($discursos) > 0) {
            $sql_ins_disc = "INSERT INTO discursos (orador_id, numero_discurso) VALUES (?, ?)";
            $stmt_disc = $conn->prepare($sql_ins_disc);
            foreach ($discursos as $num) {
                $stmt_disc->execute([$orador_id, intval($num)]);
            }
        }

        header("Location: oradores.php?mensaje=actualizado");
        exit();

    } catch (PDOException $e) {
        die("Error de Base de Datos al actualizar el orador: " . $e->getMessage());
    }

} else {
    header("Location: oradores.php");
    exit();
}
?>