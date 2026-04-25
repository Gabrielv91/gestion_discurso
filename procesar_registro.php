<?php
// procesar_registro.php
session_start();
require_once 'conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: registro.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();

// 1. Recibir los datos del formulario exactos como los llamaste
$codigo_usuario = trim($_POST['codigo_usuario']);
$pass_plana = $_POST['password'];
$telefono = trim($_POST['telefono']);

// Preguntas y respuestas (pasamos las respuestas a minúsculas para evitar errores futuros)
$p1 = $_POST['preg_seguridad_1'];
$r1 = strtolower(trim($_POST['resp_seguridad_1']));
$p2 = $_POST['preg_seguridad_2'];
$r2 = strtolower(trim($_POST['resp_seguridad_2']));
$p3 = $_POST['preg_seguridad_3'];
$r3 = strtolower(trim($_POST['resp_seguridad_3']));

// Encriptar la contraseña por seguridad
$pass_hash = password_hash($pass_plana, PASSWORD_DEFAULT);

// Estado inicial: "Pendiente" hasta que un admin lo apruebe
$estado = 'Pendiente';
$rol = 'Coordinador';

try {
    // 2. Insertar en la base de datos (Ajusta el nombre de tu tabla si es distinto)
    $sql = "INSERT INTO usuarios (codigo_usuario, password, telefono, rol, estado, 
            preg_seguridad_1, resp_seguridad_1, 
            preg_seguridad_2, resp_seguridad_2, 
            preg_seguridad_3, resp_seguridad_3) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    $registro_exitoso = $stmt->execute([
        $codigo_usuario, $pass_hash, $telefono, $rol, $estado,
        $p1, $r1, $p2, $r2, $p3, $r3
    ]);

    if ($registro_exitoso) {
        
        // 3. Formatear teléfono para WhatsApp
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        if (substr($limpio, 0, 1) === '0') { 
            $num_wa = '58' . substr($limpio, 1); 
        } elseif (strlen($limpio) == 10 && substr($limpio, 0, 2) !== '58') { 
            $num_wa = '58' . $limpio; 
        } else {
            $num_wa = $limpio;
        }

        // 4. Armar el mensaje de WhatsApp
        $texto_wa = "¡Hola! Tu registro en el Directorio de Congregaciones ha sido recibido. 🎉\n\n";
        $texto_wa .= "👤 *Código de Usuario:* $codigo_usuario\n";
        $texto_wa .= "🔑 *Contraseña:* $pass_plana\n\n";
        $texto_wa .= "🛡️ *Tus Respuestas de Seguridad:*\n";
        $texto_wa .= "1️⃣ Mascota: " . $_POST['resp_seguridad_1'] . "\n";
        $texto_wa .= "2️⃣ Ciudad: " . $_POST['resp_seguridad_2'] . "\n";
        $texto_wa .= "3️⃣ Comida: " . $_POST['resp_seguridad_3'] . "\n\n";
        $texto_wa .= "⚠️ _Guarda este mensaje en un lugar seguro. Un administrador aprobará tu cuenta pronto._";

        $url_whatsapp = "https://api.whatsapp.com/send?phone=" . $num_wa . "&text=" . urlencode($texto_wa);

        // 5. Lanzar SweetAlert2 interactivo
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Procesando...</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <style>body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; }</style>
        </head>
        <body>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: '¡Registro Exitoso! 🎉',
                        text: 'Tus datos han sido guardados. ¿Deseas recibir una copia de tu código, contraseña y preguntas de seguridad por WhatsApp para que no se te olviden?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#25D366',
                        cancelButtonColor: '#7f8c8d',
                        confirmButtonText: '<span style=\"font-size: 1.1em;\">📲 Sí, enviar a mi WhatsApp</span>',
                        cancelButtonText: 'No, gracias',
                        reverseButtons: true,
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open('$url_whatsapp', '_blank');
                            window.location.href = 'index.php';
                        } else {
                            window.location.href = 'index.php';
                        }
                    });
                });
            </script>
        </body>
        </html>
        ";
        exit();
    }

} catch (PDOException $e) {
    // Si el código de usuario ya existe, mostramos error
    echo "<script>
            alert('❌ Error: El código de congregación ya está en uso o hubo un problema en la base de datos.');
            window.history.back();
          </script>";
    exit();
}
?>