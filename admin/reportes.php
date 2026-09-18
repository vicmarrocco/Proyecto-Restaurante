<?php
// ============================================================
// admin/reportes.php
// Reportes Detallados de Ventas, Auditoría y Rendimiento Gerencial (EPET Nº 7).
// ============================================================
session_start();
require('conexion.php');

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
$rankingRes = $stmtRanking->get_result();
$ranking = [];
$maxVendido = 1;
while ($row = $rankingRes->fetch_assoc()) {
    $ranking[] = $row;
    if ($row['total_vendido'] > $maxVendido) {
        $maxVendido = $row['total_vendido'];
    }
}

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
    "SELECT tipo_entrega, COUNT(*) as cant, IFNULL(SUM(total), 0) as monto
     FROM pedido_web
     WHERE DATE(fecha) BETWEEN ? AND ?
     GROUP BY tipo_entrega"
);
$stmtModalidad->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtModalidad->execute();
$modalidadesRes = $stmtModalidad->get_result();
$modalidades = [];
$modLabels = [];
$modTotals = [];
while ($row = $modalidadesRes->fetch_assoc()) {
    $modalidades[] = $row;
    $modLabels[] = strtoupper($row['tipo_entrega'] ?: 'Local');
    $modTotals[] = (float)$row['monto'];
}

// Ventas por medio de pago
$stmtMetodos = $conn->prepare(
    "SELECT metodo_pago, COUNT(*) as cant, IFNULL(SUM(total), 0) as monto
     FROM pedido_web
     WHERE DATE(fecha) BETWEEN ? AND ?
     GROUP BY metodo_pago"
);
$stmtMetodos->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtMetodos->execute();
$metodosRes = $stmtMetodos->get_result();
$metodos = [];
$payLabels = [];
$payTotals = [];
while ($row = $metodosRes->fetch_assoc()) {
    $metodos[] = $row;
    $payLabels[] = strtoupper($row['metodo_pago'] ?: 'Efectivo');
    $payTotals[] = (float)$row['monto'];
}

// Productividad por Terminal (Caja 1 vs Caja 2)
$stmtTerminales = $conn->prepare(
    "SELECT 
        numero_caja,
        COUNT(*) as cant_pedidos,
        IFNULL(SUM(total), 0) as total_ventas
     FROM pedido_web
     WHERE DATE(fecha) BETWEEN ? AND ?
     GROUP BY numero_caja
     ORDER BY numero_caja ASC"
);
$stmtTerminales->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtTerminales->execute();
$termRes = $stmtTerminales->get_result();
$terminales = [];
while ($row = $termRes->fetch_assoc()) {
    $terminales[] = $row;
}

// Productividad Cocineros KDS
$stmtCocina = $conn->prepare(
    "SELECT 
        cocinero_nombre,
        COUNT(*) as comandas_procesadas,
        ROUND(AVG(duracion_cocina_minutos), 1) as tiempo_promedio_min
     FROM pedido_web
     WHERE cocinero_nombre IS NOT NULL AND cocinero_nombre != ''
       AND DATE(fecha) BETWEEN ? AND ?
     GROUP BY cocinero_nombre
     ORDER BY comandas_procesadas DESC"
);
$stmtCocina->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtCocina->execute();
$cocinaRes = $stmtCocina->get_result();
$cocineros = [];
while ($row = $cocinaRes->fetch_assoc()) {
    $cocineros[] = $row;
}

// Productividad Repartidores Flota
$stmtDelivery = $conn->prepare(
    "SELECT 
        repartidor_asignado,
        COUNT(*) as entregas_completadas,
        ROUND(AVG(duracion_entrega_minutos), 1) as tiempo_promedio_min
     FROM pedido_web
     WHERE repartidor_asignado IS NOT NULL AND repartidor_asignado != ''
       AND DATE(fecha) BETWEEN ? AND ?
     GROUP BY repartidor_asignado
     ORDER BY entregas_completadas DESC"
);
$stmtDelivery->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtDelivery->execute();
$deliveryRes = $stmtDelivery->get_result();
$repartidores = [];
while ($row = $deliveryRes->fetch_assoc()) {
    $repartidores[] = $row;
}

// Últimos 15 pedidos para auditoría detallada
$stmtAuditoria = $conn->prepare(
    "SELECT idpedido, fecha, total, tipo_entrega, metodo_pago, estado, cajero_nombre, cocinero_nombre, repartidor_asignado, mesero_nombre, duracion_total_minutos
     FROM pedido_web
     WHERE DATE(fecha) BETWEEN ? AND ?
     ORDER BY idpedido DESC
     LIMIT 15"
);
$stmtAuditoria->bind_param("ss", $fechaDesde, $fechaHasta);
$stmtAuditoria->execute();
$pedidosAud = $stmtAuditoria->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes de Ventas & Productividad · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.progress-bar-container {
    background: #F1F5F9;
    border-radius: 999px;
    height: 8px;
    width: 100%;
    overflow: hidden;
    margin-top: 5px;
}
.progress-bar-fill {
    background: var(--admin-rojo);
    height: 100%;
    border-radius: 999px;
}
@media print {
    .admin-sidebar, .admin-topbar form, .btn-print-hide, .sidebar-footer { display: none !important; }
    .admin-main { margin-left: 0 !important; max-width: 100% !important; padding: 10px !important; }
    .admin-card { box-shadow: none !important; border: 1px solid #ccc !important; }
    body { background: #fff !important; }
}
</style>
</head>
<body class="admin-body">

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
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php" class="active"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Reportes Detallados de Ventas & Rendimiento</h1>
            <p>Período analizado: <strong><?= date('d/m/Y', strtotime($fechaDesde)) ?></strong> al <strong><?= date('d/m/Y', strtotime($fechaHasta)) ?></strong></p>
        </div>

        <form method="get" action="reportes.php" style="display:flex;gap:10px;align-items:center;background:#fff;padding:8px 14px;border-radius:12px;border:1px solid #E2E8F0;box-shadow:var(--shadow-sm);flex-wrap:wrap;">
            <label style="font-size:0.8rem;font-weight:700;color:#64748B;">Desde:</label>
            <input type="date" name="desde" value="<?= htmlspecialchars($fechaDesde) ?>" style="border:1px solid #CBD5E1;padding:6px 10px;border-radius:8px;font-family:inherit;font-size:0.85rem;">
            <label style="font-size:0.8rem;font-weight:700;color:#64748B;">Hasta:</label>
            <input type="date" name="hasta" value="<?= htmlspecialchars($fechaHasta) ?>" style="border:1px solid #CBD5E1;padding:6px 10px;border-radius:8px;font-family:inherit;font-size:0.85rem;">
            <button type="submit" class="btn-cash-quick" style="border:none;background:var(--admin-dark);color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;cursor:pointer;">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            <button type="button" onclick="window.print()" class="btn-cash-quick btn-print-hide" style="border:none;background:var(--admin-rojo);color:#fff;padding:8px 16px;border-radius:8px;font-weight:700;cursor:pointer;">
                <i class="fa-solid fa-print"></i> Imprimir Reporte
            </button>
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
                <h3><?= number_format($global['cant_pedidos'], 0, ',', '.') ?></h3>
                <span>Cantidad de Pedidos Concretados</span>
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

    <!-- RENDIMIENTO POR CAJA / TERMINAL -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fa-solid fa-cash-register" style="color:#A30002;"></i> Rendimiento por Terminal de Venta</h2>
            <span style="font-size:0.8rem;color:#64748B;font-weight:600;">Comparativa de Caja 1 vs Caja 2</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;">
            <?php foreach ($terminales as $t): 
                $num = $t['numero_caja'];
                $termName = ($num == 1) ? 'Caja 1 - Mostrador Principal' : (($num == 2) ? 'Caja 2 - Express / Salón' : 'Web / App');
                $borderCol = ($num == 1) ? '#A30002' : (($num == 2) ? '#D4930A' : '#3B82F6');
            ?>
            <div style="background:#FFFFFF;border-radius:12px;border:1px solid #E2E8F0;border-top:4px solid <?= $borderCol ?>;padding:18px;box-shadow:0 2px 4px rgba(0,0,0,0.02);">
                <span class="badge <?= ($num == 2) ? 'badge-warning' : 'badge-danger' ?>" style="font-weight:900;">
                    P.V. 000<?= $num ?>
                </span>
                <h3 style="margin:8px 0 4px;font-size:1.1rem;color:#0F172A;"><?= $termName ?></h3>
                <div style="display:flex;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px solid #F1F5F9;">
                    <span style="color:#64748B;font-size:0.85rem;">Tickets Emitidos:</span>
                    <strong style="color:#0F172A;"><?= number_format($t['cant_pedidos'], 0, ',', '.') ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:6px;">
                    <span style="color:#64748B;font-size:0.85rem;">Total Facturado:</span>
                    <strong style="color:<?= $borderCol ?>;font-size:1.1rem;">$<?= number_format($t['total_ventas'], 0, ',', '.') ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PRODUCTIVIDAD DE PERSONAL: COCINA Y REPARTO -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
        <!-- Cocina -->
        <div class="admin-card" style="margin-bottom:0;">
            <div class="admin-card-header">
                <h2><i class="fa-solid fa-fire-burner" style="color:#F59E0B;"></i> Rendimiento Cocineros (KDS)</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Cocinero</th>
                        <th style="text-align:center;">Comandas</th>
                        <th style="text-align:right;">Tiempo Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cocineros)): ?>
                        <tr><td colspan="3" style="text-align:center;color:#94A3B8;padding:20px;">Sin registros en el período</td></tr>
                    <?php else: ?>
                        <?php foreach ($cocineros as $c): ?>
                        <tr>
                            <td><strong>👨‍🍳 <?= htmlspecialchars($c['cocinero_nombre']) ?></strong></td>
                            <td style="text-align:center;"><span class="badge badge-warning"><?= $c['comandas_procesadas'] ?></span></td>
                            <td style="text-align:right;font-weight:700;color:#059669;"><?= $c['tiempo_promedio_min'] ? $c['tiempo_promedio_min'] . ' min' : 'En tiempo' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Reparto -->
        <div class="admin-card" style="margin-bottom:0;">
            <div class="admin-card-header">
                <h2><i class="fa-solid fa-motorcycle" style="color:#10B981;"></i> Rendimiento Delivery (Flota)</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Repartidor</th>
                        <th style="text-align:center;">Viajes</th>
                        <th style="text-align:right;">Tiempo Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($repartidores)): ?>
                        <tr><td colspan="3" style="text-align:center;color:#94A3B8;padding:20px;">Sin registros en el período</td></tr>
                    <?php else: ?>
                        <?php foreach ($repartidores as $rep): ?>
                        <tr>
                            <td><strong>🛵 <?= htmlspecialchars($rep['repartidor_asignado']) ?></strong></td>
                            <td style="text-align:center;"><span class="badge badge-success"><?= $rep['entregas_completadas'] ?></span></td>
                            <td style="text-align:right;font-weight:700;color:#2563EB;"><?= $rep['tiempo_promedio_min'] ? $rep['tiempo_promedio_min'] . ' min' : 'En tiempo' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gráficos Gerenciales -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:24px;margin-bottom:28px;">
        <div class="admin-card" style="margin-bottom:0;">
            <div class="admin-card-header">
                <h2>Ventas por Medio de Pago</h2>
            </div>
            <div style="padding:20px;display:flex;justify-content:center;align-items:center;min-height:260px;">
                <canvas id="chartMedios" style="max-height:240px;"></canvas>
            </div>
        </div>

        <div class="admin-card" style="margin-bottom:0;">
            <div class="admin-card-header">
                <h2>Ventas por Modalidad de Entrega</h2>
            </div>
            <div style="padding:20px;display:flex;justify-content:center;align-items:center;min-height:260px;">
                <canvas id="chartModalidades" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Ranking de Productos Más Vendidos -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Top 10 Productos Más Vendidos del Período</h2>
            <span style="font-size:0.8rem;color:#64748B;font-weight:600;">Ordenado por volumen de unidades</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:70px;text-align:center;">Puesto</th>
                        <th>Producto / Combo</th>
                        <th style="width:160px;text-align:center;">Unidades Vendidas</th>
                        <th style="width:240px;">Rendimiento Relativo</th>
                        <th style="width:180px;text-align:right;">Recaudación Acumulada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ranking)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:30px;color:#94A3B8;">
                            <i class="fa-solid fa-chart-bar" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
                            No se registran ventas para el período seleccionado.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $pos = 1; foreach ($ranking as $r): 
                            $pct = round(($r['total_vendido'] / $maxVendido) * 100);
                        ?>
                        <tr>
                            <td style="text-align:center;">
                                <span style="display:inline-flex;width:28px;height:28px;background:<?= $pos <= 3 ? 'var(--admin-rojo)' : '#F1F5F9' ?>;color:<?= $pos <= 3 ? '#fff' : '#64748B' ?>;border-radius:50%;align-items:center;justify-content:center;font-weight:800;font-size:0.8rem;">
                                    <?= $pos++ ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color:var(--admin-dark);"><?= htmlspecialchars($r['descripcion']) ?></strong>
                            </td>
                            <td style="text-align:center;">
                                <span style="font-weight:800;color:var(--admin-rojo);font-size:1.05rem;"><?= $r['total_vendido'] ?></span> <span style="font-size:0.75rem;color:#64748B;">u.</span>
                            </td>
                            <td>
                                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#64748B;font-weight:600;">
                                    <span>Volumen</span>
                                    <span><?= $pct ?>%</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <strong style="color:var(--admin-verde);font-size:1.05rem;">$<?= number_format($r['recaudacion'], 0, ',', '.') ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- AUDITORÍA DE PEDIDOS RECIENTES -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Registro de Trazabilidad y Auditoría de Pedidos</h2>
            <span style="font-size:0.8rem;color:#64748B;font-weight:600;">Historial auditado por orden</span>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Fecha</th>
                        <th>Modalidad</th>
                        <th>Medio Pago</th>
                        <th>Cajero / Mesero</th>
                        <th>Cocinero</th>
                        <th>Repartidor</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$pedidosAud || $pedidosAud->num_rows === 0): ?>
                        <tr><td colspan="9" style="text-align:center;color:#64748B;padding:30px;">No hay pedidos en el período.</td></tr>
                    <?php else: ?>
                        <?php while ($pa = $pedidosAud->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $pa['idpedido'] ?></strong></td>
                            <td><?= date('d/m H:i', strtotime($pa['fecha'])) ?></td>
                            <td><span class="badge badge-muted"><?= strtoupper($pa['tipo_entrega']) ?></span></td>
                            <td><?= strtoupper($pa['metodo_pago']) ?></td>
                            <td><?= htmlspecialchars($pa['cajero_nombre'] ?: ($pa['mesero_nombre'] ?: 'POS/Web')) ?></td>
                            <td><?= htmlspecialchars($pa['cocinero_nombre'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($pa['repartidor_asignado'] ?: '-') ?></td>
                            <td><strong>$<?= number_format($pa['total'], 0, ',', '.') ?></strong></td>
                            <td>
                                <button type="button" onclick="verAuditoria(<?= $pa['idpedido'] ?>)" class="btn-admin btn-admin-sm" style="background:#0F172A;color:#FFF;">
                                    <i class="fa-solid fa-timeline"></i> Cronología
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal Auditoría de Pedido -->
<div id="modalAuditoria" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.75);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#FFFFFF;border-radius:16px;width:100%;max-width:680px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 25px 50px -12px rgba(0,0,0,0.4);">
        <div style="padding:18px 24px;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;background:#F8FAFC;">
            <div>
                <h3 style="margin:0;font-size:1.15rem;font-weight:900;color:#0F172A;" id="audModalTitle">Auditoría de Pedido #0</h3>
                <span style="font-size:0.8rem;color:#64748B;" id="audModalSub">Trazabilidad completa del ciclo de vida y responsables</span>
            </div>
            <button type="button" onclick="cerrarModalAuditoria()" style="border:none;background:transparent;font-size:1.4rem;cursor:pointer;color:#64748B;">&times;</button>
        </div>
        <div style="padding:20px 24px;overflow-y:auto;flex:1;" id="audModalBody">
            <!-- Timeline inyectada aquí -->
        </div>
        <div style="padding:14px 24px;border-top:1px solid #E2E8F0;background:#F8FAFC;text-align:right;">
            <button type="button" onclick="cerrarModalAuditoria()" class="btn-admin btn-admin-white">Cerrar</button>
        </div>
    </div>
</div>

<script>
// Gráfico Medios de Pago
const ctxMedios = document.getElementById('chartMedios');
if (ctxMedios) {
    new Chart(ctxMedios, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($payLabels) ?>,
            datasets: [{
                data: <?= json_encode($payTotals) ?>,
                backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#8B5CF6', '#EC4899', '#64748B'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Montserrat', weight: 600 } } }
            }
        }
    });
}

// Gráfico Modalidades
const ctxMod = document.getElementById('chartModalidades');
if (ctxMod) {
    new Chart(ctxMod, {
        type: 'pie',
        data: {
            labels: <?= json_encode($modLabels) ?>,
            datasets: [{
                data: <?= json_encode($modTotals) ?>,
                backgroundColor: ['#A30002', '#FBB728', '#2563EB', '#10B981'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Montserrat', weight: 600 } } }
            }
        }
    });
}

async function verAuditoria(idpedido) {
    const modal = document.getElementById('modalAuditoria');
    const body = document.getElementById('audModalBody');
    document.getElementById('audModalTitle').textContent = `Auditoría Estricta · Pedido #${idpedido}`;
    body.innerHTML = '<div style="text-align:center;padding:30px;color:#64748B;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><p style="margin-top:10px;">Cargando cronología de eventos...</p></div>';
    modal.style.display = 'flex';

    try {
        const res = await fetch(`../mostaza_web/api/pos/auditoria.php?idpedido=${idpedido}`);
        const data = await res.json();
        if (!data.ok) {
            body.innerHTML = `<div style="color:#DC2626;padding:20px;text-align:center;">Error: ${data.error}</div>`;
            return;
        }

        const ped = data.pedido;
        const dur = data.duraciones;
        const eventos = data.eventos;

        let html = `
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;margin-bottom:20px;background:#F1F5F9;padding:12px;border-radius:10px;font-size:0.8rem;">
                <div><strong>Tipo:</strong> ${ped.tipo_entrega.toUpperCase()}</div>
                <div><strong>Total:</strong> $${Number(ped.total).toLocaleString('es-AR')}</div>
                <div><strong>Pago:</strong> ${ped.metodo_pago.toUpperCase()}</div>
                <div><strong>Cocina:</strong> ${dur.cocina_min !== null ? dur.cocina_min + ' min' : 'En proceso'}</div>
                <div><strong>Reparto:</strong> ${dur.entrega_min !== null ? dur.entrega_min + ' min' : (ped.tipo_entrega === 'delivery' ? 'Pendiente' : 'N/A')}</div>
                <div><strong>Total:</strong> ${dur.total_min !== null ? dur.total_min + ' min' : 'En curso'}</div>
            </div>
            <div style="position:relative;padding-left:30px;border-left:2px solid #CBD5E1;margin-left:15px;">
        `;

        eventos.forEach(ev => {
            html += `
                <div style="position:relative;margin-bottom:20px;">
                    <div style="position:absolute;left:-39px;top:2px;width:16px;height:16px;border-radius:50%;background:#A30002;border:3px solid #FFF;box-shadow:0 0 0 2px #CBD5E1;"></div>
                    <div style="font-size:0.75rem;color:#64748B;font-weight:700;">${ev.fecha_hora_formateada} · <span class="badge" style="background:#E2E8F0;color:#0F172A;">${ev.rol}</span></div>
                    <div style="font-size:0.95rem;font-weight:800;color:#0F172A;margin:2px 0;">${ev.accion}</div>
                    <div style="font-size:0.85rem;color:#334155;"><strong>Responsable:</strong> ${ev.nombre_responsable || 'Sistema Central'}</div>
                    ${ev.detalles ? `<div style="font-size:0.8rem;color:#64748B;margin-top:3px;background:#F8FAFC;padding:6px 10px;border-radius:6px;border:1px solid #E2E8F0;">${ev.detalles}</div>` : ''}
                </div>
            `;
        });

        html += '</div>';
        body.innerHTML = html;
    } catch (e) {
        body.innerHTML = `<div style="color:#DC2626;padding:20px;text-align:center;">Error de conexión: ${e.message}</div>`;
    }
}

function cerrarModalAuditoria() {
    document.getElementById('modalAuditoria').style.display = 'none';
}
</script>

</body>
</html>
