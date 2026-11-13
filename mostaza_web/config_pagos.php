<?php
// ============================================================
// config_pagos.php
// Configuración Centralizada de Cuentas Bancarias, Pasarelas
// de Pago (Mercado Pago API Oficial) y Coordenadas de Sucursales.
// ============================================================

// Cargar variables de entorno desde archivo .env si existe
$envPaths = [
    __DIR__ . '/../.env',
    __DIR__ . '/.env'
];
foreach ($envPaths as $envFile) {
    if (file_exists($envFile) && is_readable($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if ($k !== '' && getenv($k) === false) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }
}

// 1. DATOS BANCARIOS PARA TRANSFERENCIAS / CUENTA RECEPTORA MERCADO PAGO
if (!defined('BANCO_NOMBRE'))      define('BANCO_NOMBRE', getenv('BANCO_NOMBRE') ?: 'Mercado Pago');
if (!defined('BANCO_TITULAR'))     define('BANCO_TITULAR', getenv('BANCO_TITULAR') ?: 'Alberto Emanuel Gomez');
if (!defined('BANCO_CUIT'))        define('BANCO_CUIT', getenv('BANCO_CUIT') ?: '20-48001876-2');
if (!defined('BANCO_CBU'))         define('BANCO_CBU', getenv('BANCO_CBU') ?: '0000003100054955214368');
if (!defined('BANCO_CVU'))         define('BANCO_CVU', getenv('BANCO_CVU') ?: '0000003100054955214368');
if (!defined('BANCO_ALIAS'))       define('BANCO_ALIAS', getenv('BANCO_ALIAS') ?: 'gomez16.mp');
if (!defined('BANCO_TIPO_CUENTA')) define('BANCO_TIPO_CUENTA', 'Cuenta Mercado Pago');

// 2. CONFIGURACIÓN MERCADO PAGO DEVELOPERS Y ENLACES DIRECTOS
// Podés configurar tus claves reales en el archivo .env en la raíz del proyecto o desde el panel Admin:
// MP_PUBLIC_KEY=APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
// MP_ACCESS_TOKEN=APP_USR-xxxxxxxxxxxxxxxx-xxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxx
if (!defined('MP_PUBLIC_KEY'))     define('MP_PUBLIC_KEY', getenv('MP_PUBLIC_KEY') ?: '');
if (!defined('MP_ACCESS_TOKEN'))   define('MP_ACCESS_TOKEN', getenv('MP_ACCESS_TOKEN') ?: '');
if (!defined('MP_SANDBOX_MODE'))   define('MP_SANDBOX_MODE', getenv('MP_SANDBOX_MODE') !== false ? filter_var(getenv('MP_SANDBOX_MODE'), FILTER_VALIDATE_BOOLEAN) : (strpos(MP_ACCESS_TOKEN, 'TEST-') === 0));
if (!defined('MP_LINK_PAGO'))      define('MP_LINK_PAGO', getenv('MP_LINK_PAGO') ?: 'https://link.mercadopago.com.ar/gomez16mp');

// 3. COORDENADAS SUCURSAL MOSTAZA (PUNTO DE DESPACHO DELIVERY)
if (!defined('SUCURSAL_LAT'))      define('SUCURSAL_LAT', -26.185200);
if (!defined('SUCURSAL_LNG'))      define('SUCURSAL_LNG', -58.175600);
if (!defined('SUCURSAL_DIRECCION')) define('SUCURSAL_DIRECCION', 'Mostaza Formosa Centro (Av. 25 de Mayo 150, Formosa Capital)');

// ============================================================
// VERIFICACIÓN Y CLIENTE REST API OFICIAL MERCADO PAGO
// ============================================================

if (!function_exists('mpTieneCredencialesReales')) {
    function mpTieneCredencialesReales() {
        $token = trim(MP_ACCESS_TOKEN);
        if (empty($token)) return false;
        if ($token === 'TEST-1234567890123456-123456-abcdef1234567890abcdef1234567890-123456789') {
            return false;
        }
        return (strpos($token, 'APP_USR-') === 0 || strpos($token, 'TEST-') === 0);
    }
}

if (!function_exists('crearPreferenciaMercadoPago')) {
    /**
     * Crea una preferencia de pago real en Mercado Pago (Checkout Pro)
     */
    function crearPreferenciaMercadoPago($items, $externalReference, $payer = [], $backUrls = [], $costoEnvio = 0, $descuentoTotal = 0, $notificationUrl = '') {
        if (!mpTieneCredencialesReales()) {
            return [
                'ok' => false,
                'modo_real' => false,
                'error' => 'Credenciales en modo demostración local. Configurá MP_ACCESS_TOKEN con tu clave de Mercado Pago Developers.'
            ];
        }

        $mpItems = [];
        $sumaItems = 0;
        foreach ($items as $it) {
            $precioUnit = round(floatval($it['precio']), 2);
            $cant = max(1, intval($it['cantidad']));
            $sumaItems += ($precioUnit * $cant);
            $mpItems[] = [
                'id'          => strval($it['idproducto'] ?? 'ITEM'),
                'title'       => substr(trim($it['nombre'] ?? 'Producto Mostaza'), 0, 120),
                'description' => substr(trim($it['detalle'] ?? 'Hamburguesa Mostaza'), 0, 200),
                'quantity'    => $cant,
                'currency_id' => 'ARS',
                'unit_price'  => $precioUnit
            ];
        }

        if ($costoEnvio > 0) {
            $mpItems[] = [
                'id'          => 'ENVIO-PROPINA',
                'title'       => 'Servicio de Entrega / Adicionales',
                'quantity'    => 1,
                'currency_id' => 'ARS',
                'unit_price'  => round(floatval($costoEnvio), 2)
            ];
        }

        if ($descuentoTotal > 0) {
            $totalNeto = max(1, round(($sumaItems + $costoEnvio) - $descuentoTotal, 2));
            $nombresResumen = [];
            foreach ($items as $it) {
                $nombresResumen[] = ($it['cantidad'] ?? 1) . 'x ' . ($it['nombre'] ?? 'Producto');
            }
            $mpItems = [[
                'id'          => 'PEDIDO-MOSTAZA',
                'title'       => 'Pedido Mostaza (' . substr(implode(', ', $nombresResumen), 0, 90) . ')',
                'description' => 'Incluye descuentos / puntos canjeados aplicados',
                'quantity'    => 1,
                'currency_id' => 'ARS',
                'unit_price'  => $totalNeto
            ]];
        }

        $payload = [
            'items'                => $mpItems,
            'external_reference'   => $externalReference,
            'statement_descriptor' => 'MOSTAZA',
            'binary_mode'          => true
        ];

        $testPayerEmail = getenv('MP_TEST_PAYER_EMAIL');
        $emailPayer = ($testPayerEmail !== false && $testPayerEmail !== '') ? $testPayerEmail : ($payer['email'] ?? '');
        if (!empty($emailPayer) && $emailPayer !== 'none') {
            $payload['payer'] = ['email' => $emailPayer];
        }

        if (!empty($backUrls)) {
            $successUrl = $backUrls['success'] ?? '';
            $esDominioPublico = (strpos($successUrl, 'https://') === 0) &&
                                !preg_match('/^https?:\/\/(localhost|127\.|192\.168\.|10\.|\d+\.\d+\.\d+\.\d+)/i', $successUrl);
            if ($esDominioPublico) {
                $payload['back_urls'] = $backUrls;
                $payload['auto_return'] = 'approved';
            }
        }

        if (!empty($notificationUrl) && strpos($notificationUrl, 'https://') === 0
            && !preg_match('/^https?:\/\/(localhost|127\.|192\.168\.|10\.)/i', $notificationUrl)) {
            $payload['notification_url'] = $notificationUrl;
        }

        $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . trim(MP_ACCESS_TOKEN),
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'ok' => false,
                'modo_real' => true,
                'error' => 'Error de red al conectar con Mercado Pago: ' . $curlErr
            ];
        }

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && !empty($data['id'])) {
            $initPoint = (MP_SANDBOX_MODE && !empty($data['sandbox_init_point']))
                ? $data['sandbox_init_point']
                : ($data['init_point'] ?? '');
            return [
                'ok'                 => true,
                'modo_real'          => true,
                'preference_id'      => $data['id'],
                'init_point'         => $initPoint,
                'sandbox_init_point' => $data['sandbox_init_point'] ?? '',
                'prod_init_point'    => $data['init_point'] ?? '',
                'external_reference' => $externalReference
            ];
        }

        $msgError = $data['message'] ?? ($data['error'] ?? 'Error al crear preferencia en Mercado Pago (HTTP ' . $httpCode . ')');
        return [
            'ok'        => false,
            'modo_real' => true,
            'http_code' => $httpCode,
            'error'     => $msgError,
            'detalle'   => $data
        ];
    }
}

if (!function_exists('consultarPagoMercadoPagoApi')) {
    /**
     * Consulta un pago específico por ID, por external_reference o por monto reciente en la API de Mercado Pago
     */
    function consultarPagoMercadoPagoApi($paymentId = '', $externalReference = '', $montoEsperado = 0) {
        if (!mpTieneCredencialesReales()) {
            return [
                'ok' => false,
                'modo_real' => false,
                'pagado' => false,
                'error' => 'Modo demostración activo (sin MP_ACCESS_TOKEN real).'
            ];
        }

        $headers = [
            'Authorization: Bearer ' . trim(MP_ACCESS_TOKEN),
            'Accept: application/json'
        ];

        // 1. Si hay un ID numérico de operación de Mercado Pago
        $cleanPaymentId = preg_replace('/\D/', '', $paymentId);
        if (!empty($cleanPaymentId) && strlen($cleanPaymentId) >= 6) {
            $ch = curl_init("https://api.mercadopago.com/v1/payments/{$cleanPaymentId}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $res) {
                $pData = json_decode($res, true);
                if (!empty($pData['id'])) {
                    $status = $pData['status'] ?? 'pending';
                    return [
                        'ok'             => true,
                        'modo_real'      => true,
                        'pagado'         => ($status === 'approved'),
                        'status'         => $status,
                        'payment_id'     => strval($pData['id']),
                        'external_reference' => $pData['external_reference'] ?? '',
                        'monto'          => floatval($pData['transaction_amount'] ?? 0),
                        'metodo_detalle' => ($pData['payment_type_id'] ?? 'account_money') . ' (' . ($pData['payment_method_id'] ?? 'mp') . ')'
                    ];
                }
            }
        }

        // 2. Búsqueda por external_reference (Checkout Pro / QR Orden MP)
        if (!empty($externalReference)) {
            $url = 'https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . urlencode($externalReference);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $res) {
                $sData = json_decode($res, true);
                $results = $sData['results'] ?? [];
                foreach ($results as $pItem) {
                    if (($pItem['status'] ?? '') === 'approved') {
                        return [
                            'ok'             => true,
                            'modo_real'      => true,
                            'pagado'         => true,
                            'status'         => 'approved',
                            'payment_id'     => strval($pItem['id']),
                            'external_reference' => $pItem['external_reference'] ?? '',
                            'monto'          => floatval($pItem['transaction_amount'] ?? 0),
                            'metodo_detalle' => ($pItem['payment_type_id'] ?? 'account_money') . ' (' . ($pItem['payment_method_id'] ?? 'mp') . ')'
                        ];
                    }
                }
            }
        }

        // 3. Búsqueda de cobros recientes (últimos 10 minutos) por monto exacto (QR EMVCo / Link)
        if ($montoEsperado > 0) {
            $urlRec = 'https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&limit=10&status=approved';
            $ch = curl_init($urlRec);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && $res) {
                $sData = json_decode($res, true);
                $results = $sData['results'] ?? [];
                $ahora = time();
                foreach ($results as $pItem) {
                    $fechaPago = !empty($pItem['date_created']) ? strtotime($pItem['date_created']) : 0;
                    $montoPago = floatval($pItem['transaction_amount'] ?? 0);
                    if ($fechaPago > 0 && ($ahora - $fechaPago) <= 600 && abs($montoPago - floatval($montoEsperado)) < 1.0) {
                        return [
                            'ok'             => true,
                            'modo_real'      => true,
                            'pagado'         => true,
                            'status'         => 'approved',
                            'payment_id'     => strval($pItem['id']),
                            'external_reference' => $pItem['external_reference'] ?? '',
                            'monto'          => $montoPago,
                            'metodo_detalle' => ($pItem['payment_type_id'] ?? 'account_money') . ' (' . ($pItem['payment_method_id'] ?? 'mp') . ')'
                        ];
                    }
                }
            }
        }

        return [
            'ok'        => true,
            'modo_real' => true,
            'pagado'    => false,
            'status'    => 'pending'
        ];
    }
}

if (!function_exists('mpVerificarPagoParaPedido')) {
    /**
     * Verificación ESTRICTA contra la API de Mercado Pago antes de dar un pedido por pagado.
     * Exige: pago 'approved', external_reference == la de esta sesión y monto >= esperado.
     * Nunca confiar en datos que vengan del navegador.
     */
    function mpVerificarPagoParaPedido($paymentId, $externalRefEsperada, $montoEsperado) {
        if (!mpTieneCredencialesReales()) {
            return ['ok' => false, 'error' => 'Mercado Pago no está configurado en el servidor.'];
        }
        if (empty($externalRefEsperada)) {
            return ['ok' => false, 'error' => 'No hay una orden de pago iniciada.'];
        }
        $headers = ['Authorization: Bearer ' . trim(MP_ACCESS_TOKEN), 'Accept: application/json'];
        $get = function ($url) use ($headers) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
                                    CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$code, $res ? json_decode($res, true) : null];
        };

        $pago = null;
        $id = preg_replace('/\D/', '', (string)$paymentId);
        if ($id !== '' && strlen($id) >= 6) {
            [$code, $d] = $get("https://api.mercadopago.com/v1/payments/{$id}");
            if ($code === 200 && !empty($d['id'])) $pago = $d;
        }
        if (!$pago) {
            [$code, $d] = $get('https://api.mercadopago.com/v1/payments/search?sort=date_created&criteria=desc&external_reference=' . urlencode($externalRefEsperada));
            foreach (($d['results'] ?? []) as $r) {
                if (($r['status'] ?? '') === 'approved') { $pago = $r; break; }
            }
        }
        if (!$pago)                                   return ['ok' => false, 'error' => 'No encontramos un pago de Mercado Pago para esta orden.'];
        if (($pago['status'] ?? '') !== 'approved')   return ['ok' => false, 'error' => 'El pago todavía no figura como aprobado (' . ($pago['status'] ?? 'desconocido') . ').'];
        if (($pago['external_reference'] ?? '') !== $externalRefEsperada) return ['ok' => false, 'error' => 'El pago no corresponde a esta orden.'];
        if (floatval($pago['transaction_amount'] ?? 0) + 0.5 < floatval($montoEsperado)) return ['ok' => false, 'error' => 'El monto abonado es menor al total del pedido.'];

        return [
            'ok'             => true,
            'payment_id'     => strval($pago['id']),
            'monto'          => floatval($pago['transaction_amount']),
            'metodo_detalle' => ($pago['payment_type_id'] ?? 'mp') . ' (' . ($pago['payment_method_id'] ?? 'mp') . ')',
        ];
    }
}

// ============================================================
// GENERADOR OFICIAL EMVCO / TRANSFERENCIAS 3.0 BCRA
// Compatible al 100% con la app de Mercado Pago, MODO, Cuenta DNI, BNA+, Galicia, Santander.
// ============================================================

if (!function_exists('emvCoTag')) {
    function emvCoTag($id, $value) {
        $len = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $id . $len . $value;
    }
}

if (!function_exists('calcularCrc16Ccitt')) {
    function calcularCrc16Ccitt($str) {
        $crc = 0xFFFF;
        $len = strlen($str);
        for ($c = 0; $c < $len; $c++) {
            $crc ^= (ord($str[$c]) << 8);
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}

if (!function_exists('generarPayloadEmvcoMercadoPago')) {
    function generarPayloadEmvcoMercadoPago($alias = null, $cvu = null, $monto = 0, $titular = null, $ciudad = 'FORMOSA') {
        $alias = $alias ?: BANCO_ALIAS;
        $cvu = $cvu ?: BANCO_CVU;
        $titular = $titular ?: BANCO_TITULAR;

        $payload = emvCoTag('00', '01');
        $payload .= emvCoTag('01', ($monto > 0 ? '12' : '11'));

        $sub00 = emvCoTag('00', 'COM.MERCADOPAGO');
        $cuentaId = !empty($cvu) ? preg_replace('/\D/', '', $cvu) : strtolower(trim($alias));
        $sub01 = emvCoTag('01', $cuentaId);
        $payload .= emvCoTag('43', $sub00 . $sub01);

        $payload .= emvCoTag('52', '5814');
        $payload .= emvCoTag('53', '032');

        if ($monto > 0) {
            $montoStr = number_format($monto, 2, '.', '');
            $payload .= emvCoTag('54', $montoStr);
        }

        $payload .= emvCoTag('58', 'AR');

        $nombreLimpio = substr(preg_replace('/[^A-Z0-9 ]/i', '', strtoupper($titular)), 0, 25);
        if (empty($nombreLimpio)) $nombreLimpio = 'MOSTAZA OFICIAL';
        $payload .= emvCoTag('59', $nombreLimpio);

        $ciudadLimpia = substr(preg_replace('/[^A-Z0-9 ]/i', '', strtoupper($ciudad)), 0, 15);
        if (empty($ciudadLimpia)) $ciudadLimpia = 'FORMOSA';
        $payload .= emvCoTag('60', $ciudadLimpia);

        $subRef = emvCoTag('01', 'MOSTAZA');
        $payload .= emvCoTag('62', $subRef);

        $payloadConTag63 = $payload . '6304';
        $crc = calcularCrc16Ccitt($payloadConTag63);

        return $payloadConTag63 . $crc;
    }
}

if (!function_exists('generarQrUrlMercadoPago')) {
    function generarQrUrlMercadoPago($monto = 0, $size = 200) {
        $emvPayload = generarPayloadEmvcoMercadoPago(BANCO_ALIAS, BANCO_CVU, $monto, BANCO_TITULAR, 'FORMOSA');
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&margin=10&data=" . urlencode($emvPayload);
    }
}

if (!function_exists('reembolsarPagoMercadoPago')) {
    /**
     * Reembolsa un pago en Mercado Pago mediante la API oficial de Refunds
     * POST https://api.mercadopago.com/v1/payments/{payment_id}/refunds
     */
    function reembolsarPagoMercadoPago($paymentId, $monto = null, $motivo = 'Cancelación de pedido') {
        $cleanId = preg_replace('/\D/', '', $paymentId);

        if (!mpTieneCredencialesReales() || empty($cleanId) || strlen($cleanId) < 6) {
            // Modo simulación / pruebas locales sin ID numérico real de pasarela
            return [
                'ok'         => true,
                'modo_real'  => false,
                'refund_id'  => 'SIM-REF-' . mt_rand(100000, 999999),
                'status'     => 'approved',
                'monto'      => $monto ?: 0,
                'mensaje'    => 'Reembolso simulado exitosamente en modo demostración/pruebas.'
            ];
        }

        $headers = [
            'Authorization: Bearer ' . trim(MP_ACCESS_TOKEN),
            'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $payload = [];
        if ($monto !== null && floatval($monto) > 0) {
            $payload['amount'] = round(floatval($monto), 2);
        }

        $url = "https://api.mercadopago.com/v1/payments/{$cleanId}/refunds";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return [
                'ok'        => false,
                'modo_real' => true,
                'error'     => "Error de conexión con Mercado Pago: $err"
            ];
        }

        $data = json_decode($res, true) ?: [];

        if (in_array($httpCode, [200, 201]) && !empty($data['id'])) {
            return [
                'ok'         => true,
                'modo_real'  => true,
                'refund_id'  => strval($data['id']),
                'status'     => $data['status'] ?? 'approved',
                'monto'      => floatval($data['amount'] ?? ($monto ?: 0)),
                'detalle'    => $data
            ];
        }

        // Si Mercado Pago responde con error específico
        $msgError = $data['message'] ?? ($data['error'] ?? 'No se pudo procesar el reembolso en Mercado Pago');
        if (!empty($data['cause'][0]['description'])) {
            $msgError .= ' (' . $data['cause'][0]['description'] . ')';
        }

        return [
            'ok'        => false,
            'modo_real' => true,
            'http_code' => $httpCode,
            'error'     => $msgError,
            'detalle'   => $data
        ];
    }
}
