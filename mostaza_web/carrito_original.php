<?php
// ============================================================
// carrito.php
// Carrito de compras integral y pasarela de pago real y funcional.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = '';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = 'carrito.php';
    header("Location: login.php");
    exit();
}

$idusuario = $_SESSION['usuario_id'];
$carrito = $_SESSION['carrito'] ?? [];

// Obtener datos y puntos del usuario actual
$stmtU = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
$stmtU->bind_param("i", $idusuario);
$stmtU->execute();
$usuario = $stmtU->get_result()->fetch_assoc() ?? [];

$puntosDisponibles = intval($usuario['puntos'] ?? 0);
$direccionGuardada = $usuario['direccion_favorita'] ?? $usuario['direccion'] ?? '';
$telefonoGuardado = $usuario['telefono_favorito'] ?? $usuario['telefono'] ?? '';

// Calcular subtotal inicial
$subtotal = 0;
$cantItems = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
    $cantItems += $item['cantidad'];
}

$cuponActual = $_SESSION['cupon_aplicado'] ?? null;
$descuentoCupon = 0;
if ($cuponActual && $subtotal >= ($cuponActual['minimo_compra'] ?? 0)) {
    if ($cuponActual['tipo'] === 'porcentaje') {
        $descuentoCupon = ($subtotal * $cuponActual['valor']) / 100;
    } else {
        $descuentoCupon = $cuponActual['valor'];
    }
    if (!empty($cuponActual['tope_descuento']) && $descuentoCupon > $cuponActual['tope_descuento']) {
        $descuentoCupon = $cuponActual['tope_descuento'];
    }
    $descuentoCupon = min($subtotal, $descuentoCupon);
}

$usarPuntos = !empty($_SESSION['usar_puntos']);
$descuentoPuntos = 0;
if ($usarPuntos && $puntosDisponibles > 0) {
    $descuentoPuntos = min($puntosDisponibles, intval(($subtotal - $descuentoCupon) * 0.5));
}

$tipoEntrega = $_SESSION['tipo_entrega'] ?? 'delivery';
$costoEnvio = ($tipoEntrega === 'delivery' && $subtotal > 0 && $subtotal < 25000) ? 1200.00 : 0.00;

$propinaVal = 0;
$propData = $_SESSION['propina'] ?? ['tipo' => 'porcentaje', 'valor' => 0];
if ($propData['tipo'] === 'porcentaje' && $propData['valor'] > 0) {
    $propinaVal = ($subtotal * $propData['valor']) / 100;
} elseif ($propData['tipo'] === 'fijo') {
    $propinaVal = $propData['valor'];
}

$totalFinal = max(0, ($subtotal - $descuentoCupon - $descuentoPuntos) + $costoEnvio + $propinaVal);
$puntosGanados = intval($totalFinal / 100);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza · Carrito & Checkout</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="checkout.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="checkout-container">

    <div class="checkout-header-title">
        <h1>Tu Carrito & Finalizar Pedido</h1>
        <p>Revisá tus productos, elegí la modalidad de entrega y completá tu pago de forma 100% segura.</p>
    </div>

    <?php if (empty($carrito)): ?>
        <div class="card-section" style="text-align:center;padding:70px 20px;">
            <div style="font-size:4rem;color:#D1D5DB;margin-bottom:16px;">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <h2 style="color:var(--dark);margin-bottom:10px;">Tu carrito está vacío</h2>
            <p style="color:#6B7280;margin-bottom:28px;">Todavía no agregaste ninguna de nuestras deliciosas hamburguesas o combos.</p>
            <a href="productos.php" class="btn-submit-order" style="display:inline-flex;width:auto;text-decoration:none;padding:14px 34px;">
                <i class="fa-solid fa-burger"></i> Explorar Menú
            </a>
        </div>
    <?php else: ?>

    <form method="post" action="finalizar_pedido.php" id="checkout-form">
        <div class="checkout-grid">

            <!-- COLUMNA IZQUIERDA: ITEMS + ENTREGA + PAGO -->
            <div class="checkout-main-col">

                <!-- 1. Items del Carrito -->
                <div class="card-section">
                    <div class="card-section-title">
                        <i class="fa-solid fa-burger"></i>
                        <span>1. Resumen de Productos</span>
                    </div>

                    <div class="cart-items-list" id="cart-items-container">
                        <?php foreach ($carrito as $key => $item): ?>
                        <div class="cart-item-card" data-key="<?= htmlspecialchars($key) ?>">
                            <img src="img/<?= htmlspecialchars($item['imagen'] ?? 'descarga.png') ?>" class="cart-item-thumb" alt="<?= htmlspecialchars($item['nombre']) ?>">
                            
                            <div class="cart-item-details">
                                <h4><?= htmlspecialchars($item['nombre']) ?></h4>
                                <div class="cart-item-unit-price">
                                    Precio unitario: $<?= number_format($item['precio'], 0, ',', '.') ?>
                                </div>
                                <input type="text" class="cart-item-note-input" 
                                       placeholder="Aclaración (ej: Sin cebolla, extra aderezo...)" 
                                       value="<?= htmlspecialchars($item['modificadores'] ?? '') ?>"
                                       onchange="guardarNotaItem('<?= htmlspecialchars($key) ?>', this.value)">
                            </div>

                            <div class="qty-stepper">
                                <button type="button" class="qty-btn" onclick="cambiarCantidad('<?= htmlspecialchars($key) ?>', -1)">-</button>
                                <span class="qty-input" id="qty-<?= htmlspecialchars($key) ?>"><?= $item['cantidad'] ?></span>
                                <button type="button" class="qty-btn" onclick="cambiarCantidad('<?= htmlspecialchars($key) ?>', 1)">+</button>
                            </div>

                            <div class="cart-item-subtotal" id="subtot-<?= htmlspecialchars($key) ?>">
                                $<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?>
                            </div>

                            <button type="button" class="btn-remove-item" title="Eliminar producto" onclick="eliminarItem('<?= htmlspecialchars($key) ?>')">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. Modalidad de Entrega -->
                <div class="card-section">
                    <div class="card-section-title">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>2. Modalidad de Entrega</span>
                    </div>

                    <div class="delivery-options-grid">
                        <div class="delivery-opt-card <?= $tipoEntrega === 'delivery' ? 'selected' : '' ?>" onclick="seleccionarEntrega('delivery')" id="opt-delivery">
                            <i class="fa-solid fa-motorcycle"></i>
                            <strong>Delivery</strong>
                            <span>Envío a domicilio</span>
                        </div>
                        <div class="delivery-opt-card <?= $tipoEntrega === 'takeaway' ? 'selected' : '' ?>" onclick="seleccionarEntrega('takeaway')" id="opt-takeaway">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <strong>Take Away</strong>
                            <span>Retiro en local</span>
                        </div>
                        <div class="delivery-opt-card <?= $tipoEntrega === 'salon' ? 'selected' : '' ?>" onclick="seleccionarEntrega('salon')" id="opt-salon">
                            <i class="fa-solid fa-utensils"></i>
                            <strong>En el Salón</strong>
                            <span>Servicio en mesa</span>
                        </div>
                    </div>
                    <input type="hidden" name="tipo_entrega" id="tipo_entrega_input" value="<?= htmlspecialchars($tipoEntrega) ?>">

                    <!-- Campos para Delivery -->
                    <div id="fields-delivery" class="delivery-form-fields" style="<?= $tipoEntrega !== 'delivery' ? 'display:none;' : '' ?>">
                        <div class="form-group-custom" style="grid-column: span 2;">
                            <label><i class="fa-solid fa-house"></i> Dirección de Entrega (Calle y Altura) *</label>
                            <input type="text" name="direccion_envio" id="direccion_envio" 
                                   placeholder="Ej: Av. Santa Fe 3420, Piso 4 Depto B" 
                                   value="<?= htmlspecialchars($direccionGuardada) ?>">
                        </div>
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-phone"></i> Teléfono de Contacto *</label>
                            <input type="tel" name="telefono_contacto" id="telefono_contacto" 
                                   placeholder="Ej: 11 4567-8901" 
                                   value="<?= htmlspecialchars($telefonoGuardado) ?>">
                        </div>
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-bell"></i> Indicaciones para el timbre / repartidor</label>
                            <input type="text" name="notas_delivery" placeholder="Ej: Tocar timbre 4B, portón negro">
                        </div>
                    </div>

                    <!-- Campos para Take Away -->
                    <div id="fields-takeaway" class="delivery-form-fields" style="<?= $tipoEntrega !== 'takeaway' ? 'display:none;' : '' ?>">
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-store"></i> Sucursal de Retiro *</label>
                            <select name="sucursal_retiro">
                                <option value="Mostaza Palermo (Av. Santa Fe 3200)">Mostaza Palermo (Av. Santa Fe 3200)</option>
                                <option value="Mostaza Recoleta (Av. Callao 1400)">Mostaza Recoleta (Av. Callao 1400)</option>
                                <option value="Mostaza Nueva Córdoba (Bv. Illia 450)">Mostaza Nueva Córdoba (Bv. Illia 450)</option>
                                <option value="Mostaza Rosario Centro (Av. Pellegrini 1200)">Mostaza Rosario Centro (Av. Pellegrini 1200)</option>
                                <option value="Mostaza Mendoza Plaza (Av. San Martín 800)">Mostaza Mendoza Plaza (Av. San Martín 800)</option>
                                <option value="Mostaza Formosa Centro (Av. 25 de Mayo 150)">Mostaza Formosa Centro (Av. 25 de Mayo 150)</option>
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-clock"></i> Horario estimado de retiro</label>
                            <select name="horario_retiro">
                                <option value="Lo antes posible (15 - 20 min)">Lo antes posible (15 - 20 min)</option>
                                <option value="En 30 minutos">En 30 minutos</option>
                                <option value="En 45 minutos">En 45 minutos</option>
                                <option value="En 1 hora">En 1 hora</option>
                            </select>
                        </div>
                    </div>

                    <!-- Campos para Salón -->
                    <div id="fields-salon" class="delivery-form-fields" style="<?= $tipoEntrega !== 'salon' ? 'display:none;' : '' ?>">
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-chair"></i> Número de Mesa en el Salón *</label>
                            <select name="numero_mesa">
                                <?php for ($m = 1; $m <= 25; $m++): ?>
                                    <option value="<?= $m ?>">Mesa <?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-comment-dots"></i> Notas para el Mozo / Servicio</label>
                            <input type="text" name="notas_salon" placeholder="Ej: Traer servilletas extras / cubiertos">
                        </div>
                    </div>
                </div>

                <!-- 3. Procedimiento y Medios de Pago -->
                <div class="card-section">
                    <div class="card-section-title">
                        <i class="fa-solid fa-credit-card"></i>
                        <span>3. Procedimiento y Medio de Pago</span>
                    </div>

                    <div class="payment-tabs">
                        <div class="payment-tab-btn active" onclick="seleccionarPago('tarjeta')" id="tab-tarjeta">
                            <i class="fa-solid fa-credit-card"></i>
                            <span>Tarjeta Débito / Crédito</span>
                        </div>
                        <div class="payment-tab-btn" onclick="seleccionarPago('mercadopago')" id="tab-mercadopago">
                            <i class="fa-solid fa-qrcode"></i>
                            <span>Mercado Pago</span>
                        </div>
                        <div class="payment-tab-btn" onclick="seleccionarPago('transferencia')" id="tab-transferencia">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>Transferencia</span>
                        </div>
                        <div class="payment-tab-btn" onclick="seleccionarPago('efectivo')" id="tab-efectivo">
                            <i class="fa-solid fa-money-bill-wave"></i>
                            <span>Efectivo</span>
                        </div>
                    </div>
                    <input type="hidden" name="metodo_pago" id="metodo_pago_input" value="tarjeta">

                    <!-- Panel: Tarjeta de Crédito / Débito interactiva -->
                    <div id="panel-tarjeta" class="payment-content-panel">
                        <!-- Vista 3D Dinámica -->
                        <div class="card-preview-wrap">
                            <div class="credit-card-3d" id="creditCardPreview">
                                <!-- Frente -->
                                <div class="card-face card-front">
                                    <div style="display:flex;justify-content:space-between;align-items:center;">
                                        <div class="card-chip"></div>
                                        <div class="card-brand-logo" id="cardBrandLogo">VISA</div>
                                    </div>
                                    <div class="card-number-display" id="cardNumberDisplay">•••• •••• •••• ••••</div>
                                    <div class="card-footer-info">
                                        <div>
                                            <div style="font-size:0.65rem;color:#9CA3AF;">TITULAR</div>
                                            <div class="card-holder-name" id="cardHolderDisplay"><?= htmlspecialchars(strtoupper($usuario['nombre'] . ' ' . $usuario['apellido'])) ?></div>
                                        </div>
                                        <div>
                                            <div style="font-size:0.65rem;color:#9CA3AF;">VENCE</div>
                                            <div class="card-exp-date" id="cardExpDisplay">MM/AA</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Dorso -->
                                <div class="card-face card-back">
                                    <div class="card-black-bar"></div>
                                    <div class="card-cvv-band" id="cardCvvDisplay">•••</div>
                                    <div style="text-align:right;padding-right:20px;font-size:0.75rem;color:#aaa;">CÓDIGO DE SEGURIDAD</div>
                                </div>
                            </div>
                        </div>

                        <div class="delivery-form-fields">
                            <div class="form-group-custom" style="grid-column: span 2;">
                                <label>Número de Tarjeta *</label>
                                <input type="text" name="tarjeta_numero" id="inputCardNumber" 
                                       placeholder="4500 0000 0000 0000" maxlength="19" autocomplete="cc-number">
                            </div>
                            <div class="form-group-custom">
                                <label>Nombre y Apellido (como figura en la tarjeta) *</label>
                                <input type="text" name="tarjeta_nombre" id="inputCardHolder" 
                                       placeholder="JUAN PEREZ" value="<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>">
                            </div>
                            <div class="form-group-custom">
                                <label>DNI del Titular *</label>
                                <input type="text" name="tarjeta_dni" placeholder="Ej: 38.123.456" maxlength="10">
                            </div>
                            <div class="form-group-custom">
                                <label>Vencimiento (MM/AA) *</label>
                                <input type="text" name="tarjeta_vence" id="inputCardExp" placeholder="MM/AA" maxlength="5">
                            </div>
                            <div class="form-group-custom">
                                <label>Código de Seguridad (CVV) *</label>
                                <input type="password" name="tarjeta_cvv" id="inputCardCvv" placeholder="123" maxlength="4">
                            </div>
                            <div class="form-group-custom" style="grid-column: span 2;">
                                <label>Cuotas disponibles</label>
                                <select name="tarjeta_cuotas">
                                    <option value="1">1 pago sin interés (Precio contado)</option>
                                    <option value="3">3 cuotas fijas</option>
                                    <option value="6">6 cuotas fijas</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Mercado Pago -->
                    <div id="panel-mercadopago" class="payment-content-panel" style="display:none;">
                        <div class="mp-box">
                            <img src="https://http2.mlstatic.com/frontend-assets/ui-navigation/5.18.9/mercadopago/logo__large.png" alt="Mercado Pago" class="mp-logo">
                            <h3 style="color:#009EE3;margin-bottom:6px;">Pagá con QR o dinero en Mercado Pago</h3>
                            <p style="color:#555;font-size:0.9rem;">Escaneá el código QR interoperable desde tu celular con cualquier app bancaria o Mercado Pago.</p>
                            
                            <div class="mp-qr-container">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=00020101021243650016COM.MERCADOPAGO0136mostaza-oficial-pos-checkout-argentina5204000053030325802AR5914MOSTAZA%20ARGENTINA6012BUENOS%20AIRES62070503***6304ABCD" alt="QR Mercado Pago">
                                <div style="font-size:0.75rem;font-weight:700;color:#009EE3;margin-top:6px;">
                                    <i class="fa-solid fa-shield-check"></i> QR Verificado Mostaza Oficial
                                </div>
                            </div>
                            <div style="background:#e0f2fe;color:#0369a1;padding:10px;border-radius:8px;font-size:0.85rem;font-weight:600;">
                                <i class="fa-solid fa-circle-info"></i> Al confirmar el pedido, tu pago se procesará y acreditará en tiempo real.
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Transferencia Bancaria -->
                    <div id="panel-transferencia" class="payment-content-panel" style="display:none;">
                        <div class="transfer-box">
                            <h4 style="color:#B45309;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-building-columns"></i> Cuentas Oficiales de Mostaza S.A.
                            </h4>
                            <div class="transfer-data-row">
                                <span>Banco / Billetera:</span>
                                <strong>Banco Santander / Mercado Pago</strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>Alias de Transferencia:</span>
                                <strong>mostaza.oficial.mp <button type="button" class="btn-copy" onclick="copiarTexto('mostaza.oficial.mp')">Copiar</button></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>CVU / CBU:</span>
                                <strong>0000003100019283746501 <button type="button" class="btn-copy" onclick="copiarTexto('0000003100019283746501')">Copiar</button></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>CUIT:</span>
                                <strong>30-71234567-9 (Mostaza Gastronomía S.A.)</strong>
                            </div>

                            <div class="form-group-custom" style="margin-top:16px;">
                                <label><i class="fa-solid fa-receipt"></i> Número de Comprobante / Referencia de Transferencia *</label>
                                <input type="text" name="comprobante_transferencia" placeholder="Ej: Operación #98765432">
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Efectivo -->
                    <div id="panel-efectivo" class="payment-content-panel" style="display:none;">
                        <div class="cash-box">
                            <h4 style="color:#15803D;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-money-bill-1-wave"></i> Pago en Efectivo contra Entrega / en Caja
                            </h4>
                            <p style="color:#444;font-size:0.9rem;">Indicá con cuánto vas a pagar para que el repartidor o cajero lleve tu cambio exacto.</p>

                            <div class="form-group-custom" style="margin-top:12px;">
                                <label>¿Con cuánto vas a pagar? ($)</label>
                                <input type="number" name="paga_con" id="inputPagaCon" 
                                       value="<?= intval($totalFinal) ?>" min="<?= intval($totalFinal) ?>" step="500"
                                       oninput="calcularVuelto()">
                            </div>

                            <div class="quick-cash-options">
                                <button type="button" class="btn-cash-quick" onclick="setMontoExacto()">Monto Exacto</button>
                                <button type="button" class="btn-cash-quick" onclick="setBillete(10000)">$10.000</button>
                                <button type="button" class="btn-cash-quick" onclick="setBillete(20000)">$20.000</button>
                                <button type="button" class="btn-cash-quick" onclick="setBillete(50000)">$50.000</button>
                            </div>

                            <div style="margin-top:16px;background:#fff;padding:12px 16px;border-radius:10px;display:flex;justify-content:space-between;align-items:center;border:1px solid #86EFAC;">
                                <span style="font-weight:700;color:#166534;">Tu cambio / vuelto estimado:</span>
                                <strong id="displayVuelto" style="font-size:1.2rem;color:#166534;">$0</strong>
                            </div>
                        </div>
                    </div>

                </div>

            </div>


            <!-- COLUMNA DERECHA: SIDEBAR DE TOTALES Y CONFIRMACIÓN -->
            <div class="checkout-sidebar-col">
                <div class="checkout-summary-card">
                    <h3 style="font-size:1.3rem;font-weight:800;color:var(--dark);margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-receipt" style="color:var(--rojo);"></i> Resumen de Compra
                    </h3>

                    <div class="summary-row">
                        <span>Subtotal (<span id="summary-cant-items"><?= $cantItems ?></span> productos)</span>
                        <strong id="summary-subtotal">$<?= number_format($subtotal, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Cupón de Descuento -->
                    <div style="margin:14px 0;">
                        <label style="font-size:0.85rem;font-weight:700;color:#374151;">¿Tenés un Cupón de Descuento?</label>
                        <?php if ($cuponActual): ?>
                            <div class="applied-coupon-tag" id="applied-coupon-box">
                                <span><i class="fa-solid fa-tag"></i> <strong><?= htmlspecialchars($cuponActual['codigo']) ?></strong> (<?= htmlspecialchars($cuponActual['descripcion']) ?>)</span>
                                <button type="button" class="btn-remove-coupon" onclick="removerCupon()">✕</button>
                            </div>
                        <?php else: ?>
                            <div class="coupon-input-group" id="coupon-input-wrap">
                                <input type="text" id="coupon-code-input" placeholder="Ej: MOSTAZA10">
                                <button type="button" class="btn-apply-coupon" onclick="aplicarCupon()">Aplicar</button>
                            </div>
                            <div style="font-size:0.75rem;color:#6B7280;margin-top:4px;">
                                Cupones disponibles: <span style="font-weight:700;color:var(--rojo);cursor:pointer;" onclick="usarCuponRapido('MOSTAZA10')">MOSTAZA10</span>, 
                                <span style="font-weight:700;color:var(--rojo);cursor:pointer;" onclick="usarCuponRapido('DIBU20')">DIBU20</span>, 
                                <span style="font-weight:700;color:var(--rojo);cursor:pointer;" onclick="usarCuponRapido('TRUENO15')">TRUENO15</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="summary-row" id="row-descuento-cupon" style="<?= $descuentoCupon <= 0 ? 'display:none;' : '' ?>">
                        <span style="color:#16A34A;"><i class="fa-solid fa-tag"></i> Descuento Cupón</span>
                        <strong style="color:#16A34A;" id="summary-descuento-cupon">-$<?= number_format($descuentoCupon, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Puntos Mostaza Fidelización -->
                    <?php if ($puntosDisponibles > 0): ?>
                    <div class="points-loyalty-box">
                        <div class="points-loyalty-info">
                            <i class="fa-solid fa-star"></i>
                            <div>
                                <strong style="font-size:0.85rem;color:#92400E;display:block;">Puntos Mostaza</strong>
                                <span style="font-size:0.75rem;color:#B45309;">Tenés <strong><?= $puntosDisponibles ?> pts</strong> ($<?= number_format($puntosDisponibles, 0, ',', '.') ?>)</span>
                            </div>
                        </div>
                        <label class="switch" style="margin-left:auto;">
                            <input type="checkbox" id="toggle-puntos" <?= $usarPuntos ? 'checked' : '' ?> onchange="togglePuntos(this.checked)">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row" id="row-descuento-puntos" style="<?= $descuentoPuntos <= 0 ? 'display:none;' : '' ?>">
                        <span style="color:#D97706;"><i class="fa-solid fa-star"></i> Puntos canjeados</span>
                        <strong style="color:#D97706;" id="summary-descuento-puntos">-$<?= number_format($descuentoPuntos, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Costo de Envío -->
                    <div class="summary-row">
                        <span>Costo de Envío</span>
                        <strong id="summary-envio"><?= ($costoEnvio > 0) ? '$' . number_format($costoEnvio, 0, ',', '.') : '¡GRATIS!' ?></strong>
                    </div>

                    <!-- Propina -->
                    <div style="margin:12px 0;">
                        <label style="font-size:0.85rem;font-weight:700;color:#374151;">Propina para el repartidor / mozo</label>
                        <div class="tip-selector-grid">
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 0) ? 'active' : '' ?>" onclick="seleccionarPropina(0, 'porcentaje')">0%</button>
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 5) ? 'active' : '' ?>" onclick="seleccionarPropina(5, 'porcentaje')">5%</button>
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 10) ? 'active' : '' ?>" onclick="seleccionarPropina(10, 'porcentaje')">10%</button>
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 15) ? 'active' : '' ?>" onclick="seleccionarPropina(15, 'porcentaje')">15%</button>
                        </div>
                    </div>

                    <div class="summary-row" id="row-propina" style="<?= $propinaVal <= 0 ? 'display:none;' : '' ?>">
                        <span>Propina</span>
                        <strong id="summary-propina">$<?= number_format($propinaVal, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Total Final -->
                    <div class="summary-row total-row">
                        <span>Total Final</span>
                        <strong id="summary-total">$<?= number_format($totalFinal, 0, ',', '.') ?></strong>
                    </div>

                    <!-- Puntos que suma esta compra -->
                    <div style="background:#FEF3C7;color:#92400E;padding:10px;border-radius:10px;font-size:0.85rem;font-weight:700;text-align:center;margin-top:14px;">
                        <i class="fa-solid fa-award"></i> ¡Con esta compra sumás <span id="summary-puntos-ganar"><?= $puntosGanados ?></span> Puntos Mostaza!
                    </div>

                    <!-- Botón de Envío del Pedido -->
                    <button type="submit" class="btn-submit-order" id="btnConfirmarPedido">
                        <i class="fa-solid fa-lock"></i> Confirmar y Pagar Pedido
                    </button>

                    <div style="text-align:center;margin-top:12px;font-size:0.75rem;color:#9CA3AF;">
                        <i class="fa-solid fa-shield-halved"></i> Transacción cifrada con protocolo SSL seguro
                    </div>

                </div>
            </div>

        </div>
    </form>

    <?php endif; ?>

</div>

<?php include('includes/footer.php'); ?>

<script>
let totalActualNum = <?= floatval($totalFinal) ?>;

// ------------------------------------------------------------
// Funciones AJAX de Carrito
// ------------------------------------------------------------
function cambiarCantidad(key, delta) {
    ejecutarAccionCarrito({ accion: 'modificar_cantidad', key: key, delta: delta });
}

function eliminarItem(key) {
    if (confirm('¿Querés quitar este producto del carrito?')) {
        ejecutarAccionCarrito({ accion: 'eliminar_item', key: key });
    }
}

function guardarNotaItem(key, nota) {
    ejecutarAccionCarrito({ accion: 'guardar_nota', key: key, nota: nota }, false);
}

function aplicarCupon() {
    const input = document.getElementById('coupon-code-input');
    const codigo = input ? input.value.trim() : '';
    if (!codigo) return;
    ejecutarAccionCarrito({ accion: 'aplicar_cupon', codigo: codigo });
}

function usarCuponRapido(cod) {
    ejecutarAccionCarrito({ accion: 'aplicar_cupon', codigo: cod });
}

function removerCupon() {
    ejecutarAccionCarrito({ accion: 'remover_cupon' });
}

function togglePuntos(usar) {
    ejecutarAccionCarrito({ accion: 'toggle_puntos', usar: usar });
}

function seleccionarPropina(valor, tipo) {
    document.querySelectorAll('.btn-tip-pill').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    ejecutarAccionCarrito({ accion: 'set_propina', valor: valor, tipo: tipo });
}

function seleccionarEntrega(tipo) {
    document.querySelectorAll('.delivery-opt-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('opt-' + tipo).classList.add('selected');
    document.getElementById('tipo_entrega_input').value = tipo;

    document.getElementById('fields-delivery').style.display = (tipo === 'delivery') ? 'grid' : 'none';
    document.getElementById('fields-takeaway').style.display = (tipo === 'takeaway') ? 'grid' : 'none';
    document.getElementById('fields-salon').style.display = (tipo === 'salon') ? 'grid' : 'none';

    ejecutarAccionCarrito({ accion: 'set_tipo_entrega', tipo_entrega: tipo });
}

function seleccionarPago(metodo) {
    document.querySelectorAll('.payment-tab-btn').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + metodo).classList.add('active');
    document.getElementById('metodo_pago_input').value = metodo;

    document.querySelectorAll('.payment-content-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel-' + metodo).style.display = 'block';

    if (metodo === 'efectivo') {
        calcularVuelto();
    }
}

function ejecutarAccionCarrito(payload, recargarSiVacio = true) {
    fetch('actualizar_carrito_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok && data.error) {
            alert(data.error);
            return;
        }
        if (recargarSiVacio && data.cant_items === 0) {
            window.location.reload();
            return;
        }
        actualizarDOMTotales(data);
    })
    .catch(err => console.error('Error actualizando carrito:', err));
}

function actualizarDOMTotales(data) {
    totalActualNum = data.total_final;

    document.getElementById('summary-cant-items').textContent = data.cant_items;
    document.getElementById('summary-subtotal').textContent = data.subtotal_formateado;
    document.getElementById('summary-envio').textContent = data.costo_envio_formateado;
    document.getElementById('summary-total').textContent = data.total_final_formateado;
    document.getElementById('summary-puntos-ganar').textContent = data.puntos_a_ganar;

    // Descuento Cupón
    const rowCupon = document.getElementById('row-descuento-cupon');
    if (data.descuento_cupon > 0) {
        rowCupon.style.display = 'flex';
        document.getElementById('summary-descuento-cupon').textContent = data.descuento_cupon_formateado;
    } else {
        rowCupon.style.display = 'none';
    }

    // Descuento Puntos
    const rowPuntos = document.getElementById('row-descuento-puntos');
    if (data.descuento_puntos > 0) {
        rowPuntos.style.display = 'flex';
        document.getElementById('summary-descuento-puntos').textContent = data.descuento_puntos_formateado;
    } else {
        rowPuntos.style.display = 'none';
    }

    // Propina
    const rowPropina = document.getElementById('row-propina');
    if (data.propina > 0) {
        rowPropina.style.display = 'flex';
        document.getElementById('summary-propina').textContent = data.propina_formateada;
    } else {
        rowPropina.style.display = 'none';
    }

    // Actualizar cantidades individuales
    if (data.items) {
        data.items.forEach(it => {
            const elQty = document.getElementById('qty-' + it.key);
            const elSub = document.getElementById('subtot-' + it.key);
            if (elQty) elQty.textContent = it.cantidad;
            if (elSub) elSub.textContent = it.subtotal_formateado;
        });
    }

    // Si cambió el total y está en efectivo, reajustar input
    const inputPaga = document.getElementById('inputPagaCon');
    if (inputPaga) {
        inputPaga.min = Math.ceil(data.total_final);
        if (parseFloat(inputPaga.value) < data.total_final) {
            inputPaga.value = Math.ceil(data.total_final);
        }
        calcularVuelto();
    }
}

// ------------------------------------------------------------
// Funciones de Pago en Efectivo y Vuelto
// ------------------------------------------------------------
function calcularVuelto() {
    const input = document.getElementById('inputPagaCon');
    const display = document.getElementById('displayVuelto');
    if (!input || !display) return;

    const pagaCon = parseFloat(input.value) || 0;
    const vuelto = Math.max(0, pagaCon - totalActualNum);
    display.textContent = '$' + vuelto.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function setMontoExacto() {
    const input = document.getElementById('inputPagaCon');
    if (input) {
        input.value = Math.ceil(totalActualNum);
        calcularVuelto();
    }
}

function setBillete(monto) {
    const input = document.getElementById('inputPagaCon');
    if (input) {
        input.value = monto;
        calcularVuelto();
    }
}

function copiarTexto(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        alert('Copiado al portapapeles: ' + texto);
    });
}

// ------------------------------------------------------------
// Interactividad de Tarjeta 3D en Vivo
// ------------------------------------------------------------
const cardPreview = document.getElementById('creditCardPreview');
const inputNumber = document.getElementById('inputCardNumber');
const inputHolder = document.getElementById('inputCardHolder');
const inputExp = document.getElementById('inputCardExp');
const inputCvv = document.getElementById('inputCardCvv');

if (inputNumber) {
    inputNumber.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 16);
        let formatted = val.match(/.{1,4}/g)?.join(' ') || '';
        e.target.value = formatted;
        document.getElementById('cardNumberDisplay').textContent = formatted || '•••• •••• •••• ••••';

        // Detectar Franquicia
        const brandLogo = document.getElementById('cardBrandLogo');
        if (val.startsWith('4')) {
            brandLogo.textContent = 'VISA';
        } else if (/^5[1-5]/.test(val)) {
            brandLogo.textContent = 'MASTERCARD';
        } else if (/^3[47]/.test(val)) {
            brandLogo.textContent = 'AMEX';
        } else if (/^5896/.test(val) || /^6042/.test(val)) {
            brandLogo.textContent = 'CABAL';
        } else {
            brandLogo.textContent = 'CARD';
        }
    });
}

if (inputHolder) {
    inputHolder.addEventListener('input', (e) => {
        document.getElementById('cardHolderDisplay').textContent = e.target.value.toUpperCase() || 'NOMBRE TITULAR';
    });
}

if (inputExp) {
    inputExp.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 4);
        if (val.length >= 2) {
            val = val.substring(0, 2) + '/' + val.substring(2);
        }
        e.target.value = val;
        document.getElementById('cardExpDisplay').textContent = val || 'MM/AA';
    });
}

if (inputCvv) {
    inputCvv.addEventListener('focus', () => cardPreview.classList.add('flipped'));
    inputCvv.addEventListener('blur', () => cardPreview.classList.remove('flipped'));
    inputCvv.addEventListener('input', (e) => {
        let val = e.target.value.replace(/\D/g, '').substring(0, 4);
        e.target.value = val;
        document.getElementById('cardCvvDisplay').textContent = '•'.repeat(val.length) || '•••';
    });
}

// Validación de Formulario antes de enviar
document.getElementById('checkout-form')?.addEventListener('submit', function(e) {
    const metodo = document.getElementById('metodo_pago_input').value;
    const tipoEntrega = document.getElementById('tipo_entrega_input').value;

    if (tipoEntrega === 'delivery') {
        const dir = document.getElementById('direccion_envio').value.trim();
        const tel = document.getElementById('telefono_contacto').value.trim();
        if (!dir || !tel) {
            alert('Por favor, completá tu dirección y teléfono para el delivery.');
            e.preventDefault();
            return;
        }
    }

    if (metodo === 'tarjeta') {
        const num = inputNumber.value.replace(/\s/g, '');
        if (num.length < 15) {
            alert('Por favor, ingresá un número de tarjeta válido.');
            e.preventDefault();
            return;
        }
    }

    const btn = document.getElementById('btnConfirmarPedido');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando Pago y Creando Comanda...';
});
</script>

</body>
</html>
