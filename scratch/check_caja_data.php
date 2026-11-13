<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
echo "=== CONTENIDO TABLA caja ===\n";
$res = $conn->query("SELECT * FROM caja");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n=== ULTIMOS REGISTROS caja_arqueo ===\n";
$res2 = $conn->query("SELECT idarqueo, numero_caja, cajero, turno, estado, monto_inicial, ventas_efectivo, ventas_mercadopago, total_ventas, monto_retiros FROM caja_arqueo ORDER BY idarqueo DESC LIMIT 5");
while ($r2 = $res2->fetch_assoc()) {
    print_r($r2);
}
