<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'rest' AND COLUMN_NAME IN ('idproducto', 'fk_id_producto', 'producto_id')
");
while ($r = $res->fetch_assoc()) {
    $t = $r['TABLE_NAME'];
    $c = $r['COLUMN_NAME'];
    $q = $conn->query("SELECT COUNT(*) as cnt FROM `$t` WHERE `$c` >= 44");
    if ($q) {
        $count = $q->fetch_assoc()['cnt'];
        echo "Table: $t, Column: $c -> $count rows >= 44\n";
    }
}
