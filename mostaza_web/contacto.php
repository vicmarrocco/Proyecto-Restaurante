<?php
session_start();
require('conexion.php');
require('mail/enviar_correo.php');
$paginaActiva = '';

$error = '';
$ok = '';

function validar($data) {
    return htmlspecialchars(trim(stripslashes($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre  = validar($_POST['nombre'] ?? '');
    $email   = validar($_POST['email'] ?? '');
    $mensaje = validar($_POST['mensaje'] ?? '');

    if (empty($nombre) || empty($mensaje)) {
        $error = 'Completá tu nombre y el mensaje.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresá un correo electrónico válido.';
    } else {
        $envio = enviarMensajeContacto($nombre, $email, $mensaje);
        if ($envio['ok']) {
            $ok = '¡Gracias! Recibimos tu mensaje, te vamos a responder a la brevedad.';
        } else {
            $error = 'No pudimos enviar tu mensaje. ' . $envio['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atención al Cliente y Contacto · Mostaza Argentina</title>
    <!-- Base Framework (Bootstrap) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Estilos Oficiales Mostaza (Sobrescriben Bootstrap) -->
    <link rel="stylesheet" href="style.css?v=<?= filemtime('style.css') ?>">
    <link rel="stylesheet" href="auth.css?v=<?= filemtime('auth.css') ?>">
    <link rel="stylesheet" href="tienda.css?v=<?= filemtime('tienda.css') ?>">
    <style>
        .contact-section-wrap {
            max-width: 1100px;
            margin: 40px auto 70px;
            padding: 0 20px;
        }
        .contact-card-grid {
            display: grid;
            grid-template-columns: 1fr 1.3fr;
            gap: 30px;
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        @media (max-width: 850px) {
            .contact-card-grid { grid-template-columns: 1fr; }
        }
        .contact-info-side {
            background: linear-gradient(135deg, #7A0002 0%, #A30002 45%, #111827 100%);
            color: #FFFFFF;
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .contact-form-side {
            padding: 40px 36px;
        }
        .info-pill-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }
        .info-pill-item i {
            font-size: 1.3rem;
            color: var(--amarillo);
            margin-top: 3px;
        }
        .info-pill-item strong {
            display: block;
            font-size: 0.95rem;
            color: #FFFFFF;
            margin-bottom: 3px;
        }
        .info-pill-item span {
            font-size: 0.85rem;
            color: #94A3B8;
            line-height: 1.4;
        }
        .contact-input-group {
            margin-bottom: 18px;
        }
        .contact-input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }
        .contact-input-group input,
        .contact-input-group textarea {
            width: 100%;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
        }
        .contact-input-group input:focus,
        .contact-input-group textarea:focus {
            border-color: var(--rojo);
            box-shadow: 0 0 0 3px rgba(163,0,2,0.08);
        }
        .btn-submit-contact {
            background: var(--rojo);
            color: #FFFFFF;
            border: none;
            padding: 14px 28px;
            border-radius: var(--radius-full);
            font-weight: 900;
            font-size: 0.95rem;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit-contact:hover {
            background: var(--rojo-dark);
        }
    </style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="contact-section-wrap">
    <div style="text-align: center; margin-bottom: 30px;">
        <span class="menu-hero-badge"><i class="fa-solid fa-headset"></i> Estamos para ayudarte</span>
        <h1 style="font-family:'Playfair Display',serif; font-size: 2.6rem; font-weight: 900; margin-top: 8px; color: var(--text-main);">
            Atención al Cliente y Contacto
        </h1>
        <p style="color: #64748B; max-width: 550px; margin: 0 auto;">
            ¿Tenés alguna consulta sobre tu pedido, sugerencia sobre nuestro servicio o propuesta comercial? Escribinos y te responderemos en el menor tiempo posible.
        </p>
    </div>

    <div class="contact-card-grid">
        <!-- Lado Izquierdo: Información Directa -->
        <div class="contact-info-side">
            <div>
                <h3 style="font-family:'Playfair Display',serif; font-size: 1.8rem; font-weight: 900; color: var(--amarillo); margin-bottom: 8px;">
                    Mostaza Argentina
                </h3>
                <p style="color: #CBD5E1; font-size: 0.9rem; line-height: 1.5; margin-bottom: 30px;">
                    Canal oficial de atención al cliente, consultas corporativas y soporte de pedidos online.
                </p>

                <div class="info-pill-item">
                    <i class="fa-solid fa-clock"></i>
                    <div>
                        <strong>Horarios de Atención</strong>
                        <span>Lunes a Domingos de 10:00 a 00:00 hs</span>
                    </div>
                </div>

                <div class="info-pill-item">
                    <i class="fa-solid fa-envelope"></i>
                    <div>
                        <strong>Correo Electrónico</strong>
                        <a href="mailto:soporte@mostazaweb.com.ar" style="color:#CBD5E1;text-decoration:none;">soporte@mostazaweb.com.ar</a>
                    </div>
                </div>

                <div class="info-pill-item">
                    <i class="fa-brands fa-whatsapp" style="color:#25D366;"></i>
                    <div>
                        <strong>WhatsApp Directo</strong>
                        <a href="https://wa.me/5493704000000?text=Hola%20Mostaza,%20tengo%20una%20consulta" target="_blank" style="color:#FBB728;font-weight:700;text-decoration:none;">
                            +54 9 370 400-0000 <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.75rem;"></i>
                        </a>
                    </div>
                </div>

                <div class="info-pill-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <strong>Sucursal Formosa Centro</strong>
                        <span>Av. 25 de Mayo 150, Formosa, Argentina</span>
                    </div>
                </div>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                <span style="font-size: 0.75rem; color: #CBD5E1; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">
                    Seguinos en redes
                </span>
                <div class="footer-social-links" style="justify-content: flex-start; gap: 10px;">
                    <a href="https://www.instagram.com/mostazaok/" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://www.facebook.com/MostazaOk/" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.tiktok.com/@mostazaok" target="_blank" rel="noopener"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="https://www.linkedin.com/company/mostaza-y-pan-s-a-/" target="_blank" rel="noopener"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>

        <!-- Lado Derecho: Formulario de Mensaje -->
        <div class="contact-form-side">
            <h3 style="font-size: 1.4rem; font-weight: 900; color: var(--text-main); margin-bottom: 6px;">
                Envianos un mensaje
            </h3>
            <p style="color: #64748B; font-size: 0.88rem; margin-bottom: 24px;">
                Completá el siguiente formulario y nuestro equipo de atención se contactará con vos.
            </p>

            <?php if ($error): ?>
                <div class="flash flash-error" style="margin: 0 0 20px;"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($ok): ?>
                <div class="flash flash-ok" style="margin: 0 0 20px;"><?= $ok ?></div>
            <?php endif; ?>

            <form method="post" action="contacto.php">
                <div class="contact-input-group">
                    <label><i class="fa-solid fa-user"></i> Nombre completo *</label>
                    <input type="text" name="nombre" placeholder="Ej: Juan Pérez" required 
                           value="<?= htmlspecialchars($_POST['nombre'] ?? ($_SESSION['usuario_nombre'] ?? '')) ?>">
                </div>

                <div class="contact-input-group">
                    <label><i class="fa-solid fa-envelope"></i> Correo electrónico de contacto *</label>
                    <input type="email" name="email" placeholder="Ej: juanperez@ejemplo.com" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['usuario_email'] ?? '')) ?>">
                </div>

                <div class="contact-input-group">
                    <label><i class="fa-solid fa-comment-dots"></i> Mensaje o consulta *</label>
                    <textarea name="mensaje" placeholder="Describí tu consulta, sugerencia o pedido con el mayor detalle posible..." rows="5" required><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn-submit-contact">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Mensaje
                </button>
            </form>
        </div>
    </div>

    <!-- PREGUNTAS FRECUENTES (FAQ) -->
    <div style="margin-top: 60px;">
        <div class="text-center mb-4">
            <span class="section-badge"><i class="fa-solid fa-circle-question"></i> Respuestas Rápidas</span>
            <h2 style="font-family:'Montserrat',sans-serif;font-size:1.8rem;font-weight:900;text-transform:uppercase;color:var(--text-main);margin-top:6px;">
                Preguntas Frecuentes
            </h2>
            <p style="color:var(--text-muted);font-size:0.95rem;max-width:550px;margin:0 auto;">
                Todo lo que necesitás saber sobre tus pedidos, envíos, métodos de pago y beneficios.
            </p>
        </div>

        <div class="accordion" id="faqAccordion" style="max-width: 900px; margin: 0 auto;">
            <div class="accordion-item" style="border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--border); overflow: hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                        🛵 ¿Cuánto demora el delivery y cómo es la entrega?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="color: #475569; font-size: 0.92rem; line-height: 1.6;">
                        El tiempo estimado estándar en Formosa Capital es de <strong>25 a 35 minutos</strong>. Despachamos tu pedido en empaques térmicos sellados con precinto de seguridad para que recibas tu hamburguesa y papas calientes y crujientes.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--border); overflow: hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                        💳 ¿Qué medios de pago aceptan en la web?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="color: #475569; font-size: 0.92rem; line-height: 1.6;">
                        Aceptamos <strong>Efectivo al recibir</strong> (con cálculo de vuelto dinámico), <strong>Mercado Pago QR</strong>, <strong>Transferencia Bancaria directa</strong> con botón para copiar CBU/Alias y adjuntar comprobante, y <strong>Tarjetas de Débito y Crédito</strong> en hasta 3 cuotas.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--border); overflow: hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                        ⭐ ¿Cómo sumo y uso mis puntos Club Mostaza?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="color: #475569; font-size: 0.92rem; line-height: 1.6;">
                        Cada compra que realices con tu cuenta suma <strong>1 punto por cada $100 gastados</strong>. Podés activar tus puntos acumulados en el paso 1 del Carrito para obtener descuentos directos de hasta el 50% de tu compra.
                    </div>
                </div>
            </div>

            <div class="accordion-item" style="border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--border); overflow: hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                        🌱 ¿Tienen hamburguesas vegetarianas y opciones Sin TACC?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body" style="color: #475569; font-size: 0.92rem; line-height: 1.6;">
                        ¡Sí! Nuestra sección <strong>Para Vos</strong> cuenta con la línea Zero Carne con medallones 100% plant-based y combos certificados Sin TACC con pan apto para celíacos preparados bajo rigurosos protocolos de manipulación segura.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
</body>
</html>
