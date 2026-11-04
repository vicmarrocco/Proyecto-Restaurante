-- ============================================================
-- schema_web.sql
-- Tablas y estructura para el sitio web y sistema de Mostaza.
-- Importar en la base de datos `rest`.
-- ============================================================

USE `rest`;

-- --------------------------------------------------------
-- Tabla `usuario_web`
-- Clientes que se registran en la página pública.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuario_web` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `producto`
-- Catálogo público de Mostaza.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `producto` (
  `idproducto` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `precio` DECIMAL(10,2) NOT NULL,
  `imagen` VARCHAR(150) NOT NULL,
  `categoria` VARCHAR(50) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idproducto`),
  KEY `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `cupon`
-- Cupones de descuento para el carrito de compras.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cupon` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `pedido_web`
-- Encabezado de cada pedido confirmado (E-Commerce y POS).
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedido_web` (
  `idpedido` INT(11) NOT NULL AUTO_INCREMENT,
  `idusuario` INT(11) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `detalle_pedido_web`
-- Líneas de cada pedido (productos o personalizados).
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalle_pedido_web` (
  `iddetalle` INT(11) NOT NULL AUTO_INCREMENT,
  `idpedido` INT(11) NOT NULL,
  `idproducto` INT(11) DEFAULT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `modificadores` VARCHAR(255) DEFAULT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `cantidad` INT(11) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`iddetalle`),
  KEY `idx_pedido` (`idpedido`),
  CONSTRAINT `fk_detalleweb_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido_web` (`idpedido`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `stock_insumo` (Inventario & Escandallo)
-- Control de ingredientes y stock de productos terminados.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `stock_insumo` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `caja_arqueo` (Caja & Turnos)
-- Apertura, cierre y arqueo de caja diario.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caja_arqueo` (
  `idarqueo` INT(11) NOT NULL AUTO_INCREMENT,
  `cajero` VARCHAR(100) NOT NULL DEFAULT 'Cajero de Turno',
  `turno` VARCHAR(30) NOT NULL DEFAULT 'Turno Mañana',
  `monto_inicial` DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
  `ventas_efectivo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_tarjeta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_mercadopago` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `ventas_transferencia` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `monto_fisico_cierre` DECIMAL(10,2) DEFAULT NULL,
  `diferencia` DECIMAL(10,2) DEFAULT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'Abierta',
  `fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` DATETIME DEFAULT NULL,
  PRIMARY KEY (`idarqueo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SEED DE CUPONES
-- ============================================================
INSERT INTO `cupon` (`codigo`, `tipo`, `valor`, `minimo_compra`, `tope_descuento`, `activo`, `descripcion`) VALUES
('MOSTAZA10', 'porcentaje', 10.00, 5000.00, 3000.00, 1, '10% de descuento en tu compra superior a $5.000'),
('DIBU20', 'porcentaje', 20.00, 10000.00, 5000.00, 1, '20% de descuento especial Dibu Martínez en compras de $10.000+'),
('TRUENO15', 'porcentaje', 15.00, 8000.00, 4000.00, 1, '15% de descuento edición Trueno'),
('PROMOAPP', 'fijo', 2000.00, 9000.00, 2000.00, 1, '$2.000 de descuento directo en compras desde $9.000'),
('BIENVENIDA', 'porcentaje', 25.00, 6000.00, 4000.00, 1, '25% de descuento especial de bienvenida')
ON DUPLICATE KEY UPDATE `activo`=1;

-- ============================================================
-- SEED DE INVENTARIO
-- ============================================================
INSERT INTO `stock_insumo` (`nombre`, `categoria`, `unidad_medida`, `stock_actual`, `stock_minimo`, `costo_unitario`, `proveedor`) VALUES
('Medallón de Carne Vacuna 120g', 'Carne', 'unidades', 350.00, 50.00, 950.00, 'Frigorífico El Norteño'),
('Pechuga de Pollo Crispy', 'Carne', 'unidades', 180.00, 30.00, 820.00, 'Distribuidora Avícola'),
('Pan de Hamburguesa con Sésamo', 'Panadería', 'unidades', 420.00, 80.00, 150.00, 'Panificadora San José'),
('Queso Cheddar en Fetas', 'Lácteos', 'kg', 45.00, 10.00, 3200.00, 'Lácteos La Serenísima'),
('Panceta / Bacon Ahumado', 'Fiambrería', 'kg', 28.00, 8.00, 4100.00, 'Frigorífico El Norteño'),
('Papas para Fritas McCain', 'Guarnición', 'kg', 120.00, 25.00, 750.00, 'Distribuidora Central'),
('Lechuga Repollada Fresca', 'Verdulería', 'kg', 22.00, 5.00, 600.00, 'Verdulería Central'),
('Tomates Redondos Seleccionados', 'Verdulería', 'kg', 26.00, 6.00, 850.00, 'Verdulería Central'),
('Cebolla Morada', 'Verdulería', 'kg', 18.00, 5.00, 500.00, 'Verdulería Central'),
('Salsa Especial Mostaza', 'Aderezos', 'litros', 35.00, 8.00, 1800.00, 'Salsas Argentinas SA'),
('Gaseosa Coca-Cola 500ml', 'Bebidas', 'unidades', 260.00, 40.00, 700.00, 'Distribuidora del Sur'),
('Caja / Packaging Mostaza', 'Descartables', 'unidades', 600.00, 100.00, 85.00, 'Packaging Express SRL')
ON DUPLICATE KEY UPDATE `stock_actual`=`stock_actual`;

-- ============================================================
-- SEED DE CAJA INICIAL
-- ============================================================
INSERT INTO `caja_arqueo` (`cajero`, `turno`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjeta`, `ventas_mercadopago`, `ventas_transferencia`, `total_ventas`, `estado`)
VALUES ('Cajero Central', 'Turno Tarde/Noche', 15000.00, 45000.00, 68000.00, 52000.00, 18500.00, 183500.00, 'Abierta')
ON DUPLICATE KEY UPDATE `estado`='Abierta';
