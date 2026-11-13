<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
$conn->query("UPDATE producto SET nombre = 'Combo Hambre de la Cuarta | Provolone y Bacon' WHERE idproducto = 4");
$conn->query("UPDATE producto SET nombre = 'Combo La Cuarta | Ahumada' WHERE idproducto = 5");
$conn->query("UPDATE producto SET nombre = 'Combo La Cuarta | Merlo' WHERE idproducto = 6");
echo "Updated combo names successfully!\n";
