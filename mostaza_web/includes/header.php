<?php
// ============================================================
// includes/header.php
// Navegación principal con sesión activa, carrito y acceso al sistema.
// ============================================================
$logueado = isset($_SESSION['usuario_id']);
$nombreUsuario = $_SESSION['usuario_nombre'] ?? '';
$idUsuarioSesion = $_SESSION['usuario_id'] ?? 0;

$puntosHeader = 0;
if ($logueado && isset($conn)) {
    $stmtPH = $conn->prepare("SELECT puntos FROM usuario_web WHERE idusuario = ?");
    $stmtPH->bind_param("i", $idUsuarioSesion);
    $stmtPH->execute();
    $resPH = $stmtPH->get_result()->fetch_assoc();
    $puntosHeader = intval($resPH['puntos'] ?? 0);
}

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
            <li><a href="personalizado.php" class="<?= $paginaActiva === 'personalizado' ? 'activo' : '' ?>">Personalizado</a></li>
            <li><a href="sobrenosotros.php" class="<?= $paginaActiva === 'nosotros' ? 'activo' : '' ?>">Nosotros</a></li>
            <li><a href="contacto.php" class="<?= $paginaActiva === 'contacto' ? 'activo' : '' ?>">Contacto</a></li>
            <?php if ($logueado): ?>
                <li><a href="mis_pedidos.php" class="<?= $paginaActiva === 'pedidos' ? 'activo' : '' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Mis Pedidos</a></li>
            <?php endif; ?>
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
            <a href="perfil.php" class="badge-puntos-link" title="Ver mis Puntos Mostaza">
                <i class="fa-solid fa-star" style="color:#FBB728;"></i> <?= $puntosHeader ?> pts
            </a>
            <span class="saludo-usuario">Hola, <?= htmlspecialchars($nombreUsuario) ?></span>
            <a href="perfil.php" class="btn-header2" title="Mi Cuenta"><i class="fa-solid fa-user"></i></a>
            <a href="logout.php" class="btn-header2" title="Cerrar Sesión"><i class="fa-solid fa-right-from-bracket"></i></a>
        <?php else: ?>
            <a href="login.php" class="btn-header2">Iniciar Sesión</a>
        <?php endif; ?>
    </div>
</header>
