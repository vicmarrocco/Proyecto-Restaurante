<?php
$url = "http://localhost/PROYECTO_REST/mostaza_web/api/pos/pedidos.php?fecha=hoy&tipo_entrega=takeaway";
$json = file_get_contents($url);
$data = json_decode($json, true);

echo "Total pedidos returned: " . count($data['pedidos'] ?? []) . "\n";
foreach ($data['pedidos'] ?? [] as $p) {
    echo "ID: #{$p['idpedido']} | Estado: {$p['estado']} | Fecha: {$p['fecha']} | idempleado_cajero: {$p['idempleado_cajero']} | Cliente: {$p['cliente']}\n";
}
