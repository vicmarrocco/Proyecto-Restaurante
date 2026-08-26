<?php
session_start();
require('conexion.php');
$paginaActiva = 'productos';

$categorias = [
    ['slug' => 'personalizado', 'nombre' => 'Personalizado', 'img' => 'cardper.jpg', 'link' => 'personalizado.php'],
    ['slug' => 'exclusivos',    'nombre' => 'Exclusivos',     'img' => 'cardex.jpg',  'link' => 'categoria.php?cat=exclusivos'],
    ['slug' => 'megacombos',    'nombre' => 'Mega Combos',    'img' => 'card1.jpg',   'link' => 'categoria.php?cat=megacombos'],
    ['slug' => 'pollo',         'nombre' => 'Pollo',          'img' => 'card2.jpg',   'link' => 'categoria.php?cat=pollo'],
    ['slug' => 'ensaladas',     'nombre' => 'Ensaladas',      'img' => 'card4.jpg',   'link' => 'categoria.php?cat=ensaladas'],
    ['slug' => 'paravos',       'nombre' => 'Para Vos',       'img' => 'card3.jpg',   'link' => 'categoria.php?cat=paravos'],
    ['slug' => 'cajitas',       'nombre' => 'Cajitas',        'img' => 'card5.jpg',   'link' => 'categoria.php?cat=cajitas'],
    ['slug' => 'postres',       'nombre' => 'Postres y Cafetería', 'img' => 'card7.jpg', 'link' => 'categoria.php?cat=postres'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Mostaza</title>
    <link rel="stylesheet" href="productos.css">
    <link rel="stylesheet" href="tienda.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('includes/header.php'); ?>

<section class="productos">
    <h2>Nuestros Productos</h2>
    <div class="productos-grid">
        <?php foreach ($categorias as $c): ?>
        <a href="<?= $c['link'] ?>" class="card-link">
            <div class="card-producto">
                <div class="img-holder">
                    <img src="img/<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['nombre']) ?>" onerror="this.src='img/image-removebg-preview.png'">
                </div>
                <h3><?= htmlspecialchars($c['nombre']) ?></h3>
            </div>
        </a>
        <?php endforeach; ?>
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
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-tiktok"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
    </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>
