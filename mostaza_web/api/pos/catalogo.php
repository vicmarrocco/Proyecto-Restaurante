<?php
// ============================================================
// api/pos/catalogo.php
// Devuelve el catálogo completo de productos organizado por categorías.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../../conexion.php');

$categorias = [
    'exclusivos' => ['nombre' => 'Exclusivos', 'icono' => 'fa-burger'],
    'megacombos' => ['nombre' => 'Mega Combos', 'icono' => 'fa-box-archive'],
    'pollo'      => ['nombre' => 'Pollo', 'icono' => 'fa-drumstick-bite'],
    'ensaladas'  => ['nombre' => 'Ensaladas', 'icono' => 'fa-leaf'],
    'paravos'    => ['nombre' => 'Para Vos', 'icono' => 'fa-heart'],
    'cajitas'    => ['nombre' => 'Cajitas', 'icono' => 'fa-child'],
    'postres'    => ['nombre' => 'Postres & Café', 'icono' => 'fa-ice-cream'],
];

$stmt = $conn->query("SELECT * FROM producto WHERE activo = 1 ORDER BY categoria, idproducto");
$productos_por_cat = [];
$productos_flat = [];

while ($p = $stmt->fetch_assoc()) {
    $cat = $p['categoria'];
    if (!isset($productos_por_cat[$cat])) {
        $productos_por_cat[$cat] = [];
    }
    $item = [
        'idproducto' => intval($p['idproducto']),
        'nombre' => $p['nombre'],
        'descripcion' => $p['descripcion'],
        'precio' => floatval($p['precio']),
        'precio_formateado' => '$' . number_format($p['precio'], 0, ',', '.'),
        'imagen' => $p['imagen'],
        'categoria' => $p['categoria']
    ];
    $productos_por_cat[$cat][] = $item;
    $productos_flat[] = $item;
}

echo json_encode([
    'ok' => true,
    'categorias' => $categorias,
    'productos_por_categoria' => $productos_por_cat,
    'productos' => $productos_flat,
    'total_productos' => count($productos_flat)
]);
