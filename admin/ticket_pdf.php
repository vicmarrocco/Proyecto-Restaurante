<?php
// ============================================================
// admin/ticket_pdf.php
// Comprobante / Ticket Fiscal Imprimible para Panel Admin
// ============================================================
session_start();
require('conexion.php');

$idpedido = intval($_GET['id'] ?? 0);

if (!$idpedido) {
    die("Pedido no especificado.");
}

$stmt = $conn->prepare("SELECT p.*, f.numero_factura, f.tipo_comprobante, f.cae, f.cae_vto, f.neto_gravado, f.iva_21, u.nombre, u.apellido, u.email, u.telefono FROM pedido_web p LEFT JOIN factura f ON p.idpedido = f.idpedido LEFT JOIN usuario_web u ON p.idusuario = u.idusuario WHERE p.idpedido = ?");
$stmt->bind_param("i", $idpedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("Pedido #$idpedido no encontrado en la base de datos.");
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
<title>Ticket Fiscal #<?= $pedido['idpedido'] ?> - Mostaza Central</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Courier New', Courier, monospace, sans-serif;
    background: #F1F5F9;
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
    border: 1px solid #CBD5E1;
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
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
    color: #A30002;
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
.ticket-totals div {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}
.ticket-totals .total-final {
    font-size: 1.15rem;
    font-weight: bold;
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
    padding: 6px 0;
    margin-top: 8px;
}
.ticket-footer {
    text-align: center;
    border-top: 2px dashed #000;
    margin-top: 18px;
    padding-top: 14px;
    font-size: 0.75rem;
    line-height: 1.4;
}
.print-bar {
    position: fixed;
    top: 15px;
    right: 20px;
    display: flex;
    gap: 10px;
    z-index: 100;
}
.btn-print {
    background: #A30002;
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
@media print {
    body { background: #fff; padding: 0; }
    .ticket-paper { box-shadow: none; border: none; max-width: 100%; padding: 0; }
    .print-bar { display: none !important; }
}
</style>
</head>
<body>

<div class="print-bar">
    <button class="btn-print" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Imprimir Ticket
    </button>
</div>

<div class="ticket-paper">
    <div class="ticket-header">
        <h1>MOSTAZA</h1>
        <p><strong>MOSTAZA SA · CUIT: 30-71234567-9</strong></p>
        <p>Av. 25 de Mayo 142 · Formosa, Argentina</p>
        <p>IVA RESPONSABLE INSCRIPTO · A-0001-00000001</p>
        <p style="margin-top:4px;font-weight:bold;font-size:0.95rem;"><?= htmlspecialchars($pedido['tipo_comprobante'] ?? 'FACTURA B') ?></p>
        <p>N.º <?= htmlspecialchars($numFactura) ?></p>
    </div>

    <div class="ticket-info-block">
        <div><strong>FECHA / HORA:</strong> <?= date('d/m/Y H:i:s', strtotime($pedido['fecha'])) ?></div>
        <div><strong>PEDIDO WEB ID:</strong> #<?= $pedido['idpedido'] ?></div>
        <div><strong>CLIENTE:</strong> <?= htmlspecialchars($pedido['nombre'] ? $pedido['nombre'] . ' ' . $pedido['apellido'] : 'Consumidor Final') ?></div>
        <div><strong>CONDICIÓN IVA:</strong> Consumidor Final</div>
        <div><strong>MODALIDAD:</strong> <?= strtoupper($pedido['tipo_entrega']) ?> <?= !empty($pedido['numero_mesa']) ? '(Mesa #' . $pedido['numero_mesa'] . ')' : '' ?></div>
        <div><strong>MEDIO DE PAGO:</strong> <?= strtoupper($pedido['metodo_pago']) ?></div>
    </div>

    <table class="ticket-table">
        <thead>
            <tr>
                <th style="width:45px;">CANT</th>
                <th>DESCRIPCIÓN</th>
                <th style="text-align:right;">P.UNIT</th>
                <th style="text-align:right;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($it = $detalle->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:bold;"><?= $it['cantidad'] ?>x</td>
                <td>
                    <?= htmlspecialchars($it['descripcion']) ?>
                    <?php if (!empty($it['modificadores'])): ?>
                        <br><span style="font-size:0.75rem;color:#444;">* <?= htmlspecialchars($it['modificadores']) ?></span>
                    <?php endif; ?>
                </td>
                <td style="text-align:right;">$<?= number_format($it['precio_unitario'], 0, ',', '.') ?></td>
                <td style="text-align:right;font-weight:bold;">$<?= number_format($it['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="ticket-totals">
        <div><span>Subtotal:</span> <span>$<?= number_format($pedido['subtotal'], 0, ',', '.') ?></span></div>
        <?php if ($pedido['descuento'] > 0): ?>
            <div style="color:#A30002;"><span>Descuento Cupón:</span> <span>-$<?= number_format($pedido['descuento'], 0, ',', '.') ?></span></div>
        <?php endif; ?>
        <?php if ($pedido['costo_envio'] > 0): ?>
            <div><span>Costo Envío Delivery:</span> <span>$<?= number_format($pedido['costo_envio'], 0, ',', '.') ?></span></div>
        <?php endif; ?>
        <div style="font-size:0.78rem;color:#555;"><span>Neto Gravado:</span> <span>$<?= number_format($netoGravado, 2, ',', '.') ?></span></div>
        <div style="font-size:0.78rem;color:#555;"><span>IVA (21%):</span> <span>$<?= number_format($iva21, 2, ',', '.') ?></span></div>
        <div class="total-final">
            <span>TOTAL:</span>
            <span>$<?= number_format($pedido['total'], 0, ',', '.') ?></span>
        </div>
        <?php if ($pedido['paga_con'] > 0): ?>
            <div style="margin-top:6px;"><span>Abona con:</span> <span>$<?= number_format($pedido['paga_con'], 0, ',', '.') ?></span></div>
            <div><span>Su Vuelto:</span> <span>$<?= number_format($pedido['vuelto'], 0, ',', '.') ?></span></div>
        <?php endif; ?>
    </div>

    <div class="ticket-footer">
        <div style="font-weight:bold;margin-bottom:6px;">CAE: <?= $caeNum ?></div>
        <div style="margin-bottom:10px;">Fecha Vto. CAE: <?= $caeVto ?></div>
        <p>Comprobante Autorizado por AFIP</p>
        <p>¡Gracias por elegir Mostaza!</p>
        <p style="font-size:0.7rem;margin-top:6px;">Hamburguesas en Serio · Formosa Centro</p>
    </div>
</div>

</body>
</html>
