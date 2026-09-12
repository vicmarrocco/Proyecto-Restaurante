<?php
session_start();
require('conexion.php');
$paginaActiva = 'nosotros';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mostaza - Sobre Nosotros</title>
<link rel="stylesheet" href="sn.css">
<link rel="stylesheet" href="tienda.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php include('includes/header.php'); ?>

<section class="historia">
    <div class="historia-texto">
        <h2>Nuestra Historia</h2>
        <p>
            Desde 1999, Mostaza se convirtió en una de las cadenas de hamburguesas
            más importantes de Argentina.
        </p>
        <p>
            Hoy contamos con más de <strong>250 locales</strong>.
        </p>
    </div>

    <div class="historia-img">
        <img src="img/local.png" alt="Local Mostaza">
    </div>
</section>

<section class="timeline">
    <h2>Nuestra Evolución</h2>

    <div class="timeline-container">
        <div class="evento"><span>1999</span><p>Fundación.</p></div>
        <div class="evento"><span>2001</span><p>Franquicias.</p></div>
        <div class="evento"><span>2012</span><p>Primer local.</p></div>
        <div class="evento"><span>2016</span><p>Expansión internacional.</p></div>
        <div class="evento"><span>2018</span><p>Sponsor AFA.</p></div>
        <div class="evento"><span>2025</span><p>250 locales.</p></div>
    </div>
</section>

<div class="about-extra">
    <div class="box">
        <i class="fa-solid fa-bullseye"></i>
        <h3>Misión</h3>
        <p>Ofrecer las mejores hamburguesas.</p>
    </div>

    <div class="box">
        <i class="fa-solid fa-eye"></i>
        <h3>Visión</h3>
        <p>Ser líderes en la región.</p>
    </div>

    <div class="box">
        <i class="fa-solid fa-heart"></i>
        <h3>Pasión</h3>
        <p>El sabor y la abundancia.</p>
    </div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
