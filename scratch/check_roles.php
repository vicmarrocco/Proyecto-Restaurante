<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');
echo "=== TABLA ROL ===\n";
$resR = $conn->query("SELECT * FROM rol");
while ($r = $resR->fetch_assoc()) {
    echo "ID: {$r['idrol']} | Rol: {$r['rol']}\n";
}

echo "\n=== EMPLEADOS Y SUS ROLES ===\n";
$resE = $conn->query("
    SELECT e.idempleado, CONCAT(e.nombre, ' ', e.apellido) as nombre, e.email, e.fk_id_rol, r.rol
    FROM empleado e
    LEFT JOIN rol r ON e.fk_id_rol = r.idrol
    ORDER BY e.fk_id_rol, e.idempleado
");
while ($re = $resE->fetch_assoc()) {
    echo "ID: {$re['idempleado']} | {$re['nombre']} | {$re['email']} | Rol ID: {$re['fk_id_rol']} ({$re['rol']})\n";
}
