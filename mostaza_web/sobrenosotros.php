<?php
// ============================================================
// sobrenosotros.php
// Historia, Visión y Compromiso de Mostaza Argentina
// ============================================================
session_start();
require('conexion.php');
$paginaActiva = 'nosotros';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nosotros · Pasión Argentina · Mostaza</title>
    <!-- Base Framework (Bootstrap) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Estilos Oficiales Mostaza (Sobrescriben Bootstrap) -->
    <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
    <link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #870002 0%, #A30002 45%, #111827 100%);
            color: #FFFFFF;
            padding: 75px 4% 65px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid var(--amarillo);
        }
        .about-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: -35px auto 50px;
            padding: 0 20px;
            position: relative;
            z-index: 3;
        }
        .stat-card {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            padding: 24px 20px;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: transform 0.25s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--amarillo);
        }
        .stat-num {
            font-size: 2.4rem;
            font-weight: 900;
            color: var(--rojo);
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .about-story-section {
            max-width: 1200px;
            margin: 0 auto 70px;
            padding: 0 4%;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        @media (max-width: 880px) {
            .about-story-section { grid-template-columns: 1fr; gap: 30px; }
        }
        .about-story-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 18px;
        }
        .about-story-text p {
            color: #475569;
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .about-img-box {
            position: relative;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .about-img-box img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        .timeline-section {
            background: linear-gradient(135deg, #A30002 0%, #7A0002 100%);
            color: #FFFFFF;
            padding: 70px 4%;
            text-align: center;
        }
        .timeline-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 40px auto 0;
        }
        .timeline-item {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: var(--radius-md);
            padding: 24px 16px;
            backdrop-filter: blur(8px);
            transition: all 0.3s;
        }
        .timeline-item:hover {
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-6px);
        }
        .timeline-year {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--amarillo);
            margin-bottom: 8px;
            display: block;
        }
        .timeline-text {
            font-size: 0.9rem;
            color: #F8FAFC;
            line-height: 1.4;
        }
        .pillars-section {
            max-width: 1200px;
            margin: 70px auto;
            padding: 0 4%;
        }
        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 26px;
        }
        .pillar-card {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            padding: 36px 28px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: transform 0.25s;
        }
        .pillar-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--amarillo);
        }
        .pillar-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #FFFBEB;
            color: var(--rojo);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .pillar-card h3 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 12px;
        }
        .pillar-card p {
            color: #64748B;
            font-size: 0.95rem;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<!-- HERO -->
<section class="about-hero">
    <div style="max-width: 800px; margin: 0 auto;">
        <span class="menu-hero-badge"><i class="fa-solid fa-flag"></i> Pasión 100% Argentina</span>
        <h1 style="font-family:'Playfair Display',serif; font-size: 3rem; font-weight: 900; margin: 14px 0;">
            Hamburguesas en Serio, <span style="color:var(--amarillo);">Hechas en el País</span>
        </h1>
        <p style="color:#CBD5E1; font-size: 1.1rem; line-height: 1.6;">
            Nacimos con una premisa clara: ofrecer hamburguesas gigantes, de calidad artesanal suprema, con ingredientes nobles y el inconfundible sabor nacional.
        </p>
    </div>
</section>

<!-- ESTADÍSTICAS CORPORATIVAS -->
<div class="about-stats-grid">
    <div class="stat-card">
        <div class="stat-num">+250</div>
        <div class="stat-label">Locales en Argentina</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">100%</div>
        <div class="stat-label">Carne Vacuna Pura</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">+10.000</div>
        <div class="stat-label">Puestos de Trabajo</div>
    </div>
    <div class="stat-card">
        <div class="stat-num">25+</div>
        <div class="stat-label">Años de Trayectoria</div>
    </div>
</div>

<!-- HISTORIA -->
<section class="about-story-section">
    <div class="about-story-text">
        <span style="font-size:0.8rem;font-weight:800;color:var(--rojo);text-transform:uppercase;letter-spacing:1px;display:block;margin-bottom:6px;">
            Nuestra Esencia
        </span>
        <h2>La historia de un sueño que conquistó a todos</h2>
        <p>
            En 1999 abrió sus puertas el primer local Mostaza. Desde el día uno entendimos que el paladar argentino exige generosidad: hamburguesas más grandes, carne de primera calidad, queso fundido en su punto justo y pan recién horneado.
        </p>
        <p>
            Hoy nos enorgullece ser la única cadena de origen nacional que compite y supera a las corporaciones extranjeras en todo el país, siendo sponsors oficiales de la Selección Argentina y llevando el sabor a cada provincia.
        </p>
        <div class="d-flex gap-3 mt-4">
            <a href="productos.php" class="btn-hero-primary" style="padding: 10px 22px;">
                <i class="fa-solid fa-burger"></i> Conocer Nuestro Menú
            </a>
            <a href="locales.php" class="btn-hero-secondary" style="padding: 10px 22px;">
                <i class="fa-solid fa-location-dot"></i> Buscar Sucursales
            </a>
        </div>
    </div>

    <div class="about-img-box">
        <img src="img/local.png" alt="Local Mostaza" onerror="this.src='img/fondo.jpg'">
    </div>
</section>

<!-- LÍNEA DE TIEMPO -->
<section class="timeline-section">
    <span style="font-size:0.8rem;font-weight:800;color:var(--amarillo);text-transform:uppercase;letter-spacing:1px;">
        Hitos Históricos
    </span>
    <h2 style="font-family:'Playfair Display',serif; font-size: 2.4rem; font-weight: 900; margin-top: 6px;">
        Nuestra Evolución
    </h2>

    <div class="timeline-grid">
        <div class="timeline-item">
            <span class="timeline-year">1999</span>
            <p class="timeline-text">Apertura del primer punto de venta e inicio del viaje de sabor.</p>
        </div>
        <div class="timeline-item">
            <span class="timeline-year">2005</span>
            <p class="timeline-text">Lanzamiento del sistema de franquicias y expansión en centros comerciales.</p>
        </div>
        <div class="timeline-item">
            <span class="timeline-year">2012</span>
            <p class="timeline-text">Inauguración del formato Mega Local a la calle y AutoMostaza 24hs.</p>
        </div>
        <div class="timeline-item">
            <span class="timeline-year">2018</span>
            <p class="timeline-text">Mostaza se convierte en Sponsor Oficial de la Selección Argentina de Fútbol (AFA).</p>
        </div>
        <div class="timeline-item">
            <span class="timeline-year">2026</span>
            <p class="timeline-text">Más de 250 sucursales activas y ecosistema digital de vanguardia.</p>
        </div>
    </div>
</section>

<!-- PILARES DE CALIDAD -->
<section class="pillars-section">
    <div class="text-center mb-5">
        <span style="font-size:0.8rem;font-weight:800;color:var(--rojo);text-transform:uppercase;letter-spacing:1px;">
            Compromiso Inquebrantable
        </span>
        <h2 style="font-family:'Playfair Display',serif; font-size: 2.2rem; font-weight: 900; margin-top: 4px;">
            Nuestros Pilares Fundamentales
        </h2>
    </div>

    <div class="pillars-grid">
        <div class="pillar-card">
            <div class="pillar-icon"><i class="fa-solid fa-bullseye"></i></div>
            <h3>Misión</h3>
            <p>Brindar la mejor experiencia de fast food gourmet de la región, destacándonos por la abundancia, la calidez de atención y la frescura de nuestros ingredientes.</p>
        </div>

        <div class="pillar-card">
            <div class="pillar-icon"><i class="fa-solid fa-eye"></i></div>
            <h3>Visión</h3>
            <p>Consolidarnos como la marca de hamburguesas más querida e innovadora de Latinoamérica, impulsando tecnología, empleo y desarrollo sustentable.</p>
        </div>

        <div class="pillar-card">
            <div class="pillar-icon"><i class="fa-solid fa-heart"></i></div>
            <h3>Pasión y Calidad</h3>
            <p>Cada hamburguesa que sale de nuestra cocina es elaborada en el instante con carne 100% vacuna garantizada y pan horneado en el día.</p>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>

