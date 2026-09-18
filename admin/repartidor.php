<?php
// ============================================================
// admin/repartidor.php
// Panel de Repartidores y Gestión de Delivery
// ============================================================
session_start();
require('conexion.php');

// Procesar cambio de estado de entrega
if (isset($_POST['accion_delivery'])) {
    $idpedido = intval($_POST['idpedido']);
    $accion = $_POST['accion_delivery'];
    $repartidor = htmlspecialchars(trim($_POST['repartidor'] ?? 'Repartidor Mostaza'));

    // Obtener ID de empleado del repartidor oficial
    $resR = $conn->query("SELECT idempleado FROM empleado WHERE fk_id_rol = 6 AND (CONCAT(nombre, ' ', apellido) = '" . $conn->real_escape_string($repartidor) . "' OR nombre = '" . $conn->real_escape_string($repartidor) . "') LIMIT 1");
    $idEmpleadoR = ($resR && $rowR = $resR->fetch_assoc()) ? intval($rowR['idempleado']) : null;

    if ($accion === 'en_camino') {
        registrarAuditoriaPedido($conn, $idpedido, 'En Camino', 'Despacho y Salida a Domicilio', 'Repartidor', $repartidor, $idEmpleadoR, "Asignado a $repartidor para entrega en domicilio");
    } elseif ($accion === 'entregado') {
        registrarAuditoriaPedido($conn, $idpedido, 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', $repartidor, $idEmpleadoR, "Pedido entregado con conformidad del cliente");
        if ($idEmpleadoR) {
            $conn->query("UPDATE empleado SET viajes_totales = viajes_totales + 1 WHERE idempleado = $idEmpleadoR");
        }
    }

    header("Location: repartidor.php");
    exit();
}

// Consultar pedidos de delivery activos (con filtro opcional de sucursal / área)
$sucursalFiltro = trim($_GET['sucursal'] ?? '');
$sqlDeliv = "SELECT p.*, u.nombre, u.apellido, u.telefono as user_tel 
             FROM pedido_web p 
             LEFT JOIN usuario_web u ON p.idusuario = u.idusuario 
             WHERE p.tipo_entrega = 'delivery' AND p.estado IN ('Listo', 'Listo para Despacho', 'En Preparacion', 'En Preparación', 'En Cocina', 'En Camino')";

if (!empty($sucursalFiltro) && $sucursalFiltro !== 'todas') {
    $sqlDeliv .= " AND p.sucursal = '" . $conn->real_escape_string($sucursalFiltro) . "'";
}
$sqlDeliv .= " ORDER BY p.idpedido ASC";
$pedidosDelivery = $conn->query($sqlDeliv);

// Sucursales activas para el filtro de área
$sucursalesFiltroRes = $conn->query("SELECT DISTINCT nombre FROM sucursal WHERE activo = 1 ORDER BY idsucursal ASC");

// Flota oficial de repartidores Mostaza
$flotaOficial = [];
$resFlota = $conn->query("SELECT idrepartidor, nombre, vehiculo, patente, avatar FROM repartidor WHERE activo = 1 ORDER BY idrepartidor ASC");
if ($resFlota) {
    while ($rf = $resFlota->fetch_assoc()) {
        $flotaOficial[] = $rf;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Repartidores · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="img/logo.png" alt="Mostaza">
        <div>
            <h2>MOSTAZA POS</h2>
            <span>EPET Nº 7 · Sistema Web</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="pos.php"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="mesero.php"><i class="fa-solid fa-utensils"></i> Salón (Mesero)</a></li>
        <li><a href="cocina.php"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php" class="active"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>

<main class="admin-main">

    <div class="admin-topbar" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
        <div>
            <h1>Panel de Despacho y Repartidores</h1>
            <p>Rutas de entrega, cobros en efectivo contra entrega y confirmación de recepción en tiempo real.</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="display:flex;align-items:center;gap:6px;background:#fff;padding:6px 12px;border-radius:10px;border:1px solid #CBD5E1;">
                <i class="fa-solid fa-filter" style="color:var(--admin-rojo);"></i>
                <label style="font-size:0.85rem;font-weight:700;color:#334155;margin:0;">Área / Sucursal:</label>
                <select onchange="location.href='repartidor.php?sucursal='+encodeURIComponent(this.value)" style="border:none;outline:none;font-weight:700;color:#1E293B;font-family:'Montserrat',sans-serif;background:transparent;cursor:pointer;">
                    <option value="todas">-- Todas las Sucursales --</option>
                    <?php if ($sucursalesFiltroRes): ?>
                        <?php while ($sf = $sucursalesFiltroRes->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($sf['nombre']) ?>" <?= $sucursalFiltro === $sf['nombre'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sf['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <button onclick="window.location.reload()" class="btn-admin btn-admin-white">
                <i class="fa-solid fa-arrows-rotate"></i> Actualizar
            </button>
        </div>
    </div>

    <?php if (!$pedidosDelivery || $pedidosDelivery->num_rows === 0): ?>
        <div class="admin-card" style="text-align:center;padding:70px 20px;">
            <div style="font-size:3.5rem;color:#CBD5E1;margin-bottom:12px;">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
            <h2 style="color:var(--admin-dark);margin-bottom:6px;font-weight:900;">No hay pedidos de delivery listos para despachar</h2>
            <p style="color:#64748B;font-size:0.9rem;">Cuando la cocina marque los pedidos como listos, se listarán automáticamente aquí para salir a reparto.</p>
        </div>
    <?php else: ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(360px, 1fr));gap:20px;">
            <?php while ($d = $pedidosDelivery->fetch_assoc()): ?>
            <?php
            $stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
            $stmtDet->bind_param("i", $d['idpedido']);
            $stmtDet->execute();
            $items = $stmtDet->get_result();
            ?>
            <div class="admin-card" style="border-top:4px solid <?= $d['estado'] === 'En Camino' ? '#8B5CF6' : '#10B981' ?>;display:flex;flex-direction:column;justify-content:space-between;">
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <strong style="font-size:1.2rem;color:var(--admin-rojo);font-weight:900;">Pedido #<?= $d['idpedido'] ?></strong>
                        <span class="badge <?= $d['estado'] === 'En Camino' ? 'badge-info' : 'badge-success' ?>">
                            <?= htmlspecialchars($d['estado']) ?>
                        </span>
                    </div>

                    <div style="background:#F8FAFC;padding:14px;border-radius:10px;margin-bottom:14px;font-size:0.88rem;border:1px solid #E2E8F0;">
                        <p style="margin-bottom:6px;"><strong><i class="fa-solid fa-user" style="color:var(--admin-dark);"></i> Cliente:</strong> <?= htmlspecialchars($d['nombre'] ? $d['nombre'] . ' ' . $d['apellido'] : 'Cliente Web') ?></p>
                        <p style="margin-bottom:6px; color:#991B1B; font-weight:700;">
                            <strong><i class="fa-solid fa-store" style="color:var(--admin-rojo);"></i> Despacho desde:</strong> <?= htmlspecialchars($d['sucursal'] ?: 'Mostaza Formosa Centro') ?>
                        </p>
                        <p style="margin-bottom:6px;"><strong><i class="fa-solid fa-location-dot" style="color:#EF4444;"></i> Dirección:</strong> <?= htmlspecialchars($d['direccion_envio'] ?? 'Formosa Centro') ?></p>
                        <?php if (!empty($d['referencia_entrega'])): ?>
                            <p style="margin-bottom:6px;color:#92400E;background:#FEF3C7;padding:4px 8px;border-radius:6px;font-weight:700;font-size:0.8rem;">
                                <i class="fa-solid fa-bell"></i> Referencia: <?= htmlspecialchars($d['referencia_entrega']) ?>
                            </p>
                        <?php endif; ?>
                        <p style="margin-bottom:6px;"><strong><i class="fa-solid fa-phone" style="color:#2563EB;"></i> Teléfono:</strong> 
                            <a href="tel:<?= urlencode($d['telefono_contacto'] ?: ($d['user_tel'] ?? '')) ?>" style="color:#2563EB;text-decoration:none;font-weight:700;">
                                <?= htmlspecialchars($d['telefono_contacto'] ?: ($d['user_tel'] ?? 'Sin teléfono')) ?>
                            </a>
                        </p>
                        <?php if (!empty($d['notas'])): ?>
                            <p style="color:#D97706;margin-top:6px;font-weight:600;"><i class="fa-solid fa-comment-dots"></i> <?= htmlspecialchars($d['notas']) ?></p>
                        <?php endif; ?>

                        <!-- Botones de Navegación GPS -->
                        <?php
                        $gMapsUrl = (!empty($d['latitud']) && !empty($d['longitud']))
                            ? "https://www.google.com/maps/dir/?api=1&destination=" . $d['latitud'] . "," . $d['longitud']
                            : "https://www.google.com/maps/search/?api=1&query=" . urlencode($d['direccion_envio'] ?? 'Formosa');
                        ?>
                        <div style="display:flex;gap:8px;margin-top:12px;">
                            <a href="<?= $gMapsUrl ?>" target="_blank" class="btn-admin btn-admin-sm" style="flex:1;background:#2563EB;color:#fff;justify-content:center;">
                                <i class="fa-solid fa-location-arrow"></i> Google Maps
                            </a>
                        </div>
                    </div>

                    <!-- Detalle de Cobro -->
                    <div style="background:<?= ($d['metodo_pago'] === 'efectivo') ? '#FEF2F2' : '#F0FDF4' ?>;padding:12px;border-radius:10px;margin-bottom:14px;font-size:0.88rem;border:1px solid <?= ($d['metodo_pago'] === 'efectivo') ? '#FECACA' : '#BBF7D0' ?>;">
                        <div style="display:flex;justify-content:space-between;">
                            <span>Forma de Pago:</span>
                            <strong style="text-transform:uppercase;color:var(--admin-dark);"><?= htmlspecialchars($d['metodo_pago']) ?></strong>
                        </div>
                        <?php if ($d['metodo_pago'] === 'efectivo'): ?>
                            <div style="display:flex;justify-content:space-between;color:#DC2626;font-weight:900;margin-top:4px;font-size:1.05rem;">
                                <span>COBRAR EN DESTINO:</span>
                                <span>$<?= number_format($d['total'], 0, ',', '.') ?></span>
                            </div>
                            <?php if ($d['paga_con']): ?>
                                <div style="font-size:0.8rem;color:#64748B;margin-top:4px;">
                                    Cliente abona con $<?= number_format($d['paga_con'], 0, ',', '.') ?> · Vuelto: <strong>$<?= number_format($d['vuelto'], 0, ',', '.') ?></strong>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color:#166534;font-weight:800;margin-top:4px;">
                                <i class="fa-solid fa-check"></i> YA PAGADO ONLINE
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones del Repartidor -->
                <div>
                    <?php if ($d['estado'] !== 'En Camino'): ?>
                        <form method="post" action="repartidor.php">
                            <input type="hidden" name="idpedido" value="<?= $d['idpedido'] ?>">
                            <input type="hidden" name="accion_delivery" value="en_camino">
                            <div style="margin-bottom:8px;">
                                <select name="repartidor" required class="form-control" style="font-size:0.85rem;padding:8px 12px;font-weight:700;">
                                    <option value="">-- Asignar Repartidor Oficial --</option>
                                    <?php foreach ($flotaOficial as $ro): ?>
                                        <option value="<?= htmlspecialchars($ro['nombre']) ?>" <?= ($d['repartidor_asignado'] ?? '') === $ro['nombre'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ro['avatar'] ?: '🛵') ?> <?= htmlspecialchars($ro['nombre']) ?> (<?= htmlspecialchars($ro['patente']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn-admin btn-admin-red" style="width:100%;justify-content:center;padding:10px;">
                                <i class="fa-solid fa-motorcycle"></i> Salir en Camino
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="post" action="repartidor.php">
                            <input type="hidden" name="idpedido" value="<?= $d['idpedido'] ?>">
                            <input type="hidden" name="accion_delivery" value="entregado">
                            <button type="submit" class="btn-admin" style="width:100%;justify-content:center;padding:10px;background:#16A34A;color:#fff;">
                                <i class="fa-solid fa-circle-check"></i> Marcar como Entregado (<?= htmlspecialchars($d['repartidor_asignado'] ?? 'Repartidor') ?>)
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>

</main>

</body>
</html>
