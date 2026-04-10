<?php
// actualizar_discurso.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] != 'Coordinador') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orador_id = intval($_POST['orador_id']);
    // Recibimos los arreglos de datos
    $canciones = isset($_POST['cancion']) ? $_POST['cancion'] : [];
    $eliminar_archivos = isset($_POST['eliminar_archivo']) ? $_POST['eliminar_archivo'] : [];
    
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            // Empezamos transacción para que o se guarda todo o no se guarda nada
            $conn->beginTransaction();

            // Recorremos todos los discursos que llegaron en el formulario
            foreach ($canciones as $discurso_id => $cancion_val) {
                $discurso_id = intval($discurso_id);
                $cancion = !empty($cancion_val) ? intval($cancion_val) : NULL;
                $eliminar = isset($eliminar_archivos[$discurso_id]) ? '1' : '0';
                
                // 1. Buscamos el archivo viejo de este discurso específico
                $sql_viejo = "SELECT ruta_archivo FROM discursos WHERE id = :id";
                $stmt_viejo = $conn->prepare($sql_viejo);
                $stmt_viejo->execute([':id' => $discurso_id]);
                $archivo_viejo = $stmt_viejo->fetchColumn();

                $ruta_archivo_final = null;
                $actualizar_archivo_sql = "";
                $nombre_input_file = "archivo_multimedia_" . $discurso_id;

                // CASO A: Eliminar el archivo
                if ($eliminar == '1') {
                    if (!empty($archivo_viejo) && file_exists($archivo_viejo)) {
                        unlink($archivo_viejo);
                    }
                    $actualizar_archivo_sql = ", ruta_archivo = NULL";
                } 
                // CASO B: Subir/Reemplazar archivo
                else if (isset($_FILES[$nombre_input_file]) && $_FILES[$nombre_input_file]['error'] == 0) {
                    $nombre_archivo = $_FILES[$nombre_input_file]['name'];
                    $ruta_temporal = $_FILES[$nombre_input_file]['tmp_name'];

                    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
                    $extensiones_permitidas = array('zip', 'rar');

                    // Si suben algo inválido, lo saltamos y seguimos con los demás
                    if (in_array($extension, $extensiones_permitidas)) {
                        $nombre_limpio = preg_replace("/[^a-zA-Z0-9.-]/", "_", $nombre_archivo);
                        $nombre_unico = time() . "_" . $discurso_id . "_" . $nombre_limpio;
                        $directorio_destino = "archivos/";
                        
                        if (!file_exists($directorio_destino)) {
                            mkdir($directorio_destino, 0777, true);
                        }

                        if (move_uploaded_file($ruta_temporal, $directorio_destino . $nombre_unico)) {
                            // Borramos el viejo si existe
                            if (!empty($archivo_viejo) && file_exists($archivo_viejo)) {
                                unlink($archivo_viejo); 
                            }
                            $ruta_archivo_final = $directorio_destino . $nombre_unico;
                            $actualizar_archivo_sql = ", ruta_archivo = :ruta_archivo";
                        }
                    }
                }

                // 2. Ejecutamos la actualización para esta fila
                $sql_update = "UPDATE discursos SET cancion = :cancion" . $actualizar_archivo_sql . " WHERE id = :id";
                $stmt_update = $conn->prepare($sql_update);
                
                $stmt_update->bindParam(':cancion', $cancion);
                $stmt_update->bindParam(':id', $discurso_id, PDO::PARAM_INT);
                
                if ($ruta_archivo_final != null) {
                    $stmt_update->bindParam(':ruta_archivo', $ruta_archivo_final);
                }

                $stmt_update->execute();
            }

            // Si todo el bucle termina sin errores, confirmamos en la base de datos
            $conn->commit();
            header("Location: ver_discursos.php?orador_id=" . $orador_id);
            exit();

        } catch(PDOException $e) {
            $conn->rollBack();
            echo "Error al guardar en lote: " . $e->getMessage();
        }
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>