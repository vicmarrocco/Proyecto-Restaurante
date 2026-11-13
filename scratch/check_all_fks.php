<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$res = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = 'rest' AND REFERENCED_TABLE_NAME IS NOT NULL
");
echo "Total declared foreign keys in DB rest: " . $res->num_rows . "\n";
while ($r = $res->fetch_assoc()) {
    echo "  {$r['TABLE_NAME']}.{$r['COLUMN_NAME']} -> {$r['REFERENCED_TABLE_NAME']}.{$r['REFERENCED_COLUMN_NAME']}\n";
}
