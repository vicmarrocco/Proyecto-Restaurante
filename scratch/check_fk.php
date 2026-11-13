<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$r = $conn->query('SELECT count(*) as c, max(idproducto) as m FROM producto')->fetch_assoc();
echo "Total products: " . $r['c'] . " | Max id: " . $r['m'] . "\n";

// Check if any orders/details reference IDs >= 44
$tables = ['detalle_pedido_web', 'detalle_factura', 'pedido_detalle', 'comanda_detalle'];
foreach ($tables as $t) {
    $chk = $conn->query("SHOW TABLES LIKE '$t'");
    if ($chk && $chk->num_rows > 0) {
        $res = $conn->query("SELECT COUNT(*) as cnt FROM $t WHERE idproducto >= 44");
        if ($res) {
            $c = $res->fetch_assoc()['cnt'];
            echo "Table $t has $c rows referencing idproducto >= 44\n";
        }
    }
}
