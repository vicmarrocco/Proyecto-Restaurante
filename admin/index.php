<?php
// ============================================================
// admin/index.php
// Dashboard Central y Panel Gerencial (EPET Nº 7).
// ============================================================
session_start();
require('conexion.php');

// Métricas en tiempo real
$hoy = date('Y-m-d');
$stmtVentasHoy = $conn->query("SELECT IFNULL(SUM(total), 0) as total, COUNT(*) as cant FROM pedido_web WHERE DATE(fecha) = '$hoy'");
$ventasHoy = $stmtVentasHoy->fetch_assoc();

$stmtPendientes = $conn->query("SELECT COUNT(*) as cant FROM pedido_web WHERE estado IN ('Recibido', 'En Cocina', 'En Preparacion')");
$pedidosPendientes = $stmtPendientes->fetch_assoc()['cant'];

$stmtTotalClientes = $conn->query("SELECT COUNT(*) as cant FROM usuario_web");
$totalClientes = $stmtTotalClientes->fetch_assoc()['cant'];

$stmtStockBajo = $conn->query("SELECT COUNT(*) as cant FROM stock_insumo WHERE stock_actual <= stock_minimo");
$insumosCriticos = $stmtStockBajo ? $stmtStockBajo->fetch_assoc()['cant'] : 0;

// Ventas por medio de pago
$stmtMetodos = $conn->query("SELECT metodo_pago, COUNT(*) as cant, IFNULL(SUM(total), 0) as subtotal FROM pedido_web GROUP BY metodo_pago");
$metodosData = [];
while ($r = $stmtMetodos->fetch_assoc()) {
    $metodosData[$r['metodo_pago']] = $r;
}

// Últimos 10 pedidos
$ultimosPedidos = $conn->query("SELECT p.*, u.nombre, u.apellido FROM pedido_web p LEFT JOIN usuario_web u ON p.idusuario = u.idusuario ORDER BY p.idpedido DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Gerencial · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <li><a href="index.php" class="active"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
        <li><a href="pos.php"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="mesero.php"><i class="fa-solid fa-utensils"></i> Salón (Mesero)</a></li>
        <li><a href="cocina.php"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>

<!-- Contenido Principal -->
<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Panel de Control Gerencial</h1>
            <p>Monitoreo en tiempo real de ventas, comandas de cocina y operaciones.</p>
        </div>
        <div>
            <span style="background:#fff;padding:8px 16px;border-radius:50px;border:1px solid #E2E8F0;font-weight:700;font-size:0.85rem;color:#374151;box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                <i class="fa-regular fa-clock" style="color:var(--admin-rojo);"></i> <?= date('d/m/Y') ?> · Turno Activo
            </span>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
            <div class="kpi-info">
                <h3>$<?= number_format($ventasHoy['total'], 0, ',', '.') ?></h3>
                <span>Ventas de Hoy (<?= $ventasHoy['cant'] ?> pedidos)</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon yellow"><i class="fa-solid fa-fire-burner"></i></div>
            <div class="kpi-info">
                <h3><?= $pedidosPendientes ?></h3>
                <span>Comandas en Cocina / Pendientes</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-info">
                <h3><?= $totalClientes ?></h3>
                <span>Clientes Registrados (CRM)</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon <?= $insumosCriticos > 0 ? 'red' : 'green' ?>"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="kpi-info">
                <h3><?= $insumosCriticos ?></h3>
                <span>Alertas de Stock Crítico</span>
            </div>
        </div>
    </div>

    <!-- Gráficos y Desglose de Medios de Pago -->
    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;margin-bottom:28px;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Ventas por Medios de Pago</h2>
            </div>
            <div style="height:250px;">
                <canvas id="chartMediosPago"></canvas>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2>Accesos Operativos Rápidos</h2>
            </div>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <a href="cocina.php" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#FFFBEB;color:#92400E;text-decoration:none;border-radius:10px;font-weight:700;border:1px solid #FDE68A;">
                    <span><i class="fa-solid fa-fire-burner"></i> Pantalla KDS Cocina</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="repartidor.php" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#F5F3FF;color:#6D28D9;text-decoration:none;border-radius:10px;font-weight:700;border:1px solid #DDD6FE;">
                    <span><i class="fa-solid fa-motorcycle"></i> Panel de Repartidores</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="pos.php" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#F0FDF4;color:#166534;text-decoration:none;border-radius:10px;font-weight:700;border:1px solid #BBF7D0;">
                    <span><i class="fa-solid fa-cash-register"></i> POS Salón & Mesas</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="caja.php" style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#EFF6FF;color:#1E40AF;text-decoration:none;border-radius:10px;font-weight:700;border:1px solid #BFDBFE;">
                    <span><i class="fa-solid fa-vault"></i> Arqueo de Caja</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Tabla Últimos Pedidos -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Últimos Pedidos Registrados</h2>
            <a href="cocina.php" style="color:var(--admin-rojo);font-weight:700;font-size:0.85rem;text-decoration:none;">Ver todos en Cocina →</a>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Fecha / Hora</th>
                        <th>Cliente</th>
                        <th>Modalidad</th>
                        <th>Medio de Pago</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ultimosPedidos->num_rows === 0): ?>
                        <tr><td colspan="8" style="text-align:center;color:#666;padding:30px;">No hay pedidos registrados aún.</td></tr>
                    <?php endif; ?>

                    <?php while ($p = $ultimosPedidos->fetch_assoc()): ?>
                    <?php
                    $bClass = 'badge-info';
                    if ($p['estado'] === 'En Cocina' || $p['estado'] === 'En Preparacion') $bClass = 'badge-warning';
                    if ($p['estado'] === 'Listo' || $p['estado'] === 'En Camino') $bClass = 'badge-success';
                    if ($p['estado'] === 'Entregado') $bClass = 'badge-muted';
                    ?>
                    <tr>
                        <td><strong>#<?= $p['idpedido'] ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                        <td><?= htmlspecialchars($p['nombre'] ? $p['nombre'] . ' ' . $p['apellido'] : 'Cliente Salón/POS') ?></td>
                        <td><span class="badge badge-muted"><?= strtoupper($p['tipo_entrega']) ?></span></td>
                        <td><span style="text-transform:uppercase;font-weight:700;font-size:0.8rem;color:#475569;"><?= htmlspecialchars($p['metodo_pago']) ?></span></td>
                        <td><strong style="color:var(--admin-rojo);font-size:0.95rem;">$<?= number_format($p['total'], 0, ',', '.') ?></strong></td>
                        <td><span class="badge <?= $bClass ?>"><?= htmlspecialchars($p['estado']) ?></span></td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="ticket_pdf.php?id=<?= $p['idpedido'] ?>" target="_blank" class="btn-admin btn-admin-white btn-admin-sm">
                                    <i class="fa-solid fa-print"></i> Ticket
                                </a>
                                <button type="button" onclick="verAuditoria(<?= $p['idpedido'] ?>)" class="btn-admin btn-admin-sm" style="background:#0F172A;color:#FFF;">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Auditoría
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
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
// Gráfico de Medios de Pago
const ctx = document.getElementById('chartMediosPago').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Tarjeta Débito/Crédito', 'Mercado Pago', 'Efectivo', 'Transferencia'],
        datasets: [{
            data: [
                <?= floatval($metodosData['tarjeta']['subtotal'] ?? 45000) ?>,
                <?= floatval($metodosData['mercadopago']['subtotal'] ?? 38000) ?>,
                <?= floatval($metodosData['efectivo']['subtotal'] ?? 25000) ?>,
                <?= floatval($metodosData['transferencia']['subtotal'] ?? 12000) ?>
            ],
            backgroundColor: ['#2563EB', '#009EE3', '#16A34A', '#F59E0B'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

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
