<?php
// actualizar_perfil.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = $_SESSION['usuario_id'];
    
    $nombre = trim($_POST['nombre']);
    $ubicacion_texto = trim($_POST['ubicacion_texto']);
    $latitud = $_POST['latitud'];
    $longitud = $_POST['longitud'];
    $coord_nombre = trim($_POST['coord_nombre']);
    $coord_apellido = trim($_POST['coord_apellido']);
    $coord_telefono = trim($_POST['coord_telefono']);
    $coord_correo = trim($_POST['coord_correo']);

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            // Actualizamos el registro donde el usuario_id coincida
            $sql = "UPDATE congregaciones 
                    SET nombre = :nombre, ubicacion_texto = :ubicacion, latitud = :latitud, longitud = :longitud, 
                        coord_nombre = :coord_nombre, coord_apellido = :coord_apellido, coord_telefono = :telefono, coord_correo = :correo 
                    WHERE usuario_id = :usuario_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':ubicacion', $ubicacion_texto);
            $stmt->bindParam(':latitud', $latitud);
            $stmt->bindParam(':longitud', $longitud);
            $stmt->bindParam(':coord_nombre', $coord_nombre);
            $stmt->bindParam(':coord_apellido', $coord_apellido);
            $stmt->bindParam(':telefono', $coord_telefono);
            $stmt->bindParam(':correo', $coord_correo);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo "<script>alert('¡Perfil actualizado con éxito!'); window.location.href='dashboard.php';</script>";
            } else {
                echo "<script>alert('Error al actualizar el perfil.'); window.history.back();</script>";
            }

        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>