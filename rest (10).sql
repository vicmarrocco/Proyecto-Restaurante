-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-08-2026 a las 06:35:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `rest`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `idadmin` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`idadmin`, `nombre`, `apellido`, `usuario`, `password`, `email`, `activo`) VALUES
(1, 'Victoria', 'Marrocco', 'Admin1', '$2b$10$59RKPtTlc2.dCTuoC3LoI.vTcAnH2iHumPmUYkzSvcVT.chuSA2nG', 'applegomez64@gmail.com', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `barrio`
--

CREATE TABLE `barrio` (
  `idbarrio` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `barrio`
--

INSERT INTO `barrio` (`idbarrio`, `nombre`) VALUES
(1, 'Palermo'),
(2, 'Recoleta'),
(3, 'Nueva Córdoba'),
(4, 'Centro'),
(5, 'Alberdi'),
(6, 'Güemes'),
(7, 'San Vicente'),
(8, 'Villa Freud');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `idcaja` int(11) NOT NULL,
  `montoFisico` decimal(10,2) DEFAULT NULL,
  `montoVirtual` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja`
--

INSERT INTO `caja` (`idcaja`, `montoFisico`, `montoVirtual`) VALUES
(1, 15000.00, 42000.50),
(2, 12500.00, 38900.00),
(3, 9800.00, 27650.75),
(4, 18200.00, 51200.00),
(5, 7600.00, 19800.25),
(6, 11000.00, 30450.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_arqueo`
--

CREATE TABLE `caja_arqueo` (
  `idarqueo` int(11) NOT NULL,
  `cajero` varchar(100) NOT NULL DEFAULT 'Cajero de Turno',
  `turno` varchar(30) NOT NULL DEFAULT 'Turno Mañana',
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT 10000.00,
  `ventas_efectivo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ventas_tarjeta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ventas_mercadopago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ventas_transferencia` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ventas` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monto_fisico_cierre` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Abierta',
  `fecha_apertura` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_final_sistema` decimal(10,2) DEFAULT NULL,
  `total_transacciones` int(11) NOT NULL DEFAULT 0,
  `observaciones_apertura` varchar(255) DEFAULT NULL,
  `observaciones_cierre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja_arqueo`
--

INSERT INTO `caja_arqueo` (`idarqueo`, `cajero`, `turno`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjeta`, `ventas_mercadopago`, `ventas_transferencia`, `total_ventas`, `monto_fisico_cierre`, `diferencia`, `estado`, `fecha_apertura`, `fecha_cierre`, `monto_final_sistema`, `total_transacciones`, `observaciones_apertura`, `observaciones_cierre`) VALUES
(1, 'Cajero Central', 'Turno Tarde/Noche', 15000.00, 175932.00, 71405.00, 59900.00, 30000.00, 337237.00, NULL, NULL, 'Abierta', '2026-08-16 18:46:24', NULL, NULL, 27, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudad`
--

CREATE TABLE `ciudad` (
  `idciudad` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ciudad`
--

INSERT INTO `ciudad` (`idciudad`, `nombre`) VALUES
(1, 'Buenos Aires'),
(2, 'Córdoba'),
(3, 'Rosario'),
(4, 'Mendoza'),
(5, 'Formosa'),
(6, 'Resistencia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `idcliente` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `usuario` varchar(50) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `fk_id_telefono` int(11) DEFAULT NULL,
  `fk_id_direccion` int(11) DEFAULT NULL,
  `fk_id_rol` int(11) DEFAULT NULL,
  `fk_id_email` int(11) DEFAULT NULL,
  `fk_id_genero` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`idcliente`, `nombre`, `apellido`, `usuario`, `dni`, `fk_id_telefono`, `fk_id_direccion`, `fk_id_rol`, `fk_id_email`, `fk_id_genero`) VALUES
(1, 'Juan', 'Pérez', 'jperez', '30111222', 1, 1, 1, 8, 1),
(2, 'María', 'Gómez', 'mgomez', '31222333', 2, 2, 1, 9, 2),
(3, 'Lucas', 'Fernández', 'lfernandez', '32333444', 3, 3, 1, 10, 1),
(4, 'Sofía', 'Martínez', 'smartinez', '33444555', 4, 4, 1, 11, 2),
(5, 'Diego', 'Rodríguez', 'drodriguez', '34555666', 5, 5, 1, 8, 1),
(6, 'Valentina', 'López', 'vlopez', '35666777', 6, 6, 1, 9, 2),
(7, 'Martín', 'Sosa', 'msosa', '36777888', 7, 7, 1, 10, 1),
(8, 'Camila', 'Torres', 'ctorres', '37888999', 8, 8, 1, 11, 2),
(9, 'Nicolás', 'Ramírez', 'nramirez', '38999000', 9, 9, 1, 8, 1),
(10, 'Agustina', 'Díaz', 'adiaz', '39000111', 10, 10, 1, 9, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comida`
--

CREATE TABLE `comida` (
  `idcomida` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fk_id_materiaPrimaComida` int(11) DEFAULT NULL,
  `fk_id_proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comida`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cupon`
--

CREATE TABLE `cupon` (
  `idcupon` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `tipo` enum('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `minimo_compra` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tope_descuento` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `descripcion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cupon`
--

INSERT INTO `cupon` (`idcupon`, `codigo`, `tipo`, `valor`, `minimo_compra`, `tope_descuento`, `activo`, `descripcion`) VALUES
(1, 'MOSTAZA10', 'porcentaje', 10.00, 5000.00, 3000.00, 1, '10% de descuento en compras de $5.000+'),
(2, 'DIBU20', 'porcentaje', 20.00, 10000.00, 5000.00, 1, '20% de descuento combo Dibu'),
(3, 'TRUENO15', 'porcentaje', 15.00, 8000.00, 4000.00, 1, '15% de descuento edición Trueno'),
(4, 'PROMOAPP', 'fijo', 2000.00, 9000.00, 2000.00, 1, '$2.000 de descuento directo en $9.000+'),
(5, 'BIENVENIDA', 'porcentaje', 25.00, 6000.00, 4000.00, 1, '25% de descuento especial de bienvenida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallemp`
--

CREATE TABLE `detallemp` (
  `iddetalleMp` int(11) NOT NULL,
  `fk_id_mp` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detallemp`
--

INSERT INTO `detallemp` (`iddetalleMp`, `fk_id_mp`, `cantidad`) VALUES
(1, 1, 500),
(2, 2, 300),
(3, 3, 400),
(4, 4, 350),
(5, 5, 20),
(6, 6, 250),
(7, 7, 1000),
(8, 8, 15),
(9, 1, 200),
(10, 3, 150);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalleticket`
--

CREATE TABLE `detalleticket` (
  `iddetalleTicket` int(11) NOT NULL,
  `fk_id_comida` int(11) DEFAULT NULL,
  `fk_id_bebida` int(11) DEFAULT NULL,
  `cantidadProducto` int(11) DEFAULT NULL,
  `cantidadBebida` int(11) DEFAULT NULL,
  `precioBebida` decimal(10,2) DEFAULT NULL,
  `precioComida` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalleticket`
--

INSERT INTO `detalleticket` (`iddetalleTicket`, `fk_id_comida`, `fk_id_bebida`, `cantidadProducto`, `cantidadBebida`, `precioBebida`, `precioComida`, `subtotal`) VALUES
(1, 1, 1, 2, 2, 1200.00, 5800.00, 14000.00),
(2, 2, 2, 1, 1, 1200.00, 6200.00, 7400.00),
(3, 3, 1, 3, 3, 1200.00, 5500.00, 20100.00),
(4, 4, NULL, 1, 0, 0.00, 3200.00, 3200.00),
(5, 5, 2, 2, 2, 1200.00, 5300.00, 13000.00),
(6, 6, 1, 1, 1, 1200.00, 4800.00, 6000.00),
(7, 8, NULL, 2, 0, 0.00, 2100.00, 4200.00),
(8, 9, 2, 1, 1, 1200.00, 4700.00, 5900.00),
(9, 7, 1, 1, 1, 1200.00, 15800.00, 17000.00),
(10, 11, 2, 2, 2, 1200.00, 5900.00, 14200.00),
(11, 1, NULL, 1, 0, 0.00, 5800.00, 5800.00),
(12, 12, 1, 1, 1, 1200.00, 4600.00, 5800.00),
(13, 3, 2, 1, 1, 1200.00, 5500.00, 6700.00),
(14, 10, NULL, 1, 0, 0.00, 4100.00, 4100.00),
(15, 2, 1, 2, 2, 1200.00, 6200.00, 14800.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_factura`
--

CREATE TABLE `detalle_factura` (
  `iddetalle_factura` int(11) NOT NULL,
  `idfactura` int(11) NOT NULL,
  `idproducto` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `modificadores` varchar(255) DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_factura`
--

INSERT INTO `detalle_factura` (`iddetalle_factura`, `idfactura`, `idproducto`, `descripcion`, `modificadores`, `precio_unitario`, `cantidad`, `subtotal`) VALUES
(1, 1, 1, 'Mega Deluxe Doble', 'Sin pepinos', 9500.00, 2, 19000.00),
(2, 1, 11, 'Papas con Cheddar y Bacon', 'Extra queso', 4800.00, 1, 4800.00),
(3, 2, 28, 'Cajita Mostaza Héroes Clásica', '', 5200.00, 1, 5200.00),
(4, 2, 33, 'Tostado Queso & Jamón', '', 3800.00, 7, 26600.00),
(5, 3, NULL, 'Hamburguesa personalizada: Queso, Nuggets de Pollo', '', 2800.00, 1, 2800.00),
(6, 3, 40, 'Budín de Banana', '', 2100.00, 1, 2100.00),
(7, 3, 39, 'Alfajor de Chocolate', '', 1800.00, 1, 1800.00),
(8, 4, 24, 'Wrap Cheddar & Pollo', '', 6200.00, 1, 6200.00),
(9, 4, 56, 'Pollo Crispy Bacon', '', 8600.00, 1, 8600.00),
(10, 4, 86, 'Cono Clásico', '', 2200.00, 1, 2200.00),
(11, 5, 1, 'Hambre de la Cuarta | Provolone y Bacon', '', 9800.00, 1, 9800.00),
(12, 10, 1, 'Mega Cuarto XL', '', 22700.00, 1, 22700.00),
(13, 11, 1, 'Mega Cuarto XL', '', 20100.00, 1, 20100.00),
(14, 12, 1, 'Mega Cuarto XL', '', 14500.00, 1, 14500.00),
(15, 13, 1, 'Mega Cuarto XL', '', 32000.00, 1, 32000.00),
(16, 14, 83, 'Budín de Banana', '', 2100.00, 1, 2100.00),
(17, 15, 14, 'Pollo Crispy', '', 7900.00, 1, 7900.00),
(18, 16, 1, 'Mega Cuarto XL', '', 22700.00, 1, 22700.00),
(19, 17, 1, 'Mega Cuarto XL', '', 20100.00, 1, 20100.00),
(20, 18, 1, 'Mega Cuarto XL', '', 14500.00, 1, 14500.00),
(21, 19, 1, 'Mega Cuarto XL', '', 32000.00, 1, 32000.00),
(22, 20, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Nuggets de Pollo', '', 10300.00, 1, 10300.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido_web`
--

CREATE TABLE `detalle_pedido_web` (
  `iddetalle` int(11) NOT NULL,
  `idpedido` int(11) NOT NULL,
  `idproducto` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `modificadores` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido_web`
--

INSERT INTO `detalle_pedido_web` (`iddetalle`, `idpedido`, `idproducto`, `descripcion`, `precio_unitario`, `cantidad`, `subtotal`, `modificadores`) VALUES
(3, 2, 4, 'Hambre de la Cuarta | Provolone y Bacon', 11800.00, 1, 11800.00, NULL),
(4, 3, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Papas Fritas', 10000.00, 1, 10000.00, NULL),
(5, 3, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Papas Fritas', 10000.00, 1, 10000.00, NULL),
(6, 3, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Huevo, Tocino, Cebolla, Nuggets de Pollo', 8800.00, 1, 8800.00, NULL),
(7, 3, 5, 'La Cuarta | Ahumada', 11300.00, 1, 11300.00, NULL),
(8, 4, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00, NULL),
(9, 5, 28, 'Cajita Mostaza Héroes Clásica', 5200.00, 1, 5200.00, ''),
(13, 6, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Nuggets de Pollo', 10300.00, 1, 10300.00, ''),
(17, 7, 14, 'Pollo Crispy', 7900.00, 1, 7900.00, ''),
(20, 10, 1, 'Mega Deluxe Doble', 9500.00, 2, 19000.00, 'Sin pepinos'),
(21, 10, 11, 'Papas con Cheddar y Bacon', 4800.00, 1, 4800.00, 'Extra queso'),
(22, 11, 28, 'Cajita Mostaza Héroes Clásica', 5200.00, 1, 5200.00, ''),
(23, 11, 33, 'Tostado Queso & Jamón', 3800.00, 7, 26600.00, ''),
(24, 12, NULL, 'Hamburguesa personalizada: Queso, Nuggets de Pollo', 2800.00, 1, 2800.00, ''),
(25, 12, 40, 'Budín de Banana', 2100.00, 1, 2100.00, ''),
(26, 12, 39, 'Alfajor de Chocolate', 1800.00, 1, 1800.00, ''),
(27, 13, 24, 'Wrap Cheddar & Pollo', 6200.00, 1, 6200.00, ''),
(28, 13, 56, 'Pollo Crispy Bacon', 8600.00, 1, 8600.00, ''),
(29, 13, 86, 'Cono Clásico', 2200.00, 1, 2200.00, ''),
(30, 14, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00, ''),
(31, 20, 1, 'Mega Cuarto XL', 22700.00, 1, 22700.00, ''),
(32, 21, 1, 'Mega Cuarto XL', 20100.00, 1, 20100.00, ''),
(33, 22, 1, 'Mega Cuarto XL', 14500.00, 1, 14500.00, ''),
(34, 23, 1, 'Mega Cuarto XL', 32000.00, 1, 32000.00, ''),
(35, 24, 83, 'Budín de Banana', 2100.00, 1, 2100.00, ''),
(36, 25, 14, 'Pollo Crispy', 7900.00, 1, 7900.00, ''),
(37, 26, 1, 'Mega Cuarto XL', 22700.00, 1, 22700.00, ''),
(38, 27, 1, 'Mega Cuarto XL', 20100.00, 1, 20100.00, ''),
(39, 28, 1, 'Mega Cuarto XL', 14500.00, 1, 14500.00, ''),
(40, 29, 1, 'Mega Cuarto XL', 32000.00, 1, 32000.00, ''),
(41, 30, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Nuggets de Pollo', 10300.00, 1, 10300.00, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `iddireccion` int(11) NOT NULL,
  `calle` varchar(100) DEFAULT NULL,
  `manzana` varchar(20) DEFAULT NULL,
  `casa` varchar(20) DEFAULT NULL,
  `fk_id_barrio` int(11) DEFAULT NULL,
  `fk_id_ciudad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `direccion`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email`
--

CREATE TABLE `email` (
  `idemail` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `email`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `idempleado` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `apellido` varchar(100) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `fk_id_telefono` int(11) DEFAULT NULL,
  `fk_id_email` int(11) DEFAULT NULL,
  `fk_id_rol` int(11) DEFAULT NULL,
  `fk_id_genero` int(11) DEFAULT NULL,
  `fk_id_turno` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `email` varchar(150) NOT NULL DEFAULT 'cajero@mostaza.com.ar',
  `token` varchar(10) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `token_intentos` int(11) NOT NULL DEFAULT 0,
  `usuario` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `dni`, `fk_id_telefono`, `fk_id_email`, `fk_id_rol`, `fk_id_genero`, `fk_id_turno`, `activo`, `email`, `token`, `token_expira`, `token_intentos`, `usuario`, `password`) VALUES
(1, 'Federico', 'Álvarez', '28111000', 11, 2, 4, 1, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(2, 'Romina', 'Castro', '29222111', 12, 3, 4, 2, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(3, 'Ezequiel', 'Molina', '30333222', 13, 4, 4, 1, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(4, 'Carla', 'Suárez', '31444333', 14, 5, 4, 2, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(5, 'Bruno', 'Acosta', '32555444', 15, 6, 4, 1, 3, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(6, 'Milagros', 'Rojas', '33666555', 1, 7, 4, 2, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(7, 'Tomás', 'Ibáñez', '34777666', 2, 2, 3, 1, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(8, 'Julieta', 'Herrera', '35888777', 3, 3, 3, 2, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(9, 'Franco', 'Vega', '36999888', 4, 4, 3, 1, 3, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(10, 'Ailén', 'Núñez', '37000999', 5, 5, 2, 2, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(11, 'Gonzalo', 'Paz', '38111888', 6, 6, 2, 1, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(12, 'Micaela', 'Benítez', '39222777', 7, 7, 2, 2, 3, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL),
(13, 'Gómez', 'Alberto Emanuel', NULL, NULL, NULL, 2, NULL, NULL, 1, 'applegomez64@gmail.com', NULL, NULL, 0, 'applegomez64', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encabezadoticket`
--

CREATE TABLE `encabezadoticket` (
  `idencabezadoTicket` int(11) NOT NULL,
  `fk_id_sucursal` int(11) DEFAULT NULL,
  `fk_id_telefono` int(11) DEFAULT NULL,
  `cuit` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `encabezadoticket`
--

INSERT INTO `encabezadoticket` (`idencabezadoTicket`, `fk_id_sucursal`, `fk_id_telefono`, `cuit`) VALUES
(1, 1, 1, '30-71234567-9'),
(2, 2, 2, '30-71234567-9'),
(3, 3, 3, '30-71234567-9'),
(4, 4, 4, '30-71234567-9'),
(5, 5, 5, '30-71234567-9'),
(6, 6, 6, '30-71234567-9'),
(7, 1, 7, '30-71234567-9'),
(8, 3, 8, '30-71234567-9');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `idfactura` int(11) NOT NULL,
  `numero_factura` varchar(50) NOT NULL,
  `tipo_comprobante` varchar(30) NOT NULL DEFAULT 'Factura B',
  `idpedido` int(11) NOT NULL,
  `idarqueo` int(11) DEFAULT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `nombre_cliente` varchar(150) NOT NULL DEFAULT 'Consumidor Final',
  `cuit_dni_cliente` varchar(20) NOT NULL DEFAULT '0',
  `fecha_emision` datetime NOT NULL DEFAULT current_timestamp(),
  `metodo_pago` varchar(50) NOT NULL DEFAULT 'efectivo',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `neto_gravado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `iva_21` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paga_con` decimal(10,2) DEFAULT NULL,
  `vuelto` decimal(10,2) DEFAULT NULL,
  `cuit_emisor` varchar(20) NOT NULL DEFAULT '30-71234567-9',
  `razon_social_emisor` varchar(100) NOT NULL DEFAULT 'Mostaza SA',
  `cae` varchar(30) DEFAULT NULL,
  `cae_vto` date DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Emitida'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`idfactura`, `numero_factura`, `tipo_comprobante`, `idpedido`, `idarqueo`, `idempleado`, `idusuario`, `nombre_cliente`, `cuit_dni_cliente`, `fecha_emision`, `metodo_pago`, `subtotal`, `descuento`, `neto_gravado`, `iva_21`, `total`, `paga_con`, `vuelto`, `cuit_emisor`, `razon_social_emisor`, `cae`, `cae_vto`, `estado`) VALUES
(1, 'FAC-B-0001-00000001', 'Factura B', 10, 1, 1, NULL, 'Mesa 4 (Salón)', '0', '2026-08-25 01:22:58', 'efectivo', 23800.00, 0.00, 19669.42, 4130.58, 23800.00, 30000.00, 6200.00, '30-71234567-9', 'Mostaza SA', '74018963360203', '2026-09-04', 'Emitida'),
(2, 'FAC-B-0001-00000002', 'Factura B', 11, 1, 1, 4, 'Gómez Alberto Emanuel', '03704848212', '2026-08-25 08:14:52', 'efectivo', 31800.00, 5225.00, 25904.96, 5440.04, 31345.00, 31345.00, 0.00, '30-71234567-9', 'Mostaza SA', '74018964112113', '2026-09-04', 'Emitida'),
(3, 'FAC-B-0001-00000003', 'Factura B', 12, 1, 1, 4, 'Gómez Alberto Emanuel', '03704848212', '2026-08-25 08:22:20', 'efectivo', 6700.00, 313.00, 6109.09, 1282.91, 7392.00, 7392.00, 0.00, '30-71234567-9', 'Mostaza SA', '74052801638193', '2026-09-04', 'Emitida'),
(4, 'FAC-B-0001-00000004', 'Factura B', 13, 1, 1, 7, 'Camila Riquelme', '3704847532', '2026-08-25 08:27:39', 'efectivo', 17000.00, 0.00, 14752.07, 3097.93, 17850.00, 17850.00, 0.00, '30-71234567-9', 'Mostaza SA', '74088942488493', '2026-09-04', 'Emitida'),
(5, 'FAC-B-0001-00000005', 'Factura B', 14, 1, 13, NULL, 'Consumidor Final', '0', '2026-08-25 08:50:55', 'efectivo', 9800.00, 0.00, 8099.17, 1700.83, 9800.00, 20000.00, 10200.00, '30-71234567-9', 'Mostaza SA', '74026604138633', '2026-09-04', 'Emitida'),
(6, 'FAC-B-0001-00000006', 'Factura B', 15, 1, 1, NULL, 'Juan Perez', '0', '2026-08-25 17:55:45', 'efectivo', 18500.00, 0.00, 15289.26, 3210.74, 18500.00, 20000.00, 1500.00, '30-71234567-9', 'Mostaza SA', '74074848532573', '2026-09-04', 'Emitida'),
(7, 'FAC-B-0001-00000007', 'Factura B', 16, 1, 1, NULL, 'Maria Rodriguez', '0', '2026-08-25 17:55:45', 'transferencia', 24600.00, 0.00, 20330.58, 4269.42, 24600.00, 24600.00, 0.00, '30-71234567-9', 'Mostaza SA', '74045018314863', '2026-09-04', 'Emitida'),
(8, 'FAC-B-0001-00000008', 'Factura B', 17, 1, 1, NULL, 'Carlos Lopez', '0', '2026-08-25 17:55:45', 'tarjeta', 31200.00, 0.00, 25785.12, 5414.88, 31200.00, 31200.00, 0.00, '30-71234567-9', 'Mostaza SA', '74086826591083', '2026-09-04', 'Emitida'),
(9, 'FAC-B-0001-00000009', 'Factura B', 19, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-25 19:46:10', 'efectivo', 10000.00, 0.00, 8264.46, 1735.54, 10000.00, 10000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74071446480433', '2026-09-05', 'Emitida'),
(10, 'FAC-B-0001-00000010', 'Factura B', 20, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'efectivo', 21500.00, 0.00, 18760.33, 3939.67, 22700.00, 25000.00, 3500.00, '30-71234567-9', 'Mostaza SA', '74092012303653', '2026-09-05', 'Emitida'),
(11, 'FAC-B-0001-00000011', 'Factura B', 21, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'transferencia', 18900.00, 0.00, 16611.57, 3488.43, 20100.00, 18900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74066551396403', '2026-09-05', 'Emitida'),
(12, 'FAC-B-0001-00000012', 'Factura B', 22, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'mercadopago', 14500.00, 0.00, 11983.47, 2516.53, 14500.00, 14500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74094634603563', '2026-09-05', 'Emitida'),
(13, 'FAC-B-0001-00000013', 'Factura B', 23, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'tarjeta', 32000.00, 0.00, 26446.28, 5553.72, 32000.00, 32000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74057467745943', '2026-09-05', 'Emitida'),
(14, 'FAC-B-0001-00000014', 'Factura B', 24, 1, 1, 4, 'Gómez Alberto Emanuel', '03704848212', '2026-08-25 19:51:09', 'tarjeta', 2100.00, 0.00, 2814.05, 590.95, 3405.00, 3405.00, 0.00, '30-71234567-9', 'Mostaza SA', '74011612174393', '2026-09-05', 'Emitida'),
(15, 'FAC-B-0001-00000015', 'Factura B', 25, 1, 13, NULL, 'Consumidor Final', '0', '2026-08-26 00:55:07', 'mercadopago', 7900.00, 0.00, 6528.93, 1371.07, 7900.00, 7900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74036829929183', '2026-09-05', 'Emitida'),
(16, 'FAC-B-0001-00000016', 'Factura B', 26, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'efectivo', 21500.00, 0.00, 18760.33, 3939.67, 22700.00, 25000.00, 3500.00, '30-71234567-9', 'Mostaza SA', '74061725645133', '2026-09-05', 'Emitida'),
(17, 'FAC-B-0001-00000017', 'Factura B', 27, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'transferencia', 18900.00, 0.00, 16611.57, 3488.43, 20100.00, 18900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74051488583173', '2026-09-05', 'Emitida'),
(18, 'FAC-B-0001-00000018', 'Factura B', 28, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'mercadopago', 14500.00, 0.00, 11983.47, 2516.53, 14500.00, 14500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74026853920733', '2026-09-05', 'Emitida'),
(19, 'FAC-B-0001-00000019', 'Factura B', 29, 1, 1, 4, 'Gómez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'tarjeta', 32000.00, 0.00, 26446.28, 5553.72, 32000.00, 32000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74077350069893', '2026-09-05', 'Emitida'),
(20, 'FAC-B-0001-00000020', 'Factura B', 30, 1, 1, 4, 'Gómez Alberto Emanuel', '3704848212', '2026-08-26 01:28:25', 'transferencia', 10300.00, 0.00, 9504.13, 1995.87, 11500.00, 11500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74052737130083', '2026-09-05', 'Emitida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `genero`
--

CREATE TABLE `genero` (
  `idgenero` int(11) NOT NULL,
  `genero` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `genero`
--

INSERT INTO `genero` (`idgenero`, `genero`) VALUES
(1, 'Masculino'),
(2, 'Femenino'),
(3, 'No binario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumo`
--

CREATE TABLE `insumo` (
  `idinsumo` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `fk_id_proveedor` int(11) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `stock_minimo` int(11) NOT NULL DEFAULT 10,
  `categoria` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumo`
--

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
(12, 'Gaseosa cola 2.25L', 1500.00, 3, 0, 100, 'Bebidas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumopedido`
--

CREATE TABLE `insumopedido` (
  `idinsumoPedido` int(11) NOT NULL,
  `fk_id_insumo` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumopedido`
--

INSERT INTO `insumopedido` (`idinsumoPedido`, `fk_id_insumo`, `cantidad`) VALUES
(1, 1, 50),
(2, 2, 40),
(3, 3, 300),
(4, 5, 25),
(5, 9, 60),
(6, 7, 15),
(7, 8, 15),
(8, 11, 10),
(9, 12, 100),
(10, 6, 20);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesa`
--

CREATE TABLE `mesa` (
  `idmesa` int(11) NOT NULL,
  `numero_mesa` int(11) NOT NULL,
  `fk_id_seccion` int(11) NOT NULL,
  `capacidad` int(11) NOT NULL DEFAULT 4,
  `estado` enum('disponible','ocupada','reservada') NOT NULL DEFAULT 'disponible',
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mesa`
--

INSERT INTO `mesa` (`idmesa`, `numero_mesa`, `fk_id_seccion`, `capacidad`, `estado`, `activa`) VALUES
(1, 1, 1, 4, 'disponible', 1),
(2, 2, 1, 4, 'disponible', 1),
(3, 3, 1, 6, 'disponible', 1),
(4, 4, 1, 4, 'disponible', 1),
(5, 5, 1, 4, 'disponible', 1),
(6, 6, 1, 6, 'disponible', 1),
(7, 7, 1, 4, 'disponible', 1),
(8, 8, 1, 4, 'disponible', 1),
(9, 9, 2, 2, 'disponible', 1),
(10, 10, 2, 2, 'disponible', 1),
(11, 11, 2, 4, 'disponible', 1),
(12, 12, 2, 4, 'disponible', 1),
(13, 13, 2, 4, 'disponible', 1),
(14, 14, 2, 4, 'disponible', 1),
(15, 15, 3, 6, 'disponible', 1),
(16, 16, 3, 6, 'disponible', 1),
(17, 17, 3, 8, 'disponible', 1),
(18, 18, 3, 8, 'disponible', 1),
(19, 19, 3, 6, 'disponible', 1),
(20, 20, 3, 6, 'disponible', 1),
(21, 21, 4, 2, 'disponible', 1),
(22, 22, 4, 2, 'disponible', 1),
(23, 23, 4, 4, 'disponible', 1),
(24, 24, 4, 4, 'disponible', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mp`
--

CREATE TABLE `mp` (
  `idmp` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `formato` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mp`
--

INSERT INTO `mp` (`idmp`, `nombre`, `formato`) VALUES
(1, NULL, NULL),
(2, NULL, NULL),
(3, NULL, NULL),
(4, NULL, NULL),
(5, NULL, NULL),
(6, NULL, NULL),
(7, NULL, NULL),
(8, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `idpedido` int(11) NOT NULL,
  `fk_id_combo` int(11) DEFAULT NULL,
  `fk_id_categoria` int(11) DEFAULT NULL,
  `fk_id_insumoMenu` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`idpedido`, `fk_id_combo`, `fk_id_categoria`, `fk_id_insumoMenu`) VALUES
(1, 1, 1, 1),
(2, 2, 1, 5),
(3, 3, 2, 2),
(4, 4, 2, 3),
(5, 1, 3, 8),
(6, 2, 3, 9),
(7, 5, 1, 11),
(8, 6, 2, 6),
(9, 3, 1, 12),
(10, 4, 3, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_web`
--

CREATE TABLE `pedido_web` (
  `idpedido` int(11) NOT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Recibido',
  `tipo_entrega` varchar(30) NOT NULL DEFAULT 'delivery',
  `direccion_envio` varchar(255) DEFAULT NULL,
  `telefono_contacto` varchar(50) DEFAULT NULL,
  `sucursal` varchar(100) DEFAULT 'Mostaza Central',
  `numero_mesa` int(11) DEFAULT NULL,
  `metodo_pago` varchar(50) NOT NULL DEFAULT 'efectivo',
  `estado_pago` varchar(30) NOT NULL DEFAULT 'Aprobado',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `codigo_cupon` varchar(50) DEFAULT NULL,
  `propina` decimal(10,2) NOT NULL DEFAULT 0.00,
  `puntos_usados` int(11) NOT NULL DEFAULT 0,
  `puntos_ganados` int(11) NOT NULL DEFAULT 0,
  `paga_con` decimal(10,2) DEFAULT NULL,
  `vuelto` decimal(10,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `comprobante_transferencia` varchar(100) DEFAULT NULL,
  `repartidor_asignado` varchar(100) DEFAULT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `idarqueo` int(11) DEFAULT NULL,
  `comprobante_archivo` varchar(255) DEFAULT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `referencia_entrega` varchar(255) DEFAULT NULL,
  `numero_pedido_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_web`
--

INSERT INTO `pedido_web` (`idpedido`, `idusuario`, `fecha`, `total`, `estado`, `tipo_entrega`, `direccion_envio`, `telefono_contacto`, `sucursal`, `numero_mesa`, `metodo_pago`, `estado_pago`, `subtotal`, `costo_envio`, `descuento`, `codigo_cupon`, `propina`, `puntos_usados`, `puntos_ganados`, `paga_con`, `vuelto`, `notas`, `comprobante_transferencia`, `repartidor_asignado`, `idempleado`, `idarqueo`, `comprobante_archivo`, `latitud`, `longitud`, `referencia_entrega`, `numero_pedido_usuario`) VALUES
(2, 5, '2026-07-31 08:28:59', 11800.00, 'En Camino', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, 'Ramiro González', NULL, NULL, NULL, NULL, NULL, NULL, 1),
(3, 5, '2026-07-31 09:53:19', 40100.00, 'Recibido', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(4, 4, '2026-07-31 10:01:04', 9800.00, 'Recibido', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(5, 4, '2026-08-17 19:59:16', 5200.00, 'Recibido', 'salon', 'B° El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 4, 'efectivo', 'Aprobado', 5200.00, 0.00, 0.00, NULL, 0.00, 0, 52, 5200.00, 0.00, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(6, 4, '2026-08-18 08:27:38', 8755.00, 'Recibido', 'salon', 'B° El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 4, 'efectivo', 'Aprobado', 10300.00, 0.00, 2060.00, 'DIBU20', 515.00, 0, 87, 8755.00, 0.00, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3),
(7, 4, '2026-08-19 11:26:55', 8690.00, 'Recibido', 'salon', 'B° El Quebranto mz1 cs 35', '03704848212', 'Mostaza Nueva Córdoba (Bv. Illia 450)', 1, 'efectivo', 'Aprobado', 7900.00, 0.00, 0.00, NULL, 790.00, 0, 86, 8690.00, 0.00, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4),
(10, NULL, '2026-08-25 01:22:58', 23800.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 4, 'efectivo', 'Aprobado', 23800.00, 0.00, 0.00, NULL, 0.00, 0, 0, 30000.00, 6200.00, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(11, 4, '2026-08-25 08:14:52', 31345.00, 'Recibido', 'salon', 'B° El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 7, 'efectivo', 'Aprobado', 31800.00, 0.00, 5225.00, 'DIBU20', 4770.00, 225, 313, 31345.00, 0.00, '', '', NULL, NULL, 1, NULL, NULL, NULL, NULL, 5),
(12, 4, '2026-08-25 08:22:20', 7392.00, 'Recibido', 'salon', 'B° El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 13, 'efectivo', 'Aprobado', 6700.00, 0.00, 313.00, NULL, 1005.00, 313, 73, 7392.00, 0.00, '', '', NULL, NULL, 1, NULL, NULL, NULL, NULL, 6),
(13, 7, '2026-08-25 08:27:39', 17850.00, 'Recibido', 'salon', 'El Porvenir', '3704847532', 'Mostaza Palermo (Av. Santa Fe 3200)', 17, 'efectivo', 'Aprobado', 17000.00, 0.00, 0.00, NULL, 850.00, 0, 178, 17850.00, 0.00, '', '', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1),
(14, NULL, '2026-08-25 08:50:55', 9800.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 10, 'efectivo', 'Aprobado', 9800.00, 0.00, 0.00, NULL, 0.00, 0, 0, 20000.00, 10200.00, 'Venta POS Escritorio (SALON)', NULL, NULL, 13, 1, NULL, NULL, NULL, NULL, NULL),
(15, NULL, '2026-08-25 17:55:45', 18500.00, 'Recibido', 'delivery', 'Av. Santa Fe 3400, CABA', '1155443322', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 18500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 20000.00, 1500.00, 'Prueba Efectivo con Vuelto', NULL, NULL, NULL, 1, NULL, -34.58760000, -58.41230000, 'Piso 3 Depto A (timbre negro)', NULL),
(16, NULL, '2026-08-25 17:55:45', 24600.00, 'Recibido', 'delivery', 'Av. Callao 1450, CABA', '1166778899', 'Mostaza Central', NULL, 'transferencia', 'Aprobado', 24600.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, 'Prueba Transferencia', 'TRANSF-OP-98765432', NULL, NULL, 1, 'uploads/comprobantes/comp_test_123.jpg', -34.59320000, -58.39120000, NULL, NULL),
(17, NULL, '2026-08-25 17:55:45', 31200.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Palermo', NULL, 'tarjeta', 'Aprobado', 31200.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, 'Tarjeta en 3 cuota(s) · Auth: #9A8B7C6D', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(19, 4, '2026-08-25 19:46:10', 10000.00, 'Recibido', 'delivery', 'Av Santa Fe 1200, CABA', '1122334455', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 10000.00, 0.00, 0.00, NULL, 0.00, 0, 100, 10000.00, 0.00, 'Prueba final', NULL, NULL, NULL, 1, NULL, -34.60370000, -58.38150000, 'Piso 1', 7),
(20, 4, '2026-08-25 19:46:41', 22700.00, 'Recibido', 'delivery', 'Av. Santa Fe 3400', '1145678901', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 21500.00, 1200.00, 0.00, NULL, 0.00, 0, 215, 25000.00, 3500.00, 'Test checkout efectivo', NULL, NULL, NULL, 1, NULL, -34.58700000, -58.41200000, 'Piso 2 B', 8),
(21, 4, '2026-08-25 19:46:41', 20100.00, 'Recibido', 'delivery', 'Av. Callao 1500', '1145678901', 'Mostaza Central', NULL, 'transferencia', 'Aprobado', 18900.00, 1200.00, 0.00, NULL, 0.00, 0, 189, 18900.00, 0.00, 'Test checkout transferencia', 'TRF-123456', NULL, NULL, 1, NULL, -34.59300000, -58.39100000, 'Piso 2 B', 9),
(22, 4, '2026-08-25 19:46:41', 14500.00, 'Recibido', 'takeaway', '', '1145678901', 'Mostaza Palermo', NULL, 'mercadopago', 'Aprobado', 14500.00, 0.00, 0.00, NULL, 0.00, 0, 145, 14500.00, 0.00, 'Test checkout mercadopago', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 10),
(23, 4, '2026-08-25 19:46:41', 32000.00, 'Recibido', 'salon', '', '1145678901', 'Mostaza Central', 3, 'tarjeta', 'Aprobado', 32000.00, 0.00, 0.00, NULL, 0.00, 0, 320, 32000.00, 0.00, 'Test checkout tarjeta', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 11),
(24, 4, '2026-08-25 19:51:09', 3405.00, 'Recibido', 'delivery', 'Calle 263, Municipio de Formosa', '03704848212', 'Mostaza Central (Av. Corrientes 1250)', 1, 'tarjeta', 'Aprobado', 2100.00, 1200.00, 0.00, NULL, 105.00, 0, 34, NULL, NULL, 'Tarjeta en 1 cuota(s) · Auth: #705BE046', '', NULL, NULL, 1, NULL, -26.14742100, -58.14500800, '', 12),
(25, NULL, '2026-08-26 00:55:07', 7900.00, 'Recibido', 'salon', 'Entrega en Domicilio Mostaza', NULL, 'Mostaza Central', 8, 'mercadopago', 'Aprobado', 7900.00, 0.00, 0.00, NULL, 0.00, 0, 0, 7900.00, 0.00, 'Venta POS Escritorio (SALON)', NULL, NULL, 13, 1, NULL, NULL, NULL, NULL, NULL),
(26, 4, '2026-08-26 01:08:02', 22700.00, 'Recibido', 'delivery', 'Av. Santa Fe 3400', '1145678901', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 21500.00, 1200.00, 0.00, NULL, 0.00, 0, 215, 25000.00, 3500.00, 'Test checkout efectivo', NULL, NULL, NULL, 1, NULL, -34.58700000, -58.41200000, 'Piso 2 B', 13),
(27, 4, '2026-08-26 01:08:02', 20100.00, 'Recibido', 'delivery', 'Av. Callao 1500', '1145678901', 'Mostaza Central', NULL, 'transferencia', 'Aprobado', 18900.00, 1200.00, 0.00, NULL, 0.00, 0, 189, 18900.00, 0.00, 'Test checkout transferencia', 'TRF-123456', NULL, NULL, 1, NULL, -34.59300000, -58.39100000, 'Piso 2 B', 14),
(28, 4, '2026-08-26 01:08:02', 14500.00, 'Recibido', 'takeaway', '', '1145678901', 'Mostaza Palermo', NULL, 'mercadopago', 'Aprobado', 14500.00, 0.00, 0.00, NULL, 0.00, 0, 145, 14500.00, 0.00, 'Test checkout mercadopago', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 15),
(29, 4, '2026-08-26 01:08:02', 32000.00, 'Recibido', 'salon', '', '1145678901', 'Mostaza Central', 3, 'tarjeta', 'Aprobado', 32000.00, 0.00, 0.00, NULL, 0.00, 0, 320, 32000.00, 0.00, 'Test checkout tarjeta', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 16),
(30, 4, '2026-08-26 01:28:25', 11500.00, 'Recibido', 'delivery', 'Calle 263, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro (Av. 25 de Mayo 150)', 1, 'transferencia', 'Aprobado', 10300.00, 1200.00, 0.00, NULL, 0.00, 0, 115, NULL, NULL, '', '', NULL, NULL, 1, NULL, -26.14736200, -58.14477500, '', 17);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pieticket`
--

CREATE TABLE `pieticket` (
  `idpieTicket` int(11) NOT NULL,
  `fk_id_detalleMp` int(11) DEFAULT NULL,
  `vuelto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pieticket`
--

INSERT INTO `pieticket` (`idpieTicket`, `fk_id_detalleMp`, `vuelto`) VALUES
(1, 1, 150.00),
(2, 2, 0.00),
(3, 3, 320.50),
(4, 4, 80.00),
(5, 5, 0.00),
(6, 6, 45.25),
(7, 7, 200.00),
(8, 8, 15.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `idproducto` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(150) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`idproducto`, `nombre`, `descripcion`, `precio`, `imagen`, `categoria`, `activo`) VALUES
(1, 'Hambre de la Cuarta | Provolone y Bacon', 'Cuarto de libra con provolone gratinado y bacon crocante', 9800.00, 'cardex.jpg', 'exclusivos', 1),
(2, 'La Cuarta | Ahumada', 'Cuarto de libra con salsa ahumada de la casa', 9300.00, 'card1.jpg', 'exclusivos', 1),
(3, 'La Cuarta | Merlo', 'Cuarto de libra especial estilo Merlo', 9500.00, 'merlo.jpg', 'exclusivos', 1),
(4, 'Hambre de la Cuarta | Provolone y Bacon', 'Combo con papas y bebida', 11800.00, 'cardex.jpg', 'megacombos', 1),
(5, 'La Cuarta | Ahumada', 'Combo con papas y bebida', 11300.00, 'card1.jpg', 'megacombos', 1),
(6, 'La Cuarta | Merlo', 'Combo con papas y bebida', 11500.00, 'merlo.jpg', 'megacombos', 1),
(7, 'Doble Mega Big Bang Cheddar', 'Doble carne, doble cheddar, combo con papas y bebida', 12800.00, 'doblwcheddar.jpg', 'megacombos', 1),
(8, 'Doble Mega Boom', 'Doble carne con salsa boom, combo con papas y bebida', 12600.00, 'boom.jpg', 'megacombos', 1),
(9, 'Doble Cuarto a Fondo', 'Doble cuarto de libra a puro sabor, combo con papas y bebida', 13200.00, 'fondo.jpg', 'megacombos', 1),
(10, 'Doble Mega Taste', 'Doble carne receta Taste, combo con papas y bebida', 12400.00, 'taste1.jpg', 'megacombos', 1),
(11, 'Doble Mega Cuarto Bacon', 'Doble cuarto de libra con bacon, combo con papas y bebida', 12900.00, 'taste2.jpg', 'megacombos', 1),
(12, 'Doble Mega Cuarto', 'Doble cuarto de libra clásico, combo con papas y bebida', 12000.00, 'cuarto.jpg', 'megacombos', 1),
(13, 'Pollo Crispy Bacon', 'Pollo crocante con bacon y salsa especial', 8600.00, 'pollobacon.jpg', 'pollo', 1),
(14, 'Pollo Crispy', 'Clásico sandwich de pollo crocante', 7900.00, 'pollocrispy.jpg', 'pollo', 1),
(15, 'Pollo Crispy Cheddar', 'Pollo crocante con cheddar fundido', 8300.00, 'pollocheddar.jpg', 'pollo', 1),
(16, 'Nuggets de Pollo x6', '6 unidades de nuggets de pollo', 4800.00, 'x6.jpg', 'pollo', 1),
(17, 'Nuggets de Pollo x10', '10 unidades de nuggets de pollo', 6900.00, 'x10.jpg', 'pollo', 1),
(18, 'Nuggets Bacon & Cheddar x10', '10 nuggets rellenos de bacon y cheddar', 7900.00, 'nuggetachedar.jpg', 'pollo', 1),
(19, 'Balde de Nuggets', 'Balde grande para compartir', 12500.00, 'balde.jpg', 'pollo', 1),
(20, 'Ensalada Palta y Huevo Grill', 'Con pollo grillado, palta y huevo', 6800.00, 'ensaladapalta1.jpg', 'ensaladas', 1),
(21, 'Ensalada Palta y Huevo Veggie', 'Opción veggie con palta y huevo', 6500.00, 'ensaladapalta2.jpg', 'ensaladas', 1),
(22, 'Ensalada Caesar Crispy', 'Con pollo crocante, verdes y aderezo caesar', 6900.00, 'Caesar 2.jpg', 'ensaladas', 1),
(23, 'Ensalada Caesar Grill', 'Con pollo grillado, verdes y aderezo caesar', 6900.00, 'x10.jpg', 'ensaladas', 1),
(24, 'Wrap Cheddar & Pollo', 'Wrap de pollo con cheddar fundido', 6200.00, 'wrap.jpg', 'ensaladas', 1),
(25, 'Cero Veggie Cheese', 'Hamburguesa veggie con cheddar', 8200.00, 'card3.jpg', 'paravos', 1),
(26, 'Cero Veggie', 'Hamburguesa 100% veggie', 7800.00, 'cerovegiee.jpg', 'paravos', 1),
(27, 'Mega Cuarto Doble sin TACC', 'Opción apta celíacos', 10800.00, 'sintacc.jpg', 'paravos', 1),
(28, 'Cajita Mostaza Héroes Clásica', 'Hamburguesa chica + papas + juguete sorpresa', 5200.00, 'cajita1.jpg', 'cajitas', 1),
(29, 'Cajita Mostaza Héroes Nuggets', 'Nuggets + papas + juguete sorpresa', 5200.00, 'cajita2.jpg', 'cajitas', 1),
(30, 'Cajita Mostaza Héroes Cheese', 'Hamburguesa con cheddar + papas + juguete sorpresa', 5400.00, 'cajita3.jpg', 'cajitas', 1),
(31, 'Cajita Mostaza Héroes Doble', 'Doble hamburguesa chica + papas + juguete sorpresa', 5900.00, 'cajita4.jpg', 'cajitas', 1),
(32, 'Cajita Mostaza Héroes Deluxe', 'Hamburguesa + papas + postre + juguete sorpresa', 6300.00, 'cajita5.jpg', 'cajitas', 1),
(33, 'Tostado Queso & Jamón', 'Tostado clásico de jamón y queso', 3800.00, 'tostado.jpg', 'postres', 1),
(34, 'Tostado XL Queso & Jamón', 'Versión XL del tostado de jamón y queso', 4600.00, 'tostadoxl.jpg', 'postres', 1),
(35, 'Sandwich Huevo & Palta', 'Sandwich de huevo con palta', 4200.00, 'huevopalta.jpg', 'postres', 1),
(36, 'Sandwich Huevo & Bacon', 'Sandwich de huevo con bacon', 4400.00, 'huevobacon.jpg', 'postres', 1),
(37, 'Medialunas', 'Porción de medialunas', 2200.00, 'medialunas1.jpg', 'postres', 1),
(38, 'Medialunas Jamón & Queso', 'Medialunas rellenas de jamón y queso', 2800.00, 'medialunas2.jpg', 'postres', 1),
(39, 'Alfajor de Chocolate', 'Alfajor bañado en chocolate', 1800.00, 'alfajor.jpg', 'postres', 1),
(40, 'Budín de Banana', 'Porción de budín de banana casero', 2100.00, 'card7.jpg', 'postres', 1),
(41, 'Shake Capuccino Frozen', 'Shake helado sabor capuccino', 3600.00, 'shake.jpg', 'postres', 1),
(42, 'Cono ARG', 'Cono helado edición Argentina', 2500.00, 'Conoarg.jpg', 'postres', 1),
(43, 'Cono Clásico', 'Cono helado clásico', 2200.00, 'conoclasi.jpg', 'postres', 1),
(44, 'Hambre de la Cuarta | Provolone y Bacon', 'Cuarto de libra con provolone gratinado y bacon crocante', 9800.00, 'cardex.jpg', 'exclusivos', 1),
(45, 'La Cuarta | Ahumada', 'Cuarto de libra con salsa ahumada de la casa', 9300.00, 'card1.jpg', 'exclusivos', 1),
(46, 'La Cuarta | Merlo', 'Cuarto de libra especial estilo Merlo', 9500.00, 'merlo.jpg', 'exclusivos', 1),
(47, 'Hambre de la Cuarta | Provolone y Bacon', 'Combo con papas y bebida', 11800.00, 'cardex.jpg', 'megacombos', 1),
(48, 'La Cuarta | Ahumada', 'Combo con papas y bebida', 11300.00, 'card1.jpg', 'megacombos', 1),
(49, 'La Cuarta | Merlo', 'Combo con papas y bebida', 11500.00, 'merlo.jpg', 'megacombos', 1),
(50, 'Doble Mega Big Bang Cheddar', 'Doble carne, doble cheddar, combo con papas y bebida', 12800.00, 'doblwcheddar.jpg', 'megacombos', 1),
(51, 'Doble Mega Boom', 'Doble carne con salsa boom, combo con papas y bebida', 12600.00, 'boom.jpg', 'megacombos', 1),
(52, 'Doble Cuarto a Fondo', 'Doble cuarto de libra a puro sabor, combo con papas y bebida', 13200.00, 'fondo.jpg', 'megacombos', 1),
(53, 'Doble Mega Taste', 'Doble carne receta Taste, combo con papas y bebida', 12400.00, 'taste1.jpg', 'megacombos', 1),
(54, 'Doble Mega Cuarto Bacon', 'Doble cuarto de libra con bacon, combo con papas y bebida', 12900.00, 'taste2.jpg', 'megacombos', 1),
(55, 'Doble Mega Cuarto', 'Doble cuarto de libra clásico, combo con papas y bebida', 12000.00, 'cuarto.jpg', 'megacombos', 1),
(56, 'Pollo Crispy Bacon', 'Pollo crocante con bacon y salsa especial', 8600.00, 'pollobacon.jpg', 'pollo', 1),
(57, 'Pollo Crispy', 'Clásico sandwich de pollo crocante', 7900.00, 'pollocrispy.jpg', 'pollo', 1),
(58, 'Pollo Crispy Cheddar', 'Pollo crocante con cheddar fundido', 8300.00, 'pollocheddar.jpg', 'pollo', 1),
(59, 'Nuggets de Pollo x6', '6 unidades de nuggets de pollo', 4800.00, 'x6.jpg', 'pollo', 1),
(60, 'Nuggets de Pollo x10', '10 unidades de nuggets de pollo', 6900.00, 'x10.jpg', 'pollo', 1),
(61, 'Nuggets Bacon & Cheddar x10', '10 nuggets rellenos de bacon y cheddar', 7900.00, 'nuggetachedar.jpg', 'pollo', 1),
(62, 'Balde de Nuggets', 'Balde grande para compartir', 12500.00, 'balde.jpg', 'pollo', 1),
(63, 'Ensalada Palta y Huevo Grill', 'Con pollo grillado, palta y huevo', 6800.00, 'ensaladapalta1.jpg', 'ensaladas', 1),
(64, 'Ensalada Palta y Huevo Veggie', 'Opción veggie con palta y huevo', 6500.00, 'ensaladapalta2.jpg', 'ensaladas', 1),
(65, 'Ensalada Caesar Crispy', 'Con pollo crocante, verdes y aderezo caesar', 6900.00, 'Caesar 2.jpg', 'ensaladas', 1),
(66, 'Ensalada Caesar Grill', 'Con pollo grillado, verdes y aderezo caesar', 6900.00, 'x10.jpg', 'ensaladas', 1),
(67, 'Wrap Cheddar & Pollo', 'Wrap de pollo con cheddar fundido', 6200.00, 'wrap.jpg', 'ensaladas', 1),
(68, 'Cero Veggie Cheese', 'Hamburguesa veggie con cheddar', 8200.00, 'card3.jpg', 'paravos', 1),
(69, 'Cero Veggie', 'Hamburguesa 100% veggie', 7800.00, 'cerovegiee.jpg', 'paravos', 1),
(70, 'Mega Cuarto Doble sin TACC', 'Opción apta celíacos', 10800.00, 'sintacc.jpg', 'paravos', 1),
(71, 'Cajita Mostaza Héroes Clásica', 'Hamburguesa chica + papas + juguete sorpresa', 5200.00, 'cajita1.jpg', 'cajitas', 1),
(72, 'Cajita Mostaza Héroes Nuggets', 'Nuggets + papas + juguete sorpresa', 5200.00, 'cajita2.jpg', 'cajitas', 1),
(73, 'Cajita Mostaza Héroes Cheese', 'Hamburguesa con cheddar + papas + juguete sorpresa', 5400.00, 'cajita3.jpg', 'cajitas', 1),
(74, 'Cajita Mostaza Héroes Doble', 'Doble hamburguesa chica + papas + juguete sorpresa', 5900.00, 'cajita4.jpg', 'cajitas', 1),
(75, 'Cajita Mostaza Héroes Deluxe', 'Hamburguesa + papas + postre + juguete sorpresa', 6300.00, 'cajita5.jpg', 'cajitas', 1),
(76, 'Tostado Queso & Jamón', 'Tostado clásico de jamón y queso', 3800.00, 'tostado.jpg', 'postres', 1),
(77, 'Tostado XL Queso & Jamón', 'Versión XL del tostado de jamón y queso', 4600.00, 'tostadoxl.jpg', 'postres', 1),
(78, 'Sandwich Huevo & Palta', 'Sandwich de huevo con palta', 4200.00, 'huevopalta.jpg', 'postres', 1),
(79, 'Sandwich Huevo & Bacon', 'Sandwich de huevo con bacon', 4400.00, 'huevobacon.jpg', 'postres', 1),
(80, 'Medialunas', 'Porción de medialunas', 2200.00, 'medialunas1.jpg', 'postres', 1),
(81, 'Medialunas Jamón & Queso', 'Medialunas rellenas de jamón y queso', 2800.00, 'medialunas2.jpg', 'postres', 1),
(82, 'Alfajor de Chocolate', 'Alfajor bañado en chocolate', 1800.00, 'alfajor.jpg', 'postres', 1),
(83, 'Budín de Banana', 'Porción de budín de banana casero', 2100.00, 'card7.jpg', 'postres', 1),
(84, 'Shake Capuccino Frozen', 'Shake helado sabor capuccino', 3600.00, 'shake.jpg', 'postres', 1),
(85, 'Cono ARG', 'Cono helado edición Argentina', 2500.00, 'Conoarg.jpg', 'postres', 1),
(86, 'Cono Clásico', 'Cono helado clásico', 2200.00, 'conoclasi.jpg', 'postres', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `idproveedor` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fk_id_telefono` int(11) DEFAULT NULL,
  `fk_id_email` int(11) DEFAULT NULL,
  `fk_id_direccion` int(11) DEFAULT NULL,
  `rubro` varchar(50) DEFAULT NULL,
  `ultima_entrega` date DEFAULT NULL,
  `estado_entrega` varchar(20) NOT NULL DEFAULT 'Al día'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`idproveedor`, `nombre`, `fk_id_telefono`, `fk_id_email`, `fk_id_direccion`, `rubro`, `ultima_entrega`, `estado_entrega`) VALUES
(1, 'Frigorífico El Norteño', 9, 12, 8, 'Cárnicos', '2026-08-01', 'Demorado'),
(2, 'Panificadora San José', 10, 13, 9, 'Panadería', '2026-08-05', 'Al día'),
(3, 'Distribuidora de Bebidas del Sur', 11, 14, 5, 'Bebidas', '2026-07-28', 'Atrasado'),
(4, 'Verdulería Central', 12, 15, 6, 'Verdulería', '2026-08-04', 'Al día'),
(5, 'Packaging Express SRL', 13, 1, 3, 'Packaging', '2026-08-03', 'Al día');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_turno_empleado`
--

CREATE TABLE `registro_turno_empleado` (
  `idregistro` int(11) NOT NULL,
  `fk_id_empleado` int(11) NOT NULL,
  `fk_id_turno` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` datetime NOT NULL,
  `hora_salida` datetime DEFAULT NULL,
  `horas_trabajadas` decimal(5,2) DEFAULT NULL,
  `estado` enum('Activo','Finalizado') NOT NULL DEFAULT 'Activo',
  `observaciones` varchar(255) DEFAULT NULL,
  `origen` varchar(50) DEFAULT 'App Cajero'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registro_turno_empleado`
--

INSERT INTO `registro_turno_empleado` (`idregistro`, `fk_id_empleado`, `fk_id_turno`, `fecha`, `hora_entrada`, `hora_salida`, `horas_trabajadas`, `estado`, `observaciones`, `origen`) VALUES
(1, 13, 1, '2026-08-24', '2026-08-24 10:36:44', '2026-08-25 08:49:13', 22.21, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cajero'),
(2, 13, 1, '2026-08-25', '2026-08-25 08:49:13', '2026-08-25 08:57:30', 0.14, 'Finalizado', 'Turno finalizado desde App Cajero', 'App Cajero'),
(3, 13, 1, '2026-08-26', '2026-08-26 00:52:16', '2026-08-26 00:55:41', 0.06, 'Finalizado', 'Turno finalizado desde App Cajero', 'App Cajero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resumenticket`
--

CREATE TABLE `resumenticket` (
  `idresumenTicket` int(11) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT NULL,
  `descuento` decimal(10,2) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `resumenticket`
--

INSERT INTO `resumenticket` (`idresumenTicket`, `subtotal`, `descuento`, `total`) VALUES
(1, 14000.00, 0.00, 14000.00),
(2, 7400.00, 740.00, 6660.00),
(3, 20100.00, 0.00, 20100.00),
(4, 3200.00, 0.00, 3200.00),
(5, 13000.00, 1300.00, 11700.00),
(6, 6000.00, 0.00, 6000.00),
(7, 17000.00, 0.00, 17000.00),
(8, 14200.00, 1420.00, 12780.00),
(9, 5800.00, 0.00, 5800.00),
(10, 6700.00, 0.00, 6700.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `idrol` int(11) NOT NULL,
  `rol` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`idrol`, `rol`) VALUES
(1, 'Cliente'),
(2, 'Cajero'),
(3, 'Cocinero'),
(4, 'Mesero'),
(5, 'Administrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion_mesa`
--

CREATE TABLE `seccion_mesa` (
  `idseccion` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'fa-chair',
  `orden` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `seccion_mesa`
--

INSERT INTO `seccion_mesa` (`idseccion`, `nombre`, `descripcion`, `icono`, `orden`) VALUES
(1, 'Salón Principal', 'Área central del restaurante frente al mostrador', 'fa-utensils', 1),
(2, 'Terraza Exterior', 'Mesas al aire libre con vista a la avenida', 'fa-sun', 2),
(3, 'Planta Alta / VIP', 'Zona exclusiva y climatizada en primer piso', 'fa-crown', 3),
(4, 'Barra & Boxes', 'Boxes privados y sillas de barra alta', 'fa-martini-glass-citrus', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stock_insumo`
--

CREATE TABLE `stock_insumo` (
  `idinsumo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'Ingrediente',
  `unidad_medida` varchar(20) NOT NULL DEFAULT 'unidades',
  `stock_actual` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT 10.00,
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `proveedor` varchar(100) DEFAULT 'Proveedor General',
  `ultima_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `stock_insumo`
--

INSERT INTO `stock_insumo` (`idinsumo`, `nombre`, `categoria`, `unidad_medida`, `stock_actual`, `stock_minimo`, `costo_unitario`, `proveedor`, `ultima_actualizacion`) VALUES
(1, 'Medallón de Carne Vacuna 120g', 'Carne', 'unidades', 323.00, 50.00, 950.00, 'Frigorífico El Norteño', '2026-08-26 01:28:25'),
(2, 'Pechuga de Pollo Crispy', 'Carne', 'unidades', 180.00, 30.00, 820.00, 'Distribuidora Avícola', '2026-08-16 18:46:24'),
(3, 'Pan de Hamburguesa con Sésamo', 'Panadería', 'unidades', 393.00, 80.00, 150.00, 'Panificadora San José', '2026-08-26 01:28:25'),
(4, 'Queso Cheddar en Fetas', 'Lácteos', 'kg', 45.00, 10.00, 3200.00, 'Lácteos La Serenísima', '2026-08-16 18:46:24'),
(5, 'Panceta / Bacon Ahumado', 'Fiambrería', 'kg', 28.00, 8.00, 4100.00, 'Frigorífico El Norteño', '2026-08-16 18:46:24'),
(6, 'Papas para Fritas McCain', 'Guarnición', 'kg', 120.00, 25.00, 750.00, 'Distribuidora Central', '2026-08-16 18:46:24'),
(7, 'Lechuga Repollada Fresca', 'Verdulería', 'kg', 22.00, 5.00, 600.00, 'Verdulería Central', '2026-08-16 18:46:24'),
(8, 'Tomates Redondos Seleccionados', 'Verdulería', 'kg', 26.00, 6.00, 850.00, 'Verdulería Central', '2026-08-16 18:46:24'),
(9, 'Cebolla Morada', 'Verdulería', 'kg', 18.00, 5.00, 500.00, 'Verdulería Central', '2026-08-16 18:46:24'),
(10, 'Salsa Especial Mostaza', 'Aderezos', 'litros', 35.00, 8.00, 1800.00, 'Salsas Argentinas SA', '2026-08-16 18:46:24'),
(11, 'Gaseosa Coca-Cola 500ml', 'Bebidas', 'unidades', 252.00, 40.00, 700.00, 'Distribuidora del Sur', '2026-08-26 00:55:07'),
(12, 'Caja / Packaging Mostaza', 'Descartables', 'unidades', 581.00, 100.00, 85.00, 'Packaging Express SRL', '2026-08-26 01:28:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `idsucursal` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fk_id_direccion` int(11) DEFAULT NULL,
  `fk_id_ciudad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`idsucursal`, `nombre`, `fk_id_direccion`, `fk_id_ciudad`) VALUES
(1, 'Mostaza Palermo', 1, 1),
(2, 'Mostaza Nueva Córdoba', 3, 2),
(3, 'Mostaza Rosario Centro', 4, 3),
(4, 'Mostaza Mendoza Centro', 5, 4),
(5, 'Mostaza Formosa Centro', 6, 5),
(6, 'Mostaza Resistencia', 8, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

CREATE TABLE `telefono` (
  `idtelefono` int(11) NOT NULL,
  `codigo_de_area` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `telefono`
--

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket`
--

CREATE TABLE `ticket` (
  `idticket` int(11) NOT NULL,
  `fk_id_encabezadoTicket` int(11) DEFAULT NULL,
  `fechaHora` datetime DEFAULT NULL,
  `fk_id_caja` int(11) DEFAULT NULL,
  `fk_id_empleado` int(11) DEFAULT NULL,
  `fk_id_detalleTicket` int(11) DEFAULT NULL,
  `fk_id_resumenTicket` int(11) DEFAULT NULL,
  `fk_id_pieTicket` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ticket`
--

INSERT INTO `ticket` (`idticket`, `fk_id_encabezadoTicket`, `fechaHora`, `fk_id_caja`, `fk_id_empleado`, `fk_id_detalleTicket`, `fk_id_resumenTicket`, `fk_id_pieTicket`) VALUES
(1, 1, '2026-07-20 12:15:00', 1, 7, 1, 1, 1),
(2, 2, '2026-07-20 13:40:00', 2, 8, 2, 2, 2),
(3, 3, '2026-07-21 19:05:00', 3, 9, 3, 3, 3),
(4, 4, '2026-07-21 20:30:00', 4, 10, 4, 4, 4),
(5, 5, '2026-07-22 12:50:00', 5, 11, 5, 5, 5),
(6, 6, '2026-07-22 21:10:00', 6, 12, 6, 6, 6),
(7, 7, '2026-07-23 14:20:00', 1, 7, 7, 7, 7),
(8, 8, '2026-07-24 18:00:00', 3, 9, 8, 8, 8),
(9, 1, '2026-07-25 13:10:00', 1, 7, 9, 9, 1),
(10, 2, '2026-07-26 20:45:00', 2, 8, 10, 10, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `idturnos` int(11) NOT NULL,
  `horaInicio` time DEFAULT NULL,
  `horaFin` time DEFAULT NULL,
  `nombre` varchar(30) DEFAULT NULL,
  `dias` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`idturnos`, `horaInicio`, `horaFin`, `nombre`, `dias`) VALUES
(1, '06:00:00', '14:00:00', 'Turno Mañana/Apertura', 'Lun a Vie'),
(2, '14:00:00', '22:00:00', 'Turno Tarde', 'Mar a Sáb'),
(3, '22:00:00', '06:00:00', 'Turno Noche/Cierre', 'Jue a Dom');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_web`
--

CREATE TABLE `usuario_web` (
  `idusuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `token` varchar(10) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `token_intentos` int(11) NOT NULL DEFAULT 0,
  `verificado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `puntos` int(11) NOT NULL DEFAULT 0,
  `direccion_favorita` varchar(200) DEFAULT NULL,
  `telefono_favorito` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_web`
--

INSERT INTO `usuario_web` (`idusuario`, `nombre`, `apellido`, `telefono`, `direccion`, `email`, `password`, `token`, `token_expira`, `token_intentos`, `verificado`, `fecha_registro`, `puntos`, `direccion_favorita`, `telefono_favorito`) VALUES
(4, 'Betosixseven67', 'Gómez', '3704848212', 'B° El Quebranto mz1 cs 35', 'applegomez64@gmail.com', '$2y$10$j56Ycz5rQS2IgkDbEWTRN.P4b1ODz2r/qhfIaLpuR/QhGyKTEnPq.', NULL, NULL, 0, 1, '2026-07-31 08:21:16', 222, 'B° El Quebranto mz1 cs 35', '3704848212'),
(5, 'Vicky', 'Marroco', '3705148612', 'Clorinda', 'victoriamarrocco95@gmail.com', '$2y$10$KgPxL42VFvKMooOqUaH1buarO.fz0/PbJoQBSP.Zi9I.btFLNLWDS', NULL, NULL, 0, 1, '2026-07-31 08:24:31', 0, NULL, NULL),
(6, 'Mirian', 'Gómez', '03704232251', 'B° El Quebranto mz1 cs 35', 'mirianeliza991@gmail.com', '', NULL, NULL, 0, 1, '2026-08-16 20:23:47', 0, NULL, NULL),
(7, 'Camilasixseven', 'Riquelme', '3704847532', 'El Porvenir', 'cmilasanches@gmail.com', '', NULL, NULL, 0, 1, '2026-08-25 08:25:27', 178, 'El Porvenir', '3704847532');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`idadmin`),
  ADD UNIQUE KEY `usuario_unico` (`usuario`);

--
-- Indices de la tabla `barrio`
--
ALTER TABLE `barrio`
  ADD PRIMARY KEY (`idbarrio`);

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`idcaja`);

--
-- Indices de la tabla `caja_arqueo`
--
ALTER TABLE `caja_arqueo`
  ADD PRIMARY KEY (`idarqueo`);

--
-- Indices de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  ADD PRIMARY KEY (`idciudad`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idcliente`),
  ADD KEY `fk_id_telefono` (`fk_id_telefono`),
  ADD KEY `fk_id_direccion` (`fk_id_direccion`),
  ADD KEY `fk_id_rol` (`fk_id_rol`),
  ADD KEY `fk_id_email` (`fk_id_email`),
  ADD KEY `fk_id_genero` (`fk_id_genero`);

--
-- Indices de la tabla `comida`
--
ALTER TABLE `comida`
  ADD PRIMARY KEY (`idcomida`),
  ADD KEY `fk_id_proveedor` (`fk_id_proveedor`);

--
-- Indices de la tabla `cupon`
--
ALTER TABLE `cupon`
  ADD PRIMARY KEY (`idcupon`),
  ADD UNIQUE KEY `codigo_unico` (`codigo`);

--
-- Indices de la tabla `detallemp`
--
ALTER TABLE `detallemp`
  ADD PRIMARY KEY (`iddetalleMp`),
  ADD KEY `fk_id_mp` (`fk_id_mp`);

--
-- Indices de la tabla `detalleticket`
--
ALTER TABLE `detalleticket`
  ADD PRIMARY KEY (`iddetalleTicket`),
  ADD KEY `fk_id_comida` (`fk_id_comida`);

--
-- Indices de la tabla `detalle_factura`
--
ALTER TABLE `detalle_factura`
  ADD PRIMARY KEY (`iddetalle_factura`),
  ADD KEY `idx_det_factura` (`idfactura`);

--
-- Indices de la tabla `detalle_pedido_web`
--
ALTER TABLE `detalle_pedido_web`
  ADD PRIMARY KEY (`iddetalle`),
  ADD KEY `idx_pedido` (`idpedido`),
  ADD KEY `fk_detalleweb_producto` (`idproducto`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`iddireccion`),
  ADD KEY `fk_id_barrio` (`fk_id_barrio`),
  ADD KEY `fk_id_ciudad` (`fk_id_ciudad`);

--
-- Indices de la tabla `email`
--
ALTER TABLE `email`
  ADD PRIMARY KEY (`idemail`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`idempleado`),
  ADD KEY `fk_id_telefono` (`fk_id_telefono`),
  ADD KEY `fk_id_email` (`fk_id_email`),
  ADD KEY `fk_id_rol` (`fk_id_rol`),
  ADD KEY `fk_id_genero` (`fk_id_genero`),
  ADD KEY `fk_id_turno` (`fk_id_turno`);

--
-- Indices de la tabla `encabezadoticket`
--
ALTER TABLE `encabezadoticket`
  ADD PRIMARY KEY (`idencabezadoTicket`),
  ADD KEY `fk_id_sucursal` (`fk_id_sucursal`),
  ADD KEY `fk_id_telefono` (`fk_id_telefono`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`idfactura`),
  ADD UNIQUE KEY `num_factura_unico` (`numero_factura`),
  ADD KEY `idx_factura_pedido` (`idpedido`),
  ADD KEY `idx_factura_caja` (`idarqueo`),
  ADD KEY `idx_factura_empleado` (`idempleado`);

--
-- Indices de la tabla `genero`
--
ALTER TABLE `genero`
  ADD PRIMARY KEY (`idgenero`);

--
-- Indices de la tabla `insumo`
--
ALTER TABLE `insumo`
  ADD PRIMARY KEY (`idinsumo`),
  ADD KEY `fk_id_proveedor` (`fk_id_proveedor`);

--
-- Indices de la tabla `insumopedido`
--
ALTER TABLE `insumopedido`
  ADD PRIMARY KEY (`idinsumoPedido`),
  ADD KEY `fk_id_insumo` (`fk_id_insumo`);

--
-- Indices de la tabla `mesa`
--
ALTER TABLE `mesa`
  ADD PRIMARY KEY (`idmesa`),
  ADD UNIQUE KEY `num_mesa_unico` (`numero_mesa`),
  ADD KEY `fk_mesa_seccion` (`fk_id_seccion`);

--
-- Indices de la tabla `mp`
--
ALTER TABLE `mp`
  ADD PRIMARY KEY (`idmp`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`idpedido`);

--
-- Indices de la tabla `pedido_web`
--
ALTER TABLE `pedido_web`
  ADD PRIMARY KEY (`idpedido`),
  ADD KEY `idx_usuario` (`idusuario`);

--
-- Indices de la tabla `pieticket`
--
ALTER TABLE `pieticket`
  ADD PRIMARY KEY (`idpieTicket`),
  ADD KEY `fk_id_detalleMp` (`fk_id_detalleMp`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`idproducto`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`idproveedor`),
  ADD KEY `fk_id_telefono` (`fk_id_telefono`),
  ADD KEY `fk_id_email` (`fk_id_email`),
  ADD KEY `fk_id_direccion` (`fk_id_direccion`);

--
-- Indices de la tabla `registro_turno_empleado`
--
ALTER TABLE `registro_turno_empleado`
  ADD PRIMARY KEY (`idregistro`),
  ADD KEY `fk_registro_empleado` (`fk_id_empleado`),
  ADD KEY `fk_registro_turno` (`fk_id_turno`);

--
-- Indices de la tabla `resumenticket`
--
ALTER TABLE `resumenticket`
  ADD PRIMARY KEY (`idresumenTicket`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`idrol`);

--
-- Indices de la tabla `seccion_mesa`
--
ALTER TABLE `seccion_mesa`
  ADD PRIMARY KEY (`idseccion`),
  ADD UNIQUE KEY `nombre_seccion_unico` (`nombre`);

--
-- Indices de la tabla `stock_insumo`
--
ALTER TABLE `stock_insumo`
  ADD PRIMARY KEY (`idinsumo`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`idsucursal`),
  ADD KEY `fk_id_direccion` (`fk_id_direccion`),
  ADD KEY `fk_id_ciudad` (`fk_id_ciudad`);

--
-- Indices de la tabla `telefono`
--
ALTER TABLE `telefono`
  ADD PRIMARY KEY (`idtelefono`);

--
-- Indices de la tabla `ticket`
--
ALTER TABLE `ticket`
  ADD PRIMARY KEY (`idticket`),
  ADD KEY `fk_id_encabezadoTicket` (`fk_id_encabezadoTicket`),
  ADD KEY `fk_id_caja` (`fk_id_caja`),
  ADD KEY `fk_id_empleado` (`fk_id_empleado`),
  ADD KEY `fk_id_detalleTicket` (`fk_id_detalleTicket`),
  ADD KEY `fk_id_resumenTicket` (`fk_id_resumenTicket`),
  ADD KEY `fk_id_pieTicket` (`fk_id_pieTicket`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`idturnos`);

--
-- Indices de la tabla `usuario_web`
--
ALTER TABLE `usuario_web`
  ADD PRIMARY KEY (`idusuario`),
  ADD UNIQUE KEY `email_unico` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `idadmin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `barrio`
--
ALTER TABLE `barrio`
  MODIFY `idbarrio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `idcaja` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `caja_arqueo`
--
ALTER TABLE `caja_arqueo`
  MODIFY `idarqueo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  MODIFY `idciudad` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idcliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `comida`
--
ALTER TABLE `comida`
  MODIFY `idcomida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `cupon`
--
ALTER TABLE `cupon`
  MODIFY `idcupon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detallemp`
--
ALTER TABLE `detallemp`
  MODIFY `iddetalleMp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `detalleticket`
--
ALTER TABLE `detalleticket`
  MODIFY `iddetalleTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `detalle_factura`
--
ALTER TABLE `detalle_factura`
  MODIFY `iddetalle_factura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido_web`
--
ALTER TABLE `detalle_pedido_web`
  MODIFY `iddetalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `iddireccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `email`
--
ALTER TABLE `email`
  MODIFY `idemail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `idempleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `encabezadoticket`
--
ALTER TABLE `encabezadoticket`
  MODIFY `idencabezadoTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `idfactura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `genero`
--
ALTER TABLE `genero`
  MODIFY `idgenero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `insumo`
--
ALTER TABLE `insumo`
  MODIFY `idinsumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `insumopedido`
--
ALTER TABLE `insumopedido`
  MODIFY `idinsumoPedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `mesa`
--
ALTER TABLE `mesa`
  MODIFY `idmesa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT de la tabla `mp`
--
ALTER TABLE `mp`
  MODIFY `idmp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `idpedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `pedido_web`
--
ALTER TABLE `pedido_web`
  MODIFY `idpedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `pieticket`
--
ALTER TABLE `pieticket`
  MODIFY `idpieTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `idproducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `idproveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `registro_turno_empleado`
--
ALTER TABLE `registro_turno_empleado`
  MODIFY `idregistro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `resumenticket`
--
ALTER TABLE `resumenticket`
  MODIFY `idresumenTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `idrol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `seccion_mesa`
--
ALTER TABLE `seccion_mesa`
  MODIFY `idseccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `stock_insumo`
--
ALTER TABLE `stock_insumo`
  MODIFY `idinsumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `idsucursal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `telefono`
--
ALTER TABLE `telefono`
  MODIFY `idtelefono` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `ticket`
--
ALTER TABLE `ticket`
  MODIFY `idticket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `idturnos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario_web`
--
ALTER TABLE `usuario_web`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`),
  ADD CONSTRAINT `cliente_ibfk_2` FOREIGN KEY (`fk_id_direccion`) REFERENCES `direccion` (`iddireccion`),
  ADD CONSTRAINT `cliente_ibfk_3` FOREIGN KEY (`fk_id_rol`) REFERENCES `rol` (`idrol`),
  ADD CONSTRAINT `cliente_ibfk_4` FOREIGN KEY (`fk_id_email`) REFERENCES `email` (`idemail`),
  ADD CONSTRAINT `cliente_ibfk_5` FOREIGN KEY (`fk_id_genero`) REFERENCES `genero` (`idgenero`);

--
-- Filtros para la tabla `comida`
--
ALTER TABLE `comida`
  ADD CONSTRAINT `comida_ibfk_1` FOREIGN KEY (`fk_id_proveedor`) REFERENCES `proveedor` (`idproveedor`);

--
-- Filtros para la tabla `detallemp`
--
ALTER TABLE `detallemp`
  ADD CONSTRAINT `detallemp_ibfk_1` FOREIGN KEY (`fk_id_mp`) REFERENCES `mp` (`idmp`);

--
-- Filtros para la tabla `detalleticket`
--
ALTER TABLE `detalleticket`
  ADD CONSTRAINT `detalleticket_ibfk_1` FOREIGN KEY (`fk_id_comida`) REFERENCES `comida` (`idcomida`);

--
-- Filtros para la tabla `detalle_pedido_web`
--
ALTER TABLE `detalle_pedido_web`
  ADD CONSTRAINT `fk_detalleweb_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido_web` (`idpedido`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalleweb_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`);

--
-- Filtros para la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD CONSTRAINT `direccion_ibfk_1` FOREIGN KEY (`fk_id_barrio`) REFERENCES `barrio` (`idbarrio`),
  ADD CONSTRAINT `direccion_ibfk_2` FOREIGN KEY (`fk_id_ciudad`) REFERENCES `ciudad` (`idciudad`);

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`),
  ADD CONSTRAINT `empleado_ibfk_2` FOREIGN KEY (`fk_id_email`) REFERENCES `email` (`idemail`),
  ADD CONSTRAINT `empleado_ibfk_3` FOREIGN KEY (`fk_id_rol`) REFERENCES `rol` (`idrol`),
  ADD CONSTRAINT `empleado_ibfk_4` FOREIGN KEY (`fk_id_genero`) REFERENCES `genero` (`idgenero`),
  ADD CONSTRAINT `empleado_ibfk_5` FOREIGN KEY (`fk_id_turno`) REFERENCES `turnos` (`idturnos`);

--
-- Filtros para la tabla `encabezadoticket`
--
ALTER TABLE `encabezadoticket`
  ADD CONSTRAINT `encabezadoticket_ibfk_1` FOREIGN KEY (`fk_id_sucursal`) REFERENCES `sucursal` (`idsucursal`),
  ADD CONSTRAINT `encabezadoticket_ibfk_2` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`);

--
-- Filtros para la tabla `insumo`
--
ALTER TABLE `insumo`
  ADD CONSTRAINT `insumo_ibfk_1` FOREIGN KEY (`fk_id_proveedor`) REFERENCES `proveedor` (`idproveedor`);

--
-- Filtros para la tabla `insumopedido`
--
ALTER TABLE `insumopedido`
  ADD CONSTRAINT `insumopedido_ibfk_1` FOREIGN KEY (`fk_id_insumo`) REFERENCES `insumo` (`idinsumo`);

--
-- Filtros para la tabla `pedido_web`
--
ALTER TABLE `pedido_web`
  ADD CONSTRAINT `fk_pedidoweb_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario_web` (`idusuario`);

--
-- Filtros para la tabla `pieticket`
--
ALTER TABLE `pieticket`
  ADD CONSTRAINT `pieticket_ibfk_1` FOREIGN KEY (`fk_id_detalleMp`) REFERENCES `detallemp` (`iddetalleMp`);

--
-- Filtros para la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD CONSTRAINT `proveedor_ibfk_1` FOREIGN KEY (`fk_id_telefono`) REFERENCES `telefono` (`idtelefono`),
  ADD CONSTRAINT `proveedor_ibfk_2` FOREIGN KEY (`fk_id_email`) REFERENCES `email` (`idemail`),
  ADD CONSTRAINT `proveedor_ibfk_3` FOREIGN KEY (`fk_id_direccion`) REFERENCES `direccion` (`iddireccion`);

--
-- Filtros para la tabla `registro_turno_empleado`
--
ALTER TABLE `registro_turno_empleado`
  ADD CONSTRAINT `fk_reg_empleado` FOREIGN KEY (`fk_id_empleado`) REFERENCES `empleado` (`idempleado`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reg_turno` FOREIGN KEY (`fk_id_turno`) REFERENCES `turnos` (`idturnos`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD CONSTRAINT `sucursal_ibfk_1` FOREIGN KEY (`fk_id_direccion`) REFERENCES `direccion` (`iddireccion`),
  ADD CONSTRAINT `sucursal_ibfk_2` FOREIGN KEY (`fk_id_ciudad`) REFERENCES `ciudad` (`idciudad`);

--
-- Filtros para la tabla `ticket`
--
ALTER TABLE `ticket`
  ADD CONSTRAINT `ticket_ibfk_1` FOREIGN KEY (`fk_id_encabezadoTicket`) REFERENCES `encabezadoticket` (`idencabezadoTicket`),
  ADD CONSTRAINT `ticket_ibfk_2` FOREIGN KEY (`fk_id_caja`) REFERENCES `caja` (`idcaja`),
  ADD CONSTRAINT `ticket_ibfk_3` FOREIGN KEY (`fk_id_empleado`) REFERENCES `empleado` (`idempleado`),
  ADD CONSTRAINT `ticket_ibfk_4` FOREIGN KEY (`fk_id_detalleTicket`) REFERENCES `detalleticket` (`iddetalleTicket`),
  ADD CONSTRAINT `ticket_ibfk_5` FOREIGN KEY (`fk_id_resumenTicket`) REFERENCES `resumenticket` (`idresumenTicket`),
  ADD CONSTRAINT `ticket_ibfk_6` FOREIGN KEY (`fk_id_pieTicket`) REFERENCES `pieticket` (`idpieTicket`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
