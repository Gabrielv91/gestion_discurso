<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Congregación | Sistema de Discursos</title>
    <style>
        /* ESTILOS PREMIUM PARA EL REGISTRO */
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
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            border-top: 6px solid #3498db;
        }

        .header-text {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-text h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8em;
        }

        .header-text p {
            color: #7f8c8d;
            margin-top: 8px;
            font-size: 0.95em;
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

        /* Cajas de texto modernas */
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
            border-color: #3498db;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
        }

        .info-nota {
            display: block;
            font-size: 0.8em;
            color: #95a5a6;
            margin-top: 6px;
            line-height: 1.4;
        }

        /* Sección de Seguridad */
        .seccion-seguridad {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border: 1px dashed #bdc3c7;
            margin-top: 30px;
            margin-bottom: 25px;
        }

        .seccion-seguridad h3 {
            margin: 0 0 15px 0;
            font-size: 1em;
            color: #e67e22;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Botón de Enviar */
        .btn-ingresar {
            background: #27ae60;
            color: white;
            border: none;
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2);
        }

        .btn-ingresar:hover {
            background: #219150;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(39, 174, 96, 0.3);
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
    </style>
</head>

<body>

    <div class="login-card">
        <div class="header-text">
            <h1>🏢 Registro de Congregación</h1>
            <p>Completa los datos para solicitar acceso a la plataforma.</p>
        </div>
        <div style="text-align: left; margin-bottom: 20px;">
            <a href="index.php"
                style="display: inline-flex; align-items: center; gap: 5px; padding: 8px 12px; background: #f8f9fa; color: #34495e; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.9em; border: 1px solid #dcdde1; transition: 0.3s;"
                onmouseover="this.style.background='#ecf0f1'" onmouseout="this.style.background='#f8f9fa'">
                ⬅ Volver
            </a>
        </div>

        <form action="procesar_registro.php" method="POST">

            <div class="form-group">
                <label for="codigo">Código Único de Congregación:</label>
                <input type="text" id="codigo" name="codigo_usuario" required placeholder="Ej: BARRANCAS-1">
            </div>

            <div class="form-group">
                <label for="password">Contraseña de Acceso:</label>
                <input type="password" id="password" name="password" required placeholder="Crea una contraseña segura">
            </div>

            <div class="form-group">
                <label for="telefono">Número de Teléfono (WhatsApp):</label>
                <input type="tel" id="telefono" name="telefono" required placeholder="Ej: 04141234567" pattern="[0-9]+"
                    title="Ingresa solo números, sin guiones ni espacios">
                <span class="info-nota">🔒 Usaremos este número internamente para comunicarnos contigo y verificar tu
                    identidad como testigo de Jehová.</span>
            </div>

            <div class="seccion-seguridad">
                <h3>🛡️ Preguntas de Seguridad</h3>
                <span class="info-nota" style="margin-bottom: 15px; display: block;">Requeridas en caso de que olvides
                    tu contraseña en el futuro.</span>

                <div class="form-group">
                    <label for="preg1">¿Cuál es el nombre de tu primera mascota?</label>
                    <input type="hidden" name="preg_seguridad_1" value="¿Cuál es el nombre de tu primera mascota?">
                    <input type="text" id="preg1" name="resp_seguridad_1" required placeholder="Tu respuesta">
                </div>

                <div class="form-group">
                    <label for="preg2">¿En qué ciudad nació tu madre?</label>
                    <input type="hidden" name="preg_seguridad_2" value="¿En qué ciudad nació tu madre?">
                    <input type="text" id="preg2" name="resp_seguridad_2" required placeholder="Tu respuesta">
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label for="preg3">¿Cuál es tu comida favorita?</label>
                    <input type="hidden" name="preg_seguridad_3" value="¿Cuál es tu comida favorita?">
                    <input type="text" id="preg3" name="resp_seguridad_3" required placeholder="Tu respuesta">
                </div>
            </div>

            <button type="submit" class="btn-ingresar">Enviar Solicitud de Registro</button>
        </form>

        <div class="login-links">
            <a href="index.php">¿Ya tienes una cuenta aprobada? Inicia sesión aquí</a>
        </div>
    </div>

</body>

</html>