<?php
// Script para restaurar los precios originales de la base de datos
require_once __DIR__ . '/mostaza_web/conexion.php';
$jsonFile = 'C:/Users/apple/.gemini/antigravity/brain/5e592de5-83c0-4487-9bc5-ae043b15e899/scratch/precios_originales.json';

if (!file_exists($jsonFile)) {
    die("No se encontró el archivo de respaldo: $jsonFile\n");
}

$datos = json_decode(file_get_contents($jsonFile), true);
$restaurados = 0;
foreach ($datos as $p) {
    $id = intval($p['idproducto']);
    $precio = floatval($p['precio']);
    $conn->query("UPDATE producto SET precio = $precio WHERE idproducto = $id");
    $restaurados++;
}

echo "Se restauraron los precios originales de $restaurados productos exitosamente.\n";
