<?php
// ============================================================
// includes/header.php
// Navegación principal moderna, responsive y profesional para Mostaza
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
$totalCarritoHeader = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantCarrito += $item['cantidad'];
        $totalCarritoHeader += ($item['precio'] * $item['cantidad']);
    }
}
$paginaActiva = $paginaActiva ?? '';
?>


<!-- Header Principal -->
<header class="main-header">
    <div class="logo">
        <a href="index.php" class="logo-link">
            <img src="img/image-removebg-preview.png" alt="Logo Mostaza" class="logo-img">
            <div class="logo-text">
                <span class="brand-name">MOSTAZA</span>
                <span class="brand-sub">HAMBURGUESAS EN SERIO</span>
            </div>
        </a>
    </div>

    <!-- Navegación de Escritorio -->
    <nav class="desktop-nav">
        <ul>
            <li><a href="index.php" class="<?= $paginaActiva === 'inicio' ? 'activo' : '' ?>"><i class="fa-solid fa-house"></i> Inicio</a></li>
            <li><a href="productos.php" class="<?= $paginaActiva === 'productos' ? 'activo' : '' ?>"><i class="fa-solid fa-burger"></i> Menú</a></li>
            <li><a href="personalizado.php" class="<?= $paginaActiva === 'personalizado' ? 'activo' : '' ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> Armá tu Burger</a></li>
            <li><a href="locales.php" class="<?= $paginaActiva === 'locales' ? 'activo' : '' ?>"><i class="fa-solid fa-shop"></i> Locales</a></li>
            <li><a href="sobrenosotros.php" class="<?= $paginaActiva === 'nosotros' ? 'activo' : '' ?>">Nosotros</a></li>
            <li><a href="contacto.php" class="<?= $paginaActiva === 'contacto' ? 'activo' : '' ?>">Contacto</a></li>
            <?php if ($logueado): ?>
                <li><a href="mis_pedidos.php" class="<?= $paginaActiva === 'pedidos' ? 'activo' : '' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Mis Pedidos</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Buscador en Cabecera con Autocompletado AJAX -->
    <div class="header-search-box" id="headerSearchBox">
        <form action="productos.php" method="GET" class="header-search-form" id="headerSearchForm">
            <i class="fa-solid fa-magnifying-glass search-submit-icon"></i>
            <input type="text" 
                   name="q" 
                   id="headerSearchInput" 
                   class="header-search-input" 
                   placeholder="Buscar hamburguesas, combos..." 
                   autocomplete="off" 
                   aria-label="Buscar productos">
            <button type="button" class="header-search-clear-btn" id="headerSearchClear" style="display:none;" title="Borrar">&times;</button>
        </form>
        <div class="header-search-dropdown" id="headerSearchDropdown" style="display:none;"></div>
    </div>

    <!-- Botones de Acción y Usuario -->
    <div class="header-actions">
        <!-- Botón Carrito -->
        <a href="carrito.php" class="btn-cart-header" title="Ver Carrito de Compras">
            <div class="cart-icon-wrap">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="cart-count-badge" id="headerCartCount" style="<?= $cantCarrito > 0 ? '' : 'display:none;' ?>"><?= $cantCarrito ?></span>
            </div>
            <div class="cart-text-wrap d-none d-sm-block">
                <span class="cart-label">Mi Pedido</span>
                <span class="cart-total">$<?= number_format($totalCarritoHeader, 0, ',', '.') ?></span>
            </div>
        </a>

        <?php if ($logueado): ?>
            <!-- Usuario Logueado -->
            <a href="perfil.php" class="badge-club-header" title="Mis Puntos Club Mostaza">
                <i class="fa-solid fa-crown" style="color:#FBB728;"></i>
                <span><?= $puntosHeader ?> pts</span>
            </a>

            <div class="user-dropdown-header">
                <a href="perfil.php" class="btn-user-avatar" title="Mi Perfil">
                    <i class="fa-solid fa-circle-user"></i>
                    <span class="user-name-short d-none d-md-inline"><?= htmlspecialchars(explode(' ', $nombreUsuario)[0]) ?></span>
                </a>
                <a href="logout.php" class="btn-logout-header" title="Cerrar Sesión" onclick="return confirm('¿Deseás cerrar tu sesión?');">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        <?php else: ?>
            <!-- Usuario Invitado -->
            <a href="login.php" class="btn-header-login">
                <i class="fa-regular fa-user"></i> Ingresar
            </a>
            <a href="productos.php" class="btn-header-order d-none d-sm-inline-flex">
                Pedir Online
            </a>
        <?php endif; ?>

        <!-- Botón Menú Móvil (Hamburguesa) -->
        <button type="button" class="mobile-toggle-btn" id="mobileMenuBtn" aria-label="Abrir menú">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</header>

<!-- Menú Móvil Desplegable (Drawer) -->
<div class="mobile-drawer-overlay" id="mobileDrawerOverlay"></div>
<aside class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-header">
        <div class="logo-mobile">
            <img src="img/image-removebg-preview.png" alt="Mostaza">
            <span>MOSTAZA</span>
        </div>
        <button class="mobile-drawer-close" id="mobileDrawerClose">&times;</button>
    </div>

    <?php if ($logueado): ?>
        <div class="mobile-user-card">
            <div class="user-avatar-circle">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <strong><?= htmlspecialchars($nombreUsuario) ?></strong>
                <span class="user-pts-tag"><i class="fa-solid fa-crown"></i> <?= $puntosHeader ?> puntos Club</span>
            </div>
        </div>
    <?php else: ?>
        <div class="mobile-auth-banner">
            <p>Iniciá sesión para acumular puntos y descuentos exclusivos.</p>
            <div class="mobile-auth-btns">
                <a href="login.php" class="btn-m-login">Ingresar</a>
                <a href="registro.php" class="btn-m-reg">Crear Cuenta</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Buscador Móvil -->
    <div class="mobile-search-wrapper" style="padding: 14px 20px 8px;">
        <form action="productos.php" method="GET" class="mobile-search-form">
            <div style="position:relative;display:flex;align-items:center;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:14px;color:#94A3B8;font-size:0.9rem;"></i>
                <input type="text" name="q" placeholder="Buscar hamburguesa, combo..." style="width:100%;padding:10px 14px 10px 38px;border-radius:24px;border:1.5px solid #E2E8F0;font-family:'Montserrat',sans-serif;font-size:0.88rem;outline:none;background:#FFFFFF;" autocomplete="off">
            </div>
        </form>
    </div>

    <ul class="mobile-nav-list">
        <li><a href="index.php" class="<?= $paginaActiva === 'inicio' ? 'activo' : '' ?>"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li><a href="productos.php" class="<?= $paginaActiva === 'productos' ? 'activo' : '' ?>"><i class="fa-solid fa-burger"></i> Todo el Menú</a></li>
        <li><a href="personalizado.php" class="<?= $paginaActiva === 'personalizado' ? 'activo' : '' ?>"><i class="fa-solid fa-wand-magic-sparkles"></i> Armá tu Hamburguesa 3D</a></li>
        <li><a href="locales.php" class="<?= $paginaActiva === 'locales' ? 'activo' : '' ?>"><i class="fa-solid fa-shop"></i> Locales y Horarios</a></li>
        <li><a href="sobrenosotros.php" class="<?= $paginaActiva === 'nosotros' ? 'activo' : '' ?>"><i class="fa-solid fa-building"></i> Sobre Nosotros</a></li>
        <li><a href="contacto.php" class="<?= $paginaActiva === 'contacto' ? 'activo' : '' ?>"><i class="fa-solid fa-headset"></i> Contacto y Soporte</a></li>
        <?php if ($logueado): ?>
            <li><a href="mis_pedidos.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial de Pedidos</a></li>
            <li><a href="perfil.php"><i class="fa-solid fa-user-gear"></i> Mi Cuenta y Puntos</a></li>
            <li><a href="logout.php" style="color:#DC2626;"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></li>
        <?php endif; ?>
    </ul>

    <div class="mobile-drawer-footer">
        <p>© 2026 Mostaza y Pan S.A. · Todos los derechos reservados</p>
    </div>
</aside>

<script>
// Mobile Drawer Toggle
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('mobileMenuBtn');
    const drawer = document.getElementById('mobileDrawer');
    const overlay = document.getElementById('mobileDrawerOverlay');
    const closeBtn = document.getElementById('mobileDrawerClose');

    function openDrawer() {
        if (drawer && overlay) {
            drawer.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    }
    function closeDrawer() {
        if (drawer && overlay) {
            drawer.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    }

    if (btn) btn.addEventListener('click', openDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    if (overlay) overlay.addEventListener('click', closeDrawer);

    // ============================================================
    // Buscador en Tiempo Real con Autocompletado AJAX
    // ============================================================
    const searchInput = document.getElementById('headerSearchInput');
    const searchDropdown = document.getElementById('headerSearchDropdown');
    const searchClearBtn = document.getElementById('headerSearchClear');
    let debounceSearchTimer = null;

    if (searchInput && searchDropdown) {
        function cerrarDropdown() {
            searchDropdown.style.display = 'none';
            searchDropdown.innerHTML = '';
        }

        if (searchClearBtn) {
            searchClearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchClearBtn.style.display = 'none';
                cerrarDropdown();
                searchInput.focus();
            });
        }

        searchInput.addEventListener('input', function() {
            const q = searchInput.value.trim();
            if (searchClearBtn) searchClearBtn.style.display = q.length > 0 ? 'block' : 'none';

            clearTimeout(debounceSearchTimer);
            if (q.length < 2) {
                cerrarDropdown();
                return;
            }

            debounceSearchTimer = setTimeout(() => {
                fetch(`buscar_ajax.php?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data.ok || !data.productos || data.productos.length === 0) {
                            searchDropdown.innerHTML = `
                                <div class="search-empty-result">
                                    <i class="fa-solid fa-burger" style="font-size:1.8rem;color:#CBD5E1;margin-bottom:6px;"></i>
                                    <p style="margin:0;font-weight:700;color:#64748B;font-size:0.85rem;">No encontramos productos con "${q}"</p>
                                    <a href="productos.php" style="font-size:0.8rem;color:var(--rojo);font-weight:800;margin-top:6px;display:inline-block;">Ver todo el Menú</a>
                                </div>
                            `;
                            searchDropdown.style.display = 'block';
                            return;
                        }

                        let html = `
                            <div class="search-results-header">
                                <span>Resultados para <strong>"${q}"</strong></span>
                                <span class="results-count">${data.productos.length} encontrados</span>
                            </div>
                        `;
                        
                        data.productos.forEach(p => {
                            let img = p.imagen || 'img/image-removebg-preview.png';
                            if (!img.startsWith('img/') && !img.startsWith('http')) img = 'img/' + img;
                            const desc = p.descripcion ? (p.descripcion.length > 55 ? p.descripcion.substring(0, 52) + '...' : p.descripcion) : '';
                            html += `
                                <a href="detalle_producto.php?id=${p.idproducto}" class="search-result-item">
                                    <img src="${img}" alt="${p.nombre}" class="search-result-img" onerror="this.src='img/image-removebg-preview.png'">
                                    <div class="search-result-info">
                                        <span class="search-result-name">${p.nombre}</span>
                                        <span class="search-result-desc">${desc}</span>
                                    </div>
                                    <div class="search-result-price-wrap">
                                        <span class="search-result-price">${p.precio_formateado || ('$' + p.precio)}</span>
                                    </div>
                                </a>
                            `;
                        });

                        html += `
                            <a href="productos.php?q=${encodeURIComponent(q)}" class="search-view-all-link">
                                <span>Ver todos los resultados para <strong>"${q}"</strong></span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        `;

                        searchDropdown.innerHTML = html;
                        searchDropdown.style.display = 'block';
                    })
                    .catch(() => {
                        cerrarDropdown();
                    });
            }, 200);
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#headerSearchBox')) {
                cerrarDropdown();
            }
        });

        // Escape para cerrar
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarDropdown();
            }
        });
    }
});
</script>
