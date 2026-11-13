<?php
require_once(__DIR__ . '/../mostaza_web/conexion.php');

// Verificar si hay registros huérfanos antes de crear la foreign key
$qOrphans = $conn->query("SELECT m.idmesa, m.fk_id_seccion FROM mesa m LEFT JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE s.idseccion IS NULL");
if ($qOrphans && $qOrphans->num_rows > 0) {
    echo "Hay mesas con secciones inexistentes. Corrigiendo a seccion 1...\n";
    $conn->query("UPDATE mesa SET fk_id_seccion = 1 WHERE fk_id_seccion NOT IN (SELECT idseccion FROM seccion_mesa)");
} else {
    echo "Mesas 100% consistentes con seccion_mesa.\n";
}

// Agregar la Foreign Key formal
$sql = "ALTER TABLE mesa ADD CONSTRAINT fk_mesa_seccion FOREIGN KEY (fk_id_seccion) REFERENCES seccion_mesa(idseccion) ON UPDATE CASCADE ON DELETE RESTRICT";
if ($conn->query($sql)) {
    echo "¡Éxito! Constraint fk_mesa_seccion creada formalmente en MySQL.\n";
} else {
    echo "Resultado: " . $conn->error . "\n";
}
