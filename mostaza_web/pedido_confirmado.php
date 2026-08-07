<?php
session_start();
require('conexion.php');
$paginaActiva = '';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$idpedido = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM pedido_web WHERE idpedido = ? AND idusuario = ?");
$stmt->bind_param("ii", $idpedido, $_SESSION['usuario_id']);
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza · Pedido confirmado</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="carrito-wrap">
    <div style="text-align:center;">
        <i class="fa-solid fa-circle-check" style="font-size:4rem;color:#1c5e2b;"></i>
        <h1 class="carrito-titulo">¡Pedido confirmado!</h1>
        <p style="color:#666;margin-bottom:30px;">Pedido N.º <?= $pedido['idpedido'] ?> · Estado: <?= htmlspecialchars($pedido['estado']) ?></p>
    </div>

    <?php while ($d = $detalle->fetch_assoc()): ?>
    <div class="carrito-item">
        <div class="carrito-item-info">
            <h3><?= htmlspecialchars($d['descripcion']) ?></h3>
            <p>Cantidad: <?= $d['cantidad'] ?> &nbsp;·&nbsp; Precio unitario: $<?= number_format($d['precio_unitario'], 0, ',', '.') ?></p>
        </div>
        <div class="carrito-item-total">$<?= number_format($d['subtotal'], 0, ',', '.') ?></div>
    </div>
    <?php endwhile; ?>

    <div class="carrito-total-row">
        <span>Total</span>
        <strong>$<?= number_format($pedido['total'], 0, ',', '.') ?></strong>
    </div>

    <div style="text-align:center;margin-top:30px;">
        <a href="productos.php" class="btn-finalizar" style="text-decoration:none;display:inline-block;">Seguir pidiendo</a>
    </div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
