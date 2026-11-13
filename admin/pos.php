<?php
// ============================================================
// admin/pos.php
// Punto de Venta (POS) y Mapa Gráfico de Mesas por Secciones.
// ============================================================
session_start();
require('conexion.php');

// Función de respaldo: registra el evento en la auditoría y actualiza el estado del pedido
if (!function_exists('registrarAuditoriaPedido')) {
    function registrarAuditoriaPedido($conn, $idpedido, $nuevoEstado, $accion, $rol, $nombre, $idEmpleado, $motivo = '') {
        $idpedido = intval($idpedido);
        if ($idpedido <= 0) return false;

        $stmtP = $conn->prepare("SELECT estado FROM pedido_web WHERE idpedido = ?");
        $stmtP->bind_param("i", $idpedido);
        $stmtP->execute();
        $p = $stmtP->get_result()->fetch_assoc();
        $estadoAnterior = $p['estado'] ?? 'Recibido';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Insertar evento en pedido_auditoria
        $stmtAud = $conn->prepare("
            INSERT INTO pedido_auditoria (idpedido, estado_anterior, estado_nuevo, accion, rol_responsable, idempleado, nombre_responsable, detalles, ip_origen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmtAud) {
            $stmtAud->bind_param("issssisss", $idpedido, $estadoAnterior, $nuevoEstado, $accion, $rol, $idEmpleado, $nombre, $motivo, $ip);
            $stmtAud->execute();
        }

        // 2. Actualizar estado (y datos de retiro) en pedido_web
        $updates = ["`estado` = '" . $conn->real_escape_string($nuevoEstado) . "'"];
        if (in_array($nuevoEstado, ['Retirado', 'Entregado'])) {
            $updates[] = "`hora_entregado` = IFNULL(`hora_entregado`, NOW())";
            if ($rol === 'Cajero' && !empty($nombre)) {
                $updates[] = "`cajero_nombre` = '" . $conn->real_escape_string($nombre) . "'";
            }
        }
        $conn->query("UPDATE pedido_web SET " . implode(', ', $updates) . " WHERE idpedido = $idpedido");
        return true;
    }
}

$mensajeOk = '';
$mensajeErr = '';

// Marcar pedido como Retirado del Lugar por el Cajero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar_retirado_pos'])) {
    $idPedRet = intval($_POST['idpedido'] ?? 0);
    if ($idPedRet > 0) {
        $cajeroNom = $_SESSION['cajero_nombre'] ?? ($_SESSION['admin_nombre'] ?? 'Cajero POS');
        $cajeroId = $_SESSION['cajero_id'] ?? null;
        registrarAuditoriaPedido(
            $conn,
            $idPedRet,
            'Retirado',
            'Pedido Retirado del Lugar',
            'Cajero',
            $cajeroNom,
            $cajeroId,
            'Pedido retirado del lugar en mostrador POS'
        );
        $mensajeOk = "¡Pedido #$idPedRet marcado como Retirado del Lugar exitosamente!";
    }
}

// Procesar pedido rápido desde el POS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_pedido_pos'])) {
    $tipoSel = $_POST['tipo_entrega'] ?? '';
    $mesa = !empty($_POST['numero_mesa']) ? intval($_POST['numero_mesa']) : null;
    if ($tipoSel === 'takeaway') {
        $mesa = null; // Para llevar: nunca se asocia a una mesa
    }
    $tipo = !empty($mesa) ? 'salon' : 'takeaway';
    $metodoPago = $_POST['metodo_pago'] ?? 'efectivo';
    $productosSeleccionados = $_POST['productos'] ?? [];

    if (!in_array($tipoSel, ['salon', 'takeaway'], true)) {
        $mensajeErr = 'Primero elegí cómo se retira el pedido (en el salón o para llevar).';
    } elseif ($tipoSel === 'salon' && empty($mesa)) {
        $mensajeErr = 'Elegiste servicio en el salón: seleccioná la mesa en el mapa.';
    } elseif (empty($productosSeleccionados)) {
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

// Obtener pedidos Take Away / Para retirar en el lugar (activos o del día de hoy)
$hoyFecha = date('Y-m-d');
$pedidosTakeawayRes = $conn->query("
    SELECT p.*, u.nombre AS u_nombre, u.apellido AS u_apellido, f.numero_factura
    FROM pedido_web p
    LEFT JOIN usuario_web u ON p.idusuario = u.idusuario
    LEFT JOIN factura f ON p.idpedido = f.idpedido
    WHERE p.tipo_entrega = 'takeaway'
      AND (DATE(p.fecha) = '$hoyFecha' OR DATE(p.hora_entregado) = '$hoyFecha' OR p.estado IN ('Recibido', 'En Cocina', 'En Preparacion', 'En Preparación', 'Listo', 'Listo para Despacho'))
      AND p.estado != 'Cancelado'
    ORDER BY FIELD(p.estado, 'Listo', 'Listo para Despacho', 'En Cocina', 'En Preparación', 'En Preparacion', 'Recibido', 'Retirado', 'Entregado'), p.idpedido DESC
    LIMIT 30
");
$pedidosTakeaway = [];
if ($pedidosTakeawayRes) {
    while ($pt = $pedidosTakeawayRes->fetch_assoc()) {
        $pedidosTakeaway[] = $pt;
    }
}
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

/* ---- Flujo por pasos del cajero ---- */
.paso-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 50%;
    background: var(--admin-rojo); color: #fff;
    font-size: 0.85rem; font-weight: 900; margin-right: 8px;
}
.tipo-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
@media (max-width: 700px) { .tipo-grid { grid-template-columns: 1fr; } }
.tipo-card {
    background: #FFFFFF; border: 2px solid #CBD5E1; border-radius: 14px;
    padding: 24px 16px; text-align: center; cursor: pointer;
    font-family: inherit; color: var(--admin-dark);
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    transition: all 0.2s;
}
.tipo-card i { font-size: 2rem; color: var(--admin-rojo); margin-bottom: 4px; }
.tipo-card strong { font-size: 1.1rem; font-weight: 900; }
.tipo-card span { font-size: 0.82rem; color: #64748B; font-weight: 600; }
.tipo-card:hover { border-color: var(--admin-rojo); transform: translateY(-2px); }
.tipo-card.active {
    border-color: var(--admin-rojo); background: #FEF2F2;
    box-shadow: 0 0 0 3px rgba(163, 0, 2, 0.15);
}
.resumen-retiro {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px;
    padding: 12px 16px; margin-bottom: 20px; font-weight: 700; font-size: 0.9rem;
}
.resumen-retiro.ok { background: #F0FDF4; border-color: #BBF7D0; color: #166534; }
.paso-titulo { font-size: 1.05rem; font-weight: 800; margin: 0 0 12px; color: var(--admin-dark); }
.paso-bloqueado { opacity: 0.45; pointer-events: none; user-select: none; }
.pago-opciones { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
@media (max-width: 700px) { .pago-opciones { grid-template-columns: 1fr; } }
.pago-card {
    display: flex; align-items: center; gap: 12px; cursor: pointer;
    background: #FFFFFF; border: 2px solid #CBD5E1; border-radius: 12px;
    padding: 14px 16px; font-weight: 800; font-size: 0.92rem; transition: all 0.2s;
}
.pago-card input { accent-color: var(--admin-rojo); width: 18px; height: 18px; }
.pago-card i { font-size: 1.3rem; color: var(--admin-rojo); }
.pago-card:has(input:checked) { border-color: var(--admin-rojo); background: #FEF2F2; box-shadow: 0 0 0 3px rgba(163, 0, 2, 0.12); }
.total-pos-bar {
    display: flex; justify-content: space-between; align-items: center;
    background: #0F172A; color: #fff; border-radius: 12px;
    padding: 14px 20px; margin-bottom: 20px; font-weight: 800;
}
.total-pos-bar strong { font-size: 1.4rem; color: #FBB728; }
#btnEnviarPOS:disabled { opacity: 0.5; cursor: not-allowed; }
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
        <li><a href="productos.php"><i class="fa-solid fa-burger"></i> Catálogo & Productos</a></li>
        <li><a href="pos.php" class="active"><i class="fa-solid fa-cash-register"></i> Punto de Venta (POS)</a></li>
        <li><a href="mesero.php"><i class="fa-solid fa-utensils"></i> Salón (Mesero)</a></li>
        <li><a href="cocina.php"><i class="fa-solid fa-fire-burner"></i> Cocina (KDS)</a></li>
        <li><a href="repartidor.php"><i class="fa-solid fa-motorcycle"></i> Repartidor</a></li>
        <li><a href="caja.php"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
        <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i> Inventario & Stock</a></li>
        <li><a href="reportes.php"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes</a></li>
        <li><a href="empleados.php"><i class="fa-solid fa-users"></i> Empleados</a></li>
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
            <p>Primero elegí cómo se retira el pedido, después armá la comanda y seleccioná el método de pago.</p>
        </div>
    </div>

    <?php if ($mensajeOk): ?><div style="background:#DCFCE7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #BBF7D0;"><?= $mensajeOk ?></div><?php endif; ?>
    <?php if ($mensajeErr): ?><div style="background:#FEE2E2;color:#991B1B;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #FECACA;"><?= $mensajeErr ?></div><?php endif; ?>

    <!-- PASO 1: ¿Cómo se retira el pedido? -->
    <div class="admin-card" id="pasoRetiro">
        <div class="admin-card-header">
            <div>
                <h2><span class="paso-num">1</span> ¿Cómo se retira el pedido?</h2>
                <span style="font-size:0.85rem;color:#64748B;">Elegí una opción para empezar la comanda</span>
            </div>
        </div>

        <div class="tipo-grid">
            <button type="button" class="tipo-card" data-tipo="salon" onclick="elegirTipoPOS('salon')">
                <i class="fa-solid fa-utensils"></i>
                <strong>En el salón</strong>
                <span>Consumo en mesa</span>
            </button>
            <button type="button" class="tipo-card" data-tipo="takeaway" onclick="elegirTipoPOS('takeaway')">
                <i class="fa-solid fa-bag-shopping"></i>
                <strong>Para llevar</strong>
                <span>Take away / retiro en mostrador</span>
            </button>
        </div>

        <!-- Mapa de Mesas (solo si eligió salón) -->
        <div id="panelMesas" style="display:none;margin-top:24px;border-top:1px solid #E2E8F0;padding-top:20px;">
        <div class="admin-card-header">
            <div>
                <h2>Elegí la mesa</h2>
                <span style="font-size:0.85rem;color:#64748B;">Hacé clic en una mesa para asignarla a la comanda</span>
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
        </div><!-- /panelMesas -->
    </div>

    <!-- Formulario de Toma de Comanda POS -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Comanda y cobro</h2>
        </div>

        <form method="post" action="pos.php" id="form-pos">
            <input type="hidden" name="crear_pedido_pos" value="1">

            <input type="hidden" name="tipo_entrega" id="inputTipoPOS" value="">
            <input type="hidden" name="numero_mesa" id="selectMesaPOS" value="">

            <div class="resumen-retiro" id="resumenRetiro">
                <i class="fa-solid fa-circle-info"></i> Elegí en el paso 1 cómo se retira el pedido para continuar.
            </div>

            <div id="bloqueComanda" class="paso-bloqueado">

            <!-- PASO 2: Armar el pedido -->
            <h3 class="paso-titulo"><span class="paso-num">2</span> Armá el pedido</h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));gap:12px;max-height:350px;overflow-y:auto;padding-right:10px;margin-bottom:20px;">
                <?php while ($p = $catalogo->fetch_assoc()): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;background:#FFFFFF;padding:12px 14px;border-radius:10px;border:1px solid #E2E8F0;box-shadow:0 1px 4px rgba(0,0,0,0.02);">
                    <div>
                        <strong style="font-size:0.9rem;color:var(--admin-dark);display:block;"><?= htmlspecialchars($p['nombre']) ?></strong>
                        <span style="font-size:0.8rem;color:var(--admin-rojo);font-weight:800;">$<?= number_format($p['precio'], 0, ',', '.') ?></span>
                    </div>
                    <input type="number"
                           class="input-prod-pos"
                           data-id="<?= $p['idproducto'] ?>"
                           data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>"
                           data-precio="<?= $p['precio'] ?>"
                           name="productos[<?= $p['idproducto'] ?>]"
                           value="0" min="0" max="20"
                           onchange="recalcularTotalPOS()" oninput="recalcularTotalPOS()"
                           style="width:60px;padding:6px;text-align:center;border-radius:6px;border:1px solid #CBD5E1;font-weight:700;">
                </div>
                <?php endwhile; ?>
            </div>

            <div class="total-pos-bar">
                <span>Total de la comanda</span>
                <strong id="lblTotalPOS">$0</strong>
            </div>

            <!-- PASO 3: Método de pago -->
            <h3 class="paso-titulo"><span class="paso-num">3</span> Método de pago</h3>
            <div class="pago-opciones">
                <label class="pago-card">
                    <input type="radio" name="metodo_pago" value="efectivo" checked onchange="togglePanelMPAdmin()">
                    <i class="fa-solid fa-money-bill-wave"></i> Efectivo en Caja
                </label>
                <label class="pago-card">
                    <input type="radio" name="metodo_pago" value="mercadopago" onchange="togglePanelMPAdmin()">
                    <i class="fa-solid fa-qrcode"></i> Mercado Pago (QR / Checkout Pro)
                </label>
            </div>

            <!-- Panel Pasarela Oficial Mercado Pago (Admin POS) -->
            <div id="panelMPAdminPOS" style="display:none;background:#F0F9FF;border:2px solid #009EE3;border-radius:14px;padding:18px;margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                    <div>
                        <h3 style="margin:0;color:#0284C7;font-size:1.05rem;"><i class="fa-solid fa-qrcode"></i> Pasarela Oficial Mercado Pago · POS Salón</h3>
                        <small style="color:#0C4A6E;">Total calculado: <strong id="lblTotalMPAdmin">$0</strong></small>
                    </div>
                    <button type="button" onclick="generarPreferenciaMPAdmin()" style="background:#009EE3;color:#fff;border:none;border-radius:8px;padding:8px 14px;font-weight:800;cursor:pointer;">
                        <i class="fa-solid fa-rotate"></i> Generar Orden QR / Checkout Pro
                    </button>
                </div>

                <div id="contenidoMPAdmin" style="display:none;grid-template-columns:200px 1fr;gap:18px;align-items:center;background:#fff;padding:14px;border-radius:10px;border:1px solid #BAE6FD;">
                    <div style="text-align:center;">
                        <img id="imgQrMPAdmin" src="" alt="QR Mercado Pago" style="width:170px;height:170px;border-radius:8px;border:1px solid #E2E8F0;">
                    </div>
                    <div>
                        <div id="statusMPAdmin" style="background:#E0F2FE;color:#0369A1;padding:10px 12px;border-radius:8px;font-weight:800;font-size:0.9rem;margin-bottom:10px;">
                            ⏳ Esperando pago del cliente en Mercado Pago...
                        </div>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                            <a id="btnLinkMPAdmin" href="#" target="_blank" style="background:#009EE3;color:#fff;text-decoration:none;padding:9px 14px;border-radius:8px;font-weight:800;font-size:0.85rem;">
                                🌐 Abrir Checkout Pro
                            </a>
                            <button type="button" onclick="verificarPagoMPAdmin(true)" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;padding:9px 14px;border-radius:8px;font-weight:800;font-size:0.85rem;cursor:pointer;">
                                🔍 Verificar Acreditación
                            </button>
                        </div>
                        <div style="font-size:0.8rem;color:#475569;">
                            <strong>Usuario Comprador de Prueba (Incógnito):</strong> <code>test_user_4298537724830968734@testuser.com</code> (Clave: <code>kqTMwlqBUK</code>) · Tarjeta: <code>5031 7557 3453 0604</code>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" id="btnEnviarPOS" disabled class="btn-admin btn-admin-red" style="width:100%;padding:14px;font-size:1.05rem;justify-content:center;">
                <i class="fa-solid fa-paper-plane"></i> Facturar, Cobrar y Enviar a Cocina
            </button>
            </div><!-- /bloqueComanda -->
        </form>
    </div>

    <!-- Pedidos para Retirar del Lugar (Take Away / Mostrador) -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <h2><i class="fa-solid fa-bag-shopping" style="color:var(--admin-rojo);"></i> Pedidos para Retirar del Lugar (Mostrador / Take Away)</h2>
            <span class="badge badge-info"><?= count($pedidosTakeaway) ?> pedidos</span>
        </div>

        <?php if (empty($pedidosTakeaway)): ?>
            <div style="padding:20px;text-align:center;color:#64748B;">No hay pedidos para retirar en mostrador en este momento.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Comanda #</th>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Medio Pago</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción de Cajero</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidosTakeaway as $pt): ?>
                            <?php
                            $estPt = $pt['estado'];
                            $yaRetirado = in_array($estPt, ['Retirado', 'Entregado']);
                            $esListo = in_array($estPt, ['Listo', 'Listo para Despacho']);
                            $clienteNom = trim(($pt['u_nombre'] ?? '') . ' ' . ($pt['u_apellido'] ?? '')) ?: 'Cliente Mostrador';
                            $badgePt = $yaRetirado ? 'badge-muted' : ($esListo ? 'badge-success' : 'badge-warning');
                            ?>
                            <tr style="<?= $esListo ? 'background:#F0FDF4;' : '' ?>">
                                <td><strong>#<?= $pt['idpedido'] ?></strong></td>
                                <td><?= date('H:i', strtotime($pt['fecha'])) ?> hs</td>
                                <td><?= htmlspecialchars($clienteNom) ?></td>
                                <td><span style="text-transform:uppercase;font-weight:700;font-size:0.8rem;"><?= htmlspecialchars($pt['metodo_pago']) ?></span></td>
                                <td><strong style="color:var(--admin-rojo);">$<?= number_format($pt['total'], 0, ',', '.') ?></strong></td>
                                <td>
                                    <span class="badge <?= $badgePt ?>">
                                        <?= $yaRetirado ? '✓ Retirado del Lugar' : ($esListo ? '🔔 Listo para Retirar' : htmlspecialchars($estPt)) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$yaRetirado): ?>
                                        <form method="post" action="pos.php" style="display:inline;" onsubmit="return confirm('¿Confirmás que el cliente retiró del lugar el pedido #<?= $pt['idpedido'] ?>?');">
                                            <input type="hidden" name="marcar_retirado_pos" value="1">
                                            <input type="hidden" name="idpedido" value="<?= $pt['idpedido'] ?>">
                                            <button type="submit" class="btn-admin btn-admin-sm" style="background:#10B981;color:#FFFFFF;border:none;font-weight:800;cursor:pointer;">
                                                <i class="fa-solid fa-bell-concierge"></i> Marcar Retirado del Lugar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:0.82rem;color:#15803D;font-weight:700;">
                                            <i class="fa-solid fa-circle-check"></i> Retirado <?= !empty($pt['cajero_nombre']) ? 'por ' . htmlspecialchars($pt['cajero_nombre']) : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
let mpExternalRefAdmin = '';
let mpTotalAdmin = 0;
let mpPollTimerAdmin = null;

function obtenerItemsPOSAdmin() {
    const inputs = document.querySelectorAll('.input-prod-pos');
    const items = [];
    let total = 0;
    inputs.forEach(inp => {
        const cant = parseInt(inp.value || '0', 10);
        if (cant > 0) {
            const precio = parseFloat(inp.dataset.precio || '0');
            total += precio * cant;
            items.push({
                idproducto: inp.dataset.id,
                nombre: inp.dataset.nombre,
                precio: precio,
                cantidad: cant
            });
        }
    });
    return { items, total };
}

function recalcularTotalPOS() {
    const { total } = obtenerItemsPOSAdmin();
    mpTotalAdmin = total;
    const lbl = document.getElementById('lblTotalMPAdmin');
    if (lbl) lbl.textContent = '$' + total.toLocaleString('es-AR');
    const lblPos = document.getElementById('lblTotalPOS');
    if (lblPos) lblPos.textContent = '$' + total.toLocaleString('es-AR');
    actualizarEstadoPOS();
}

function getMetodoPagoPOS() {
    const r = document.querySelector('input[name="metodo_pago"]:checked');
    return r ? r.value : 'efectivo';
}

function togglePanelMPAdmin() {
    const panel = document.getElementById('panelMPAdminPOS');
    recalcularTotalPOS();
    if (getMetodoPagoPOS() === 'mercadopago') {
        panel.style.display = 'block';
        if (mpTotalAdmin > 0) generarPreferenciaMPAdmin();
    } else {
        panel.style.display = 'none';
        if (mpPollTimerAdmin) clearInterval(mpPollTimerAdmin);
    }
}

async function generarPreferenciaMPAdmin() {
    const { items, total } = obtenerItemsPOSAdmin();
    if (total <= 0) {
        alert('Seleccioná al menos un producto con cantidad mayor a 0 para generar el QR de Mercado Pago.');
        return;
    }
    const mesa = document.getElementById('selectMesaPOS').value || 0;
    const cont = document.getElementById('contenidoMPAdmin');
    const status = document.getElementById('statusMPAdmin');
    cont.style.display = 'grid';
    status.textContent = '🔄 Generando orden oficial en Mercado Pago...';

    try {
        const res = await fetch('../mostaza_web/api/pagos/crear_preferencia_pos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ origen: 'ADMINPOS', numero_mesa: mesa, total, items })
        });
        const data = await res.json();
        if (data.ok) {
            mpExternalRefAdmin = data.external_reference;
            document.getElementById('imgQrMPAdmin').src = data.qr_checkout_url;
            document.getElementById('btnLinkMPAdmin').href = data.init_point;
            status.style.background = '#E0F2FE';
            status.style.color = '#0369A1';
            status.textContent = '⏳ Orden generada (' + data.external_reference + '). Esperando pago...';
            if (mpPollTimerAdmin) clearInterval(mpPollTimerAdmin);
            mpPollTimerAdmin = setInterval(() => verificarPagoMPAdmin(false), 4000);
        }
    } catch (e) {
        status.textContent = '⚠️ Error al conectar con la pasarela.';
    }
}

async function verificarPagoMPAdmin(manual) {
    if (!mpExternalRefAdmin) return;
    const status = document.getElementById('statusMPAdmin');
    try {
        const res = await fetch('../mostaza_web/api/pagos/verificar_pago_mp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ external_reference: mpExternalRefAdmin, monto_esperado: mpTotalAdmin })
        });
        const data = await res.json();
        if (data.ok && data.pagado) {
            if (mpPollTimerAdmin) clearInterval(mpPollTimerAdmin);
            status.style.background = '#DCFCE7';
            status.style.color = '#15803D';
            status.textContent = '✅ ¡PAGO ACREDITADO EN MERCADO PAGO! (Op #' + data.payment_id + ')';
        } else if (manual) {
            alert(data.mensaje || 'Todavía no se registra pago aprobado para esta orden.');
        }
    } catch (e) {}
}

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

let tipoPOS = '';

function elegirTipoPOS(tipo) {
    tipoPOS = tipo;
    document.getElementById('inputTipoPOS').value = tipo;
    document.getElementById('selectMesaPOS').value = '';
    document.querySelectorAll('.pos-table-btn').forEach(b => b.classList.remove('selected-table'));
    document.querySelectorAll('.tipo-card').forEach(c => c.classList.toggle('active', c.dataset.tipo === tipo));
    document.getElementById('panelMesas').style.display = (tipo === 'salon') ? 'block' : 'none';
    actualizarEstadoPOS();
    if (tipo === 'takeaway') {
        document.getElementById('form-pos').scrollIntoView({ behavior: 'smooth' });
    }
}

function seleccionarMesaPOS(numero, ocupada) {
    if (tipoPOS !== 'salon') return;
    document.querySelectorAll('.pos-table-btn').forEach(b => b.classList.remove('selected-table'));
    const btn = document.getElementById('btn-mesa-' + numero);
    if (btn) btn.classList.add('selected-table');

    document.getElementById('selectMesaPOS').value = numero;
    window.mesaOcupadaPOS = !!ocupada;
    actualizarEstadoPOS();
    document.getElementById('form-pos').scrollIntoView({ behavior: 'smooth' });
}

function actualizarEstadoPOS() {
    const mesa = document.getElementById('selectMesaPOS').value;
    const retiroListo = (tipoPOS === 'takeaway') || (tipoPOS === 'salon' && mesa !== '');
    const bloque = document.getElementById('bloqueComanda');
    const resumen = document.getElementById('resumenRetiro');

    bloque.classList.toggle('paso-bloqueado', !retiroListo);
    bloque.inert = !retiroListo;

    if (tipoPOS === 'takeaway') {
        resumen.className = 'resumen-retiro ok';
        resumen.innerHTML = '<i class="fa-solid fa-bag-shopping"></i> Retiro: Para llevar (mostrador)';
    } else if (tipoPOS === 'salon' && mesa !== '') {
        resumen.className = 'resumen-retiro ok';
        resumen.innerHTML = '<i class="fa-solid fa-utensils"></i> Retiro: En el salón · Mesa ' + mesa +
            (window.mesaOcupadaPOS ? ' <span class="badge badge-warning">mesa con pedido abierto</span>' : '');
    } else if (tipoPOS === 'salon') {
        resumen.className = 'resumen-retiro';
        resumen.innerHTML = '<i class="fa-solid fa-hand-pointer"></i> Elegí la mesa en el mapa para continuar.';
    } else {
        resumen.className = 'resumen-retiro';
        resumen.innerHTML = '<i class="fa-solid fa-circle-info"></i> Elegí en el paso 1 cómo se retira el pedido para continuar.';
    }

    const { total } = obtenerItemsPOSAdmin();
    document.getElementById('btnEnviarPOS').disabled = !(retiroListo && total > 0);
}

actualizarEstadoPOS();
</script>

</body>
</html>
