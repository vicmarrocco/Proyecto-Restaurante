<?php
// ============================================================
// admin/caja.php
// Arqueo, Apertura, Sangrías y Cierre Z Multiterminal (Caja 1 y Caja 2)
// Mostaza - EPET Nº 7
// ============================================================
session_start();
require('conexion.php');

$mensajeOk = '';
$mensajeError = '';

// Procesar cierre de caja Z
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_caja'])) {
    $idarqueo = intval($_POST['idarqueo']);
    $montoFisico = floatval($_POST['monto_fisico_cierre']);
    $totalEsperado = floatval($_POST['total_esperado']);
    $diferencia = $montoFisico - $totalEsperado;
    $fechaCierre = date('Y-m-d H:i:s');

    $stmtCierre = $conn->prepare("UPDATE caja_arqueo SET monto_fisico_cierre = ?, monto_final_sistema = ?, diferencia = ?, estado = 'Cerrada', fecha_cierre = ? WHERE idarqueo = ?");
    $stmtCierre->bind_param("dddsi", $montoFisico, $totalEsperado, $diferencia, $fechaCierre, $idarqueo);
    if ($stmtCierre->execute()) {
        $mensajeOk = "¡Cierre Z fiscal procesado con éxito para el arqueo #$idarqueo!";
    } else {
        $mensajeError = 'Error al procesar el cierre de caja.';
    }
}

// Procesar apertura de nueva caja (especificando numero_caja)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_caja'])) {
    $numeroCaja = intval($_POST['numero_caja'] ?? 1);
    $cajero = htmlspecialchars(trim($_POST['cajero'] ?? 'Cajero de Turno'));
    $turno = htmlspecialchars(trim($_POST['turno'] ?? 'Turno Mañana'));
    $montoInicial = floatval($_POST['monto_inicial']);
    $terminalNombre = $numeroCaja === 2 ? 'Caja 2 - Express / Salón' : 'Caja 1 - Principal / Mostrador';

    // Verificar si ya hay una caja abierta en ese número
    $check = $conn->prepare("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = ?");
    $check->bind_param("i", $numeroCaja);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $mensajeError = "La Caja $numeroCaja ya tiene un turno abierto actualmente.";
    } else {
        $stmtApertura = $conn->prepare("INSERT INTO caja_arqueo (numero_caja, terminal_nombre, cajero, turno, monto_inicial, estado) VALUES (?, ?, ?, ?, ?, 'Abierta')");
        $stmtApertura->bind_param("isssd", $numeroCaja, $terminalNombre, $cajero, $turno, $montoInicial);
        if ($stmtApertura->execute()) {
            $mensajeOk = "¡Caja $numeroCaja ($terminalNombre) abierta exitosamente!";
        } else {
            $mensajeError = 'Error al abrir la caja.';
        }
    }
}

// Procesar Retiro de Seguridad / Sangría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retiro_seguridad'])) {
    $idarqueo = intval($_POST['idarqueo']);
    $numeroCaja = intval($_POST['numero_caja']);
    $montoRetiro = floatval($_POST['monto_retiro']);
    $motivo = htmlspecialchars(trim($_POST['motivo'] ?? 'Sangría preventiva a caja fuerte / tesorería'));
    $cajero = htmlspecialchars(trim($_POST['cajero'] ?? 'Cajero'));

    if ($montoRetiro <= 0) {
        $mensajeError = 'El monto a retirar debe ser mayor a $0.';
    } else {
        $stmtRetiro = $conn->prepare("INSERT INTO caja_retiro (idarqueo, numero_caja, cajero, monto, motivo) VALUES (?, ?, ?, ?, ?)");
        $stmtRetiro->bind_param("iisds", $idarqueo, $numeroCaja, $cajero, $montoRetiro, $motivo);
        if ($stmtRetiro->execute()) {
            // Actualizar total retiros en arqueo
            $stmtUpd = $conn->prepare("UPDATE caja_arqueo SET monto_retiros = monto_retiros + ? WHERE idarqueo = ?");
            $stmtUpd->bind_param("di", $montoRetiro, $idarqueo);
            $stmtUpd->execute();
            $mensajeOk = "¡Retiro de seguridad de $" . number_format($montoRetiro, 0, ',', '.') . " registrado con éxito en Caja $numeroCaja!";
        } else {
            $mensajeError = 'Error al registrar el retiro.';
        }
    }
}

// Consultar cajas activas (Caja 1 y Caja 2)
$stmtCaja1 = $conn->query("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = 1 ORDER BY idarqueo DESC LIMIT 1");
$caja1 = ($stmtCaja1 && $stmtCaja1->num_rows > 0) ? $stmtCaja1->fetch_assoc() : null;

$stmtCaja2 = $conn->query("SELECT * FROM caja_arqueo WHERE estado = 'Abierta' AND numero_caja = 2 ORDER BY idarqueo DESC LIMIT 1");
$caja2 = ($stmtCaja2 && $stmtCaja2->num_rows > 0) ? $stmtCaja2->fetch_assoc() : null;

// Consultar historial de arqueos y retiros
$historialCajas = $conn->query("SELECT * FROM caja_arqueo ORDER BY idarqueo DESC LIMIT 20");
$ultimosRetiros = $conn->query("SELECT * FROM caja_retiro ORDER BY idretiro DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control de Caja Multiterminal · Mostaza Central</title>
<link rel="stylesheet" href="admin.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.terminal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 28px;
}
@media (max-width: 1024px) {
    .terminal-grid { grid-template-columns: 1fr; }
}
.badge-caja1 { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
.badge-caja2 { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
.alert-sangria {
    background: #FFF1F2;
    border: 1.5px solid #E11D48;
    color: #9F1239;
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 700;
    margin-top: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: pulseAlert 2s infinite ease-in-out;
}
@keyframes pulseAlert {
    0%, 100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(225, 29, 72, 0); }
}

/* Estilo Ticket Térmico */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.75);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.active { display: flex; }
.ticket-thermal {
    background: #FFFDF7;
    width: 100%;
    max-width: 380px;
    border-radius: 6px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    font-family: 'Courier Prime', monospace, monospace;
    font-size: 13px;
    line-height: 1.35;
    color: #111;
    padding: 20px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
    border-top: 4px dashed #D1D5DB;
    border-bottom: 4px dashed #D1D5DB;
}
.ticket-thermal .t-center { text-align: center; }
.ticket-thermal .t-dashed { border-bottom: 1px dashed #4B5563; margin: 8px 0; }
.ticket-thermal .t-double { border-bottom: 2px dashed #111; margin: 8px 0; }
.ticket-thermal .t-row { display: flex; justify-content: space-between; margin: 3px 0; }
.ticket-thermal .t-bold { font-weight: 700; }
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
        <li><a href="caja.php" class="active"><i class="fa-solid fa-vault"></i> Control de Caja</a></li>
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
            <h1>Control de Caja & Tesorería Multiterminal</h1>
            <p>Monitoreo independiente en vivo de <strong>Caja 1 (Mostrador)</strong> y <strong>Caja 2 (Express)</strong> · Sangrías y Cierres Fiscales Z.</p>
        </div>
    </div>

    <?php if ($mensajeOk): ?><div style="background:#DCFCE7;color:#166534;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #BBF7D0;"><i class="fa-solid fa-circle-check"></i> <?= $mensajeOk ?></div><?php endif; ?>
    <?php if ($mensajeError): ?><div style="background:#FEE2E2;color:#991B1B;padding:14px;border-radius:10px;margin-bottom:20px;font-weight:700;border:1px solid #FCA5A5;"><i class="fa-solid fa-triangle-exclamation"></i> <?= $mensajeError ?></div><?php endif; ?>

    <!-- PANELES MULTITERMINAL: CAJA 1 Y CAJA 2 -->
    <div class="terminal-grid">

        <!-- =================== TERMINAL 1 =================== -->
        <div class="admin-card" style="border-top: 5px solid #A30002;">
            <div class="admin-card-header">
                <div>
                    <span class="badge badge-caja1" style="font-weight:900;letter-spacing:1px;">P.V. 0001 · TERMINAL 01</span>
                    <h2 style="margin-top:6px;color:#0F172A;">Caja 1 - Mostrador Principal</h2>
                </div>
                <?php if ($caja1): ?>
                    <span class="badge badge-success" style="font-size:0.85rem;padding:6px 12px;">● ABIERTA</span>
                <?php else: ?>
                    <span class="badge badge-muted" style="font-size:0.85rem;padding:6px 12px;">○ CERRADA</span>
                <?php endif; ?>
            </div>

            <?php if ($caja1): ?>
                <?php
                $esperado1 = $caja1['monto_inicial'] + $caja1['ventas_efectivo'] - ($caja1['monto_retiros'] ?? 0);
                $alerta1 = $esperado1 >= 150000;
                ?>
                <div style="font-size:0.85rem;color:#64748B;margin-bottom:14px;">
                    Cajero: <strong style="color:#0F172A;"><?= htmlspecialchars($caja1['cajero']) ?></strong> · Turno: <strong><?= htmlspecialchars($caja1['turno']) ?></strong><br>
                    Apertura: <?= date('d/m/Y H:i', strtotime($caja1['fecha_apertura'])) ?>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0;">
                    <div style="background:#F8FAFC;padding:12px;border-radius:10px;border:1px solid #E2E8F0;">
                        <span style="font-size:0.75rem;color:#64748B;font-weight:700;">Fondo Inicial</span>
                        <strong style="display:block;font-size:1.15rem;color:#0F172A;">$<?= number_format($caja1['monto_inicial'], 0, ',', '.') ?></strong>
                    </div>
                    <div style="background:#F0FDF4;padding:12px;border-radius:10px;border:1px solid #BBF7D0;">
                        <span style="font-size:0.75rem;color:#166534;font-weight:700;">Ventas Efectivo</span>
                        <strong style="display:block;font-size:1.15rem;color:#166534;">+$<?= number_format($caja1['ventas_efectivo'], 0, ',', '.') ?></strong>
                    </div>
                    <div style="background:#FFF1F2;padding:12px;border-radius:10px;border:1px solid #FECDD3;">
                        <span style="font-size:0.75rem;color:#9F1239;font-weight:700;">Retiros / Sangrías</span>
                        <strong style="display:block;font-size:1.15rem;color:#E11D48;">-$<?= number_format($caja1['monto_retiros'] ?? 0, 0, ',', '.') ?></strong>
                    </div>
                    <div style="background:#FEF3C7;padding:12px;border-radius:10px;border:1px solid #FDE68A;">
                        <span style="font-size:0.75rem;color:#92400E;font-weight:800;">Efectivo en Gaveta</span>
                        <strong style="display:block;font-size:1.25rem;color:#B45309;">$<?= number_format($esperado1, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <div style="background:#F1F5F9;padding:10px 14px;border-radius:8px;font-size:0.8rem;color:#475569;display:flex;justify-content:space-between;margin-bottom:14px;">
                    <span>Tarjetas: <strong>$<?= number_format($caja1['ventas_tarjeta'], 0, ',', '.') ?></strong></span>
                    <span>Mercado Pago: <strong>$<?= number_format($caja1['ventas_mercadopago'], 0, ',', '.') ?></strong></span>
                    <span>Total Ventas: <strong style="color:#A30002;">$<?= number_format($caja1['total_ventas'], 0, ',', '.') ?></strong></span>
                </div>

                <?php if ($alerta1): ?>
                <div class="alert-sangria">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.4rem;"></i>
                    <div>
                        <strong>ALERTA DE SEGURIDAD:</strong> Efectivo supera los $150.000 ($<?= number_format($esperado1, 0, ',', '.') ?>). Realizar sangría a tesorería.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acciones Operativas Caja 1 -->
                <div style="display:flex;gap:10px;margin-top:16px;">
                    <button type="button" onclick="mostrarModalSangria(<?= $caja1['idarqueo'] ?>, 1, '<?= htmlspecialchars($caja1['cajero']) ?>', <?= $esperado1 ?>)" class="btn-admin btn-admin-white" style="flex:1;font-size:0.85rem;color:#E11D48;border-color:#FDA4AF;">
                        <i class="fa-solid fa-money-bill-transfer"></i> Sangría a Tesorería
                    </button>
                    <button type="button" onclick="mostrarModalCierre(<?= $caja1['idarqueo'] ?>, 1, <?= $esperado1 ?>, '<?= htmlspecialchars($caja1['cajero']) ?>')" class="btn-admin btn-admin-red" style="flex:1;font-size:0.85rem;">
                        <i class="fa-solid fa-lock"></i> Emitir Cierre Z
                    </button>
                </div>

            <?php else: ?>
                <!-- Formulario Apertura Caja 1 -->
                <p style="color:#64748B;font-size:0.85rem;margin-bottom:16px;">El terminal de Mostrador Principal se encuentra cerrado. Abrir turno para comenzar a facturar.</p>
                <form method="post" action="caja.php">
                    <input type="hidden" name="abrir_caja" value="1">
                    <input type="hidden" name="numero_caja" value="1">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="form-label">Cajero Responsable:</label>
                        <input type="text" name="cajero" value="Alberto Gómez (Cajero 1)" required class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="form-label">Turno Operativo:</label>
                        <select name="turno" class="form-control">
                            <option value="Turno Mañana (08:00 - 16:00)">Turno Mañana (08:00 - 16:00)</option>
                            <option value="Turno Tarde/Noche (16:00 - 00:00)">Turno Tarde/Noche (16:00 - 00:00)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label class="form-label">Fondo Inicial ($):</label>
                        <input type="number" name="monto_inicial" value="15000" step="500" required class="form-control">
                    </div>
                    <button type="submit" class="btn-admin btn-admin-red" style="width:100%;padding:10px;">
                        <i class="fa-solid fa-key"></i> Iniciar Turno Caja 1
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- =================== TERMINAL 2 =================== -->
        <div class="admin-card" style="border-top: 5px solid #D4930A;">
            <div class="admin-card-header">
                <div>
                    <span class="badge badge-caja2" style="font-weight:900;letter-spacing:1px;">P.V. 0002 · TERMINAL 02</span>
                    <h2 style="margin-top:6px;color:#0F172A;">Caja 2 - Express / Salón</h2>
                </div>
                <?php if ($caja2): ?>
                    <span class="badge badge-success" style="font-size:0.85rem;padding:6px 12px;">● ABIERTA</span>
                <?php else: ?>
                    <span class="badge badge-muted" style="font-size:0.85rem;padding:6px 12px;">○ CERRADA</span>
                <?php endif; ?>
            </div>

            <?php if ($caja2): ?>
                <?php
                $esperado2 = $caja2['monto_inicial'] + $caja2['ventas_efectivo'] - ($caja2['monto_retiros'] ?? 0);
                $alerta2 = $esperado2 >= 150000;
                ?>
                <div style="font-size:0.85rem;color:#64748B;margin-bottom:14px;">
                    Cajero: <strong style="color:#0F172A;"><?= htmlspecialchars($caja2['cajero']) ?></strong> · Turno: <strong><?= htmlspecialchars($caja2['turno']) ?></strong><br>
                    Apertura: <?= date('d/m/Y H:i', strtotime($caja2['fecha_apertura'])) ?>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0;">
                    <div style="background:#F8FAFC;padding:12px;border-radius:10px;border:1px solid #E2E8F0;">
                        <span style="font-size:0.75rem;color:#64748B;font-weight:700;">Fondo Inicial</span>
                        <strong style="display:block;font-size:1.15rem;color:#0F172A;">$<?= number_format($caja2['monto_inicial'], 0, ',', '.') ?></strong>
                    </div>
                    <div style="background:#F0FDF4;padding:12px;border-radius:10px;border:1px solid #BBF7D0;">
                        <span style="font-size:0.75rem;color:#166534;font-weight:700;">Ventas Efectivo</span>
                        <strong style="display:block;font-size:1.15rem;color:#166534;">+$<?= number_format($caja2['ventas_efectivo'], 0, ',', '.') ?></strong>
                    </div>
                    <div style="background:#FFF1F2;padding:12px;border-radius:10px;border:1px solid #FECDD3;">
                        <span style="font-size:0.75rem;color:#9F1239;font-weight:700;">Retiros / Sangrías</span>
                        <strong style="display:block;font-size:1.15rem;color:#E11D48;">-$<?= number_format($caja2['monto_retiros'] ?? 0, 0, ',', '.') ?></strong>
                    </div>
                    <div style="background:#FEF3C7;padding:12px;border-radius:10px;border:1px solid #FDE68A;">
                        <span style="font-size:0.75rem;color:#92400E;font-weight:800;">Efectivo en Gaveta</span>
                        <strong style="display:block;font-size:1.25rem;color:#B45309;">$<?= number_format($esperado2, 0, ',', '.') ?></strong>
                    </div>
                </div>

                <div style="background:#F1F5F9;padding:10px 14px;border-radius:8px;font-size:0.8rem;color:#475569;display:flex;justify-content:space-between;margin-bottom:14px;">
                    <span>Tarjetas: <strong>$<?= number_format($caja2['ventas_tarjeta'], 0, ',', '.') ?></strong></span>
                    <span>Mercado Pago: <strong>$<?= number_format($caja2['ventas_mercadopago'], 0, ',', '.') ?></strong></span>
                    <span>Total Ventas: <strong style="color:#D4930A;">$<?= number_format($caja2['total_ventas'], 0, ',', '.') ?></strong></span>
                </div>

                <?php if ($alerta2): ?>
                <div class="alert-sangria">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size:1.4rem;"></i>
                    <div>
                        <strong>ALERTA DE SEGURIDAD:</strong> Efectivo supera los $150.000 ($<?= number_format($esperado2, 0, ',', '.') ?>). Realizar sangría a tesorería.
                    </div>
                </div>
                <?php endif; ?>

                <!-- Acciones Operativas Caja 2 -->
                <div style="display:flex;gap:10px;margin-top:16px;">
                    <button type="button" onclick="mostrarModalSangria(<?= $caja2['idarqueo'] ?>, 2, '<?= htmlspecialchars($caja2['cajero']) ?>', <?= $esperado2 ?>)" class="btn-admin btn-admin-white" style="flex:1;font-size:0.85rem;color:#E11D48;border-color:#FDA4AF;">
                        <i class="fa-solid fa-money-bill-transfer"></i> Sangría a Tesorería
                    </button>
                    <button type="button" onclick="mostrarModalCierre(<?= $caja2['idarqueo'] ?>, 2, <?= $esperado2 ?>, '<?= htmlspecialchars($caja2['cajero']) ?>')" class="btn-admin btn-admin-red" style="flex:1;font-size:0.85rem;background:#B45309;border-color:#B45309;">
                        <i class="fa-solid fa-lock"></i> Emitir Cierre Z
                    </button>
                </div>

            <?php else: ?>
                <!-- Formulario Apertura Caja 2 -->
                <p style="color:#64748B;font-size:0.85rem;margin-bottom:16px;">El terminal Express / Salón se encuentra cerrado. Abrir turno para comenzar a facturar.</p>
                <form method="post" action="caja.php">
                    <input type="hidden" name="abrir_caja" value="1">
                    <input type="hidden" name="numero_caja" value="2">
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="form-label">Cajero Responsable:</label>
                        <input type="text" name="cajero" value="Romina Castro (Cajero 2)" required class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom:10px;">
                        <label class="form-label">Turno Operativo:</label>
                        <select name="turno" class="form-control">
                            <option value="Turno Mañana (08:00 - 16:00)">Turno Mañana (08:00 - 16:00)</option>
                            <option value="Turno Tarde/Noche (16:00 - 00:00)">Turno Tarde/Noche (16:00 - 00:00)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:14px;">
                        <label class="form-label">Fondo Inicial ($):</label>
                        <input type="number" name="monto_inicial" value="10000" step="500" required class="form-control">
                    </div>
                    <button type="submit" class="btn-admin" style="width:100%;padding:10px;background:#B45309;color:#FFF;">
                        <i class="fa-solid fa-key"></i> Iniciar Turno Caja 2
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>

    <!-- TABLA DE RETIROS DE SEGURIDAD RECIENTES -->
    <?php if ($ultimosRetiros && $ultimosRetiros->num_rows > 0): ?>
    <div class="admin-card" style="margin-bottom:28px;">
        <div class="admin-card-header">
            <h2><i class="fa-solid fa-shield-halved" style="color:#E11D48;"></i> Retiros de Seguridad a Tesorería (Sangrías)</h2>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>N.º Retiro</th>
                        <th>Terminal</th>
                        <th>Cajero</th>
                        <th>Monto Retirado</th>
                        <th>Motivo / Destino</th>
                        <th>Fecha y Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $ultimosRetiros->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $r['idretiro'] ?></strong></td>
                        <td>
                            <span class="badge <?= $r['numero_caja'] == 2 ? 'badge-caja2' : 'badge-caja1' ?>">
                                Caja <?= $r['numero_caja'] ?>
                            </span>
                        </td>
                        <td><strong><?= htmlspecialchars($r['cajero']) ?></strong></td>
                        <td style="color:#E11D48;font-weight:900;font-size:1rem;">
                            -$<?= number_format($r['monto'], 0, ',', '.') ?>
                        </td>
                        <td><?= htmlspecialchars($r['motivo']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['fecha_hora'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- HISTORIAL GENERAL DE ARQUEOS Y CIERRES Z -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> Historial de Arqueos y Cierres Fiscales Z</h2>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>N.º Arqueo</th>
                        <th>Terminal</th>
                        <th>Cajero / Turno</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Fondo Inicial</th>
                        <th>Ventas Totales</th>
                        <th>Retiros</th>
                        <th>Físico Contado</th>
                        <th>Diferencia</th>
                        <th>Estado</th>
                        <th>Ticket Z</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$historialCajas || $historialCajas->num_rows === 0): ?>
                        <tr><td colspan="12" style="text-align:center;color:#64748B;padding:30px;">No hay registros de arqueo aún.</td></tr>
                    <?php endif; ?>

                    <?php while ($h = $historialCajas->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= $h['idarqueo'] ?></strong></td>
                        <td>
                            <span class="badge <?= ($h['numero_caja'] ?? 1) == 2 ? 'badge-caja2' : 'badge-caja1' ?>" style="font-size:0.75rem;">
                                <?= htmlspecialchars($h['terminal_nombre'] ?? ('Caja ' . ($h['numero_caja'] ?? 1))) ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($h['cajero']) ?></strong><br>
                            <span style="font-size:0.75rem;color:#64748B;"><?= htmlspecialchars($h['turno']) ?></span>
                        </td>
                        <td><?= date('d/m H:i', strtotime($h['fecha_apertura'])) ?></td>
                        <td><?= $h['fecha_cierre'] ? date('d/m H:i', strtotime($h['fecha_cierre'])) : '<span class="badge badge-warning">En curso</span>' ?></td>
                        <td>$<?= number_format($h['monto_inicial'], 0, ',', '.') ?></td>
                        <td><strong>$<?= number_format($h['total_ventas'], 0, ',', '.') ?></strong></td>
                        <td style="color:#E11D48;">$<?= number_format($h['monto_retiros'] ?? 0, 0, ',', '.') ?></td>
                        <td>$<?= number_format($h['monto_fisico_cierre'] ?? 0, 0, ',', '.') ?></td>
                        <td style="color:<?= ($h['diferencia'] < 0) ? '#EF4444' : (($h['diferencia'] > 0) ? '#2563EB' : '#10B981') ?>;font-weight:700;">
                            <?= ($h['estado'] === 'Cerrada') ? ('$' . number_format($h['diferencia'] ?? 0, 0, ',', '.')) : '-' ?>
                        </td>
                        <td>
                            <span class="badge <?= $h['estado'] === 'Abierta' ? 'badge-success' : 'badge-muted' ?>">
                                <?= $h['estado'] ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" onclick="cargarTicketZ(<?= $h['idarqueo'] ?>)" class="btn-admin btn-admin-white btn-admin-sm" title="Ver Ticket Térmico Cierre Z">
                                <i class="fa-solid fa-receipt"></i> Ticket Z
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- ================= MODAL SANGRÍA / RETIRO ================= -->
<div id="modalSangria" class="modal-overlay">
    <div style="background:#FFFFFF;border-radius:14px;padding:24px;width:100%;max-width:440px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;color:#9F1239;"><i class="fa-solid fa-shield-halved"></i> Retiro de Seguridad / Sangría</h3>
            <button type="button" onclick="cerrarModal('modalSangria')" style="border:none;background:transparent;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form method="post" action="caja.php">
            <input type="hidden" name="retiro_seguridad" value="1">
            <input type="hidden" name="idarqueo" id="sangriaIdArqueo">
            <input type="hidden" name="numero_caja" id="sangriaNumCaja">
            <input type="hidden" name="cajero" id="sangriaCajero">

            <div style="background:#F8FAFC;padding:12px;border-radius:8px;border:1px solid #E2E8F0;margin-bottom:14px;font-size:0.85rem;">
                Terminal: <strong id="sangriaTerminalTexto">Caja 1</strong><br>
                Cajero: <strong id="sangriaCajeroTexto">Alberto</strong><br>
                Efectivo en Gaveta: <strong id="sangriaEfectivoMax" style="color:#B45309;">$0</strong>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Monto a Retirar a Caja Fuerte ($):</label>
                <input type="number" name="monto_retiro" id="sangriaMonto" placeholder="Ej: 100000" step="500" required class="form-control">
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Motivo del Retiro:</label>
                <input type="text" name="motivo" value="Sangría de seguridad por exceso de efectivo en gaveta" required class="form-control">
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModal('modalSangria')" class="btn-admin btn-admin-white" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-admin btn-admin-red" style="flex:1;"><i class="fa-solid fa-check"></i> Confirmar Retiro</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL CIERRE Z ================= -->
<div id="modalCierre" class="modal-overlay">
    <div style="background:#FFFFFF;border-radius:14px;padding:24px;width:100%;max-width:480px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;color:#0F172A;"><i class="fa-solid fa-lock" style="color:#A30002;"></i> Arqueo Físico y Cierre Z</h3>
            <button type="button" onclick="cerrarModal('modalCierre')" style="border:none;background:transparent;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form method="post" action="caja.php">
            <input type="hidden" name="cerrar_caja" value="1">
            <input type="hidden" name="idarqueo" id="cierreIdArqueo">
            <input type="hidden" name="total_esperado" id="cierreTotalEsperado">

            <div style="background:#F8FAFC;padding:12px;border-radius:8px;border:1px solid #E2E8F0;margin-bottom:14px;font-size:0.85rem;">
                Terminal: <strong id="cierreTerminalTexto">Caja 1</strong> · Cajero: <strong id="cierreCajeroTexto">Alberto</strong><br>
                Efectivo Teórico Esperado: <strong id="cierreEsperadoTexto" style="color:#166534;font-size:1rem;">$0</strong>
            </div>

            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Efectivo Físico Contado en Gaveta ($):</label>
                <input type="number" name="monto_fisico_cierre" id="cierreMontoFisico" placeholder="0" step="100" required class="form-control" oninput="actualizarDiffCierre()">
            </div>

            <div style="margin-bottom:16px;">
                <label class="form-label">Diferencia de Cuadre:</label>
                <div id="cierreDiffDisplay" style="padding:10px;border-radius:8px;font-weight:800;font-size:1rem;background:#F1F5F9;color:#475569;border:1px solid #CBD5E1;">
                    Ingresá el conteo físico...
                </div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="cerrarModal('modalCierre')" class="btn-admin btn-admin-white" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn-admin btn-admin-red" style="flex:1.5;"><i class="fa-solid fa-lock"></i> Confirmar Cierre Z</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL TICKET TÉRMICO Z ================= -->
<div id="modalTicketZ" class="modal-overlay">
    <div style="display:flex;flex-direction:column;align-items:center;">
        <div id="ticketContenido" class="ticket-thermal">
            <div class="t-center">
                <div class="t-bold" style="font-size:16px;">MOSTAZA ARGENTINA S.A.</div>
                <div>SUCURSAL 01 · FORMOSA CENTRO</div>
                <div>CUIT: 30-71123456-9</div>
                <div class="t-dashed"></div>
                <div class="t-bold" style="font-size:14px;">INFORME DE CIERRE FISCAL "Z"</div>
                <div id="tzTerminal" class="t-bold">CAJA 01 · P.V. 0001</div>
                <div class="t-dashed"></div>
            </div>

            <div class="t-row"><span>ARQUEO FISCAL N.º:</span><span id="tzId" class="t-bold">#0</span></div>
            <div class="t-row"><span>CAJERO RESPONSABLE:</span><span id="tzCajero" class="t-bold">-</span></div>
            <div class="t-row"><span>TURNO OPERATIVO:</span><span id="tzTurno">-</span></div>
            <div class="t-row"><span>APERTURA:</span><span id="tzApertura">-</span></div>
            <div class="t-row"><span>CIERRE Z:</span><span id="tzCierre">-</span></div>

            <div class="t-dashed"></div>
            <div class="t-center t-bold">--- DESGLOSE DE VENTAS ---</div>
            <div class="t-row"><span>VENTAS EFECTIVO:</span><span id="tzVentasEfectivo">$0</span></div>
            <div class="t-row"><span>VENTAS TARJETA D/C:</span><span id="tzVentasTarjeta">$0</span></div>
            <div class="t-row"><span>VENTAS MERCADO PAGO:</span><span id="tzVentasMP">$0</span></div>
            <div class="t-double"></div>
            <div class="t-row t-bold" style="font-size:14px;"><span>TOTAL VENTAS TURNO:</span><span id="tzTotalVentas">$0</span></div>

            <div class="t-dashed"></div>
            <div class="t-center t-bold">--- ARQUEO DE GAVETA ---</div>
            <div class="t-row"><span>(+) FONDO INICIAL:</span><span id="tzFondoInicial">$0</span></div>
            <div class="t-row"><span>(+) INGRESOS EFECTIVO:</span><span id="tzIngresosEfectivo">$0</span></div>
            <div class="t-row"><span>(-) RETIROS / SANGRÍAS:</span><span id="tzRetiros">$0</span></div>
            <div class="t-dashed"></div>
            <div class="t-row t-bold"><span>(=) EFECTIVO TEÓRICO:</span><span id="tzTeorico">$0</span></div>
            <div class="t-row t-bold"><span>(=) EFECTIVO FÍSICO:</span><span id="tzFisico">$0</span></div>
            <div class="t-double"></div>
            <div class="t-row t-bold" style="font-size:14px;" id="tzRowDiff">
                <span>DIFERENCIA:</span><span id="tzDiferencia">$0</span>
            </div>

            <div class="t-dashed"></div>
            <div class="t-center" style="font-size:10px;margin-top:10px;">
                *** DOCUMENTO NO FISCAL DE CONTROL INTERNO ***<br>
                CONSERVAR POR 2 AÑOS PARA AUDITORÍA GERENCIAL<br>
                SISTEMA MOSTAZA POS v2.5
            </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:14px;">
            <button type="button" onclick="imprimirTicket()" class="btn-admin btn-admin-red" style="padding:10px 20px;">
                <i class="fa-solid fa-print"></i> Imprimir Ticket Z
            </button>
            <button type="button" onclick="cerrarModal('modalTicketZ')" class="btn-admin btn-admin-white" style="padding:10px 20px;">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
}

function mostrarModalSangria(idarqueo, numeroCaja, cajero, maxEfectivo) {
    document.getElementById('sangriaIdArqueo').value = idarqueo;
    document.getElementById('sangriaNumCaja').value = numeroCaja;
    document.getElementById('sangriaCajero').value = cajero;
    document.getElementById('sangriaTerminalTexto').textContent = 'Caja ' + numeroCaja + (numeroCaja === 2 ? ' (Express)' : ' (Mostrador)');
    document.getElementById('sangriaCajeroTexto').textContent = cajero;
    document.getElementById('sangriaEfectivoMax').textContent = '$' + maxEfectivo.toLocaleString('es-AR');
    document.getElementById('sangriaMonto').value = Math.max(0, maxEfectivo - 15000);
    document.getElementById('modalSangria').classList.add('active');
}

function mostrarModalCierre(idarqueo, numeroCaja, esperado, cajero) {
    document.getElementById('cierreIdArqueo').value = idarqueo;
    document.getElementById('cierreTotalEsperado').value = esperado;
    document.getElementById('cierreTerminalTexto').textContent = 'Caja ' + numeroCaja + (numeroCaja === 2 ? ' (Express)' : ' (Mostrador)');
    document.getElementById('cierreCajeroTexto').textContent = cajero;
    document.getElementById('cierreEsperadoTexto').textContent = '$' + esperado.toLocaleString('es-AR');
    document.getElementById('cierreMontoFisico').value = esperado;
    actualizarDiffCierre();
    document.getElementById('modalCierre').classList.add('active');
}

function actualizarDiffCierre() {
    const esperado = parseFloat(document.getElementById('cierreTotalEsperado').value) || 0;
    const fisico = parseFloat(document.getElementById('cierreMontoFisico').value) || 0;
    const diff = fisico - esperado;
    const display = document.getElementById('cierreDiffDisplay');

    if (diff === 0) {
        display.textContent = '$0 (Cuadre Exacto)';
        display.style.color = '#166534';
        display.style.background = '#DCFCE7';
    } else if (diff > 0) {
        display.textContent = '+$' + diff.toLocaleString('es-AR') + ' (Sobrante en gaveta)';
        display.style.color = '#1E40AF';
        display.style.background = '#DBEAFE';
    } else {
        display.textContent = '-$' + Math.abs(diff).toLocaleString('es-AR') + ' (Faltante en gaveta)';
        display.style.color = '#991B1B';
        display.style.background = '#FEE2E2';
    }
}

async function cargarTicketZ(idarqueo) {
    try {
        const res = await fetch(`../mostaza_web/api/caja/movimiento.php?accion=informe_cierre&idarqueo=${idarqueo}`);
        const data = await res.json();
        if (!data.ok || !data.informe) {
            alert('No se pudo obtener el informe de cierre para el arqueo #' + idarqueo);
            return;
        }
        const inf = data.informe;
        document.getElementById('tzId').textContent = '#' + inf.idarqueo;
        document.getElementById('tzTerminal').textContent = (inf.terminal_nombre || ('Caja ' + inf.numero_caja)).toUpperCase();
        document.getElementById('tzCajero').textContent = inf.cajero;
        document.getElementById('tzTurno').textContent = inf.turno;
        document.getElementById('tzApertura').textContent = inf.fecha_apertura;
        document.getElementById('tzCierre').textContent = inf.fecha_cierre || 'En curso';
        document.getElementById('tzVentasEfectivo').textContent = '$' + Number(inf.ventas_efectivo).toLocaleString('es-AR');
        document.getElementById('tzVentasTarjeta').textContent = '$' + Number(inf.ventas_tarjeta).toLocaleString('es-AR');
        document.getElementById('tzVentasMP').textContent = '$' + Number(inf.ventas_mercadopago).toLocaleString('es-AR');
        document.getElementById('tzTotalVentas').textContent = '$' + Number(inf.total_ventas).toLocaleString('es-AR');
        document.getElementById('tzFondoInicial').textContent = '$' + Number(inf.monto_inicial).toLocaleString('es-AR');
        document.getElementById('tzIngresosEfectivo').textContent = '$' + Number(inf.ventas_efectivo).toLocaleString('es-AR');
        document.getElementById('tzRetiros').textContent = '$' + Number(inf.monto_retiros || 0).toLocaleString('es-AR');
        document.getElementById('tzTeorico').textContent = '$' + Number(inf.total_efectivo_esperado || inf.monto_final_sistema || 0).toLocaleString('es-AR');
        document.getElementById('tzFisico').textContent = '$' + Number(inf.monto_fisico_cierre || 0).toLocaleString('es-AR');

        const diff = Number(inf.diferencia || 0);
        const diffEl = document.getElementById('tzDiferencia');
        if (diff === 0) {
            diffEl.textContent = '$0 (CUADRADO)';
            diffEl.style.color = '#166534';
        } else if (diff > 0) {
            diffEl.textContent = '+$' + diff.toLocaleString('es-AR') + ' (SOBRANTE)';
            diffEl.style.color = '#1E40AF';
        } else {
            diffEl.textContent = '-$' + Math.abs(diff).toLocaleString('es-AR') + ' (FALTANTE)';
            diffEl.style.color = '#991B1B';
        }

        document.getElementById('modalTicketZ').classList.add('active');
    } catch (e) {
        alert('Error al comunicar con la API: ' + e.message);
    }
}

function imprimirTicket() {
    const printContent = document.getElementById('ticketContenido').outerHTML;
    const win = window.open('', '_blank', 'width=420,height=600');
    win.document.write(`
        <html>
        <head>
            <title>Ticket Cierre Z</title>
            <style>
                body { font-family: monospace; font-size: 13px; margin: 20px; line-height: 1.35; }
                .t-center { text-align: center; }
                .t-dashed { border-bottom: 1px dashed #4B5563; margin: 8px 0; }
                .t-double { border-bottom: 2px dashed #111; margin: 8px 0; }
                .t-row { display: flex; justify-content: space-between; margin: 3px 0; }
                .t-bold { font-weight: 700; }
            </style>
        </head>
        <body onload="window.print();window.close();">
            ${printContent}
        </body>
        </html>
    `);
    win.document.close();
}
</script>

</body>
</html>
