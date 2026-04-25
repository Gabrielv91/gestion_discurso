<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | Sistema de Discursos</title>
    <style>
        /* ESTILOS PREMIUM (Iguales a login y registro) */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f4f7f6 0%, #e0eafc 100%);
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 450px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px 35px;
            border-top: 6px solid #e67e22;
            /* Color Naranja para "Alerta/Recuperación" */
        }

        .header-text {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-text h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5em;
        }

        .header-text p {
            color: #7f8c8d;
            margin-top: 8px;
            font-size: 0.95em;
            line-height: 1.4;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #dcdde1;
            border-radius: 8px;
            font-size: 1em;
            color: #2c3e50;
            background-color: #fcfcfc;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e67e22;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.15);
        }

        .btn-ingresar {
            background: #e67e22;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(230, 126, 34, 0.2);
            margin-top: 10px;
        }

        .btn-ingresar:hover {
            background: #d35400;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(230, 126, 34, 0.3);
        }

        .btn-admin {
            background: #25D366;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            box-sizing: border-box;
        }

        .btn-admin:hover {
            background: #1ebe57;
            transform: translateY(-2px);
        }

        .login-links {
            text-align: center;
            margin-top: 25px;
        }

        .login-links a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9em;
            transition: 0.2s;
        }

        .login-links a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .info-nota {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>

<body>

    <main class="login-container">
        <div class="login-card">

            <div class="header-text">
                <h1>🔐 Recuperar Contraseña</h1>
                <p>Ingresa tu código de congregación y responde a las preguntas de seguridad.</p>
            </div>
            <div style="text-align: left; margin-bottom: 20px;">
                <a href="index.php"
                    style="display: inline-flex; align-items: center; gap: 5px; padding: 8px 12px; background: #f8f9fa; color: #34495e; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.9em; border: 1px solid #dcdde1; transition: 0.3s;"
                    onmouseover="this.style.background='#ecf0f1'" onmouseout="this.style.background='#f8f9fa'">
                    ⬅ Volver
                </a>
            </div>

            <form action="procesar_recuperacion.php" method="POST">

                <div class="form-group">
                    <label for="codigo">Código de Congregación:</label>
                    <input type="text" id="codigo" name="codigo_usuario" required autocomplete="off"
                        placeholder="Ej: BARRANCAS-1">
                </div>

                <hr style="border: 0; border-top: 1px dashed #bdc3c7; margin: 25px 0;">

                <div class="form-group">
                    <label for="resp1">1. ¿Cuál es el nombre de tu primera mascota?</label>
                    <input type="text" id="resp1" name="resp_seguridad_1" required placeholder="Tu respuesta">
                </div>

                <div class="form-group">
                    <label for="resp2">2. ¿En qué ciudad nació tu madre?</label>
                    <input type="text" id="resp2" name="resp_seguridad_2" required placeholder="Tu respuesta">
                </div>

                <div class="form-group">
                    <label for="resp3">3. ¿Cuál es tu comida favorita?</label>
                    <input type="text" id="resp3" name="resp_seguridad_3" required placeholder="Tu respuesta">
                </div>

                <div class="form-group"
                    style="margin-top: 25px; background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #3498db;">
                    <label for="nueva_password" style="color: #2980b9;">🔑 Crea una nueva contraseña:</label>
                    <input type="password" id="nueva_password" name="nueva_password" required
                        placeholder="Ingresa tu nueva contraseña">
                    <span class="info-nota">Si las respuestas de arriba son correctas, esta será tu nueva clave de
                        acceso.</span>
                </div>

                <button type="submit" class="btn-ingresar">Verificar y Cambiar Contraseña</button>
            </form>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">

            <div style="text-align: center;">
                <p style="font-size: 0.85em; color: #7f8c8d; margin-bottom: 10px;">¿No recuerdas las respuestas o tu
                    cuenta fue bloqueada?</p>

                <?php
                // Aquí pones TU número de teléfono (El del administrador del sistema)
                // Formato internacional sin el + (Ej: 584141234567)
                $admin_whatsapp = "584145021471"; // <-- CAMBIA ESTO POR TU NÚMERO
                
                $mensaje = "Hola Administrador. Necesito ayuda para restablecer la contraseña de mi congregación en la plataforma.";
                $enlace_ayuda = "https://wa.me/" . $admin_whatsapp . "?text=" . urlencode($mensaje);
                ?>
                <a href="<?php echo $enlace_ayuda; ?>" target="_blank" class="btn-admin">
                    💬 Contactar Soporte (WhatsApp)
                </a>
            </div>

            <div class="login-links">
                <a href="index.php">⬅ Volver a Iniciar Sesión</a>
            </div>
        </div>
    </main>

</body>

</html>