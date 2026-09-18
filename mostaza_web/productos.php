<?php
// ============================================================
// productos.php
// Menú Digital Completo y Catálogo Premium de Mostaza Argentina
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'productos';

// Parámetro de categoría por URL (ej: productos.php?cat=megacombos)
$catFiltroUrl = isset($_GET['cat']) ? trim(strtolower($_GET['cat'])) : 'todos';
$busquedaUrl = isset($_GET['q']) ? trim($_GET['q']) : '';

// Consultar todos los productos activos
$queryProd = "SELECT * FROM producto WHERE activo = 1 ORDER BY FIELD(categoria, 'megacombos', 'exclusivos', 'pollo', 'paravos', 'cajitas', 'ensaladas', 'postres'), idproducto ASC";
$resProd = $conn->query($queryProd);
$productos = [];
$totalesPorCat = [];

if ($resProd) {
    while ($row = $resProd->fetch_assoc()) {
        $productos[] = $row;
        $c = $row['categoria'] ?? 'otros';
        $totalesPorCat[$c] = ($totalesPorCat[$c] ?? 0) + 1;
    }
}
$totalGeneral = count($productos);

// Mapa amigable de categorías
$categoriasInfo = [
    'todos'       => ['nombre' => 'Todos',               'icono' => 'fa-utensils',  'count' => $totalGeneral],
    'megacombos'  => ['nombre' => 'Mega Combos',         'icono' => 'fa-burger',    'count' => $totalesPorCat['megacombos'] ?? 0],
    'exclusivos'  => ['nombre' => 'Exclusivos',          'icono' => 'fa-crown',     'count' => $totalesPorCat['exclusivos'] ?? 0],
    'pollo'       => ['nombre' => 'Pollo Crispy',        'icono' => 'fa-drumstick-bite', 'count' => $totalesPorCat['pollo'] ?? 0],
    'paravos'     => ['nombre' => 'Para Vos (Veggie)',   'icono' => 'fa-leaf',      'count' => $totalesPorCat['paravos'] ?? 0],
    'cajitas'     => ['nombre' => 'Cajitas Kids',        'icono' => 'fa-box-open',  'count' => $totalesPorCat['cajitas'] ?? 0],
    'ensaladas'   => ['nombre' => 'Ensaladas',           'icono' => 'fa-bowl-rice', 'count' => $totalesPorCat['ensaladas'] ?? 0],
    'postres'     => ['nombre' => 'Postres & Cafetería', 'icono' => 'fa-ice-cream', 'count' => $totalesPorCat['postres'] ?? 0],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Digital Completo · Mostaza Argentina</title>
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

<!-- HERO CATÁLOGO -->
<section class="menu-catalog-hero">
    <div class="menu-catalog-hero-inner">
        <div class="menu-hero-left">
            <span class="menu-hero-badge"><i class="fa-solid fa-fire"></i> Menú Oficial Mostaza</span>
            <h1>Hamburguesas en Serio <span>y Mucho Más</span></h1>
            <p>
                Explorá nuestra carta completa elaborada con 100% carne vacuna seleccionada, pollo super crocante, pan horneado en el día y las opciones más abundantes de Argentina.
            </p>
            <div class="d-flex align-items-center gap-3">
                <a href="#menuProductsGrid" class="btn-hero-primary" style="padding: 10px 22px; font-size: 0.88rem;">
                    <i class="fa-solid fa-list-check"></i> Ver Carta Completa
                </a>
                <a href="personalizado.php" class="btn-hero-secondary" style="padding: 10px 22px; font-size: 0.88rem;">
                    <i class="fa-solid fa-sliders"></i> Armá tu Burger
                </a>
            </div>
        </div>

        <div class="menu-hero-action-card d-none d-lg-flex">
            <div class="action-icon">🍔</div>
            <div>
                <h3>¿Querés armarla vos?</h3>
                <p>Elegí tus ingredientes capa por capa en nuestro simulador 3D interactivo.</p>
                <a href="personalizado.php" style="color:var(--amarillo);font-weight:800;font-size:0.85rem;display:inline-flex;align-items:center;gap:6px;">
                    Personalizar Ahora <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- EXPLORADOR VISUAL DE CATEGORÍAS (TARJETAS OFICIALES) -->
<section class="section-official-categories" style="padding: 30px 4% 10px; max-width: 1240px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <span class="section-badge"><i class="fa-solid fa-layer-group"></i> Menú Por Secciones</span>
            <h2 style="font-family:'Montserrat',sans-serif;font-size:1.4rem;font-weight:900;text-transform:uppercase;color:var(--text-main);margin:4px 0 0;">
                Elegí qué querés comer hoy
            </h2>
        </div>
        <button type="button" class="btn-hero-secondary" onclick="filtrarPorCategoria('todos')" style="padding: 8px 18px; font-size: 0.82rem;">
            <i class="fa-solid fa-border-all"></i> Ver Todo el Menú
        </button>
    </div>

    <div class="official-cat-grid" style="grid-template-columns: repeat(4, 1fr); gap: 16px;">
        <!-- 1. Mega Combos -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'megacombos' ? 'active-cat-card' : '' ?>" 
             data-cat="megacombos" 
             onclick="filtrarPorCategoria('megacombos'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/card1.jpg" alt="Mega Combos" class="cat-bg" onerror="this.src='img/doblwcheddar.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Combo XL + Papas + Bebida</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Mega Combos</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <!-- 2. Exclusivos -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'exclusivos' ? 'active-cat-card' : '' ?>" 
             data-cat="exclusivos" 
             onclick="filtrarPorCategoria('exclusivos'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/cardex.jpg" alt="Exclusivos" class="cat-bg" onerror="this.src='img/cuarto.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Hamburguesas de Autor</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Exclusivos</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <!-- 3. Pollo Crispy -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'pollo' ? 'active-cat-card' : '' ?>" 
             data-cat="pollo" 
             onclick="filtrarPorCategoria('pollo'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/card2.jpg" alt="Pollo Crispy" class="cat-bg" onerror="this.src='img/pollocrispy.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">100% Pechuga Crocante</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Pollo Crispy</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <!-- 4. Armá tu Burger 3D -->
        <a href="personalizado.php" class="official-cat-card" style="height: 175px; border-color: var(--amarillo);">
            <img src="img/cardper.jpg" alt="Armá tu Burger" class="cat-bg" onerror="this.src='img/boom.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Simulador Interactivo</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem; color: var(--amarillo);">Armá tu Burger</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem; color: var(--amarillo);">Personalizar 3D <i class="fa-solid fa-wand-magic-sparkles"></i></span>
            </div>
        </a>

        <!-- 5. Para Vos (Veggie / Sin TACC) -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'paravos' ? 'active-cat-card' : '' ?>" 
             data-cat="paravos" 
             onclick="filtrarPorCategoria('paravos'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/card3.jpg" alt="Para Vos" class="cat-bg" onerror="this.src='img/cerovegiee.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Plant-Based & Sin Gluten</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Para Vos</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <!-- 6. Cajitas Kids -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'cajitas' ? 'active-cat-card' : '' ?>" 
             data-cat="cajitas" 
             onclick="filtrarPorCategoria('cajitas'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/card5.jpg" alt="Cajitas Kids" class="cat-bg" onerror="this.src='img/cajita1.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Menú Infantil + Juguete</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Cajitas Kids</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <!-- 7. Ensaladas -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'ensaladas' ? 'active-cat-card' : '' ?>" 
             data-cat="ensaladas" 
             onclick="filtrarPorCategoria('ensaladas'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/card4.jpg" alt="Ensaladas" class="cat-bg" onerror="this.src='img/ensaladapalta1.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Frescas & Nutritivas</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Ensaladas</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>

        <!-- 8. Postres & Cafetería -->
        <div class="official-cat-card visual-cat-item <?= $catFiltroUrl === 'postres' ? 'active-cat-card' : '' ?>" 
             data-cat="postres" 
             onclick="filtrarPorCategoria('postres'); document.getElementById('stickyNav').scrollIntoView({behavior:'smooth'});" 
             style="height: 175px; cursor: pointer;">
            <img src="img/card7.jpg" alt="Postres y Cafetería" class="cat-bg" onerror="this.src='img/shake.jpg'">
            <div class="cat-card-info">
                <span class="cat-card-tag">Helados, Shakes & Café</span>
                <h3 class="cat-card-title" style="font-size: 1.25rem;">Postres & Café</h3>
                <span class="cat-card-btn" style="font-size: 0.75rem;">Filtrar Menú <i class="fa-solid fa-arrow-down"></i></span>
            </div>
        </div>
    </div>
</section>

<!-- BARRA DE CATEGORÍAS STICKY -->
<nav class="sticky-menu-nav" id="stickyNav">
    <div class="sticky-menu-inner">
        <div class="category-pills-scroll" id="categoryPillsContainer">
            <?php foreach ($categoriasInfo as $slug => $cat): ?>
                <button type="button" 
                        class="cat-pill-btn <?= ($catFiltroUrl === $slug || ($catFiltroUrl === '' && $slug === 'todos')) ? 'active' : '' ?>" 
                        data-category="<?= $slug ?>" 
                        onclick="filtrarPorCategoria('<?= $slug ?>', this)">
                    <i class="fa-solid <?= $cat['icono'] ?>"></i>
                    <span><?= htmlspecialchars($cat['nombre']) ?></span>
                    <span style="opacity: 0.7; font-size: 0.75rem;">(<?= $cat['count'] ?>)</span>
                </button>
            <?php endforeach; ?>

            <a href="personalizado.php" class="cat-pill-btn custom-burger-pill">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>Armá tu Burger 3D</span>
            </a>
        </div>
    </div>
</nav>

<!-- CONTROLES Y BUSCADOR EN VIVO -->
<div class="menu-controls-bar">
    <div class="menu-search-wrapper">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" 
               id="menuSearchInput" 
               class="menu-search-input" 
               placeholder="Buscar por nombre, combo, ingrediente (ej: Cheddar, Bacon, Pollo)..." 
               value="<?= htmlspecialchars($busquedaUrl) ?>"
               oninput="ejecutarFiltroCombinado()"
               autocomplete="off">
        <button type="button" class="clear-search-btn" id="clearSearchBtn" onclick="limpiarBusqueda()" title="Borrar búsqueda">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="menu-count-badge" id="menuCountBadge">
        Mostrando <strong id="visibleCount"><?= $totalGeneral ?></strong> productos
    </div>
</div>

<!-- CHIPS DE FILTRO RÁPIDO -->
<div class="quick-tags-strip">
    <span style="font-size: 0.78rem; font-weight: 800; color: #94A3B8; text-transform: uppercase; margin-right: 4px;">Filtro rápido:</span>
    <button type="button" class="quick-tag active" data-tag="todos" onclick="filtrarPorTag('todos', this)"><i class="fa-solid fa-asterisk"></i> Todos</button>
    <button type="button" class="quick-tag" data-tag="bacon" onclick="filtrarPorTag('bacon', this)"><i class="fa-solid fa-bacon"></i> Con Bacon</button>
    <button type="button" class="quick-tag" data-tag="cheddar" onclick="filtrarPorTag('cheddar', this)"><i class="fa-solid fa-cheese"></i> Cheddar Lovers</button>
    <button type="button" class="quick-tag" data-tag="pollo" onclick="filtrarPorTag('pollo', this)"><i class="fa-solid fa-drumstick-bite"></i> Pollo</button>
    <button type="button" class="quick-tag" data-tag="veggie" onclick="filtrarPorTag('veggie', this)"><i class="fa-solid fa-seedling"></i> Veggie / Sin TACC</button>
    <button type="button" class="quick-tag" data-tag="dulce" onclick="filtrarPorTag('dulce', this)"><i class="fa-solid fa-cookie-bite"></i> Postres & Dulces</button>
    <button type="button" class="quick-tag" data-tag="promo" onclick="filtrarPorTag('promo', this)"><i class="fa-solid fa-bolt"></i> Menos de $10.000</button>
</div>

<!-- GRID DE PRODUCTOS -->
<section class="section-featured-products" style="padding-top: 10px;">
    <div class="products-modern-grid" id="menuProductsGrid">
        <?php foreach ($productos as $p): 
            $catSlug = strtolower(trim($p['categoria'] ?? ''));
            $prodNombre = $p['nombre'] ?? '';
            $prodDesc = $p['descripcion'] ?? '';
            $prodPrecio = floatval($p['precio'] ?? 0);

            // Imagen segura con fallback
            $imgSrc = $p['imagen'] ?? 'image-removebg-preview.png';
            if (strpos($imgSrc, 'img/') !== 0 && strpos($imgSrc, 'http') !== 0) {
                $imgSrc = 'img/' . $imgSrc;
            }

            // Flag decorativo
            $badgeTexto = 'Sabor Único';
            $badgeGold = false;
            if ($catSlug === 'megacombos') {
                $badgeTexto = 'Combo XL';
                $badgeGold = true;
            } elseif ($catSlug === 'exclusivos') {
                $badgeTexto = 'Insignia';
            } elseif ($catSlug === 'pollo') {
                $badgeTexto = '100% Pechuga';
            } elseif ($catSlug === 'paravos') {
                $badgeTexto = 'Plant Based / Sin TACC';
                $badgeGold = true;
            } elseif ($catSlug === 'cajitas') {
                $badgeTexto = 'Kids + Juguete';
            } elseif ($catSlug === 'postres') {
                $badgeTexto = 'Tentación';
                $badgeGold = true;
            }
        ?>
        <article class="modern-product-card" 
                 data-categoria="<?= htmlspecialchars($catSlug) ?>"
                 data-nombre="<?= strtolower(htmlspecialchars($prodNombre)) ?>"
                 data-desc="<?= strtolower(htmlspecialchars($prodDesc)) ?>"
                 data-precio="<?= $prodPrecio ?>">
            
            <div class="product-img-wrapper">
                <span class="product-flag <?= $badgeGold ? 'gold' : '' ?>"><?= htmlspecialchars($badgeTexto) ?></span>
                <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" title="Ver detalles e información nutricional">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" 
                         alt="<?= htmlspecialchars($prodNombre) ?>" 
                         loading="lazy" 
                         onerror="this.src='img/image-removebg-preview.png'">
                </a>
            </div>

            <div class="product-body">
                <span class="product-cat-name"><?= htmlspecialchars($categoriasInfo[$catSlug]['nombre'] ?? ucfirst($catSlug)) ?></span>
                <h3 class="product-name">
                    <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>"><?= htmlspecialchars($prodNombre) ?></a>
                </h3>
                <p class="product-snippet">
                    <?= htmlspecialchars(!empty($prodDesc) ? $prodDesc : 'Elaborado al momento con ingredientes seleccionados de primera calidad.') ?>
                </p>

                <div class="product-footer">
                    <div class="product-price-box">
                        <span class="price-currency">PRECIO FINAL</span>
                        <span class="price-amount">$<?= number_format($prodPrecio, 0, ',', '.') ?></span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" 
                           class="btn-detalles-link" 
                           title="Ver receta completa y alérgenos"
                           style="width: 38px; height: 38px; border-radius: 50%; background: #F1F5F9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transition: all 0.2s;">
                            <i class="fa-solid fa-eye"></i>
                        </a>

                        <button type="button" 
                                class="btn-add-fast" 
                                onclick="agregarCarritoAjax(<?= $p['idproducto'] ?>, 1, this)"
                                title="Sumar 1 al pedido">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span>Pedir</span>
                        </button>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>

        <!-- ESTADO VACÍO (CUANDO NO HAY COINCIDENCIAS EN LA BÚSQUEDA) -->
        <div class="empty-catalog-state" id="emptyCatalogState" style="display: none;">
            <span class="empty-icon">🔍🍔</span>
            <h3>No encontramos coincidencias</h3>
            <p>Probá buscando con otros términos como "Cheddar", "Papas", "Crispy" o restablecé los filtros.</p>
            <button type="button" class="btn-hero-primary" onclick="limpiarBusqueda()" style="padding: 10px 24px;">
                <i class="fa-solid fa-rotate-left"></i> Restablecer Filtros
            </button>
        </div>
    </div>
</section>

<!-- BANNER BURGER BUILDER -->
<section class="custom-burger-teaser">
    <div class="custom-burger-card">
        <div class="custom-burger-text">
            <span style="font-size:0.8rem;font-weight:900;color:var(--rojo);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:6px;">
                👨‍🍳 Simulador en Tiempo Real
            </span>
            <h3>¿Querés una hamburguesa 100% a tu medida?</h3>
            <p>
                En nuestro creador 3D podés elegir la cantidad de medallones, agregar queso derretido, huevo frito, cebolla morada grillada, tocino crocante y aderezos especiales.
            </p>
            <a href="personalizado.php" class="btn-hero-primary">
                <i class="fa-solid fa-sliders"></i> Abrir Creador de Hamburguesas
            </a>
        </div>
        <div class="custom-burger-img" style="max-width: 220px;">
            <img src="img/boom.jpg" alt="Hamburguesa a Medida" style="max-width:100%;filter:drop-shadow(0 15px 25px rgba(0,0,0,0.2));border-radius:16px;">
        </div>
    </div>
</section>

<!-- SECCIÓN BENEFICIOS -->
<div class="container py-4">
    <div class="hero-trust-badges justify-content-center">
        <div class="trust-badge-item">
            <i class="fa-solid fa-certificate"></i>
            <div>
                <strong>100% Carne Vacuna</strong>
                <span>Pura y sin conservantes</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-bread-slice"></i>
            <div>
                <strong>Pan Artesanal</strong>
                <span>Horneado en el día</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-truck-fast"></i>
            <div>
                <strong>Envío Térmico</strong>
                <span>Llega caliente en 30 min</span>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- SCRIPT DE FILTRADO Y BÚSQUEDA INTERACTIVA -->
<script>
let categoriaActiva = '<?= htmlspecialchars($catFiltroUrl) ?>' || 'todos';
let tagActivo = 'todos';

function filtrarPorCategoria(slug, btn) {
    categoriaActiva = slug;

    // Actualizar botones activos en nav
    document.querySelectorAll('.cat-pill-btn').forEach(b => b.classList.remove('active'));
    if (btn && btn.classList.contains('cat-pill-btn')) {
        btn.classList.add('active');
    } else {
        document.querySelector(`.cat-pill-btn[data-category="${slug}"]`)?.classList.add('active');
    }

    // Actualizar tarjetas visuales activas
    document.querySelectorAll('.visual-cat-item').forEach(c => {
        if (c.dataset.cat === slug) {
            c.classList.add('active-cat-card');
        } else {
            c.classList.remove('active-cat-card');
        }
    });

    // Cambiar URL sin recargar para persistir estado
    const url = new URL(window.location);
    if (slug === 'todos') {
        url.searchParams.delete('cat');
    } else {
        url.searchParams.set('cat', slug);
    }
    window.history.replaceState({}, '', url);

    ejecutarFiltroCombinado();
}

function filtrarPorTag(tag, btn) {
    tagActivo = tag;
    document.querySelectorAll('.quick-tag').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    ejecutarFiltroCombinado();
}

function limpiarBusqueda() {
    document.getElementById('menuSearchInput').value = '';
    document.getElementById('clearSearchBtn').style.display = 'none';
    tagActivo = 'todos';
    document.querySelectorAll('.quick-tag').forEach(t => t.classList.remove('active'));
    document.querySelector('.quick-tag[data-tag="todos"]')?.classList.add('active');
    categoriaActiva = 'todos';
    document.querySelectorAll('.cat-pill-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.cat-pill-btn[data-category="todos"]')?.classList.add('active');
    
    const url = new URL(window.location);
    url.searchParams.delete('cat');
    window.history.replaceState({}, '', url);

    ejecutarFiltroCombinado();
}

function ejecutarFiltroCombinado() {
    const input = document.getElementById('menuSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');
    const query = (input.value || '').toLowerCase().trim();

    if (clearBtn) {
        clearBtn.style.display = query.length > 0 ? 'block' : 'none';
    }

    const cards = document.querySelectorAll('.modern-product-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const cat = card.dataset.categoria || '';
        const nom = card.dataset.nombre || '';
        const desc = card.dataset.desc || '';
        const precio = parseFloat(card.dataset.precio || 0);

        // 1. Filtro por categoría principal
        const coincideCat = (categoriaActiva === 'todos' || cat === categoriaActiva);

        // 2. Filtro por texto de búsqueda
        const coincideBusqueda = (query === '' || nom.includes(query) || desc.includes(query));

        // 3. Filtro por tag rápido
        let coincideTag = true;
        if (tagActivo === 'bacon') {
            coincideTag = nom.includes('bacon') || desc.includes('bacon');
        } else if (tagActivo === 'cheddar') {
            coincideTag = nom.includes('cheddar') || desc.includes('cheddar');
        } else if (tagActivo === 'pollo') {
            coincideTag = cat === 'pollo' || nom.includes('pollo') || desc.includes('pollo');
        } else if (tagActivo === 'veggie') {
            coincideTag = cat === 'paravos' || nom.includes('veggie') || desc.includes('veggie') || nom.includes('sin tacc');
        } else if (tagActivo === 'dulce') {
            coincideTag = cat === 'postres';
        } else if (tagActivo === 'promo') {
            coincideTag = precio <= 10000;
        }

        if (coincideCat && coincideBusqueda && coincideTag) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Actualizar badge de conteo
    const countEl = document.getElementById('visibleCount');
    if (countEl) countEl.innerText = visibleCount;

    // Controlar estado vacío
    const emptyState = document.getElementById('emptyCatalogState');
    if (emptyState) {
        emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('menuSearchInput');
    const hasQuery = searchInput && searchInput.value.trim() !== '';

    if (categoriaActiva && categoriaActiva !== 'todos') {
        const activeBtn = document.querySelector(`.cat-pill-btn[data-category="${categoriaActiva}"]`);
        if (activeBtn) {
            document.querySelectorAll('.cat-pill-btn').forEach(b => b.classList.remove('active'));
            activeBtn.classList.add('active');
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        document.querySelectorAll('.visual-cat-item').forEach(c => {
            if (c.dataset.cat === categoriaActiva) {
                c.classList.add('active-cat-card');
            } else {
                c.classList.remove('active-cat-card');
            }
        });
    }

    if (hasQuery) {
        const clearBtn = document.getElementById('clearSearchBtn');
        if (clearBtn) clearBtn.style.display = 'block';
        setTimeout(() => {
            const ctrlBar = document.querySelector('.menu-controls-bar');
            if (ctrlBar) ctrlBar.scrollIntoView({ behavior: 'smooth' });
        }, 250);
    }

    ejecutarFiltroCombinado();
});
</script>

</body>
</html>

