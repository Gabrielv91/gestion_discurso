<?php
// seguridad.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguridad de la Cuenta</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; padding-bottom: 40px; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header a { color: #bdc3c7; text-decoration: underline; font-size: 0.9em; }
        .container { max-width: 700px; margin: 30px auto; padding: 0 20px; }
        
        .caja-seguridad { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); border-top: 5px solid #e74c3c; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { display: block; font-weight: bold; color: #34495e; margin-bottom: 5px; font-size: 0.9em; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #bdc3c7; border-radius: 6px; font-size: 1em; box-sizing: border-box; background: #fdfdfd; transition: border 0.3s; }
        input:focus { border-color: #3498db; outline: none; background: #fff; }
        
        .btn-submit { background: #e74c3c; color: white; border: none; padding: 12px 20px; font-size: 1.1em; border-radius: 6px; cursor: pointer; width: 100%; font-weight: bold; transition: 0.3s; margin-top: 20px; }
        .btn-submit:hover { background: #c0392b; }
    </style>
</head>
<body>

    <header class="header">
        <h1 style="margin: 0 0 10px 0;">Seguridad de la Cuenta</h1>
        <a href="dashboard.php">⬅ Volver al Panel Maestro</a>
    </header>

    <div class="container">
        <div class="caja-seguridad">
            <h2 style="color: #2c3e50; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px;">🔒 Actualizar Credenciales</h2>
            <p style="color: #7f8c8d; font-size: 0.9em; margin-bottom: 20px;">
                Aquí puedes cambiar tu contraseña y configurar tus respuestas de seguridad para recuperar el acceso si lo pierdes.
            </p>

            <form action="actualizar_seguridad.php" method="POST">
                
                <div style="margin-bottom: 25px; background: #fdf2f0; padding: 15px; border-radius: 8px; border: 1px solid #fadbd8;">
                    <label for="pass_actual" style="color: #c0392b;">Contraseña Actual (Obligatoria para guardar cambios):</label>
                    <input type="password" id="pass_actual" name="pass_actual" required placeholder="Ingresa tu contraseña actual">
                </div>

                <div class="form-grid" style="margin-bottom: 30px;">
                    <div>
                        <label for="nueva_pass">Nueva Contraseña:</label>
                        <input type="password" id="nueva_pass" name="nueva_pass" placeholder="Déjalo en blanco si no cambias">
                    </div>
                    <div>
                        <label for="conf_pass">Confirmar Nueva Contraseña:</label>
                        <input type="password" id="conf_pass" name="conf_pass" placeholder="Repite la nueva contraseña">
                    </div>
                </div>

                <div style="border: 1px dashed #bdc3c7; padding: 20px; border-radius: 8px; background: #fafbfc; margin-bottom: 20px;">
                    <h3 style="color: #e67e22; font-size: 1.1em; margin-top: 0; margin-bottom: 5px;">🛡️ Preguntas de Seguridad</h3>
                    <p style="font-size: 0.85em; color: #7f8c8d; margin-bottom: 15px;">Solo llena los campos que desees actualizar.</p>
                    
                    <div style="margin-bottom: 15px;">
                        <label>¿Cuál es el nombre de tu primera mascota?</label>
                        <input type="text" name="respuesta_1" placeholder="Nueva respuesta secreta">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label>¿En qué ciudad nació tu madre?</label>
                        <input type="text" name="respuesta_2" placeholder="Nueva respuesta secreta">
                    </div>

                    <div style="margin-bottom: 10px;">
                        <label>¿Cuál es tu comida favorita?</label>
                        <input type="text" name="respuesta_3" placeholder="Nueva respuesta secreta">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    🔐 Guardar Cambios de Seguridad
                </button>
            </form>
        </div>
    </div>

</body>
</html>