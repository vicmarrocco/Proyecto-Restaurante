-- ============================================================
-- database_definitiva.sql
-- BASE DE DATOS DEFINITIVA Y UNIFICADA PARA TODO EL PROYECTO
-- E-Commerce Web + API REST + App de Cajero + KDS + Repartidores
-- EPET Nº 7 "Vicente Arcadio Salemi" (7º IV - Programación)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `rest` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `rest`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. TABLAS BASE GEOGRÁFICAS Y DE CONTACTO
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ciudad` (
  `idciudad` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`idciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `barrio` (
  `idbarrio` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`idbarrio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `direccion` (
  `iddireccion` INT(11) NOT NULL AUTO_INCREMENT,
  `calle` VARCHAR(100) DEFAULT NULL,
  `manzana` VARCHAR(20) DEFAULT NULL,
  `casa` VARCHAR(20) DEFAULT NULL,
  `fk_id_barrio` INT(11) DEFAULT NULL,
  `fk_id_ciudad` INT(11) DEFAULT NULL,
  PRIMARY KEY (`iddireccion`),
  KEY `fk_dir_barrio` (`fk_id_barrio`),
  KEY `fk_dir_ciudad` (`fk_id_ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `telefono` (
  `idtelefono` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_de_area` VARCHAR(10) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`idtelefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `email` (
  `idemail` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`idemail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `genero` (
  `idgenero` INT(11) NOT NULL AUTO_INCREMENT,
  `genero` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`idgenero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `rol` (
  `idrol` INT(11) NOT NULL AUTO_INCREMENT,
  `rol` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`idrol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `turnos` (
  `idturnos` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) DEFAULT NULL,
  `horaInicio` TIME DEFAULT NULL,
  `horaFin` TIME DEFAULT NULL,
  `dias` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`idturnos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sucursal` (
  `idsucursal` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `fk_id_direccion` INT(11) DEFAULT NULL,
  `fk_id_ciudad` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idsucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 2. EMPLEADOS Y USUARIOS (LOGIN POR GMAIL / TOKEN)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `empleado` (
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
  `fk_id_rol` INT(11) DEFAULT NULL,
  `fk_id_genero` INT(11) DEFAULT NULL,
  `fk_id_turno` INT(11) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idempleado`),
  UNIQUE KEY `email_empleado_unico` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `registro_turno_empleado` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE IF NOT EXISTS `cliente` (
  `idcliente` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `apellido` VARCHAR(100) DEFAULT NULL,
  `usuario` VARCHAR(50) DEFAULT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `puntos` INT(11) NOT NULL DEFAULT 0,
  `fk_id_telefono` INT(11) DEFAULT NULL,
  `fk_id_direccion` INT(11) DEFAULT NULL,
  `fk_id_rol` INT(11) DEFAULT 1,
  `fk_id_email` INT(11) DEFAULT NULL,
  `fk_id_genero` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idcliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 3. PROVEEDORES E INVENTARIO (ESCANDALLO & STOCK)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `proveedor` (
  `idproveedor` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `rubro` VARCHAR(50) DEFAULT NULL,
  `fk_id_telefono` INT(11) DEFAULT NULL,
  `fk_id_email` INT(11) DEFAULT NULL,
  `fk_id_direccion` INT(11) DEFAULT NULL,
  `ultima_entrega` DATE DEFAULT NULL,
  `estado_entrega` VARCHAR(20) NOT NULL DEFAULT 'Al día',
  PRIMARY KEY (`idproveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- 4. CATÁLOGO DE PRODUCTOS & CUPONES
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
  KEY `idx_cat_prod` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  UNIQUE KEY `cod_cupon_unico` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 5. PEDIDOS, COMANDAS Y VENTAS
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pedido_web` (
  `idpedido` INT(11) NOT NULL AUTO_INCREMENT,
  `idusuario` INT(11) DEFAULT NULL,
  `idempleado` INT(11) DEFAULT NULL,
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
  KEY `idx_pedido_usuario` (`idusuario`),
  KEY `idx_pedido_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  KEY `idx_det_pedido` (`idpedido`),
  CONSTRAINT `fk_det_pedido_web` FOREIGN KEY (`idpedido`) REFERENCES `pedido_web` (`idpedido`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- 6. CAJA REGISTRADORA Y ARQUEOS POR TURNO
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `caja_arqueo` (
  `idarqueo` INT(11) NOT NULL AUTO_INCREMENT,
  `idempleado` INT(11) DEFAULT NULL,
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATOS INICIALES (SEEDS MAESTROS)
-- ============================================================

-- Roles
INSERT INTO `rol` (`idrol`, `rol`) VALUES
(1, 'Cliente'),
(2, 'Cajero'),
(3, 'Cocinero'),
(4, 'Mesero'),
(5, 'Administrador')
ON DUPLICATE KEY UPDATE `rol`=VALUES(`rol`);

-- Turnos
INSERT INTO `turnos` (`idturnos`, `nombre`, `horaInicio`, `horaFin`, `dias`) VALUES
(1, 'Turno Mañana', '08:00:00', '16:00:00', 'Lunes a Sábado'),
(2, 'Turno Tarde/Noche', '16:00:00', '00:00:00', 'Lunes a Domingo'),
(3, 'Turno Trasnoche', '00:00:00', '08:00:00', 'Viernes y Sábados')
ON DUPLICATE KEY UPDATE `nombre`=VALUES(`nombre`);

-- Empleados (Cajeros con email para autenticación vía Gmail)
INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `dni`, `email`, `usuario`, `fk_id_rol`, `fk_id_turno`, `activo`) VALUES
(1, 'Alberto Emanuel', 'Gómez', '38111222', 'applegomez64@gmail.com', 'cajero_central', 2, 1, 1),
(2, 'Federico', 'Álvarez', '28111000', 'falvarez@mostaza.com.ar', 'falvarez', 2, 1, 1),
(3, 'Romina', 'Castro', '29222111', 'rcastro@mostaza.com.ar', 'rcastro', 2, 2, 1),
(4, 'Ezequiel', 'Molina', '30333222', 'emolina@mostaza.com.ar', 'emolina', 3, 1, 1),
(5, 'Carla', 'Suárez', '31444333', 'csuarez@mostaza.com.ar', 'csuarez', 4, 2, 1)
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`), `activo`=1;

-- Catálogo de Productos
INSERT INTO `producto` (`idproducto`, `nombre`, `descripcion`, `precio`, `imagen`, `categoria`, `activo`) VALUES
(1, 'Hambre de la Cuarta | Provolone y Bacon', 'Cuarto de libra con provolone gratinado y bacon crocante', 9800.00, 'cardex.jpg', 'exclusivos', 1),
(2, 'La Cuarta | Ahumada', 'Cuarto de libra con salsa ahumada de la casa', 9300.00, 'card1.jpg', 'exclusivos', 1),
(3, 'La Cuarta | Merlo', 'Cuarto de libra especial estilo Merlo', 9500.00, 'merlo.jpg', 'exclusivos', 1),
(4, 'Combo Mega Provolone & Bacon', 'Combo con papas fritas y bebida grande', 11800.00, 'cardex.jpg', 'megacombos', 1),
(5, 'Combo Mega Cuarta Ahumada', 'Combo con papas fritas y bebida grande', 11300.00, 'card1.jpg', 'megacombos', 1),
(6, 'Combo Mega Cuarta Merlo', 'Combo con papas fritas y bebida grande', 11500.00, 'merlo.jpg', 'megacombos', 1),
(7, 'Doble Mega Big Bang Cheddar', 'Doble carne, doble cheddar, papas y bebida', 12800.00, 'doblwcheddar.jpg', 'megacombos', 1),
(8, 'Doble Mega Boom', 'Doble carne con salsa boom, papas y bebida', 12600.00, 'boom.jpg', 'megacombos', 1),
(9, 'Doble Cuarto a Fondo', 'Doble cuarto de libra a puro sabor con papas y bebida', 13200.00, 'fondo.jpg', 'megacombos', 1),
(10, 'Pollo Crispy Bacon', 'Sandwich de pollo crispy con bacon y salsa especial', 8600.00, 'pollobacon.jpg', 'pollo', 1),
(11, 'Pollo Crispy Clásico', 'Clásico sandwich de pechuga de pollo crocante', 7900.00, 'pollocrispy.jpg', 'pollo', 1),
(12, 'Nuggets de Pollo x10', '10 unidades de crocantes nuggets de pollo', 6900.00, 'x10.jpg', 'pollo', 1),
(13, 'Balde de Nuggets Mostaza', 'Balde gigante de nuggets para compartir', 12500.00, 'balde.jpg', 'pollo', 1),
(14, 'Ensalada Palta y Huevo Grill', 'Con pollo grillado, palta y huevo duro', 6800.00, 'ensaladapalta1.jpg', 'ensaladas', 1),
(15, 'Ensalada Caesar Crispy', 'Pechuga crispy, mix de verdes y aderezo caesar', 6900.00, 'Caesar 2.jpg', 'ensaladas', 1),
(16, 'Cero Veggie Cheese', 'Hamburguesa 100% veggie con queso cheddar', 8200.00, 'card3.jpg', 'paravos', 1),
(17, 'Mega Cuarto Doble sin TACC', 'Hamburguesa apta celíacos con pan sin TACC', 10800.00, 'sintacc.jpg', 'paravos', 1),
(18, 'Cajita Mostaza Héroes', 'Hamburguesa infantil + papas + juguete sorpresa', 5400.00, 'cajita1.jpg', 'cajitas', 1),
(19, 'Tostado XL Queso & Jamón', 'Tostado clásico gigante en pan de miga', 4600.00, 'tostadoxl.jpg', 'postres', 1),
(20, 'Medialunas Jamón & Queso', '2 medialunas de manteca rellenas', 2800.00, 'medialunas2.jpg', 'postres', 1),
(21, 'Shake Capuccino Frozen', 'Batido helado sabor café y dulce de leche', 3600.00, 'shake.jpg', 'postres', 1),
(22, 'Cono Helado ARG', 'Cono de helado suave edición celeste y blanca', 2500.00, 'Conoarg.jpg', 'postres', 1)
ON DUPLICATE KEY UPDATE `precio`=VALUES(`precio`), `activo`=1;

-- Cupones
INSERT INTO `cupon` (`codigo`, `tipo`, `valor`, `minimo_compra`, `tope_descuento`, `activo`, `descripcion`) VALUES
('MOSTAZA10', 'porcentaje', 10.00, 5000.00, 3000.00, 1, '10% de descuento en compras desde $5.000'),
('DIBU20', 'porcentaje', 20.00, 10000.00, 5000.00, 1, '20% de descuento combo Dibu Martínez'),
('TRUENO15', 'porcentaje', 15.00, 8000.00, 4000.00, 1, '15% de descuento edición Trueno'),
('PROMOAPP', 'fijo', 2000.00, 9000.00, 2000.00, 1, '$2.000 de descuento directo en $9.000+'),
('BIENVENIDA', 'porcentaje', 25.00, 6000.00, 4000.00, 1, '25% de descuento especial de bienvenida')
ON DUPLICATE KEY UPDATE `activo`=1;

-- Stock de Insumos (Escandallo)
INSERT INTO `stock_insumo` (`idinsumo`, `nombre`, `categoria`, `unidad_medida`, `stock_actual`, `stock_minimo`, `costo_unitario`, `proveedor`) VALUES
(1, 'Medallón de Carne Vacuna 120g', 'Carne', 'unidades', 350.00, 50.00, 950.00, 'Frigorífico El Norteño'),
(2, 'Pechuga de Pollo Crispy', 'Carne', 'unidades', 180.00, 30.00, 820.00, 'Distribuidora Avícola'),
(3, 'Pan de Hamburguesa con Sésamo', 'Panadería', 'unidades', 420.00, 80.00, 150.00, 'Panificadora San José'),
(4, 'Queso Cheddar en Fetas', 'Lácteos', 'kg', 45.00, 10.00, 3200.00, 'Lácteos La Serenísima'),
(5, 'Panceta / Bacon Ahumado', 'Fiambrería', 'kg', 28.00, 8.00, 4100.00, 'Frigorífico El Norteño'),
(6, 'Papas para Fritas McCain', 'Guarnición', 'kg', 120.00, 25.00, 750.00, 'Distribuidora Central'),
(7, 'Lechuga Repollada Fresca', 'Verdulería', 'kg', 22.00, 5.00, 600.00, 'Verdulería Central'),
(8, 'Tomates Redondos', 'Verdulería', 'kg', 26.00, 6.00, 850.00, 'Verdulería Central'),
(9, 'Salsa Especial Mostaza', 'Aderezos', 'litros', 35.00, 8.00, 1800.00, 'Salsas Argentinas SA'),
(10, 'Gaseosa Coca-Cola 500ml', 'Bebidas', 'unidades', 260.00, 40.00, 700.00, 'Distribuidora del Sur'),
(11, 'Packaging / Cajas Mostaza', 'Descartables', 'unidades', 600.00, 100.00, 85.00, 'Packaging Express SRL')
ON DUPLICATE KEY UPDATE `stock_actual`=VALUES(`stock_actual`);

-- Caja Inicial
INSERT INTO `caja_arqueo` (`cajero`, `turno`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjeta`, `ventas_mercadopago`, `ventas_transferencia`, `total_ventas`, `estado`)
VALUES ('Cajero Central Mostaza', 'Turno Tarde/Noche', 15000.00, 45000.00, 68000.00, 52000.00, 18500.00, 183500.00, 'Abierta')
ON DUPLICATE KEY UPDATE `estado`='Abierta';
