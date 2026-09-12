<?php
// ============================================================
// carrito.php
// Carrito de compras y Checkout Premium de Mostaza:
// - 100% del diseño visual, tarjetas, notas por producto, cupones,
//   puntos Mostaza, propinas, selector de modalidad y tarjeta 3D original.
// - Geolocalización con Mapa Leaflet GPS en tiempo real.
// - Mapa interactivo de mesas por secciones.
// - Datos bancarios reales (Mercado Pago, Alberto Emanuel Gomez, gomez16.mp),
//   botones de copia 1-clic y adjunto de comprobantes.
// - Pago en efectivo fluido sin bloqueos de monto y vuelto dinámico.
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

// Obtener datos del cliente
$stmtU = $conn->prepare("SELECT * FROM usuario_web WHERE idusuario = ?");
$stmtU->bind_param("i", $idusuario);
$stmtU->execute();
$usuario = $stmtU->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: login.php");
    exit();
}

$puntosDisponibles = intval($usuario['puntos'] ?? 0);
$direccionGuardada = $usuario['direccion_favorita'] ?? $usuario['direccion'] ?? 'Av. 25 de Mayo 150, Formosa';
$telefonoGuardado = $usuario['telefono_favorito'] ?? $usuario['telefono'] ?? '';

// Recálculo inicial de subtotal
$subtotal = 0;
$cantItems = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
    $cantItems += $item['cantidad'];
}

// Cupón de descuento
$descuentoCupon = 0;
$cuponActual = $_SESSION['cupon_aplicado'] ?? null;
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

// Puntos de fidelización
$usarPuntos = !empty($_SESSION['usar_puntos']);
$descuentoPuntos = 0;
if ($usarPuntos && $puntosDisponibles > 0) {
    $maxDescuentoPuntos = intval(($subtotal - $descuentoCupon) * 0.5);
    $descuentoPuntos = min($puntosDisponibles, $maxDescuentoPuntos);
}

// Modalidad de entrega y costo
$tipoEntrega = $_SESSION['tipo_entrega'] ?? 'delivery';
$costoEnvio = ($tipoEntrega === 'delivery' && $subtotal > 0 && $subtotal < 25000) ? 1200.00 : 0.00;

// Propina
$propData = $_SESSION['propina'] ?? ['tipo' => 'porcentaje', 'valor' => 0];
$propinaVal = 0;
if ($propData['tipo'] === 'porcentaje' && $propData['valor'] > 0) {
    $propinaVal = ($subtotal * $propData['valor']) / 100;
} elseif ($propData['tipo'] === 'fijo') {
    $propinaVal = floatval($propData['valor']);
}

// Total Final
$totalFinal = max(0, ($subtotal - $descuentoCupon - $descuentoPuntos) + $costoEnvio + $propinaVal);
$puntosGanados = intval($totalFinal / 100);

// Consultar secciones y mesas en tiempo real
$stmtActiveSalon = $conn->query("SELECT numero_mesa FROM pedido_web WHERE tipo_entrega = 'salon' AND estado IN ('Recibido', 'En Cocina', 'Listo') AND numero_mesa IS NOT NULL");
$mesasOcupadasWeb = [];
if ($stmtActiveSalon) {
    while ($rM = $stmtActiveSalon->fetch_assoc()) {
        $mesasOcupadasWeb[intval($rM['numero_mesa'])] = true;
    }
}

$seccionesWebRes = $conn->query("SELECT * FROM seccion_mesa ORDER BY orden");
$seccionesWeb = [];
if ($seccionesWebRes) {
    while ($s = $seccionesWebRes->fetch_assoc()) {
        $seccionesWeb[] = $s;
    }
}

$mesasWebRes = $conn->query("SELECT m.*, s.nombre AS seccion_nombre FROM mesa m JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE m.activa = 1 ORDER BY m.numero_mesa");
$mesasWeb = [];
if ($mesasWebRes) {
    while ($m = $mesasWebRes->fetch_assoc()) {
        $mesasWeb[] = $m;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito y Checkout · Mostaza</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="checkout.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Leaflet Map para GPS en Vivo -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
#deliveryMap {
    height: 270px;
    width: 100%;
    border-radius: 12px;
    margin-top: 10px;
    border: 2px solid #E2E8F0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    z-index: 10;
}
.btn-gps-loc {
    background: #2563EB;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s ease;
}
.btn-gps-loc:hover {
    background: #1D4ED8;
}
.gps-status-badge {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    color: #1E40AF;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-autofill-card {
    background: #F1F5F9;
    border: 1px solid #CBD5E1;
    color: #334155;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    margin: 4px;
    transition: all 0.2s ease;
}
.btn-autofill-card:hover {
    background: #E2E8F0;
    color: #0F172A;
}
.btn-copy-bank {
    background: #FEF3C7;
    border: 1px solid #FDE68A;
    color: #92400E;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    margin-left: 8px;
}
.btn-copy-bank:hover {
    background: #FDE68A;
}
</style>
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="checkout-container">

    <div class="checkout-header-title">
        <h1>Finalizar tu Pedido</h1>
        <p>Revisá tus productos seleccionados, la entrega con GPS y elegí tu medio de pago.</p>
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

    <form method="post" action="finalizar_pedido.php" id="checkout-form" enctype="multipart/form-data">
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
                        <?php
                        $imgItem = $item['imagen'] ?? 'img/image-removebg-preview.png';
                        if (strpos($imgItem, 'img/') !== 0 && strpos($imgItem, 'http') !== 0) {
                            $imgItem = 'img/' . $imgItem;
                        }
                        ?>
                        <div class="cart-item-card" data-key="<?= htmlspecialchars($key) ?>">
                            <img src="<?= htmlspecialchars($imgItem) ?>" class="cart-item-thumb" alt="<?= htmlspecialchars($item['nombre']) ?>" onerror="this.src='img/image-removebg-preview.png'">
                            
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

                <!-- 2. Modalidad de Entrega y Geolocalización -->
                <div class="card-section">
                    <div class="card-section-title">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>2. Modalidad de Entrega</span>
                    </div>

                    <div class="delivery-options-grid">
                        <div class="delivery-opt-card <?= $tipoEntrega === 'delivery' ? 'selected' : '' ?>" onclick="seleccionarEntrega('delivery')" id="opt-delivery">
                            <i class="fa-solid fa-motorcycle"></i>
                            <strong>Delivery</strong>
                            <span>Envío con GPS a domicilio</span>
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

                    <!-- Campos para Delivery con Mapa Leaflet GPS -->
                    <div id="fields-delivery" class="delivery-form-fields" style="<?= $tipoEntrega !== 'delivery' ? 'display:none;' : '' ?>">
                        <div class="form-group-custom" style="grid-column: span 2;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <label style="margin:0;"><i class="fa-solid fa-house"></i> Dirección de Entrega (Calle y Altura) *</label>
                                <button type="button" class="btn-gps-loc" onclick="geolocalizarUsuario()">
                                    <i class="fa-solid fa-crosshairs"></i> Usar mi ubicación GPS
                                </button>
                            </div>
                            <input type="text" name="direccion_envio" id="direccion_envio" 
                                   placeholder="Ej: Av. 25 de Mayo 450, Formosa" 
                                   value="<?= htmlspecialchars($direccionGuardada) ?>"
                                   onchange="buscarDireccionEnMapa(this.value)">
                        </div>

                        <!-- Mapa Interactivo Leaflet -->
                        <div style="grid-column: span 2;">
                            <label style="font-size:0.85rem;color:#475569;font-weight:700;">
                                <i class="fa-solid fa-map-location-dot" style="color:var(--rojo);"></i> Ajustá el marcador en el mapa para ubicar tu puerta exacta:
                            </label>
                            <div id="deliveryMap"></div>
                            
                            <div class="gps-status-badge" id="gpsStatusBadge">
                                <i class="fa-solid fa-location-dot"></i>
                                <span id="gpsStatusText">Ubicación fijada en mapa. Arrastrá el marcador si necesitás ajustar la posición.</span>
                            </div>

                            <input type="hidden" name="latitud" id="latitud_input" value="<?= SUCURSAL_LAT ?>">
                            <input type="hidden" name="longitud" id="longitud_input" value="<?= SUCURSAL_LNG ?>">
                        </div>

                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-phone"></i> Teléfono de Contacto *</label>
                            <input type="tel" name="telefono_contacto" id="telefono_contacto" 
                                   placeholder="Ej: 3704 123456" 
                                   value="<?= htmlspecialchars($telefonoGuardado) ?>">
                        </div>
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-bell"></i> Piso / Depto / Indicaciones timbre</label>
                            <input type="text" name="referencia_entrega" placeholder="Ej: Piso 2 Depto B, timbre blanco">
                        </div>
                    </div>

                    <!-- Campos para Take Away -->
                    <div id="fields-takeaway" class="delivery-form-fields" style="<?= $tipoEntrega !== 'takeaway' ? 'display:none;' : '' ?>">
                        <div class="form-group-custom">
                            <label><i class="fa-solid fa-store"></i> Sucursal de Retiro *</label>
                            <select name="sucursal_retiro">
                                <option value="Mostaza Formosa Centro (Av. 25 de Mayo 150)">Mostaza Formosa Centro (Av. 25 de Mayo 150)</option>
                                <option value="Mostaza Costanera Formosa">Mostaza Costanera Formosa</option>
                                <option value="Mostaza Circunvalación Formosa">Mostaza Circunvalación Formosa</option>
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

                    <!-- Campos para Salón con Mapa por Secciones -->
                    <div id="fields-salon" class="delivery-form-fields" style="<?= $tipoEntrega !== 'salon' ? 'display:none;' : '' ?> grid-column: span 2;">
                        <div style="margin-bottom:12px;">
                            <label style="font-weight:700;font-size:0.95rem;color:#111827;display:block;margin-bottom:6px;">
                                <i class="fa-solid fa-chair" style="color:var(--rojo);"></i> Seleccioná tu Mesa en el Salón *
                            </label>
                            <span style="font-size:0.85rem;color:#64748B;display:block;margin-bottom:12px;">
                                Elegí la sección del local y hacé clic sobre una mesa disponible (en verde).
                            </span>

                            <!-- Pestañas de Secciones Web -->
                            <div style="display:flex;gap:6px;margin-bottom:12px;overflow-x:auto;padding-bottom:4px;" id="tabsSeccionesWeb">
                                <button type="button" onclick="filtrarSeccionWeb('todas', this)" class="sec-web-tab" style="background:#A30002;color:#fff;border:none;padding:6px 14px;border-radius:20px;font-size:0.85rem;font-weight:700;cursor:pointer;">
                                    Todas las Secciones
                                </button>
                                <?php foreach ($seccionesWeb as $secW): ?>
                                <button type="button" onclick="filtrarSeccionWeb(<?= $secW['idseccion'] ?>, this)" class="sec-web-tab" style="background:#F1F5F9;color:#475569;border:none;padding:6px 14px;border-radius:20px;font-size:0.85rem;font-weight:700;cursor:pointer;">
                                    <i class="fa-solid <?= htmlspecialchars($secW['icono']) ?>"></i> <?= htmlspecialchars($secW['nombre']) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- Mapa de Mesas Web -->
                            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(130px, 1fr));gap:10px;max-height:260px;overflow-y:auto;padding:8px;background:#F8FAFC;border-radius:12px;border:1px solid #E2E8F0;" id="gridMesasWeb">
                                <?php foreach ($mesasWeb as $mWeb): ?>
                                <?php 
                                $numM = intval($mWeb['numero_mesa']);
                                $isOcupada = isset($mesasOcupadasWeb[$numM]);
                                ?>
                                <div class="card-mesa-web <?= $isOcupada ? 'mesa-ocupada' : 'mesa-libre' ?> <?= ($numM === 1 && !$isOcupada) ? 'mesa-selected' : '' ?>" 
                                     data-sec="<?= $mWeb['fk_id_seccion'] ?>"
                                     id="mesa-web-<?= $numM ?>"
                                     onclick="seleccionarMesaWeb(<?= $numM ?>, '<?= htmlspecialchars($mWeb['seccion_nombre']) ?>', <?= $mWeb['capacidad'] ?>, <?= $isOcupada ? 'true' : 'false' ?>)"
                                     style="background:<?= $isOcupada ? '#FEF2F2' : '#FFFFFF' ?>;border:2px solid <?= $isOcupada ? '#EF4444' : (($numM === 1) ? '#F59E0B' : '#10B981') ?>;border-radius:10px;padding:10px 8px;text-align:center;cursor:<?= $isOcupada ? 'not-allowed' : 'pointer' ?>;transition:all 0.2s;">
                                    <i class="fa-solid fa-chair" style="color:<?= $isOcupada ? '#EF4444' : '#10B981' ?>;font-size:1.2rem;margin-bottom:4px;"></i>
                                    <strong style="display:block;font-size:0.9rem;color:#0F172A;">Mesa <?= $numM ?></strong>
                                    <small style="display:block;font-size:0.75rem;color:#64748B;"><?= $mWeb['capacidad'] ?> personas</small>
                                    <span style="display:inline-block;margin-top:4px;font-size:0.7rem;font-weight:800;color:<?= $isOcupada ? '#DC2626' : '#166534' ?>;">
                                        <?= $isOcupada ? 'OCUPADA' : 'DISPONIBLE' ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Indicador de Mesa Seleccionada -->
                            <div id="bannerMesaSeleccionada" style="margin-top:10px;background:#DCFCE7;border:1px solid #86EFAC;color:#166534;padding:10px 14px;border-radius:8px;font-weight:700;font-size:0.9rem;display:flex;align-items:center;justify-content:space-between;">
                                <span><i class="fa-solid fa-circle-check"></i> Mesa seleccionada: <strong>Mesa 1</strong> (Salón Principal · 4 personas)</span>
                                <span style="font-size:0.8rem;background:#166534;color:#fff;padding:2px 8px;border-radius:12px;">Confirmada</span>
                            </div>

                            <input type="hidden" name="numero_mesa" id="inputNumeroMesa" value="1">
                        </div>

                        <div class="form-group-custom" style="margin-top:10px;">
                            <label><i class="fa-solid fa-comment-dots"></i> Notas para el Mozo / Servicio (Opcional)</label>
                            <input type="text" name="notas_salon" placeholder="Ej: Traer servilletas extras / cubiertos">
                        </div>
                    </div>
                </div>

                <!-- 3. Procedimiento y Medios de Pago Electrónicos -->
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

                    <!-- Panel 1: Tarjeta 3D Interactiva -->
                    <div id="panel-tarjeta" class="payment-content-panel">
                        <div class="card-preview-wrap">
                            <div class="credit-card-3d" id="creditCardPreview">
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
                                <div class="card-face card-back">
                                    <div class="card-black-bar"></div>
                                    <div class="card-cvv-band" id="cardCvvDisplay">•••</div>
                                    <div style="text-align:right;padding-right:20px;font-size:0.75rem;color:#aaa;">CÓDIGO DE SEGURIDAD</div>
                                </div>
                            </div>
                        </div>

                        <div style="text-align:center;margin-bottom:12px;">
                            <button type="button" class="btn-autofill-card" onclick="autocompletarTarjetaPrueba('visa')">
                                💳 Llenar con Visa de Prueba
                            </button>
                            <button type="button" class="btn-autofill-card" onclick="autocompletarTarjetaPrueba('mastercard')">
                                💳 Llenar con Mastercard de Prueba
                            </button>
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
                                <input type="text" name="tarjeta_dni" id="inputCardDni" placeholder="Ej: 38.123.456" maxlength="10" value="38123456">
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

                    <!-- Panel 2: Mercado Pago -->
                    <div id="panel-mercadopago" class="payment-content-panel" style="display:none;">
                        <div class="mp-box">
                            <img src="https://http2.mlstatic.com/frontend-assets/ui-navigation/5.18.9/mercadopago/logo__large.png" alt="Mercado Pago" class="mp-logo">
                            <h3 style="color:#009EE3;margin-bottom:6px;">Pagá con QR o dinero en Mercado Pago</h3>
                            <p style="color:#555;font-size:0.9rem;">Escaneá el código QR interoperable desde tu celular con cualquier app bancaria o Mercado Pago.</p>
                            
                            <div class="mp-qr-container">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode('00020101021243650016COM.MERCADOPAGO0136' . BANCO_ALIAS . '5204000053030325802AR5914MOSTAZA%20SA6012BUENOS%20AIRES540' . number_format($totalFinal, 2, '.', '') . '6304') ?>" alt="QR Mercado Pago">
                                <div style="font-size:0.8rem;font-weight:700;color:#009EE3;margin-top:6px;">
                                    <i class="fa-solid fa-shield-check"></i> Monto vinculado: $<?= number_format($totalFinal, 0, ',', '.') ?>
                                </div>
                            </div>
                            <div style="background:#e0f2fe;color:#0369a1;padding:10px;border-radius:8px;font-size:0.85rem;font-weight:600;margin-top:12px;">
                                <i class="fa-solid fa-circle-info"></i> Al confirmar el pedido, tu pago se procesará y acreditará en tiempo real.
                            </div>
                        </div>
                    </div>

                    <!-- Panel 3: Transferencia Bancaria (Datos de Alberto Emanuel Gomez) -->
                    <div id="panel-transferencia" class="payment-content-panel" style="display:none;">
                        <div class="transfer-box">
                            <h4 style="color:#B45309;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-building-columns"></i> Cuentas Oficiales para Transferencia Inmediata
                            </h4>
                            <div class="transfer-data-row">
                                <span>Banco / Entidad:</span>
                                <strong><?= htmlspecialchars(BANCO_NOMBRE) ?></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>Titular de la Cuenta:</span>
                                <strong><?= htmlspecialchars(BANCO_TITULAR) ?></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>CUIT / CUIL:</span>
                                <strong><?= htmlspecialchars(BANCO_CUIT) ?> <button type="button" class="btn-copy-bank" onclick="copiarTexto('<?= htmlspecialchars(BANCO_CUIT) ?>')"><i class="fa-regular fa-copy"></i> Copiar</button></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>Alias de Transferencia:</span>
                                <strong style="color:var(--rojo);font-weight:800;"><?= htmlspecialchars(BANCO_ALIAS) ?> <button type="button" class="btn-copy-bank" onclick="copiarTexto('<?= htmlspecialchars(BANCO_ALIAS) ?>')"><i class="fa-regular fa-copy"></i> Copiar</button></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>CVU / CBU:</span>
                                <strong><?= htmlspecialchars(BANCO_CVU) ?> <button type="button" class="btn-copy-bank" onclick="copiarTexto('<?= htmlspecialchars(BANCO_CVU) ?>')"><i class="fa-regular fa-copy"></i> Copiar</button></strong>
                            </div>
                            <div class="transfer-data-row">
                                <span>Monto Exacto a Transferir:</span>
                                <strong style="color:#15803D;font-size:1.1rem;">$<?= number_format($totalFinal, 0, ',', '.') ?> <button type="button" class="btn-copy-bank" onclick="copiarTexto('<?= intval($totalFinal) ?>')"><i class="fa-regular fa-copy"></i> Copiar Monto</button></strong>
                            </div>

                            <div class="form-group-custom" style="margin-top:16px;">
                                <label><i class="fa-solid fa-receipt"></i> Número de Comprobante / Referencia de Transferencia</label>
                                <input type="text" name="comprobante_transferencia" placeholder="Ej: Operación #98765432">
                            </div>

                            <div class="form-group-custom" style="margin-top:12px;">
                                <label><i class="fa-solid fa-file-arrow-up"></i> Adjuntar Comprobante (Opcional - JPG, PNG o PDF)</label>
                                <input type="file" name="comprobante_archivo" id="comprobante_archivo" accept="image/*,.pdf" style="background:#fff;padding:8px;border:1px solid #CBD5E1;border-radius:8px;width:100%;">
                            </div>
                        </div>
                    </div>

                    <!-- Panel 4: Efectivo (Sin restricciones) -->
                    <div id="panel-efectivo" class="payment-content-panel" style="display:none;">
                        <div class="cash-box">
                            <h4 style="color:#15803D;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                                <i class="fa-solid fa-money-bill-1-wave"></i> Pago en Efectivo contra Entrega / en Caja
                            </h4>
                            <p style="color:#444;font-size:0.9rem;">Indicá con cuánto vas a pagar para que el repartidor o cajero lleve tu cambio exacto.</p>

                            <div class="form-group-custom" style="margin-top:12px;">
                                <label>¿Con cuánto vas a pagar? ($)</label>
                                <input type="number" name="paga_con" id="inputPagaCon" 
                                       value="<?= intval($totalFinal) ?>"
                                       placeholder="Ej: <?= intval($totalFinal) ?>"
                                       oninput="calcularVuelto()">
                            </div>

                            <div class="quick-cash-options" id="quickCashContainer">
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


            <!-- COLUMNA DERECHA: SIDEBAR DE TOTALES Y CONFIRMACIÓN ORIGINAL -->
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
                    <div class="summary-row" id="row-envio-display">
                        <span>Costo de Envío</span>
                        <strong id="summary-envio"><?= ($costoEnvio > 0) ? '$' . number_format($costoEnvio, 0, ',', '.') : '¡GRATIS!' ?></strong>
                    </div>

                    <!-- Propina -->
                    <div style="margin:12px 0;">
                        <label style="font-size:0.85rem;font-weight:700;color:#374151;">Propina para el repartidor / mozo</label>
                        <div class="tip-selector-grid">
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 0) ? 'active' : '' ?>" onclick="seleccionarPropina(0, 'porcentaje', this)">0%</button>
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 5) ? 'active' : '' ?>" onclick="seleccionarPropina(5, 'porcentaje', this)">5%</button>
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 10) ? 'active' : '' ?>" onclick="seleccionarPropina(10, 'porcentaje', this)">10%</button>
                            <button type="button" class="btn-tip-pill <?= ($propData['valor'] == 15) ? 'active' : '' ?>" onclick="seleccionarPropina(15, 'porcentaje', this)">15%</button>
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
let sucursalLat = <?= SUCURSAL_LAT ?>;
let sucursalLng = <?= SUCURSAL_LNG ?>;

// ------------------------------------------------------------
// INICIALIZACIÓN DE MAPA LEAFLET GPS
// ------------------------------------------------------------
let map = null;
let marker = null;

function initDeliveryMap() {
    const mapContainer = document.getElementById('deliveryMap');
    if (!mapContainer) return;

    let initLat = parseFloat(document.getElementById('latitud_input').value) || sucursalLat;
    let initLng = parseFloat(document.getElementById('longitud_input').value) || sucursalLng;

    map = L.map('deliveryMap').setView([initLat, initLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const mostazaPinIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    marker = L.marker([initLat, initLng], {
        draggable: true,
        icon: mostazaPinIcon
    }).addTo(map);

    marker.bindPopup("<b>📍 Tu ubicación de entrega</b><br>Arrastrá el marcador si necesitás ajustar la puerta.").openPopup();

    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        document.getElementById('latitud_input').value = pos.lat.toFixed(6);
        document.getElementById('longitud_input').value = pos.lng.toFixed(6);
        reverseGeocode(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('latitud_input').value = e.latlng.lat.toFixed(6);
        document.getElementById('longitud_input').value = e.latlng.lng.toFixed(6);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
}

function geolocalizarUsuario() {
    if (!navigator.geolocation) {
        alert("Tu navegador no soporta geolocalización GPS.");
        return;
    }

    const btn = document.querySelector('.btn-gps-loc');
    const prevText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Obteniendo GPS...';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            document.getElementById('latitud_input').value = lat.toFixed(6);
            document.getElementById('longitud_input').value = lng.toFixed(6);

            if (map && marker) {
                map.setView([lat, lng], 16);
                marker.setLatLng([lat, lng]);
            }

            reverseGeocode(lat, lng);

            btn.innerHTML = '<i class="fa-solid fa-check"></i> ¡Ubicación obtenida!';
            setTimeout(() => {
                btn.innerHTML = prevText;
                btn.disabled = false;
            }, 2500);
        },
        function(err) {
            btn.innerHTML = prevText;
            btn.disabled = false;
            alert("No se pudo obtener la ubicación GPS automática. Podés escribir tu dirección o mover el marcador en el mapa.");
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
}

function reverseGeocode(lat, lng) {
    const statusText = document.getElementById('gpsStatusText');
    if (statusText) statusText.textContent = "Buscando dirección de las coordenadas...";

    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.display_name) {
                const addr = data.address || {};
                const road = addr.road || addr.pedestrian || addr.street || '';
                const house = addr.house_number || '';
                const city = addr.city || addr.town || addr.village || addr.suburb || '';

                let direccionLimpia = road;
                if (house) direccionLimpia += ' ' + house;
                if (city) direccionLimpia += ', ' + city;

                if (!direccionLimpia) direccionLimpia = data.display_name.split(',').slice(0, 3).join(',');

                const inputDir = document.getElementById('direccion_envio');
                if (inputDir) inputDir.value = direccionLimpia;

                if (statusText) statusText.textContent = "📍 Ubicación fijada: " + direccionLimpia;
            }
        })
        .catch(() => {
            if (statusText) statusText.textContent = `📍 Coordenadas fijadas: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        });
}

function buscarDireccionEnMapa(dir) {
    if (!dir || dir.length < 4) return;
    let query = dir;
    if (!query.toLowerCase().includes('formosa') && !query.toLowerCase().includes('argentina')) {
        query += ', Formosa, Argentina';
    } else if (!query.toLowerCase().includes('argentina')) {
        query += ', Argentina';
    }
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(results => {
            if (results && results.length > 0) {
                const lat = parseFloat(results[0].lat);
                const lon = parseFloat(results[0].lon);
                document.getElementById('latitud_input').value = lat.toFixed(6);
                document.getElementById('longitud_input').value = lon.toFixed(6);
                if (map && marker) {
                    map.setView([lat, lon], 16);
                    marker.setLatLng([lat, lon]);
                }
            }
        });
}

// ------------------------------------------------------------
// Funciones AJAX de Carrito Originales
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

function seleccionarPropina(valor, tipo, btn) {
    document.querySelectorAll('.btn-tip-pill').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    ejecutarAccionCarrito({ accion: 'set_propina', valor: valor, tipo: tipo });
}

function seleccionarEntrega(tipo) {
    document.querySelectorAll('.delivery-opt-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('opt-' + tipo).classList.add('selected');
    document.getElementById('tipo_entrega_input').value = tipo;

    document.getElementById('fields-delivery').style.display = (tipo === 'delivery') ? 'grid' : 'none';
    document.getElementById('fields-takeaway').style.display = (tipo === 'takeaway') ? 'grid' : 'none';
    document.getElementById('fields-salon').style.display = (tipo === 'salon') ? 'block' : 'none';

    if (tipo === 'delivery' && map) {
        setTimeout(() => map.invalidateSize(), 200);
    }

    ejecutarAccionCarrito({ accion: 'set_tipo_entrega', tipo_entrega: tipo });
}

function seleccionarPago(metodo) {
    document.querySelectorAll('.payment-tab-btn').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + metodo).classList.add('active');
    document.getElementById('metodo_pago_input').value = metodo;

    document.querySelectorAll('.payment-content-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel-' + metodo).style.display = 'block';

    if (metodo === 'efectivo') {
        renderBilletesRapidos();
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

    // Efectivo y vuelto
    const inputPaga = document.getElementById('inputPagaCon');
    if (inputPaga) {
        inputPaga.value = Math.ceil(data.total_final);
        renderBilletesRapidos();
        calcularVuelto();
    }
}

// ------------------------------------------------------------
// Pago en Efectivo y Vuelto Dinámico
// ------------------------------------------------------------
function renderBilletesRapidos() {
    const container = document.getElementById('quickCashContainer');
    if (!container) return;

    container.innerHTML = '';
    const tot = Math.ceil(totalActualNum);
    const opciones = [
        { label: 'Monto Exacto ($' + tot.toLocaleString('es-AR') + ')', val: tot }
    ];

    [10000, 20000, 30000, 50000, 100000].forEach(m => {
        if (m > tot && !opciones.some(o => o.val === m)) {
            opciones.push({ label: '$' + m.toLocaleString('es-AR'), val: m });
        }
    });

    opciones.slice(0, 4).forEach(op => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-cash-quick';
        btn.textContent = op.label;
        btn.onclick = () => setBillete(op.val);
        container.appendChild(btn);
    });
}

function calcularVuelto() {
    const input = document.getElementById('inputPagaCon');
    const display = document.getElementById('displayVuelto');
    if (!input || !display) return;

    const pagaCon = parseFloat(input.value) || 0;
    const vuelto = Math.max(0, pagaCon - totalActualNum);
    display.textContent = '$' + Math.round(vuelto).toLocaleString('es-AR');
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
// Autocompletado de Tarjeta de Prueba
// ------------------------------------------------------------
function autocompletarTarjetaPrueba(tipo) {
    if (tipo === 'visa') {
        document.getElementById('inputCardNumber').value = '4500 1234 5678 9010';
        document.getElementById('inputCardHolder').value = 'JUAN PEREZ';
        document.getElementById('inputCardExp').value = '12/28';
        document.getElementById('inputCardCvv').value = '456';
    } else {
        document.getElementById('inputCardNumber').value = '5400 9876 5432 1098';
        document.getElementById('inputCardHolder').value = 'MARIA GOMEZ';
        document.getElementById('inputCardExp').value = '08/29';
        document.getElementById('inputCardCvv').value = '789';
    }

    document.getElementById('inputCardNumber').dispatchEvent(new Event('input'));
    document.getElementById('inputCardHolder').dispatchEvent(new Event('input'));
    document.getElementById('inputCardExp').dispatchEvent(new Event('input'));
}

// ------------------------------------------------------------
// Tarjeta 3D Interactiva
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

// ------------------------------------------------------------
// Mesas en Salón
// ------------------------------------------------------------
function filtrarSeccionWeb(secId, btn) {
    document.querySelectorAll('.sec-web-tab').forEach(b => {
        b.style.background = '#F1F5F9';
        b.style.color = '#475569';
    });
    btn.style.background = '#A30002';
    btn.style.color = '#FFFFFF';

    const cards = document.querySelectorAll('.card-mesa-web');
    cards.forEach(c => {
        if (secId === 'todas' || c.getAttribute('data-sec') == secId) {
            c.style.display = 'block';
        } else {
            c.style.display = 'none';
        }
    });
}

function seleccionarMesaWeb(num, seccionNombre, capacidad, ocupada) {
    if (ocupada) {
        alert('La Mesa ' + num + ' está actualmente OCUPADA por otros clientes. Por favor seleccioná una mesa disponible en verde.');
        return;
    }

    document.querySelectorAll('.card-mesa-web').forEach(c => {
        if (!c.classList.contains('mesa-ocupada')) {
            c.style.borderColor = '#10B981';
            c.style.background = '#FFFFFF';
        }
    });

    const selectedCard = document.getElementById('mesa-web-' + num);
    if (selectedCard) {
        selectedCard.style.borderColor = '#F59E0B';
        selectedCard.style.background = '#FFFBEB';
    }

    document.getElementById('inputNumeroMesa').value = num;
    const banner = document.getElementById('bannerMesaSeleccionada');
    if (banner) {
        banner.innerHTML = '<span><i class="fa-solid fa-circle-check"></i> Mesa seleccionada: <strong>Mesa ' + num + '</strong> (' + seccionNombre + ' · ' + capacidad + ' personas)</span><span style="font-size:0.8rem;background:#166534;color:#fff;padding:2px 8px;border-radius:12px;">Confirmada</span>';
    }
}

// Validación de Formulario antes de enviar
document.getElementById('checkout-form')?.addEventListener('submit', function(e) {
    const metodo = document.getElementById('metodo_pago_input').value;
    const tipoEntrega = document.getElementById('tipo_entrega_input').value;

    if (tipoEntrega === 'delivery') {
        const dir = document.getElementById('direccion_envio').value.trim();
        const tel = document.getElementById('telefono_contacto').value.trim();
        if (!dir || !tel) {
            alert('Por favor completá tu dirección y teléfono para el delivery.');
            e.preventDefault();
            return;
        }
    }

    if (metodo === 'tarjeta') {
        const num = inputNumber.value.replace(/\s/g, '');
        if (num.length < 15) {
            alert('Por favor ingresá un número de tarjeta válido o usá el botón de prueba.');
            e.preventDefault();
            return;
        }
    }

    const btn = document.getElementById('btnConfirmarPedido');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando Pago y Creando Comanda...';
});

window.addEventListener('DOMContentLoaded', () => {
    initDeliveryMap();
    renderBilletesRapidos();
    calcularVuelto();
});
</script>

</body>
</html>
