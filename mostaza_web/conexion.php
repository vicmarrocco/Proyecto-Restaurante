<?php
// ============================================================
// conexion.php
// Conexión y auto-verificación de la Base de Datos Definitiva.
// ============================================================
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "rest";

$conn = @new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

require_once(__DIR__ . '/config_pagos.php');

// Auto-migración y compatibilidad silenciosa
function verificarEstructuraWeb($conn) {
    // 1. Tabla usuario_web
    $conn->query("CREATE TABLE IF NOT EXISTS `usuario_web` (
      `idusuario` INT(11) NOT NULL AUTO_INCREMENT,
      `nombre` VARCHAR(100) NOT NULL,
      `apellido` VARCHAR(100) NOT NULL,
      `telefono` VARCHAR(30) DEFAULT NULL,
      `direccion` VARCHAR(150) DEFAULT NULL,
      `email` VARCHAR(150) NOT NULL,
      `token` VARCHAR(10) DEFAULT NULL,
      `token_expira` DATETIME DEFAULT NULL,
      `token_intentos` INT(11) NOT NULL DEFAULT 0,
      `verificado` TINYINT(1) NOT NULL DEFAULT 0,
      `puntos` INT(11) NOT NULL DEFAULT 0,
      `direccion_favorita` VARCHAR(200) DEFAULT NULL,
      `telefono_favorito` VARCHAR(50) DEFAULT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`idusuario`),
      UNIQUE KEY `email_unico` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 1.1 Tabla rol oficial
    $conn->query("CREATE TABLE IF NOT EXISTS `rol` (
      `idrol` INT(11) NOT NULL AUTO_INCREMENT,
      `rol` VARCHAR(50) NOT NULL,
      PRIMARY KEY (`idrol`),
      UNIQUE KEY `rol_unico` (`rol`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    $conn->query("INSERT IGNORE INTO `rol` (`idrol`, `rol`) VALUES 
      (1, 'Cliente'),
      (2, 'Cajero'),
      (3, 'Cocinero'),
      (4, 'Mesero'),
      (5, 'Administrador'),
      (6, 'Repartidor');");

    // 2. Tabla empleado unificada (Todos los roles, incluido delivery, con autenticación por código Gmail)
    $conn->query("CREATE TABLE IF NOT EXISTS `empleado` (
      `idempleado` INT(11) NOT NULL AUTO_INCREMENT,
      `nombre` VARCHAR(100) NOT NULL,
      `apellido` VARCHAR(100) NOT NULL,
      `dni` VARCHAR(20) DEFAULT NULL,
      `email` VARCHAR(150) NOT NULL,
      `usuario` VARCHAR(50) DEFAULT NULL,
      `password` VARCHAR(255) DEFAULT NULL,
      `token` VARCHAR(10) DEFAULT NULL,
      `token_expira` DATETIME DEFAULT NULL,
      `token_intentos` INT(11) NOT NULL DEFAULT 0,
      `fk_id_telefono` INT(11) DEFAULT NULL,
      `fk_id_email` INT(11) DEFAULT NULL,
      `fk_id_rol` INT(11) NOT NULL DEFAULT 2,
      `fk_id_genero` INT(11) DEFAULT NULL,
      `fk_id_turno` INT(11) DEFAULT 1,
      `telefono` VARCHAR(50) DEFAULT NULL,
      `vehiculo` VARCHAR(100) DEFAULT NULL,
      `patente` VARCHAR(20) DEFAULT NULL,
      `avatar` VARCHAR(50) DEFAULT '👤',
      `sucursal_base` VARCHAR(100) DEFAULT 'Mostaza Formosa Centro',
      `estado_servicio` ENUM('Disponible', 'En Reparto', 'En Descanso', 'Desconectado') DEFAULT 'Disponible',
      `calificacion` DECIMAL(3,2) DEFAULT 4.95,
      `viajes_totales` INT(11) DEFAULT 0,
      `activo` TINYINT(1) NOT NULL DEFAULT 1,
      `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`idempleado`),
      UNIQUE KEY `email_empleado_unico` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // Verificar si faltan columnas en empleado
    $colsEmp = $conn->query("SHOW COLUMNS FROM `empleado`");
    $empCols = [];
    if ($colsEmp) {
        while ($r = $colsEmp->fetch_assoc()) {
            $empCols[] = $r['Field'];
        }
    }
    $nuevasColsEmp = [
        'email' => "VARCHAR(150) NOT NULL DEFAULT 'cajero@mostaza.com.ar'",
        'usuario' => "VARCHAR(50) DEFAULT NULL",
        'password' => "VARCHAR(255) DEFAULT NULL",
        'activo' => "TINYINT(1) NOT NULL DEFAULT 1",
        'token' => "VARCHAR(10) DEFAULT NULL",
        'token_expira' => "DATETIME DEFAULT NULL",
        'token_intentos' => "INT(11) NOT NULL DEFAULT 0",
        'telefono' => "VARCHAR(50) DEFAULT NULL",
        'vehiculo' => "VARCHAR(100) DEFAULT NULL",
        'patente' => "VARCHAR(20) DEFAULT NULL",
        'avatar' => "VARCHAR(50) DEFAULT '👤'",
        'sucursal_base' => "VARCHAR(100) DEFAULT 'Mostaza Formosa Centro'",
        'estado_servicio' => "ENUM('Disponible', 'En Reparto', 'En Descanso', 'Desconectado') DEFAULT 'Disponible'",
        'calificacion' => "DECIMAL(3,2) DEFAULT 4.95",
        'viajes_totales' => "INT(11) DEFAULT 0",
        'fk_id_seccion' => "INT(11) NOT NULL DEFAULT 1"
    ];
    foreach ($nuevasColsEmp as $cName => $cDef) {
        if (!in_array($cName, $empCols)) {
            $conn->query("ALTER TABLE `empleado` ADD `$cName` $cDef");
        }
    }

    // Sembrar la nómina oficial diferenciada por rol con sus respectivos correos
    $nominaOficial = [
        // Cajeros (Rol 2)
        ['nombre' => 'Alberto Emanuel', 'apellido' => 'Gómez', 'dni' => '38111222', 'email' => 'applegomez64@gmail.com', 'usuario' => 'cajero_central', 'rol' => 2, 'avatar' => '👨‍💼', 'vehiculo' => null, 'patente' => null, 'telefono' => '3704-112233'],
        ['nombre' => 'Romina', 'apellido' => 'Castro', 'dni' => '29222111', 'email' => 'cajero1@mostaza.com.ar', 'usuario' => 'cajero1', 'rol' => 2, 'avatar' => '👩‍💼', 'vehiculo' => null, 'patente' => null, 'telefono' => '3704-223344'],
        // Cocineros (Rol 3)
        ['nombre' => 'Martín', 'apellido' => 'Paredes', 'dni' => '33444555', 'email' => 'cocina1@mostaza.com.ar', 'usuario' => 'cocina1', 'rol' => 3, 'avatar' => '👨‍🍳', 'vehiculo' => null, 'patente' => null, 'telefono' => '3704-334455'],
        ['nombre' => 'Lucas', 'apellido' => 'Silveira', 'dni' => '34555666', 'email' => 'cocina2@mostaza.com.ar', 'usuario' => 'cocina2', 'rol' => 3, 'avatar' => '👨‍🍳', 'vehiculo' => null, 'patente' => null, 'telefono' => '3704-445566'],
        // Meseros (Rol 4)
        ['nombre' => 'Franco', 'apellido' => 'Morales', 'dni' => '35666777', 'email' => 'mesero1@mostaza.com.ar', 'usuario' => 'mesero1', 'rol' => 4, 'avatar' => '🤵', 'vehiculo' => null, 'patente' => null, 'telefono' => '3704-556677'],
        ['nombre' => 'Ezequiel', 'apellido' => 'Molina', 'dni' => '36777888', 'email' => 'mesero2@mostaza.com.ar', 'usuario' => 'mesero2', 'rol' => 4, 'avatar' => '🤵', 'vehiculo' => null, 'patente' => null, 'telefono' => '3704-667788'],
        // Repartidores / Delivery (Rol 6)
        ['nombre' => 'Ramiro', 'apellido' => 'González', 'dni' => '36852147', 'email' => 'delivery1@mostaza.com.ar', 'usuario' => 'delivery1', 'rol' => 6, 'avatar' => '🛵', 'vehiculo' => 'Moto Honda Wave 110cc', 'patente' => 'A123BCD', 'telefono' => '3704-551122'],
        ['nombre' => 'Lucas', 'apellido' => 'Benítez', 'dni' => '38411902', 'email' => 'delivery2@mostaza.com.ar', 'usuario' => 'delivery2', 'rol' => 6, 'avatar' => '🏍️', 'vehiculo' => 'Moto Yamaha YBR 125cc', 'patente' => 'A045EFG', 'telefono' => '3704-662233'],
        ['nombre' => 'Mateo', 'apellido' => 'Silveira', 'dni' => '40231876', 'email' => 'delivery3@mostaza.com.ar', 'usuario' => 'delivery3', 'rol' => 6, 'avatar' => '⚡', 'vehiculo' => 'Moto Gilera Smash 110', 'patente' => 'A654NOP', 'telefono' => '3704-995566']
    ];

    foreach ($nominaOficial as $empData) {
        $chk = $conn->prepare("SELECT idempleado FROM `empleado` WHERE email = ? LIMIT 1");
        $chk->bind_param("s", $empData['email']);
        $chk->execute();
        $resChk = $chk->get_result();
        if ($resChk->num_rows === 0) {
            $insEmp = $conn->prepare("INSERT INTO `empleado` (nombre, apellido, dni, email, usuario, fk_id_rol, fk_id_turno, avatar, vehiculo, patente, telefono, activo) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 1)");
            $insEmp->bind_param("sssssissss", $empData['nombre'], $empData['apellido'], $empData['dni'], $empData['email'], $empData['usuario'], $empData['rol'], $empData['avatar'], $empData['vehiculo'], $empData['patente'], $empData['telefono']);
            $insEmp->execute();
        } else {
            $rowEmp = $resChk->fetch_assoc();
            $updEmp = $conn->prepare("UPDATE `empleado` SET fk_id_rol = ?, avatar = ?, vehiculo = ?, patente = ?, telefono = ?, activo = 1 WHERE idempleado = ?");
            $updEmp->bind_param("issssi", $empData['rol'], $empData['avatar'], $empData['vehiculo'], $empData['patente'], $empData['telefono'], $rowEmp['idempleado']);
            $updEmp->execute();
        }
    }

    // 3. Tabla cupon
    $conn->query("CREATE TABLE IF NOT EXISTS `cupon` (
      `idcupon` INT(11) NOT NULL AUTO_INCREMENT,
      `codigo` VARCHAR(30) NOT NULL,
      `tipo` ENUM('porcentaje', 'fijo') NOT NULL DEFAULT 'porcentaje',
      `valor` DECIMAL(10,2) NOT NULL,
      `minimo_compra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `tope_descuento` DECIMAL(10,2) DEFAULT NULL,
      `activo` TINYINT(1) NOT NULL DEFAULT 1,
      `descripcion` VARCHAR(150) DEFAULT NULL,
      PRIMARY KEY (`idcupon`),
      UNIQUE KEY `codigo_unico` (`codigo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 4. Tabla pedido_web
    $conn->query("CREATE TABLE IF NOT EXISTS `pedido_web` (
      `idpedido` INT(11) NOT NULL AUTO_INCREMENT,
      `idusuario` INT(11) DEFAULT NULL,
      `idempleado` INT(11) DEFAULT NULL,
      `idarqueo` INT(11) DEFAULT NULL,
      `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `tipo_entrega` VARCHAR(30) NOT NULL DEFAULT 'delivery',
      `direccion_envio` VARCHAR(255) DEFAULT NULL,
      `telefono_contacto` VARCHAR(50) DEFAULT NULL,
      `sucursal` VARCHAR(100) DEFAULT 'Mostaza Central',
      `numero_mesa` INT(11) DEFAULT NULL,
      `metodo_pago` VARCHAR(50) NOT NULL DEFAULT 'efectivo',
      `estado_pago` VARCHAR(30) NOT NULL DEFAULT 'Aprobado',
      `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `costo_envio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `codigo_cupon` VARCHAR(50) DEFAULT NULL,
      `propina` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `puntos_usados` INT(11) NOT NULL DEFAULT 0,
      `puntos_ganados` INT(11) NOT NULL DEFAULT 0,
      `paga_con` DECIMAL(10,2) DEFAULT NULL,
      `vuelto` DECIMAL(10,2) DEFAULT NULL,
      `notas` TEXT DEFAULT NULL,
      `comprobante_transferencia` VARCHAR(100) DEFAULT NULL,
      `total` DECIMAL(10,2) NOT NULL,
      `estado` VARCHAR(30) NOT NULL DEFAULT 'Recibido',
      `repartidor_asignado` VARCHAR(100) DEFAULT NULL,
      PRIMARY KEY (`idpedido`),
      KEY `idx_usuario` (`idusuario`),
      KEY `idx_estado` (`estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // Verificar si faltan columnas en pedido_web
    $colsPed = $conn->query("SHOW COLUMNS FROM `pedido_web`");
    $pedCols = [];
    if ($colsPed) {
        while ($r = $colsPed->fetch_assoc()) {
            $pedCols[] = $r['Field'];
        }
    }
    $nuevasCols = [
        'idempleado' => "INT(11) DEFAULT NULL",
        'idarqueo' => "INT(11) DEFAULT NULL",
        'tipo_entrega' => "VARCHAR(30) NOT NULL DEFAULT 'delivery'",
        'direccion_envio' => "VARCHAR(255) DEFAULT NULL",
        'telefono_contacto' => "VARCHAR(50) DEFAULT NULL",
        'sucursal' => "VARCHAR(100) DEFAULT 'Mostaza Central'",
        'numero_mesa' => "INT(11) DEFAULT NULL",
        'metodo_pago' => "VARCHAR(50) NOT NULL DEFAULT 'efectivo'",
        'estado_pago' => "VARCHAR(30) NOT NULL DEFAULT 'Aprobado'",
        'subtotal' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'costo_envio' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'descuento' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'codigo_cupon' => "VARCHAR(50) DEFAULT NULL",
        'propina' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'puntos_usados' => "INT(11) NOT NULL DEFAULT 0",
        'puntos_ganados' => "INT(11) NOT NULL DEFAULT 0",
        'paga_con' => "DECIMAL(10,2) DEFAULT NULL",
        'vuelto' => "DECIMAL(10,2) DEFAULT NULL",
        'notas' => "TEXT DEFAULT NULL",
        'comprobante_transferencia' => "VARCHAR(100) DEFAULT NULL",
        'comprobante_archivo' => "VARCHAR(255) DEFAULT NULL",
        'latitud' => "DECIMAL(10,8) DEFAULT NULL",
        'longitud' => "DECIMAL(11,8) DEFAULT NULL",
        'referencia_entrega' => "VARCHAR(255) DEFAULT NULL",
        'repartidor_asignado' => "VARCHAR(100) DEFAULT NULL",
        'numero_pedido_usuario' => "INT(11) DEFAULT NULL",
        'idempleado_cajero' => "INT(11) DEFAULT NULL",
        'cajero_nombre' => "VARCHAR(100) DEFAULT NULL",
        'idempleado_mesero' => "INT(11) DEFAULT NULL",
        'mesero_nombre' => "VARCHAR(100) DEFAULT NULL",
        'idempleado_cocinero' => "INT(11) DEFAULT NULL",
        'cocinero_nombre' => "VARCHAR(100) DEFAULT NULL",
        'idempleado_repartidor' => "INT(11) DEFAULT NULL",
        'hora_recibido' => "DATETIME DEFAULT NULL",
        'hora_cocina_inicio' => "DATETIME DEFAULT NULL",
        'hora_cocina_listo' => "DATETIME DEFAULT NULL",
        'hora_despacho_camino' => "DATETIME DEFAULT NULL",
        'hora_entregado' => "DATETIME DEFAULT NULL",
        'duracion_cocina_minutos' => "INT(11) DEFAULT NULL",
        'duracion_entrega_minutos' => "INT(11) DEFAULT NULL",
        'duracion_total_minutos' => "INT(11) DEFAULT NULL",
        'numero_caja' => "INT(11) NOT NULL DEFAULT 1"
    ];
    foreach ($nuevasCols as $colName => $colDef) {
        if (!in_array($colName, $pedCols)) {
            $conn->query("ALTER TABLE `pedido_web` ADD `$colName` $colDef");
        }
    }
    // Permitir idusuario NULL para ventas de salón y mostrador en terminal POS
    $conn->query("ALTER TABLE `pedido_web` MODIFY `idusuario` INT(11) NULL DEFAULT NULL");

    // 5. Tabla detalle_pedido_web
    $conn->query("CREATE TABLE IF NOT EXISTS `detalle_pedido_web` (
      `iddetalle` INT(11) NOT NULL AUTO_INCREMENT,
      `idpedido` INT(11) NOT NULL,
      `idproducto` INT(11) DEFAULT NULL,
      `descripcion` VARCHAR(255) NOT NULL,
      `modificadores` VARCHAR(255) DEFAULT NULL,
      `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `cantidad` INT(11) NOT NULL DEFAULT 1,
      `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      PRIMARY KEY (`iddetalle`),
      KEY `idx_detalle_pedido` (`idpedido`),
      KEY `idx_detalle_producto` (`idproducto`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 6. Tabla caja_arqueo (Control de Caja POS y Turnos por Terminal)
    $conn->query("CREATE TABLE IF NOT EXISTS `caja_arqueo` (
      `idarqueo` INT(11) NOT NULL AUTO_INCREMENT,
      `numero_caja` INT(11) NOT NULL DEFAULT 1,
      `terminal_nombre` VARCHAR(50) DEFAULT 'Caja 1 - Principal',
      `idempleado` INT(11) DEFAULT NULL,
      `cajero` VARCHAR(100) NOT NULL,
      `turno` VARCHAR(50) NOT NULL,
      `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_cierre` DATETIME DEFAULT NULL,
      `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `ventas_efectivo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `ventas_tarjeta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `ventas_mercadopago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `ventas_transferencia` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `monto_retiros` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `total_ventas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `total_transacciones` INT(11) NOT NULL DEFAULT 0,
      `monto_final_sistema` DECIMAL(10,2) DEFAULT NULL,
      `monto_fisico_cierre` DECIMAL(10,2) DEFAULT NULL,
      `diferencia` DECIMAL(10,2) DEFAULT NULL,
      `observaciones_apertura` VARCHAR(255) DEFAULT NULL,
      `observaciones_cierre` VARCHAR(255) DEFAULT NULL,
      `estado` VARCHAR(20) NOT NULL DEFAULT 'Abierta',
      PRIMARY KEY (`idarqueo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // Verificar columnas en caja_arqueo
    $colsCaja = $conn->query("SHOW COLUMNS FROM `caja_arqueo`");
    $cajaCols = [];
    if ($colsCaja) {
        while ($r = $colsCaja->fetch_assoc()) {
            $cajaCols[] = $r['Field'];
        }
    }
    if (!in_array('numero_caja', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `numero_caja` INT(11) NOT NULL DEFAULT 1");
    }
    if (!in_array('terminal_nombre', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `terminal_nombre` VARCHAR(50) DEFAULT 'Caja 1 - Principal'");
    }
    if (!in_array('idempleado', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `idempleado` INT(11) DEFAULT NULL AFTER `terminal_nombre`");
    }
    if (!in_array('monto_retiros', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `monto_retiros` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }
    if (!in_array('monto_final_sistema', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `monto_final_sistema` DECIMAL(10,2) DEFAULT NULL");
    }
    if (!in_array('total_transacciones', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `total_transacciones` INT(11) NOT NULL DEFAULT 0");
    }
    if (!in_array('observaciones_apertura', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `observaciones_apertura` VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('observaciones_cierre', $cajaCols)) {
        $conn->query("ALTER TABLE `caja_arqueo` ADD `observaciones_cierre` VARCHAR(255) DEFAULT NULL");
    }

    // 6.1 Tabla caja_retiro (Sangrías / Retiros de Seguridad a Tesorería)
    $conn->query("CREATE TABLE IF NOT EXISTS `caja_retiro` (
      `idretiro` INT(11) NOT NULL AUTO_INCREMENT,
      `idarqueo` INT(11) NOT NULL,
      `numero_caja` INT(11) NOT NULL DEFAULT 1,
      `idempleado` INT(11) DEFAULT NULL,
      `cajero` VARCHAR(100) NOT NULL,
      `monto` DECIMAL(10,2) NOT NULL,
      `motivo` VARCHAR(255) NOT NULL DEFAULT 'Retiro de seguridad / Sangría a tesorería',
      `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`idretiro`),
      KEY `idx_retiro_arqueo` (`idarqueo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 7. Tabla factura (Facturación Fiscal de Cada Venta)
    $conn->query("CREATE TABLE IF NOT EXISTS `factura` (
      `idfactura` INT(11) NOT NULL AUTO_INCREMENT,
      `numero_factura` VARCHAR(50) NOT NULL,
      `tipo_comprobante` VARCHAR(30) NOT NULL DEFAULT 'Factura B',
      `idpedido` INT(11) NOT NULL,
      `idarqueo` INT(11) DEFAULT NULL,
      `idempleado` INT(11) DEFAULT NULL,
      `idusuario` INT(11) DEFAULT NULL,
      `nombre_cliente` VARCHAR(150) NOT NULL DEFAULT 'Consumidor Final',
      `cuit_dni_cliente` VARCHAR(20) NOT NULL DEFAULT '0',
      `fecha_emision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `metodo_pago` VARCHAR(50) NOT NULL DEFAULT 'efectivo',
      `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `neto_gravado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `iva_21` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `paga_con` DECIMAL(10,2) DEFAULT NULL,
      `vuelto` DECIMAL(10,2) DEFAULT NULL,
      `cuit_emisor` VARCHAR(20) NOT NULL DEFAULT '30-71234567-9',
      `razon_social_emisor` VARCHAR(100) NOT NULL DEFAULT 'Mostaza SA',
      `cae` VARCHAR(30) DEFAULT NULL,
      `cae_vto` DATE DEFAULT NULL,
      `estado` VARCHAR(30) NOT NULL DEFAULT 'Emitida',
      PRIMARY KEY (`idfactura`),
      UNIQUE KEY `num_factura_unico` (`numero_factura`),
      KEY `idx_factura_pedido` (`idpedido`),
      KEY `idx_factura_caja` (`idarqueo`),
      KEY `idx_factura_empleado` (`idempleado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 8. Tabla detalle_factura
    $conn->query("CREATE TABLE IF NOT EXISTS `detalle_factura` (
      `iddetalle_factura` INT(11) NOT NULL AUTO_INCREMENT,
      `idfactura` INT(11) NOT NULL,
      `idproducto` INT(11) DEFAULT NULL,
      `descripcion` VARCHAR(255) NOT NULL,
      `modificadores` VARCHAR(255) DEFAULT NULL,
      `precio_unitario` DECIMAL(10,2) NOT NULL,
      `cantidad` INT(11) NOT NULL DEFAULT 1,
      `subtotal` DECIMAL(10,2) NOT NULL,
      PRIMARY KEY (`iddetalle_factura`),
      KEY `idx_det_factura` (`idfactura`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 9. Tabla seccion_mesa (Secciones del Salón)
    $conn->query("CREATE TABLE IF NOT EXISTS `seccion_mesa` (
      `idseccion` INT(11) NOT NULL AUTO_INCREMENT,
      `nombre` VARCHAR(50) NOT NULL,
      `descripcion` VARCHAR(150) DEFAULT NULL,
      `icono` VARCHAR(50) DEFAULT 'fa-chair',
      `orden` INT(11) DEFAULT 1,
      PRIMARY KEY (`idseccion`),
      UNIQUE KEY `nombre_seccion_unico` (`nombre`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 10. Tabla mesa (Mesas interactivas con capacidad y sección)
    $conn->query("CREATE TABLE IF NOT EXISTS `mesa` (
      `idmesa` INT(11) NOT NULL AUTO_INCREMENT,
      `numero_mesa` INT(11) NOT NULL,
      `fk_id_seccion` INT(11) NOT NULL,
      `capacidad` INT(11) NOT NULL DEFAULT 4,
      `estado` ENUM('disponible', 'ocupada', 'reservada') NOT NULL DEFAULT 'disponible',
      `activa` TINYINT(1) NOT NULL DEFAULT 1,
      PRIMARY KEY (`idmesa`),
      UNIQUE KEY `num_mesa_unico` (`numero_mesa`),
      KEY `fk_mesa_seccion` (`fk_id_seccion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // Sembrar Secciones de Mesas
    $checkSec = $conn->query("SELECT COUNT(*) as c FROM `seccion_mesa`");
    if ($checkSec && ($checkSec->fetch_assoc()['c'] == 0)) {
        $conn->query("INSERT INTO `seccion_mesa` (`idseccion`, `nombre`, `descripcion`, `icono`, `orden`) VALUES
        (1, 'Salón Principal', 'Área central del restaurante frente al mostrador', 'fa-utensils', 1),
        (2, 'Terraza Exterior', 'Mesas al aire libre con vista a la avenida', 'fa-umbrella-beach', 2),
        (3, 'Planta Alta / VIP', 'Zona exclusiva y climatizada en primer piso', 'fa-crown', 3),
        (4, 'Barra & Boxes', 'Boxes privados y sillas de barra alta', 'fa-wine-glass', 4);");
    }

    // Sembrar 24 Mesas en las 4 Secciones
    $checkMesas = $conn->query("SELECT COUNT(*) as c FROM `mesa`");
    if ($checkMesas && ($checkMesas->fetch_assoc()['c'] == 0)) {
        $conn->query("INSERT INTO `mesa` (`numero_mesa`, `fk_id_seccion`, `capacidad`, `estado`, `activa`) VALUES
        (1, 1, 4, 'disponible', 1), (2, 1, 4, 'disponible', 1), (3, 1, 2, 'disponible', 1), (4, 1, 6, 'disponible', 1),
        (5, 1, 4, 'disponible', 1), (6, 1, 4, 'disponible', 1), (7, 1, 8, 'disponible', 1), (8, 1, 2, 'disponible', 1),
        (9, 2, 4, 'disponible', 1), (10, 2, 4, 'disponible', 1), (11, 2, 6, 'disponible', 1), (12, 2, 2, 'disponible', 1),
        (13, 2, 4, 'disponible', 1), (14, 2, 4, 'disponible', 1),
        (15, 3, 4, 'disponible', 1), (16, 3, 6, 'disponible', 1), (17, 3, 4, 'disponible', 1), (18, 3, 8, 'disponible', 1),
        (19, 3, 2, 'disponible', 1), (20, 3, 4, 'disponible', 1),
        (21, 4, 2, 'disponible', 1), (22, 4, 2, 'disponible', 1), (23, 4, 4, 'disponible', 1), (24, 4, 4, 'disponible', 1);");
    }

    // 11. Tabla stock_insumo
    $conn->query("CREATE TABLE IF NOT EXISTS `stock_insumo` (
      `idinsumo` INT(11) NOT NULL AUTO_INCREMENT,
      `nombre` VARCHAR(100) NOT NULL,
      `categoria` VARCHAR(50) NOT NULL DEFAULT 'Ingrediente',
      `unidad_medida` VARCHAR(20) NOT NULL DEFAULT 'unidades',
      `stock_actual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `stock_minimo` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
      `costo_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      `proveedor` VARCHAR(100) DEFAULT 'Proveedor General',
      `ultima_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`idinsumo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 12. Tabla registro_turno_empleado
    $conn->query("CREATE TABLE IF NOT EXISTS `registro_turno_empleado` (
      `idregistro` INT(11) NOT NULL AUTO_INCREMENT,
      `fk_id_empleado` INT(11) NOT NULL,
      `fk_id_turno` INT(11) NOT NULL,
      `fecha` DATE NOT NULL,
      `hora_entrada` DATETIME NOT NULL,
      `hora_salida` DATETIME DEFAULT NULL,
      `horas_trabajadas` DECIMAL(5,2) DEFAULT NULL,
      `estado` ENUM('Activo', 'Finalizado') NOT NULL DEFAULT 'Activo',
      `observaciones` VARCHAR(255) DEFAULT NULL,
      `origen` VARCHAR(50) DEFAULT 'App Cajero',
      PRIMARY KEY (`idregistro`),
      KEY `fk_registro_empleado` (`fk_id_empleado`),
      KEY `fk_registro_turno` (`fk_id_turno`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    // 13. Tabla inmutable pedido_auditoria (Trazabilidad estricta y detallada de cada empleado por pedido)
    $conn->query("CREATE TABLE IF NOT EXISTS `pedido_auditoria` (
      `idauditoria` INT(11) NOT NULL AUTO_INCREMENT,
      `idpedido` INT(11) NOT NULL,
      `estado_anterior` VARCHAR(50) DEFAULT NULL,
      `estado_nuevo` VARCHAR(50) NOT NULL,
      `accion` VARCHAR(100) NOT NULL,
      `rol_responsable` VARCHAR(50) NOT NULL,
      `idempleado` INT(11) DEFAULT NULL,
      `nombre_responsable` VARCHAR(100) NOT NULL,
      `detalles` TEXT DEFAULT NULL,
      `ip_origen` VARCHAR(45) DEFAULT NULL,
      `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`idauditoria`),
      KEY `idx_auditoria_pedido` (`idpedido`),
      KEY `idx_auditoria_fecha` (`fecha_hora`),
      KEY `idx_auditoria_rol` (`rol_responsable`),
      KEY `idx_auditoria_empleado` (`idempleado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Unificación de flota delivery: los repartidores son empleados con fk_id_rol = 6.
    // Vista de compatibilidad para evitar roturas en consultas legacy que consulten `repartidor`:
    $chkBaseRep = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE' AND (Tables_in_rest = 'repartidor' OR Tables_in_rest LIKE '%repartidor')");
    if ($chkBaseRep && $chkBaseRep->num_rows > 0) {
        $conn->query("DROP TABLE IF EXISTS `repartidor`");
    }
    $conn->query("CREATE OR REPLACE VIEW `repartidor` AS
      SELECT 
        `idempleado` AS `idrepartidor`,
        `idempleado`,
        CONCAT(`nombre`, ' ', `apellido`) AS `nombre`,
        `dni`,
        `telefono`,
        `vehiculo`,
        `patente`,
        `token` AS `pin`,
        `avatar`,
        `sucursal_base`,
        `activo`,
        `estado_servicio`,
        `calificacion`,
        `viajes_totales`,
        `email`
      FROM `empleado`
      WHERE `fk_id_rol` = 6;");

    // 14. Columnas en sucursal (GPS y radio de delivery)
    $colsSuc = $conn->query("SHOW COLUMNS FROM `sucursal`");
    if ($colsSuc) {
        $sucCols = [];
        while ($r = $colsSuc->fetch_assoc()) {
            $sucCols[] = $r['Field'];
        }
        if (!in_array('latitud', $sucCols)) {
            $conn->query("ALTER TABLE `sucursal` ADD `latitud` DECIMAL(10,8) DEFAULT NULL");
        }
        if (!in_array('longitud', $sucCols)) {
            $conn->query("ALTER TABLE `sucursal` ADD `longitud` DECIMAL(11,8) DEFAULT NULL");
        }
        if (!in_array('horario_atencion', $sucCols)) {
            $conn->query("ALTER TABLE `sucursal` ADD `horario_atencion` VARCHAR(100) DEFAULT 'Lunes a Domingo de 08:00 a 02:00'");
        }
    }
}

verificarEstructuraWeb($conn);

// ============================================================
// FUNCIÓN FISCAL MAESTRA: GENERAR FACTURA EN BASE DE DATOS
// ============================================================
function generarFacturaFiscal($conn, $idPedido, $tipoComprobante = 'Factura B', $idCajero = null, $datosExtra = []) {
    // 1. Obtener pedido
    $stmtP = $conn->prepare("SELECT p.*, u.nombre, u.apellido, u.email, u.telefono FROM pedido_web p LEFT JOIN usuario_web u ON p.idusuario = u.idusuario WHERE p.idpedido = ?");
    $stmtP->bind_param("i", $idPedido);
    $stmtP->execute();
    $pedido = $stmtP->get_result()->fetch_assoc();

    if (!$pedido) {
        throw new Exception("Pedido #$idPedido no encontrado para facturar.");
    }

    // 2. Obtener arqueo de caja abierto
    $idarqueo = $pedido['idarqueo'] ?? null;
    if (!$idarqueo) {
        $resCaja = $conn->query("SELECT idarqueo FROM caja_arqueo WHERE estado = 'Abierta' ORDER BY idarqueo DESC LIMIT 1");
        if ($resCaja && $cRow = $resCaja->fetch_assoc()) {
            $idarqueo = $cRow['idarqueo'];
            $conn->query("UPDATE pedido_web SET idarqueo = $idarqueo WHERE idpedido = $idPedido");
        }
    }

    // 3. Empleado / Cajero
    $idEmpleado = $idCajero ?? ($pedido['idempleado'] ?? 1);

    // 4. Datos del Cliente
    $nombreCliente = !empty($pedido['nombre']) ? ($pedido['nombre'] . ' ' . $pedido['apellido']) : ($datosExtra['nombre_cliente'] ?? 'Consumidor Final');
    $cuitDni = $datosExtra['cuit_dni_cliente'] ?? '0';

    // 5. Cálculos Impositivos (IVA 21% y Neto Gravado)
    $subtotal = floatval($pedido['subtotal']);
    $descuento = floatval($pedido['descuento']);
    $total = floatval($pedido['total']);
    $netoGravado = round($total / 1.21, 2);
    $iva21 = round($total - $netoGravado, 2);
    $pagaCon = floatval($pedido['paga_con'] ?? $total);
    $vuelto = floatval($pedido['vuelto'] ?? 0);
    $metodoPago = $pedido['metodo_pago'];

    // 6. Generar Número de Factura Oficial y CAE Fiscal AFIP Simulado
    $stmtCount = $conn->query("SELECT IFNULL(MAX(idfactura), 0) + 1 AS prox FROM factura");
    $proxId = ($stmtCount && $r = $stmtCount->fetch_assoc()) ? intval($r['prox']) : $idPedido;
    $puntoVenta = "0001";
    $numCorrelativo = str_pad($proxId, 8, '0', STR_PAD_LEFT);
    $numeroFactura = "FAC-B-$puntoVenta-$numCorrelativo";

    // CAE simulado estándar AFIP de 14 dígitos y Vencimiento a 10 días
    $cae = "74" . str_pad(mt_rand(1000000000, 9999999999), 11, '0', STR_PAD_LEFT) . "3";
    $caeVto = date('Y-m-d', strtotime('+10 days'));

    // 7. Insertar Encabezado de Factura
    $stmtF = $conn->prepare(
        "INSERT INTO factura (
            numero_factura, tipo_comprobante, idpedido, idarqueo, idempleado, idusuario,
            nombre_cliente, cuit_dni_cliente, metodo_pago, subtotal, descuento,
            neto_gravado, iva_21, total, paga_con, vuelto, cae, cae_vto, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Emitida')"
    );

    $stmtF->bind_param(
        "ssiiiisssddddddsss",
        $numeroFactura,
        $tipoComprobante,
        $idPedido,
        $idarqueo,
        $idEmpleado,
        $pedido['idusuario'],
        $nombreCliente,
        $cuitDni,
        $metodoPago,
        $subtotal,
        $descuento,
        $netoGravado,
        $iva21,
        $total,
        $pagaCon,
        $vuelto,
        $cae,
        $caeVto
    );

    $stmtF->execute();
    $idFactura = $conn->insert_id;

    // 8. Copiar Líneas de Detalle a detalle_factura
    $stmtDetPed = $conn->prepare("SELECT * FROM detalle_pedido_web WHERE idpedido = ?");
    $stmtDetPed->bind_param("i", $idPedido);
    $stmtDetPed->execute();
    $resDet = $stmtDetPed->get_result();

    $stmtDetFac = $conn->prepare(
        "INSERT INTO detalle_factura (idfactura, idproducto, descripcion, modificadores, precio_unitario, cantidad, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $itemsFactura = [];
    while ($it = $resDet->fetch_assoc()) {
        $stmtDetFac->bind_param("iissdid", $idFactura, $it['idproducto'], $it['descripcion'], $it['modificadores'], $it['precio_unitario'], $it['cantidad'], $it['subtotal']);
        $stmtDetFac->execute();

        $itemsFactura[] = [
            'idproducto' => $it['idproducto'],
            'descripcion' => $it['descripcion'],
            'modificadores' => $it['modificadores'],
            'precio_unitario' => floatval($it['precio_unitario']),
            'cantidad' => intval($it['cantidad']),
            'subtotal' => floatval($it['subtotal']),
            'subtotal_formateado' => '$' . number_format($it['subtotal'], 0, ',', '.')
        ];
    }

    // 9. Actualizar contador de transacciones en caja abierta
    if ($idarqueo) {
        $conn->query("UPDATE caja_arqueo SET total_transacciones = total_transacciones + 1 WHERE idarqueo = $idarqueo");
    }

    return [
        'idfactura' => $idFactura,
        'numero_factura' => $numeroFactura,
        'tipo_comprobante' => $tipoComprobante,
        'idpedido' => $idPedido,
        'idarqueo' => $idarqueo,
        'cajero' => $datosExtra['nombre_cajero'] ?? 'Cajero Mostaza',
        'nombre_cliente' => $nombreCliente,
        'cuit_dni_cliente' => $cuitDni,
        'cuit_emisor' => '30-71234567-9',
        'razon_social_emisor' => 'Mostaza SA',
        'fecha_emision' => date('d/m/Y H:i:s'),
        'metodo_pago' => $metodoPago,
        'subtotal' => $subtotal,
        'subtotal_formateado' => '$' . number_format($subtotal, 0, ',', '.'),
        'descuento' => $descuento,
        'descuento_formateado' => '$' . number_format($descuento, 0, ',', '.'),
        'neto_gravado' => $netoGravado,
        'neto_gravado_formateado' => '$' . number_format($netoGravado, 2, ',', '.'),
        'iva_21' => $iva21,
        'iva_21_formateado' => '$' . number_format($iva21, 2, ',', '.'),
        'total' => $total,
        'total_formateado' => '$' . number_format($total, 0, ',', '.'),
        'paga_con' => $pagaCon,
        'vuelto' => $vuelto,
        'vuelto_formateado' => '$' . number_format($vuelto, 0, ',', '.'),
        'cae' => $cae,
        'cae_vto' => date('d/m/Y', strtotime($caeVto)),
        'items' => $itemsFactura
    ];
}

// ============================================================
// FUNCIÓN CENTRAL DE AUDITORÍA ESTRICTA DE PEDIDOS Y EMPLEADOS
// Registra cada transición de estado inmutablemente y actualiza hitos
// ============================================================
function registrarAuditoriaPedido($conn, $idpedido, $estadoNuevo, $accion, $rol, $nombreResponsable, $idempleado = null, $detalles = null) {
    $idpedido = intval($idpedido);
    if ($idpedido <= 0) return false;

    // Obtener estado y fechas actuales del pedido
    $stmtP = $conn->prepare("SELECT estado, fecha, hora_recibido, hora_cocina_inicio, hora_cocina_listo, hora_despacho_camino, hora_entregado FROM pedido_web WHERE idpedido = ?");
    $stmtP->bind_param("i", $idpedido);
    $stmtP->execute();
    $p = $stmtP->get_result()->fetch_assoc();
    $estadoAnterior = $p['estado'] ?? 'Recibido';

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // 1. Insertar evento inmutable en pedido_auditoria
    $stmtAud = $conn->prepare("
        INSERT INTO pedido_auditoria (idpedido, estado_anterior, estado_nuevo, accion, rol_responsable, idempleado, nombre_responsable, detalles, ip_origen)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtAud->bind_param("issssisss", $idpedido, $estadoAnterior, $estadoNuevo, $accion, $rol, $idempleado, $nombreResponsable, $detalles, $ip);
    $stmtAud->execute();
    $idAuditoria = $stmtAud->insert_id;

    // 2. Actualizar estado e hitos temporales en pedido_web
    $updates = ["`estado` = '" . $conn->real_escape_string($estadoNuevo) . "'"];

    if ($estadoNuevo === 'Recibido') {
        $updates[] = "`hora_recibido` = IFNULL(`hora_recibido`, NOW())";
        if ($rol === 'Cajero' || $rol === 'Cajero POS') {
            $updates[] = "`cajero_nombre` = '" . $conn->real_escape_string($nombreResponsable) . "'";
            if ($idempleado) $updates[] = "`idempleado_cajero` = " . intval($idempleado);
        } elseif ($rol === 'Mesero' || $rol === 'Mesero Salón') {
            $updates[] = "`mesero_nombre` = '" . $conn->real_escape_string($nombreResponsable) . "'";
            if ($idempleado) $updates[] = "`idempleado_mesero` = " . intval($idempleado);
        }
    } elseif (in_array($estadoNuevo, ['En Cocina', 'En Preparacion', 'En Preparación'])) {
        $updates[] = "`hora_cocina_inicio` = IFNULL(`hora_cocina_inicio`, NOW())";
        $updates[] = "`cocinero_nombre` = '" . $conn->real_escape_string($nombreResponsable) . "'";
        if ($idempleado) $updates[] = "`idempleado_cocinero` = " . intval($idempleado);
    } elseif (in_array($estadoNuevo, ['Listo', 'Listo para Despacho'])) {
        $updates[] = "`hora_cocina_listo` = NOW()";
        $updates[] = "`hora_completado_cocina` = NOW()";
        if (!empty($nombreResponsable)) {
            $updates[] = "`cocinero_nombre` = '" . $conn->real_escape_string($nombreResponsable) . "'";
            if ($idempleado) $updates[] = "`idempleado_cocinero` = " . intval($idempleado);
        }
        $updates[] = "`duracion_cocina_minutos` = GREATEST(1, TIMESTAMPDIFF(MINUTE, IFNULL(hora_cocina_inicio, IFNULL(hora_recibido, fecha)), NOW()))";
    } elseif ($estadoNuevo === 'En Camino') {
        $updates[] = "`hora_despacho_camino` = NOW()";
        $updates[] = "`repartidor_asignado` = '" . $conn->real_escape_string($nombreResponsable) . "'";
        if ($idempleado) $updates[] = "`idempleado_repartidor` = " . intval($idempleado);
    } elseif ($estadoNuevo === 'Entregado') {
        $updates[] = "`hora_entregado` = NOW()";
        if (!empty($nombreResponsable)) {
            if ($rol === 'Repartidor' || $rol === 'Repartidor Delivery') {
                $updates[] = "`repartidor_asignado` = '" . $conn->real_escape_string($nombreResponsable) . "'";
                if ($idempleado) $updates[] = "`idempleado_repartidor` = " . intval($idempleado);
            } elseif ($rol === 'Mesero' || $rol === 'Mesero Salón' || $rol === 'Mozo') {
                $updates[] = "`mesero_nombre` = '" . $conn->real_escape_string($nombreResponsable) . "'";
                if ($idempleado) $updates[] = "`idempleado_mesero` = " . intval($idempleado);
            }
        }
        $updates[] = "`duracion_entrega_minutos` = GREATEST(1, TIMESTAMPDIFF(MINUTE, IFNULL(hora_despacho_camino, IFNULL(hora_cocina_listo, fecha)), NOW()))";
        $updates[] = "`duracion_total_minutos` = GREATEST(1, TIMESTAMPDIFF(MINUTE, IFNULL(hora_recibido, fecha), NOW()))";
    } elseif ($estadoNuevo === 'Cancelado') {
        $updates[] = "`cancelado_por` = '" . $conn->real_escape_string("$rol ($nombreResponsable)") . "'";
        if (!empty($detalles)) {
            $updates[] = "`motivo_cancelacion` = '" . $conn->real_escape_string($detalles) . "'";
        }
    }

    $conn->query("UPDATE pedido_web SET " . implode(', ', $updates) . " WHERE idpedido = $idpedido");

    return $idAuditoria;
}


