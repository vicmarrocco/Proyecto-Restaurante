<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("SELECT idproducto, nombre, imagen FROM producto LIMIT 10");
while ($r = $res->fetch_assoc()) {
    echo "ID: {$r['idproducto']} | Nombre: {$r['nombre']} | Imagen: {$r['imagen']}\n";
}
