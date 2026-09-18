<?php
// ============================================================
// includes/footer.php
// Footer corporativo moderno y profesional para Mostaza
// ============================================================
?>
<footer class="site-footer">
    <div class="footer-top-strip">
        <div class="container footer-strip-inner">
            <div class="strip-item">
                <i class="fa-solid fa-burger"></i>
                <div>
                    <strong>100% Carne Vacuna</strong>
                    <span>Sin conservantes ni aditivos</span>
                </div>
            </div>
            <div class="strip-item">
                <i class="fa-solid fa-truck-fast"></i>
                <div>
                    <strong>Delivery en 30 Minutos</strong>
                    <span>Empaque térmico sellado</span>
                </div>
            </div>
            <div class="strip-item">
                <i class="fa-solid fa-crown"></i>
                <div>
                    <strong>Club Mostaza</strong>
                    <span>Puntos y beneficios exclusivos</span>
                </div>
            </div>
            <div class="strip-item">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <strong>Calidad Certificada</strong>
                    <span>Normas de higiene BPM</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container footer-main-content">
        <div class="row g-4">
            <!-- Columna 1: Marca y Redes -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <img src="img/image-removebg-preview.png" alt="Mostaza">
                        <span>MOSTAZA</span>
                    </div>
                    <p class="footer-bio">
                        La cadena de comida rápida con mayor crecimiento de Argentina. Pasión por el sabor auténtico, pan horneado en el día y las combinaciones más generosas.
                    </p>
                    <div class="footer-social-links">
                        <a href="https://www.instagram.com/mostazaok/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="https://www.facebook.com/MostazaOk/" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="https://www.tiktok.com/@mostazaok" target="_blank" rel="noopener" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        <a href="https://www.linkedin.com/company/mostaza-y-pan-s-a-/" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <!-- Columna 2: Menú -->
            <div class="col-lg-2 col-md-6 col-6">
                <h4 class="footer-col-title">Nuestro Menú</h4>
                <ul class="footer-links">
                    <li><a href="categoria.php?cat=exclusivos">Hamburguesas Insignia</a></li>
                    <li><a href="categoria.php?cat=megacombos">Mega Combos</a></li>
                    <li><a href="categoria.php?cat=pollo">Pollo Crispy</a></li>
                    <li><a href="personalizado.php">Armá tu Burger 3D</a></li>
                    <li><a href="categoria.php?cat=ensaladas">Ensaladas Frescas</a></li>
                    <li><a href="categoria.php?cat=postres">Postres y Cafetería</a></li>
                </ul>
            </div>

            <!-- Columna 3: Empresa y Locales -->
            <div class="col-lg-3 col-md-6 col-6">
                <h4 class="footer-col-title">Sucursales y Ayuda</h4>
                <ul class="footer-links">
                    <li><a href="locales.php">Sucursal Formosa Centro</a></li>
                    <li><a href="sobrenosotros.php">Nuestra Historia</a></li>
                    <li><a href="contacto.php">Atención al Cliente</a></li>
                    <li><a href="perfil.php">Beneficios Club Mostaza</a></li>
                </ul>
            </div>

            <!-- Columna 4: Contacto y Medios de Pago -->
            <div class="col-lg-3 col-md-6">
                <h4 class="footer-col-title">Horarios y Pedidos</h4>
                <div class="footer-contact-item">
                    <i class="fa-regular fa-clock"></i>
                    <div>
                        <strong>Lunes a Domingo</strong>
                        <span>10:00 a 00:00 hs (Madrugada en Salón)</span>
                    </div>
                </div>
                <div class="footer-contact-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <strong>Sucursal Formosa</strong>
                        <span>Av. 25 de Mayo y Moreno, Formosa</span>
                    </div>
                </div>
                <div class="footer-contact-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <strong>Línea de Delivery</strong>
                        <span>+54 (370) 442-9900</span>
                    </div>
                </div>

                <div class="footer-payments mt-3">
                    <span class="pay-title">Medios de Pago Aceptados:</span>
                    <div class="pay-badges">
                        <span class="pay-badge"><i class="fa-regular fa-credit-card"></i> Tarjetas</span>
                        <span class="pay-badge"><i class="fa-solid fa-qrcode"></i> Mercado Pago</span>
                        <span class="pay-badge"><i class="fa-solid fa-money-bill-wave"></i> Efectivo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra Inferior de Copyright -->
    <div class="footer-bottom-bar">
        <div class="container footer-bottom-inner">
            <p>© <?= date('Y') ?> Mostaza y Pan S.A. Todos los derechos reservados · Proyecto Restaurante EPET Nº 7 "Vicente Arcadio Salemi".</p>
            <div class="footer-bottom-legal">
                <a href="#">Términos y Condiciones</a>
                <a href="#">Política de Privacidad</a>
                <a href="#">Defensa del Consumidor</a>
            </div>
        </div>
    </div>
</footer>

<!-- Toast de Notificaciones para Agregar al Carrito en Vivo -->
<div id="mostazaToast" class="mostaza-toast">
    <div class="toast-icon">✅</div>
    <div class="toast-content">
        <strong id="toastTitle">¡Producto agregado!</strong>
        <span id="toastMsg">Se agregó a tu carrito de compras.</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script Global para Agregar al Carrito con AJAX sin recargar -->
<script>
function agregarCarritoAjax(idproducto, cantidad = 1, btn = null) {
    if (btn) {
        btn.disabled = true;
        btn.classList.add('loading');
    }

    fetch('agregar_carrito.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'idproducto=' + encodeURIComponent(idproducto) + '&cantidad=' + encodeURIComponent(cantidad) + '&ajax=1'
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('loading');
        }

        if (data.ok) {
            mostrarToast('¡Agregado!', data.mensaje || 'Se sumó a tu pedido correctamente.');
            // Actualizar contadores en header
            const badge = document.getElementById('headerCartCount');
            if (badge) {
                badge.innerText = data.total_items;
                badge.style.display = 'inline-block';
            }
            const totalH = document.querySelector('.btn-cart-header .cart-total');
            if (totalH && data.total_formateado) {
                totalH.innerText = data.total_formateado;
            }
        } else {
            mostrarToast('Atención', data.error || 'No se pudo agregar el producto.');
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
        // Fallback clásico
        window.location.href = 'agregar_carrito.php?id=' + idproducto;
    });
}

function mostrarToast(titulo, mensaje) {
    const toast = document.getElementById('mostazaToast');
    const tTitle = document.getElementById('toastTitle');
    const tMsg = document.getElementById('toastMsg');
    if (!toast) return;

    if (tTitle) tTitle.innerText = titulo;
    if (tMsg) tMsg.innerText = mensaje;

    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3200);
}
</script>
