<?php
// ============================================================
// api/delivery/repartidores.php
// Lista de repartidores disponibles y perfiles para la app.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$repartidoresPredefinidos = [
    [
        'id' => 1,
        'nombre' => 'Ramiro González',
        'vehiculo' => 'Moto Honda Wave 110cc',
        'patente' => 'A123BCD',
        'telefono' => '11-4567-8901',
        'avatar' => '🛵',
        'estado' => 'Disponible'
    ],
    [
        'id' => 2,
        'nombre' => 'Lucas Benítez',
        'vehiculo' => 'Bicicleta MTB Venzo',
        'patente' => 'Sin patente',
        'telefono' => '11-5678-9012',
        'avatar' => '🚲',
        'estado' => 'Disponible'
    ],
    [
        'id' => 3,
        'nombre' => 'Franco Morales',
        'vehiculo' => 'Moto Yamaha YBR 125cc',
        'patente' => 'A234EFG',
        'telefono' => '11-6789-0123',
        'avatar' => '🛵',
        'estado' => 'Disponible'
    ],
    [
        'id' => 4,
        'nombre' => 'Alberto Gómez',
        'vehiculo' => 'Moto Corven Energy 110cc',
        'patente' => 'A345HIJ',
        'telefono' => '11-7890-1234',
        'avatar' => '🛵',
        'estado' => 'Disponible'
    ]
];

echo json_encode([
    'ok' => true,
    'total' => count($repartidoresPredefinidos),
    'repartidores' => $repartidoresPredefinidos
]);
