<?php
// guardar_solicitud_arreglo.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orador_id = intval($_POST['orador_id']);
    $numero_discurso = intval($_POST['numero_discurso']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $notas = trim($_POST['notas']);
    $usuario_id = $_SESSION['usuario_id']; 

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    try {
        // 1. Obtener ID de TU congregación
        $sql_mi_cong = "SELECT id FROM congregaciones WHERE usuario_id = :usuario_id LIMIT 1";
        $stmt_mi_cong = $conn->prepare($sql_mi_cong);
        $stmt_mi_cong->execute([':usuario_id' => $usuario_id]);
        $mi_congregacion = $stmt_mi_cong->fetchColumn();

        // 2. Obtener la congregación dueña del orador solicitado
        $sql_owner = "SELECT congregacion_id FROM oradores WHERE id = :orador_id LIMIT 1";
        $stmt_owner = $conn->prepare($sql_owner);
        $stmt_owner->execute([':orador_id' => $orador_id]);
        $congregacion_orador = $stmt_owner->fetchColumn();

        // --- REGLA DE LAS 2 SALIDAS ---
        // Solo aplica si el orador NO es de mi congregación
        if ($congregacion_orador != $mi_congregacion) {
            $sql_count = "SELECT COUNT(*) FROM solicitudes s
                          INNER JOIN oradores o ON s.orador_id = o.id
                          WHERE o.congregacion_id = :cong_id 
                          AND s.fecha = :fecha 
                          AND s.estado != 'Rechazado'
                          AND s.congregacion_solicitante_id != o.congregacion_id";
            
            $stmt_count = $conn->prepare($sql_count);
            $stmt_count->execute([':cong_id' => $congregacion_orador, ':fecha' => $fecha]);
            $total_salidas = $stmt_count->fetchColumn();

            if ($total_salidas >= 2) {
                echo "<script>
                        alert('Atención: Esta congregación ya tiene 2 hermanos asignados para salir en esta fecha. Elige otra congregación.');
                        window.history.back();
                      </script>";
                exit();
            }
        }

        // --- AUTO-APROBACIÓN ---
        // Si el orador es mío, estado 'Aprobado' (Verde), si no, 'Pendiente' (Naranja)
        $estado_final = ($congregacion_orador == $mi_congregacion) ? 'Aprobado' : 'Pendiente';

        // --- SOLUCIÓN AL DUPLICADO ---
        // Borrar cualquier solicitud previa tuya para esa misma fecha (limpia el calendario)
        $sql_delete = "DELETE FROM solicitudes 
                       WHERE congregacion_solicitante_id = :mi_cong 
                       AND fecha = :fecha";
        $stmt_del = $conn->prepare($sql_delete);
        $stmt_del->execute([':mi_cong' => $mi_congregacion, ':fecha' => $fecha]);

        // 3. Insertar la nueva solicitud con el estado correspondiente
        $sql = "INSERT INTO solicitudes (congregacion_solicitante_id, orador_id, numero_discurso, fecha, hora, notas, estado) 
                VALUES (:mi_cong, :orador, :num, :fecha, :hora, :notas, :estado)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':mi_cong' => $mi_congregacion,
            ':orador' => $orador_id,
            ':num' => $numero_discurso,
            ':fecha' => $fecha,
            ':hora' => $hora,
            ':notas' => $notas,
            ':estado' => $estado_final
        ]);

        echo "<script>
                alert('Solicitud guardada correctamente.');
                window.location.href = 'calendario_arreglos.php';
              </script>";
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>