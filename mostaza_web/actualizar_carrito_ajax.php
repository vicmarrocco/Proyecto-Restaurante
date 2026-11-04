<?php
// ============================================================
// actualizar_carrito_ajax.php
// Maneja actualización dinámica del carrito, cupones, puntos,
// propinas y modalidades de entrega sin recargar la página.
// ============================================================
session_start();
header('Content-Type: application/json');
require('conexion.php');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$accion = $input['accion'] ?? '';

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$idusuario = $_SESSION['usuario_id'] ?? null;
$puntosUsuario = 0;
if ($idusuario) {
    $stmtU = $conn->prepare("SELECT puntos FROM usuario_web WHERE idusuario = ?");
    $stmtU->bind_param("i", $idusuario);
    $stmtU->execute();
    $resU = $stmtU->get_result()->fetch_assoc();
    if ($resU) {
        $puntosUsuario = intval($resU['puntos']);
    }
}

// ------------------------------------------------------------
// Acciones del Carrito
// ------------------------------------------------------------
if ($accion === 'modificar_cantidad') {
    $key = $input['key'] ?? '';
    $delta = intval($input['delta'] ?? 0);
    $setQty = isset($input['cantidad']) ? intval($input['cantidad']) : null;

    if (isset($_SESSION['carrito'][$key])) {
        if ($setQty !== null) {
            $_SESSION['carrito'][$key]['cantidad'] = max(1, min(50, $setQty));
        } else {
            $_SESSION['carrito'][$key]['cantidad'] += $delta;
            if ($_SESSION['carrito'][$key]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$key]);
            }
        }
    }
} elseif ($accion === 'eliminar_item') {
    $key = $input['key'] ?? '';
    if (isset($_SESSION['carrito'][$key])) {
        unset($_SESSION['carrito'][$key]);
    }
} elseif ($accion === 'guardar_nota') {
    $key = $input['key'] ?? '';
    $nota = htmlspecialchars(trim($input['nota'] ?? ''));
    if (isset($_SESSION['carrito'][$key])) {
        $_SESSION['carrito'][$key]['modificadores'] = $nota;
    }
} elseif ($accion === 'aplicar_cupon') {
    $codigo = strtoupper(trim($input['codigo'] ?? ''));
    if (empty($codigo)) {
        unset($_SESSION['cupon_aplicado']);
    } else {
        $stmtC = $conn->prepare("SELECT * FROM cupon WHERE codigo = ? AND activo = 1");
        $stmtC->bind_param("s", $codigo);
        $stmtC->execute();
        $cup = $stmtC->get_result()->fetch_assoc();

        if ($cup) {
            // Verificar subtotal
            $subtotalActual = 0;
            foreach ($_SESSION['carrito'] as $it) {
                $subtotalActual += $it['precio'] * $it['cantidad'];
            }

            if ($subtotalActual < floatval($cup['minimo_compra'])) {
                echo json_encode([
                    'ok' => false,
                    'error' => "El cupón requiere una compra mínima de $" . number_format($cup['minimo_compra'], 0, ',', '.')
                ]);
                exit();
            }

            $_SESSION['cupon_aplicado'] = [
                'codigo' => $cup['codigo'],
                'tipo' => $cup['tipo'],
                'valor' => floatval($cup['valor']),
                'minimo_compra' => floatval($cup['minimo_compra']),
                'tope_descuento' => $cup['tope_descuento'] !== null ? floatval($cup['tope_descuento']) : null,
                'descripcion' => $cup['descripcion']
            ];
        } else {
            echo json_encode(['ok' => false, 'error' => 'El código de cupón no es válido o expiró.']);
            exit();
        }
    }
} elseif ($accion === 'remover_cupon') {
    unset($_SESSION['cupon_aplicado']);
} elseif ($accion === 'toggle_puntos') {
    $usar = !empty($input['usar']);
    $_SESSION['usar_puntos'] = $usar;
} elseif ($accion === 'set_propina') {
    $tipo = $input['tipo'] ?? 'porcentaje';
    $valor = floatval($input['valor'] ?? 0);
    $_SESSION['propina'] = ['tipo' => $tipo, 'valor' => max(0, $valor)];
} elseif ($accion === 'set_tipo_entrega') {
    $tipo = in_array($input['tipo_entrega'] ?? '', ['delivery', 'takeaway', 'salon']) ? $input['tipo_entrega'] : 'delivery';
    $_SESSION['tipo_entrega'] = $tipo;
}

// ------------------------------------------------------------
// Recalcular Totales
// ------------------------------------------------------------
$subtotal = 0;
$cantItems = 0;
$itemsResumen = [];

foreach ($_SESSION['carrito'] as $k => $it) {
    $itemTot = $it['precio'] * $it['cantidad'];
    $subtotal += $itemTot;
    $cantItems += $it['cantidad'];
    $itemsResumen[] = [
        'key' => $k,
        'nombre' => $it['nombre'],
        'precio' => $it['precio'],
        'cantidad' => $it['cantidad'],
        'imagen' => $it['imagen'] ?? 'descarga.png',
        'modificadores' => $it['modificadores'] ?? '',
        'subtotal' => $itemTot,
        'subtotal_formateado' => '$' . number_format($itemTot, 0, ',', '.')
    ];
}

// Descuento Cupón
$descuentoCupon = 0;
$cuponInfo = null;
if (isset($_SESSION['cupon_aplicado']) && $subtotal > 0) {
    $cup = $_SESSION['cupon_aplicado'];
    if ($subtotal >= $cup['minimo_compra']) {
        if ($cup['tipo'] === 'porcentaje') {
            $descuentoCupon = ($subtotal * $cup['valor']) / 100;
        } else {
            $descuentoCupon = $cup['valor'];
        }
        if ($cup['tope_descuento'] && $descuentoCupon > $cup['tope_descuento']) {
            $descuentoCupon = $cup['tope_descuento'];
        }
        $descuentoCupon = min($subtotal, $descuentoCupon);
        $cuponInfo = [
            'codigo' => $cup['codigo'],
            'descripcion' => $cup['descripcion'],
            'descuento' => $descuentoCupon,
            'descuento_formateado' => '-$' . number_format($descuentoCupon, 0, ',', '.')
        ];
    } else {
        unset($_SESSION['cupon_aplicado']);
    }
}

// Puntos Mostaza ($1 de descuento por cada punto, hasta el 50% del subtotal restante)
$puntosUsados = 0;
$descuentoPuntos = 0;
$usarPuntos = !empty($_SESSION['usar_puntos']);
if ($usarPuntos && $puntosUsuario > 0 && $subtotal > 0) {
    $maxPuntos = intval(($subtotal - $descuentoCupon) * 0.5); // hasta 50%
    $puntosUsados = min($puntosUsuario, $maxPuntos);
    $descuentoPuntos = $puntosUsados; // 1 punto = $1
}

// Costo de Envío (Delivery = $1.200, Gratis superando $25.000)
$tipoEntrega = $_SESSION['tipo_entrega'] ?? 'delivery';
$costoEnvio = 0;
if ($tipoEntrega === 'delivery' && $subtotal > 0) {
    $costoEnvio = ($subtotal >= 25000) ? 0.00 : 1200.00;
}

// Propina
$propina = 0;
$propData = $_SESSION['propina'] ?? ['tipo' => 'porcentaje', 'valor' => 0];
if ($propData['tipo'] === 'porcentaje' && $propData['valor'] > 0) {
    $propina = ($subtotal * $propData['valor']) / 100;
} elseif ($propData['tipo'] === 'fijo' && $propData['valor'] > 0) {
    $propina = $propData['valor'];
}

$totalFinal = max(0, ($subtotal - $descuentoCupon - $descuentoPuntos) + $costoEnvio + $propina);
$puntosAGanar = intval($totalFinal / 100); // 1 punto cada $100

echo json_encode([
    'ok' => true,
    'cant_items' => $cantItems,
    'items' => $itemsResumen,
    'subtotal' => $subtotal,
    'subtotal_formateado' => '$' . number_format($subtotal, 0, ',', '.'),
    'descuento_cupon' => $descuentoCupon,
    'descuento_cupon_formateado' => '-$' . number_format($descuentoCupon, 0, ',', '.'),
    'cupon_info' => $cuponInfo,
    'puntos_usuario' => $puntosUsuario,
    'usar_puntos' => $usarPuntos,
    'puntos_usados' => $puntosUsados,
    'descuento_puntos' => $descuentoPuntos,
    'descuento_puntos_formateado' => '-$' . number_format($descuentoPuntos, 0, ',', '.'),
    'tipo_entrega' => $tipoEntrega,
    'costo_envio' => $costoEnvio,
    'costo_envio_formateado' => ($costoEnvio > 0) ? '$' . number_format($costoEnvio, 0, ',', '.') : '¡GRATIS!',
    'propina' => $propina,
    'propina_formateada' => '$' . number_format($propina, 0, ',', '.'),
    'total_final' => $totalFinal,
    'total_final_formateado' => '$' . number_format($totalFinal, 0, ',', '.'),
    'puntos_a_ganar' => $puntosAGanar
]);
