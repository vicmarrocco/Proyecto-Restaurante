-- ====================================================================
-- SCHEMA DEFINITIVO Y UNIFICADO PARA MYSQL WORKBENCH / PHPMYADMIN
-- PROYECTO: SISTEMA INTEGRAL RESTAURANTE MOSTAZA (EPET Nº 7)
-- Compatible con: MySQL Workbench, HeidiSQL, DBeaver, phpMyAdmin
-- ====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `rest` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `rest`;

-- --------------------------------------------------------
-- 1. TABLAS GEOGRÁFICAS Y ESTRUCTURALES
-- --------------------------------------------------------

DROP TABLE IF EXISTS `barrio`;
CREATE TABLE `barrio` (
  `idbarrio` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`idbarrio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `barrio` (`idbarrio`, `nombre`) VALUES
(1, 'Palermo'),
(2, 'Recoleta'),
(3, 'Nueva Córdoba'),
(4, 'Centro'),
(5, 'Alberdi'),
(6, 'Güemes'),
(7, 'San Vicente'),
(8, 'Villa Freud');

DROP TABLE IF EXISTS `ciudad`;
CREATE TABLE `ciudad` (
  `idciudad` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`idciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ciudad` (`idciudad`, `nombre`) VALUES
(1, 'Buenos Aires'),
(2, 'Córdoba'),
(3, 'Rosario'),
(4, 'Mendoza'),
(5, 'Formosa'),
(6, 'Resistencia');

DROP TABLE IF EXISTS `direccion`;
CREATE TABLE `direccion` (
  `iddireccion` INT(11) NOT NULL AUTO_INCREMENT,
  `calle` VARCHAR(100) DEFAULT NULL,
  `manzana` VARCHAR(20) DEFAULT NULL,
  `casa` VARCHAR(20) DEFAULT NULL,
  `fk_id_barrio` INT(11) DEFAULT NULL,
  `fk_id_ciudad` INT(11) DEFAULT NULL,
  PRIMARY KEY (`iddireccion`),
  KEY `fk_id_barrio` (`fk_id_barrio`),
  KEY `fk_id_ciudad` (`fk_id_ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `direccion` (`iddireccion`, `calle`, `manzana`, `casa`, `fk_id_barrio`, `fk_id_ciudad`) VALUES
(1, 'Av. Santa Fe', NULL, '1234', 1, 1),
(2, 'Av. Callao', NULL, '567', 2, 1),
(3, 'Bv. Illia', NULL, '890', 3, 2),
(4, 'Av. Pellegrini', NULL, '456', 4, 3),
(5, 'Av. San Martín', NULL, '210', 4, 4),
(6, 'Av. 25 de Mayo', NULL, '150', 4, 5),
(7, 'Calle Brown', 'Mz 3', 'Casa 12', 5, 6),
(8, 'Av. Colón', NULL, '780', 6, 2),
(9, 'Bv. Oroño', NULL, '345', 1, 3),
(10, 'Av. Belgrano', NULL, '620', 7, 5);

DROP TABLE IF EXISTS `telefono`;
CREATE TABLE `telefono` (
  `idtelefono` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_de_area` VARCHAR(10) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`idtelefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `telefono` (`idtelefono`, `codigo_de_area`, `telefono`) VALUES
(1, '011', '45671234'),
(2, '011', '48901122'),
(3, '0351', '4223344'),
(4, '0341', '4556677'),
(5, '0261', '4334455'),
(6, '0370', '4432211'),
(7, '0362', '4445566'),
(8, '011', '15678901'),
(9, '0351', '4998877'),
(10, '0341', '15223344'),
(11, '0261', '4112233'),
(12, '0370', '4223311'),
(13, '0362', '4334422'),
(14, '011', '15990011'),
(15, '0351', '15223300');

DROP TABLE IF EXISTS `email`;
CREATE TABLE `email` (
  `idemail` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`idemail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `email` (`idemail`, `email`) VALUES
(1, 'contacto@mostaza.com.ar'),
(2, 'sucursal.palermo@mostaza.com.ar'),
(3, 'sucursal.cordoba@mostaza.com.ar'),
(4, 'sucursal.rosario@mostaza.com.ar'),
(5, 'sucursal.mendoza@mostaza.com.ar'),
(6, 'sucursal.formosa@mostaza.com.ar'),
(7, 'sucursal.resistencia@mostaza.com.ar'),
(8, 'juan.perez@gmail.com'),
(9, 'maria.gomez@hotmail.com'),
(10, 'lucas.fernandez@gmail.com'),
(11, 'sofia.martinez@gmail.com'),
(12, 'proveedor.carnes@frigorificonorte.com.ar'),
(13, 'proveedor.pan@panificadorasj.com.ar'),
(14, 'proveedor.bebidas@distribuidorasur.com.ar'),
(15, 'proveedor.verduras@verduleriacentral.com.ar'),
(16, 'applegomez64@gmail.com');

DROP TABLE IF EXISTS `genero`;
CREATE TABLE `genero` (
  `idgenero` INT(11) NOT NULL AUTO_INCREMENT,
  `genero` VARCHAR(30) DEFAULT NULL,
  PRIMARY KEY (`idgenero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `genero` (`idgenero`, `genero`) VALUES
(1, 'Masculino'),
(2, 'Femenino'),
(3, 'No binario');

DROP TABLE IF EXISTS `rol`;
CREATE TABLE `rol` (
  `idrol` INT(11) NOT NULL AUTO_INCREMENT,
  `rol` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`idrol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rol` (`idrol`, `rol`) VALUES
(1, 'Cliente'),
(2, 'Cajero'),
(3, 'Cocinero'),
(4, 'Mesero'),
(5, 'Administrador');

DROP TABLE IF EXISTS `turnos`;
CREATE TABLE `turnos` (
  `idturnos` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) DEFAULT 'Turno',
  `horaInicio` TIME DEFAULT NULL,
  `horaFin` TIME DEFAULT NULL,
  `dias` VARCHAR(30) DEFAULT 'Lunes a Domingo',
  PRIMARY KEY (`idturnos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `turnos` (`idturnos`, `nombre`, `horaInicio`, `horaFin`, `dias`) VALUES
(1, 'Turno Mañana', '06:00:00', '14:00:00', 'Lunes a Sábado'),
(2, 'Turno Tarde', '14:00:00', '22:00:00', 'Lunes a Domingo'),
(3, 'Turno Noche/Cierre', '22:00:00', '06:00:00', 'Viernes y Sábados');

DROP TABLE IF EXISTS `sucursal`;
CREATE TABLE `sucursal` (
  `idsucursal` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `fk_id_direccion` INT(11) DEFAULT NULL,
  `fk_id_ciudad` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idsucursal`),
  KEY `fk_id_direccion` (`fk_id_direccion`),
  KEY `fk_id_ciudad` (`fk_id_ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sucursal` (`idsucursal`, `nombre`, `fk_id_direccion`, `fk_id_ciudad`) VALUES
(1, 'Mostaza Palermo', 1, 1),
(2, 'Mostaza Nueva Córdoba', 3, 2),
(3, 'Mostaza Rosario Centro', 4, 3),
(4, 'Mostaza Mendoza Centro', 5, 4),
(5, 'Mostaza Formosa Centro', 6, 5),
(6, 'Mostaza Resistencia', 8, 6);

-- --------------------------------------------------------
-- 2. EMPLEADOS (CON LOGIN POR GMAIL) Y CLIENTES (CLUB MOSTAZA)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `empleado`;
CREATE TABLE `empleado` (
  `idempleado` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `apellido` VARCHAR(100) DEFAULT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) NOT NULL DEFAULT 'empleado@mostaza.com.ar',
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
  PRIMARY KEY (`idempleado`),
  UNIQUE KEY `usuario_unico` (`usuario`),
  KEY `fk_id_telefono` (`fk_id_telefono`),
  KEY `fk_id_email` (`fk_id_email`),
  KEY `fk_id_rol` (`fk_id_rol`),
  KEY `fk_id_genero` (`fk_id_genero`),
  KEY `fk_id_turno` (`fk_id_turno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `dni`, `email`, `usuario`, `fk_id_telefono`, `fk_id_email`, `fk_id_rol`, `fk_id_genero`, `fk_id_turno`, `activo`) VALUES
(1, 'Alberto Emanuel', 'Gómez', '38111222', 'applegomez64@gmail.com', 'cajero_central', 1, 16, 2, 1, 1, 1),
(2, 'Federico', 'Álvarez', '28111000', 'falvarez@mostaza.com.ar', 'falvarez', 11, 2, 4, 1, 1, 1),
(3, 'Romina', 'Castro', '29222111', 'rcastro@mostaza.com.ar', 'rcastro', 12, 3, 4, 2, 2, 1),
(4, 'Ezequiel', 'Molina', '30333222', 'emolina@mostaza.com.ar', 'emolina', 13, 4, 4, 1, 1, 1),
(5, 'Carla', 'Suárez', '31444333', 'csuarez@mostaza.com.ar', 'csuarez', 14, 5, 4, 2, 2, 1),
(6, 'Bruno', 'Acosta', '32555444', 'bacosta@mostaza.com.ar', 'bacosta', 15, 6, 4, 1, 3, 1),
(7, 'Milagros', 'Rojas', '33666555', 'mrojas@mostaza.com.ar', 'mrojas', 1, 7, 4, 2, 1, 1),
(8, 'Tomás', 'Ibáñez', '34777666', 'tibanez@mostaza.com.ar', 'tibanez', 2, 2, 3, 1, 1, 1),
(9, 'Julieta', 'Herrera', '35888777', 'jherrera@mostaza.com.ar', 'jherrera', 3, 3, 3, 2, 2, 1),
(10, 'Franco', 'Vega', '36999888', 'fvega@mostaza.com.ar', 'fvega', 4, 4, 3, 1, 3, 1),
(11, 'Ailén', 'Núñez', '37000999', 'anunez@mostaza.com.ar', 'anunez', 5, 5, 2, 2, 1, 1),
(12, 'Gonzalo', 'Paz', '38111888', 'gpaz@mostaza.com.ar', 'gpaz', 6, 6, 2, 1, 2, 1);

-- --------------------------------------------------------
-- 2.1 REGISTRO DE ASISTENCIA Y TURNOS DE EMPLEADOS (ENTRADA / SALIDA)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `registro_turno_empleado`;
CREATE TABLE `registro_turno_empleado` (
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
  KEY `fk_registro_turno` (`fk_id_turno`),
  CONSTRAINT `fk_reg_empleado` FOREIGN KEY (`fk_id_empleado`) REFERENCES `empleado` (`idempleado`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_turno` FOREIGN KEY (`fk_id_turno`) REFERENCES `turnos` (`idturnos`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `registro_turno_empleado` (`idregistro`, `fk_id_empleado`, `fk_id_turno`, `fecha`, `hora_entrada`, `hora_salida`, `horas_trabajadas`, `estado`, `observaciones`, `origen`) VALUES
(1, 1, 1, '2026-08-23', '2026-08-23 06:02:15', '2026-08-23 14:05:30', 8.05, 'Finalizado', 'Turno Mañana completado sin novedades', 'App Cajero'),
(2, 1, 1, '2026-08-24', '2026-08-24 06:00:00', NULL, NULL, 'Activo', 'Turno en curso', 'App Cajero');

DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
  `idcliente` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `apellido` VARCHAR(100) DEFAULT NULL,
  `usuario` VARCHAR(50) DEFAULT NULL,
  `dni` VARCHAR(20) DEFAULT NULL,
  `puntos` INT(11) NOT NULL DEFAULT 0,
  `fk_id_telefono` INT(11) DEFAULT NULL,
  `fk_id_direccion` INT(11) DEFAULT NULL,
  `fk_id_rol` INT(11) DEFAULT NULL,
  `fk_id_email` INT(11) DEFAULT NULL,
  `fk_id_genero` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idcliente`),
  KEY `fk_id_telefono` (`fk_id_telefono`),
  KEY `fk_id_direccion` (`fk_id_direccion`),
  KEY `fk_id_rol` (`fk_id_rol`),
  KEY `fk_id_email` (`fk_id_email`),
  KEY `fk_id_genero` (`fk_id_genero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cliente` (`idcliente`, `nombre`, `apellido`, `usuario`, `dni`, `fk_id_telefono`, `fk_id_direccion`, `fk_id_rol`, `fk_id_email`, `fk_id_genero`) VALUES
(1, 'Juan', 'Pérez', 'jperez', '30111222', 1, 1, 1, 8, 1),
(2, 'María', 'Gómez', 'mgomez', '31222333', 2, 2, 1, 9, 2),
(3, 'Lucas', 'Fernández', 'lfernandez', '32333444', 3, 3, 1, 10, 1),
(4, 'Sofía', 'Martínez', 'smartinez', '33444555', 4, 4, 1, 11, 2),
(5, 'Diego', 'Rodríguez', 'drodriguez', '34555666', 5, 5, 1, 8, 1);

DROP TABLE IF EXISTS `usuario_web`;
CREATE TABLE `usuario_web` (
  `idusuario` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(30) DEFAULT NULL,
  `direccion` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL DEFAULT '',
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

INSERT INTO `usuario_web` (`idusuario`, `nombre`, `apellido`, `telefono`, `direccion`, `email`, `verificado`, `puntos`) VALUES
(4, 'Gómez', 'Alberto Emanuel', '03704848212', 'B° El Quebranto mz1 cs 35', 'applegomez64@gmail.com', 1, 250),
(5, 'Vicky', 'Marroco', '3705148612', 'Clorinda', 'victoriamarrocco95@gmail.com', 1, 180);

-- --------------------------------------------------------
-- 3. PROVEEDORES, MATERIAS PRIMAS E INVENTARIO (ESCANDALLO)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `proveedor`;
CREATE TABLE `proveedor` (
  `idproveedor` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `rubro` VARCHAR(50) DEFAULT 'Insumos',
  `ultima_entrega` DATE DEFAULT NULL,
  `estado_entrega` VARCHAR(20) NOT NULL DEFAULT 'Al día',
  `fk_id_telefono` INT(11) DEFAULT NULL,
  `fk_id_email` INT(11) DEFAULT NULL,
  `fk_id_direccion` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idproveedor`),
  KEY `fk_id_telefono` (`fk_id_telefono`),
  KEY `fk_id_email` (`fk_id_email`),
  KEY `fk_id_direccion` (`fk_id_direccion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `proveedor` (`idproveedor`, `nombre`, `rubro`, `fk_id_telefono`, `fk_id_email`, `fk_id_direccion`) VALUES
(1, 'Frigorífico El Norteño', 'Cárnicos', 9, 12, 8),
(2, 'Panificadora San José', 'Panadería', 10, 13, 9),
(3, 'Distribuidora de Bebidas del Sur', 'Bebidas', 11, 14, 5),
(4, 'Verdulería Central', 'Verdulería', 12, 15, 6),
(5, 'Packaging Express SRL', 'Packaging', 13, 1, 3);

DROP TABLE IF EXISTS `mp`;
CREATE TABLE `mp` (
  `idmp` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `formato` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`idmp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `detallemp`;
CREATE TABLE `detallemp` (
  `iddetalleMp` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_mp` INT(11) DEFAULT NULL,
  `cantidad` INT(11) DEFAULT NULL,
  PRIMARY KEY (`iddetalleMp`),
  KEY `fk_id_mp` (`fk_id_mp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `insumo`;
CREATE TABLE `insumo` (
  `idinsumo` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `precio` DECIMAL(10,2) DEFAULT NULL,
  `stock` INT(11) NOT NULL DEFAULT 0,
  `stock_minimo` INT(11) NOT NULL DEFAULT 10,
  `categoria` VARCHAR(50) DEFAULT NULL,
  `fk_id_proveedor` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idinsumo`),
  KEY `fk_id_proveedor` (`fk_id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `insumo` (`idinsumo`, `nombre`, `precio`, `fk_id_proveedor`, `stock`, `stock_minimo`, `categoria`) VALUES
(1, 'Carne vacuna (kg)', 5200.00, 1, 180, 250, 'Insumo base'),
(2, 'Pechuga de pollo (kg)', 4300.00, 1, 220, 150, 'Insumo base'),
(3, 'Pan de hamburguesa (unidad)', 220.00, 2, 1240, 300, 'Insumo base'),
(4, 'Pan de pancho (unidad)', 180.00, 2, 560, 200, 'Insumo base'),
(5, 'Queso cheddar (kg)', 3800.00, 1, 640, 200, 'Insumo base'),
(6, 'Panceta (kg)', 4700.00, 1, 90, 100, 'Insumo base'),
(7, 'Lechuga (kg)', 900.00, 4, 75, 50, 'Verdulería'),
(8, 'Tomate (kg)', 1100.00, 4, 60, 50, 'Verdulería'),
(9, 'Papa para fritas (kg)', 700.00, 4, 410, 150, 'Acompañamiento'),
(10, 'Cebolla (kg)', 650.00, 4, 95, 40, 'Verdulería'),
(11, 'Salsa especial Mostaza (litro)', 2600.00, 5, 35, 20, 'Insumo base'),
(12, 'Gaseosa cola 2.25L', 1500.00, 3, 80, 100, 'Bebidas');

DROP TABLE IF EXISTS `stock_insumo`;
CREATE TABLE `stock_insumo` (
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
(11, 'Packaging / Cajas Mostaza', 'Descartables', 'unidades', 600.00, 100.00, 85.00, 'Packaging Express SRL');

-- --------------------------------------------------------
-- 4. PRODUCTOS, MENÚ Y PROMOCIONES
-- --------------------------------------------------------

DROP TABLE IF EXISTS `comida`;
CREATE TABLE `comida` (
  `idcomida` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) DEFAULT NULL,
  `fk_id_materiaPrimaComida` INT(11) DEFAULT NULL,
  `fk_id_proveedor` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idcomida`),
  KEY `fk_id_proveedor` (`fk_id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `comida` (`idcomida`, `nombre`, `fk_id_materiaPrimaComida`, `fk_id_proveedor`) VALUES
(1, 'Big Mostaza', 1, 1),
(2, 'Cheddar Doble', 5, 1),
(3, 'Crocanticio de Pollo', 2, 1),
(4, 'Pancho Especial', 4, 2),
(5, 'Wrap de Pollo', 2, 1),
(6, 'Hamburguesa Clásica', 1, 1),
(7, 'Combo Familiar Mostaza', 1, 1),
(8, 'Papas Fritas Grandes', 9, 4),
(9, 'Nuggets de Pollo x8', 2, 1),
(10, 'Ensalada César', 7, 4),
(11, 'Bacon Cheese', 6, 1),
(12, 'Veggie Burger', 9, 4);

DROP TABLE IF EXISTS `producto`;
CREATE TABLE `producto` (
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
(22, 'Cono Helado ARG', 'Cono de helado suave edición celeste y blanca', 2500.00, 'Conoarg.jpg', 'postres', 1);

DROP TABLE IF EXISTS `cupon`;
CREATE TABLE `cupon` (
  `idcupon` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `tipo` ENUM('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` DECIMAL(10,2) NOT NULL,
  `minimo_compra` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tope_descuento` DECIMAL(10,2) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `descripcion` VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (`idcupon`),
  UNIQUE KEY `cod_cupon_unico` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cupon` (`codigo`, `tipo`, `valor`, `minimo_compra`, `tope_descuento`, `activo`, `descripcion`) VALUES
('MOSTAZA10', 'porcentaje', 10.00, 5000.00, 3000.00, 1, '10% de descuento en compras desde $5.000'),
('DIBU20', 'porcentaje', 20.00, 10000.00, 5000.00, 1, '20% de descuento combo Dibu Martínez'),
('TRUENO15', 'porcentaje', 15.00, 8000.00, 4000.00, 1, '15% de descuento edición Trueno'),
('PROMOAPP', 'fijo', 2000.00, 9000.00, 2000.00, 1, '$2.000 de descuento directo en $9.000+'),
('BIENVENIDA', 'porcentaje', 25.00, 6000.00, 4000.00, 1, '25% de descuento especial de bienvenida');

-- --------------------------------------------------------
-- 5. CAJA REGISTRADORA Y ARQUEOS POR TURNO
-- --------------------------------------------------------

DROP TABLE IF EXISTS `caja`;
CREATE TABLE `caja` (
  `idcaja` INT(11) NOT NULL AUTO_INCREMENT,
  `montoFisico` DECIMAL(10,2) DEFAULT NULL,
  `montoVirtual` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`idcaja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `caja` (`idcaja`, `montoFisico`, `montoVirtual`) VALUES
(1, 15000.00, 42000.50),
(2, 12500.00, 38900.00),
(3, 9800.00, 27650.75),
(4, 18200.00, 51200.00),
(5, 7600.00, 19800.25),
(6, 11000.00, 30450.00);

DROP TABLE IF EXISTS `caja_arqueo`;
CREATE TABLE `caja_arqueo` (
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

INSERT INTO `caja_arqueo` (`cajero`, `turno`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjeta`, `ventas_mercadopago`, `ventas_transferencia`, `total_ventas`, `estado`)
VALUES ('Alberto Emanuel Gómez', 'Turno Mañana', 15000.00, 35000.00, 48000.00, 32000.00, 12000.00, 127000.00, 'Abierta');

-- --------------------------------------------------------
-- 6. PEDIDOS, COMANDAS Y VENTAS
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pedido`;
CREATE TABLE `pedido` (
  `idpedido` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_combo` INT(11) DEFAULT NULL,
  `fk_id_categoria` INT(11) DEFAULT NULL,
  `fk_id_insumoMenu` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idpedido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `insumopedido`;
CREATE TABLE `insumopedido` (
  `idinsumoPedido` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_insumo` INT(11) DEFAULT NULL,
  `cantidad` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idinsumoPedido`),
  KEY `fk_id_insumo` (`fk_id_insumo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `pedido_web`;
CREATE TABLE `pedido_web` (
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

DROP TABLE IF EXISTS `detalle_pedido_web`;
CREATE TABLE `detalle_pedido_web` (
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
-- 7. TICKETS Y FACTURACIÓN FISCAL
-- --------------------------------------------------------

DROP TABLE IF EXISTS `encabezadoticket`;
CREATE TABLE `encabezadoticket` (
  `idencabezadoTicket` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_sucursal` INT(11) DEFAULT NULL,
  `fk_id_telefono` INT(11) DEFAULT NULL,
  `cuit` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`idencabezadoTicket`),
  KEY `fk_id_sucursal` (`fk_id_sucursal`),
  KEY `fk_id_telefono` (`fk_id_telefono`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `encabezadoticket` (`idencabezadoTicket`, `fk_id_sucursal`, `fk_id_telefono`, `cuit`) VALUES
(1, 1, 1, '30-71234567-9'),
(2, 2, 2, '30-71234567-9'),
(3, 3, 3, '30-71234567-9');

DROP TABLE IF EXISTS `detalleticket`;
CREATE TABLE `detalleticket` (
  `iddetalleTicket` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_comida` INT(11) DEFAULT NULL,
  `fk_id_bebida` INT(11) DEFAULT NULL,
  `cantidadProducto` INT(11) DEFAULT NULL,
  `cantidadBebida` INT(11) DEFAULT NULL,
  `precioBebida` DECIMAL(10,2) DEFAULT NULL,
  `precioComida` DECIMAL(10,2) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`iddetalleTicket`),
  KEY `fk_id_comida` (`fk_id_comida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `resumenticket`;
CREATE TABLE `resumenticket` (
  `idresumenTicket` INT(11) NOT NULL AUTO_INCREMENT,
  `subtotal` DECIMAL(10,2) DEFAULT NULL,
  `descuento` DECIMAL(10,2) DEFAULT NULL,
  `total` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`idresumenTicket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `pieticket`;
CREATE TABLE `pieticket` (
  `idpieTicket` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_detalleMp` INT(11) DEFAULT NULL,
  `vuelto` DECIMAL(10,2) DEFAULT NULL,
  PRIMARY KEY (`idpieTicket`),
  KEY `fk_id_detalleMp` (`fk_id_detalleMp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ticket`;
CREATE TABLE `ticket` (
  `idticket` INT(11) NOT NULL AUTO_INCREMENT,
  `fk_id_encabezadoTicket` INT(11) DEFAULT NULL,
  `fechaHora` DATETIME DEFAULT NULL,
  `fk_id_caja` INT(11) DEFAULT NULL,
  `fk_id_empleado` INT(11) DEFAULT NULL,
  `fk_id_detalleTicket` INT(11) DEFAULT NULL,
  `fk_id_resumenTicket` INT(11) DEFAULT NULL,
  `fk_id_pieTicket` INT(11) DEFAULT NULL,
  PRIMARY KEY (`idticket`),
  KEY `fk_id_encabezadoTicket` (`fk_id_encabezadoTicket`),
  KEY `fk_id_caja` (`fk_id_caja`),
  KEY `fk_id_empleado` (`fk_id_empleado`),
  KEY `fk_id_detalleTicket` (`fk_id_detalleTicket`),
  KEY `fk_id_resumenTicket` (`fk_id_resumenTicket`),
  KEY `fk_id_pieTicket` (`fk_id_pieTicket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- CLAVES FORÁNEAS (RELACIONES DE INTEGRIDAD)
-- --------------------------------------------------------

ALTER TABLE `cliente`
  ADD CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`) ON DELETE SET NULL,
  ADD CONSTRAINT `cliente_ibfk_2` FOREIGN KEY (`fk_id_direccion`) REFERENCES `direccion` (`iddireccion`) ON DELETE SET NULL,
  ADD CONSTRAINT `cliente_ibfk_3` FOREIGN KEY (`fk_id_rol`) REFERENCES `rol` (`idrol`) ON DELETE SET NULL,
  ADD CONSTRAINT `cliente_ibfk_4` FOREIGN KEY (`fk_id_email`) REFERENCES `email` (`idemail`) ON DELETE SET NULL,
  ADD CONSTRAINT `cliente_ibfk_5` FOREIGN KEY (`fk_id_genero`) REFERENCES `genero` (`idgenero`) ON DELETE SET NULL;

ALTER TABLE `empleado`
  ADD CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`) ON DELETE SET NULL,
  ADD CONSTRAINT `empleado_ibfk_2` FOREIGN KEY (`fk_id_email`) REFERENCES `email` (`idemail`) ON DELETE SET NULL,
  ADD CONSTRAINT `empleado_ibfk_3` FOREIGN KEY (`fk_id_rol`) REFERENCES `rol` (`idrol`) ON DELETE SET NULL,
  ADD CONSTRAINT `empleado_ibfk_4` FOREIGN KEY (`fk_id_genero`) REFERENCES `genero` (`idgenero`) ON DELETE SET NULL,
  ADD CONSTRAINT `empleado_ibfk_5` FOREIGN KEY (`fk_id_turno`) REFERENCES `turnos` (`idturnos`) ON DELETE SET NULL;

ALTER TABLE `proveedor`
  ADD CONSTRAINT `proveedor_ibfk_1` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`) ON DELETE SET NULL,
  ADD CONSTRAINT `proveedor_ibfk_2` FOREIGN KEY (`fk_id_email`) REFERENCES `email` (`idemail`) ON DELETE SET NULL,
  ADD CONSTRAINT `proveedor_ibfk_3` FOREIGN KEY (`fk_id_direccion`) REFERENCES `direccion` (`iddireccion`) ON DELETE SET NULL;

ALTER TABLE `sucursal`
  ADD CONSTRAINT `sucursal_ibfk_1` FOREIGN KEY (`fk_id_direccion`) REFERENCES `direccion` (`iddireccion`) ON DELETE SET NULL,
  ADD CONSTRAINT `sucursal_ibfk_2` FOREIGN KEY (`fk_id_ciudad`) REFERENCES `ciudad` (`idciudad`) ON DELETE SET NULL;

ALTER TABLE `ticket`
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`fk_id_encabezadoTicket`) REFERENCES `encabezadoticket` (`idencabezadoTicket`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`fk_id_caja`) REFERENCES `caja` (`idcaja`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_ibfk_3` FOREIGN KEY (`fk_id_empleado`) REFERENCES `empleado` (`idempleado`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_ibfk_4` FOREIGN KEY (`fk_id_detalleTicket`) REFERENCES `detalleticket` (`iddetalleTicket`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_ibfk_5` FOREIGN KEY (`fk_id_resumenTicket`) REFERENCES `resumenticket` (`idresumenTicket`) ON DELETE SET NULL,
  ADD CONSTRAINT `ticket_ibfk_6` FOREIGN KEY (`fk_id_pieTicket`) REFERENCES `pieticket` (`idpieTicket`) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
