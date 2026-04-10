<?php
// login.php
session_start();
require_once 'conexion/conexion.php';

// Verificamos si los datos fueron enviados por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codigo = trim($_POST['codigo_usuario']);
    $password = trim($_POST['password']);

    // Instanciamos la conexión
    $baseDatos = new Conexion();
    $conn = $baseDatos->obtenerConexion();

    if ($conn != null) {
        try {
            // Preparamos la consulta para evitar Inyección SQL
            $sql = "SELECT id, codigo_usuario, password, rol, estado FROM usuarios WHERE codigo_usuario = :codigo LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->execute();

            // Si encontramos el usuario
            if ($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verificamos la contraseña encriptada (usaremos password_hash al registrar)
                if (password_verify($password, $usuario['password'])) {
                    
                    // Verificamos el estado de la cuenta
                    if ($usuario['estado'] == 'Pendiente') {
                        echo "<script>alert('Tu cuenta está pendiente de aprobación por el Administrador.'); window.location.href='index.php';</script>";
                    } else if ($usuario['estado'] == 'Suspendido') {
                        echo "<script>alert('Esta cuenta ha sido suspendida.'); window.location.href='index.php';</script>";
                    } else {
                        // ¡Login exitoso! Guardamos datos en variables de sesión
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['rol'] = $usuario['rol'];
                        $_SESSION['codigo_usuario'] = $usuario['codigo_usuario'];

                        // Redirigimos al panel de control
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    echo "<script>alert('Contraseña incorrecta.'); window.location.href='index.php';</script>";
                }
            } else {
                echo "<script>alert('El código de congregación no existe.'); window.location.href='index.php';</script>";
            }
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
} else {
    // Si intentan entrar a login.php por la URL directamente sin enviar el formulario
    header("Location: index.php");
    exit();
}
?>