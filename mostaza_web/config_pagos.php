<?php
// ============================================================
// config_pagos.php
// Configuración Centralizada de Cuentas Bancarias, Pasarelas
// de Pago y Coordenadas de Sucursales de Mostaza.
// ============================================================

// 1. DATOS BANCARIOS PARA TRANSFERENCIAS DIRECTAS
// Datos bancarios oficiales configurados para pagos y transferencias:
if (!defined('BANCO_NOMBRE'))      define('BANCO_NOMBRE', 'Mercado Pago');
if (!defined('BANCO_TITULAR'))     define('BANCO_TITULAR', 'Alberto Emanuel Gomez');
if (!defined('BANCO_CUIT'))        define('BANCO_CUIT', '20-48001876-2');
if (!defined('BANCO_CBU'))         define('BANCO_CBU', '0000003100054955214368');
if (!defined('BANCO_CVU'))         define('BANCO_CVU', '0000003100054955214368');
if (!defined('BANCO_ALIAS'))       define('BANCO_ALIAS', 'gomez16.mp');
if (!defined('BANCO_TIPO_CUENTA')) define('BANCO_TIPO_CUENTA', 'Cuenta Mercado Pago');

// 2. CONFIGURACIÓN MERCADO PAGO Y ENLACES DIRECTOS
if (!defined('MP_PUBLIC_KEY'))     define('MP_PUBLIC_KEY', 'TEST-7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d');
if (!defined('MP_ACCESS_TOKEN'))   define('MP_ACCESS_TOKEN', 'TEST-1234567890123456-123456-abcdef1234567890abcdef1234567890-123456789');
if (!defined('MP_SANDBOX_MODE'))   define('MP_SANDBOX_MODE', true);
if (!defined('MP_LINK_PAGO'))      define('MP_LINK_PAGO', 'https://link.mercadopago.com.ar/gomez16mp');

// 3. COORDENADAS SUCURSAL MOSTAZA (PUNTO DE DESPACHO DELIVERY)
// Coordenadas oficiales de la sucursal de despacho de Mostaza en Formosa Capital:
if (!defined('SUCURSAL_LAT'))      define('SUCURSAL_LAT', -26.185200);
if (!defined('SUCURSAL_LNG'))      define('SUCURSAL_LNG', -58.175600);
if (!defined('SUCURSAL_DIRECCION')) define('SUCURSAL_DIRECCION', 'Mostaza Formosa Centro (Av. 25 de Mayo 150, Formosa Capital)');

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

        // Tag 00: Indicador de formato de carga
        $payload = emvCoTag('00', '01');

        // Tag 01: Método de iniciación de punto (12 = Dinámico con monto específico)
        $payload .= emvCoTag('01', ($monto > 0 ? '12' : '11'));

        // Tag 43: Merchant Account Info Interoperable BCRA / Mercado Pago
        $sub00 = emvCoTag('00', 'COM.MERCADOPAGO');
        $cuentaId = !empty($cvu) ? preg_replace('/\D/', '', $cvu) : strtolower(trim($alias));
        $sub01 = emvCoTag('01', $cuentaId);
        $payload .= emvCoTag('43', $sub00 . $sub01);

        // Tag 52: Merchant Category Code (MCC 5814 = Fast Food / Restaurantes)
        $payload .= emvCoTag('52', '5814');

        // Tag 53: Código de moneda (032 = ARS Pesos Argentinos)
        $payload .= emvCoTag('53', '032');

        // Tag 54: Monto de la transacción (opcional para estático, obligatorio para dinámico)
        if ($monto > 0) {
            $montoStr = number_format($monto, 2, '.', '');
            $payload .= emvCoTag('54', $montoStr);
        }

        // Tag 58: Código de País (AR)
        $payload .= emvCoTag('58', 'AR');

        // Tag 59: Nombre del Comercio / Titular (max 25 chars, sin caracteres especiales)
        $nombreLimpio = substr(preg_replace('/[^A-Z0-9 ]/i', '', strtoupper($titular)), 0, 25);
        if (empty($nombreLimpio)) $nombreLimpio = 'MOSTAZA OFICIAL';
        $payload .= emvCoTag('59', $nombreLimpio);

        // Tag 60: Ciudad del comercio (max 15 chars)
        $ciudadLimpia = substr(preg_replace('/[^A-Z0-9 ]/i', '', strtoupper($ciudad)), 0, 15);
        if (empty($ciudadLimpia)) $ciudadLimpia = 'FORMOSA';
        $payload .= emvCoTag('60', $ciudadLimpia);

        // Tag 62: Datos adicionales (Referencia interna de Mostaza)
        $subRef = emvCoTag('01', 'MOSTAZA');
        $payload .= emvCoTag('62', $subRef);

        // Tag 63: Checksum CRC-16 CCITT
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
