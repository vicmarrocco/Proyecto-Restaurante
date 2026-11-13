<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("SELECT idempleado, nombre, apellido, email, fk_id_rol, activo FROM empleado ORDER BY idempleado");
while ($r = $res->fetch_assoc()) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
