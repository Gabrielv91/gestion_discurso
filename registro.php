<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Congregación</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Sistema de Gestión de Discursos</h1>
    </header>

    <main class="login-container">
        <div class="login-card" style="max-width: 500px;">
            <h2>Registro de Congregación</h2>
            <p>Completa los datos para solicitar acceso al sistema.</p>
            
            <form action="procesar_registro.php" method="POST">
                <div class="input-group">
                    <label for="codigo">Código Único de Congregación:</label>
                    <input type="text" id="codigo" name="codigo_usuario" required placeholder="Ej: CONG-123">
                </div>
                
                <div class="input-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required placeholder="Crea una contraseña segura">
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                <p style="font-size: 0.85rem; margin-bottom: 10px;">Preguntas de Seguridad (para recuperar acceso)</p>

                <div class="input-group">
                    <label for="preg1">Pregunta 1: ¿Cuál es el nombre de tu primera mascota?</label>
                    <input type="hidden" name="preg_seguridad_1" value="¿Cuál es el nombre de tu primera mascota?">
                    <input type="text" id="preg1" name="resp_seguridad_1" required>
                </div>

                <div class="input-group">
                    <label for="preg2">Pregunta 2: ¿En qué ciudad nació tu madre?</label>
                    <input type="hidden" name="preg_seguridad_2" value="¿En qué ciudad nació tu madre?">
                    <input type="text" id="preg2" name="resp_seguridad_2" required>
                </div>

                <div class="input-group">
                    <label for="preg3">Pregunta 3: ¿Cuál es tu comida favorita?</label>
                    <input type="hidden" name="preg_seguridad_3" value="¿Cuál es tu comida favorita?">
                    <input type="text" id="preg3" name="resp_seguridad_3" required>
                </div>
                
                <button type="submit" class="btn-ingresar">Enviar Solicitud de Registro</button>
            </form>
            
            <div class="login-links">
                <a href="index.php">¿Ya tienes cuenta? Inicia sesión aquí.</a>
            </div>
        </div>
    </main>
</body>
</html>