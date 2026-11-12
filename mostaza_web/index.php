<?php
// ============================================================
// index.php
// Portal Web Oficial de Mostaza - Experiencia Digital Premium
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'inicio';

// Consultar productos destacados de la base de datos
$stmtDestacados = $conn->query("
    SELECT * FROM producto 
    WHERE activo = 1 
      AND categoria IN ('megacombos', 'exclusivos', 'pollo')
    ORDER BY precio DESC 
    LIMIT 8
");
$productosDestacados = [];
if ($stmtDestacados) {
    while ($r = $stmtDestacados->fetch_assoc()) {
        $productosDestacados[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostaza · Hamburguesas en Serio</title>
    <!-- Base Framework (Bootstrap) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Estilos Oficiales Mostaza (Sobrescriben Bootstrap) -->
    <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
    <link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>">
</head>
<body>

<?php include('includes/header.php'); ?>

<?php if (!isset($_SESSION['usuario_id'])): ?>
<div class="aviso-sesion">
    <span>👋 ¡Bienvenido a Mostaza!</span> <a href="login.php">Iniciá sesión</a> o <a href="registro.php">registrate</a> para sumar puntos en cada compra y canjear promos exclusivas.
</div>
<?php endif; ?>

<!-- ============================================================
     1. HERO BILLBOARD SLIDER (CAMPAÑAS DIBU / TRUENO / PAPAS)
     ============================================================ -->
<section class="hero-billboard-section">
    <div id="heroMostazaCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
        <!-- Indicadores -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroMostazaCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Campaña Dibu"></button>
            <button type="button" data-bs-target="#heroMostazaCarousel" data-bs-slide-to="1" aria-label="Campaña Trueno"></button>
            <button type="button" data-bs-target="#heroMostazaCarousel" data-bs-slide-to="2" aria-label="Papas Cheddar"></button>
        </div>

        <div class="carousel-inner">
            <!-- Slide 1: Dibu Martínez -->
            <div class="carousel-item active">
                <div class="hero-billboard-slide" style="background-image: url('img/dibu.jpg');">
                    <div class="container">
                        <div class="billboard-slide-content">
                            <span class="billboard-badge gold"><i class="fa-solid fa-crown"></i> Campaña Oficial AFA</span>
                            <h1 class="billboard-title">HAMBRE DE LA <span>CUARTA.</span></h1>
                            <p class="billboard-desc">
                                La hamburguesa de los campeones del mundo. Medallones de 100% pura carne vacuna, queso provolone seleccionado, bacon extra crocante y salsa especial elegida por el Dibu Martínez.
                            </p>
                            <div class="billboard-actions">
                                <button type="button" class="btn-hero-primary" onclick="agregarCarritoAjax(1, 1, this)">
                                    <i class="fa-solid fa-cart-plus"></i> Pedir Combo Dibu
                                </button>
                                <a href="productos.php" class="btn-hero-secondary">
                                    <i class="fa-solid fa-list"></i> Ver Todo el Menú
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Trueno Mega Boom -->
            <div class="carousel-item">
                <div class="hero-billboard-slide" style="background-image: url('img/trueno.jpg');">
                    <div class="container">
                        <div class="billboard-slide-content">
                            <span class="billboard-badge"><i class="fa-solid fa-bolt"></i> Mega Edición Limitada</span>
                            <h1 class="billboard-title">NUNCA TE <span>ACHIQUES.</span></h1>
                            <p class="billboard-desc">
                                TR1 x TRUENO: Doble carne colosal, queso cheddar fundido al punto justo, lluvia de cebolla crispy dorada y salsa ahumada smoke. El verdadero flow en una burger.
                            </p>
                            <div class="billboard-actions">
                                <button type="button" class="btn-hero-primary" onclick="agregarCarritoAjax(8, 1, this)">
                                    <i class="fa-solid fa-fire"></i> Pedir Mega Boom
                                </button>
                                <a href="personalizado.php" class="btn-hero-secondary">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Armá tu Burger 3D
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Papas Cheddar & Bacon -->
            <div class="carousel-item">
                <div class="hero-billboard-slide" style="background-image: url('img/papas.jpg');">
                    <div class="container">
                        <div class="billboard-slide-content">
                            <span class="billboard-badge gold"><i class="fa-solid fa-star"></i> El Acompañamiento Definitivo</span>
                            <h1 class="billboard-title">PAPAS CHEDDAR <span>& EXTRA BACON.</span></h1>
                            <p class="billboard-desc">
                                Papas fritas doradas y crujientes, bañadas en abundante salsa de queso cheddar caliente y terminadas con lluvia de panceta crocante.
                            </p>
                            <div class="billboard-actions">
                                <a href="categoria.php?cat=megacombos" class="btn-hero-primary">
                                    <i class="fa-solid fa-utensils"></i> Pedir con Papas XL
                                </a>
                                <a href="productos.php" class="btn-hero-secondary">
                                    <i class="fa-solid fa-burger"></i> Explorar Menú
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controles Prev / Next -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroMostazaCarousel" data-bs-slide="prev" aria-label="Anterior">
            <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color:rgba(0,0,0,0.5);border-radius:50%;padding:22px;"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroMostazaCarousel" data-bs-slide="next" aria-label="Siguiente">
            <span class="carousel-control-next-icon" aria-hidden="true" style="background-color:rgba(0,0,0,0.5);border-radius:50%;padding:22px;"></span>
        </button>
    </div>
</section>

<!-- ============================================================
     BUSCADOR RÁPIDO INTERACTIVO HOME
     ============================================================ -->
<section class="home-search-hero-strip">
    <div class="home-search-container">
        <form action="productos.php" method="GET" class="home-search-form">
            <i class="fa-solid fa-magnifying-glass home-search-icon"></i>
            <input type="text" 
                   name="q" 
                   class="home-search-input" 
                   placeholder="¿Qué vas a pedir hoy? Buscá tu hamburguesa, combo, papas o postre..." 
                   autocomplete="off">
            <button type="submit" class="home-search-btn">
                <span>Buscar</span> <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
        <div class="home-search-suggestions">
            <span class="suggestion-label"><i class="fa-solid fa-fire"></i> Populares:</span>
            <a href="productos.php?q=Cheddar" class="suggestion-tag">Mega Doble Cheddar</a>
            <a href="productos.php?q=Bacon" class="suggestion-tag">Extra Bacon</a>
            <a href="productos.php?q=Pollo" class="suggestion-tag">Pollo Crispy</a>
            <a href="productos.php?q=Papas" class="suggestion-tag">Papas con Cheddar</a>
            <a href="productos.php?q=Veggie" class="suggestion-tag">Veggie</a>
            <a href="personalizado.php" class="suggestion-tag tag-special"><i class="fa-solid fa-wand-magic-sparkles"></i> Armá tu Burger</a>
        </div>
    </div>
</section>

<!-- ============================================================
     2. EXPLORADOR VISUAL DE CATEGORÍAS (LAS 8 TARJETAS OFICIALES)
     ============================================================ -->
<section class="section-official-categories">
    <div class="text-center mb-4">
        <span class="section-badge"><i class="fa-solid fa-utensils"></i> Carta Oficial</span>
        <h2 style="font-family:'Montserrat',sans-serif;font-size:clamp(1.8rem, 4vw, 2.6rem);font-weight:900;text-transform:uppercase;color:var(--text-main);margin-top:6px;">
            Explorá Nuestras <span style="color:var(--rojo);">Categorías</span>
        </h2>
        <p style="color:var(--text-muted);font-size:1rem;max-width:560px;margin:0 auto;">
            Hacé clic en cualquier sección para ver todas las opciones disponibles con sus ingredientes y precios.
        </p>
    </div>

    <div class="official-cat-grid">
        <!-- 1. Exclusivos -->
        <a href="categoria.php?cat=exclusivos" class="official-cat-card">
            <img src="img/cardex.jpg" alt="Exclusivos" class="cat-bg" onerror="this.src='img/cuarto.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Hamburguesas de Autor</span>
                <h3 class="cat-card-title">Exclusivos</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>

        <!-- 2. Mega Combos -->
        <a href="categoria.php?cat=megacombos" class="official-cat-card">
            <img src="img/card1.jpg" alt="Mega Combos" class="cat-bg" onerror="this.src='img/doblwcheddar.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Burger + Papas + Gaseosa</span>
                <h3 class="cat-card-title">Mega Combos</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>

        <!-- 3. Pollo Crispy -->
        <a href="categoria.php?cat=pollo" class="official-cat-card">
            <img src="img/card2.jpg" alt="Pollo Crispy" class="cat-bg" onerror="this.src='img/pollocrispy.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">100% Pechuga Crocante</span>
                <h3 class="cat-card-title">Pollo Crispy</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>

        <!-- 4. Personalizado 3D -->
        <a href="personalizado.php" class="official-cat-card" style="border-color:var(--amarillo);">
            <img src="img/cardper.jpg" alt="Armá tu Burger" class="cat-bg" onerror="this.src='img/boom.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Simulador en Vivo</span>
                <h3 class="cat-card-title" style="color:var(--amarillo);">Armá tu Burger</h3>
                <span class="cat-card-btn" style="color:var(--amarillo);">Crear Ahora <i class="fa-solid fa-wand-magic-sparkles"></i></span>
            </div>
        </a>

        <!-- 5. Para Vos (Veggie & Sin TACC) -->
        <a href="categoria.php?cat=paravos" class="official-cat-card">
            <img src="img/card3.jpg" alt="Para Vos" class="cat-bg" onerror="this.src='img/cerovegiee.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Plant-Based & Sin Gluten</span>
                <h3 class="cat-card-title">Para Vos</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>

        <!-- 6. Cajitas Kids -->
        <a href="categoria.php?cat=cajitas" class="official-cat-card">
            <img src="img/card5.jpg" alt="Cajitas Kids" class="cat-bg" onerror="this.src='img/cajita1.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Menú Infantil + Juguete</span>
                <h3 class="cat-card-title">Cajitas Kids</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>

        <!-- 7. Ensaladas -->
        <a href="categoria.php?cat=ensaladas" class="official-cat-card">
            <img src="img/card4.jpg" alt="Ensaladas" class="cat-bg" onerror="this.src='img/ensaladapalta1.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Frescas & Nutritivas</span>
                <h3 class="cat-card-title">Ensaladas</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>

        <!-- 8. Postres & Cafetería -->
        <a href="categoria.php?cat=postres" class="official-cat-card">
            <img src="img/card7.jpg" alt="Postres y Cafetería" class="cat-bg" onerror="this.src='img/shake.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Helados, Shakes & Café</span>
                <h3 class="cat-card-title">Postres & Café</h3>
                <span class="cat-card-btn">Ver Productos <i class="fa-solid fa-arrow-right"></i></span>
            </div>
        </a>
    </div>
</section>

<!-- ============================================================
     3. LAS MÁS PEDIDAS (PRODUCTOS DESTACADOS CON PEDIDO AJAX)
     ============================================================ -->
<section class="section-featured-products" style="background:#FFFFFF;border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:60px 4% 70px;">
    <div class="text-center mb-5">
        <span class="section-badge"><i class="fa-solid fa-fire"></i> Top Ventas de la Semana</span>
        <h2 style="font-family:'Montserrat',sans-serif;font-size:clamp(1.8rem, 4vw, 2.6rem);font-weight:900;text-transform:uppercase;color:var(--text-main);margin-top:6px;">
            Las Hamburguesas <span style="color:var(--rojo);">Más Pedidas</span>
        </h2>
        <p style="color:var(--text-muted);font-size:1rem;max-width:550px;margin:0 auto;">
            Elaboradas al momento con 100% carne vacuna fresca, pan horneado hoy y queso fundido premium.
        </p>
    </div>

    <div class="products-modern-grid">
        <?php foreach ($productosDestacados as $p): 
            $catSlug = strtolower(trim($p['categoria'] ?? ''));
            $prodImg = $p['imagen'] ?? 'image-removebg-preview.png';
            if (strpos($prodImg, 'img/') !== 0 && strpos($prodImg, 'http') !== 0) {
                $prodImg = 'img/' . $prodImg;
            }
        ?>
        <article class="modern-product-card">
            <div class="product-img-wrapper">
                <span class="product-flag"><?= strtoupper($catSlug) ?></span>
                <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" title="Ver receta y alérgenos">
                    <img src="<?= htmlspecialchars($prodImg) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy" onerror="this.src='img/image-removebg-preview.png'">
                </a>
            </div>

            <div class="product-body">
                <span class="product-cat-name"><?= htmlspecialchars(ucfirst($catSlug)) ?></span>
                <h3 class="product-name">
                    <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>"><?= htmlspecialchars($p['nombre']) ?></a>
                </h3>
                <p class="product-snippet">
                    <?= htmlspecialchars($p['descripcion'] ?: 'Elaborada con 100% carne vacuna, queso seleccionado y pan recién horneado.') ?>
                </p>

                <div class="product-footer">
                    <div class="product-price-box">
                        <span class="price-currency">PRECIO</span>
                        <span class="price-amount">$<?= number_format($p['precio'], 0, ',', '.') ?></span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" 
                           class="btn-detalles-link" 
                           title="Ver detalles"
                           style="width: 38px; height: 38px; border-radius: 50%; background: #F1F5F9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                            <i class="fa-solid fa-eye"></i>
                        </a>

                        <button type="button" 
                                class="btn-add-fast" 
                                onclick="agregarCarritoAjax(<?= $p['idproducto'] ?>, 1, this)" 
                                title="Sumar al pedido">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span>Pedir</span>
                        </button>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <div class="text-center mt-5">
        <a href="productos.php" class="btn-hero-primary" style="padding:14px 36px;font-size:1rem;">
            <i class="fa-solid fa-burger"></i> Ver Todo el Menú (86 Opciones)
        </a>
    </div>
</section>

<!-- ============================================================
     4. BANNER CREADOR 3D PERSONALIZADO
     ============================================================ -->
<section class="custom-burger-teaser" style="margin-top:60px;">
    <div class="custom-burger-card">
        <div class="custom-burger-text">
            <span style="font-size:0.8rem;font-weight:900;color:var(--rojo);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:6px;">
                👨‍🍳 Simulador Interactivo
            </span>
            <h3 style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;text-transform:uppercase;">
                Armá tu hamburguesa a tu gusto
            </h3>
            <p>
                Elegí tus ingredientes capa por capa en nuestro creador interactivo: doble o triple medallón de carne, queso cheddar fundido, huevo frito, cebolla morada grillada, tocino crocante y aderezos especiales.
            </p>
            <a href="personalizado.php" class="btn-hero-primary">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Abrir Simulador 3D
            </a>
        </div>
        <div class="custom-burger-img" style="max-width:240px;">
            <img src="img/boom.jpg" alt="Hamburguesa Personalizada" style="max-width:100%;filter:drop-shadow(0 15px 25px rgba(0,0,0,0.25));border-radius:18px;">
        </div>
    </div>
</section>

<!-- ============================================================
     5. PROGRAMA CLUB MOSTAZA VIP
     ============================================================ -->
<section class="club-banner-section">
    <div class="club-banner-card">
        <div class="club-content-left">
            <span style="color:var(--amarillo);font-weight:900;font-size:0.82rem;letter-spacing:1px;text-transform:uppercase;">
                PROGRAMA DE BENEFICIOS EXCLUSIVOS
            </span>
            <h2>Club Mostaza: Comer rico tiene premio</h2>
            <p>Registrate gratis, acumulá puntos con cada pedido y canjealos por descuentos directos, hamburguesas completas y bebidas gratis.</p>

            <div class="club-perks-list">
                <div class="club-perk-item">
                    <i class="fa-solid fa-coins"></i>
                    <span><strong>Puntos en cada compra:</strong> Sumás puntos automáticamente que podés aplicar en el carrito.</span>
                </div>
                <div class="club-perk-item">
                    <i class="fa-solid fa-cake-candles"></i>
                    <span><strong>Regalo de Cumpleaños:</strong> Una hamburguesa gratis para celebrar tu día especial.</span>
                </div>
                <div class="club-perk-item">
                    <i class="fa-solid fa-tags"></i>
                    <span><strong>Cupones semanales:</strong> Descuentos de hasta el 30% en combos seleccionados.</span>
                </div>
            </div>

            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <a href="registro.php" class="btn-club-join">
                    <i class="fa-solid fa-user-plus"></i> Unirme al Club Gratis
                </a>
            <?php else: ?>
                <a href="perfil.php" class="btn-club-join">
                    <i class="fa-solid fa-crown"></i> Ver Mis Puntos y Cupones
                </a>
            <?php endif; ?>
        </div>

        <div class="club-visual-right">
            <div class="club-phone-mockup">
                <div class="mockup-header">
                    <span style="font-weight:900;color:var(--amarillo);font-size:0.9rem;">⭐ CLUB MOSTAZA</span>
                    <span style="font-size:0.75rem;color:#94A3B8;">Miembro Oficial</span>
                </div>
                <div class="mockup-points-box">
                    <span class="pts-label">Tus Puntos Acumulados</span>
                    <span class="pts-count">1.450</span>
                    <span style="font-size:0.75rem;color:#10B981;font-weight:700;">● ¡Canjeable por un Mega Combo!</span>
                </div>
                <div style="margin-top:16px;background:#1E293B;border-radius:10px;padding:12px;display:flex;align-items:center;gap:10px;">
                    <span style="font-size:1.4rem;">🎟️</span>
                    <div style="font-size:0.78rem;color:#CBD5E1;">
                        <strong style="color:#FFF;">Cupón 25% OFF</strong>
                        <div>Válido en Mega Doble Cuarto</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     6. SUCURSAL FORMOSA CENTRO & MAPA INTERACTIVO
     ============================================================ -->
<section style="padding:20px 4% 70px;max-width:1240px;margin:0 auto;">
    <div style="background:#FFFFFF;border-radius:24px;padding:40px;border:1px solid var(--border);box-shadow:var(--shadow-sm);display:grid;grid-template-columns:1.1fr 1fr;gap:35px;align-items:center;" class="local-teaser-box">
        <div>
            <span class="section-badge"><i class="fa-solid fa-store"></i> Nuestra Sucursal</span>
            <h2 style="font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:var(--text-main);margin-bottom:12px;text-transform:uppercase;">
                Mostaza Formosa Centro
            </h2>
            <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.6;margin-bottom:20px;">
                Vení a disfrutar de nuestras instalaciones con salón climatizado, área infantil, WiFi de alta velocidad, kioscos táctiles de autogestión y AutoMostaza.
            </p>

            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:24px;">
                <div style="display:flex;align-items:center;gap:12px;font-size:0.92rem;color:var(--text-main);">
                    <i class="fa-solid fa-location-dot" style="color:var(--rojo);font-size:1.2rem;width:22px;"></i>
                    <span><strong>Av. 25 de Mayo y Moreno</strong>, Formosa Capital</span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;font-size:0.92rem;color:var(--text-main);">
                    <i class="fa-regular fa-clock" style="color:var(--amarillo-dark);font-size:1.2rem;width:22px;"></i>
                    <span>Lunes a Domingos de <strong>10:00 a 00:00 hs</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;font-size:0.92rem;color:var(--text-main);">
                    <i class="fa-solid fa-motorcycle" style="color:var(--rojo);font-size:1.2rem;width:22px;"></i>
                    <span>Delivery Oficial en <strong>30 minutos</strong> a tu puerta</span>
                </div>
            </div>

            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <a href="locales.php" class="btn-hero-primary" style="padding:11px 24px;font-size:0.92rem;">
                    <i class="fa-solid fa-shop"></i> Información del Local
                </a>
                <a href="productos.php" class="btn-hero-secondary" style="padding:10px 22px;font-size:0.92rem;">
                    Pedir para Retiro
                </a>
            </div>
        </div>

        <div style="border-radius:18px;overflow:hidden;box-shadow:var(--shadow-md);border:1px solid var(--border);height:340px;">
            <!-- Google Maps Iframe Oficial Mostaza Formosa -->
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3580.4461714055283!2d-58.16660502545612!3d-26.18216057709014!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x945ca500269043ed%3A0x40145dc47c42b78e!2sMostaza!5e0!3m2!1ses-419!2sar!4v1781050980295!5m2!1ses-419!2sar"
                width="100%"
                height="100%"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<!-- ============================================================
     7. BENEFICIOS Y COMPROMISO DE CALIDAD
     ============================================================ -->
<div class="container py-4 mb-4">
    <div class="hero-trust-badges justify-content-center" style="background:#FFFFFF;border-radius:20px;padding:26px;box-shadow:var(--shadow-sm);border:1px solid var(--border);">
        <div class="trust-badge-item">
            <i class="fa-solid fa-burger" style="font-size:1.8rem;color:var(--rojo);"></i>
            <div>
                <strong>100% Carne Vacuna</strong>
                <span>Pura y sin conservantes</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-bread-slice" style="font-size:1.8rem;color:var(--amarillo-dark);"></i>
            <div>
                <strong>Pan Artesanal Brioche</strong>
                <span>Horneado en el día</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-truck-fast" style="font-size:1.8rem;color:var(--rojo);"></i>
            <div>
                <strong>Delivery en 30 Minutos</strong>
                <span>Empaque térmico sellado</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-shield-halved" style="font-size:1.8rem;color:#10B981;"></i>
            <div>
                <strong>Calidad Certificada</strong>
                <span>Normas de higiene BPM</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     8. REDES SOCIALES OFICIALES
     ============================================================ -->
<section style="padding:40px 4% 60px;text-align:center;">
    <span style="font-size:0.8rem;font-weight:900;color:var(--rojo);letter-spacing:1px;text-transform:uppercase;">Comunidad Oficial</span>
    <h2 style="font-family:'Montserrat',sans-serif;font-size:2rem;font-weight:900;color:var(--text-main);margin:6px 0 24px;text-transform:uppercase;">
        Seguinos en Redes Sociales
    </h2>
    <div class="social-icons" style="display:flex;justify-content:center;gap:18px;flex-wrap:wrap;">
        <a href="https://www.instagram.com/mostazaok/" target="_blank" rel="noopener" aria-label="Instagram" style="width:55px;height:55px;background:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--rojo);box-shadow:var(--shadow-sm);transition:all 0.25s;"><i class="fab fa-instagram"></i></a>
        <a href="https://www.facebook.com/MostazaOk/" target="_blank" rel="noopener" aria-label="Facebook" style="width:55px;height:55px;background:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--rojo);box-shadow:var(--shadow-sm);transition:all 0.25s;"><i class="fab fa-facebook"></i></a>
        <a href="https://www.tiktok.com/@mostazaok" target="_blank" rel="noopener" aria-label="TikTok" style="width:55px;height:55px;background:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--rojo);box-shadow:var(--shadow-sm);transition:all 0.25s;"><i class="fab fa-tiktok"></i></a>
        <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube" style="width:55px;height:55px;background:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--rojo);box-shadow:var(--shadow-sm);transition:all 0.25s;"><i class="fab fa-youtube"></i></a>
        <a href="https://www.linkedin.com/company/mostaza-y-pan-s-a-/" target="_blank" rel="noopener" aria-label="LinkedIn" style="width:55px;height:55px;background:#FFFFFF;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--rojo);box-shadow:var(--shadow-sm);transition:all 0.25s;"><i class="fab fa-linkedin"></i></a>
    </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>
