<?php
// ============================================================
// api/caja/estado.php
// Consulta el estado de la caja registradora activa y métricas.
// ============================================================
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../../conexion.php');

$stmt = $conn->query("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
$caja = $stmt ? $stmt->fetch_assoc() : null;

if (!$caja) {
    echo json_encode([
        'ok' => true,
        'abierta' => false,
        'mensaje' => 'No hay ningún turno de caja abierto actualmente.'
    ]);
    exit();
}

$totalEfectivoEsperado = floatval($caja['monto_inicial']) + floatval($caja['ventas_efectivo']);

echo json_encode([
    'ok' => true,
    'abierta' => true,
    'caja' => [
        'idarqueo' => intval($caja['idarqueo']),
        'cajero' => $caja['cajero'],
        'turno' => $caja['turno'],
        'fecha_apertura' => date('d/m/Y H:i', strtotime($caja['fecha_apertura'])),
        'monto_inicial' => floatval($caja['monto_inicial']),
        'ventas_efectivo' => floatval($caja['ventas_efectivo']),
        'ventas_tarjeta' => floatval($caja['ventas_tarjeta']),
        'ventas_mercadopago' => floatval($caja['ventas_mercadopago']),
        'ventas_transferencia' => floatval($caja['ventas_transferencia']),
        'total_ventas' => floatval($caja['total_ventas']),
        'total_efectivo_esperado' => $totalEfectivoEsperado,
        'total_ventas_formateado' => '$' . number_format($caja['total_ventas'], 0, ',', '.')
    ]
]);
