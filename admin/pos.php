<?php
// ============================================================
// admin/pos.php
// Punto de Venta (POS) y Mapa Gráfico de Mesas por Secciones.
// ============================================================
session_start();
require('conexion.php');

$mensajeOk = '';
$mensajeErr = '';

// Procesar pedido rápido desde el POS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido_pos'])) {
    $mesa = !empty($_POST['numero_mesa']) ? intval($_POST['numero_mesa']) : null;
    $tipo = !empty($mesa) ? 'salon' : 'takeaway';
    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';
    $productosSeleccionados = $_POST['productos'] ?? [];

    if (empty($productosSeleccionados)) {
        $mensajeErr = 'Debes agregar al menos un producto a la comanda.';
    } else {
        $subtotal = 0;
        $itemsParaGuardar = [];

        foreach ($productosSeleccionados as $idp => $cant) {
            $cant = intval($cant);
            if ($cant > 0) {
                $stmtP = $conn->prepare("SELECT idproducto, nombre, precio FROM producto WHERE idproducto = ?");
                $stmtP->bind_param("i", $idp);
                $stmtP->execute();
                $p = $stmtP->get_result()->fetch_assoc();
                if ($p) {
                    $itemTot = $p['precio'] * $cant;
                    $subtotal += $itemTot;
                    $itemsParaGuardar[] = [
                        'idproducto' => $p['idproducto'],
                        'nombre' => $p['nombre'],
                        'precio' => $p['precio'],
                        'cantidad' => $cant,
                        'subtotal' => $itemTot
                    ];
                }
            }
        }

        if (empty($itemsParaGuardar)) {
            $mensajeErr = 'No se seleccionaron cantidades válidas.';
        } else {
            $conn->begin_transaction();
            try {
                // Obtener arqueo activo
                $idarqueo = null;
                $resCaja = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
                if ($resCaja && $cRow = $resCaja->fetch_assoc()) {
                    $idarqueo = intval($cRow['idarqueo']);
                }

                $stmtIns = $conn->prepare(
                    "INSERT INTO pedido_web (
                        idarqueo, tipo_entrega, numero_mesa, metodo_pago, estado_pago, subtotal,
                        total, estado, notas
                    ) VALUES (?, ?, ?, ?, 'Aprobado', ?, ?, 'Recibido', 'Pedido tomado en POS Mostaza')"
                );
                $stmtIns->bind_param("isisdd", $idarqueo, $tipo, $mesa, $metodoPago, $subtotal, $subtotal);
                $stmtIns->execute();
                $idpedido = $conn->insert_id;

                $stmtDet = $conn->prepare(
                    "INSERT INTO detalle_pedido_web (idpedido, idproducto, descripcion, precio_unitario, cantidad, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                foreach ($itemsParaGuardar as $it) {
                    $stmtDet->bind_param("iisdid", $idpedido, $it['idproducto'], $it['nombre'], $it['precio'], $it['cantidad'], $it['subtotal']);
                    $stmtDet->execute();
                }

                // Sumar a caja activa
                if ($idarqueo) {
                    $colCaja = ($metodoPago === 'tarjeta') ? 'ventas_tarjeta' : (($metodoPago === 'mercadopago') ? 'ventas_mercadopago' : 'ventas_efectivo');
                    $conn->query("UPDATE caja_arqueo SET $colCaja = $colCaja + $subtotal, total_ventas = total_ventas + $subtotal, total_transacciones = total_transacciones + 1 WHERE idarqueo = $idarqueo");
                }

                // Generar Factura Fiscal
                $facturaFiscal = generarFacturaFiscal($conn, $idpedido, 'Factura B', null, [
                    'nombre_cajero' => 'Cajero Admin POS',
                    'nombre_cliente' => $mesa ? "Mesa $mesa (Salón)" : "Cliente Mostrador"
                ]);

                $conn->commit();
                $mensajeOk = "¡Comanda POS #$idpedido y Factura {$facturaFiscal['numero_factura']} creadas exitosamente!";
            } catch (Exception $e) {
                $conn->rollback();
                $mensajeErr = 'Error creando comanda POS: ' . $e->getMessage();
            }
        }
    }
}

// Consultar mesas ocupadas en tiempo real
$stmtMesas = $conn->query(
    "SELECT p.numero_mesa, p.idpedido, p.total, p.fecha, f.numero_factura 
     FROM pedido_web p
     LEFT JOIN factura f ON p.idpedido = f.idpedido
     WHERE p.tipo_entrega = 'salon' AND p.estado IN ('Recibido', 'En Cocina', 'Listo') AND p.numero_mesa IS NOT NULL"
);
$mesasOcupadas = [];
if ($stmtMesas) {
    while ($m = $stmtMesas->fetch_assoc()) {
        $mesasOcupadas[$m['numero_mesa']] = $m;
    }
}

// Obtener secciones y mesas de la BD
$seccionesRes = $conn->query("SELECT * FROM seccion_mesa ORDER BY orden");
$secciones = [];
if ($seccionesRes) {
    while ($s = $seccionesRes->fetch_assoc()) {
        $secciones[] = $s;
    }
}

$mesasRes = $conn->query("SELECT m.*, s.nombre AS seccion_nombre FROM mesa m JOIN seccion_mesa s ON m.fk_id_seccion = s.idseccion WHERE m.activa = 1 ORDER BY m.numero_mesa");
$mesasLista = [];
if ($mesasRes) {
    while ($m = $mesasRes->fetch_assoc()) {
        $mesasLista[] = $m;
    }
}

// Obtener catálogo de productos
$catalogo = $conn->query("SELECT * FROM producto WHERE activo = 1 ORDER BY categoria, idproducto");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>POS Salón & Mesas · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.seccion-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}
.seccion-tab-btn {
    padding: 8px 16px;
    border-radius: 8px;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    color: #475569;
}
.seccion-tab-btn.active, .seccion-tab-btn:hover {
    background: var(--admin-rojo);
    color: #fff;
    border-color: var(--admin-rojo);
}
.pos-table-btn.occupied {
    background: #FEF2F2 !important;
    border-color: #EF4444 !important;
}
.pos-table-btn.occupied span {
    color: #DC2626 !important;
    font-weight: 800;
}
.pos-table-btn.selected-table {
    border: 2px solid #F59E0B !important;
    background: #FFFBEB !important;
    transform: scale(1.03);
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
        <li><a href="pos.php" class="active"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
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

<main class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Punto de Venta (POS) & Servicio a Mesa</h1>
            <p>Mapa táctil de mesas del salón por secciones, toma de comandas y facturación fiscal automática.</p>
        </div>
    </div>

    <?php if ($mensajeOk): ?><div style="background:#DCFCE7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #BBF7D0;"><?= $mensajeOk ?></div><?php endif; ?>
    <?php if ($mensajeErr): ?><div style="background:#FEE2E2;color:#991B1B;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #FECACA;"><?= $mensajeErr ?></div><?php endif; ?>

    <!-- Mapa de Mesas por Secciones -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Mapa Gráfico de Mesas Interactivo</h2>
                <span style="font-size:0.85rem;color:#64748B;">Hacé clic en una mesa disponible para asignarla a la comanda</span>
            </div>
            <div style="font-size:0.85rem;display:flex;gap:14px;">
                <span style="color:#10B981;font-weight:700;"><i class="fa-solid fa-square"></i> Libre</span>
                <span style="color:#EF4444;font-weight:700;"><i class="fa-solid fa-square"></i> Ocupada</span>
            </div>
        </div>

        <!-- Pestañas de Secciones -->
        <div class="seccion-tabs">
            <button type="button" class="seccion-tab-btn active" onclick="filtrarSeccion('todas', this)">
                <i class="fa-solid fa-border-all"></i> Todas las Secciones (<?= count($mesasLista) ?>)
            </button>
            <?php foreach ($secciones as $sec): ?>
            <button type="button" class="seccion-tab-btn" onclick="filtrarSeccion(<?= $sec['idseccion'] ?>, this)">
                <i class="fa-solid <?= $sec['icono'] ?>"></i> <?= htmlspecialchars($sec['nombre']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="pos-tables-grid" id="tablesGrid">
            <?php foreach ($mesasLista as $mRow): ?>
            <?php 
            $numMesa = intval($mRow['numero_mesa']);
            $estaOcupada = isset($mesasOcupadas[$numMesa]); 
            ?>
            <div class="pos-table-btn <?= $estaOcupada ? 'occupied' : '' ?>" 
                 data-seccion="<?= $mRow['fk_id_seccion'] ?>"
                 id="btn-mesa-<?= $numMesa ?>"
                 onclick="seleccionarMesaPOS(<?= $numMesa ?>, <?= $estaOcupada ? 'true' : 'false' ?>)">
                <i class="fa-solid fa-chair" style="color:<?= $estaOcupada ? '#EF4444' : '#10B981' ?>;font-size:1.4rem;"></i>
                <strong>Mesa <?= $numMesa ?></strong>
                <small style="font-size:0.75rem;color:#64748B;display:block;"><?= htmlspecialchars($mRow['seccion_nombre']) ?> (<?= $mRow['capacidad'] ?>p)</small>
                <span><?= $estaOcupada ? 'OCUPADA ($' . number_format($mesasOcupadas[$numMesa]['total'], 0, ',', '.') . ')' : 'LIBRE' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Formulario de Toma de Comanda POS -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Toma Rápida de Comanda POS & Factura</h2>
        </div>

        <form method="post" action="pos.php" id="form-pos">
            <input type="hidden" name="crear_pedido_pos" value="1">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                    <label class="form-label">Asignar a Mesa (Opcional si es Take-Away)</label>
                    <select name="numero_mesa" id="selectMesaPOS" class="form-control">
                        <option value="">Mostrador / Para Llevar (Take Away)</option>
                        <?php foreach ($mesasLista as $mRow): ?>
                            <option value="<?= $mRow['numero_mesa'] ?>">
                                Mesa <?= $mRow['numero_mesa'] ?> · <?= htmlspecialchars($mRow['seccion_nombre']) ?> (<?= $mRow['capacidad'] ?> pers.) <?= isset($mesasOcupadas[$mRow['numero_mesa']]) ? '⚠️ [OCUPADA]' : '✓ [LIBRE]' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Forma de Pago</label>
                    <select name="metodo_pago" class="form-control">
                        <option value="efectivo">💵 Efectivo en Caja</option>
                        <option value="tarjeta">💳 Tarjeta Débito / Crédito POS</option>
                        <option value="mercadopago">📱 Mercado Pago QR Salón</option>
                        <option value="transferencia">🏦 Transferencia Bancaria</option>
                    </select>
                </div>
            </div>

            <!-- Grilla de Productos Rápidos -->
            <label class="form-label" style="margin-bottom:10px;">Seleccionar Productos para la Comanda</label>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:12px;max-height:350px;overflow-y:auto;padding-right:10px;margin-bottom:20px;">
                <?php while ($p = $catalogo->fetch_assoc()): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;background:#FFFFFF;padding:12px 14px;border-radius:10px;border:1px solid #E2E8F0;box-shadow:0 1px 4px rgba(0,0,0,0.02);">
                    <div>
                        <strong style="font-size:0.9rem;color:var(--admin-dark);display:block;"><?= htmlspecialchars($p['nombre']) ?></strong>
                        <span style="font-size:0.8rem;color:var(--admin-rojo);font-weight:800;">$<?= number_format($p['precio'], 0, ',', '.') ?></span>
                    </div>
                    <input type="number" name="productos[<?= $p['idproducto'] ?>]" value="0" min="0" max="20" style="width:60px;padding:6px;text-align:center;border-radius:6px;border:1px solid #CBD5E1;font-weight:700;">
                </div>
                <?php endwhile; ?>
            </div>

            <button type="submit" class="btn-admin btn-admin-red" style="width:100%;padding:14px;font-size:1.05rem;justify-content:center;">
                <i class="fa-solid fa-paper-plane"></i> Facturar, Cobrar y Enviar a Cocina
            </button>
        </form>
    </div>

</main>

<script>
function filtrarSeccion(secId, btn) {
    document.querySelectorAll('.seccion-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.pos-table-btn');
    cards.forEach(card => {
        if (secId === 'todas' || card.getAttribute('data-seccion') == secId) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function seleccionarMesaPOS(numero, ocupada) {
    document.querySelectorAll('.pos-table-btn').forEach(b => b.classList.remove('selected-table'));
    const btn = document.getElementById('btn-mesa-' + numero);
    if (btn) btn.classList.add('selected-table');

    document.getElementById('selectMesaPOS').value = numero;
    document.getElementById('form-pos').scrollIntoView({ behavior: 'smooth' });
}
</script>

</body>
</html>
