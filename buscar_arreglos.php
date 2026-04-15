<?php
// buscar_arreglos.php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// CAPTURAMOS LA FECHA SI VIENE DEL CALENDARIO
// Asegurarnos de que el formato de URL sea correcto
$fecha_buscada = isset($_GET['fecha']) ? $_GET['fecha'] : null;
$fecha_url = $fecha_buscada ? '&fecha=' . urlencode($fecha_buscada) : '';
$fecha_texto = $fecha_buscada ? date("d/m/Y", strtotime($fecha_buscada)) : "Sin fecha";

// 1. Obtener los datos de TU congregación
$sql_mi_cong = "SELECT id, latitud, longitud FROM congregaciones WHERE usuario_id = :uid LIMIT 1";
$stmt_mi = $conn->prepare($sql_mi_cong);
$stmt_mi->execute([':uid' => $usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);

$congregaciones = [];
$mi_id = $mi_cong ? $mi_cong['id'] : 0;

// Verificamos que tengas tus coordenadas configuradas
if ($mi_cong && !empty($mi_cong['latitud']) && !empty($mi_cong['longitud'])) {
    
    $mi_lat = $mi_cong['latitud'];
    $mi_lng = $mi_cong['longitud'];
    $distancia_maxima = 150; // Límite en kilómetros

    // 2. FÓRMULA DE HAVERSINE
    $sql = "SELECT id, nombre, coord_nombre, 
            (6371 * acos( cos( radians(:mi_lat) ) * cos( radians( latitud ) ) 
            * cos( radians( longitud ) - radians(:mi_lng) ) 
            + sin( radians(:mi_lat) ) * sin( radians( latitud ) ) ) ) AS distancia
            FROM congregaciones
            HAVING distancia <= $distancia_maxima
            ORDER BY distancia ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':mi_lat' => $mi_lat,
        ':mi_lng' => $mi_lng
    ]);
    $congregaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Si aún no has puesto tus coordenadas, muestra todas
    $sql = "SELECT *, '?' AS distancia FROM congregaciones ORDER BY nombre ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $congregaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio - Buscar Oradores</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #ecf0f1; margin: 0; color: #333; }
        
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; font-size: 1.6em; }
        .header p { margin: 5px 0 10px 0; color: #bdc3c7; }
        .header a { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.95em; }
        .header a:hover { text-decoration: underline; }

        .container { max-width: 800px; margin: 30px auto; padding: 0 15px; }

        .info-bar { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 25px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        .info-bar p { margin: 0; color: #2c3e50; }

        /* TARJETAS DE CONGREGACIONES */
        .card-cong { background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; transition: 0.2s; border: 1px solid #eee; border-left: 5px solid #bdc3c7; }
        .card-cong:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
        
        /* Estilo especial para MI congregación */
        .card-mia { border-left-color: #27ae60; background: #f9fdfa; }
        
        .cong-info h3 { margin: 0 0 5px 0; color: #2c3e50; font-size: 1.2em; }
        .cong-info p { margin: 0; color: #7f8c8d; font-size: 0.9em; }
        
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75em; font-weight: bold; margin-top: 8px; }
        .badge-mia { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-dist { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .btn-ver { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s; white-space: nowrap; }
        .btn-ver:hover { background: #2980b9; }
        .btn-ver-mio { background: #27ae60; }
        .btn-ver-mio:hover { background: #219150; }

        .empty-state { text-align: center; padding: 40px; background: white; border-radius: 10px; color: #7f8c8d; border: 1px dashed #bdc3c7; }
    </style>
</head>
<body>

    <header class="header">
        <h1>Buscar Orador Visitante</h1>
        <p>Selecciona una congregación para ver su lista de hermanos</p>
        <a href="calendario_arreglos.php">⬅ Volver a Mi Calendario</a>
    </header>

    <div class="container">
        
        <div class="info-bar">
            <div>
                <p>📅 Buscando para la fecha: <strong><?php echo $fecha_texto; ?></strong></p>
                <p style="font-size: 0.85em; color: #7f8c8d; margin-top: 4px;">Mostrando congregaciones en un radio de 150 km de tu ubicación.</p>
            </div>
        </div>

        <?php if (count($congregaciones) > 0): ?>
            <?php foreach ($congregaciones as $cong): ?>
                <?php 
                    $es_mia = ($cong['id'] == $mi_id); 
                    $clase_card = $es_mia ? 'card-mia' : '';
                    $clase_btn = $es_mia ? 'btn-ver-mio' : '';
                ?>
                
                <div class="card-cong <?php echo $clase_card; ?>">
                    <div class="cong-info">
                        <h3><?php echo htmlspecialchars($cong['nombre']); ?></h3>
                        <p>👤 Coordinador: <?php echo htmlspecialchars($cong['coord_nombre']); ?></p>
                        
                        <?php if ($es_mia): ?>
                            <span class="badge badge-mia">🏠 Mi Congregación Local</span>
                        <?php elseif ($cong['distancia'] !== '?'): ?>
                            <span class="badge badge-dist">📍 A <?php echo number_format($cong['distancia'], 1); ?> km</span>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <a href="ver_congregacion.php?id=<?php echo $cong['id'] . $fecha_url; ?>" class="btn-ver <?php echo $clase_btn; ?>">
                            <?php echo $es_mia ? 'Mis Oradores' : 'Ver Oradores'; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <h2 style="margin-top: 0; color: #34495e;">No hay congregaciones cercanas</h2>
                <p>No se encontraron otras congregaciones registradas a menos de 150 kilómetros de distancia con las coordenadas actuales.</p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>