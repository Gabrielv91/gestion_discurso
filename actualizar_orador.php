<?php
// actualizar_orador.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orador_id = intval($_POST['orador_id']);
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']); // <-- RECIBIMOS EL TELÉFONO
    $espiritualidad = $_POST['espiritualidad'];
    $estado = $_POST['estado'];
    
    // Los discursos que el usuario dejó marcados en la pantalla
    $discursos_nuevos = isset($_POST['discursos_seleccionados']) ? $_POST['discursos_seleccionados'] : [];

    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            $conn->beginTransaction();

            // 1. Actualizamos los datos básicos del orador (AGREGADO EL TELÉFONO)
            $sql_update = "UPDATE oradores SET nombre = :nombre, apellido = :apellido, telefono = :telefono, espiritualidad = :espiritualidad, estado = :estado WHERE id = :id";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':telefono' => $telefono,
                ':espiritualidad' => $espiritualidad,
                ':estado' => $estado,
                ':id' => $orador_id
            ]);

            // 2. Traemos de la base de datos lo que el orador TENÍA antes de este cambio
            $sql_viejos = "SELECT numero_discurso, ruta_archivo FROM discursos WHERE orador_id = :id";
            $stmt_viejos = $conn->prepare($sql_viejos);
            $stmt_viejos->execute([':id' => $orador_id]);
            $discursos_bd = $stmt_viejos->fetchAll(PDO::FETCH_ASSOC);
            
            $discursos_viejos_nums = [];
            foreach ($discursos_bd as $d) {
                $discursos_viejos_nums[] = $d['numero_discurso'];
            }

            // 3. MATEMÁTICAS DE CONJUNTOS
            $a_borrar = array_diff($discursos_viejos_nums, $discursos_nuevos);
            $a_insertar = array_diff($discursos_nuevos, $discursos_viejos_nums);

            // 4. Borrar los desmarcados y eliminar sus archivos físicos
            if (!empty($a_borrar)) {
                foreach ($discursos_bd as $d) {
                    if (in_array($d['numero_discurso'], $a_borrar)) {
                        if (!empty($d['ruta_archivo']) && file_exists($d['ruta_archivo'])) {
                            unlink($d['ruta_archivo']); 
                        }
                        $sql_del = "DELETE FROM discursos WHERE orador_id = :id AND numero_discurso = :num";
                        $stmt_del = $conn->prepare($sql_del);
                        $stmt_del->execute([':id' => $orador_id, ':num' => $d['numero_discurso']]);
                    }
                }
            }

            // 5. Insertar los discursos nuevos que se marcaron
            if (!empty($a_insertar)) {
                $sql_ins = "INSERT INTO discursos (orador_id, numero_discurso, tema) VALUES (:id, :num, :tema)";
                $stmt_ins = $conn->prepare($sql_ins);
                
                foreach ($a_insertar as $num) {
                    $stmt_ins->execute([
                        ':id' => $orador_id,
                        ':num' => $num,
                        ':tema' => "Bosquejo " . $num
                    ]);
                }
            }

            $conn->commit();
            header("Location: oradores.php");
            exit();
            
        } catch(PDOException $e) {
            $conn->rollBack();
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    header("Location: oradores.php");
    exit();
}
?>