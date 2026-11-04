-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-07-2026 a las 23:03:23
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
  `fk_id_turno` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `dni`, `fk_id_telefono`, `fk_id_email`, `fk_id_rol`, `fk_id_genero`, `fk_id_turno`) VALUES
(1, 'Federico', 'Álvarez', '28111000', 11, 2, 4, 1, 1),
(2, 'Romina', 'Castro', '29222111', 12, 3, 4, 2, 2),
(3, 'Ezequiel', 'Molina', '30333222', 13, 4, 4, 1, 1),
(4, 'Carla', 'Suárez', '31444333', 14, 5, 4, 2, 2),
(5, 'Bruno', 'Acosta', '32555444', 15, 6, 4, 1, 3),
(6, 'Milagros', 'Rojas', '33666555', 1, 7, 4, 2, 1),
(7, 'Tomás', 'Ibáñez', '34777666', 2, 2, 3, 1, 1),
(8, 'Julieta', 'Herrera', '35888777', 3, 3, 3, 2, 2),
(9, 'Franco', 'Vega', '36999888', 4, 4, 3, 1, 3),
(10, 'Ailén', 'Núñez', '37000999', 5, 5, 2, 2, 1),
(11, 'Gonzalo', 'Paz', '38111888', 6, 6, 2, 1, 2),
(12, 'Micaela', 'Benítez', '39222777', 7, 7, 2, 2, 3);

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
  `fk_id_proveedor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `insumo`
--

INSERT INTO `insumo` (`idinsumo`, `nombre`, `precio`, `fk_id_proveedor`) VALUES
(1, 'Carne vacuna (kg)', 5200.00, 1),
(2, 'Pechuga de pollo (kg)', 4300.00, 1),
(3, 'Pan de hamburguesa (unidad)', 220.00, 2),
(4, 'Pan de pancho (unidad)', 180.00, 2),
(5, 'Queso cheddar (kg)', 3800.00, 1),
(6, 'Panceta (kg)', 4700.00, 1),
(7, 'Lechuga (kg)', 900.00, 4),
(8, 'Tomate (kg)', 1100.00, 4),
(9, 'Papa para fritas (kg)', 700.00, 4),
(10, 'Cebolla (kg)', 650.00, 4),
(11, 'Salsa especial Mostaza (litro)', 2600.00, 5),
(12, 'Gaseosa cola 2.25L', 1500.00, 3);

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
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `idproveedor` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fk_id_telefono` int(11) DEFAULT NULL,
  `fk_id_email` int(11) DEFAULT NULL,
  `fk_id_direccion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`idproveedor`, `nombre`, `fk_id_telefono`, `fk_id_email`, `fk_id_direccion`) VALUES
(1, 'Frigorífico El Norteño', 9, 12, 8),
(2, 'Panificadora San José', 10, 13, 9),
(3, 'Distribuidora de Bebidas del Sur', 11, 14, 5),
(4, 'Verdulería Central', 12, 15, 6),
(5, 'Packaging Express SRL', 13, 1, 3);

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
  `horaFin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `turnos`
--

INSERT INTO `turnos` (`idturnos`, `horaInicio`, `horaFin`) VALUES
(1, '06:00:00', '14:00:00'),
(2, '14:00:00', '22:00:00'),
(3, '22:00:00', '06:00:00');

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
-- Indices de la tabla `pieticket`
--
ALTER TABLE `pieticket`
  ADD PRIMARY KEY (`idpieTicket`),
  ADD KEY `fk_id_detalleMp` (`fk_id_detalleMp`);

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
-- AUTO_INCREMENT de la tabla `pieticket`
--
ALTER TABLE `pieticket`
  MODIFY `idpieTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
