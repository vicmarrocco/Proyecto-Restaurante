<?php
// ============================================================
// enviar_correo.php
// Helper para enviar el código de acceso, mensajes de contacto
// y confirmación de pedidos por Gmail usando PHPMailer.
// ============================================================

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';
require_once __DIR__ . '/config_mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía el código de acceso (token) al email indicado.
 */
function enviarTokenPorCorreo($destinatario, $nombre, $token) {
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
        $mail->addAddress($destinatario, $nombre);

        $mail->isHTML(true);
        $mail->Subject = "Tu código de acceso Mostaza: $token";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;'>
                <div style='text-align:center;margin-bottom:20px;'>
                    <h2 style='color:#A30002;margin:0;font-size:28px;'>MOSTAZA</h2>
                    <span style='color:#666;font-size:12px;'>Sabor sin límites</span>
                </div>
                <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                <p>Tu código de acceso de 6 dígitos para ingresar a tu cuenta es:</p>
                <div style='text-align:center;background:#FFF5F5;border:2px dashed #A30002;border-radius:10px;padding:16px;margin:20px 0;'>
                    <span style='font-size:36px;font-weight:800;letter-spacing:8px;color:#A30002;font-family:monospace;'>$token</span>
                </div>
                <p style='color:#777;font-size:13px;'>Este código vence en 10 minutos. Si vos no solicitaste este acceso, podés ignorar este mensaje.</p>
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
 * Envía la confirmación de un pedido al cliente con el desglose de productos.
 */
function enviarConfirmacionPedidoPorCorreo($destinatario, $nombre, $pedido, $detalles) {
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
        $mail->addAddress($destinatario, $nombre);

        $filasHtml = '';
        foreach ($detalles as $d) {
            $filasHtml .= "
            <tr style='border-bottom:1px solid #eee;'>
                <td style='padding:10px 0;'>
                    <strong>" . htmlspecialchars($d['descripcion']) . "</strong>" .
                    (!empty($d['modificadores']) ? "<br><span style='font-size:12px;color:#666;'>" . htmlspecialchars($d['modificadores']) . "</span>" : "") . "
                </td>
                <td style='padding:10px 0;text-align:center;'>x" . intval($d['cantidad']) . "</td>
                <td style='padding:10px 0;text-align:right;'>$" . number_format($d['subtotal'], 0, ',', '.') . "</td>
            </tr>";
        }

        $mail->isHTML(true);
        $mail->Subject = "¡Pedido Confirmado #" . $pedido['idpedido'] . " en Mostaza!";
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:550px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:14px;background:#fff;'>
                <div style='text-align:center;padding-bottom:16px;border-bottom:2px solid #A30002;'>
                    <h1 style='color:#A30002;margin:0;font-size:26px;'>MOSTAZA</h1>
                    <p style='color:#555;margin:4px 0 0;'>¡Gracias por tu compra, " . htmlspecialchars($nombre) . "!</p>
                </div>
                
                <div style='background:#f8f9fa;padding:16px;border-radius:10px;margin:18px 0;'>
                    <p style='margin:4px 0;'><strong>N.º de Pedido:</strong> #" . $pedido['idpedido'] . "</p>
                    <p style='margin:4px 0;'><strong>Modalidad:</strong> " . strtoupper($pedido['tipo_entrega']) . "</p>
                    <p style='margin:4px 0;'><strong>Forma de Pago:</strong> " . strtoupper($pedido['metodo_pago']) . " (Aprobado)</p>
                    " . (!empty($pedido['direccion_envio']) ? "<p style='margin:4px 0;'><strong>Dirección:</strong> " . htmlspecialchars($pedido['direccion_envio']) . "</p>" : "") . "
                </div>

                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <thead>
                        <tr style='border-bottom:2px solid #ddd;color:#555;font-size:13px;text-align:left;'>
                            <th style='padding:8px 0;'>Producto</th>
                            <th style='padding:8px 0;text-align:center;'>Cant.</th>
                            <th style='padding:8px 0;text-align:right;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        $filasHtml
                    </tbody>
                </table>

                <div style='border-top:2px solid #eee;padding-top:12px;'>
                    <div style='display:flex;justify-content:space-between;margin:4px 0;color:#555;'>
                        <span>Subtotal:</span>
                        <strong>$" . number_format($pedido['subtotal'], 0, ',', '.') . "</strong>
                    </div>
                    " . ($pedido['descuento'] > 0 ? "
                    <div style='display:flex;justify-content:space-between;margin:4px 0;color:#16A34A;'>
                        <span>Descuento:</span>
                        <strong>-$" . number_format($pedido['descuento'], 0, ',', '.') . "</strong>
                    </div>" : "") . "
                    " . ($pedido['costo_envio'] > 0 ? "
                    <div style='display:flex;justify-content:space-between;margin:4px 0;color:#555;'>
                        <span>Envío:</span>
                        <strong>$" . number_format($pedido['costo_envio'], 0, ',', '.') . "</strong>
                    </div>" : "") . "
                    " . ($pedido['propina'] > 0 ? "
                    <div style='display:flex;justify-content:space-between;margin:4px 0;color:#555;'>
                        <span>Propina:</span>
                        <strong>$" . number_format($pedido['propina'], 0, ',', '.') . "</strong>
                    </div>" : "") . "
                    <div style='display:flex;justify-content:space-between;margin:12px 0 0;font-size:18px;color:#A30002;font-weight:bold;'>
                        <span>Total Pagado:</span>
                        <span>$" . number_format($pedido['total'], 0, ',', '.') . "</span>
                    </div>
                </div>

                <div style='text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #eee;color:#888;font-size:12px;'>
                    <p>Tu pedido ya ingresó a la cocina de Mostaza y se encuentra en preparación.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "¡Pedido #" . $pedido['idpedido'] . " confirmado en Mostaza! Total: $" . number_format($pedido['total'], 0, ',', '.');

        $mail->send();
        return ['ok' => true, 'error' => null];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Envía un mensaje del formulario de contacto a la casilla de Mostaza.
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
        $mail->addAddress(MAIL_USER, MAIL_FROM_NAME);
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
