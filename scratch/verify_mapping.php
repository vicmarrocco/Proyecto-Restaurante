<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("
    SELECT p1.idproducto as id1, p1.nombre as n1, p1.categoria as c1, p1.precio as pr1,
           p2.idproducto as id2, p2.nombre as n2, p2.categoria as c2, p2.precio as pr2
    FROM producto p1
    JOIN producto p2 ON p2.idproducto = p1.idproducto + 43
    WHERE p1.idproducto BETWEEN 1 AND 43
");

$allMatch = true;
while ($r = $res->fetch_assoc()) {
    if (trim($r['n1']) !== trim($r['n2']) || $r['c1'] !== $r['c2'] || $r['pr1'] !== $r['pr2']) {
        echo "Mismatch: {$r['id1']} ({$r['n1']}) vs {$r['id2']} ({$r['n2']})\n";
        $allMatch = false;
    }
}
if ($allMatch) {
    echo "EXACT 1-to-1 MATCH for all 43 products! id2 = id1 + 43.\n";
}
