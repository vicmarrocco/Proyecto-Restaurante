<?php
// ============================================================
// admin/inventario.php
// Control de Stock, Insumos, Escandallo y Alertas
// ============================================================
session_start();
require('conexion.php');

$mensajeOk = '';

// Procesar ingreso/ajuste de stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajustar_stock'])) {
    $idinsumo = intval($_POST['idinsumo']);
    $cantidad = floatval($_POST['cantidad_agregar']);
    $costo = floatval($_POST['costo_unitario']);

    $stmtUpd = $conn->prepare("UPDATE stock_insumo SET stock_actual = stock_actual + ?, costo_unitario = IF(? > 0, ?, costo_unitario) WHERE idinsumo = ?");
    $stmtUpd->bind_param("dddi", $cantidad, $costo, $costo, $idinsumo);
    $stmtUpd->execute();
    $mensajeOk = '¡Stock actualizado correctamente!';
}

// Consultar insumos
$insumos = $conn->query("SELECT * FROM stock_insumo ORDER BY (stock_actual <= stock_minimo) DESC, nombre ASC");

// Valorización del inventario
$valRes = $conn->query("SELECT SUM(stock_actual * costo_unitario) as valor_total, COUNT(*) as total_items FROM stock_insumo");
$valorizacion = $valRes ? $valRes->fetch_assoc() : ['valor_total' => 0, 'total_items' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario & Stock · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
        <li><a href="inventario.php" class="active"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li style="margin-top:20px;"><a href="../mostaza_web/" target="_blank"><i class="fa-solid fa-store"></i> Ir a la Tienda Web</a></li>
    </ul>

    <div class="sidebar-footer">
        Sistema Mostaza v2.5<br>EPET Nº 7 "Vicente Arcadio Salemi"
    </div>
</aside>

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Control de Stock & Inventario Valorizado</h1>
            <p>Control de materias primas, alertas automáticas de reposición crítica y valor de stock.</p>
        </div>
    </div>

    <?php if ($mensajeOk): ?><div style="background:#DCFCE7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #BBF7D0;"><?= $mensajeOk ?></div><?php endif; ?>

    <!-- Tarjetas de Resumen de Inventario -->
    <div class="kpi-grid" style="grid-template-columns: repeat(2, 1fr);">
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa-solid fa-coins"></i></div>
            <div class="kpi-info">
                <h3>$<?= number_format($valorizacion['valor_total'] ?? 0, 0, ',', '.') ?></h3>
                <span>Inventario Total Valorizado</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div class="kpi-info">
                <h3><?= $valorizacion['total_items'] ?? 0 ?></h3>
                <span>Insumos y Materias Primas Registradas</span>
            </div>
        </div>
    </div>

    <!-- Tabla de Insumos -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Listado de Insumos y Materias Primas</h2>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Insumo / Materia Prima</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Costo Unit.</th>
                        <th>Proveedor</th>
                        <th>Estado de Stock</th>
                        <th>Acción Rápida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($in = $insumos->fetch_assoc()): ?>
                    <?php
                    $esCritico = ($in['stock_actual'] <= $in['stock_minimo']);
                    $esAdvertencia = ($in['stock_actual'] <= $in['stock_minimo'] * 1.5);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($in['nombre']) ?></strong></td>
                        <td><span style="color:#64748B;font-size:0.85rem;"><?= htmlspecialchars($in['categoria']) ?></span></td>
                        <td>
                            <strong style="font-size:1.05rem;color:<?= $esCritico ? '#EF4444' : '#0F172A' ?>;">
                                <?= number_format($in['stock_actual'], 1) ?> <?= htmlspecialchars($in['unidad_medida']) ?>
                            </strong>
                        </td>
                        <td style="color:#64748B;"><?= number_format($in['stock_minimo'], 1) ?> <?= htmlspecialchars($in['unidad_medida']) ?></td>
                        <td>$<?= number_format($in['costo_unitario'], 2, ',', '.') ?></td>
                        <td><span style="color:#64748B;font-size:0.85rem;"><?= htmlspecialchars($in['proveedor']) ?></span></td>
                        <td>
                            <?php if ($esCritico): ?>
                                <span class="badge badge-danger">
                                    <i class="fa-solid fa-triangle-exclamation"></i> REPOSICIÓN URGENTE
                                </span>
                            <?php elseif ($esAdvertencia): ?>
                                <span class="badge badge-warning">
                                    <i class="fa-solid fa-clock"></i> STOCK MEDIO
                                </span>
                            <?php else: ?>
                                <span class="badge badge-success">
                                    <i class="fa-solid fa-check"></i> NORMAL
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn-admin btn-admin-white btn-admin-sm" onclick="abrirModalStock(<?= $in['idinsumo'] ?>, '<?= htmlspecialchars($in['nombre']) ?>', <?= $in['costo_unitario'] ?>)">
                                <i class="fa-solid fa-plus"></i> Reponer
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Reposición -->
    <div id="modalStock" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.6);z-index:200;align-items:center;justify-content:center;">
        <div style="background:#FFFFFF;padding:26px;border-radius:16px;max-width:440px;width:90%;border:1px solid #CBD5E1;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
            <h3 style="margin-bottom:6px;color:var(--admin-dark);font-size:1.25rem;font-weight:900;">Recepción de Mercadería</h3>
            <p id="modalInsumoNombre" style="font-weight:800;color:var(--admin-rojo);margin-bottom:16px;font-size:0.95rem;"></p>
            
            <form method="post" action="inventario.php">
                <input type="hidden" name="ajustar_stock" value="1">
                <input type="hidden" name="idinsumo" id="modalIdInsumo">

                <div class="form-group">
                    <label class="form-label">Cantidad a Ingresar / Reponer:</label>
                    <input type="number" name="cantidad_agregar" value="50" min="1" step="0.5" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Costo Unitario ($):</label>
                    <input type="number" name="costo_unitario" id="modalCostoUnit" step="0.01" required class="form-control">
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="document.getElementById('modalStock').style.display='none'" class="btn-admin btn-admin-white">Cancelar</button>
                    <button type="submit" class="btn-admin btn-admin-red">Confirmar Ingreso</button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
function abrirModalStock(id, nombre, costo) {
    document.getElementById('modalIdInsumo').value = id;
    document.getElementById('modalInsumoNombre').textContent = 'Insumo: ' + nombre;
    document.getElementById('modalCostoUnit').value = costo;
    document.getElementById('modalStock').style.display = 'flex';
}
</script>

</body>
</html>
