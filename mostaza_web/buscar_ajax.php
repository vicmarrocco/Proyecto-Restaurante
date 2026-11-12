<?php
// ============================================================
// buscar_ajax.php
// Búsqueda instantánea de productos en tiempo real para el autocompletado del buscador.
// ============================================================
header('Content-Type: application/json');
require_once('conexion.php');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['ok' => true, 'total' => 0, 'productos' => []]);
    exit();
}

$param = "%$q%";
$stmt = $conn->prepare("SELECT idproducto, nombre, descripcion, precio, imagen, categoria 
                        FROM producto 
                        WHERE activo = 1 AND (nombre LIKE ? OR descripcion LIKE ? OR categoria LIKE ?)
                        ORDER BY 
                            CASE 
                                WHEN nombre LIKE ? THEN 1 
                                WHEN nombre LIKE ? THEN 2 
                                ELSE 3 
                            END, idproducto ASC 
                        LIMIT 8");

$startParam = "$q%";
$stmt->bind_param("sssss", $param, $param, $param, $startParam, $param);
$stmt->execute();
$res = $stmt->get_result();

$productos = [];
while ($row = $res->fetch_assoc()) {
    $row['precio_formateado'] = '$' . number_format($row['precio'], 0, ',', '.');
    $productos[] = $row;
}

echo json_encode([
    'ok' => true,
    'total' => count($productos),
    'query' => $q,
    'productos' => $productos
]);
