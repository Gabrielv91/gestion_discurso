<?php
// procesar_estado.php
session_start();
require_once 'conexion/conexion.php';

// Validamos que quien hace esto sea realmente el Administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Administrador') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_id = intval($_POST['usuario_id']);
    $accion = $_POST['accion']; // Vendrá como 'Aprobado' o 'Suspendido'

    // Validación básica de seguridad
    if ($accion !== 'Aprobado' && $accion !== 'Suspendido') {
        die("Acción no válida.");
    }

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            $sql = "UPDATE usuarios SET estado = :estado WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':estado', $accion);
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Redirigimos de vuelta al panel de control
                header("Location: dashboard.php");
                exit();
            } else {
                echo "Error al actualizar el estado.";
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