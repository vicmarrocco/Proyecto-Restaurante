<?php
session_start();
require('conexion.php');
$paginaActiva = '';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['redirect_after_login'] = 'carrito.php';
    header("Location: login.php");
    exit();
}

$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza · Carrito</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="carrito-wrap">
    <h1 class="carrito-titulo">Tu Carrito</h1>

    <?php if (empty($carrito)): ?>
        <div class="carrito-vacio">
            <p>Tu carrito está vacío.</p>
            <br>
            <a href="productos.php" class="btn-finalizar" style="text-decoration:none;display:inline-block;">Ver productos</a>
        </div>
    <?php else: ?>

        <?php foreach ($carrito as $key => $item): ?>
        <div class="carrito-item">
            <img src="img/<?= htmlspecialchars($item['imagen'] ?? 'image-removebg-preview.png') ?>" alt="">
            <div class="carrito-item-info">
                <h3><?= htmlspecialchars($item['nombre']) ?></h3>
                <p>Precio unitario: $<?= number_format($item['precio'], 0, ',', '.') ?> &nbsp;·&nbsp; Cantidad: <?= $item['cantidad'] ?></p>
            </div>
            <div class="carrito-item-total">
                $<?= number_format($item['precio'] * $item['cantidad'], 0, ',', '.') ?>
            </div>
            <form method="post" action="eliminar_carrito.php">
                <input type="hidden" name="key" value="<?= htmlspecialchars($key) ?>">
                <button type="submit" class="btn-quitar">Quitar</button>
            </form>
        </div>
        <?php endforeach; ?>

        <div class="carrito-total-row">
            <span>Total a pagar</span>
            <strong>$<?= number_format($total, 0, ',', '.') ?></strong>
        </div>

        <form method="post" action="finalizar_pedido.php" style="text-align:center;margin-top:24px;">
            <button type="submit" class="btn-finalizar">Confirmar pedido</button>
        </form>

    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
