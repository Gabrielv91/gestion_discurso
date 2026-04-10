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
$fecha_url = isset($_GET['fecha']) ? '&fecha=' . $_GET['fecha'] : '';

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
    $distancia_maxima = 70; // Límite en kilómetros

    // 2. FÓRMULA DE HAVERSINE: (Le quitamos el WHERE id != mi_id para que te incluya)
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
    // Si aún no has puesto tus coordenadas, muestra todas (incluyéndote a ti)
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
    <title>Buscar Oradores</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header style="background-color: #2c3e50;">
        <h1>Directorio de Congregaciones</h1>
        <p><a href="calendario_arreglos.php" style="color: white;">Volver al Calendario</a></p>
    </header>

    <main style="padding: 20px;">
        <div class="admin-container">
            <p style="margin-bottom: 20px; color: #7f8c8d;">
                <em>Mostrando congregaciones en un radio de 150 km.</em>
            </p>

            <?php if (count($congregaciones) > 0): ?>
                <?php foreach ($congregaciones as $cong): ?>
                    
                    <?php $es_mia = ($cong['id'] == $mi_id); ?>
                    
                    <div style="border: <?php echo $es_mia ? '2px solid #2ecc71' : '1px solid #ccc'; ?>; padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px; background-color: <?php echo $es_mia ? '#f0fdf4' : '#fdfdfd'; ?>;">
                        <div>
                            <strong style="font-size: 1.1em; color: #2c3e50;"><?php echo htmlspecialchars($cong['nombre']); ?></strong><br>
                            <small style="color: #666;">Coord: <?php echo htmlspecialchars($cong['coord_nombre']); ?></small>
                            
                            <?php if ($es_mia): ?>
                                <br><span style="display: inline-block; margin-top: 5px; background-color: #2ecc71; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; color: white; font-weight: bold;">
                                    🏠 Mi Congregación
                                </span>
                            <?php elseif ($cong['distancia'] !== '?'): ?>
                                <br><span style="display: inline-block; margin-top: 5px; background-color: #ecf0f1; padding: 3px 8px; border-radius: 12px; font-size: 0.8em; color: #e67e22; font-weight: bold;">
                                    📍 A <?php echo number_format($cong['distancia'], 1); ?> km de distancia
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="ver_congregacion.php?id=<?php echo $cong['id'] . $fecha_url; ?>" class="btn-ver" style="background: <?php echo $es_mia ? '#27ae60' : '#3498db'; ?>; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">
                            Ver Oradores
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="mensaje-vacio">
                    <p>No se encontraron congregaciones a menos de 150 kilómetros.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>