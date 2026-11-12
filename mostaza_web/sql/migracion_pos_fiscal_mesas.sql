-- ============================================================
-- MIGRACIÓN COMPLETA DE BASE DE DATOS · MOSTAZA REST
-- Facturación Fiscal AFIP, Arqueo de Turnos y Mesas por Secciones
-- Base de Datos: `rest`
-- ============================================================

USE `rest`;

-- ------------------------------------------------------------
-- 1. TABLA SECCIÓN DE MESAS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seccion_mesa` (
  `idseccion` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `icono` VARCHAR(50) NOT NULL DEFAULT 'fa-chair',
  `orden` INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idseccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar las 4 Secciones Principales
INSERT INTO `seccion_mesa` (`idseccion`, `nombre`, `icono`, `orden`) VALUES
(1, 'Salón Principal', 'fa-utensils', 1),
(2, 'Terraza Exterior', 'fa-sun', 2),
(3, 'Planta Alta / VIP', 'fa-crown', 3),
(4, 'Barra & Boxes', 'fa-martini-glass-citrus', 4)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `icono` = VALUES(`icono`), `orden` = VALUES(`orden`);

-- ------------------------------------------------------------
-- 2. TABLA MESAS CON ASIGNACIÓN DE SECCIÓN Y CAPACIDAD
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mesa` (
  `idmesa` INT(11) NOT NULL AUTO_INCREMENT,
  `numero_mesa` INT(11) NOT NULL,
  `fk_id_seccion` INT(11) NOT NULL,
  `capacidad` INT(11) NOT NULL DEFAULT 4,
  `activa` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idmesa`),
  UNIQUE KEY `idx_numero_mesa_unico` (`numero_mesa`),
  KEY `fk_mesa_seccion` (`fk_id_seccion`),
  CONSTRAINT `fk_mesa_seccion` FOREIGN KEY (`fk_id_seccion`) REFERENCES `seccion_mesa` (`idseccion`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sembrar las 24 Mesas con sus Secciones
INSERT INTO `mesa` (`numero_mesa`, `fk_id_seccion`, `capacidad`, `activa`) VALUES
-- Salón Principal (Mesas 1 a 8)
(1, 1, 4, 1), (2, 1, 4, 1), (3, 1, 6, 1), (4, 1, 4, 1),
(5, 1, 4, 1), (6, 1, 6, 1), (7, 1, 4, 1), (8, 1, 4, 1),
-- Terraza Exterior (Mesas 9 a 14)
(9, 2, 2, 1), (10, 2, 2, 1), (11, 2, 4, 1), (12, 2, 4, 1), (13, 2, 4, 1), (14, 2, 4, 1),
-- Planta Alta / VIP (Mesas 15 a 20)
(15, 3, 6, 1), (16, 3, 6, 1), (17, 3, 8, 1), (18, 3, 8, 1), (19, 3, 6, 1), (20, 3, 6, 1),
-- Barra & Boxes (Mesas 21 a 24)
(21, 4, 2, 1), (22, 4, 2, 1), (23, 4, 4, 1), (24, 4, 4, 1)
ON DUPLICATE KEY UPDATE `fk_id_seccion` = VALUES(`fk_id_seccion`), `capacidad` = VALUES(`capacidad`);

-- ------------------------------------------------------------
-- 3. TABLA FACTURA FISCAL AFIP
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `factura` (
  `idfactura` INT(11) NOT NULL AUTO_INCREMENT,
  `idpedido` INT(11) NOT NULL,
  `idempleado` INT(11) DEFAULT NULL,
  `idusuario` INT(11) DEFAULT NULL,
  `numero_factura` VARCHAR(30) NOT NULL,
  `tipo_comprobante` ENUM('Factura A', 'Factura B', 'Ticket Fiscal') NOT NULL DEFAULT 'Factura B',
  `punto_venta` VARCHAR(10) NOT NULL DEFAULT '0001',
  `cae` VARCHAR(20) NOT NULL,
  `cae_vto` DATE NOT NULL,
  `fecha_emision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cuit_emisor` VARCHAR(20) NOT NULL DEFAULT '30-71234567-9',
  `razon_social_emisor` VARCHAR(100) NOT NULL DEFAULT 'MOSTAZA GASTRONOMÍA S.A.',
  `nombre_cliente` VARCHAR(100) NOT NULL DEFAULT 'Consumidor Final',
  `cuit_dni_cliente` VARCHAR(20) DEFAULT '0',
  `metodo_pago` VARCHAR(50) NOT NULL DEFAULT 'efectivo',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descuento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `neto_gravado` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `iva_21` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paga_con` DECIMAL(10,2) DEFAULT NULL,
  `vuelto` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`idfactura`),
  UNIQUE KEY `idx_factura_numero` (`numero_factura`),
  KEY `fk_factura_pedido` (`idpedido`),
  CONSTRAINT `fk_factura_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido_web` (`idpedido`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 4. TABLA DETALLE DE FACTURA FISCAL
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalle_factura` (
  `iddetalle_factura` INT(11) NOT NULL AUTO_INCREMENT,
  `idfactura` INT(11) NOT NULL,
  `idproducto` INT(11) DEFAULT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `modificadores` VARCHAR(255) DEFAULT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `cantidad` INT(11) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`iddetalle_factura`),
  KEY `fk_detfac_factura` (`idfactura`),
  CONSTRAINT `fk_detfac_factura` FOREIGN KEY (`idfactura`) REFERENCES `factura` (`idfactura`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 5. AJUSTES EN TABLA CAJA_ARQUEO (TURNOS, MONTOS Y DIFERENCIAS)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caja_arqueo` (
  `idarqueo` INT(11) NOT NULL AUTO_INCREMENT,
  `idempleado` INT(11) DEFAULT NULL,
  `cajero` VARCHAR(100) NOT NULL DEFAULT 'Cajero Mostaza',
  `turno` VARCHAR(50) NOT NULL DEFAULT 'Turno Mañana',
  `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` DATETIME DEFAULT NULL,
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
  `ventas_efectivo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_tarjeta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_mercadopago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_transferencia` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_fisico_cierre` DECIMAL(10,2) DEFAULT NULL,
  `monto_final_sistema` DECIMAL(10,2) DEFAULT NULL,
  `diferencia` DECIMAL(10,2) DEFAULT NULL,
  `observaciones_apertura` TEXT DEFAULT NULL,
  `observaciones_cierre` TEXT DEFAULT NULL,
  `total_transacciones` INT(11) NOT NULL DEFAULT 0,
  `estado` ENUM('Abierta', 'Cerrada') NOT NULL DEFAULT 'Abierta',
  PRIMARY KEY (`idarqueo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 6. AJUSTES EN TABLA PEDIDO_WEB (SOPORTE POS, GPS Y MEDIOS DE PAGO)
-- ------------------------------------------------------------
ALTER TABLE `pedido_web` MODIFY `idusuario` INT(11) NULL DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `comprobante_archivo` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `latitud` DECIMAL(10,8) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `longitud` DECIMAL(11,8) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `referencia_entrega` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `pedido_web` ADD COLUMN IF NOT EXISTS `numero_pedido_usuario` INT DEFAULT NULL;
