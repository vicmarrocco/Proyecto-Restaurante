<?php
// ============================================================
// enviar_correo.php
// Helper para enviar el código de acceso por Gmail usando PHPMailer,
// sin necesidad de Composer (se usan los archivos de /mail/src).
// ============================================================

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
require_once __DIR__ . '/config_mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía el código de acceso (token) al email indicado.
 *
 * @param string $destinatario Email del cliente
 * @param string $nombre       Nombre del cliente (para el saludo)
 * @param string $token        Código de 6 dígitos
 * @return array ['ok' => bool, 'error' => string|null]
 */
function enviarTokenPorCorreo($destinatario, $nombre, $token) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress($destinatario, $nombre);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = "Tu código de acceso Mostaza: $token";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:24px;'>
                <h2 style='color:#A30002;'>MOSTAZA</h2>
                <p>Hola " . htmlspecialchars($nombre) . ",</p>
                <p>Tu código de acceso es:</p>
                <p style='font-size:32px;font-weight:800;letter-spacing:6px;color:#A30002;'>$token</p>
                <p>Este código vence en 10 minutos. Si no fuiste vos, ignorá este correo.</p>
            </div>
        ";
        $mail->AltBody = "Tu código de acceso Mostaza es: $token (vence en 10 minutos)";

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Envía un mensaje del formulario de contacto a la casilla de Mostaza (MAIL_USER),
 * usando el correo del cliente como "reply-to" para poder contestarle directo.
 *
 * @param string $nombreCliente
 * @param string $emailCliente
 * @param string $mensaje
 * @return array ['ok' => bool, 'error' => string|null]
 */
function enviarMensajeContacto($nombreCliente, $emailCliente, $mensaje) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $mail->addAddress(MAIL_USER, MAIL_FROM_NAME); // llega a la casilla del local
        $mail->addReplyTo($emailCliente, $nombreCliente);

        $mail->isHTML(true);
        $mail->Subject = "Nuevo mensaje de contacto de " . $nombreCliente;
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:24px;'>
                <h2 style='color:#A30002;'>MOSTAZA · Contacto</h2>
                <p><strong>Nombre:</strong> " . htmlspecialchars($nombreCliente) . "</p>
                <p><strong>Correo:</strong> " . htmlspecialchars($emailCliente) . "</p>
                <p><strong>Mensaje:</strong></p>
                <p>" . nl2br(htmlspecialchars($mensaje)) . "</p>
            </div>
        ";
        $mail->AltBody = "Nombre: $nombreCliente\nCorreo: $emailCliente\nMensaje: $mensaje";

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}
