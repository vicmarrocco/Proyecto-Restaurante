<?php
// ============================================================
// ticket_pdf.php
// Comprobante / Ticket fiscal imprimible y descargable en PDF.
// ============================================================
session_start();
require('conexion.php');

$idpedido = intval($_GET['id'] ?? 0);
$idusuario = $_SESSION['usuario_id'] ?? 0;

if (!$idpedido || !$idusuario) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT p.*, f.numero_factura, f.tipo_comprobante, f.cae, f.cae_vto, f.neto_gravado, f.iva_21, u.nombre, u.apellido, u.email, u.telefono FROM pedido_web p LEFT JOIN factura f ON p.idpedido = f.idpedido JOIN usuario_web u ON p.idusuario = u.idusuario WHERE p.idpedido = ? AND p.idusuario = ?");
$stmt->bind_param("ii", $idpedido, $idusuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header("Location: index.php");
    exit();
}

$stmtDet = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
$stmtDet->bind_param("i", $idpedido);
$stmtDet->execute();
$detalle = $stmtDet->get_result();

$netoGravado = !empty($pedido['neto_gravado']) ? floatval($pedido['neto_gravado']) : ($pedido['total'] / 1.21);
$iva21 = !empty($pedido['iva_21']) ? floatval($pedido['iva_21']) : ($pedido['total'] - $netoGravado);
$numFactura = $pedido['numero_factura'] ?? ('FAC-B-0001-' . str_pad($pedido['idpedido'], 8, '0', STR_PAD_LEFT));
$caeNum = $pedido['cae'] ?? '74218392019482';
$caeVto = $pedido['cae_vto'] ? date('d/m/Y', strtotime($pedido['cae_vto'])) : date('d/m/Y', strtotime('+10 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ticket Factura #<?= $pedido['idpedido'] ?> - Mostaza</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Courier New', Courier, monospace, sans-serif;
    background: #525659;
    color: #111;
    display: flex;
    justify-content: center;
    padding: 30px 15px;
}
.ticket-paper {
    background: #fff;
    width: 100%;
    max-width: 420px;
    padding: 30px 24px;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.ticket-header {
    text-align: center;
    border-bottom: 2px dashed #000;
    padding-bottom: 16px;
    margin-bottom: 16px;
}
.ticket-header h1 {
    font-size: 1.8rem;
    font-weight: 900;
    letter-spacing: 2px;
    color: #000;
}
.ticket-header p {
    font-size: 0.8rem;
    line-height: 1.3;
}
.ticket-info-block {
    font-size: 0.8rem;
    margin-bottom: 14px;
    line-height: 1.4;
}
.ticket-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
    margin: 14px 0;
}
.ticket-table th {
    border-top: 1px dashed #000;
    border-bottom: 1px dashed #000;
    padding: 6px 0;
    text-align: left;
}
.ticket-table td {
    padding: 6px 0;
    vertical-align: top;
}
.ticket-totals {
    border-top: 1px dashed #000;
    padding-top: 10px;
    font-size: 0.85rem;
}
.ticket-totals-row {
    display: flex;
    justify-content: space-between;
    padding: 3px 0;
}
.ticket-totals-row.total-final {
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
    margin-top: 6px;
    padding: 8px 0;
    font-size: 1.2rem;
    font-weight: 900;
}
.ticket-footer {
    text-align: center;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 2px dashed #000;
    font-size: 0.75rem;
}
.ticket-qr {
    margin: 12px auto;
    width: 110px;
    height: 110px;
}
.ticket-qr img {
    width: 100%;
    height: 100%;
}
.actions-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #111;
    padding: 12px 24px;
    border-radius: 50px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    display: flex;
    gap: 12px;
}
.btn-action {
    background: #A30002;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 50px;
    font-family: Arial, sans-serif;
    font-weight: bold;
    font-size: 0.9rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-action.secondary {
    background: #374151;
}

@media print {
    body {
        background: #fff;
        padding: 0;
    }
    .ticket-paper {
        box-shadow: none;
        max-width: 100%;
        padding: 0;
    }
    .actions-bar {
        display: none !important;
    }
}
</style>
</head>
<body>

<div class="ticket-paper">

    <div class="ticket-header">
        <h1>MOSTAZA</h1>
        <p><strong>MOSTAZA GASTRONOMÍA S.A.</strong></p>
        <p>CUIT: 30-71234567-9 · IVA RESPONSABLE INSCRIPTO</p>
        <p><?= htmlspecialchars($pedido['sucursal'] ?? 'Mostaza Central') ?></p>
        <p>Ingresos Brutos: 30-71234567-9</p>
        <p>Inicio de Actividades: 01/10/1999</p>
    </div>

    <div class="ticket-info-block">
        <div><strong>FACTURA "B" N.º:</strong> <?= htmlspecialchars($numFactura) ?></div>
        <div><strong>COMANDA GENERAL:</strong> #<?= $pedido['idpedido'] ?></div>
        <?php if (!empty($pedido['numero_pedido_usuario'])): ?>
            <div><strong>PEDIDO DEL CLIENTE:</strong> #<?= $pedido['numero_pedido_usuario'] ?></div>
        <?php endif; ?>
        <div><strong>FECHA / HORA:</strong> <?= date('d/m/Y H:i:s', strtotime($pedido['fecha'])) ?></div>
        <div><strong>CLIENTE:</strong> <?= htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']) ?></div>
        <div><strong>CORREO:</strong> <?= htmlspecialchars($pedido['email']) ?></div>
        <div><strong>MODALIDAD:</strong> <?= strtoupper($pedido['tipo_entrega']) ?></div>
        <?php if (!empty($pedido['direccion_envio'])): ?>
            <div><strong>DIRECCIÓN:</strong> <?= htmlspecialchars($pedido['direccion_envio']) ?></div>
        <?php endif; ?>
        <?php if (!empty($pedido['numero_mesa'])): ?>
            <div><strong>MESA N.º:</strong> <?= $pedido['numero_mesa'] ?></div>
        <?php endif; ?>
    </div>

    <table class="ticket-table">
        <thead>
            <tr>
                <th>DESCRIPCIÓN</th>
                <th style="text-align:center;">CANT</th>
                <th style="text-align:right;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($d = $detalle->fetch_assoc()): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($d['descripcion']) ?>
                    <?php if (!empty($d['modificadores'])): ?>
                        <div style="font-size:0.7rem;color:#555;">(<?= htmlspecialchars($d['modificadores']) ?>)</div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">x<?= $d['cantidad'] ?></td>
                <td style="text-align:right;">$<?= number_format($d['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="ticket-totals">
        <div class="ticket-totals-row">
            <span>SUBTOTAL:</span>
            <strong>$<?= number_format($pedido['subtotal'], 0, ',', '.') ?></strong>
        </div>
        <?php if ($pedido['descuento'] > 0): ?>
        <div class="ticket-totals-row">
            <span>DESCUENTO:</span>
            <strong>-$<?= number_format($pedido['descuento'], 0, ',', '.') ?></strong>
        </div>
        <?php endif; ?>
        <?php if ($pedido['costo_envio'] > 0): ?>
        <div class="ticket-totals-row">
            <span>COSTO DE ENVÍO:</span>
            <strong>$<?= number_format($pedido['costo_envio'], 0, ',', '.') ?></strong>
        </div>
        <?php endif; ?>
        <?php if ($pedido['propina'] > 0): ?>
        <div class="ticket-totals-row">
            <span>PROPINA:</span>
            <strong>$<?= number_format($pedido['propina'], 0, ',', '.') ?></strong>
        </div>
        <?php endif; ?>
        <div class="ticket-totals-row total-final">
            <span>TOTAL:</span>
            <strong>$<?= number_format($pedido['total'], 0, ',', '.') ?></strong>
        </div>
    </div>

    <div style="margin-top:12px;font-size:0.78rem;line-height:1.4;">
        <div><strong>FORMA DE PAGO:</strong> <?= strtoupper($pedido['metodo_pago']) ?> (APROBADO)</div>
        <?php if ($pedido['metodo_pago'] === 'efectivo' && $pedido['paga_con']): ?>
            <div>Abonó con: $<?= number_format($pedido['paga_con'], 0, ',', '.') ?></div>
            <div>Vuelto entregado: $<?= number_format($pedido['vuelto'], 0, ',', '.') ?></div>
        <?php endif; ?>
        <div style="margin-top:6px;color:#555;">
            Neto Gravado 21%: $<?= number_format($netoGravado, 2, ',', '.') ?><br>
            IVA 21.00%: $<?= number_format($iva21, 2, ',', '.') ?>
        </div>
    </div>

    <div class="ticket-footer">
        <div class="ticket-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://serviciosweb.afip.gob.ar/genericos/comprobantes/cae.aspx?cuit=30712345679&tipoCmp=006&puntoVta=0004&nroCmp=<?= $pedido['idpedido'] ?>" alt="QR Fiscal AFIP">
        </div>
        <p><strong>CAE N.º: <?= htmlspecialchars($caeNum) ?></strong></p>
        <p>Vto. CAE: <?= htmlspecialchars($caeVto) ?></p>
        <br>
        <p>¡Gracias por elegir Mostaza!</p>
        <p>Conservá este comprobante para cualquier cambio o consulta.</p>
    </div>

</div>

<div class="actions-bar">
    <button class="btn-action" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Imprimir / Guardar PDF
    </button>
    <a href="pedido_confirmado.php?id=<?= $pedido['idpedido'] ?>" class="btn-action secondary">
        <i class="fa-solid fa-arrow-left"></i> Volver al Seguimiento
    </a>
</div>

</body>
</html>
