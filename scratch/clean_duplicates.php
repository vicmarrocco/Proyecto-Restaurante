<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

$conn->begin_transaction();

try {
    $res1 = $conn->query("UPDATE detalle_factura SET idproducto = idproducto - 43 WHERE idproducto >= 44");
    echo "Updated detalle_factura: " . $conn->affected_rows . " rows\n";

    $res2 = $conn->query("UPDATE detalle_pedido_web SET idproducto = idproducto - 43 WHERE idproducto >= 44");
    echo "Updated detalle_pedido_web: " . $conn->affected_rows . " rows\n";

    $res3 = $conn->query("DELETE FROM producto WHERE idproducto >= 44");
    echo "Deleted duplicate products: " . $conn->affected_rows . " rows\n";

    $conn->query("ALTER TABLE producto AUTO_INCREMENT = 44");

    $conn->commit();
    echo "SUCCESS: Duplicates successfully merged and deleted!\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}
