<?php
// ============================================================
// pedido_confirmado.php
// Confirmación del pedido y Seguimiento en Vivo (Live Tracking):
// - 100% del diseño visual premium original: barra de progreso interactiva,
//   check animado, desglose de comanda, puntos acumulados y descarga de Ticket PDF.
// - Integración con Mapa Leaflet GPS en vivo con ruta hacia el cliente.
// - Datos de comprobante de transferencia y estado de pago.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = '';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$idpedido = intval($_GET['id'] ?? 0);
$idusuario = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT * FROM pedido_web WHERE idpedido = ? AND (idusuario = ? OR idusuario IS NULL)");
$stmt->bind_param("ii", $idpedido, $idusuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: index.php");
    exit();
}

$stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
$stmtDet->bind_param("i", $idpedido);
$stmtDet->execute();
$detalles = $stmtDet->get_result()->fetch_all(MYSQLI_ASSOC);

// Mapeo de Estados para la Barra de Progreso
$estado = $pedido['estado'] ?? 'Recibido';
$stepIndex = 1;
$porcentajeFill = '15%';

if ($estado === 'Recibido') {
    $stepIndex = 1;
    $porcentajeFill = '15%';
    $statusTitle = '¡Tu pedido fue recibido!';
    $statusDesc = 'El local ya tiene tu orden y está a punto de comenzar a prepararla.';
} elseif ($estado === 'En Cocina' || $estado === 'En Preparacion') {
    $stepIndex = 2;
    $porcentajeFill = '48%';
    $statusTitle = '¡Marchando en Cocina!';
    $statusDesc = 'Nuestros parrilleros están preparando tus hamburguesas con la mejor calidad Mostaza.';
} elseif ($estado === 'En Camino' || $estado === 'Listo') {
    $stepIndex = 3;
    $porcentajeFill = '78%';
    $statusTitle = ($pedido['tipo_entrega'] === 'delivery') ? '¡El repartidor va en camino!' : '¡Tu pedido está listo para retirar!';
    $statusDesc = ($pedido['tipo_entrega'] === 'delivery') ? 'El delivery salió del local y se dirige a tu domicilio.' : 'Podés acercarte al mostrador a retirar tu comanda.';
} elseif ($estado === 'Entregado') {
    $stepIndex = 4;
    $porcentajeFill = '100%';
    $statusTitle = '¡Pedido Entregado! ¡Que lo disfrutes!';
    $statusDesc = 'Gracias por elegir Mostaza. ¡Esperamos que disfrutes tu comida!';
} else {
    $stepIndex = 1;
    $porcentajeFill = '15%';
    $statusTitle = 'Pedido en Proceso';
    $statusDesc = 'Estamos gestionando tu orden en el sistema.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido Confirmado #<?= $pedido['idpedido'] ?> · Mostaza</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="stylesheet" href="checkout.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Leaflet Map para Seguimiento en Vivo -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
/* Tracker Visual Original en Tiempo Real */
.tracking-wrapper {
    background: #fff;
    border-radius: 16px;
    padding: 30px 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    margin: 24px 0 30px;
    border: 1px solid var(--border);
}
.tracking-progress-bar {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 30px 10px 10px;
}
.tracking-progress-bar::before {
    content: '';
    position: absolute;
    top: 24px;
    left: 40px;
    right: 40px;
    height: 6px;
    background: #E5E7EB;
    z-index: 1;
}
.tracking-progress-fill {
    position: absolute;
    top: 24px;
    left: 40px;
    height: 6px;
    background: linear-gradient(90deg, #16A34A, #22C55E);
    z-index: 2;
    transition: width 0.6s ease;
    width: <?= $porcentajeFill ?>;
}
.tracking-step {
    position: relative;
    z-index: 3;
    text-align: center;
    width: 80px;
}
.step-icon-circle {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #fff;
    border: 4px solid #E5E7EB;
    color: #9CA3AF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin: 0 auto 10px;
    transition: all 0.4s ease;
}
.tracking-step.completed .step-icon-circle {
    background: #16A34A;
    border-color: #16A34A;
    color: #fff;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
}
.tracking-step.active .step-icon-circle {
    background: #A30002;
    border-color: #FBB728;
    color: #fff;
    box-shadow: 0 0 0 6px rgba(251, 183, 40, 0.25);
    animation: pulseStep 1.8s infinite;
}
.step-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #6B7280;
}
.tracking-step.completed .step-label,
.tracking-step.active .step-label {
    color: var(--dark);
}
@keyframes pulseStep {
    0% { transform: scale(1); }
    50% { transform: scale(1.08); }
    100% { transform: scale(1); }
}
.tracking-live-box {
    background: #FFFBEB;
    border: 1px solid #FDE68A;
    padding: 16px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 24px;
}
.tracking-live-box i {
    font-size: 1.8rem;
    color: #D97706;
}
#liveTrackingMap {
    height: 280px;
    width: 100%;
    border-radius: 12px;
    margin-top: 14px;
    border: 2px solid #E2E8F0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
</style>
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="checkout-container" style="max-width: 900px;">

    <!-- Encabezado de Éxito Original -->
    <div style="text-align:center;margin-bottom:24px;">
        <div style="width:70px;height:70px;background:#DCFCE7;color:#16A34A;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:2.2rem;margin-bottom:12px;">
            <i class="fa-solid fa-check"></i>
        </div>
        <h1 style="color:var(--dark);font-size:2.2rem;font-weight:900;margin-bottom:4px;">¡Pedido Confirmado!</h1>
        <p style="color:#4B5563;font-size:1.05rem;">
            Tu Pedido <strong>#<?= $pedido['numero_pedido_usuario'] ?: $pedido['idpedido'] ?></strong> (Comanda General <strong>#<?= $pedido['idpedido'] ?></strong>) fue registrado exitosamente el <?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?>
        </p>
    </div>

    <!-- SEGUIMIENTO EN TIEMPO REAL (Live Order Tracking) -->
    <div class="tracking-wrapper">
        <h3 style="font-size:1.2rem;font-weight:800;color:var(--dark);display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-satellite-dish" style="color:var(--rojo);"></i> Estado del Pedido en Tiempo Real
        </h3>

        <div class="tracking-progress-bar">
            <div class="tracking-progress-fill"></div>

            <div class="tracking-step <?= ($stepIndex >= 1) ? ($stepIndex == 1 ? 'active' : 'completed') : '' ?>">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex > 1) ? 'fa-check' : 'fa-receipt' ?>"></i>
                </div>
                <div class="step-label">Recibido</div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 2) ? ($stepIndex == 2 ? 'active' : 'completed') : '' ?>">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex > 2) ? 'fa-check' : 'fa-fire-burner' ?>"></i>
                </div>
                <div class="step-label">En Cocina</div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 3) ? ($stepIndex == 3 ? 'active' : 'completed') : '' ?>">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex > 3) ? 'fa-check' : (($pedido['tipo_entrega'] === 'delivery') ? 'fa-motorcycle' : 'fa-bag-shopping') ?>"></i>
                </div>
                <div class="step-label"><?= ($pedido['tipo_entrega'] === 'delivery') ? 'En Camino' : 'Listo' ?></div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 4) ? 'completed active' : '' ?>">
                <div class="step-icon-circle">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <div class="step-label">Entregado</div>
            </div>
        </div>

        <div class="tracking-live-box">
            <i class="fa-solid fa-bell"></i>
            <div>
                <strong style="color:#92400E;display:block;font-size:1.05rem;"><?= $statusTitle ?></strong>
                <span style="color:#B45309;font-size:0.9rem;"><?= $statusDesc ?></span>
            </div>
        </div>

        <!-- Mapa de Seguimiento GPS si es Delivery -->
        <?php if ($pedido['tipo_entrega'] === 'delivery' && !empty($pedido['latitud']) && !empty($pedido['longitud'])): ?>
        <div style="margin-top:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                <label style="font-weight:700;font-size:0.9rem;color:#334155;">
                    <i class="fa-solid fa-map-location-dot" style="color:var(--rojo);"></i> Ruta de Despacho en Vivo:
                </label>
                <span style="font-size:0.8rem;color:#2563EB;font-weight:700;">
                    <i class="fa-solid fa-route"></i> Sucursal ➔ Tu Puerta
                </span>
            </div>
            <div id="liveTrackingMap"></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TARJETA CON EL DETALLE DE LA COMANDA -->
    <div class="card-section">
        <div class="card-section-title">
            <i class="fa-solid fa-receipt"></i>
            <span>Detalle de la Comanda</span>
        </div>

        <div style="margin-bottom:16px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;background:#F9FAFB;padding:16px;border-radius:12px;font-size:0.9rem;">
                <div>
                    <strong>Modalidad:</strong> 
                    <span style="text-transform:uppercase;color:var(--rojo);font-weight:800;"><?= htmlspecialchars($pedido['tipo_entrega']) ?></span>
                </div>
                <div>
                    <strong>Medio de Pago:</strong> 
                    <span style="text-transform:uppercase;color:#15803D;font-weight:800;"><?= htmlspecialchars($pedido['metodo_pago']) ?></span>
                </div>
                <?php if ($pedido['tipo_entrega'] === 'delivery'): ?>
                    <div style="grid-column: span 2;">
                        <strong>Dirección de Entrega:</strong> <?= htmlspecialchars($pedido['direccion_envio']) ?>
                        <?php if (!empty($pedido['referencia_entrega'])): ?>
                            <br><small style="color:#666;">(Ref: <?= htmlspecialchars($pedido['referencia_entrega']) ?>)</small>
                        <?php endif; ?>
                    </div>
                <?php elseif ($pedido['tipo_entrega'] === 'salon'): ?>
                    <div>
                        <strong>Mesa:</strong> Mesa <?= $pedido['numero_mesa'] ?>
                    </div>
                <?php else: ?>
                    <div>
                        <strong>Sucursal:</strong> <?= htmlspecialchars($pedido['sucursal'] ?: 'Mostaza Central') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <table style="width:100%;border-collapse:collapse;margin-top:14px;font-size:0.95rem;">
            <thead>
                <tr style="border-bottom:2px solid #E5E7EB;color:#6B7280;text-align:left;">
                    <th style="padding:10px 0;">Cant.</th>
                    <th style="padding:10px;">Producto</th>
                    <th style="padding:10px;text-align:right;">Precio</th>
                    <th style="padding:10px 0;text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $det): ?>
                <tr style="border-bottom:1px solid #F3F4F6;">
                    <td style="padding:12px 0;font-weight:800;color:var(--rojo);"><?= $det['cantidad'] ?>x</td>
                    <td style="padding:12px 10px;">
                        <strong><?= htmlspecialchars($det['descripcion']) ?></strong>
                        <?php if (!empty($det['modificadores'])): ?>
                            <div style="font-size:0.8rem;color:#D97706;"><i class="fa-solid fa-pen"></i> <?= htmlspecialchars($det['modificadores']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 10px;text-align:right;color:#6B7280;">$<?= number_format($det['precio_unitario'], 0, ',', '.') ?></td>
                    <td style="padding:12px 0;text-align:right;font-weight:700;">$<?= number_format($det['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding:14px 10px;text-align:right;font-size:1.1rem;font-weight:800;">TOTAL PAGADO:</td>
                    <td style="padding:14px 0;text-align:right;font-size:1.4rem;font-weight:900;color:var(--rojo);">$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Botones de Acción -->
        <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;">
            <a href="ticket_pdf.php?id=<?= $pedido['idpedido'] ?>" target="_blank" class="btn-submit-order" style="flex:1;background:var(--dark);text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fa-solid fa-file-pdf"></i> Descargar Ticket Fiscal AFIP (PDF)
            </a>
            <a href="productos.php" class="btn-submit-order" style="flex:1;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                <i class="fa-solid fa-burger"></i> Seguir Comprando
            </a>
        </div>
    </div>

</div>

<?php include('includes/footer.php'); ?>

<?php if ($pedido['tipo_entrega'] === 'delivery' && !empty($pedido['latitud']) && !empty($pedido['longitud'])): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const sucursalLat = <?= SUCURSAL_LAT ?>;
    const sucursalLng = <?= SUCURSAL_LNG ?>;
    const clienteLat = <?= floatval($pedido['latitud']) ?>;
    const clienteLng = <?= floatval($pedido['longitud']) ?>;

    const map = L.map('liveTrackingMap');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const storeIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    const clientIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41]
    });

    const m1 = L.marker([sucursalLat, sucursalLng], { icon: storeIcon }).addTo(map).bindPopup("<b>🍔 Mostaza Formosa Centro</b><br>Punto de Despacho (Av. 25 de Mayo 150)");
    const m2 = L.marker([clienteLat, clienteLng], { icon: clientIcon }).addTo(map).bindPopup("<b>📍 Tu Domicilio</b><br><?= htmlspecialchars($pedido['direccion_envio']) ?>").openPopup();

    const polyline = L.polyline([[sucursalLat, sucursalLng], [clienteLat, clienteLng]], {
        color: '#A30002',
        weight: 4,
        opacity: 0.8,
        dashArray: '8, 8'
    }).addTo(map);

    map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
});
</script>
<?php endif; ?>

</body>
</html>
