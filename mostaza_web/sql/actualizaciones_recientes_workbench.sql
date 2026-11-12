-- ============================================================
-- SCRIPT DE ACTUALIZACIONES RECIENTES PARA MYSQL WORKBENCH
-- Base de Datos: `mostaza_db` (o `rest`)
-- Proyecto: Mostaza Web, App Delivery, App Cocina KDS, App Mesero,
--           App Cajero (Terminal 01) y App Cajero 2 (Terminal 02).
-- EPET Nº 7 "Vicente Arcadio Salemi"
-- ============================================================

-- USE `mostaza_db`;

-- ------------------------------------------------------------
-- 1. UNIFICACIÓN DE ROLES Y EMPLEADOS (Nómina Única)
-- ------------------------------------------------------------
-- Los repartidores forman parte de la nómina de empleados (fk_id_rol = 6)
-- sin tablas base duplicadas.
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `dni` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(50) DEFAULT '👤';
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `sucursal_base` VARCHAR(100) DEFAULT 'Mostaza Formosa Centro';
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `vehiculo` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `patente` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `calificacion` DECIMAL(3,2) DEFAULT 4.95;
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `viajes_totales` INT(11) DEFAULT 0;
ALTER TABLE `empleado` ADD COLUMN IF NOT EXISTS `estado_servicio` ENUM('Disponible', 'En Reparto', 'En Descanso', 'Desconectado') DEFAULT 'Disponible';

-- Vista `repartidor` para compatibilidad transparente con endpoints legacy
CREATE OR REPLACE VIEW `repartidor` AS
SELECT 
    `idempleado` AS `idrepartidor`,
    `idempleado` AS `id`,
    CONCAT(`nombre`, ' ', `apellido`) AS `nombre`,
    `dni`,
    `telefono`,
    `email`,
    `vehiculo`,
    `patente`,
    '1234' AS `pin`,
    `avatar`,
    `sucursal_base`,
    `activo`,
    `estado_servicio`,
    `calificacion`,
    `viajes_totales`
FROM `empleado`
WHERE `fk_id_rol` = 6;

-- Semillas de Personal Oficial por Aplicación:
-- Rol 2: Cajero | Rol 3: Cocinero | Rol 4: Mesero | Rol 5: Administrador/Gerente | Rol 6: Delivery
INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `email`, `telefono`, `dni`, `fk_id_rol`, `avatar`, `vehiculo`, `patente`, `activo`) VALUES
(11, 'Martín', 'Paredes', 'cocina1@mostaza.com.ar', '3704-112233', '38.123.456', 3, '👨‍🍳', NULL, NULL, 1),
(12, 'Lucas', 'Silveira', 'cocina2@mostaza.com.ar', '3704-223344', '39.654.321', 3, '🍳', NULL, NULL, 1),
(13, 'Ramiro', 'González', 'delivery1@mostaza.com.ar', '3704-551122', '36.852.147', 6, '🛵', 'Moto Honda Wave 110cc', 'A123BCD', 1),
(14, 'Lucas', 'Benítez', 'delivery2@mostaza.com.ar', '3704-662233', '38.411.902', 6, '🏍️', 'Moto Yamaha YBR 125cc', 'A045EFG', 1),
(15, 'Alberto Emanuel', 'Gómez', 'applegomez64@gmail.com', '3704-884455', '35.602.819', 2, '🧑‍💼', NULL, NULL, 1),
(16, 'Romina', 'Castro', 'cajero1@mostaza.com.ar', '3704-998877', '37.456.789', 2, '👩‍💼', NULL, NULL, 1),
(17, 'Franco', 'Morales', 'mesero1@mostaza.com.ar', '3704-773344', '39.120.445', 4, '🤵', NULL, NULL, 1),
(18, 'Ezequiel', 'Molina', 'mesero2@mostaza.com.ar', '3704-445566', '40.890.123', 4, '🤵', NULL, NULL, 1)
ON DUPLICATE KEY UPDATE 
  `nombre` = VALUES(`nombre`),
  `apellido` = VALUES(`apellido`),
  `email` = VALUES(`email`),
  `fk_id_rol` = VALUES(`fk_id_rol`),
  `vehiculo` = VALUES(`vehiculo`),
  `patente` = VALUES(`patente`),
  `activo` = 1;

-- ------------------------------------------------------------
-- 2. CONTROL DE CAJA Y ARQUEO MULTITERMINAL (`caja_arqueo`)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caja_arqueo` (
  `idarqueo` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_caja` INT(11) NOT NULL DEFAULT 1,
  `terminal_nombre` VARCHAR(50) DEFAULT 'Caja 1 - Principal / Mostrador',
  `idempleado` INT(11) DEFAULT NULL,
  `cajero` VARCHAR(100) NOT NULL DEFAULT 'Cajero de Turno',
  `turno` VARCHAR(30) NOT NULL DEFAULT 'Turno Mañana',
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 15000.00,
  `ventas_efectivo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_tarjeta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_mercadopago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_transferencia` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_retiros` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_transacciones` INT(11) NOT NULL DEFAULT 0,
  `monto_fisico_cierre` DECIMAL(10,2) DEFAULT NULL,
  `monto_final_sistema` DECIMAL(10,2) DEFAULT NULL,
  `diferencia` DECIMAL(10,2) DEFAULT NULL,
  `observaciones_apertura` VARCHAR(255) DEFAULT NULL,
  `observaciones_cierre` VARCHAR(255) DEFAULT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'Abierta',
  `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` DATETIME DEFAULT NULL,
  PRIMARY KEY (`idarqueo`),
  KEY `idx_caja_numero` (`numero_caja`),
  KEY `idx_caja_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `caja_arqueo` ADD COLUMN IF NOT EXISTS `numero_caja` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `caja_arqueo` ADD COLUMN IF NOT EXISTS `terminal_nombre` VARCHAR(50) DEFAULT 'Caja 1 - Principal / Mostrador';
ALTER TABLE `caja_arqueo` ADD COLUMN IF NOT EXISTS `idempleado` INT(11) DEFAULT NULL;
ALTER TABLE `caja_arqueo` ADD COLUMN IF NOT EXISTS `monto_retiros` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE `caja_arqueo` ADD COLUMN IF NOT EXISTS `monto_final_sistema` DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE `caja_arqueo` ADD COLUMN IF NOT EXISTS `total_transacciones` INT(11) NOT NULL DEFAULT 0;

-- ------------------------------------------------------------
-- 3. TABLA `caja_retiro` (Sangrías / Retiros de Seguridad a Tesorería)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caja_retiro` (
  `idretiro` INT(11) NOT NULL AUTO_INCREMENT,
  `idarqueo` INT(11) NOT NULL,
  `numero_caja` INT(11) NOT NULL DEFAULT 1,
  `idempleado` INT(11) DEFAULT NULL,
  `cajero` VARCHAR(100) NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL,
  `motivo` VARCHAR(255) NOT NULL DEFAULT 'Retiro de seguridad / Sangría a tesorería',
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idretiro`),
  KEY `idx_retiro_arqueo` (`idarqueo`),
  KEY `idx_retiro_caja` (`numero_caja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 4. TRAZABILIDAD Y AUDITORÍA ESTRICTA (`pedido_auditoria`)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedido_auditoria` (
  `idauditoria` INT(11) NOT NULL AUTO_INCREMENT,
  `idpedido` INT(11) NOT NULL,
  `estado_anterior` VARCHAR(50) DEFAULT NULL,
  `estado_nuevo` VARCHAR(50) NOT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `rol_responsable` VARCHAR(50) NOT NULL,
  `idempleado` INT(11) DEFAULT NULL,
  `nombre_responsable` VARCHAR(150) NOT NULL,
  `detalles` TEXT DEFAULT NULL,
  `ip_origen` VARCHAR(50) DEFAULT '127.0.0.1',
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idauditoria`),
  KEY `idx_auditoria_pedido` (`idpedido`),
  KEY `idx_auditoria_empleado` (`idempleado`),
  KEY `idx_auditoria_fecha` (`fecha_hora`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 5. COLUMNAS DE TRAZABILIDAD Y TERMINAL EN `pedido_web`
-- ------------------------------------------------------------
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `numero_caja` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `idempleado` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `idarqueo` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `idempleado_cajero` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `cajero_nombre` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `idempleado_mesero` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `mesero_nombre` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `idempleado_cocinero` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `cocinero_nombre` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `idempleado_repartidor` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `repartidor_asignado` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `hora_recibido` DATETIME DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `hora_cocina_inicio` DATETIME DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `hora_cocina_listo` DATETIME DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `hora_despacho_camino` DATETIME DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `hora_entregado` DATETIME DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `duracion_cocina_minutos` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `duracion_entrega_minutos` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `duracion_total_minutos` INT(11) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `cancelado_por` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `motivo_cancelacion` TEXT DEFAULT NULL;

-- ------------------------------------------------------------
-- 6. CÓDIGOS DE SEGURIDAD GMAIL OTP (`codigo_seguridad`)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `codigo_seguridad` (
  `idcodigo` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(150) NOT NULL,
  `codigo` VARCHAR(10) NOT NULL,
  `expiracion` DATETIME NOT NULL,
  `usado` TINYINT(1) DEFAULT 0,
  `intentos` INT(11) DEFAULT 0,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idcodigo`),
  KEY `idx_email_codigo` (`email`, `codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 7. FACTURACIÓN FISCAL AFIP (`factura` y `detalle_factura`)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `factura` (
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
  `razon_social_emisor` VARCHAR(100) NOT NULL DEFAULT 'Mostaza Argentina SA',
  `cae` VARCHAR(30) DEFAULT NULL,
  `cae_vto` DATE DEFAULT NULL,
  `estado` VARCHAR(30) NOT NULL DEFAULT 'Emitida',
  PRIMARY KEY (`idfactura`),
  UNIQUE KEY `num_factura_unico` (`numero_factura`),
  KEY `idx_factura_pedido` (`idpedido`),
  KEY `idx_factura_caja` (`idarqueo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `detalle_factura` (
  `iddetalle` INT(11) NOT NULL AUTO_INCREMENT,
  `idfactura` INT(11) NOT NULL,
  `idproducto` INT(11) NOT NULL,
  `descripcion` VARCHAR(150) NOT NULL,
  `cantidad` INT(11) NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`iddetalle`),
  KEY `idx_detalle_factura` (`idfactura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
