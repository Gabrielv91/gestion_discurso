<?php
session_start();
require_once 'conexion/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Obtener datos y coordenadas de MI congregación
$stmt_mi = $conn->prepare("SELECT id, latitud, longitud FROM congregaciones WHERE usuario_id = ?");
$stmt_mi->execute([$usuario_id]);
$mi_cong = $stmt_mi->fetch(PDO::FETCH_ASSOC);

$mi_id = $mi_cong['id'];
$mi_lat = $mi_cong['latitud'];
$mi_lng = $mi_cong['longitud'];

// 2. LA SUPER CONSULTA SQL (Vacunada contra distancias cero y ajustada para PDO)
// 2. LA SUPER CONSULTA SQL (Apuntando a la tabla correcta 'discursos')
$sql = "
    SELECT 
        d.numero, 
        d.tema,
        
        (SELECT COUNT(*) FROM solicitudes s
         WHERE s.congregacion_solicitante_id = :mi_id_1 
         AND s.numero_discurso = d.numero 
         AND s.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
         AND s.estado = 'Aprobado') AS discurso_dado_reciente,

        (
            SELECT GROUP_CONCAT(CONCAT(o.nombre, ' ', o.apellido, ' (', c.nombre, ') - ID:', o.id) SEPARATOR '|')
            -- ¡AQUÍ ESTÁ LA CORRECCIÓN! Cambiamos orador_discursos por discursos
            FROM discursos od
            JOIN oradores o ON od.orador_id = o.id
            JOIN congregaciones c ON o.congregacion_id = c.id
            WHERE od.numero_discurso = d.numero
            
            -- REGLA 1: Es de mi congregación OR está a menos de 70km
            AND (
                c.id = :mi_id_3 
                OR 
                (6371 * acos( LEAST(1.0, cos( radians(:mi_lat_1) ) * cos( radians( c.latitud ) ) 
                * cos( radians( c.longitud ) - radians(:mi_lng) ) 
                + sin( radians(:mi_lat_2) ) * sin( radians( c.latitud ) ) ) )) <= 70
            )
            
            -- REGLA 2: No ha venido en los últimos 6 meses
            AND o.id NOT IN (
                SELECT s2.orador_id FROM solicitudes s2
                WHERE s2.congregacion_solicitante_id = :mi_id_2 
                AND s2.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                AND s2.estado = 'Aprobado'
            )
        ) AS oradores_disponibles

    FROM catalogo_discursos d
    ORDER BY d.numero ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':mi_id_1' => $mi_id,
    ':mi_id_2' => $mi_id,
    ':mi_id_3' => $mi_id,
    ':mi_lat_1' => $mi_lat,
    ':mi_lat_2' => $mi_lat,
    ':mi_lng'  => $mi_lng
]);
$lista_discursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ¡AQUÍ CIERRA EL PHP!
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Discursos | Sistema de Gestión</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #ecf0f1; margin: 0; padding-bottom: 50px; }
        .header { background: #2c3e50; color: white; padding: 25px; text-align: center; }
        .header a { color: #bdc3c7; text-decoration: underline; font-size: 0.9em; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        
        .search-box { width: 100%; padding: 15px; border-radius: 8px; border: 2px solid #bdc3c7; font-size: 1.1em; margin-bottom: 25px; box-sizing: border-box;}
        .search-box:focus { border-color: #3498db; outline: none; }

        .discurso-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 5px solid #3498db; display: flex; flex-direction: column; gap: 10px;}
        .discurso-bloqueado { border-left-color: #e74c3c; opacity: 0.8; background: #fdf2f0;}
        
        .numero-tema { font-size: 1.2em; font-weight: bold; color: #2c3e50; }
        .badge-rojo { background: #e74c3c; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; display: inline-block;}
        .badge-verde { background: #27ae60; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; display: inline-block;}
        
        .lista-oradores { background: #f8f9fa; padding: 10px; border-radius: 6px; border: 1px dashed #bdc3c7; margin-top: 10px; }
        .orador-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; }
        .orador-item:last-child { border-bottom: none; }
        
        .btn-solicitar { background: #f39c12; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 0.9em; transition: 0.2s;}
        .btn-solicitar:hover { background: #d68910; }
    </style>
</head>
<body>

    <header class="header">
        <h1 style="margin: 0 0 10px 0;">📋 Lista de los 194 Discursos</h1>
        <a href="dashboard.php">⬅ Volver al Panel Maestro</a>
    </header>

    <div class="container">
        
        <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f39c12; font-size: 0.9em; color: #555;">
            <strong>Filtros Automáticos Aplicados:</strong> El sistema muestra oradores locales y visitantes a menos de 70km. Se ocultan los hermanos y discursos que ya pasaron por tu congregación en los últimos 6 meses.
        </div>

        <input type="text" id="buscador" class="search-box" placeholder="🔍 Buscar por número o tema del discurso..." onkeyup="filtrarDiscursos()">

        <div id="lista-contenedor">
            <?php foreach($lista_discursos as $d): ?>
                
                <?php 
                $bloqueado = $d['discurso_dado_reciente'] > 0; 
                ?>

                <div class="discurso-card <?php echo $bloqueado ? 'discurso-bloqueado' : ''; ?>" data-busqueda="<?php echo strtolower($d['numero'] . ' ' . $d['tema']); ?>">
                    
                    <div class="numero-tema">
                        Núm. <?php echo $d['numero']; ?>: <?php echo htmlspecialchars($d['tema']); ?>
                    </div>

                    <?php if ($bloqueado): ?>
                        <div>
                            <span class="badge-rojo">🚫 Discurso dado en los últimos 6 meses</span>
                        </div>
                    <?php else: ?>
                        
                        <?php if (empty($d['oradores_disponibles'])): ?>
                            <div><span class="badge-rojo" style="background: #95a5a6;">No hay oradores disponibles a 70km para este tema</span></div>
                        <?php else: ?>
                            <div><span class="badge-verde">✅ Disponible para agendar</span></div>
                            
                            <div class="lista-oradores">
                                <strong style="font-size: 0.9em; color: #7f8c8d; display: block; margin-bottom: 8px;">Oradores Elegibles:</strong>
                                <?php 
                                $oradores = explode('|', $d['oradores_disponibles']);
                                foreach($oradores as $o): 
                                    $partes = explode(' - ID:', $o);
                                    $nombre_completo = $partes[0];
                                    $id_orador = $partes[1] ?? 0;
                                ?>
                                    <div class="orador-item">
                                        <span>🗣️ <?php echo htmlspecialchars($nombre_completo); ?></span>
                                        <a href="calendario_arreglos.php?orador_id=<?php echo $id_orador; ?>&discurso_id=<?php echo $d['numero']; ?>" class="btn-solicitar">Agendar</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <script>
        function filtrarDiscursos() {
            let filtro = document.getElementById('buscador').value.toLowerCase();
            let tarjetas = document.querySelectorAll('.discurso-card');

            tarjetas.forEach(tarjeta => {
                let texto = tarjeta.getAttribute('data-busqueda');
                if(texto.includes(filtro)) {
                    tarjeta.style.display = 'flex';
                } else {
                    tarjeta.style.display = 'none';
                }
            });
        }
    </script>

</body>
</html>