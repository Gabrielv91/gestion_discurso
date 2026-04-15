<?php
// cambiar_horario.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambio de Horario Anual</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; margin: 0; color: #333; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border-top: 5px solid #e67e22; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #34495e; }
        select, input[type="time"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 1em; box-sizing: border-box; }
        
        .alert-info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; border-radius: 4px; font-size: 0.9em; color: #2c3e50; line-height: 1.5; }
        
        .checkbox-group { display: flex; align-items: flex-start; gap: 10px; background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; }
        .checkbox-group input { width: 20px; height: 20px; margin-top: 2px; }
        
        .btn-submit { background: #e67e22; color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-size: 1.1em; font-weight: bold; cursor: pointer; margin-top: 20px; transition: 0.3s;}
        .btn-submit:hover { background: #d35400; }
    </style>
</head>
<body>

    <header class="header">
        <h1 style="margin:0;">🔄 Cambio de Horario Anual</h1>
        <p style="margin:5px 0 0; color:#bdc3c7;"><a href="dashboard.php" style="color: white;">⬅ Volver al Panel</a></p>
    </header>

    <div class="container">
        <div class="card">
            <div class="alert-info">
                <strong>¿Compartes Salón?</strong> Actualiza tu horario aquí. Si marcas la casilla, el sistema adelantará o atrasará automáticamente las fechas de los discursos que ya tienes programados para ajustarlos a tu nuevo día.
            </div>

            <form action="procesar_horario.php" method="POST">
                
                <div class="form-group">
                    <label for="dia">Nuevo Día de Reunión (Fin de Semana):</label>
                    <select id="dia" name="dia" required>
                        <option value="">Selecciona un día...</option>
                        <option value="Sábado">Sábado</option>
                        <option value="Domingo">Domingo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="hora">Nueva Hora:</label>
                    <input type="time" id="hora" name="hora" required>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="ajustar" name="ajustar_futuros" value="SI" checked>
                    <label for="ajustar" style="font-weight: normal; margin:0; color: #856404;">
                        <strong>Mover discursos futuros:</strong> Reprogramar automáticamente los discursos pendientes y aprobados para que encajen en este nuevo día y generarme los links de WhatsApp para avisar a los oradores.
                    </label>
                </div>

                <button type="submit" class="btn-submit" onclick="return confirm('¿Estás seguro de hacer este cambio masivo?');">
                    Aplicar Cambio de Horario
                </button>
            </form>
        </div>
    </div>

</body>
</html>