<?php
// ============================================================
// admin/caja.php
// Arqueo, Apertura y Cierre de Caja por Turno (EPET Nº 7).
// ============================================================
session_start();
require('../conexion.php');

$mensajeOk = '';

// Procesar cierre de caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_caja'])) {
    $idarqueo = intval($_POST['idarqueo']);
    $montoFisico = floatval($_POST['monto_fisico_cierre']);
    $totalEsperado = floatval($_POST['total_esperado']);
    $diferencia = $montoFisico - $totalEsperado;
    $fechaCierre = date('Y-m-d H:i:s');

    $stmtCierre = $conn->prepare("UPDATE caja_arqueo SET monto_fisico_cierre = ?, monto_final_sistema = ?, diferencia = ?, estado = 'Cerrada', fecha_cierre = ? WHERE idarqueo = ?");
    $stmtCierre->bind_param("dddsi", $montoFisico, $totalEsperado, $diferencia, $fechaCierre, $idarqueo);
    $stmtCierre->execute();
    $mensajeOk = '¡Caja cerrada y arqueo registrado correctamente!';
}

// Procesar apertura de nueva caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_caja'])) {
    $cajero = htmlspecialchars(trim($_POST['cajero'] ?? 'Cajero de Turno'));
    $turno = htmlspecialchars(trim($_POST['turno'] ?? 'Turno Mañana'));
    $montoInicial = floatval($_POST['monto_inicial']);

    $stmtApertura = $conn->prepare("INSERT INTO caja_arqueo (cajero, turno, monto_inicial, estado) VALUES (?, ?, ?, 'Abierta')");
    $stmtApertura->bind_param("ssd", $cajero, $turno, $montoInicial);
    $stmtApertura->execute();
    $mensajeOk = '¡Nueva caja abierta con éxito!';
}

// Consultar caja activa
$stmtActiva = $conn->query("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
$cajaActiva = $stmtActiva ? $stmtActiva->fetch_assoc() : null;

// Consultar historial de cierres
$historialCajas = $conn->query("SELECT * FROM caja_arqueo ORDER BY idarqueo DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control de Caja & Arqueo · Mostaza</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        <li><a href="caja.php" class="active"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../index.php"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Arqueo & Control de Caja</h1>
            <p>Monitoreo de ingresos en efectivo, tarjetas, billeteras virtuales y balances de turno.</p>
        </div>
    </div>

    <?php if ($mensajeOk): ?><div style="background:#DCFCE7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;"><?= $mensajeOk ?></div><?php endif; ?>

    <?php if ($cajaActiva): ?>
    <?php
    $totalEfectivoEsperado = $cajaActiva['monto_inicial'] + $cajaActiva['ventas_efectivo'];
    ?>
    <div class="admin-card" style="border-left:5px solid #10B981;">
        <div class="admin-card-header">
            <div>
                <h2>Turno Abierto: <?= htmlspecialchars($cajaActiva['turno']) ?></h2>
                <span style="font-size:0.85rem;color:#64748B;">Cajero: <strong><?= htmlspecialchars($cajaActiva['cajero']) ?></strong> · Abierto el <?= date('d/m/Y H:i', strtotime($cajaActiva['fecha_apertura'])) ?></span>
            </div>
            <span class="order-status-badge badge-entregado">CAJA ABIERTA</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;margin:20px 0;">
            <div style="background:#F8FAFC;padding:16px;border-radius:10px;border:1px solid #E2E8F0;">
                <span style="font-size:0.8rem;color:#64748B;">Monto Inicial (Fondo de Caja)</span>
                <strong style="display:block;font-size:1.3rem;color:var(--admin-dark);">$<?= number_format($cajaActiva['monto_inicial'], 0, ',', '.') ?></strong>
            </div>
            <div style="background:#F0FDF4;padding:16px;border-radius:10px;border:1px solid #86EFAC;">
                <span style="font-size:0.8rem;color:#166534;">Ventas en Efectivo</span>
                <strong style="display:block;font-size:1.3rem;color:#166534;">$<?= number_format($cajaActiva['ventas_efectivo'], 0, ',', '.') ?></strong>
            </div>
            <div style="background:#EFF6FF;padding:16px;border-radius:10px;border:1px solid #BFDBFE;">
                <span style="font-size:0.8rem;color:#1E40AF;">Ventas Tarjetas</span>
                <strong style="display:block;font-size:1.3rem;color:#1E40AF;">$<?= number_format($cajaActiva['ventas_tarjeta'], 0, ',', '.') ?></strong>
            </div>
            <div style="background:#F0F9FF;padding:16px;border-radius:10px;border:1px solid #7DD3FC;">
                <span style="font-size:0.8rem;color:#0369A1;">Ventas Mercado Pago</span>
                <strong style="display:block;font-size:1.3rem;color:#0369A1;">$<?= number_format($cajaActiva['ventas_mercadopago'], 0, ',', '.') ?></strong>
            </div>
            <div style="background:#FEF3C7;padding:16px;border-radius:10px;border:1px solid #FDE68A;">
                <span style="font-size:0.8rem;color:#92400E;">Recaudación Total del Turno</span>
                <strong style="display:block;font-size:1.3rem;color:#92400E;">$<?= number_format($cajaActiva['total_ventas'], 0, ',', '.') ?></strong>
            </div>
        </div>

        <!-- Formulario Arqueo Físico y Cierre -->
        <div style="background:#FAF5EA;border:1px solid #FDE68A;padding:20px;border-radius:12px;margin-top:20px;">
            <h3 style="color:#92400E;margin-bottom:12px;font-size:1.1rem;"><i class="fa-solid fa-calculator"></i> Realizar Arqueo Físico y Cerrar Turno</h3>
            <form method="post" action="caja.php">
                <input type="hidden" name="cerrar_caja" value="1">
                <input type="hidden" name="idarqueo" value="<?= $cajaActiva['idarqueo'] ?>">
                <input type="hidden" name="total_esperado" id="totalEsperado" value="<?= $totalEfectivoEsperado ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div>
                        <label style="font-weight:700;font-size:0.85rem;display:block;margin-bottom:4px;">Efectivo Físico Contado en Caja ($)</label>
                        <input type="number" name="monto_fisico_cierre" id="inputFisico" placeholder="Ej: <?= $totalEfectivoEsperado ?>" required step="100"
                               style="width:100%;padding:10px;border-radius:8px;border:1px solid #D1D5DB;font-size:1rem;" oninput="calcularDiferencia()">
                    </div>
                    <div>
                        <label style="font-weight:700;font-size:0.85rem;display:block;margin-bottom:4px;">Diferencia de Arqueo (Sobrante / Faltante)</label>
                        <div id="displayDiferencia" style="padding:10px;background:#fff;border-radius:8px;border:1px solid #D1D5DB;font-weight:800;font-size:1rem;color:#166534;">
                            $0 (Sin diferencia)
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit-order" style="background:var(--admin-dark);padding:12px 24px;width:auto;">
                    <i class="fa-solid fa-lock"></i> Confirmar Arqueo y Cerrar Turno de Caja
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Apertura de Caja -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Abrir Turno de Caja</h2>
        </div>
        <form method="post" action="caja.php" style="max-width:500px;">
            <input type="hidden" name="abrir_caja" value="1">
            <div class="form-group-custom" style="margin-bottom:12px;">
                <label>Nombre del Cajero</label>
                <input type="text" name="cajero" required placeholder="Ej: Marcos Gómez">
            </div>
            <div class="form-group-custom" style="margin-bottom:12px;">
                <label>Turno</label>
                <select name="turno">
                    <option value="Turno Mañana (08:00 - 16:00)">Turno Mañana (08:00 - 16:00)</option>
                    <option value="Turno Tarde/Noche (16:00 - 00:00)">Turno Tarde/Noche (16:00 - 00:00)</option>
                </select>
            </div>
            <div class="form-group-custom" style="margin-bottom:18px;">
                <label>Monto Inicial de Apertura (Fondo para cambio $)</label>
                <input type="number" name="monto_inicial" value="15000" required step="500">
            </div>
            <button type="submit" class="btn-submit-order" style="width:auto;padding:12px 24px;">
                <i class="fa-solid fa-key"></i> Abrir Caja de Turno
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Historial de Arqueos -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Historial de Arqueos y Turnos Anteriores</h2>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Cajero / Turno</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Fondo Inicial</th>
                        <th>Ventas Totales</th>
                        <th>Físico Contado</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($h = $historialCajas->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $h['idarqueo'] ?></strong></td>
                        <td><?= htmlspecialchars($h['cajero']) ?><br><span style="font-size:0.75rem;color:#64748B;"><?= htmlspecialchars($h['turno']) ?></span></td>
                        <td><?= date('d/m H:i', strtotime($h['fecha_apertura'])) ?></td>
                        <td><?= $h['fecha_cierre'] ? date('d/m H:i', strtotime($h['fecha_cierre'])) : 'En curso' ?></td>
                        <td>$<?= number_format($h['monto_inicial'], 0, ',', '.') ?></td>
                        <td><strong>$<?= number_format($h['total_ventas'], 0, ',', '.') ?></strong></td>
                        <td>$<?= number_format($h['monto_fisico_cierre'] ?? 0, 0, ',', '.') ?></td>
                        <td style="color:<?= ($h['diferencia'] < 0) ? '#EF4444' : '#10B981' ?>;font-weight:700;">
                            $<?= number_format($h['diferencia'] ?? 0, 0, ',', '.') ?>
                        </td>
                        <td><span class="order-status-badge <?= $h['estado'] === 'Abierta' ? 'badge-entregado' : 'badge-recibido' ?>" style="font-size:0.75rem;"><?= $h['estado'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
function calcularDiferencia() {
    const input = document.getElementById('inputFisico');
    const esperado = parseFloat(document.getElementById('totalEsperado').value) || 0;
    const fisico = parseFloat(input.value) || 0;
    const diff = fisico - esperado;
    const display = document.getElementById('displayDiferencia');

    if (diff === 0) {
        display.textContent = '$0 (Cuadre Exacto)';
        display.style.color = '#166534';
    } else if (diff > 0) {
        display.textContent = '+$' + diff.toLocaleString('es-AR') + ' (Sobrante)';
        display.style.color = '#2563EB';
    } else {
        display.textContent = '-$' + Math.abs(diff).toLocaleString('es-AR') + ' (Faltante)';
        display.style.color = '#DC2626';
    }
}
</script>

</body>
</html>
