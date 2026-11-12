<?php
// ============================================================
// api/cocina/recetas.php
// Consulta de libro de recetas y fichas técnicas de cocina
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$categoria = $_GET['categoria'] ?? null;
$busqueda = $_GET['q'] ?? null;

$sql = "SELECT * FROM receta WHERE 1=1";
if ($categoria) {
    $sql .= " AND categoria = '" . $conn->real_escape_string($categoria) . "'";
}
if ($busqueda) {
    $q = $conn->real_escape_string($busqueda);
    $sql .= " AND (nombre_plato LIKE '%$q%' OR ingredientes_lista LIKE '%$q%')";
}
$sql .= " ORDER BY categoria, nombre_plato ASC";

$res = $conn->query($sql);
$recetas = [];

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $recetas[] = [
            'idreceta' => intval($r['idreceta']),
            'idproducto' => intval($r['idproducto'] ?? 0),
            'nombre' => $r['nombre_plato'],
            'categoria' => $r['categoria'],
            'tiempo' => ($r['tiempo_preparacion_min'] ?? 4) . ' min',
            'tiempo_min' => intval($r['tiempo_preparacion_min'] ?? 4),
            'dificultad' => $r['dificultad'] ?? 'Estándar',
            'ingredientes' => $r['ingredientes_lista'] ?? '',
            'pasos' => $r['pasos_preparacion'] ?? '',
            'temperatura' => $r['temperatura_coccion'] ?? '177°C'
        ];
    }
}

echo json_encode([
    'ok' => true,
    'recetas' => $recetas,
    'total' => count($recetas)
], JSON_UNESCAPED_UNICODE);
