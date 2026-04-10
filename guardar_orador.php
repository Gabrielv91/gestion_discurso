<?php
// guardar_orador.php
session_start();
require_once 'conexion/conexion.php';

// Verificamos que los datos vengan por POST y que sea un coordinador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id']) && $_SESSION['rol'] == 'Coordinador') {
    
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    // Recibimos los datos básicos
    $congregacion_id = $_POST['congregacion_id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $espiritualidad = $_POST['espiritualidad'];
    $estado = 'Activo';

    try {
        $conn->beginTransaction();

        // 1. Guardar los datos del orador en la tabla 'oradores'
        $sql_orador = "INSERT INTO oradores (congregacion_id, nombre, apellido, telefono, espiritualidad, estado) 
                       VALUES (:congregacion_id, :nombre, :apellido, :telefono, :espiritualidad, :estado)";
        $stmt = $conn->prepare($sql_orador);
        $stmt->execute([
            ':congregacion_id' => $congregacion_id,
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':telefono' => $telefono,
            ':espiritualidad' => $espiritualidad,
            ':estado' => $estado
        ]);

        // Obtenemos el ID que la base de datos le acaba de asignar a este hermano
        $orador_id = $conn->lastInsertId();

        // 2. Guardar los discursos seleccionados en la tabla 'discursos'
        if (isset($_POST['discursos_seleccionados']) && is_array($_POST['discursos_seleccionados'])) {
            
            $sql_discurso = "INSERT INTO discursos (orador_id, numero_discurso, tema) VALUES (:orador_id, :numero_discurso, :tema)";
            $stmt_disc = $conn->prepare($sql_discurso);

            foreach ($_POST['discursos_seleccionados'] as $numero) {
                $stmt_disc->execute([
                    ':orador_id' => $orador_id,
                    ':numero_discurso' => intval($numero),
                    ':tema' => "Bosquejo " . $numero
                ]);
            }
        }

        // Si todo salió bien, confirmamos los cambios
        $conn->commit();

        // Lo devolvemos a la pantalla de oradores
        header("Location: oradores.php?mensaje=exito");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        die("Error de Base de Datos: " . $e->getMessage());
    }

} else {
    // Si intentan entrar a este archivo directo por la URL, los devolvemos
    header("Location: oradores.php");
    exit();
}
?>