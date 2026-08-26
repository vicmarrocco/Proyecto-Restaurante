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

// 2. CONFIGURACIÓN MERCADO PAGO
// Credenciales de prueba / producción de Mercado Pago:
if (!defined('MP_PUBLIC_KEY'))     define('MP_PUBLIC_KEY', 'TEST-7a8b9c0d-1e2f-3a4b-5c6d-7e8f9a0b1c2d');
if (!defined('MP_ACCESS_TOKEN'))   define('MP_ACCESS_TOKEN', 'TEST-1234567890123456-123456-abcdef1234567890abcdef1234567890-123456789');
if (!defined('MP_SANDBOX_MODE'))   define('MP_SANDBOX_MODE', true);

// 3. COORDENADAS SUCURSAL MOSTAZA (PUNTO DE DESPACHO DELIVERY)
// Coordenadas oficiales de la sucursal de despacho de Mostaza en Formosa Capital:
if (!defined('SUCURSAL_LAT'))      define('SUCURSAL_LAT', -26.185200);
if (!defined('SUCURSAL_LNG'))      define('SUCURSAL_LNG', -58.175600);
if (!defined('SUCURSAL_DIRECCION')) define('SUCURSAL_DIRECCION', 'Mostaza Formosa Centro (Av. 25 de Mayo 150, Formosa Capital)');
