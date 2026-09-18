<?php
// ============================================================
// api/delivery/repartidores.php
// Lista de repartidores oficiales precargados en la base de datos de Mostaza
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$repartidores = [];

$res = $conn->query("
    SELECT idrepartidor, nombre, dni, telefono, vehiculo, patente, avatar, sucursal_base, calificacion, viajes_totales, estado_servicio, email 
    FROM repartidor 
    WHERE activo = 1 
    ORDER BY idrepartidor ASC
");

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $repartidores[] = [
            'id' => intval($r['idrepartidor']),
            'idempleado' => intval($r['idrepartidor']),
            'nombre' => $r['nombre'],
            'email' => $r['email'] ?? '',
            'dni' => $r['dni'],
            'telefono' => $r['telefono'],
            'vehiculo' => $r['vehiculo'],
            'patente' => $r['patente'],
            'avatar' => $r['avatar'] ?: '🛵',
            'sucursal_base' => $r['sucursal_base'] ?: 'Mostaza Formosa Centro',
            'calificacion' => floatval($r['calificacion']),
            'viajes_totales' => intval($r['viajes_totales']),
            'estado_servicio' => $r['estado_servicio'] ?: 'Disponible',
            'estado' => $r['estado_servicio'] ?: 'Disponible'
        ];
    }
}

echo json_encode([
    'ok' => true,
    'empresa' => 'Mostaza Argentina S.A.',
    'total' => count($repartidores),
    'repartidores' => $repartidores
]);
