<?php
// ============================================================
// api/tesoreria/registrar_movimiento.php
// Permite registrar movimientos directos en Tesorería
// (ej: Pago a Proveedores, Gastos Operativos, Retiros Centrales)
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once(__DIR__ . '/../../conexion.php');
require_once(__DIR__ . '/TesoreriaService.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$tipo = trim($input['tipo'] ?? '');
$categoria = trim($input['categoria'] ?? 'Gasto Operativo');
$medioPago = trim($input['medio_pago'] ?? 'Efectivo');
$monto = floatval($input['monto'] ?? 0);
$origenDestino = trim($input['origen_destino'] ?? 'Tesorería Central');
$concepto = trim($input['concepto'] ?? '');
$comprobante = trim($input['comprobante'] ?? '');
$idempleado = !empty($input['idempleado']) ? intval($input['idempleado']) : ($_SESSION['cajero_id'] ?? null);

if (empty($tipo) || $monto <= 0 || empty($concepto)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tipo, monto mayor a $0 y concepto son obligatorios.']);
    exit();
}

$conn->begin_transaction();
try {
    $res = TesoreriaService::registrarMovimiento(
        $conn,
        $tipo,
        $categoria,
        $medioPago,
        $monto,
        $origenDestino,
        $concepto,
        [
            'idempleado' => $idempleado,
            'comprobante' => $comprobante
        ]
    );

    if ($res['ok']) {
        $conn->commit();
        echo json_encode($res);
    } else {
        $conn->rollback();
        http_response_code(400);
        echo json_encode($res);
    }
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
