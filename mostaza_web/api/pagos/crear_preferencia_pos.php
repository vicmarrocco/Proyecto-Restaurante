<?php
// ============================================================
// api/pagos/crear_preferencia_pos.php
// Crea una Preferencia de Pago Oficial en Mercado Pago (Checkout Pro)
// para App Mesero (Salón), App Cajero (POS Desktop) y Admin POS Web.
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once(__DIR__ . '/../../conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? array_merge($_GET, $_POST);

$rawItems = $input['items'] ?? [];
$totalInput = floatval($input['total'] ?? 0);
$origen = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['origen'] ?? 'POS');
$mesa = intval($input['numero_mesa'] ?? ($input['mesa'] ?? 0));

// Construir lista de items para Mercado Pago
$items = [];
$sumaItems = 0;

if (!empty($rawItems) && is_array($rawItems)) {
    foreach ($rawItems as $it) {
        $precio = floatval($it['precio_unitario'] ?? ($it['precio'] ?? 0));
        $cant = max(1, intval($it['cantidad'] ?? 1));
        $nombre = trim($it['nombre'] ?? ($it['descripcion'] ?? 'Producto Mostaza'));
        if ($precio > 0) {
            $sumaItems += ($precio * $cant);
            $items[] = [
                'idproducto' => $it['idproducto'] ?? 'POS-ITEM',
                'nombre'     => $nombre,
                'detalle'    => $mesa > 0 ? "Mesa #$mesa - Mostaza" : "Mostrador POS - Mostaza",
                'precio'     => $precio,
                'cantidad'   => $cant
            ];
        }
    }
}

$totalFinal = $totalInput > 0 ? $totalInput : max(1, $sumaItems);

if (empty($items) || abs($sumaItems - $totalFinal) > 1) {
    $tituloOrden = $mesa > 0 ? "Comanda Mostaza - Mesa #$mesa" : "Orden Mostaza POS ($origen)";
    $items = [[
        'idproducto' => 'COMANDA-MOSTAZA',
        'nombre'     => $tituloOrden,
        'detalle'    => 'Pago oficial vía Mercado Pago',
        'precio'     => round($totalFinal, 2),
        'cantidad'   => 1
    ]];
}

$externalRef = 'MOSTAZA-' . strtoupper($origen) . '-' . ($mesa > 0 ? "M{$mesa}-" : '') . time() . '-' . mt_rand(100, 999);

$resPref = crearPreferenciaMercadoPago(
    $items,
    $externalRef,
    ['nombre' => 'Cliente', 'apellido' => 'Mostaza', 'email' => 'comprador@mostaza.com.ar'],
    [],
    0,
    0
);

$qrEmvcoUrl = generarQrUrlMercadoPago($totalFinal, 200);
$incluirB64 = !empty($input['incluir_qr_b64']);

$descargarPngBase64 = function($url) {
    $ctx = stream_context_create([
        'http' => ['timeout' => 5, 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    return ($raw && strlen($raw) > 100) ? base64_encode($raw) : '';
};

if (!empty($resPref['ok']) && !empty($resPref['init_point'])) {
    $qrCheckoutUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=8&data=" . urlencode($resPref['init_point']);
    $resp = [
        'ok'                 => true,
        'modo_real'          => true,
        'public_key'         => MP_PUBLIC_KEY,
        'preference_id'      => $resPref['preference_id'],
        'init_point'         => $resPref['init_point'],
        'external_reference' => $externalRef,
        'total'              => $totalFinal,
        'total_formateado'   => '$' . number_format($totalFinal, 0, ',', '.'),
        'qr_checkout_url'    => $qrCheckoutUrl,
        'qr_emvco_url'       => $qrEmvcoUrl,
        'alias'              => BANCO_ALIAS,
        'cvu'                => BANCO_CVU,
        'titular'            => BANCO_TITULAR
    ];
    if ($incluirB64) {
        $resp['qr_checkout_b64'] = $descargarPngBase64($qrCheckoutUrl);
        $resp['qr_emvco_b64']    = $descargarPngBase64($qrEmvcoUrl);
    }
    echo json_encode($resp);
    exit();
}

$respFallback = [
    'ok'                 => true,
    'modo_real'          => false,
    'aviso_api'          => $resPref['error'] ?? 'Modo QR EMVCo activo',
    'init_point'         => MP_LINK_PAGO,
    'external_reference' => $externalRef,
    'total'              => $totalFinal,
    'total_formateado'   => '$' . number_format($totalFinal, 0, ',', '.'),
    'qr_checkout_url'    => $qrEmvcoUrl,
    'qr_emvco_url'       => $qrEmvcoUrl,
    'alias'              => BANCO_ALIAS,
    'cvu'                => BANCO_CVU,
    'titular'            => BANCO_TITULAR
];
if ($incluirB64) {
    $b64 = $descargarPngBase64($qrEmvcoUrl);
    $respFallback['qr_checkout_b64'] = $b64;
    $respFallback['qr_emvco_b64']    = $b64;
}
echo json_encode($respFallback);
