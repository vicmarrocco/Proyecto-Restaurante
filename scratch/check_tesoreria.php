<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

$res = $conn->query("SHOW TABLES");
$allTables = [];
while ($r = $res->fetch_array()) {
    $allTables[] = $r[0];
}

echo "Total tables: " . count($allTables) . "\n";
echo "Tables list:\n" . implode(', ', $allTables) . "\n\n";

// Search for any column with tesoreria, fondo, saldo, banco, etc.
$resCol = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'rest' 
      AND (
        COLUMN_NAME LIKE '%tesor%' OR 
        COLUMN_NAME LIKE '%fondo%' OR 
        COLUMN_NAME LIKE '%banco%' OR 
        COLUMN_NAME LIKE '%boveda%' OR 
        COLUMN_NAME LIKE '%saldo%'
      )
");
echo "Matching columns:\n";
while ($rc = $resCol->fetch_assoc()) {
    echo "  {$rc['TABLE_NAME']} -> {$rc['COLUMN_NAME']}\n";
}
