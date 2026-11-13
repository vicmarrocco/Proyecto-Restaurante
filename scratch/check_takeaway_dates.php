<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("
    SELECT idpedido, fecha, DATE(fecha) as d, estado, tipo_entrega 
    FROM pedido_web 
    WHERE tipo_entrega = 'takeaway' 
    ORDER BY idpedido DESC 
    LIMIT 10
");
echo "Today's server date: " . date('Y-m-d') . "\n";
while ($r = $res->fetch_assoc()) {
    echo "Pedido #{$r['idpedido']} | Fecha: {$r['fecha']} | Date: {$r['d']} | Estado: {$r['estado']}\n";
}
