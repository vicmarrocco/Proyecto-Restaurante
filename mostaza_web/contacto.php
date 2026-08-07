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
<title>Mostaza - Contacto</title>
<link rel="stylesheet" href="sn.css">
<link rel="stylesheet" href="auth.css">
<link rel="stylesheet" href="tienda.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php include('includes/header.php'); ?>

<section class="auth-container">
    <div class="auth-box registro-box">

        <div class="auth-top">
            <i class="fa-solid fa-envelope-open-text"></i>
            <h2>Contactanos</h2>
            <p>¿Tenés una consulta, sugerencia o reclamo? Escribinos.</p>
        </div>

        <?php if ($error): ?><div class="flash flash-error"><?= $error ?></div><?php endif; ?>
        <?php if ($ok): ?><div class="flash flash-ok"><?= $ok ?></div><?php endif; ?>

        <form method="post" action="contacto.php">
            <div class="input-group">
                <i class="fa-solid fa-user"></i>
                <input type="text" name="nombre" placeholder="Tu nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? ($_SESSION['usuario_nombre'] ?? '')) ?>">
            </div>

            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="Tu correo electrónico" required value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['usuario_email'] ?? '')) ?>">
            </div>

            <div class="input-group" style="align-items:flex-start;">
                <i class="fa-solid fa-comment" style="margin-top:12px;"></i>
                <textarea name="mensaje" placeholder="Escribí tu mensaje..." rows="5" required
                    style="width:100%;border:none;outline:none;font-family:'Montserrat',sans-serif;font-size:1rem;padding:10px 0;resize:vertical;"><?= htmlspecialchars($_POST['mensaje'] ?? '') ?></textarea>
            </div>

            <button type="submit">Enviar mensaje</button>
        </form>
    </div>
</section>

<?php include('includes/footer.php'); ?>
</body>
</html>
