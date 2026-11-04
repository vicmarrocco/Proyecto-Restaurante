<?php
session_start();
require('conexion.php');
$paginaActiva = 'personalizado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Simulador 3D: Armá tu Hamburguesa · Mostaza Argentina</title>
  <!-- Base Framework (Bootstrap) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts & Font Awesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Estilos Oficiales Mostaza (Sobrescriben Bootstrap) -->
  <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>" />
  <link rel="stylesheet" href="perso.css?v=<?= filemtime('perso.css') ?>" />
  <link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>" />
</head>
<body>

<?php include('includes/header.php'); ?>

<?php if (!isset($_SESSION['usuario_id'])): ?>
<div class="aviso-sesion">
    <a href="login.php">Iniciá sesión</a> o <a href="registro.php">registrate</a> para agregar tu hamburguesa personalizada al carrito.
</div>
<?php endif; ?>

  <section class="pedidos-section" id="pedidos">

    <!-- ENCABEZADO -->
    <div class="pedidos-header">
      <h1 class="titulo-main">PEDIDOS <span class="titulo-cursiva">Personalizados</span></h1>
      <p class="subtitulo">— ¡ARMÁ TU HAMBURGUESA IDEAL! —</p>
    </div>

    <div class="pedidos-wrapper">


      <div class="burger-visual-col">

        <!-- Etiqueta decorativa izquierda -->
        <div class="label-crea">
          <span class="crea-text">CREÁ</span>
          <span class="crea-sub">TU COMBINACIÓN<br><strong>PERFECTA</strong></span>
          <div class="crea-arrow">→</div>
        </div>

        <!-- Sello "100% a tu gusto" -->
        <div class="sello">
          <span class="sello-icon">👨‍🍳</span>
          <span class="sello-pct">100%</span>
          <span class="sello-txt">A TU GUSTO</span>
        </div>

   
        <div class="burger-stack" id="burgerStack">

          <!-- CAPA 1: Pan superior -->
          <div class="burger-layer" id="layer-pan" data-layer="pan">
          <img src="img/panarriba.png" alt="Pan superior">
          </div>

           <!-- CAPA 2: Huevo -->
          <div class="burger-layer" id="layer-huevo" data-layer="huevo">
          <img src="img/huevo.png" alt="Pan superior">
          </div>

          <!-- CAPA 3: Tomate -->
          <div class="burger-layer" id="layer-tomate" data-layer="tomate">
           <img src="img/tomate.png" alt="Pan superior">
          </div>

            <!-- CAPA 3: Cebolla -->
          <div class="burger-layer" id="layer-cebolla" data-layer="cebolla">
           <img src="img/cebolla.png" alt="Cebolla">
          </div>

          <!-- CAPA 4: Lechuga -->
          <div class="burger-layer" id="layer-lechuga" data-layer="lechuga">
           <img src="img/lechuga.png" alt="Pan superior">
          </div>

          <!-- CAPA 5: Queso -->
          <div class="burger-layer" id="layer-queso" data-layer="queso">

           <img src="img/queso.png" alt="Pan superior">
          </div>

            <!-- CAPA 5: Tocino -->
          <div class="burger-layer" id="layer-tocino" data-layer="tocino">

           <img src="img/tocino.png" alt="Tocino">
          </div>
        
          <!-- CAPA 5: Pepinillos -->
          <div class="burger-layer" id="layer-pepino" data-layer="pepino">

           <img src="img/pepino.png" alt="Pepino">
          </div>

          <!-- CAPA 6: Carne -->
          <div class="burger-layer" id="layer-carne" data-layer="carne">
           <img src="img/carne.png" alt="Pan superior">
          </div>

          <!-- CAPA 7: Pan inferior -->
          <div class="burger-layer" id="layer-pan-bottom" data-layer="pan">
            <img src="img/panabajo.png" alt="Pan superior">
          </div>

        </div><!-- /burger-stack -->

        <!-- Etiqueta flotante acompañamiento -->
        <div class="label-acomp" id="labelAcomp">
          <div class="acomp-arrow">↗</div>
          <span>ELEGÍ TU<br><strong>ACOMPAÑAMIENTO</strong></span>
        </div>

        <div class="acomp-bubble" id="acompBubble">
          <span id="acompEmoji">🍟</span>
        </div>

        <!-- Texto bottom-left -->
        <p class="hint-text">Seleccioná los<br>ingredientes que<br>más te gusten</p>

      </div><!-- /burger-visual-col -->


      <!-- COLUMNA DERECHA: PANEL DE PERSONALIZACIÓN-->
      <div class="panel-col">

        <!-- ---- SECCIÓN 1: Arma tu hamburguesa ---- -->
        <div class="panel-card">
          <div class="panel-header">
            <span class="panel-num">1</span>
            <h2>ARMA TU HAMBURGUESA</h2>
          </div>

          <ul class="ingredient-list">

            <!-- Switch: Pan (Obligatorio) -->
            <li class="ingredient-item obligatorio" style="background:#FFFBEB;border-left:4px solid #F59E0B;padding-left:12px;">
              <div class="ing-icon">🍞</div>
              <div style="flex:1;">
                <span class="ing-name" style="font-weight:700;">Pan Tostado Mostaza</span>
                <span style="font-size:0.72rem;color:#B45309;font-weight:800;display:block;">BASE OBLIGATORIA (No se puede quitar)</span>
              </div>
              <label class="switch" style="cursor:not-allowed;" title="El pan es la base de la hamburguesa">
                <input type="checkbox" id="toggle-pan" data-layer="pan" checked disabled />
                <span class="slider" style="background:#16A34A;cursor:not-allowed;"></span>
              </label>
            </li>

             <!-- Switch: Huevo -->
            <li class="ingredient-item">
              <div class="ing-icon">🍳</div>
              <span class="ing-name">Huevo</span>
              <label class="switch">
                <input type="checkbox" id="toggle-huevo" data-layer="huevo" checked />
                <span class="slider"></span>
              </label>
            </li>


            <!-- Switch: Carne -->
            <li class="ingredient-item">
              <div class="ing-icon">🥩</div>
              <span class="ing-name">Carne</span>
              <label class="switch">
                <input type="checkbox" id="toggle-carne" data-layer="carne" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Switch: Queso -->
            <li class="ingredient-item">
              <div class="ing-icon">🧀</div>
              <span class="ing-name">Queso</span>
              <label class="switch">
                <input type="checkbox" id="toggle-queso" data-layer="queso" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Switch: Tocino -->
            <li class="ingredient-item">
              <div class="ing-icon">🥓</div>
              <span class="ing-name">Tocino</span>
              <label class="switch">
                <input type="checkbox" id="toggle-tocino" data-layer="tocino" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Switch: Cebolla morada -->
            <li class="ingredient-item">
              <div class="ing-icon">🧅</div>
              <span class="ing-name">Cebolla Morada</span>
              <label class="switch">
                <input type="checkbox" id="toggle-cebolla" data-layer="cebolla" checked />
                <span class="slider"></span>
              </label>
            </li>



             <!-- Switch: Pepino -->
            <li class="ingredient-item">
              <div class="ing-icon">🥒</div>
              <span class="ing-name">Pepino</span>
              <label class="switch">
                <input type="checkbox" id="toggle-pepino" data-layer="pepino" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Switch: Lechuga -->
            <li class="ingredient-item">
              <div class="ing-icon">🥬</div>
              <span class="ing-name">Lechuga</span>
              <label class="switch">
                <input type="checkbox" id="toggle-lechuga" data-layer="lechuga" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Switch: Tomate -->
            <li class="ingredient-item">
              <div class="ing-icon">🍅</div>
              <span class="ing-name">Tomate</span>
              <label class="switch">
                <input type="checkbox" id="toggle-tomate" data-layer="tomate" checked />
                <span class="slider"></span>
              </label>
            </li>

          </ul>
        </div><!-- /panel-card -->


        <!-- ---- SECCIÓN 2: Aderezos y Salsas ---- -->
        <div class="panel-card">
          <div class="panel-header">
            <span class="panel-num">2</span>
            <h2>ELEGÍ TUS ADEREZOS & SALSAS</h2>
          </div>

          <ul class="ingredient-list">
            <!-- Mayonesa -->
            <li class="ingredient-item">
              <div class="ing-icon">🥫</div>
              <div style="flex:1;">
                <span class="ing-name">Mayonesa Clásica</span>
                <span style="font-size:0.75rem;color:#16A34A;font-weight:700;display:block;">Sin cargo · $0</span>
              </div>
              <label class="switch">
                <input type="checkbox" class="toggle-aderezo" data-aderezo="mayonesa" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Ketchup -->
            <li class="ingredient-item">
              <div class="ing-icon">🍅</div>
              <div style="flex:1;">
                <span class="ing-name">Ketchup Mostaza</span>
                <span style="font-size:0.75rem;color:#16A34A;font-weight:700;display:block;">Sin cargo · $0</span>
              </div>
              <label class="switch">
                <input type="checkbox" class="toggle-aderezo" data-aderezo="ketchup" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Mostaza Suave & Dulce -->
            <li class="ingredient-item">
              <div class="ing-icon">🟡</div>
              <div style="flex:1;">
                <span class="ing-name">Mostaza Suave & Dulce</span>
                <span style="font-size:0.75rem;color:#16A34A;font-weight:700;display:block;">Sin cargo · $0</span>
              </div>
              <label class="switch">
                <input type="checkbox" class="toggle-aderezo" data-aderezo="mostaza" checked />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Barbacoa BBQ -->
            <li class="ingredient-item">
              <div class="ing-icon">🍯</div>
              <div style="flex:1;">
                <span class="ing-name">Barbacoa Ahumada (BBQ)</span>
                <span style="font-size:0.75rem;color:var(--rojo);font-weight:700;display:block;">+$300</span>
              </div>
              <label class="switch">
                <input type="checkbox" class="toggle-aderezo" data-aderezo="barbacoa" />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Cheddar Fundido -->
            <li class="ingredient-item">
              <div class="ing-icon">🧀</div>
              <div style="flex:1;">
                <span class="ing-name">Salsa Cheddar Fundido</span>
                <span style="font-size:0.75rem;color:var(--rojo);font-weight:700;display:block;">+$450</span>
              </div>
              <label class="switch">
                <input type="checkbox" class="toggle-aderezo" data-aderezo="cheddar" />
                <span class="slider"></span>
              </label>
            </li>

            <!-- Salsa Especial Mostaza -->
            <li class="ingredient-item">
              <div class="ing-icon">⭐</div>
              <div style="flex:1;">
                <span class="ing-name">Salsa Especial Mostaza (Mega)</span>
                <span style="font-size:0.75rem;color:var(--rojo);font-weight:700;display:block;">+$350</span>
              </div>
              <label class="switch">
                <input type="checkbox" class="toggle-aderezo" data-aderezo="salsa_especial" />
                <span class="slider"></span>
              </label>
            </li>
          </ul>
        </div><!-- /panel-card -->


        <!-- ---- SECCIÓN 3: Acompañamientos ---- -->
        <div class="panel-card">
          <div class="panel-header">
            <span class="panel-num">3</span>
            <h2>ELEGÍ TUS ACOMPAÑAMIENTOS</h2>
          </div>

          <div class="acomp-grid">

            <!-- Opción: Papas Fritas -->
            <label class="acomp-card" id="card-papas">
              <input type="radio" name="acompanamiento" value="papas" checked />
              <div class="acomp-ph">🍟</div>
              <span>Papas Fritas</span>
              <div class="acomp-radio-dot"></div>
            </label>

            <!-- Opción: Nuggets de Pollo -->
            <label class="acomp-card" id="card-nuggets">
              <input type="radio" name="acompanamiento" value="nuggets" />
              <div class="acomp-ph">🍗</div>
              <span>Nuggets de Pollo</span>
              <div class="acomp-radio-dot"></div>
            </label>

          </div>
        </div><!-- /panel-card -->


        <!-- ---- RESUMEN DEL PEDIDO ---- -->
        <div class="pedido-resumen">
          <div class="resumen-info">
            <div class="resumen-icon">📋</div>
            <div>
              <strong>TU PEDIDO</strong>
              <p id="resumenTexto">Personalizá tu hamburguesa a tu gusto!</p>
            </div>
          </div>
          <div class="resumen-precio">$ <span id="precioTotal">0,00</span></div>
        </div>

        <button class="btn-agregar" id="btnAgregar">
          AGREGAR AL PEDIDO <span class="btn-arrow">→</span>
        </button>

      </div><!-- /panel-col -->

    </div><!-- /pedidos-wrapper -->

  </section>

<?php include('includes/footer.php'); ?>
<script src="script.js"></script>
</body>
</html>
