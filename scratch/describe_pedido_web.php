<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("DESCRIBE pedido_web");
while ($r = $res->fetch_assoc()) {
    echo "{$r['Field']} | {$r['Type']} | {$r['Null']} | {$r['Key']}\n";
}
