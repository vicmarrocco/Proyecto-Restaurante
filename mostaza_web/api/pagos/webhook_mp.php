<?php
// ============================================================
// api/pagos/webhook_mp.php
// Recibe notificaciones (webhooks) de Mercado Pago.
// Configurar en: Tus integraciones > Webhooks (evento "Pagos") con la URL pública https.
// Opcional: MP_WEBHOOK_SECRET en .env para validar la firma x-signature.
// ============================================================
require_once(__DIR__ . '/../../config_pagos.php');
header('Content-Type: application/json; charset=utf-8');

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$paymentId = $body['data']['id'] ?? ($_GET['data_id'] ?? ($_GET['id'] ?? ''));
$tipo      = $body['type'] ?? ($_GET['type'] ?? ($_GET['topic'] ?? ''));

// Validación de firma (si hay secreto configurado)
$secret = getenv('MP_WEBHOOK_SECRET');
if ($secret) {
    $sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $reqId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    $ts = $v1 = '';
    foreach (explode(',', $sig) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 'ts') $ts = $v;
        if ($k === 'v1') $v1 = $v;
    }
    $manifest = 'id:' . strtolower((string)($_GET['data_id'] ?? $paymentId)) . ";request-id:$reqId;ts:$ts;";
    if (!$v1 || !hash_equals(hash_hmac('sha256', $manifest, $secret), $v1)) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'firma inválida']);
        exit;
    }
}

if ($tipo !== 'payment' || !preg_match('/^\d+$/', (string)$paymentId)) {
    http_response_code(200); // responder 200 para que MP no reintente eventos que no usamos
    echo json_encode(['ok' => true, 'ignorado' => true]);
    exit;
}

// Siempre consultar el estado real a la API (nunca confiar en el cuerpo del webhook)
$res = consultarPagoMercadoPagoApi((string)$paymentId, '', 0);

$logDir = __DIR__ . '/../../logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
@file_put_contents($logDir . '/mp_webhook.log',
    date('c') . " payment=$paymentId status=" . ($res['status'] ?? '?') . " ref=" . ($res['external_reference'] ?? '') . "\n",
    FILE_APPEND | LOCK_EX);

http_response_code(200);
echo json_encode(['ok' => true, 'status' => $res['status'] ?? 'unknown']);
