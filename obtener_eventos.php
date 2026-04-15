<?php
// obtener_eventos.php
session_start();
require_once 'conexion/conexion.php';

$baseDatos = new Conexion();
$conn = $baseDatos->obtenerConexion();
$usuario_id = $_SESSION['usuario_id'];

// 1. Obtener mi ID de congregación
$sql_mi_cong = "SELECT id FROM congregaciones WHERE usuario_id = :uid";
$stmt = $conn->prepare($sql_mi_cong);
$stmt->execute([':uid' => $usuario_id]);
$mi_cong_id = $stmt->fetchColumn();

// 2. Traer las solicitudes + Datos del Orador + Nombre de su Congregación
// NOTA: Agregamos el JOIN con congregaciones (c) para traer el nombre
$sql = "SELECT s.id, s.fecha, s.estado, o.nombre, o.apellido, s.numero_discurso, o.congregacion_id, c.nombre AS nombre_congregacion
        FROM solicitudes s
        INNER JOIN oradores o ON s.orador_id = o.id
        LEFT JOIN congregaciones c ON o.congregacion_id = c.id
        WHERE s.congregacion_solicitante_id = :mi_id";

$stmt = $conn->prepare($sql);
$stmt->execute([':mi_id' => $mi_cong_id]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventos = [];
foreach($resultados as $row) {
    // Definimos los colores según el estado
    if ($row['estado'] == 'Pendiente') {
        $colorFondo = '#f39c12'; // Naranja
    } elseif ($row['estado'] == 'Aprobado') {
        $colorFondo = '#27ae60'; // Verde
    } elseif ($row['estado'] == 'Rechazado') {
        $colorFondo = '#e74c3c'; // Rojo
    } else {
        $colorFondo = '#95a5a6'; // Gris por defecto
    }

    $colorTexto = '#ffffff'; // Blanco para que resalte

    // Detectar si el orador es de mi misma congregación
    $es_local = ($row['congregacion_id'] == $mi_cong_id) ? true : false;
    
    // Si es de mi congregación, le ponemos (Local), si no, el nombre de su congre
    $etiqueta_cong = $es_local ? "(Local)" : "(" . $row['nombre_congregacion'] . ")";

    // Construimos el título que saldrá en la tarjetita del calendario
    // Ej: Gabriel Vielma (Sabaneta) - N° 5
    $titulo_mostrar = $row['nombre'] . " " . $row['apellido'] . "\n" . $etiqueta_cong . " | N° " . $row['numero_discurso'];

    $eventos[] = [
        'id' => $row['id'],
        'title' => $titulo_mostrar,
        'start' => $row['fecha'],
        'backgroundColor' => $colorFondo,
        'borderColor' => $colorFondo,
        'textColor' => $colorTexto,
        'extendedProps' => [
            'estado' => $row['estado'],
            'es_local' => $es_local 
        ]
    ];
}

echo json_encode($eventos);
?>