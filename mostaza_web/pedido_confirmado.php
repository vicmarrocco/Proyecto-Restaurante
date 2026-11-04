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
$estado = trim($pedido['estado'] ?? 'Recibido');
$tipoEntrega = $pedido['tipo_entrega'] ?? 'delivery';
$repartidor = trim($pedido['repartidor_asignado'] ?? '');
$mesa = $pedido['numero_mesa'];

$stepIndex = 1;
$porcentajeFill = '15%';

if ($estado === 'Recibido') {
    $stepIndex = 1;
    $porcentajeFill = '15%';
    $statusTitle = '¡Tu pedido fue recibido!';
    $statusDesc = 'El local ya tiene tu orden y está a punto de comenzar a prepararla en cocina.';
} elseif (in_array($estado, ['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'])) {
    $stepIndex = 2;
    $porcentajeFill = '48%';
    $statusTitle = '¡Marchando en Cocina!';
    $statusDesc = 'Nuestros parrilleros están preparando tus hamburguesas con la mejor calidad Mostaza.';
} elseif (in_array($estado, ['Listo', 'Listo para Despacho'])) {
    if ($tipoEntrega === 'delivery') {
        $stepIndex = 2;
        $porcentajeFill = '60%';
        $statusTitle = '¡Listo en Cocina! Esperando salida del repartidor';
        $statusDesc = 'Tu pedido ya salió de la parrilla y está empaquetado para que el repartidor inicie el viaje.';
    } else {
        $stepIndex = 3;
        $porcentajeFill = '78%';
        if ($tipoEntrega === 'salon') {
            $statusTitle = '¡Tu comanda está lista!';
            $statusDesc = !empty($mesa) ? "Tu pedido está listo y servido en la Mesa #$mesa." : "Tu pedido está listo para retirar en el salón.";
        } else {
            $statusTitle = '¡Tu pedido está listo para retirar!';
            $statusDesc = 'Podés acercarte al mostrador con tu número de comanda para retirarlo.';
        }
    }
} elseif ($estado === 'En Camino') {
    $stepIndex = 3;
    $porcentajeFill = '78%';
    $statusTitle = '¡El repartidor va en camino!';
    $nomRep = !empty($repartidor) ? "El repartidor <strong>" . htmlspecialchars($repartidor) . "</strong>" : "El repartidor";
    $statusDesc = "$nomRep salió del local y se dirige a tu domicilio.";
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
.tracking-line-track {
    position: absolute;
    top: 24px;
    left: 40px;
    right: 40px;
    height: 6px;
    background: #E5E7EB;
    z-index: 1;
    border-radius: 3px;
    overflow: hidden;
}
.tracking-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #16A34A, #22C55E);
    transition: width 0.6s ease;
    border-radius: 3px;
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
            <div class="tracking-line-track">
                <div class="tracking-progress-fill" id="trackingProgressFill" style="width: <?= $porcentajeFill ?>;"></div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 1) ? (($stepIndex == 1 && $stepIndex < 4) ? 'active' : 'completed') : '' ?>" id="stepTrack1">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex > 1) ? 'fa-check' : 'fa-receipt' ?>"></i>
                </div>
                <div class="step-label">Recibido</div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 2) ? (($stepIndex == 2 && $stepIndex < 4) ? 'active' : 'completed') : '' ?>" id="stepTrack2">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex > 2) ? 'fa-check' : 'fa-fire-burner' ?>"></i>
                </div>
                <div class="step-label">En Cocina</div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 3) ? (($stepIndex == 3 && $stepIndex < 4) ? 'active' : 'completed') : '' ?>" id="stepTrack3">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex > 3) ? 'fa-check' : (($pedido['tipo_entrega'] === 'delivery') ? 'fa-motorcycle' : 'fa-bag-shopping') ?>"></i>
                </div>
                <div class="step-label" id="stepTrackLabel3"><?= ($pedido['tipo_entrega'] === 'delivery') ? 'En Camino' : 'Listo' ?></div>
            </div>

            <div class="tracking-step <?= ($stepIndex >= 4) ? 'completed' : '' ?>" id="stepTrack4">
                <div class="step-icon-circle">
                    <i class="fa-solid <?= ($stepIndex >= 4) ? 'fa-check' : 'fa-house-chimney' ?>"></i>
                </div>
                <div class="step-label">Entregado</div>
            </div>
        </div>

        <div class="tracking-live-box" id="trackingLiveBox">
            <i class="fa-solid fa-bell" id="trackingLiveIcon"></i>
            <div>
                <strong style="color:#92400E;display:block;font-size:1.05rem;" id="trackingStatusTitle"><?= $statusTitle ?></strong>
                <span style="color:#B45309;font-size:0.9rem;" id="trackingStatusDesc"><?= $statusDesc ?></span>
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
                        <div style="margin-top:6px;color:#991B1B;font-weight:700;">
                            <i class="fa-solid fa-store"></i> Sucursal asignada por cercanía: <span style="color:#111827;"><?= htmlspecialchars($pedido['sucursal'] ?: 'Mostaza Formosa Centro') ?></span>
                        </div>
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
<?php
// Buscar coordenadas y dirección real de la sucursal asignada por cercanía
$sucursalNombre = !empty($pedido['sucursal']) ? $pedido['sucursal'] : 'Mostaza Formosa Centro';
$stmtSucInfo = $conn->prepare("SELECT latitud, longitud, direccion, ciudad FROM sucursal WHERE nombre = ? LIMIT 1");
$stmtSucInfo->bind_param("s", $sucursalNombre);
$stmtSucInfo->execute();
$infoSuc = $stmtSucInfo->get_result()->fetch_assoc();

$sucursalLat = !empty($infoSuc['latitud']) ? floatval($infoSuc['latitud']) : SUCURSAL_LAT;
$sucursalLng = !empty($infoSuc['longitud']) ? floatval($infoSuc['longitud']) : SUCURSAL_LNG;
$sucursalDir = !empty($infoSuc['direccion']) ? $infoSuc['direccion'] : 'Av. 25 de Mayo 150';
?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const sucursalLat = <?= $sucursalLat ?>;
    const sucursalLng = <?= $sucursalLng ?>;
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

    const m1 = L.marker([sucursalLat, sucursalLng], { icon: storeIcon }).addTo(map).bindPopup("<b>🍔 <?= addslashes($sucursalNombre) ?></b><br>Punto de Despacho Asignado (<?= addslashes($sucursalDir) ?>)");
    const m2 = L.marker([clienteLat, clienteLng], { icon: clientIcon }).addTo(map).bindPopup("<b>📍 Tu Domicilio</b><br><?= addslashes($pedido['direccion_envio']) ?>").openPopup();

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

<script>
// ------------------------------------------------------------
// Polling en Vivo del Estado del Pedido (Sincronizado con Cocina y Repartidor)
// ------------------------------------------------------------
let currentOrderStep = <?= $stepIndex ?>;
let orderPollingInterval = null;

function consultarEstadoPedidoEnVivo() {
    const idpedido = <?= $pedido['idpedido'] ?>;
    fetch('tracking_estado_ajax.php?id=' + idpedido)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;

            const paso = data.paso;
            const porcentaje = data.porcentajeFill;
            const titulo = data.titulo;
            const desc = data.descripcion;
            const tipoEntrega = data.tipo_entrega;
            const estadoActual = data.estado;

            // Actualizar textos informativos
            const elTitle = document.getElementById('trackingStatusTitle');
            const elDesc = document.getElementById('trackingStatusDesc');
            const elLiveBox = document.getElementById('trackingLiveBox');
            const elLiveIcon = document.getElementById('trackingLiveIcon');

            if (elTitle) elTitle.innerHTML = titulo;
            if (elDesc) elDesc.innerHTML = desc;

            // Actualizar estilo si está entregado o cancelado
            if (data.es_cancelado) {
                if (elLiveBox) {
                    elLiveBox.style.background = '#FEF2F2';
                    elLiveBox.style.borderColor = '#FCA5A5';
                }
                if (elTitle) elTitle.style.color = '#991B1B';
                if (elDesc) elDesc.style.color = '#B91C1C';
                if (elLiveIcon) {
                    elLiveIcon.className = 'fa-solid fa-triangle-exclamation';
                    elLiveIcon.style.color = '#DC2626';
                }
            } else if (estadoActual === 'Entregado') {
                if (elLiveBox) {
                    elLiveBox.style.background = '#F0FDF4';
                    elLiveBox.style.borderColor = '#BBF7D0';
                }
                if (elTitle) elTitle.style.color = '#15803D';
                if (elDesc) elDesc.style.color = '#166534';
                if (elLiveIcon) {
                    elLiveIcon.className = 'fa-solid fa-circle-check';
                    elLiveIcon.style.color = '#16A34A';
                }
            }

            // Actualizar barra de progreso
            const elFill = document.getElementById('trackingProgressFill');
            if (elFill) elFill.style.width = porcentaje;

            // Actualizar steps visuales
            [1, 2, 3, 4].forEach(i => {
                const elStep = document.getElementById('stepTrack' + i);
                if (!elStep) return;

                const circle = elStep.querySelector('.step-icon-circle');
                const icon = circle ? circle.querySelector('i') : null;

                elStep.classList.remove('active', 'completed');

                if (paso >= 4 || estadoActual === 'Entregado') {
                    // Cuando el pedido está entregado, TODOS los pasos quedan completados con el mismo ícono verde
                    elStep.classList.add('completed');
                    if (icon) icon.className = 'fa-solid fa-check';
                } else if (paso > i) {
                    elStep.classList.add('completed');
                    if (icon) icon.className = 'fa-solid fa-check';
                } else if (paso === i) {
                    elStep.classList.add('active');
                    if (icon) {
                        if (i === 1) icon.className = 'fa-solid fa-receipt';
                        else if (i === 2) icon.className = 'fa-solid fa-fire-burner';
                        else if (i === 3) icon.className = (tipoEntrega === 'delivery') ? 'fa-solid fa-motorcycle' : 'fa-solid fa-bag-shopping';
                        else if (i === 4) icon.className = 'fa-solid fa-house-chimney';
                    }
                } else {
                    if (icon) {
                        if (i === 1) icon.className = 'fa-solid fa-receipt';
                        else if (i === 2) icon.className = 'fa-solid fa-fire-burner';
                        else if (i === 3) icon.className = (tipoEntrega === 'delivery') ? 'fa-solid fa-motorcycle' : 'fa-solid fa-bag-shopping';
                        else if (i === 4) icon.className = 'fa-solid fa-house-chimney';
                    }
                }
            });

            // Si fue entregado o cancelado, detener polling para ahorrar recursos
            if (estadoActual === 'Entregado' || data.es_cancelado) {
                if (orderPollingInterval) clearInterval(orderPollingInterval);
            }
        })
        .catch(err => console.log('Polling tracking error:', err));
}

// Iniciar consulta cada 3 segundos
orderPollingInterval = setInterval(consultarEstadoPedidoEnVivo, 3000);
</script>

</body>
</html>
