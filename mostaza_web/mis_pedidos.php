<?php
// ============================================================
// mis_pedidos.php
// Historial de compras del cliente con seguimiento y repetición de pedidos.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'pedidos';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = 'mis_pedidos.php';
    header("Location: login.php");
    exit();
}

$idusuario = $_SESSION['usuario_id'];

// Obtener todos los pedidos del usuario ordenados por fecha descendente
$stmt = $conn->prepare("SELECT * FROM pedido_web WHERE idusuario = ? ORDER BY idpedido DESC");
$stmt->bind_param("i", $idusuario);
$stmt->execute();
$pedidos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis Pedidos · Mostaza</title>
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
</head>
<body class="checkout-page">

<?php include('includes/header.php'); ?>

<div class="checkout-container" style="max-width: 1000px;">

    <div class="checkout-header-title">
        <h1>Historial de Mis Pedidos</h1>
        <p>Consultá el estado de tus compras anteriores, descargá tus comprobantes o volvé a pedir con un clic.</p>
    </div>

    <?php if ($pedidos->num_rows === 0): ?>
        <div class="card-section" style="text-align:center;padding:60px 20px;">
            <div style="font-size:3.5rem;color:#D1D5DB;margin-bottom:14px;">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <h2 style="color:var(--dark);margin-bottom:8px;">Todavía no realizaste ningún pedido</h2>
            <p style="color:#6B7280;margin-bottom:24px;">¡Hacé tu primer pedido ahora y disfrutá las mejores hamburguesas de Mostaza!</p>
            <a href="productos.php" class="btn-submit-order" style="display:inline-flex;width:auto;text-decoration:none;padding:12px 30px;">
                <i class="fa-solid fa-burger"></i> Ver Productos
            </a>
        </div>
    <?php else: ?>

        <div style="display:flex;flex-direction:column;gap:20px;">
            <?php while ($p = $pedidos->fetch_assoc()): ?>
                <?php
                // Obtener items de este pedido
                $stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
                $stmtDet->bind_param("i", $p['idpedido']);
                $stmtDet->execute();
                $det = $stmtDet->get_result();

                $badgeClass = 'badge-recibido';
                if (in_array($p['estado'], ['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'])) {
                    $badgeClass = 'badge-cocina';
                } elseif (in_array($p['estado'], ['Listo', 'Listo para Despacho', 'En Camino'])) {
                    $badgeClass = 'badge-camino';
                } elseif ($p['estado'] === 'Entregado') {
                    $badgeClass = 'badge-entregado';
                } elseif ($p['estado'] === 'Cancelado') {
                    $badgeClass = 'badge-cancelado';
                }
                ?>
                <div class="card-section" style="margin-bottom:0;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;border-bottom:1px solid #eee;padding-bottom:14px;margin-bottom:14px;">
                        <div>
                            <span style="font-size:0.85rem;color:#6B7280;display:block;">Mi Pedido N.º</span>
                            <h3 style="font-size:1.35rem;font-weight:900;color:var(--rojo);margin:0;">#<?= $p['numero_pedido_usuario'] ?: $p['idpedido'] ?></h3>
                            <span style="font-size:0.8rem;color:#9CA3AF;"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($p['fecha'])) ?> · <?= strtoupper($p['tipo_entrega']) ?> · <small style="color:#64748B;">Comanda Global #<?= $p['idpedido'] ?></small></span>
                        </div>
                        <div style="text-align:right;">
                            <span class="order-status-badge <?= $badgeClass ?>"><?= htmlspecialchars($p['estado']) ?></span>
                            <div style="font-size:1.3rem;font-weight:900;color:var(--dark);margin-top:6px;">$<?= number_format($p['total'], 0, ',', '.') ?></div>
                        </div>
                    </div>

                    <!-- Items del pedido -->
                    <div style="margin:12px 0;">
                        <?php while ($d = $det->fetch_assoc()): ?>
                            <div style="display:flex;justify-content:space-between;font-size:0.9rem;padding:4px 0;color:#374151;">
                                <span>
                                    <strong><?= $d['cantidad'] ?>x</strong> <?= htmlspecialchars($d['descripcion']) ?>
                                    <?php if (!empty($d['modificadores'])): ?>
                                        <span style="font-size:0.78rem;color:var(--rojo);"> (<?= htmlspecialchars($d['modificadores']) ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <strong>$<?= number_format($d['subtotal'], 0, ',', '.') ?></strong>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Botones de Acción -->
                    <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;border-top:1px solid #eee;padding-top:14px;margin-top:14px;">
                        <a href="pedido_confirmado.php?id=<?= $p['idpedido'] ?>" class="btn-submit-order" style="width:auto;padding:8px 18px;font-size:0.85rem;text-decoration:none;background:#4B5563;">
                            <i class="fa-solid fa-satellite-dish"></i> Ver Seguimiento
                        </a>
                        <a href="ticket_pdf.php?id=<?= $p['idpedido'] ?>" target="_blank" class="btn-submit-order" style="width:auto;padding:8px 18px;font-size:0.85rem;text-decoration:none;background:var(--dark);">
                            <i class="fa-solid fa-file-invoice"></i> Factura PDF
                        </a>
                        <a href="reordenar.php?id=<?= $p['idpedido'] ?>" class="btn-submit-order" style="width:auto;padding:8px 20px;font-size:0.85rem;text-decoration:none;background:var(--rojo);">
                            <i class="fa-solid fa-rotate-right"></i> Volver a Pedir
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>

</div>

<?php include('includes/footer.php'); ?>

</body>
</html>
