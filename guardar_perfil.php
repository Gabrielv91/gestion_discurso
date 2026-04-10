<?php
// guardar_perfil.php
session_start();
require_once 'conexion/conexion.php';

// Validar que haya sesión activa y sea Coordinador
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
            // Insertamos los datos en la tabla congregaciones
            $sql = "INSERT INTO congregaciones (usuario_id, nombre, ubicacion_texto, latitud, longitud, coord_nombre, coord_apellido, coord_telefono, coord_correo) 
                    VALUES (:usuario_id, :nombre, :ubicacion_texto, :latitud, :longitud, :coord_nombre, :coord_apellido, :coord_telefono, :coord_correo)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':ubicacion_texto', $ubicacion_texto);
            $stmt->bindParam(':latitud', $latitud);
            $stmt->bindParam(':longitud', $longitud);
            $stmt->bindParam(':coord_nombre', $coord_nombre);
            $stmt->bindParam(':coord_apellido', $coord_apellido);
            $stmt->bindParam(':coord_telefono', $coord_telefono);
            $stmt->bindParam(':coord_correo', $coord_correo);

            if ($stmt->execute()) {
                // Si guarda con éxito, lo regresamos al dashboard, donde ahora verá su resumen
                header("Location: dashboard.php");
                exit();
            } else {
                echo "<script>alert('Error al guardar el perfil.'); window.history.back();</script>";
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