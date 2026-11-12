<?php
// ============================================================
// api/pagos/crear_preferencia_mp.php
// Crea una Preferencia de Pago Oficial en Mercado Pago (Checkout Pro)
// con el carrito actual del cliente, descuentos, envío y propina.
// ============================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../../conexion.php');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Debés iniciar sesión para generar la orden de pago.']);
    exit();
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    echo json_encode(['ok' => false, 'error' => 'El carrito está vacío.']);
    exit();
}

$idusuario = intval($_SESSION['usuario_id']);
$stmtU = $conn->prepare("SELECT nombre, apellido, email, puntos FROM usuario_web WHERE idusuario = ?");
$stmtU->bind_param("i", $idusuario);
$stmtU->execute();
$usuario = $stmtU->get_result()->fetch_assoc();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$tipoEntrega = $input['tipo_entrega'] ?? ($_GET['tipo_entrega'] ?? 'delivery');

// 1. Calcular subtotal
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += floatval($item['precio']) * intval($item['cantidad']);
}

// 2. Descuento por cupón
$descuentoCupon = 0;
if (isset($_SESSION['cupon_aplicado'])) {
    $cup = $_SESSION['cupon_aplicado'];
    if ($subtotal >= ($cup['minimo_compra'] ?? 0)) {
        if ($cup['tipo'] === 'porcentaje') {
            $descuentoCupon = ($subtotal * $cup['valor']) / 100;
        } else {
            $descuentoCupon = $cup['valor'];
        }
        if (!empty($cup['tope_descuento']) && $descuentoCupon > $cup['tope_descuento']) {
            $descuentoCupon = $cup['tope_descuento'];
        }
        $descuentoCupon = min($subtotal, $descuentoCupon);
    }
}

// 3. Descuento por Puntos Mostaza
$descuentoPuntos = 0;
$puntosDisponibles = intval($usuario['puntos'] ?? 0);
if (!empty($_SESSION['usar_puntos']) && $puntosDisponibles > 0) {
    $maxPuntos = intval(($subtotal - $descuentoCupon) * 0.5);
    $descuentoPuntos = min($puntosDisponibles, $maxPuntos);
}

$descuentoTotal = $descuentoCupon + $descuentoPuntos;

// 4. Costo de envío
$costoEnvio = 0;
if ($tipoEntrega === 'delivery' && $subtotal > 0 && $subtotal < 25000) {
    $costoEnvio = 1200;
}

// 5. Propina
$propina = 0;
$propData = $_SESSION['propina'] ?? ['tipo' => 'porcentaje', 'valor' => 0];
if ($propData['tipo'] === 'porcentaje' && $propData['valor'] > 0) {
    $propina = ($subtotal * $propData['valor']) / 100;
} elseif ($propData['tipo'] === 'fijo') {
    $propina = floatval($propData['valor']);
}

$totalFinal = max(1, ($subtotal - $descuentoTotal) + $costoEnvio + $propina);
$_SESSION['mp_monto_esperado'] = $totalFinal;

// Generar referencia externa única para rastrear el pago en vivo
if (empty($_SESSION['mp_external_ref']) || !empty($input['regenerar'])) {
    $_SESSION['mp_external_ref'] = 'MOSTAZA-WEB-' . $idusuario . '-' . time();
}
$externalRef = $_SESSION['mp_external_ref'];

// Construir URLs de retorno hacia carrito.php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/\\');
$returnBase = "{$scheme}://{$host}{$basePath}/carrito.php";

$backUrls = [
    'success' => $returnBase . '?mp_return=success&ext_ref=' . urlencode($externalRef),
    'failure' => $returnBase . '?mp_return=failure&ext_ref=' . urlencode($externalRef),
    'pending' => $returnBase . '?mp_return=pending&ext_ref=' . urlencode($externalRef)
];

// Llamar a la API Oficial de Mercado Pago si hay credenciales configuradas
$resPref = crearPreferenciaMercadoPago(
    $carrito,
    $externalRef,
    $usuario ?: [],
    $backUrls,
    ($costoEnvio + $propina),
    $descuentoTotal
);

$qrEmvcoUrl = generarQrUrlMercadoPago($totalFinal, 220);

if (!empty($resPref['ok']) && !empty($resPref['init_point'])) {
    $_SESSION['mp_preference_id'] = $resPref['preference_id'];
    $_SESSION['mp_init_point']    = $resPref['init_point'];
    $qrCheckoutUrl = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=" . urlencode($resPref['init_point']);

    echo json_encode([
        'ok'                 => true,
        'modo_real'          => true,
        'sandbox'            => MP_SANDBOX_MODE,
        'public_key'         => MP_PUBLIC_KEY,
        'preference_id'      => $resPref['preference_id'],
        'init_point'         => $resPref['init_point'],
        'external_reference' => $externalRef,
        'total'              => $totalFinal,
        'total_formateado'   => '$' . number_format($totalFinal, 0, ',', '.'),
        'qr_checkout_url'    => $qrCheckoutUrl,
        'qr_emvco_url'       => $qrEmvcoUrl
    ]);
    exit();
}

// Si aún no tiene credenciales reales o hubo un error en la clave, devolver el QR EMVCo + diagnóstico claro
echo json_encode([
    'ok'                 => true,
    'modo_real'          => false,
    'credenciales_reales'=> mpTieneCredencialesReales(),
    'aviso_api'          => $resPref['error'] ?? 'Usando QR EMVCo / Link de Pago directo.',
    'init_point'         => MP_LINK_PAGO,
    'external_reference' => $externalRef,
    'total'              => $totalFinal,
    'total_formateado'   => '$' . number_format($totalFinal, 0, ',', '.'),
    'qr_checkout_url'    => $qrEmvcoUrl,
    'qr_emvco_url'       => $qrEmvcoUrl
]);
