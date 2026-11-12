<?php
// ============================================================
// categoria.php
// Catálogo por Categoría con Diseño Corporativo Moderno de Mostaza
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'productos';

$catalogo = [
    'megacombos' => ['nombre' => 'Mega Combos',         'sub' => 'Nuestros combos más grandes y abundantes con papas y gaseosa', 'icono' => 'fa-burger', 'badge' => 'Combo XL'],
    'exclusivos' => ['nombre' => 'Exclusivos',          'sub' => 'Nuestras hamburguesas insignia de autor con 100% carne vacuna', 'icono' => 'fa-crown', 'badge' => 'Línea Insignia'],
    'pollo'      => ['nombre' => 'Pollo Crispy',        'sub' => 'Pechuga super crocante por fuera y tierna y jugosa por dentro', 'icono' => 'fa-drumstick-bite', 'badge' => '100% Pechuga'],
    'paravos'    => ['nombre' => 'Para Vos',            'sub' => 'Opciones veggies, plant-based y recetas certificadas sin TACC', 'icono' => 'fa-leaf', 'badge' => 'Veggie & Gluten Free'],
    'cajitas'    => ['nombre' => 'Cajitas Mostaza Kids', 'sub' => 'El menú más divertido y completo para los más pequeños', 'icono' => 'fa-box-open', 'badge' => 'Menú Infantil + Juguete'],
    'ensaladas'  => ['nombre' => 'Ensaladas Frescas',   'sub' => 'Combinaciones nutritivas y frescas elaboradas al instante', 'icono' => 'fa-bowl-rice', 'badge' => 'Livianas & Frescas'],
    'postres'    => ['nombre' => 'Postres y Cafetería', 'sub' => 'Tentate con helados, conos, medialunas y café de especialidad', 'icono' => 'fa-ice-cream', 'badge' => 'Dulce Tentación'],
];

$cat = trim(strtolower($_GET['cat'] ?? ''));
if (!isset($catalogo[$cat])) {
    header("Location: productos.php");
    exit();
}

$info = $catalogo[$cat];
$logueado = isset($_SESSION['usuario_id']);

$stmt = $conn->prepare("SELECT * FROM producto WHERE categoria = ? AND activo = 1 ORDER BY precio DESC, idproducto ASC");
$stmt->bind_param("s", $cat);
$stmt->execute();
$productos = $stmt->get_result();
$totalCat = $productos->num_rows;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($info['nombre']) ?> · Menú Mostaza</title>
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

<!-- HERO DE CATEGORÍA -->
<section class="menu-catalog-hero" style="padding: 45px 4% 35px;">
    <div class="menu-catalog-hero-inner">
        <div class="menu-hero-left">
            <div style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #94A3B8;">
                <a href="index.php" style="color:#CBD5E1;">Inicio</a>
                <span>›</span>
                <a href="productos.php" style="color:#CBD5E1;">Menú Completo</a>
                <span>›</span>
                <span style="color:var(--amarillo);font-weight:700;"><?= htmlspecialchars($info['nombre']) ?></span>
            </div>

            <span class="menu-hero-badge"><i class="fa-solid <?= $info['icono'] ?>"></i> <?= htmlspecialchars($info['badge']) ?></span>
            <h1 style="font-size: 2.5rem;"><?= htmlspecialchars($info['nombre']) ?></h1>
            <p><?= htmlspecialchars($info['sub']) ?></p>
        </div>

        <div class="menu-hero-action-card d-none d-lg-flex" style="padding: 20px;">
            <div class="action-icon" style="font-size:2.2rem;">⚡</div>
            <div>
                <h3>Delivery Directo</h3>
                <p>Pedí tu menú preferido y recibilo caliente en tu domicilio.</p>
                <a href="carrito.php" style="color:var(--amarillo);font-weight:800;font-size:0.85rem;display:inline-flex;align-items:center;gap:6px;">
                    Ver Mi Pedido <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- NAVEGACIÓN RÁPIDA ENTRE OTRAS CATEGORÍAS -->
<nav class="sticky-menu-nav">
    <div class="sticky-menu-inner">
        <div class="category-pills-scroll">
            <a href="productos.php" class="cat-pill-btn">
                <i class="fa-solid fa-border-all"></i>
                <span>Ver Todo el Menú</span>
            </a>

            <?php foreach ($catalogo as $slug => $c): ?>
                <a href="categoria.php?cat=<?= urlencode($slug) ?>" 
                   class="cat-pill-btn <?= ($cat === $slug) ? 'active' : '' ?>">
                    <i class="fa-solid <?= $c['icono'] ?>"></i>
                    <span><?= htmlspecialchars($c['nombre']) ?></span>
                </a>
            <?php endforeach; ?>

            <a href="personalizado.php" class="cat-pill-btn custom-burger-pill">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span>Armá tu Burger 3D</span>
            </a>
        </div>
    </div>
</nav>

<!-- CONTROLES Y BUSCADOR DENTRO DE LA CATEGORÍA -->
<div class="menu-controls-bar">
    <div class="menu-search-wrapper">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" 
               id="catSearchInput" 
               class="menu-search-input" 
               placeholder="Buscar en <?= htmlspecialchars($info['nombre']) ?>..." 
               oninput="filtrarCategoriaCards()"
               autocomplete="off">
        <button type="button" class="clear-search-btn" id="clearCatBtn" onclick="limpiarFiltroCategoria()" title="Borrar búsqueda">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="menu-count-badge">
        Mostrando <strong id="catCountEl"><?= $totalCat ?></strong> opciones disponibles
    </div>
</div>

<!-- GRID DE PRODUCTOS DE LA CATEGORÍA -->
<section class="section-featured-products" style="padding-top: 15px;">
    <div class="products-modern-grid" id="catProductsGrid">
        <?php if ($totalCat === 0): ?>
            <div class="empty-catalog-state">
                <span class="empty-icon">🍔</span>
                <h3>Próximamente más productos</h3>
                <p>Estamos cocinando nuevas propuestas irresistibles para esta sección.</p>
                <a href="productos.php" class="btn-hero-primary" style="display:inline-flex;padding:10px 24px;">
                    Ver otros productos del menú
                </a>
            </div>
        <?php endif; ?>

        <?php while ($p = $productos->fetch_assoc()): 
            $prodNombre = $p['nombre'] ?? '';
            $prodDesc = $p['descripcion'] ?? '';
            $prodPrecio = floatval($p['precio'] ?? 0);

            // Imagen segura con fallback
            $imgSrc = $p['imagen'] ?? 'image-removebg-preview.png';
            if (strpos($imgSrc, 'img/') !== 0 && strpos($imgSrc, 'http') !== 0) {
                $imgSrc = 'img/' . $imgSrc;
            }
        ?>
        <article class="modern-product-card" 
                 data-nombre="<?= strtolower(htmlspecialchars($prodNombre)) ?>"
                 data-desc="<?= strtolower(htmlspecialchars($prodDesc)) ?>">
            
            <div class="product-img-wrapper">
                <span class="product-flag"><?= htmlspecialchars($info['badge']) ?></span>
                <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" title="Ver detalles del producto">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" 
                         alt="<?= htmlspecialchars($prodNombre) ?>" 
                         loading="lazy" 
                         onerror="this.src='img/image-removebg-preview.png'">
                </a>
            </div>

            <div class="product-body">
                <span class="product-cat-name"><?= htmlspecialchars($info['nombre']) ?></span>
                <h3 class="product-name">
                    <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>"><?= htmlspecialchars($prodNombre) ?></a>
                </h3>
                <p class="product-snippet">
                    <?= htmlspecialchars(!empty($prodDesc) ? $prodDesc : 'Elaborado en el instante con ingredientes frescos y seleccionados.') ?>
                </p>

                <div class="product-footer">
                    <div class="product-price-box">
                        <span class="price-currency">PRECIO</span>
                        <span class="price-amount">$<?= number_format($prodPrecio, 0, ',', '.') ?></span>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" 
                           class="btn-detalles-link" 
                           title="Ver receta y alérgenos"
                           style="width: 38px; height: 38px; border-radius: 50%; background: #F1F5F9; color: #475569; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transition: all 0.2s;">
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
        <?php endwhile; ?>

        <!-- ESTADO VACÍO DE BÚSQUEDA -->
        <div class="empty-catalog-state" id="catEmptyState" style="display: none;">
            <span class="empty-icon">🔍</span>
            <h3>No encontramos resultados</h3>
            <p>Intentá con otro nombre o limpiá la búsqueda para ver todas las opciones.</p>
            <button type="button" class="btn-hero-primary" onclick="limpiarFiltroCategoria()" style="padding: 10px 22px;">
                <i class="fa-solid fa-rotate-left"></i> Ver todas las opciones
            </button>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<div class="container py-4">
    <div class="hero-trust-badges justify-content-center">
        <div class="trust-badge-item">
            <i class="fa-solid fa-burger"></i>
            <div>
                <strong>Ingredientes Frescos</strong>
                <span>Preparados en el acto</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-truck-fast"></i>
            <div>
                <strong>Entrega Rápida</strong>
                <span>Delivery seguro y térmico</span>
            </div>
        </div>
        <div class="trust-badge-item">
            <i class="fa-solid fa-award"></i>
            <div>
                <strong>Calidad Garantizada</strong>
                <span>Sabor auténtico Mostaza</span>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script>
function filtrarCategoriaCards() {
    const input = document.getElementById('catSearchInput');
    const clearBtn = document.getElementById('clearCatBtn');
    const q = (input.value || '').toLowerCase().trim();

    if (clearBtn) clearBtn.style.display = q.length > 0 ? 'block' : 'none';

    const cards = document.querySelectorAll('#catProductsGrid .modern-product-card');
    let visible = 0;

    cards.forEach(card => {
        const nom = card.dataset.nombre || '';
        const desc = card.dataset.desc || '';
        if (q === '' || nom.includes(q) || desc.includes(q)) {
            card.style.display = 'flex';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    const countEl = document.getElementById('catCountEl');
    if (countEl) countEl.innerText = visible;

    const empty = document.getElementById('catEmptyState');
    if (empty) empty.style.display = (visible === 0) ? 'block' : 'none';
}

function limpiarFiltroCategoria() {
    document.getElementById('catSearchInput').value = '';
    filtrarCategoriaCards();
}
</script>

</body>
</html>

