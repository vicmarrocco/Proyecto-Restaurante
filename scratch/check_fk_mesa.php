<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

echo "=== ESTRUCTURA DE LA TABLA mesa ===\n";
$resM = $conn->query("DESCRIBE mesa");
while ($r = $resM->fetch_assoc()) {
    echo "  {$r['Field']} | {$r['Type']} | Null: {$r['Null']} | Key: {$r['Key']} | Default: {$r['Default']}\n";
}

echo "\n=== ESTRUCTURA DE LA TABLA seccion_mesa ===\n";
$resS = $conn->query("DESCRIBE seccion_mesa");
while ($r = $resS->fetch_assoc()) {
    echo "  {$r['Field']} | {$r['Type']} | Null: {$r['Null']} | Key: {$r['Key']} | Default: {$r['Default']}\n";
}

echo "\n=== FOREIGN KEYS EN EL ESQUEMA (mesa y seccion_mesa) ===\n";
$resFk = $conn->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'rest' 
      AND TABLE_NAME IN ('mesa', 'seccion_mesa')
      AND REFERENCED_TABLE_NAME IS NOT NULL
");
if ($resFk && $resFk->num_rows > 0) {
    while ($rf = $resFk->fetch_assoc()) {
        echo "  {$rf['TABLE_NAME']}.{$rf['COLUMN_NAME']} -> {$rf['REFERENCED_TABLE_NAME']}.{$rf['REFERENCED_COLUMN_NAME']} (Constraint: {$rf['CONSTRAINT_NAME']})\n";
    }
} else {
    echo "  [AVISO] No hay Foreign Keys (FOREIGN KEY constraints) declaradas formalmente a nivel de motor MySQL entre estas tablas.\n";
}

echo "\n=== DATOS EN mesa ===\n";
$rm = $conn->query("SELECT * FROM mesa LIMIT 10");
while ($row = $rm->fetch_assoc()) {
    print_r($row);
}

echo "\n=== DATOS EN seccion_mesa ===\n";
$rs = $conn->query("SELECT * FROM seccion_mesa");
while ($row = $rs->fetch_assoc()) {
    print_r($row);
}
