<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("SHOW TABLES LIKE '%user%' OR TABLE_NAME LIKE '%client%'");
$res = $conn->query("SHOW TABLES");
while ($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}
