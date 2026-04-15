<?php
// directorio_congregaciones.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Obtenemos TU congregación y TUS coordenadas
$sql_mi = "SELECT id, latitud, longitud FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);

$congregaciones = [];
$error_coordenadas = false;

// 2. Verificamos que tengas coordenadas configuradas para poder medir distancias
if ($mi_cong && !empty($mi_cong['latitud']) && !empty($mi_cong['longitud'])) {
    
    $mi_cong_id = $mi_cong['id'];
    $mi_lat = $mi_cong['latitud'];
    $mi_lng = $mi_cong['longitud'];
    $distancia_maxima = 70; // 70 Kilómetros a la redonda

    // CONSULTA MAESTRA: Añadimos GROUP_CONCAT para traer la lista de oradores
    $sql_directorio = "
        SELECT c.id, c.nombre, c.ubicacion_texto, c.coord_nombre, c.coord_telefono, c.correo,
               COUNT(o.id) AS total_oradores,
               GROUP_CONCAT(o.nombre SEPARATOR '|') AS lista_oradores,
               (6371 * acos( cos( radians(:mi_lat) ) * cos( radians( c.latitud ) ) 
               * cos( radians( c.longitud ) - radians(:mi_lng) ) 
               + sin( radians(:mi_lat) ) * sin( radians( c.latitud ) ) ) ) AS distancia
        FROM congregaciones c
        LEFT JOIN oradores o ON c.id = o.congregacion_id
        WHERE c.id != :mi_id
        GROUP BY c.id, c.nombre, c.ubicacion_texto, c.coord_nombre, c.coord_telefono, c.correo, c.latitud, c.longitud
        HAVING distancia <= $distancia_maxima
        ORDER BY distancia ASC
    ";
    
    $stmt_dir = $conn->prepare($sql_directorio);
    $stmt_dir->execute([
        ':mi_lat' => $mi_lat,
        ':mi_lng' => $mi_lng,
        ':mi_id' => $mi_cong_id
    ]);
    $congregaciones = $stmt_dir->fetchAll(PDO::FETCH_ASSOC);

} else {
    $error_coordenadas = true;
}

function formatearTelefonoWA($numero) {
    $limpio = preg_replace('/[^0-9]/', '', $numero);
    if (substr($limpio, 0, 1) === '0') { return '58' . substr($limpio, 1); } 
    elseif (strlen($limpio) == 10 && substr($limpio, 0, 2) !== '58') { return '58' . $limpio; }
    return $limpio;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Congregaciones</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #ecf0f1; margin: 0; padding-bottom: 40px;}
        .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* BUSCADOR */
        .search-container { max-width: 600px; margin: 0 auto 30px auto; position: relative; }
        #buscador { width: 100%; padding: 15px 20px 15px 45px; border-radius: 30px; border: 2px solid #bdc3c7; font-size: 1.1em; outline: none; transition: 0.3s; box-sizing: border-box; }
        #buscador:focus { border-color: #3498db; box-shadow: 0 0 10px rgba(52,152,219,0.2); }
        .search-icon { position: absolute; left: 18px; top: 15px; font-size: 1.2em; color: #7f8c8d; }

        .grid-directorio { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        .card-cong { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #ddd; display: flex; flex-direction: column; transition: 0.3s;}
        .card-cong.hidden { display: none; } /* Clase para ocultar al buscar */
        
        .card-header { background: #34495e; color: white; padding: 15px; font-size: 1.2em; font-weight: bold; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;}
        
        .badge-oradores { background: #f1c40f; color: #2c3e50; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .badge-dist { background: #e8f4f8; color: #2980b9; padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        
        .card-body { padding: 20px; flex-grow: 1;}
        .info-item { margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px; }
        .info-icon { font-size: 1.2em; }
        .info-text { color: #555; font-size: 0.95em; line-height: 1.4; width: 100%; }
        .info-text strong { color: #2c3e50; display: block; margin-bottom: 2px; }
        
        .contact-actions { display: flex; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
        .btn-wa { display: inline-block; background: #25D366; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-size: 0.85em; font-weight: bold; transition: background 0.2s; text-align: center; flex-grow: 1;}
        .btn-wa:hover { background: #1ebe57; }
        
        .btn-correo { display: inline-block; background: #3498db; color: white; text-decoration: none; padding: 8px 12px; border-radius: 4px; font-size: 0.85em; font-weight: bold; transition: background 0.2s; text-align: center; flex-grow: 1;}
        .btn-correo:hover { background: #2980b9; }

        .btn-ver-oradores { width: 100%; background: #f39c12; color: white; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 15px; transition: 0.2s; }
        .btn-ver-oradores:hover { background: #e67e22; }

        .alert-box { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }

        /* MODAL */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); }
        .modal-content { background: white; margin: 10% auto; padding: 25px; width: 90%; max-width: 400px; border-radius: 12px; position: relative; box-shadow: 0 5px 25px rgba(0,0,0,0.2); }
        .close-modal { position: absolute; right: 20px; top: 15px; font-size: 25px; cursor: pointer; color: #7f8c8d; }
        .orador-lista-item { padding: 10px; border-bottom: 1px solid #eee; color: #34495e; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

    <header class="header">
        <h1 style="margin:0;">📖 Directorio de Congregaciones</h1>
        <p style="margin: 5px 0 0 0; color: #bdc3c7;">Mostrando contactos a un máximo de 70 km</p>
        <p style="margin-top: 15px;"><a href="control_arreglos.php" style="color: white; text-decoration: underline;">⬅ Volver al Panel Maestro</a></p>
    </header>

    <div class="container">
        
        <?php if ($error_coordenadas): ?>
            <div class="alert-box">
                <h3 style="margin-top: 0;">⚠️ Faltan tus coordenadas</h3>
                <p>Para mostrarte quién está a 70 km, primero necesitamos saber dónde estás tú. Por favor configura tu ubicación en el mapa de tu perfil.</p>
                <a href="control_arreglos.php" style="display: inline-block; background: #856404; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; margin-top: 10px; font-weight: bold;">Ir a mi Perfil</a>
            </div>
        <?php else: ?>

            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" id="buscador" placeholder="Buscar congregación por nombre..." onkeyup="filtrar()">
            </div>

            <?php if (count($congregaciones) > 0): ?>
                <div class="grid-directorio">
                    <?php foreach($congregaciones as $c): ?>
                    <div class="card-cong" data-nombre="<?php echo strtolower(htmlspecialchars($c['nombre'])); ?>">
                        <div class="card-header">
                            <span><?php echo htmlspecialchars($c['nombre']); ?></span>
                            <div style="display: flex; gap: 5px;">
                                <span class="badge-dist">📍 <?php echo number_format($c['distancia'], 1); ?> km</span>
                                <span class="badge-oradores">🗣️ <?php echo $c['total_oradores']; ?></span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <button class="btn-ver-oradores" onclick="abrirModal('<?php echo addslashes(htmlspecialchars($c['nombre'])); ?>', '<?php echo addslashes(htmlspecialchars($c['lista_oradores'])); ?>')">
                                🗣️ Ver Lista de Oradores (<?php echo $c['total_oradores']; ?>)
                            </button>

                            <div class="info-item">
                                <span class="info-icon">📍</span>
                                <div class="info-text">
                                    <strong>Ubicación del Salón:</strong>
                                    <?php echo htmlspecialchars($c['ubicacion_texto'] ? $c['ubicacion_texto'] : 'No registrada'); ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-icon">👤</span>
                                <div class="info-text">
                                    <strong>Coordinador:</strong>
                                    <?php echo htmlspecialchars($c['coord_nombre'] ? $c['coord_nombre'] : 'No registrado'); ?>
                                </div>
                            </div>
                            
                            <div class="info-item" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                                <span class="info-icon">📞</span>
                                <div class="info-text">
                                    <strong>Teléfono:</strong>
                                    <?php echo htmlspecialchars($c['coord_telefono'] ? $c['coord_telefono'] : 'No registrado'); ?>
                                    
                                    <?php if(!empty($c['coord_telefono'])): 
                                        $num_wa = formatearTelefonoWA($c['coord_telefono']);
                                    ?>
                                    <div class="contact-actions">
                                        <a href="https://api.whatsapp.com/send?phone=<?php echo $num_wa; ?>" target="_blank" class="btn-wa">📲 WhatsApp</a>
                                        <a href="tel:<?php echo $num_wa; ?>" class="btn-wa" style="background: #34495e;">📞 Llamar</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="info-item" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                                <span class="info-icon">✉️</span>
                                <div class="info-text">
                                    <strong>Correo Electrónico:</strong>
                                    <?php echo htmlspecialchars($c['correo'] ? $c['correo'] : 'No registrado'); ?>
                                    
                                    <?php if(!empty($c['correo'])): ?>
                                    <div class="contact-actions">
                                        <a href="mailto:<?php echo htmlspecialchars($c['correo']); ?>" class="btn-correo">📧 Enviar Correo</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 20px; background: white; border-radius: 12px; border: 1px dashed #bdc3c7;">
                    <h2 style="color: #7f8c8d; margin-top: 0;">No hay congregaciones cercanas</h2>
                    <p style="color: #95a5a6;">Aún no hay otras congregaciones registradas a 70 kilómetros o menos desde tu ubicación actual.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <div id="modalOradores" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
            <h2 id="modalTitulo" style="color:#2c3e50; margin-top:0; border-bottom:2px solid #f39c12; padding-bottom:10px; font-size:1.3em;"></h2>
            <div id="modalCuerpo" style="max-height: 350px; overflow-y: auto;">
                </div>
        </div>
    </div>

    <script>
        // Función del Buscador
        function filtrar() {
            let filtro = document.getElementById('buscador').value.toLowerCase();
            let tarjetas = document.querySelectorAll('.card-cong');

            tarjetas.forEach(tarjeta => {
                let nombre = tarjeta.getAttribute('data-nombre');
                if(nombre.includes(filtro)) {
                    tarjeta.classList.remove('hidden');
                } else {
                    tarjeta.classList.add('hidden');
                }
            });
        }

        // Funciones del Modal
        function abrirModal(congNombre, oradoresString) {
            document.getElementById('modalTitulo').innerText = "Oradores de " + congNombre;
            let cuerpo = document.getElementById('modalCuerpo');
            cuerpo.innerHTML = "";

            if (!oradoresString) {
                cuerpo.innerHTML = "<p style='text-align:center; color:#95a5a6; padding:20px;'>Aún no tienen oradores registrados.</p>";
            } else {
                let oradores = oradoresString.split('|');
                oradores.forEach(nombre => {
                    let div = document.createElement('div');
                    div.className = 'orador-lista-item';
                    div.innerHTML = "<span style='font-size:1.2em;'>🗣️</span> <strong>" + nombre + "</strong>";
                    cuerpo.appendChild(div);
                });
            }

            document.getElementById('modalOradores').style.display = "block";
        }

        function cerrarModal() {
            document.getElementById('modalOradores').style.display = "none";
        }

        window.onclick = function(event) {
            let modal = document.getElementById('modalOradores');
            if (event.target == modal) {
                cerrarModal();
            }
        }
    </script>

</body>
</html>