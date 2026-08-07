-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-08-2026 a las 13:54:29
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
-- Estructura de tabla para la tabla `detalle_pedido_web`
--

CREATE TABLE `detalle_pedido_web` (
  `iddetalle` int(11) NOT NULL,
  `idpedido` int(11) NOT NULL,
  `idproducto` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_pedido_web`
--

INSERT INTO `detalle_pedido_web` (`iddetalle`, `idpedido`, `idproducto`, `descripcion`, `precio_unitario`, `cantidad`, `subtotal`) VALUES
(3, 2, 4, 'Hambre de la Cuarta | Provolone y Bacon', 11800.00, 1, 11800.00),
(4, 3, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Papas Fritas', 10000.00, 1, 10000.00),
(5, 3, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Papas Fritas', 10000.00, 1, 10000.00),
(6, 3, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Huevo, Tocino, Cebolla, Nuggets de Pollo', 8800.00, 1, 8800.00),
(7, 3, 5, 'La Cuarta | Ahumada', 11300.00, 1, 11300.00),
(8, 4, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00);

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
(15, 'proveedor.verduras@verduleriacentral.com.ar');

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
  `usuario` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `dni`, `fk_id_telefono`, `fk_id_email`, `fk_id_rol`, `fk_id_genero`, `fk_id_turno`, `usuario`, `password`, `activo`) VALUES
(1, 'Federico', 'Álvarez', '28111000', 11, 2, 4, 1, 1, 'falvarez', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(2, 'Romina', 'Castro', '29222111', 12, 3, 4, 2, 2, 'rcastro', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(3, 'Ezequiel', 'Molina', '30333222', 13, 4, 4, 1, 1, 'emolina', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(4, 'Carla', 'Suárez', '31444333', 14, 5, 4, 2, 2, 'csuarez', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(5, 'Bruno', 'Acosta', '32555444', 15, 6, 4, 1, 3, 'bacosta', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(6, 'Milagros', 'Rojas', '33666555', 1, 7, 4, 2, 1, 'mrojas', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(7, 'Tomás', 'Ibáñez', '34777666', 2, 2, 3, 1, 1, 'tibanez', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(8, 'Julieta', 'Herrera', '35888777', 3, 3, 3, 2, 2, 'jherrera', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(9, 'Franco', 'Vega', '36999888', 4, 4, 3, 1, 3, 'fvega', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(10, 'Ailén', 'Núñez', '37000999', 5, 5, 2, 2, 1, 'anunez', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(11, 'Gonzalo', 'Paz', '38111888', 6, 6, 2, 1, 2, 'gpaz', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1),
(12, 'Micaela', 'Benítez', '39222777', 7, 7, 2, 2, 3, 'mbenitez', '$2y$10$HTAnJjJdZAhq334N6Pv.Iu4f2k9dzkf8hsnLsM36VNLxQ3YpD8ji6', 1);

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
  `idusuario` int(11) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'Recibido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_web`
--

INSERT INTO `pedido_web` (`idpedido`, `idusuario`, `fecha`, `total`, `estado`) VALUES
(2, 5, '2026-07-31 08:28:59', 11800.00, 'Recibido'),
(3, 5, '2026-07-31 09:53:19', 40100.00, 'Recibido'),
(4, 4, '2026-07-31 10:01:04', 9800.00, 'Recibido');

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
(4, 'Mesero');

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
(1, '06:00:00', '14:00:00', 'Mañana', 'Lun a Vie'),
(2, '14:00:00', '22:00:00', 'Tarde', 'Mar a Sáb'),
(3, '22:00:00', '06:00:00', 'Noche', 'Jue a Dom');

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
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario_web`
--

INSERT INTO `usuario_web` (`idusuario`, `nombre`, `apellido`, `telefono`, `direccion`, `email`, `password`, `token`, `token_expira`, `token_intentos`, `verificado`, `fecha_registro`) VALUES
(4, 'Gómez', 'Alberto Emanuel', '03704848212', 'B° El Quebranto mz1 cs 35', 'applegomez64@gmail.com', '$2y$10$j56Ycz5rQS2IgkDbEWTRN.P4b1ODz2r/qhfIaLpuR/QhGyKTEnPq.', NULL, NULL, 0, 1, '2026-07-31 08:21:16'),
(5, 'Vicky', 'Marroco', '3705148612', 'Clorinda', 'victoriamarrocco95@gmail.com', '$2y$10$KgPxL42VFvKMooOqUaH1buarO.fz0/PbJoQBSP.Zi9I.btFLNLWDS', NULL, NULL, 0, 1, '2026-07-31 08:24:31');

--
-- Índices para tablas volcadas
--

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
  ADD UNIQUE KEY `usuario_unico` (`usuario`),
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
-- AUTO_INCREMENT de la tabla `detalle_pedido_web`
--
ALTER TABLE `detalle_pedido_web`
  MODIFY `iddetalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `iddireccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `email`
--
ALTER TABLE `email`
  MODIFY `idemail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `idempleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `encabezadoticket`
--
ALTER TABLE `encabezadoticket`
  MODIFY `idencabezadoTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `idpedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT de la tabla `resumenticket`
--
ALTER TABLE `resumenticket`
  MODIFY `idresumenTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `idrol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
