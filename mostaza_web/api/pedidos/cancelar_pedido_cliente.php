<?php
// ============================================================
// api/pedidos/cancelar_pedido_cliente.php
// Permite al cliente autenticado cancelar su propio pedido web
// y realiza el reembolso automático si se abonó con Mercado Pago.
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
require_once(__DIR__ . '/../../config_pagos.php');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para cancelar un pedido.']);
    exit();
}

$idusuario = intval($_SESSION['usuario_id']);
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$idpedido = intval($input['idpedido'] ?? 0);
$motivoCliente = htmlspecialchars(trim($input['motivo'] ?? 'Cancelado por el cliente desde la web'));

if ($idpedido <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Identificador de pedido no proporcionado.']);
    exit();
}

// 1. Obtener pedido verificando titularidad
$stmt = $conn->prepare("SELECT * FROM pedido_web WHERE idpedido = ? AND idusuario = ? LIMIT 1");
$stmt->bind_param("ii", $idpedido, $idusuario);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Pedido no encontrado o no pertenece a tu cuenta.']);
    exit();
}

$pedido = $res->fetch_assoc();
$estadoActual = trim($pedido['estado']);

// 2. Validar si el estado permite cancelación
$estadosNoCancelables = ['Entregado', 'Cancelado', 'En Camino', 'Despachado'];
if (in_array($estadoActual, $estadosNoCancelables)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => "El pedido #{$pedido['idpedido']} no se puede cancelar porque se encuentra en estado '{$estadoActual}'."
    ]);
    exit();
}

$metodoPago = strtolower(trim($pedido['metodo_pago'] ?? ''));
$reembolsoData = null;
$nuevoEstadoPago = $pedido['estado_pago'];

// 3. Reembolso automático si el pago fue mediante Mercado Pago
if ($metodoPago === 'mercadopago') {
    // Extraer Payment ID numérico desde comprobante_transferencia o notas
    $paymentId = preg_replace('/\D/', '', $pedido['comprobante_transferencia'] ?? '');
    if (empty($paymentId) && !empty($pedido['notas'])) {
        if (preg_match('/(?:Operaci[oó]n|ID|Pago)[\s:#]+(\d{6,})/i', $pedido['notas'], $m)) {
            $paymentId = $m[1];
        }
    }

    $montoReembolso = floatval($pedido['total']);
    $resReembolso = reembolsarPagoMercadoPago($paymentId, $montoReembolso, 'Cancelación solicitada por el cliente');

    if ($resReembolso && !empty($resReembolso['ok'])) {
        $reembolsoData = $resReembolso;
        $nuevoEstadoPago = 'Reembolsado';
    } else {
        // En caso de fallo crítico en pasarela real
        $reembolsoData = [
            'ok' => false,
            'error' => $resReembolso['error'] ?? 'No se pudo comunicar con Mercado Pago',
            'modo_real' => $resReembolso['modo_real'] ?? false
        ];
    }
}

// 4. Iniciar transacción para actualizar pedido y restablecer recursos
$conn->begin_transaction();
try {
    $notaCancelacion = " | [Cancelado por Cliente: " . date('Y-m-d H:i:s') . "]";
    if ($reembolsoData && !empty($reembolsoData['ok'])) {
        $ref = $reembolsoData['refund_id'] ?? 'REF-AUTO';
        $notaCancelacion .= " [Reembolso MP OK: {$ref}]";
    }

    $upd = $conn->prepare("
        UPDATE pedido_web SET 
            estado = 'Cancelado',
            motivo_cancelacion = ?,
            cancelado_por = 'Cliente',
            estado_pago = ?,
            notas = CONCAT(IFNULL(notas, ''), ?)
        WHERE idpedido = ? AND idusuario = ?
    ");
    $upd->bind_param("sssii", $motivoCliente, $nuevoEstadoPago, $notaCancelacion, $idpedido, $idusuario);
    $upd->execute();

    // Liberar mesa si era pedido de salón
    if (!empty($pedido['numero_mesa'])) {
        $numMesa = intval($pedido['numero_mesa']);
        $conn->query("UPDATE mesa SET estado = 'disponible' WHERE numero_mesa = $numMesa");
    }

    // Reintegrar puntos utilizados y anular puntos ganados
    $puntosUsados = intval($pedido['puntos_usados'] ?? 0);
    $puntosGanados = intval($pedido['puntos_ganados'] ?? 0);
    if ($puntosUsados > 0 || $puntosGanados > 0) {
        $updPuntos = $conn->prepare("UPDATE usuario_web SET puntos = GREATEST(0, puntos + ? - ?) WHERE idusuario = ?");
        $updPuntos->bind_param("iii", $puntosUsados, $puntosGanados, $idusuario);
        $updPuntos->execute();
    }

    // Reintegrar stock estimado de insumos consumidos
    $stmtItems = $conn->prepare("SELECT SUM(cantidad) as total_items FROM detalle_pedido_web WHERE idpedido = ?");
    $stmtItems->bind_param("i", $idpedido);
    $stmtItems->execute();
    $totItems = intval($stmtItems->get_result()->fetch_assoc()['total_items'] ?? 0);
    if ($totItems > 0) {
        $conn->query("UPDATE stock_insumo SET stock_actual = stock_actual + $totItems WHERE idinsumo IN (1, 3, 12)");
    }

    // Impactar egreso por reembolso en Tesorería Central
    if ($metodoPago === 'mercadopago' && $reembolsoData && !empty($reembolsoData['ok'])) {
        require_once(__DIR__ . '/../tesoreria/TesoreriaService.php');
        TesoreriaService::egresarReembolsoMP($conn, $idpedido, floatval($pedido['total']), "Cliente #$idusuario", $reembolsoData['refund_id'] ?? null);
    }

    $conn->commit();

    $mensajeFinal = "Tu pedido #" . ($pedido['numero_pedido_usuario'] ?: $pedido['idpedido']) . " ha sido cancelado con éxito.";
    if ($metodoPago === 'mercadopago') {
        if ($reembolsoData && !empty($reembolsoData['ok'])) {
            $mensajeFinal .= " Tu pago de $" . number_format($pedido['total'], 2, ',', '.') . " fue reembolsado a través de Mercado Pago.";
        } else {
            $mensajeFinal .= " El pedido se canceló; la solicitud de reintegro en Mercado Pago quedó en revisión administrativa.";
        }
    }

    echo json_encode([
        'ok' => true,
        'mensaje' => $mensajeFinal,
        'idpedido' => $idpedido,
        'numero_pedido_usuario' => $pedido['numero_pedido_usuario'] ?: $idpedido,
        'metodo_pago' => $metodoPago,
        'reembolso' => $reembolsoData
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Ocurrió un error al cancelar el pedido: ' . $e->getMessage()]);
}
