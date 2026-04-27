<?php
// actualizar_orador.php
session_start();
require_once 'conexion/conexion.php';

// Verificamos que los datos vengan por POST y haya sesión
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['usuario_id'])) {
    
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    // Recibimos los datos del formulario
    $orador_id = intval($_POST['orador_id']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $espiritualidad = $_POST['espiritualidad'];
    $telefono = trim($_POST['telefono']);
    $estado = $_POST['estado']; // Aquí viene 'Activo' o 'Inactivo'
    
    // Recibimos los discursos marcados (si no marcó ninguno, creamos un arreglo vacío)
    $discursos = isset($_POST['discursos_seleccionados']) ? $_POST['discursos_seleccionados'] : [];

    try {
        // ========================================================================
        // 1. EL EFECTO DOMINÓ: REGLA DE INACTIVACIÓN
        // ========================================================================
        if ($estado == 'Inactivo') {
            // Si lo suspendemos, buscamos arreglos futuros (Verdes o Naranjas) y los pasamos a Rojo
            // Importante: Ponemos notificado = 0 para que le suene la alarma a la otra congregación
            $sql_dominio = "UPDATE solicitudes 
                            SET estado = 'Rechazado', notificado = 0 
                            WHERE orador_id = :oid 
                            AND fecha >= CURDATE() 
                            AND estado IN ('Aprobado', 'Pendiente')";
            
            $stmt_dom = $conn->prepare($sql_dominio);
            $stmt_dom->execute([':oid' => $orador_id]);
        }
        // ========================================================================

        // 2. Actualizamos los datos personales del orador
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

        // 3. Actualizamos los bosquejos (La forma más limpia es borrar los viejos y guardar los nuevos)
        $conn->prepare("DELETE FROM discursos WHERE orador_id = ?")->execute([$orador_id]);
        
        if (count($discursos) > 0) {
            $sql_ins_disc = "INSERT INTO discursos (orador_id, numero_discurso) VALUES (?, ?)";
            $stmt_disc = $conn->prepare($sql_ins_disc);
            foreach ($discursos as $num) {
                $stmt_disc->execute([$orador_id, intval($num)]);
            }
        }

        // Todo listo, devolvemos al usuario a su lista de oradores
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