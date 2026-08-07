<?php
// ============================================================
// includes/header.php
// Requiere que la página que lo incluye ya haya hecho session_start().
// Variables opcionales que la página puede definir antes de incluir:
//   $paginaActiva = 'inicio' | 'productos' | 'nosotros'
// ============================================================
$logueado = isset($_SESSION['usuario_id']);
$nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
$cantCarrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantCarrito += $item['cantidad'];
    }
}
$paginaActiva = $paginaActiva ?? '';
?>
<header>
    <div class="logo">
        <a href="index.php" style="display:flex;align-items:center;gap:12px;text-decoration:none;">
            <img src="img/image-removebg-preview.png" alt="Logo Mostaza">
            <span>MOSTAZA</span>
        </a>
    </div>

    <nav>
        <ul>
            <li><a href="index.php" class="<?= $paginaActiva === 'inicio' ? 'activo' : '' ?>">Inicio</a></li>
            <li><a href="productos.php" class="<?= $paginaActiva === 'productos' ? 'activo' : '' ?>">Productos</a></li>
            <li><a href="sobrenosotros.php" class="<?= $paginaActiva === 'nosotros' ? 'activo' : '' ?>">Nosotros</a></li>
            <li><a href="contacto.php" class="<?= $paginaActiva === 'contacto' ? 'activo' : '' ?>">Contacto</a></li>
        </ul>
    </nav>

    <div class="btns">
        <a href="productos.php" class="btn-header">Pedir Ahora</a>

        <a href="carrito.php" class="btn-header2" style="position:relative;">
            <i class="fa-solid fa-cart-shopping"></i> Carrito
            <?php if ($cantCarrito > 0): ?>
                <span class="badge-carrito"><?= $cantCarrito ?></span>
            <?php endif; ?>
        </a>

        <?php if ($logueado): ?>
            <span class="saludo-usuario">Hola, <?= htmlspecialchars($nombreUsuario) ?></span>
            <a href="logout.php" class="btn-header2">Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php" class="btn-header2">Iniciar Sesión</a>
        <?php endif; ?>
    </div>
</header>
