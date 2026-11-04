<?php
// ============================================================
// api/pagos/verificar_pago_mp.php
// Consulta en tiempo real a la API de Mercado Pago si el pago
// asociado al carrito actual o a un ID de operación fue aprobado.
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? array_merge($_GET, $_POST);
$paymentId     = trim($input['payment_id'] ?? ($input['collection_id'] ?? ''));
$externalRef   = trim($input['external_reference'] ?? ($_SESSION['mp_external_ref'] ?? ''));
$montoEsperado = floatval($input['monto_esperado'] ?? ($_SESSION['mp_monto_esperado'] ?? 0));

// Si no hay credenciales reales configuradas aún
if (!mpTieneCredencialesReales()) {
    echo json_encode([
        'ok'         => true,
        'modo_real'  => false,
        'pagado'     => false,
        'status'     => 'demo_mode',
        'mensaje'    => 'Configurá tu MP_ACCESS_TOKEN real para la detección automática por API.'
    ]);
    exit();
}

$res = consultarPagoMercadoPagoApi($paymentId, $externalRef, $montoEsperado);

if (!empty($res['pagado'])) {
    $_SESSION['mp_pago_verificado'] = [
        'payment_id'     => $res['payment_id'],
        'status'         => 'approved',
        'monto'          => $res['monto'] ?? 0,
        'metodo_detalle' => $res['metodo_detalle'] ?? 'Mercado Pago Oficial',
        'fecha'          => date('Y-m-d H:i:s')
    ];

    echo json_encode([
        'ok'             => true,
        'modo_real'      => true,
        'pagado'         => true,
        'status'         => 'approved',
        'payment_id'     => $res['payment_id'],
        'monto'          => $res['monto'] ?? 0,
        'metodo_detalle' => $res['metodo_detalle'] ?? 'Mercado Pago Oficial'
    ]);
    exit();
}

echo json_encode([
    'ok'         => true,
    'modo_real'  => true,
    'pagado'     => false,
    'status'     => $res['status'] ?? 'pending',
    'payment_id' => $res['payment_id'] ?? ''
]);
