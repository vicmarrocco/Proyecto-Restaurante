<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("DESCRIBE usuario");
while ($r = $res->fetch_assoc()) {
    echo "{$r['Field']} | {$r['Type']}\n";
}
