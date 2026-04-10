<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Discursos</title>
    
    <link rel="stylesheet" href="css/style.css">
    
    </head>
<body>

    <header>
        <h1>Sistema de Gestión de Discursos</h1>
    </header>

    <main class="login-container">
    <div class="login-card">
        <h2>Iniciar Sesión</h2>
        <p>Ingresa con el código de tu congregación</p>
        
        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="codigo">Código de Congregación:</label>
                <input type="text" id="codigo" name="codigo_usuario" required autocomplete="off" placeholder="Ej: CONG-123">
            </div>
            
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required placeholder="********">
            </div>
            
            <button type="submit" class="btn-ingresar">Ingresar al Sistema</button>
        </form>
        
        <div class="login-links">
            <a href="registro.php">¿Tu congregación no está registrada? Solicita acceso aquí.</a>
        </div>
    </div>
</main>

    <button id="btn-instalar" style="display: none;">Instalar App</button>

    <script src="js/app.js"></script>
</body>
</html>