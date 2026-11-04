<?php
// ============================================================
// config_mail.php
// Credenciales para enviar correos con Gmail vía SMTP + PHPMailer.
//
// PASO A PASO para obtener una "contraseña de aplicación" de Gmail:
//   1. Entrá a https://myaccount.google.com/security
//   2. Activá la "Verificación en dos pasos" (si no la tenés activada,
//      Gmail no te va a dejar crear una contraseña de aplicación).
//   3. Buscá "Contraseñas de aplicaciones"
//      (https://myaccount.google.com/apppasswords)
//   4. Creá una nueva, elegí un nombre (ej: "Mostaza Web") y copiá
//      el código de 16 letras que te da Google.
//   5. Pegá ese código abajo en MAIL_PASS (SIN espacios).
//      NO uses tu contraseña normal de Gmail: no va a funcionar.
// ============================================================

define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', 'applegomez64@gmail.com');       // <-- tu cuenta de Gmail
define('MAIL_PASS', 'pmej ekpy iipv dszl');           // <-- contraseña de aplicación (16 caracteres)
define('MAIL_FROM_NAME', 'Mostaza');
