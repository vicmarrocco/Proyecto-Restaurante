<?php
// ============================================================
// categoria.php
// Listado de productos por categoría con buscador y agregado al carrito.
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'productos';

$catalogo = [
    'exclusivos' => ['nombre' => 'Exclusivos',          'sub' => 'Nuestras hamburguesas insignia', 'css' => 'exclusivos.css'],
    'megacombos' => ['nombre' => 'Mega Combos',         'sub' => 'Para los que tienen mucha hambre', 'css' => 'exclusivos.css'],
    'pollo'      => ['nombre' => 'Pollo',               'sub' => 'Crocante por fuera, jugoso por dentro', 'css' => 'exclusivos.css'],
    'ensaladas'  => ['nombre' => 'Ensaladas',           'sub' => 'Opciones frescas y livianas', 'css' => 'ensalada.css'],
    'paravos'    => ['nombre' => 'Para Vos',            'sub' => 'Opciones veggie y sin TACC', 'css' => 'exclusivos.css'],
    'cajitas'    => ['nombre' => 'Cajitas',             'sub' => 'El menú favorito de los más chicos', 'css' => 'exclusivos.css'],
    'postres'    => ['nombre' => 'Postres y Cafetería', 'sub' => 'Para cerrar con algo dulce', 'css' => 'postres.css'],
];

$cat = $_GET['cat'] ?? '';
if (!isset($catalogo[$cat])) {
    header("Location: productos.php");
    exit();
}
$info = $catalogo[$cat];
$logueado = isset($_SESSION['usuario_id']);

$stmt = $conn->prepare("SELECT * FROM producto WHERE categoria = ? AND activo = 1 ORDER BY idproducto");
$stmt->bind_param("s", $cat);
$stmt->execute();
$productos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($info['nombre']) ?> - Mostaza</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($info['css']) ?>">
    <link rel="stylesheet" href="tienda.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .filter-search-box {
            max-width: 500px;
            margin: 20px auto 30px;
            display: flex;
            align-items: center;
            background: #fff;
            border-radius: 50px;
            padding: 8px 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #ddd;
        }
        .filter-search-box input {
            border: none;
            outline: none;
            width: 100%;
            padding: 8px;
            font-size: 0.95rem;
            font-family: inherit;
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<?php if (!$logueado): ?>
<div class="aviso-sesion">
    <a href="login.php">Iniciá sesión</a> o <a href="registro.php">registrate</a> para agregar productos al carrito.
</div>
<?php endif; ?>

<section class="productos-destacados">

    <div class="titulo-productos">
        <h2><?= htmlspecialchars($info['nombre']) ?></h2>
        <p><?= htmlspecialchars($info['sub']) ?></p>
        
        <div class="filter-search-box">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--rojo);margin-right:8px;"></i>
            <input type="text" id="filtroProductos" placeholder="Buscar en <?= htmlspecialchars($info['nombre']) ?>..." oninput="filtrarCatalogo()">
        </div>
    </div>

    <div class="productos-container" id="productosGrid">
        <?php if ($productos->num_rows === 0): ?>
            <p style="text-align:center;color:#666;">Por el momento no hay productos cargados en esta categoría.</p>
        <?php endif; ?>

        <?php while ($p = $productos->fetch_assoc()): ?>
        <div class="producto-card" data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>" data-desc="<?= strtolower(htmlspecialchars($p['descripcion'] ?? '')) ?>">
            <div class="producto-img">
                <?php
                $prodImg = $p['imagen'] ?? 'image-removebg-preview.png';
                if (strpos($prodImg, 'img/') !== 0 && strpos($prodImg, 'http') !== 0) {
                    $prodImg = 'img/' . $prodImg;
                }
                ?>
                <a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" title="Ver detalles e ingredientes">
                    <img src="<?= htmlspecialchars($prodImg) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" onerror="this.src='img/image-removebg-preview.png'">
                </a>
            </div>
            <h3><a href="detalle_producto.php?id=<?= $p['idproducto'] ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($p['nombre']) ?></a></h3>
            <?php if (!empty($p['descripcion'])): ?>
                <p class="producto-desc"><?= htmlspecialchars($p['descripcion']) ?></p>
            <?php endif; ?>
            <div class="producto-precio">$<?= number_format($p['precio'], 0, ',', '.') ?></div>

            <div class="producto-actions">
                <?php if ($logueado): ?>
                    <form method="post" action="agregar_carrito.php" style="display:flex;gap:10px;align-items:center;">
                        <input type="hidden" name="idproducto" value="<?= $p['idproducto'] ?>">
                        <input type="hidden" name="volver" value="categoria.php?cat=<?= urlencode($cat) ?>">
                        <input type="number" name="cantidad" value="1" min="1" max="20" class="producto-qty">
                        <button type="submit" class="btn-agregar-carrito"><i class="fa-solid fa-cart-plus"></i> Agregar</button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn-login-chico"><i class="fa-solid fa-lock"></i> Pedir</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

</section>

<section class="beneficios">
    <div><i class="fa-solid fa-burger"></i><h3>Ingredientes Frescos</h3></div>
    <div><i class="fa-solid fa-truck-fast"></i><h3>Entrega Rápida</h3></div>
    <div><i class="fa-solid fa-award"></i><h3>Calidad Garantizada</h3></div>
</section>

<section class="social">
    <h2>SEGUINOS</h2>
    <div class="social-icons">
        <a href="https://www.instagram.com/mostazaok/"><i class="fab fa-instagram"></i></a>
        <a href="https://www.facebook.com/MostazaOk/"><i class="fab fa-facebook"></i></a>
        <a href="https://www.tiktok.com/@mostazaok"><i class="fab fa-tiktok"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
    </div>
</section>

<?php include('includes/footer.php'); ?>

<script>
function filtrarCatalogo() {
    const q = document.getElementById('filtroProductos').value.toLowerCase().trim();
    document.querySelectorAll('.producto-card').forEach(card => {
        const nom = card.dataset.nombre || '';
        const desc = card.dataset.desc || '';
        if (nom.includes(q) || desc.includes(q)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
