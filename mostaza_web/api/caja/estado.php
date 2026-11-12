<?php
// ============================================================
// api/caja/estado.php
// Consulta el estado de la caja registradora activa y métricas por terminal.
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$numeroCaja = intval($_GET['numero_caja'] ?? 0);

if ($numeroCaja > 0) {
    $stmt = $conn->prepare("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = ? ORDER BY idarqueo DESC LIMIT 1");
    $stmt->bind_param("i", $numeroCaja);
    $stmt->execute();
    $caja = $stmt->get_result()->fetch_assoc();
} else {
    $res = $conn->query("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
    $caja = $res ? $res->fetch_assoc() : null;
}

if (!$caja) {
    $nombreTerminal = $numeroCaja > 0 ? "Caja $numeroCaja" : "Terminal POS";
    echo json_encode([
        'ok' => true,
        'abierta' => false,
        'numero_caja' => $numeroCaja ?: 1,
        'terminal_nombre' => $numeroCaja > 0 ? ($numeroCaja == 1 ? 'Caja 1 - Principal / Mostrador' : 'Caja 2 - Express / Salón') : 'Terminal Principal',
        'mensaje' => "No hay ningún turno de caja abierto actualmente para $nombreTerminal."
    ]);
    exit();
}

$montoInicial = floatval($caja['monto_inicial']);
$ventasEfectivo = floatval($caja['ventas_efectivo']);
$ventasTarjeta = floatval($caja['ventas_tarjeta']);
$ventasMP = floatval($caja['ventas_mercadopago']);
$ventasTransf = floatval($caja['ventas_transferencia']);
$montoRetiros = floatval($caja['monto_retiros'] ?? 0);
$totalVentas = floatval($caja['total_ventas']);
$totalTransacciones = intval($caja['total_transacciones'] ?? 0);

// Total de efectivo esperado en gaveta: Fondo inicial + Ventas en efectivo - Retiros a tesorería
$totalEfectivoEsperado = max(0, $montoInicial + $ventasEfectivo - $montoRetiros);

// Alerta de seguridad: si el efectivo en gaveta supera los $150.000 se sugiere sangría/retiro
$alertaRetiro = ($totalEfectivoEsperado >= 150000);

echo json_encode([
    'ok' => true,
    'abierta' => true,
    'total_efectivo_esperado' => $totalEfectivoEsperado,
    'caja' => [
        'idarqueo' => intval($caja['idarqueo']),
        'numero_caja' => intval($caja['numero_caja'] ?? 1),
        'terminal_nombre' => $caja['terminal_nombre'] ?: ('Caja ' . ($caja['numero_caja'] ?? 1)),
        'idempleado' => $caja['idempleado'] ? intval($caja['idempleado']) : null,
        'cajero' => $caja['cajero'],
        'turno' => $caja['turno'],
        'fecha_apertura' => date('d/m/Y H:i', strtotime($caja['fecha_apertura'])),
        'fecha_apertura_raw' => $caja['fecha_apertura'],
        'monto_inicial' => $montoInicial,
        'monto_inicial_formateado' => '$' . number_format($montoInicial, 0, ',', '.'),
        'ventas_efectivo' => $ventasEfectivo,
        'ventas_tarjeta' => $ventasTarjeta,
        'ventas_mercadopago' => $ventasMP,
        'ventas_transferencia' => $ventasTransf,
        'monto_retiros' => $montoRetiros,
        'total_ventas' => $totalVentas,
        'total_transacciones' => $totalTransacciones,
        'total_efectivo_esperado' => $totalEfectivoEsperado,
        'total_efectivo_esperado_formateado' => '$' . number_format($totalEfectivoEsperado, 0, ',', '.'),
        'total_ventas_formateado' => '$' . number_format($totalVentas, 0, ',', '.'),
        'alerta_retiro_seguridad' => $alertaRetiro,
        'mensaje_alerta' => $alertaRetiro ? "⚠️ Alerta de Seguridad: La gaveta supera los $150.000 en efectivo ($" . number_format($totalEfectivoEsperado, 0, ',', '.') . "). Realizá una sangría / retiro a tesorería." : null
    ]
]);
