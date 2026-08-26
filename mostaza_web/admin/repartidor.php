<?php
// ============================================================
// admin/repartidor.php
// Panel de Repartidores y Gestión de Delivery (EPET Nº 7).
// ============================================================
session_start();
require('../conexion.php');

// Procesar cambio de estado de entrega
if (isset($_POST['accion_delivery'])) {
    $idpedido = intval($_POST['idpedido']);
    $accion = $_POST['accion_delivery'];
    $repartidor = htmlspecialchars(trim($_POST['repartidor'] ?? 'Repartidor Mostaza'));

    if ($accion === 'en_camino') {
        $stmt = $conn->prepare("UPDATE pedido_web SET estado = 'En Camino', repartidor_asignado = ? WHERE idpedido = ?");
        $stmt->bind_param("si", $repartidor, $idpedido);
        $stmt->execute();
    } elseif ($accion === 'entregado') {
        $stmt = $conn->prepare("UPDATE pedido_web SET estado = 'Entregado' WHERE idpedido = ?");
        $stmt->bind_param("i", $idpedido);
        $stmt->execute();
    }

    header("Location: repartidor.php");
    exit();
}

// Consultar pedidos de delivery activos
$pedidosDelivery = $conn->query(
    "SELECT p.*, u.nombre, u.apellido, u.telefono as user_tel 
     FROM pedido_web p 
     LEFT JOIN usuario_web u ON p.idusuario = u.idusuario 
     WHERE p.tipo_entrega = 'delivery' AND p.estado IN ('Listo', 'En Camino') 
     ORDER BY p.idpedido ASC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>App Repartidor · Delivery Mostaza</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="admin-body">

<!-- Sidebar -->
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <img src="../img/image-removebg-preview.png" alt="Mostaza">
        <div>
            <h2>MOSTAZA POS</h2>
            <span>EPET Nº 7 · Sistema Web</span>
        </div>
    </div>

    <ul class="sidebar-nav">
        <li><a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="pos.php"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="cocina.php"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php" class="active"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../index.php"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Panel de Despacho y Repartidores</h1>
            <p>Rutas de entrega, cobros en efectivo contra entrega y confirmación de recepción.</p>
        </div>
        <button onclick="window.location.reload()" style="background:#fff;border:1px solid #E5E7EB;padding:8px 16px;border-radius:50px;font-weight:700;cursor:pointer;">
            <i class="fa-solid fa-arrows-rotate"></i> Actualizar
        </button>
    </div>

    <?php if ($pedidosDelivery->num_rows === 0): ?>
        <div class="admin-card" style="text-align:center;padding:70px 20px;">
            <div style="font-size:3.5rem;color:#D1D5DB;margin-bottom:12px;">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
            <h2 style="color:var(--admin-dark);margin-bottom:6px;">No hay pedidos de delivery listos para despachar</h2>
            <p style="color:#64748B;">Cuando la cocina marque los pedidos como listos, se listarán aquí para salir a reparto.</p>
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
            <div class="admin-card" style="border-top:4px solid <?= $d['estado'] === 'En Camino' ? '#8B5CF6' : '#10B981' ?>;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <strong style="font-size:1.2rem;color:var(--admin-rojo);">Pedido #<?= $d['idpedido'] ?></strong>
                    <span class="order-status-badge <?= $d['estado'] === 'En Camino' ? 'badge-camino' : 'badge-entregado' ?>">
                        <?= htmlspecialchars($d['estado']) ?>
                    </span>
                </div>

                <div style="background:#F8FAFC;padding:14px;border-radius:10px;margin-bottom:14px;font-size:0.9rem;">
                    <p style="margin-bottom:6px;"><strong><i class="fa-solid fa-user"></i> Cliente:</strong> <?= htmlspecialchars($d['nombre'] . ' ' . $d['apellido']) ?></p>
                    <p style="margin-bottom:6px;"><strong><i class="fa-solid fa-location-dot" style="color:#EF4444;"></i> Dirección:</strong> <?= htmlspecialchars($d['direccion_envio']) ?></p>
                    <?php if (!empty($d['referencia_entrega'])): ?>
                        <p style="margin-bottom:6px;color:#92400E;background:#FEF3C7;padding:4px 8px;border-radius:6px;font-weight:600;">
                            <i class="fa-solid fa-bell"></i> Piso / Depto: <?= htmlspecialchars($d['referencia_entrega']) ?>
                        </p>
                    <?php endif; ?>
                    <p style="margin-bottom:6px;"><strong><i class="fa-solid fa-phone"></i> Teléfono:</strong> 
                        <a href="tel:<?= urlencode($d['telefono_contacto'] ?: $d['user_tel']) ?>" style="color:#2563EB;text-decoration:none;font-weight:700;">
                            <?= htmlspecialchars($d['telefono_contacto'] ?: $d['user_tel']) ?>
                        </a>
                    </p>
                    <?php if (!empty($d['notas'])): ?>
                        <p style="color:#D97706;margin-top:6px;font-weight:600;"><i class="fa-solid fa-comment-dots"></i> <?= htmlspecialchars($d['notas']) ?></p>
                    <?php endif; ?>

                    <!-- Botones de Navegación GPS -->
                    <?php
                    $gMapsUrl = (!empty($d['latitud']) && !empty($d['longitud']))
                        ? "https://www.google.com/maps/dir/?api=1&destination=" . $d['latitud'] . "," . $d['longitud']
                        : "https://www.google.com/maps/search/?api=1&query=" . urlencode($d['direccion_envio']);
                    $wazeUrl = (!empty($d['latitud']) && !empty($d['longitud']))
                        ? "https://waze.com/ul?ll=" . $d['latitud'] . "," . $d['longitud'] . "&navigate=yes"
                        : "https://waze.com/ul?q=" . urlencode($d['direccion_envio']);
                    ?>
                    <div style="display:flex;gap:8px;margin-top:10px;">
                        <a href="<?= $gMapsUrl ?>" target="_blank" style="flex:1;text-align:center;background:#2563EB;color:#fff;padding:8px 10px;border-radius:8px;text-decoration:none;font-weight:700;font-size:0.8rem;display:inline-block;">
                            <i class="fa-solid fa-location-arrow"></i> Google Maps
                        </a>
                        <a href="<?= $wazeUrl ?>" target="_blank" style="flex:1;text-align:center;background:#00D1C1;color:#0F172A;padding:8px 10px;border-radius:8px;text-decoration:none;font-weight:700;font-size:0.8rem;display:inline-block;">
                            <i class="fa-solid fa-car"></i> Waze
                        </a>
                    </div>
                </div>

                <!-- Detalle de Cobro -->
                <div style="background:<?= ($d['metodo_pago'] === 'efectivo') ? '#FEF2F2' : '#F0FDF4' ?>;padding:12px;border-radius:10px;margin-bottom:14px;font-size:0.9rem;">
                    <div style="display:flex;justify-content:space-between;">
                        <span>Forma de Pago:</span>
                        <strong style="text-transform:uppercase;"><?= htmlspecialchars($d['metodo_pago']) ?></strong>
                    </div>
                    <?php if ($d['metodo_pago'] === 'efectivo'): ?>
                        <div style="display:flex;justify-content:space-between;color:#DC2626;font-weight:800;margin-top:4px;">
                            <span>COBRAR EN DESTINO:</span>
                            <span>$<?= number_format($d['total'], 0, ',', '.') ?></span>
                        </div>
                        <?php if ($d['paga_con']): ?>
                            <div style="font-size:0.8rem;color:#666;margin-top:2px;">
                                Cliente paga con $<?= number_format($d['paga_con'], 0, ',', '.') ?> · Llevar <strong>$<?= number_format($d['vuelto'], 0, ',', '.') ?></strong> de vuelto.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color:#16A34A;font-weight:800;margin-top:4px;">
                            <i class="fa-solid fa-check"></i> YA PAGADO ONLINE
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones del Repartidor -->
                <?php if ($d['estado'] === 'Listo'): ?>
                    <form method="post" action="repartidor.php">
                        <input type="hidden" name="idpedido" value="<?= $d['idpedido'] ?>">
                        <input type="hidden" name="accion_delivery" value="en_camino">
                        <div style="margin-bottom:10px;">
                            <input type="text" name="repartidor" placeholder="Nombre de Repartidor (ej: Marcos)" required 
                                   style="width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:0.85rem;">
                        </div>
                        <button type="submit" class="btn-submit-order" style="width:100%;padding:10px;background:#8B5CF6;font-size:0.9rem;">
                            <i class="fa-solid fa-motorcycle"></i> Salir en Camino
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="repartidor.php">
                        <input type="hidden" name="idpedido" value="<?= $d['idpedido'] ?>">
                        <input type="hidden" name="accion_delivery" value="entregado">
                        <button type="submit" class="btn-submit-order" style="width:100%;padding:10px;background:#16A34A;font-size:0.9rem;">
                            <i class="fa-solid fa-circle-check"></i> Marcar como Entregado
                        </button>
                    </form>
                <?php endif; ?>

            </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>

</main>

</body>
</html>
