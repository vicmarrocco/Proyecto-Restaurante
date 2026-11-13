<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

$res = $conn->query("
    SELECT nombre, COUNT(*) as qty, GROUP_CONCAT(idproducto) as ids, GROUP_CONCAT(precio) as precios, GROUP_CONCAT(categoria) as cats, GROUP_CONCAT(activo) as activos
    FROM producto
    GROUP BY LOWER(TRIM(nombre))
    HAVING qty > 1
    ORDER BY qty DESC
");

if (!$res) {
    echo "Error: " . $conn->error . "\n";
    exit;
}

echo "Total distinct duplicate product names: " . $res->num_rows . "\n";
while ($row = $res->fetch_assoc()) {
    echo "Product: '{$row['nombre']}' | Count: {$row['qty']} | IDs: {$row['ids']} | Precios: {$row['precios']} | Categorias: {$row['cats']} | Activos: {$row['activos']}\n";
}
