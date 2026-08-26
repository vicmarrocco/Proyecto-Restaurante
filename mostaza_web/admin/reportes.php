<?php
// ============================================================
// admin/reportes.php
// Reportes Detallados de Ventas y Rendimiento (EPET Nº 7).
// ============================================================
session_start();
require('../conexion.php');

$fechaDesde = $_GET['desde'] ?? date('Y-m-01');
$fechaHasta = $_GET['hasta'] ?? date('Y-m-d');

// Consulta de ventas por producto
$stmtRanking = $conn->prepare(
    "SELECT d.descripcion, SUM(d.cantidad) as total_vendido, SUM(d.subtotal) as recaudacion
     FROM detalle_pedido_web d
     JOIN pedido_web p ON d.idpedido = p.idpedido
     WHERE DATE(p.fecha) BETWEEN ? AND ?
     GROUP BY d.descripcion
     ORDER BY total_vendido DESC
     LIMIT 10"
);
$stmtRanking->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtRanking->execute();
$ranking = $stmtRanking->get_result();

// Métricas globales del período
$stmtGlobal = $conn->prepare(
    "SELECT COUNT(*) as cant_pedidos, IFNULL(SUM(total), 0) as total_ventas, IFNULL(AVG(total), 0) as ticket_promedio
     FROM pedido_web
     WHERE DATE(fecha) BETWEEN ? AND ?"
);
$stmtGlobal->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtGlobal->execute();
$global = $stmtGlobal->get_result()->fetch_assoc();

// Ventas por modalidad
$stmtModalidad = $conn->prepare(
    "SELECT tipo_entrega, COUNT(*) as cant, SUM(total) as monto
     FROM pedido_web
     WHERE DATE(fecha) BETWEEN ? AND ?
     GROUP BY tipo_entrega"
);
$stmtModalidad->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtModalidad->execute();
$modalidades = $stmtModalidad->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes de Ventas · Mostaza</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
@media print {
    .admin-sidebar, .admin-topbar form, .btn-print-hide { display: none !important; }
    .admin-main { margin-left: 0 !important; max-width: 100% !important; padding: 0 !important; }
}
</style>
</head>
<body class="admin-body">

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
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php" class="active"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../index.php"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Reportes Detallados de Ventas</h1>
            <p>Período analizado: <strong><?= date('d/m/Y', strtotime($fechaDesde)) ?></strong> al <strong><?= date('d/m/Y', strtotime($fechaHasta)) ?></strong></p>
        </div>

        <form method="get" action="reportes.php" style="display:flex;gap:10px;align-items:center;background:#fff;padding:8px 14px;border-radius:10px;border:1px solid #E5E7EB;">
            <label style="font-size:0.8rem;font-weight:700;">Desde:</label>
            <input type="date" name="desde" value="<?= htmlspecialchars($fechaDesde) ?>" style="border:1px solid #ddd;padding:4px 8px;border-radius:6px;">
            <label style="font-size:0.8rem;font-weight:700;">Hasta:</label>
            <input type="date" name="hasta" value="<?= htmlspecialchars($fechaHasta) ?>" style="border:1px solid #ddd;padding:4px 8px;border-radius:6px;">
            <button type="submit" class="btn-cash-quick" style="border:none;background:var(--admin-dark);color:#fff;padding:6px 14px;">Filtrar</button>
            <button type="button" onclick="window.print()" class="btn-cash-quick btn-print-hide" style="border:none;background:var(--admin-rojo);color:#fff;padding:6px 14px;"><i class="fa-solid fa-print"></i> Imprimir</button>
        </form>
    </div>

    <!-- KPIs del Período -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
            <div class="kpi-info">
                <h3>$<?= number_format($global['total_ventas'], 0, ',', '.') ?></h3>
                <span>Recaudación Total del Período</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa-solid fa-receipt"></i></div>
            <div class="kpi-info">
                <h3><?= $global['cant_pedidos'] ?></h3>
                <span>Cantidad de Pedidos Realizados</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon yellow"><i class="fa-solid fa-chart-line"></i></div>
            <div class="kpi-info">
                <h3>$<?= number_format($global['ticket_promedio'], 0, ',', '.') ?></h3>
                <span>Ticket Promedio por Pedido</span>
            </div>
        </div>
    </div>

    <!-- Ranking de Productos Más Vendidos -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Top 10 Productos Más Vendidos</h2>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ranking</th>
                    <th>Producto / Plato</th>
                    <th>Unidades Vendidas</th>
                    <th>Recaudación Acumulada</th>
                </tr>
            </thead>
            <tbody>
                <?php $pos = 1; while ($r = $ranking->fetch_assoc()): ?>
                <tr>
                    <td><strong>#<?= $pos++ ?></strong></td>
                    <td><strong><?= htmlspecialchars($r['descripcion']) ?></strong></td>
                    <td><span style="font-weight:800;color:var(--admin-rojo);"><?= $r['total_vendido'] ?></span> u.</td>
                    <td><strong>$<?= number_format($r['recaudacion'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Desglose por Modalidad de Entrega -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Ventas por Modalidad de Entrega</h2>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Modalidad</th>
                    <th>Cantidad de Pedidos</th>
                    <th>Total Facturado</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $modalidades->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= strtoupper($m['tipo_entrega']) ?></strong></td>
                    <td><?= $m['cant'] ?> pedidos</td>
                    <td><strong>$<?= number_format($m['monto'], 0, ',', '.') ?></strong></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</main>

</body>
</html>
