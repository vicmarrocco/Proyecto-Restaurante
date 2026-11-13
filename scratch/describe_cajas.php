<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

$tables = ['caja', 'caja_arqueo', 'caja_retiro'];
foreach ($tables as $t) {
    echo "=== TABLA: $t ===\n";
    $res = $conn->query("DESCRIBE `$t`");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            echo "  {$r['Field']} | {$r['Type']} | {$r['Null']} | Key: {$r['Key']} | Default: {$r['Default']}\n";
        }
    } else {
        echo "  Error: " . $conn->error . "\n";
    }
    echo "\n";
}
