<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("SELECT idproducto, nombre, descripcion, precio, categoria FROM producto WHERE idproducto IN (1,2,3,4,5,6)");
while ($r = $res->fetch_assoc()) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
