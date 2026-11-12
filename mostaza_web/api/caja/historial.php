<?php
// ============================================================
// api/caja/historial.php
// Devuelve el historial de turnos de caja y arqueos con montos
// iniciales, finales físicos y diferencias.
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');

$limite = intval($_GET['limite'] ?? 30);
$numeroCaja = intval($_GET['numero_caja'] ?? 0);

$sql = "SELECT * FROM caja_arqueo";
if ($numeroCaja > 0) {
    $sql .= " WHERE numero_caja = $numeroCaja";
}
$sql .= " ORDER BY idarqueo DESC LIMIT $limite";
$stmt = $conn->query($sql);

$historial = [];
if ($stmt) {
    while ($c = $stmt->fetch_assoc()) {
        $montoIni = floatval($c['monto_inicial']);
        $vEfectivo = floatval($c['ventas_efectivo']);
        $montoRetiros = floatval($c['monto_retiros'] ?? 0);
        $totEsperado = max(0, $montoIni + $vEfectivo - $montoRetiros);
        $montoFisico = ($c['monto_fisico_cierre'] !== null) ? floatval($c['monto_fisico_cierre']) : null;
        $diff = ($c['diferencia'] !== null) ? floatval($c['diferencia']) : null;

        $diffTexto = "En curso";
        if ($c['estado'] === 'Cerrada') {
            if ($diff == 0) $diffTexto = "Exacto ($0)";
            elseif ($diff > 0) $diffTexto = "+$" . number_format($diff, 0, ',', '.') . " (Sobrante)";
            else $diffTexto = "-$" . number_format(abs($diff), 0, ',', '.') . " (Faltante)";
        }

        $historial[] = [
            'idarqueo' => intval($c['idarqueo']),
            'numero_caja' => intval($c['numero_caja'] ?? 1),
            'terminal_nombre' => $c['terminal_nombre'] ?: ('Caja ' . ($c['numero_caja'] ?? 1)),
            'cajero' => $c['cajero'],
            'turno' => $c['turno'],
            'estado' => $c['estado'],
            'fecha_apertura' => date('d/m/Y H:i', strtotime($c['fecha_apertura'])),
            'fecha_cierre' => $c['fecha_cierre'] ? date('d/m/Y H:i', strtotime($c['fecha_cierre'])) : null,
            'monto_inicial' => $montoIni,
            'monto_inicial_formateado' => '$' . number_format($montoIni, 0, ',', '.'),
            'ventas_efectivo' => $vEfectivo,
            'ventas_efectivo_formateado' => '$' . number_format($vEfectivo, 0, ',', '.'),
            'ventas_tarjeta' => floatval($c['ventas_tarjeta']),
            'ventas_mercadopago' => floatval($c['ventas_mercadopago']),
            'ventas_transferencia' => floatval($c['ventas_transferencia']),
            'total_ventas' => floatval($c['total_ventas']),
            'total_ventas_formateado' => '$' . number_format($c['total_ventas'], 0, ',', '.'),
            'total_esperado' => $totEsperado,
            'total_esperado_formateado' => '$' . number_format($totEsperado, 0, ',', '.'),
            'monto_fisico_cierre' => $montoFisico,
            'monto_fisico_cierre_formateado' => ($montoFisico !== null) ? ('$' . number_format($montoFisico, 0, ',', '.')) : '--',
            'diferencia' => $diff,
            'diferencia_formateada' => ($diff !== null) ? ('$' . number_format($diff, 0, ',', '.')) : '--',
            'diferencia_texto' => $diffTexto,
            'total_transacciones' => intval($c['total_transacciones'] ?? 0)
        ];
    }
}

echo json_encode([
    'ok' => true,
    'total_registros' => count($historial),
    'historial' => $historial
]);
