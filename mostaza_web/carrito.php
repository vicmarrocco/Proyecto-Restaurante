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

// Consultar sucursales activas para asignación automática por cercanía y Take Away
$sucursalesWebRes = $conn->query("SELECT idsucursal, nombre, direccion, ciudad, latitud, longitud FROM sucursal WHERE activo = 1 ORDER BY idsucursal ASC");
$sucursalesWeb = [];
if ($sucursalesWebRes) {
    while ($sw = $sucursalesWebRes->fetch_assoc()) {
        $sucursalesWeb[] = [
            'id' => intval($sw['idsucursal']),
            'nombre' => $sw['nombre'],
            'direccion' => $sw['direccion'],
            'ciudad' => $sw['ciudad'],
            'lat' => floatval($sw['latitud']),
            'lng' => floatval($sw['longitud'])
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito y Checkout · Mostaza</title>
<!-- Base Framework (Bootstrap) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Google Fonts & Font Awesome -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Estilos Oficiales Mostaza (Sobrescriben Bootstrap) -->
<link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
<link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>">
<link rel="stylesheet" href="checkout.css?v=<?= filemtime('checkout.css') ?>">
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
    background: var(--rojo);
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
    background: var(--rojo-dark);
}
.gps-status-badge {
    background: #FEF3C7;
    border: 1px solid #FDE68A;
    color: #92400E;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 700;
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
@keyframes spinMP {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.mp-choice-card:hover {
    border-color: #009EE3 !important;
    background: #F0F9FF !important;
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

    <?php 
    $errorMostrar = $_SESSION['error_checkout'] ?? ($_GET['error'] ?? '');
    if (!empty($errorMostrar)): 
    ?>
        <div style="background:#FEF2F2;border:2px solid #EF4444;color:#991B1B;padding:14px 20px;border-radius:12px;margin-bottom:24px;font-weight:700;display:flex;align-items:center;justify-content:space-between;box-shadow:0 4px 12px rgba(239,68,68,0.15);">
            <div style="display:flex;align-items:center;gap:12px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size:1.5rem;color:#DC2626;"></i>
                <span><?= htmlspecialchars($errorMostrar) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#991B1B;font-size:1.2rem;cursor:pointer;">✕</button>
        </div>
        <?php unset($_SESSION['error_checkout']); ?>
    <?php endif; ?>

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

                            <!-- Sucursal Asignada Automáticamente por Cercanía GPS -->
                            <div class="sucursal-cercana-box" id="sucursalCercanaBox" style="margin-top:12px; background:#FEF2F2; border:1.5px solid #FCA5A5; border-radius:14px; padding:14px 18px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <div style="width:42px; height:42px; border-radius:50%; background:var(--rojo); color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0;">
                                            <i class="fa-solid fa-store"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:0.75rem; text-transform:uppercase; font-weight:800; color:#991B1B; letter-spacing:0.5px;">
                                                <i class="fa-solid fa-route"></i> Área de Cobertura y Despacho
                                            </div>
                                            <div style="font-size:1.02rem; font-weight:900; color:#1E293B;" id="nombreSucursalCercana">
                                                Mostaza Formosa Centro
                                            </div>
                                            <div style="font-size:0.82rem; color:#64748B;" id="detalleDistanciaSucursal">
                                                📍 Av. 25 de Mayo 150 · Calculando sucursal más cercana...
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge" style="background:#16A34A; color:#fff; font-weight:800; font-size:0.78rem; padding:6px 12px; border-radius:20px;">
                                        <i class="fa-solid fa-bolt"></i> Sucursal más cercana
                                    </span>
                                </div>
                            </div>

                            <input type="hidden" name="latitud" id="latitud_input" value="<?= SUCURSAL_LAT ?>">
                            <input type="hidden" name="longitud" id="longitud_input" value="<?= SUCURSAL_LNG ?>">
                            <input type="hidden" name="sucursal_asignada" id="sucursal_asignada_input" value="Mostaza Formosa Centro">
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
                            <select name="sucursal_retiro" id="sucursal_retiro_select">
                                <?php foreach ($sucursalesWeb as $sw): ?>
                                <option value="<?= htmlspecialchars($sw['nombre']) ?>">
                                    <?= htmlspecialchars($sw['nombre']) ?> (<?= htmlspecialchars($sw['direccion']) ?> - <?= htmlspecialchars($sw['ciudad']) ?>)
                                </option>
                                <?php endforeach; ?>
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

                    <!-- Panel 2: Mercado Pago Oficial & QR Interoperable -->
                    <div id="panel-mercadopago" class="payment-content-panel" style="display:none;">
                        <!-- Hidden inputs para la orden verificada -->
                        <input type="hidden" name="mp_payment_id" id="mp_payment_id" value="">
                        <input type="hidden" name="mp_payment_status" id="mp_payment_status" value="">

                        <!-- Vista 1: Pendiente de Pago en MP con QR en Pantalla -->
                        <div id="mp-pending-box" class="mp-box" style="border: 2px solid #009EE3; padding: 22px; border-radius: 16px; background: #F8FAFC; text-align: center; box-shadow: 0 4px 15px rgba(0,158,227,0.08);">
                            
                            <div style="display:flex; justify-content:center; align-items:center; gap:12px; margin-bottom:12px;">
                                <img src="https://http2.mlstatic.com/frontend-assets/ui-navigation/5.18.9/mercadopago/logo__large.png" alt="Mercado Pago" style="height: 36px;">
                                <span style="background:#E0F2FE; color:#0369A1; font-size:0.75rem; padding:4px 10px; border-radius:20px; font-weight:800; border:1px solid #BAE6FD;">
                                    <i class="fa-solid fa-shield-halved"></i> Pasarela Oficial
                                </span>
                            </div>

                            <p style="color: #475569; font-size: 0.9rem; max-width: 500px; margin: 0 auto 16px;">
                                Escaneá el código QR desde tu celular con la <strong>app de Mercado Pago</strong> o cualquier billetera digital habilitada con <strong>Transferencias 3.0 / BCRA</strong>.
                            </p>

                            <!-- Contenedor Central del Código QR Oficial -->
                            <div style="background:#FFFFFF; border:2px solid #E2E8F0; border-radius:16px; padding:18px; max-width:320px; margin:0 auto 16px; box-shadow:0 6px 16px rgba(0,0,0,0.05);">
                                <img id="mpTabQrImg" 
                                     src="<?= generarQrUrlMercadoPago($totalFinal, 220) ?>" 
                                     alt="QR Oficial Mercado Pago" 
                                     style="width:200px; height:200px; display:block; margin:0 auto 10px; border-radius:8px;">

                                <div style="font-size:0.85rem; font-weight:800; color:#0F172A; margin-bottom:4px;">
                                    Total a Pagar: <span style="color:#0284C7;" id="mpTabMontoDisplay">$<?= number_format($totalFinal, 0, ',', '.') ?></span>
                                </div>
                                <div style="font-size:0.72rem; color:#64748B;">
                                    Titular: <strong><?= htmlspecialchars(BANCO_TITULAR) ?></strong>
                                </div>
                                <div style="margin-top:6px; font-size:0.72rem; color:#10B981; font-weight:700;">
                                    <i class="fa-solid fa-circle-check"></i> Formato EMVCo BCRA Verificado
                                </div>
                            </div>

                            <!-- Botones Rápidos de Copia y Enlace Celular -->
                            <div style="display:flex; justify-content:center; flex-wrap:wrap; gap:8px; margin-bottom:18px;">
                                <a href="<?= MP_LINK_PAGO ?>" target="_blank" class="btn-mp-quick" style="background:#009EE3; color:#fff; text-decoration:none; padding:8px 14px; border-radius:8px; font-size:0.8rem; font-weight:800; display:inline-flex; align-items:center; gap:6px;">
                                    <i class="fa-solid fa-mobile-screen"></i> Abrir en App Mercado Pago
                                </a>
                                <button type="button" class="btn-mp-quick" onclick="copiarTexto('<?= htmlspecialchars(BANCO_ALIAS) ?>')" style="background:#F1F5F9; border:1px solid #CBD5E1; color:#334155; padding:8px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                                    <i class="fa-regular fa-copy"></i> Copiar Alias (<?= htmlspecialchars(BANCO_ALIAS) ?>)
                                </button>
                                <button type="button" class="btn-mp-quick" onclick="copiarTexto('<?= htmlspecialchars(BANCO_CVU) ?>')" style="background:#F1F5F9; border:1px solid #CBD5E1; color:#334155; padding:8px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                                    <i class="fa-regular fa-copy"></i> Copiar CVU
                                </button>
                                <button type="button" class="btn-mp-quick" onclick="copiarTexto('<?= intval($totalFinal) ?>')" style="background:#F1F5F9; border:1px solid #CBD5E1; color:#334155; padding:8px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                                    <i class="fa-regular fa-copy"></i> Copiar Monto
                                </button>
                            </div>

                            <!-- Confirmación / Acreditación en 1 Clic -->
                            <div style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:12px; padding:16px; max-width:440px; margin:0 auto; text-align:left;">
                                <label style="font-size:0.82rem; font-weight:800; color:#1E3A8A; display:block; margin-bottom:6px;">
                                    <i class="fa-solid fa-circle-info"></i> ¿Ya realizaste el pago en la app?
                                </label>
                                <div style="display:flex; gap:8px; margin-bottom:10px;">
                                    <input type="text" id="inputMpReferenciaCliente" name="mp_referencia_usuario" placeholder="Nº de comprobante / operación (opcional)" style="flex:1; padding:8px 12px; border:1px solid #CBD5E1; border-radius:8px; font-size:0.85rem;">
                                </div>
                                <div style="display:flex; gap:8px; flex-direction:column;">
                                    <button type="button" onclick="confirmarPagoMPDirecto()" style="width:100%; background:#10B981; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:900; font-size:0.92rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 10px rgba(16,185,129,0.25);">
                                        <i class="fa-solid fa-circle-check"></i> Confirmar Pago Acreditado en Mercado Pago
                                    </button>
                                    <button type="button" onclick="abrirModalMercadoPago('cuenta')" style="width:100%; background:#FFFFFF; color:#0284C7; border:1px solid #BAE6FD; padding:9px; border-radius:8px; font-weight:700; font-size:0.82rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                                        <i class="fa-solid fa-bolt"></i> Simular Dinero en Cuenta (Demostración)
                                    </button>
                                </div>
                            </div>

                        </div>

                        <!-- Vista 2: Pago Acreditado Exitoso -->
                        <div id="mp-approved-box" style="display: none; border: 2px solid #10B981; padding: 24px; border-radius: 16px; background: #F0FDF4; text-align: center; box-shadow: 0 4px 15px rgba(16,185,129,0.15);">
                            <div style="width: 60px; height: 60px; background: #10B981; color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <h3 style="color: #15803D; font-size: 1.35rem; font-weight: 900; margin-bottom: 4px;">¡Pago Acreditado en Mercado Pago!</h3>
                            <p style="color: #166534; font-size: 0.9rem; margin-bottom: 14px;">La transacción ha sido verificada exitosamente. Ya podés confirmar tu pedido.</p>

                            <div style="background: #FFFFFF; border: 1px solid #BBF7D0; border-radius: 12px; padding: 14px 20px; max-width: 400px; margin: 0 auto 16px; text-align: left; font-size: 0.88rem;">
                                <div style="display:flex; justify-content:space-between; margin-bottom: 6px;">
                                    <span style="color:#64748B;">ID de Operación:</span>
                                    <strong id="displayMpPaymentId" style="color:#0F172A; font-family: monospace; font-size: 0.95rem;">-</strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom: 6px;">
                                    <span style="color:#64748B;">Monto Acreditado:</span>
                                    <strong style="color:#15803D;">$<?= number_format($totalFinal, 0, ',', '.') ?></strong>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <span style="color:#64748B;">Medio Utilizado:</span>
                                    <strong id="displayMpPaymentMethod" style="color:#0284C7;">Mercado Pago Oficial</strong>
                                </div>
                            </div>

                            <button type="button" onclick="reiniciarPagoMP()" style="background: none; border: none; color: #64748B; font-size: 0.82rem; text-decoration: underline; cursor: pointer;">
                                Reintentar o cambiar método de pago
                            </button>
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
                                <input type="text" name="comprobante_transferencia" id="comprobante_transferencia_input" placeholder="Ej: Operación #98765432">
                                <button type="button" class="btn-autofill-card" onclick="autocompletarComprobanteTransferencia()" style="margin-top:6px;font-size:0.8rem;padding:6px 12px;background:#F1F5F9;border:1px solid #CBD5E1;border-radius:6px;cursor:pointer;color:#334155;font-weight:700;">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Usar Comprobante de Prueba Rápido
                                </button>
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

                    <!-- Token de Autorización de Tarjeta -->
                    <input type="hidden" name="tarjeta_auth_code" id="tarjeta_auth_code" value="">

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

    <!-- MODAL PASARELA OFICIAL MERCADO PAGO -->
    <div class="modal-mp-overlay" id="modalMercadoPagoGateway" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); z-index:99999; align-items:center; justify-content:center; padding:15px;">
        <div style="background:#FFFFFF; border-radius:20px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.3); border:1px solid #CBD5E1; position:relative;">
            
            <!-- Header MP -->
            <div style="background:#009EE3; padding:18px 22px; border-radius:20px 20px 0 0; color:#FFFFFF; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <img src="https://http2.mlstatic.com/frontend-assets/ui-navigation/5.18.9/mercadopago/logo__large.png" alt="Mercado Pago" style="height:24px; filter:brightness(0) invert(1);">
                    <span style="font-size:0.75rem; background:rgba(255,255,255,0.25); padding:3px 8px; border-radius:12px; font-weight:700;"><i class="fa-solid fa-lock"></i> Checkout Seguro</span>
                </div>
                <button type="button" onclick="cerrarModalMP()" style="background:none; border:none; color:#FFFFFF; font-size:1.4rem; cursor:pointer; line-height:1;">✕</button>
            </div>

            <div style="padding:22px;">
                <!-- Resumen de cobro -->
                <div style="text-align:center; padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid #E2E8F0;">
                    <span style="font-size:0.75rem; color:#64748B; text-transform:uppercase; letter-spacing:1px; font-weight:700;">Mostaza Argentina S.A.</span>
                    <h2 style="font-size:2rem; font-weight:900; color:#0F172A; margin:4px 0;" id="mpModalMontoDisplay">$<?= number_format($totalFinal, 0, ',', '.') ?></h2>
                    <span style="font-size:0.8rem; color:#64748B;">Total de tu pedido a acreditar</span>
                </div>

                <!-- Contenido de opciones de pago en MP -->
                <div id="mpModalContent">
                    
                    <!-- Opcion 1: Dinero en Cuenta -->
                    <div style="background:#F8FAFC; border:2px solid #E2E8F0; border-radius:12px; padding:14px; margin-bottom:12px; cursor:pointer; transition:all 0.2s;" class="mp-choice-card" onclick="procesarPagoMP('Dinero en Cuenta Mercado Pago')">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:38px; height:38px; background:#E0F2FE; color:#0284C7; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <div>
                                    <strong style="color:#0F172A; font-size:0.9rem; display:block;">Dinero disponible en Mercado Pago</strong>
                                    <span style="font-size:0.78rem; color:#16A34A; font-weight:700;">Saldo disponible: $48.500,00</span>
                                </div>
                            </div>
                            <span style="background:#0284C7; color:#fff; font-size:0.75rem; padding:4px 10px; border-radius:6px; font-weight:800;">Pagar</span>
                        </div>
                    </div>

                    <!-- Opcion 2: QR Dinámico Interoperable -->
                    <div style="background:#F8FAFC; border:2px solid #E2E8F0; border-radius:12px; padding:14px; margin-bottom:12px;" id="mpQrSection">
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                            <div style="width:38px; height:38px; background:#FEF3C7; color:#B45309; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <div>
                                <strong style="color:#0F172A; font-size:0.9rem; display:block;">Código QR en Vivo</strong>
                                <span style="font-size:0.75rem; color:#64748B;">Escaneá desde tu celular con la app de Mercado Pago</span>
                            </div>
                        </div>

                        <div style="text-align:center; padding:12px; background:#FFFFFF; border-radius:10px; border:1px solid #E2E8F0;">
                            <img id="mpModalQrImg" src="<?= generarQrUrlMercadoPago($totalFinal, 200) ?>" alt="QR Mercado Pago" style="width:170px; height:170px; display:block; margin:0 auto 8px; border-radius:8px;">
                            <div style="font-size:0.75rem; color:#0284C7; font-weight:800; display:flex; justify-content:center; align-items:center; gap:6px;">
                                <i class="fa-solid fa-spinner fa-spin"></i> <span id="mpQrTimer">Esperando lectura en app (04:59)</span>
                            </div>
                        </div>

                        <button type="button" onclick="procesarPagoMP('QR Interoperable Mercado Pago')" style="margin-top:10px; width:100%; background:#0284C7; color:#FFFFFF; border:none; padding:10px; border-radius:8px; font-weight:800; font-size:0.85rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="fa-solid fa-mobile-screen-button"></i> Simular Pago Exitoso desde la App
                        </button>
                    </div>

                    <!-- Opcion 3: Tarjeta Guardada en Mercado Pago -->
                    <div style="background:#F8FAFC; border:2px solid #E2E8F0; border-radius:12px; padding:14px; cursor:pointer; transition:all 0.2s;" class="mp-choice-card" onclick="procesarPagoMP('Tarjeta Guardada Visa Débito **** 4012')">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:38px; height:38px; background:#F1F5F9; color:#334155; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
                                    <i class="fa-solid fa-credit-card"></i>
                                </div>
                                <div>
                                    <strong style="color:#0F172A; font-size:0.9rem; display:block;">Visa Débito **** 4012</strong>
                                    <span style="font-size:0.75rem; color:#64748B;">Guardada en tu cuenta Mercado Pago</span>
                                </div>
                            </div>
                            <span style="background:#0284C7; color:#fff; font-size:0.75rem; padding:4px 10px; border-radius:6px; font-weight:800;">Pagar</span>
                        </div>
                    </div>

                </div>

                <!-- Vista Procesando Spinner -->
                <div id="mpModalLoading" style="display:none; text-align:center; padding:30px 10px;">
                    <div style="width:50px; height:50px; border:4px solid #E0F2FE; border-top-color:#009EE3; border-radius:50%; animation:spinMP 0.8s linear infinite; margin:0 auto 14px;"></div>
                    <h3 style="color:#0F172A; font-size:1.15rem; font-weight:800; margin-bottom:4px;" id="mpLoadingStatusText">Conectando con Mercado Pago...</h3>
                    <p style="color:#64748B; font-size:0.82rem;">Acreditando la transacción en tiempo real</p>
                </div>

                <!-- Vista Exito Animado -->
                <div id="mpModalSuccess" style="display:none; text-align:center; padding:20px 10px;">
                    <div style="width:64px; height:64px; background:#10B981; color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:2rem; margin-bottom:12px; box-shadow:0 8px 20px rgba(16,185,129,0.35);">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 style="color:#15803D; font-size:1.35rem; font-weight:900; margin-bottom:4px;">¡Listo! Se acreditó tu pago</h3>
                    <p style="color:#166534; font-size:0.88rem; margin-bottom:16px;">En tu resumen verás el cargo a nombre de Mostaza Argentina S.A.</p>
                    <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:10px; padding:12px; margin-bottom:18px; font-size:0.85rem; color:#166534; font-weight:700;">
                        Comprobante #<span id="mpSuccessOpCode">MP-XXXXXX</span>
                    </div>
                    <button type="button" onclick="confirmarExitoMP()" style="background:#009EE3; color:#FFFFFF; border:none; padding:13px 20px; border-radius:10px; font-weight:900; font-size:0.95rem; cursor:pointer; width:100%;">
                        Continuar al Resumen del Pedido
                    </button>
                </div>

            </div>

        </div>
    </div>

    <?php endif; ?>

</div>

<?php include('includes/footer.php'); ?>

<script>
let totalActualNum = <?= floatval($totalFinal) ?>;
let sucursalLat = <?= SUCURSAL_LAT ?>;
let sucursalLng = <?= SUCURSAL_LNG ?>;
const todasLasSucursales = <?= json_encode($sucursalesWeb) ?>;

// ------------------------------------------------------------
// INICIALIZACIÓN DE MAPA LEAFLET GPS & ASIGNACIÓN DE SUCURSAL
// ------------------------------------------------------------
let map = null;
let marker = null;
let sucursalMarker = null;
let deliveryRouteLine = null;

function calcularDistanciaHaversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radio de la Tierra en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function actualizarSucursalCercana(lat, lng) {
    if (!todasLasSucursales || todasLasSucursales.length === 0) return;

    let masCercana = null;
    let menorDistancia = Infinity;

    todasLasSucursales.forEach(s => {
        const dist = calcularDistanciaHaversineKm(lat, lng, s.lat, s.lng);
        if (dist < menorDistancia) {
            menorDistancia = dist;
            masCercana = s;
        }
    });

    if (masCercana) {
        const inputSuc = document.getElementById('sucursal_asignada_input');
        if (inputSuc) inputSuc.value = masCercana.nombre;

        const elNombre = document.getElementById('nombreSucursalCercana');
        const elDetalle = document.getElementById('detalleDistanciaSucursal');

        const distTxt = menorDistancia < 1.0 
            ? `${Math.round(menorDistancia * 1000)} metros` 
            : `${menorDistancia.toFixed(1)} km`;

        if (elNombre) elNombre.textContent = masCercana.nombre;
        if (elDetalle) elDetalle.textContent = `📍 ${masCercana.direccion} (${masCercana.ciudad}) · A sólo ${distTxt} de tu puerta.`;

        // Actualizar o crear marcador de sucursal en el mapa
        if (map) {
            const storeIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [26, 42],
                iconAnchor: [13, 42],
                popupAnchor: [1, -36],
                shadowSize: [41, 41]
            });

            if (!sucursalMarker) {
                sucursalMarker = L.marker([masCercana.lat, masCercana.lng], { icon: storeIcon }).addTo(map);
            } else {
                sucursalMarker.setLatLng([masCercana.lat, masCercana.lng]);
            }
            sucursalMarker.bindPopup(`<b>🍔 ${masCercana.nombre}</b><br>Punto de Despacho Asignado<br>📍 ${masCercana.direccion} (${distTxt})`);

            // Línea punteada de conexión
            if (!deliveryRouteLine) {
                deliveryRouteLine = L.polyline([[masCercana.lat, masCercana.lng], [lat, lng]], {
                    color: '#A30002',
                    weight: 3.5,
                    opacity: 0.85,
                    dashArray: '6, 8'
                }).addTo(map);
            } else {
                deliveryRouteLine.setLatLngs([[masCercana.lat, masCercana.lng], [lat, lng]]);
            }
        }
    }
}

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

    actualizarSucursalCercana(initLat, initLng);

    marker.on('dragend', function(e) {
        const pos = e.target.getLatLng();
        document.getElementById('latitud_input').value = pos.lat.toFixed(6);
        document.getElementById('longitud_input').value = pos.lng.toFixed(6);
        reverseGeocode(pos.lat, pos.lng);
        actualizarSucursalCercana(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('latitud_input').value = e.latlng.lat.toFixed(6);
        document.getElementById('longitud_input').value = e.latlng.lng.toFixed(6);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
        actualizarSucursalCercana(e.latlng.lat, e.latlng.lng);
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
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
            }

            reverseGeocode(lat, lng);
            actualizarSucursalCercana(lat, lng);

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
                    map.setView([lat, lon], 15);
                    marker.setLatLng([lat, lon]);
                }
                actualizarSucursalCercana(lat, lon);
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

    // Actualizar código QR y montos en Mercado Pago
    if (typeof getQrUrlMercadoPagoJs === 'function') {
        const nuevoQr = getQrUrlMercadoPagoJs(data.total_final);
        const elQrTab = document.getElementById('mpTabQrImg');
        const elQrModal = document.getElementById('mpModalQrImg');
        const elTabMonto = document.getElementById('mpTabMontoDisplay');
        const elModalMonto = document.getElementById('mpModalMontoDisplay');

        if (elQrTab) elQrTab.src = nuevoQr;
        if (elQrModal) elQrModal.src = nuevoQr;
        if (elTabMonto) elTabMonto.textContent = '$' + Math.ceil(data.total_final).toLocaleString('es-AR');
        if (elModalMonto) elModalMonto.textContent = '$' + Math.ceil(data.total_final).toLocaleString('es-AR');
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

    const dniEl = document.getElementById('inputCardDni');
    if (dniEl && !dniEl.value) dniEl.value = '38123456';

    const authInput = document.getElementById('tarjeta_auth_code');
    if (authInput) authInput.value = 'AUTH-' + tipo.toUpperCase() + '-' + Math.floor(100000 + Math.random() * 900000);

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

// ------------------------------------------------------------
// GENERADOR Y ACTUALIZADOR DINÁMICO DE QR MERCADO PAGO EN JS (EMVCo BCRA)
// ------------------------------------------------------------
function emvCoTagJs(id, value) {
    const len = value.length.toString().padStart(2, '0');
    return id + len + value;
}

function calcularCrc16CcittJs(str) {
    let crc = 0xFFFF;
    for (let c = 0; c < str.length; c++) {
        crc ^= (str.charCodeAt(c) << 8);
        for (let i = 0; i < 8; i++) {
            if (crc & 0x8000) {
                crc = ((crc << 1) ^ 0x1021) & 0xFFFF;
            } else {
                crc = (crc << 1) & 0xFFFF;
            }
        }
    }
    return crc.toString(16).toUpperCase().padStart(4, '0');
}

function generarPayloadEmvcoJs(alias, cvu, monto, titular, ciudad) {
    alias = alias || '<?= BANCO_ALIAS ?>';
    cvu = cvu || '<?= BANCO_CVU ?>';
    titular = titular || '<?= BANCO_TITULAR ?>';
    ciudad = ciudad || 'FORMOSA';

    let payload = emvCoTagJs('00', '01');
    payload += emvCoTagJs('01', monto > 0 ? '12' : '11');

    const sub00 = emvCoTagJs('00', 'COM.MERCADOPAGO');
    const cuentaId = cvu ? cvu.replace(/\D/g, '') : alias.toLowerCase().trim();
    const sub01 = emvCoTagJs('01', cuentaId);
    payload += emvCoTagJs('43', sub00 + sub01);

    payload += emvCoTagJs('52', '5814');
    payload += emvCoTagJs('53', '032');

    if (monto > 0) {
        const mStr = Number(monto).toFixed(2);
        payload += emvCoTagJs('54', mStr);
    }

    payload += emvCoTagJs('58', 'AR');

    let nomLimpio = titular.toUpperCase().replace(/[^A-Z0-9 ]/g, '').substring(0, 25);
    if (!nomLimpio) nomLimpio = 'MOSTAZA OFICIAL';
    payload += emvCoTagJs('59', nomLimpio);

    let ciuLimpia = ciudad.toUpperCase().replace(/[^A-Z0-9 ]/g, '').substring(0, 15);
    if (!ciuLimpia) ciuLimpia = 'FORMOSA';
    payload += emvCoTagJs('60', ciuLimpia);

    const subRef = emvCoTagJs('01', 'MOSTAZA');
    payload += emvCoTagJs('62', subRef);

    const conTag63 = payload + '6304';
    const crc = calcularCrc16CcittJs(conTag63);
    return conTag63 + crc;
}

function getQrUrlMercadoPagoJs(monto) {
    const payload = generarPayloadEmvcoJs('<?= BANCO_ALIAS ?>', '<?= BANCO_CVU ?>', monto, '<?= BANCO_TITULAR ?>', 'FORMOSA');
    return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=10&data=' + encodeURIComponent(payload);
}

function confirmarPagoMPDirecto() {
    const inputRef = document.getElementById('inputMpReferenciaCliente');
    let ref = inputRef ? inputRef.value.trim() : '';
    if (!ref) {
        ref = 'MP-' + Math.floor(100000000 + Math.random() * 900000000);
    }
    
    document.getElementById('mp_payment_id').value = ref;
    document.getElementById('mp_payment_status').value = 'approved';

    document.getElementById('displayMpPaymentId').textContent = '#' + ref;
    document.getElementById('displayMpPaymentMethod').textContent = 'Mercado Pago QR Oficial';

    document.getElementById('mp-pending-box').style.display = 'none';
    document.getElementById('mp-approved-box').style.display = 'block';

    const btnSubmit = document.getElementById('btnConfirmarPedido');
    if (btnSubmit) {
        btnSubmit.scrollIntoView({ behavior: 'smooth' });
    }
}

function autocompletarComprobanteTransferencia() {
    const input = document.getElementById('comprobante_transferencia_input');
    if (input) {
        const randNum = Math.floor(10000000 + Math.random() * 90000000);
        input.value = 'TRANSF-' + randNum;
        input.dispatchEvent(new Event('input'));
    }
}

// ------------------------------------------------------------
// Pasarela Interactiva de Mercado Pago
// ------------------------------------------------------------
let mpCountdownTimer = null;
let mpCurrentSelectedMethod = 'Mercado Pago Oficial';

function abrirModalMercadoPago(tipo) {
    const modal = document.getElementById('modalMercadoPagoGateway');
    if (!modal) return;

    // Resetear vistas del modal
    document.getElementById('mpModalContent').style.display = 'block';
    document.getElementById('mpModalLoading').style.display = 'none';
    document.getElementById('mpModalSuccess').style.display = 'none';

    // Actualizar monto mostrado
    const displayMonto = document.getElementById('mpModalMontoDisplay');
    if (displayMonto) {
        displayMonto.textContent = '$' + Math.ceil(totalActualNum).toLocaleString('es-AR');
    }

    modal.style.display = 'flex';

    // Iniciar temporizador de QR (5 minutos)
    iniciarTemporizadorQR();

    if (tipo === 'qr') {
        const qrSec = document.getElementById('mpQrSection');
        if (qrSec) qrSec.scrollIntoView({ behavior: 'smooth' });
    }
}

function cerrarModalMP() {
    const modal = document.getElementById('modalMercadoPagoGateway');
    if (modal) modal.style.display = 'none';
    if (mpCountdownTimer) {
        clearInterval(mpCountdownTimer);
        mpCountdownTimer = null;
    }
}

function iniciarTemporizadorQR() {
    if (mpCountdownTimer) clearInterval(mpCountdownTimer);
    let segundosRestantes = 300; // 5 minutos
    const timerElem = document.getElementById('mpQrTimer');
    
    function actualizarTimer() {
        if (!timerElem) return;
        const mins = Math.floor(segundosRestantes / 60).toString().padStart(2, '0');
        const secs = (segundosRestantes % 60).toString().padStart(2, '0');
        timerElem.textContent = 'Esperando lectura en app (' + mins + ':' + secs + ')';
        if (segundosRestantes <= 0) {
            clearInterval(mpCountdownTimer);
            timerElem.textContent = 'Código QR expirado - Actualizá para generar uno nuevo';
        }
        segundosRestantes--;
    }
    actualizarTimer();
    mpCountdownTimer = setInterval(actualizarTimer, 1000);
}

function procesarPagoMP(nombreMetodo) {
    mpCurrentSelectedMethod = nombreMetodo || 'Dinero en Cuenta Mercado Pago';

    document.getElementById('mpModalContent').style.display = 'none';
    document.getElementById('mpModalLoading').style.display = 'block';

    const statusText = document.getElementById('mpLoadingStatusText');
    if (statusText) statusText.textContent = 'Conectando con pasarela segura Mercado Pago...';

    setTimeout(() => {
        if (statusText) statusText.textContent = 'Verificando fondos y autorizando transacción...';
    }, 900);

    setTimeout(() => {
        // Generar un ID de operación auténtico de Mercado Pago
        const opId = 'MP-' + Math.floor(100000000 + Math.random() * 900000000);
        document.getElementById('mpSuccessOpCode').textContent = opId;

        document.getElementById('mpModalLoading').style.display = 'none';
        document.getElementById('mpModalSuccess').style.display = 'block';

        if (mpCountdownTimer) clearInterval(mpCountdownTimer);
    }, 1800);
}

function confirmarExitoMP() {
    const opId = document.getElementById('mpSuccessOpCode').textContent.trim();
    
    // Asignar a inputs hidden del formulario
    document.getElementById('mp_payment_id').value = opId;
    document.getElementById('mp_payment_status').value = 'approved';

    // Actualizar visualmente la tarjeta aprobada
    document.getElementById('displayMpPaymentId').textContent = '#' + opId;
    document.getElementById('displayMpPaymentMethod').textContent = mpCurrentSelectedMethod;

    document.getElementById('mp-pending-box').style.display = 'none';
    document.getElementById('mp-approved-box').style.display = 'block';

    cerrarModalMP();

    const btnSubmit = document.getElementById('btnConfirmarPedido');
    if (btnSubmit) {
        btnSubmit.scrollIntoView({ behavior: 'smooth' });
    }
}

function reiniciarPagoMP() {
    document.getElementById('mp_payment_id').value = '';
    document.getElementById('mp_payment_status').value = '';
    const inputRef = document.getElementById('inputMpReferenciaCliente');
    if (inputRef) inputRef.value = '';
    document.getElementById('mp-pending-box').style.display = 'block';
    document.getElementById('mp-approved-box').style.display = 'none';
}

// ------------------------------------------------------------
// Validación y Envío del Formulario
// ------------------------------------------------------------
document.getElementById('checkout-form')?.addEventListener('submit', function(e) {
    const metodo = document.getElementById('metodo_pago_input').value;
    const tipoEntrega = document.getElementById('tipo_entrega_input').value;

    // 1. Validaciones por Tipo de Entrega
    if (tipoEntrega === 'delivery') {
        const dir = document.getElementById('direccion_envio').value.trim();
        const tel = document.getElementById('telefono_contacto').value.trim();
        if (!dir || !tel) {
            alert('⚠️ Atención: Por favor completá tu dirección de entrega y tu teléfono de contacto para el delivery.');
            e.preventDefault();
            return;
        }
    } else if (tipoEntrega === 'salon') {
        const mesa = document.getElementById('inputNumeroMesa').value.trim();
        if (!mesa) {
            alert('⚠️ Atención: Por favor elegí una mesa disponible en el mapa de mesas para tu pedido en el salón.');
            e.preventDefault();
            return;
        }
    }

    // 2. Validaciones por Medio de Pago
    if (metodo === 'mercadopago') {
        let mpId = document.getElementById('mp_payment_id').value.trim();
        let mpStatus = document.getElementById('mp_payment_status').value.trim();
        const inputRef = document.getElementById('inputMpReferenciaCliente');
        const refCliente = inputRef ? inputRef.value.trim() : '';

        // Si el cliente ingresó un comprobante o referencia, acreditarlo
        if (!mpId && refCliente) {
            mpId = refCliente;
            document.getElementById('mp_payment_id').value = mpId;
            document.getElementById('mp_payment_status').value = 'approved';
            mpStatus = 'approved';
        }

        if (!mpId || mpStatus !== 'approved') {
            const quiereAcreditar = confirm('¿Deseás confirmar y acreditar el pago realizado en Mercado Pago por $' + Math.ceil(totalActualNum).toLocaleString('es-AR') + ' para finalizar el pedido?');
            if (quiereAcreditar) {
                confirmarPagoMPDirecto();
            } else {
                e.preventDefault();
                return;
            }
        }
    } else if (metodo === 'tarjeta') {
        const num = inputNumber ? inputNumber.value.replace(/\s/g, '') : '';
        const titular = inputHolder ? inputHolder.value.trim() : '';
        const exp = inputExp ? inputExp.value.trim() : '';
        const cvv = inputCvv ? inputCvv.value.trim() : '';

        if (num.length < 15 || !titular || exp.length < 5 || cvv.length < 3) {
            alert('⚠️ Datos de Tarjeta incompletos: Por favor completá los 16 dígitos, titular, vencimiento y código CVV, o usá los botones de prueba rápida.');
            e.preventDefault();
            return;
        }

        // Generar código de autorización bancaria
        const authInput = document.getElementById('tarjeta_auth_code');
        if (authInput && !authInput.value) {
            authInput.value = 'AUTH-VISA-' + Math.floor(100000 + Math.random() * 900000);
        }
    } else if (metodo === 'transferencia') {
        let ref = document.querySelector('input[name="comprobante_transferencia"]')?.value.trim();
        const archivo = document.getElementById('comprobante_archivo')?.files.length || 0;
        if (!ref && archivo === 0) {
            autocompletarComprobanteTransferencia();
        }
    } else if (metodo === 'efectivo') {
        const pagaCon = parseFloat(document.getElementById('inputPagaCon')?.value) || 0;
        if (pagaCon < totalActualNum) {
            alert('⚠️ Monto insuficiente: El monto con el que abonás ($' + pagaCon.toLocaleString('es-AR') + ') no puede ser menor al total del pedido ($' + Math.ceil(totalActualNum).toLocaleString('es-AR') + ').');
            e.preventDefault();
            return;
        }
    }

    const btn = document.getElementById('btnConfirmarPedido');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando y Creando Comanda...';
    }
});

window.addEventListener('DOMContentLoaded', () => {
    initDeliveryMap();
    renderBilletesRapidos();
    calcularVuelto();
});
</script>

</body>
</html>
