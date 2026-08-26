<?php
session_start();
require('conexion.php');
$paginaActiva = 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="tienda.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('includes/header.php'); ?>

<?php if (!isset($_SESSION['usuario_id'])): ?>
<div class="aviso-sesion">
    Bienvenido a Mostaza. <a href="login.php">Iniciá sesión</a> o <a href="registro.php">registrate</a> para hacer tu pedido.
</div>
<?php endif; ?>

<!-- HERO -->
<section class="hero">
    <div class="hero-img">
        <img src="img/descarga.png" alt="Hamburguesa">
        <span class="sparkle uno"></span>
        <span class="sparkle dos"></span>
    </div>
    <div class="hero-content">
        <div class="semi-circle"></div>
        <h1>LAS MEJORES HAMBURGUESAS</h1>
        <p>Promociones que no vas a encontrar en ningún otro lugar.</p>
        <div class="hero-buttons">
            <a href="productos.php" class="btn-menu">Ver Menú</a>
            <a href="productos.php" class="btn-order">Pedir Online</a>
        </div>
    </div>
</section>

<section class="promos">
<div id="miCarrusel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#miCarrusel" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#miCarrusel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#miCarrusel" data-bs-slide-to="2"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active"><img src="img/dibu.jpg" class="d-block w-100"></div>
    <div class="carousel-item"><img src="img/trueno.jpg" class="d-block w-100"></div>
    <div class="carousel-item"><img src="img/papas.jpg" class="d-block w-100"></div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#miCarrusel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#miCarrusel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
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
        <a href=""><i class="fab fa-youtube"></i></a>
        <a href=""><i class="fab fa-twitter"></i></a>
        <a href=""><i class="fab fa-linkedin"></i></a>
    </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>
