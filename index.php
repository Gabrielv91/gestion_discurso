<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Sistema de Discursos</title>
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2c3e50">
    <link rel="apple-touch-icon" href="icono-192.png">

    <style>
        /* ESTILOS PREMIUM PARA EL LOGIN */
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
            max-width: 420px; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            padding: 40px 35px; 
            border-top: 6px solid #2c3e50; /* Azul oscuro corporativo */
        }

        .header-text { text-align: center; margin-bottom: 30px; }
        .header-text h1 { margin: 0; color: #2c3e50; font-size: 1.6em; }
        .header-text p { color: #7f8c8d; margin-top: 8px; font-size: 0.95em; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; color: #34495e; margin-bottom: 8px; font-size: 0.9em; }
        
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

        .btn-ingresar { 
            background: #2c3e50; 
            color: white; 
            border: none; 
            width: 100%; 
            padding: 14px; 
            border-radius: 8px; 
            font-size: 1.1em; 
            font-weight: bold; 
            cursor: pointer; 
            transition: 0.3s; 
            box-shadow: 0 4px 6px rgba(44, 62, 80, 0.2);
            margin-top: 10px;
        }
        .btn-ingresar:hover { background: #1a252f; transform: translateY(-2px); box-shadow: 0 6px 12px rgba(44, 62, 80, 0.3); }

        .login-links { text-align: center; margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }
        .login-links a { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.9em; transition: 0.2s; }
        .login-links a:hover { color: #2980b9; text-decoration: underline; }
        
        .link-recuperar { color: #e67e22 !important; }
        .link-recuperar:hover { color: #d35400 !important; }

        /* ESTILOS DEL BOTÓN PWA FLOTANTE */
        #btn-instalar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #f39c12;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 1em;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
            cursor: pointer;
            z-index: 9999;
            transition: transform 0.3s, background-color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        #btn-instalar:hover {
            background-color: #d68910;
            transform: scale(1.05);
        }
    </style>
</head>
<body>

    <main class="login-container">
        <div class="login-card">
            
            <div class="header-text">
                <h1>Sistema de Gestión</h1>
                <p>Ingresa con el código de tu congregación</p>
            </div>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="codigo">Código de Congregación:</label>
                    <input type="text" id="codigo" name="codigo_usuario" required autocomplete="off" placeholder="Ej: BARRANCAS-1">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn-ingresar">Ingresar al Sistema</button>
            </form>
            
            <div class="login-links">
                <a href="recuperar.php" class="link-recuperar">¿Olvidaste tu contraseña? Recupérala aquí</a>
                
                <hr style="border: 0; border-top: 1px solid #eee; width: 100%; margin: 5px 0;">
                
                <a href="registro.php">¿Tu congregación no está registrada? Solicita acceso</a>
            </div>
        </div>
    </main>

    <button id="btn-instalar" style="display: none;">📲 Instalar App</button>
    
    <script src="js/app.js"></script>

    <script>
        let eventoInstalacion = null;
        const btnInstalar = document.getElementById('btn-instalar');

        // 1. Registramos el Service Worker silenciosamente al entrar al login
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }

        // 2. Comprobamos si la app YA está instalada (pantalla completa)
        if (window.matchMedia('(display-mode: standalone)').matches) {
            btnInstalar.style.display = 'none';
        }

        // 3. Escuchamos al navegador para ver si nos da permiso de mostrar el botón
        window.addEventListener('beforeinstallprompt', (e) => {
            // Evitamos que salga el banner feo nativo de Android
            e.preventDefault();
            // Guardamos el evento para dispararlo cuando toquen nuestro botón naranja
            eventoInstalacion = e;
            // ¡Mostramos nuestro botón flotante!
            btnInstalar.style.display = 'flex';
        });

        // 4. Qué pasa cuando tocan el botón naranja
        btnInstalar.addEventListener('click', async () => {
            if (!eventoInstalacion) return;
            
            // Disparamos la ventana de instalación del sistema operativo
            eventoInstalacion.prompt();
            
            // Esperamos la respuesta del usuario
            const { outcome } = await eventoInstalacion.userChoice;
            if (outcome === 'accepted') {
                console.log('El usuario instaló la App');
            }
            
            // Limpiamos y ocultamos el botón
            eventoInstalacion = null;
            btnInstalar.style.display = 'none';
        });

        // 5. Si la app se instala con éxito, el botón se oculta automáticamente
        window.addEventListener('appinstalled', () => {
            btnInstalar.style.display = 'none';
        });
    </script>
</body>
</html>