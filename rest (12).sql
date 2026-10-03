-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 03-10-2026 a las 23:58:04
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
(3, 'Nueva C├│rdoba'),
(4, 'Centro'),
(5, 'Alberdi'),
(6, 'G├╝emes'),
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
  `turno` varchar(30) NOT NULL DEFAULT 'Turno Ma├▒ana',
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
  `observaciones_cierre` varchar(255) DEFAULT NULL,
  `numero_caja` int(11) NOT NULL DEFAULT 1,
  `terminal_nombre` varchar(50) DEFAULT 'Caja 1 - Principal',
  `idempleado` int(11) DEFAULT NULL,
  `monto_retiros` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja_arqueo`
--

INSERT INTO `caja_arqueo` (`idarqueo`, `cajero`, `turno`, `monto_inicial`, `ventas_efectivo`, `ventas_tarjeta`, `ventas_mercadopago`, `ventas_transferencia`, `total_ventas`, `monto_fisico_cierre`, `diferencia`, `estado`, `fecha_apertura`, `fecha_cierre`, `monto_final_sistema`, `total_transacciones`, `observaciones_apertura`, `observaciones_cierre`, `numero_caja`, `terminal_nombre`, `idempleado`, `monto_retiros`) VALUES
(1, 'Cajero Central', 'Turno Tarde/Noche', 15000.00, 225835.00, 94705.00, 100800.00, 46465.00, 467805.00, NULL, NULL, 'Cerrada', '2026-08-16 18:46:24', '2026-09-16 02:36:22', NULL, 53, NULL, NULL, 1, 'Caja 1 - Principal', NULL, 0.00),
(2, 'Alberto Gómez', '0', 15000.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, NULL, 'Cerrada', '2026-09-16 02:37:59', '2026-09-16 02:38:22', NULL, 0, 'Apertura de turno en terminal POS', NULL, 1, 'Caja 1 - Mostrador Principal', 15, 0.00),
(3, 'Alberto Gómez', '0', 15000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 7500.00, 0.00, 'Cerrada', '2026-09-16 02:38:22', '2026-09-16 07:38:23', 7500.00, 0, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 1, 'Caja 1 - Mostrador Principal', 15, 7500.00),
(4, 'Romina Castro', '0', 10000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 10000.00, 0.00, 'Cerrada', '2026-09-16 02:38:22', '2026-09-16 07:38:23', 10000.00, 0, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 2, 'Caja 2 - Express / Salón', 16, 0.00),
(6, 'Alberto Gómez', 'Turno Actual', 15000.00, 12500.00, 0.00, 0.00, 0.00, 12500.00, NULL, NULL, 'Cerrada', '2026-09-16 02:39:09', '2026-09-16 02:40:20', NULL, 2, NULL, NULL, 1, 'Caja 1 - Principal / Mostrador', 15, 0.00),
(7, 'Alberto Gómez', '0', 15000.00, 12500.00, 0.00, 0.00, 0.00, 12500.00, 20000.00, 0.00, 'Cerrada', '2026-09-16 02:40:20', '2026-09-16 07:40:20', 20000.00, 2, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 1, 'Caja 1 - Mostrador Principal', 15, 7500.00),
(8, 'Romina Castro', '0', 10000.00, 0.00, 0.00, 8900.00, 0.00, 8900.00, 10000.00, 0.00, 'Cerrada', '2026-09-16 02:40:20', '2026-09-16 07:40:20', 10000.00, 2, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 2, 'Caja 2 - Express / Salón', 16, 0.00),
(9, 'Alberto Gómez', '0', 15000.00, 12500.00, 0.00, 0.00, 0.00, 12500.00, 20000.00, 0.00, 'Cerrada', '2026-09-16 02:42:06', '2026-09-16 07:42:07', 20000.00, 2, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 1, 'Caja 1 - Mostrador Principal', 15, 7500.00),
(10, 'Romina Castro', '0', 10000.00, 0.00, 0.00, 8900.00, 0.00, 8900.00, 10000.00, 0.00, 'Cerrada', '2026-09-16 02:42:06', '2026-09-16 07:42:07', 10000.00, 2, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 2, 'Caja 2 - Express / Salón', 16, 0.00),
(11, 'Alberto Gómez', '0', 15000.00, 12500.00, 0.00, 0.00, 0.00, 12500.00, 20000.00, 0.00, 'Cerrada', '2026-09-16 02:43:25', '2026-09-16 07:43:26', 20000.00, 2, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 1, 'Caja 1 - Mostrador Principal', 15, 7500.00),
(12, 'Romina Castro', '0', 10000.00, 0.00, 0.00, 8900.00, 0.00, 8900.00, 10000.00, 0.00, 'Cerrada', '2026-09-16 02:43:25', '2026-09-16 07:43:26', 10000.00, 2, 'Apertura de turno en terminal POS', 'Cierre de turno y arqueo Z regular', 2, 'Caja 2 - Express / Salón', 16, 0.00),
(13, 'Cajero Central', 'Turno Mañana', 15000.00, 85846.00, 0.00, 14440.00, 0.00, 100286.00, 100846.00, 0.00, 'Cerrada', '2026-09-18 09:45:30', '2026-09-25 13:25:01', 100846.00, 22, NULL, 'Cierre de turno y arqueo Z regular', 1, 'Caja 1 - Principal', NULL, 0.00),
(14, 'Cajero Central', 'Turno Mañana', 15000.00, 44047.00, 0.00, 0.00, 0.00, 44047.00, 100000.00, 40953.00, 'Cerrada', '2026-10-02 09:21:24', '2026-10-02 14:42:11', 59047.00, 8, NULL, 'Cierre de turno y arqueo Z regular', 1, 'Caja 1 - Principal', NULL, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja_retiro`
--

CREATE TABLE `caja_retiro` (
  `idretiro` int(11) NOT NULL,
  `idarqueo` int(11) NOT NULL,
  `numero_caja` int(11) NOT NULL DEFAULT 1,
  `idempleado` int(11) DEFAULT NULL,
  `cajero` varchar(100) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `motivo` varchar(255) NOT NULL DEFAULT 'Retiro de seguridad / Sangría a tesorería',
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `caja_retiro`
--

INSERT INTO `caja_retiro` (`idretiro`, `idarqueo`, `numero_caja`, `idempleado`, `cajero`, `monto`, `motivo`, `fecha_hora`) VALUES
(1, 3, 1, 15, 'Alberto Gómez', 7500.00, 'Sangría preventiva a caja fuerte', '2026-09-16 02:38:22'),
(2, 7, 1, 15, 'Alberto Gómez', 7500.00, 'Sangría preventiva a caja fuerte', '2026-09-16 02:40:20'),
(3, 9, 1, 15, 'Alberto Gómez', 7500.00, 'Sangría preventiva a caja fuerte', '2026-09-16 02:42:07'),
(4, 11, 1, 15, 'Alberto Gómez', 7500.00, 'Sangría preventiva a caja fuerte', '2026-09-16 02:43:25');

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
(2, 'C├│rdoba'),
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
(1, 'Juan', 'P├®rez', 'jperez', '30111222', 1, 1, 1, 8, 1),
(2, 'Mar├¡a', 'G├│mez', 'mgomez', '31222333', 2, 2, 1, 9, 2),
(3, 'Lucas', 'Fern├índez', 'lfernandez', '32333444', 3, 3, 1, 10, 1),
(4, 'Sof├¡a', 'Mart├¡nez', 'smartinez', '33444555', 4, 4, 1, 11, 2),
(5, 'Diego', 'Rodr├¡guez', 'drodriguez', '34555666', 5, 5, 1, 8, 1),
(6, 'Valentina', 'L├│pez', 'vlopez', '35666777', 6, 6, 1, 9, 2),
(7, 'Mart├¡n', 'Sosa', 'msosa', '36777888', 7, 7, 1, 10, 1),
(8, 'Camila', 'Torres', 'ctorres', '37888999', 8, 8, 1, 11, 2),
(9, 'Nicol├ís', 'Ram├¡rez', 'nramirez', '38999000', 9, 9, 1, 8, 1),
(10, 'Agustina', 'D├¡az', 'adiaz', '39000111', 10, 10, 1, 9, 3);

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
(6, 'Hamburguesa Cl├ísica', 1, 1),
(7, 'Combo Familiar Mostaza', 1, 1),
(8, 'Papas Fritas Grandes', 9, 4),
(9, 'Nuggets de Pollo x8', 2, 1),
(10, 'Ensalada C├®sar', 7, 4),
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
(3, 'TRUENO15', 'porcentaje', 15.00, 8000.00, 4000.00, 1, '15% de descuento edici├│n Trueno'),
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
(3, 2, 28, 'Cajita Mostaza H├®roes Cl├ísica', '', 5200.00, 1, 5200.00),
(4, 2, 33, 'Tostado Queso & Jam├│n', '', 3800.00, 7, 26600.00),
(5, 3, NULL, 'Hamburguesa personalizada: Queso, Nuggets de Pollo', '', 2800.00, 1, 2800.00),
(6, 3, 40, 'Bud├¡n de Banana', '', 2100.00, 1, 2100.00),
(7, 3, 39, 'Alfajor de Chocolate', '', 1800.00, 1, 1800.00),
(8, 4, 24, 'Wrap Cheddar & Pollo', '', 6200.00, 1, 6200.00),
(9, 4, 56, 'Pollo Crispy Bacon', '', 8600.00, 1, 8600.00),
(10, 4, 86, 'Cono Cl├ísico', '', 2200.00, 1, 2200.00),
(11, 5, 1, 'Hambre de la Cuarta | Provolone y Bacon', '', 9800.00, 1, 9800.00),
(12, 10, 1, 'Mega Cuarto XL', '', 22700.00, 1, 22700.00),
(13, 11, 1, 'Mega Cuarto XL', '', 20100.00, 1, 20100.00),
(14, 12, 1, 'Mega Cuarto XL', '', 14500.00, 1, 14500.00),
(15, 13, 1, 'Mega Cuarto XL', '', 32000.00, 1, 32000.00),
(16, 14, 83, 'Bud├¡n de Banana', '', 2100.00, 1, 2100.00),
(17, 15, 14, 'Pollo Crispy', '', 7900.00, 1, 7900.00),
(18, 16, 1, 'Mega Cuarto XL', '', 22700.00, 1, 22700.00),
(19, 17, 1, 'Mega Cuarto XL', '', 20100.00, 1, 20100.00),
(20, 18, 1, 'Mega Cuarto XL', '', 14500.00, 1, 14500.00),
(21, 19, 1, 'Mega Cuarto XL', '', 32000.00, 1, 32000.00),
(22, 20, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Nuggets de Pollo', '', 10300.00, 1, 10300.00),
(23, 21, 2, 'La Cuarta | Ahumada', '', 9300.00, 1, 9300.00),
(24, 22, NULL, 'Hamburguesa Mega Cuádruple XL', 'Pan artesanal, Aderezos: Mayonesa clásica, Barbacoa BBQ', 15000.00, 1, 15000.00),
(25, 23, 38, 'Medialunas Jamón & Queso', '', 2800.00, 1, 2800.00),
(26, 24, 38, 'Medialunas Jamón & Queso', '', 2800.00, 1, 2800.00),
(27, 25, 14, 'Pollo Crispy', '', 7900.00, 1, 7900.00),
(28, 26, NULL, 'Mega Cuarto XL', '', 12500.00, 1, 12500.00),
(29, 27, NULL, 'Mega Bacon Doble', '', 14000.00, 1, 14000.00),
(30, 28, NULL, 'Papas Cheddar Bacon', '', 5500.00, 1, 5500.00),
(31, 29, 25, 'Cero Veggie Cheese', '', 8200.00, 1, 8200.00),
(32, 30, 1, 'Mega Cuarto XL', '', 6500.00, 1, 6500.00),
(33, 31, 33, 'Tostado Queso & Jam├│n', '', 3800.00, 1, 3800.00),
(34, 32, 1, 'Mega Cuarto Doble', '', 14500.00, 1, 14500.00),
(35, 32, 25, 'Cero Veggie Cheese', '', 8200.00, 1, 8200.00),
(36, 33, 1, 'Hambre de la Cuarta | Provolone y Bacon', '', 9800.00, 2, 19600.00),
(37, 34, 1, 'Mega Cuarto XL', '', 12500.00, 1, 12500.00),
(38, 35, 1, 'Mega Cuarto XL', '', 12500.00, 1, 12500.00),
(39, 36, 2, 'Mega Doble Pollo', '', 8900.00, 1, 8900.00),
(40, 37, 1, 'Mega Cuarto XL', '', 12500.00, 1, 12500.00),
(41, 38, 2, 'Mega Doble Pollo', '', 8900.00, 1, 8900.00),
(42, 39, 1, 'Mega Cuarto XL', '', 12500.00, 1, 12500.00),
(43, 40, 2, 'Mega Doble Pollo', '', 8900.00, 1, 8900.00),
(44, 41, 32, 'Cajita Mostaza H├®roes Deluxe', '', 6300.00, 1, 6300.00),
(45, 42, 34, 'Tostado XL Queso & Jam├│n', '', 4600.00, 1, 4600.00),
(46, 42, 33, 'Tostado Queso & Jam├│n', '', 3800.00, 1, 3800.00),
(47, 43, 34, 'Tostado XL Queso & Jam├│n', '', 4600.00, 1, 4600.00),
(48, 43, 33, 'Tostado Queso & Jam├│n', '', 3800.00, 1, 3800.00),
(49, 44, 1, 'Hambre de la Cuarta | Provolone y Bacon', '', 9800.00, 1, 9800.00),
(50, 45, 34, 'Tostado XL Queso & Jam├│n', '', 4600.00, 1, 4600.00),
(51, 46, 29, 'Cajita Mostaza H├®roes Nuggets', '', 5200.00, 1, 5200.00),
(52, 47, 27, 'Mega Cuarto Doble sin TACC', '', 10800.00, 1, 10800.00),
(53, 48, NULL, 'Hamburguesa Personalizada: Pan Tostado, Carne a la Parrilla, Queso Cheddar, Lechuga, Tomate, Huevo, Tocino / Bacon, Cebolla Morada + Salsas: Mayonesa, Ketchup, Mostaza, Barbacoa BBQ + Acomp: Nuggets de Pollo', 'Aderezos: Mayonesa, Ketchup, Mostaza, Barbacoa BBQ', 9100.00, 1, 9100.00),
(54, 48, 4, 'Hambre de la Cuarta | Provolone y Bacon', '', 11800.00, 1, 11800.00),
(55, 49, 20, 'Ensalada Palta y Huevo Grill', '', 6800.00, 1, 6800.00),
(56, 50, 1, 'Hambre de la Cuarta | Provolone y Bacon', '', 9800.00, 1, 9800.00),
(57, 51, 77, 'Tostado XL Queso & Jam├│n', '', 4600.00, 1, 4600.00),
(58, 51, 81, 'Medialunas Jam├│n & Queso', '', 2800.00, 2, 5600.00),
(59, 52, 4, 'Hambre de la Cuarta | Provolone y Bacon', '', 11800.00, 1, 11800.00),
(60, 53, 1, 'Hambre de la Cuarta | Provolone y Bacon', '', 9800.00, 1, 9800.00),
(61, 54, 28, 'Cajita Mostaza H├®roes Cl├ísica', '', 5200.00, 1, 5200.00),
(62, 55, 5, 'La Cuarta | Ahumada', '', 11300.00, 1, 11300.00),
(63, 55, 20, 'Ensalada Palta y Huevo Grill', '', 6800.00, 1, 6800.00);

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
(9, 5, 28, 'Cajita Mostaza H├®roes Cl├ísica', 5200.00, 1, 5200.00, ''),
(13, 6, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Nuggets de Pollo', 10300.00, 1, 10300.00, ''),
(17, 7, 14, 'Pollo Crispy', 7900.00, 1, 7900.00, ''),
(20, 10, 1, 'Mega Deluxe Doble', 9500.00, 2, 19000.00, 'Sin pepinos'),
(21, 10, 11, 'Papas con Cheddar y Bacon', 4800.00, 1, 4800.00, 'Extra queso'),
(22, 11, 28, 'Cajita Mostaza H├®roes Cl├ísica', 5200.00, 1, 5200.00, ''),
(23, 11, 33, 'Tostado Queso & Jam├│n', 3800.00, 7, 26600.00, ''),
(24, 12, NULL, 'Hamburguesa personalizada: Queso, Nuggets de Pollo', 2800.00, 1, 2800.00, ''),
(25, 12, 40, 'Bud├¡n de Banana', 2100.00, 1, 2100.00, ''),
(26, 12, 39, 'Alfajor de Chocolate', 1800.00, 1, 1800.00, ''),
(27, 13, 24, 'Wrap Cheddar & Pollo', 6200.00, 1, 6200.00, ''),
(28, 13, 56, 'Pollo Crispy Bacon', 8600.00, 1, 8600.00, ''),
(29, 13, 86, 'Cono Cl├ísico', 2200.00, 1, 2200.00, ''),
(30, 14, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00, ''),
(31, 20, 1, 'Mega Cuarto XL', 22700.00, 1, 22700.00, ''),
(32, 21, 1, 'Mega Cuarto XL', 20100.00, 1, 20100.00, ''),
(33, 22, 1, 'Mega Cuarto XL', 14500.00, 1, 14500.00, ''),
(34, 23, 1, 'Mega Cuarto XL', 32000.00, 1, 32000.00, ''),
(35, 24, 83, 'Bud├¡n de Banana', 2100.00, 1, 2100.00, ''),
(36, 25, 14, 'Pollo Crispy', 7900.00, 1, 7900.00, ''),
(37, 26, 1, 'Mega Cuarto XL', 22700.00, 1, 22700.00, ''),
(38, 27, 1, 'Mega Cuarto XL', 20100.00, 1, 20100.00, ''),
(39, 28, 1, 'Mega Cuarto XL', 14500.00, 1, 14500.00, ''),
(40, 29, 1, 'Mega Cuarto XL', 32000.00, 1, 32000.00, ''),
(41, 30, NULL, 'Hamburguesa personalizada: Pan, Carne, Queso, Lechuga, Tomate, Pepino, Huevo, Tocino, Cebolla, Nuggets de Pollo', 10300.00, 1, 10300.00, ''),
(42, 31, 2, 'La Cuarta | Ahumada', 9300.00, 1, 9300.00, ''),
(43, 32, 1, 'Mega Cuarto Doble', 14500.00, 1, 14500.00, NULL),
(44, 33, NULL, 'Hamburguesa Mega Cuádruple XL', 15000.00, 1, 15000.00, 'Pan artesanal, Aderezos: Mayonesa clásica, Barbacoa BBQ'),
(45, 34, 38, 'Medialunas Jamón & Queso', 2800.00, 1, 2800.00, ''),
(46, 36, 38, 'Medialunas Jamón & Queso', 2800.00, 1, 2800.00, ''),
(47, 37, 14, 'Pollo Crispy', 7900.00, 1, 7900.00, ''),
(48, 38, NULL, 'Mega Cuarto XL', 12500.00, 1, 12500.00, ''),
(49, 39, NULL, 'Mega Bacon Doble', 14000.00, 1, 14000.00, ''),
(50, 40, NULL, 'Papas Cheddar Bacon', 5500.00, 1, 5500.00, ''),
(51, 41, 25, 'Cero Veggie Cheese', 8200.00, 1, 8200.00, ''),
(52, 42, 1, 'Mega Cuarto XL', 6500.00, 1, 6500.00, ''),
(53, 43, 33, 'Tostado Queso & Jam├│n', 3800.00, 1, 3800.00, ''),
(54, 44, 1, 'Mega Cuarto Doble', 14500.00, 1, 14500.00, ''),
(55, 44, 25, 'Cero Veggie Cheese', 8200.00, 1, 8200.00, ''),
(56, 45, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 2, 19600.00, ''),
(57, 47, 1, 'Mega Cuarto XL', 12500.00, 1, 12500.00, ''),
(58, 48, 1, 'Mega Cuarto XL', 12500.00, 1, 12500.00, ''),
(59, 49, 2, 'Mega Doble Pollo', 8900.00, 1, 8900.00, ''),
(60, 50, 1, 'Mega Cuarto XL', 12500.00, 1, 12500.00, ''),
(61, 51, 2, 'Mega Doble Pollo', 8900.00, 1, 8900.00, ''),
(62, 52, 1, 'Mega Cuarto XL', 12500.00, 1, 12500.00, ''),
(63, 53, 2, 'Mega Doble Pollo', 8900.00, 1, 8900.00, ''),
(64, 54, 32, 'Cajita Mostaza H├®roes Deluxe', 6300.00, 1, 6300.00, ''),
(65, 55, 34, 'Tostado XL Queso & Jam├│n', 4600.00, 1, 4600.00, ''),
(66, 55, 33, 'Tostado Queso & Jam├│n', 3800.00, 1, 3800.00, ''),
(67, 56, 34, 'Tostado XL Queso & Jam├│n', 4600.00, 1, 4600.00, ''),
(68, 56, 33, 'Tostado Queso & Jam├│n', 3800.00, 1, 3800.00, ''),
(69, 57, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00, ''),
(70, 58, 34, 'Tostado XL Queso & Jam├│n', 4600.00, 1, 4600.00, ''),
(71, 59, 29, 'Cajita Mostaza H├®roes Nuggets', 5200.00, 1, 5200.00, ''),
(72, 60, 27, 'Mega Cuarto Doble sin TACC', 10800.00, 1, 10800.00, ''),
(73, 61, NULL, 'Hamburguesa Personalizada: Pan Tostado, Carne a la Parrilla, Queso Cheddar, Lechuga, Tomate, Huevo, Tocino / Bacon, Cebolla Morada + Salsas: Mayonesa, Ketchup, Mostaza, Barbacoa BBQ + Acomp: Nuggets de Pollo', 9100.00, 1, 9100.00, 'Aderezos: Mayonesa, Ketchup, Mostaza, Barbacoa BBQ'),
(74, 61, 4, 'Hambre de la Cuarta | Provolone y Bacon', 11800.00, 1, 11800.00, ''),
(75, 62, 20, 'Ensalada Palta y Huevo Grill', 6800.00, 1, 6800.00, ''),
(76, 63, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00, ''),
(77, 64, 77, 'Tostado XL Queso & Jam├│n', 4600.00, 1, 4600.00, ''),
(78, 64, 81, 'Medialunas Jam├│n & Queso', 2800.00, 2, 5600.00, ''),
(79, 65, 4, 'Hambre de la Cuarta | Provolone y Bacon', 11800.00, 1, 11800.00, ''),
(80, 66, 1, 'Hambre de la Cuarta | Provolone y Bacon', 9800.00, 1, 9800.00, ''),
(81, 67, 28, 'Cajita Mostaza H├®roes Cl├ísica', 5200.00, 1, 5200.00, ''),
(82, 68, 5, 'La Cuarta | Ahumada', 11300.00, 1, 11300.00, ''),
(83, 68, 20, 'Ensalada Palta y Huevo Grill', 6800.00, 1, 6800.00, '');

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
(5, 'Av. San Mart├¡n', NULL, '210', 4, 4),
(6, 'Av. 25 de Mayo', NULL, '150', 4, 5),
(7, 'Calle Brown', 'Mz 3', 'Casa 12', 5, 6),
(8, 'Av. Col├│n', NULL, '780', 6, 2),
(9, 'Bv. Oro├▒o', NULL, '345', 1, 3),
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
  `password` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `vehiculo` varchar(100) DEFAULT NULL,
  `patente` varchar(20) DEFAULT NULL,
  `avatar` varchar(50) DEFAULT '?',
  `sucursal_base` varchar(100) DEFAULT 'Mostaza Formosa Centro',
  `estado_servicio` enum('Disponible','En Reparto','En Descanso','Desconectado') DEFAULT 'Disponible',
  `calificacion` decimal(3,2) DEFAULT 4.95,
  `viajes_totales` int(11) DEFAULT 0,
  `fk_id_seccion` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`idempleado`, `nombre`, `apellido`, `dni`, `fk_id_telefono`, `fk_id_email`, `fk_id_rol`, `fk_id_genero`, `fk_id_turno`, `activo`, `email`, `token`, `token_expira`, `token_intentos`, `usuario`, `password`, `telefono`, `vehiculo`, `patente`, `avatar`, `sucursal_base`, `estado_servicio`, `calificacion`, `viajes_totales`, `fk_id_seccion`) VALUES
(1, 'Federico', '├ülvarez', '28111000', 11, 2, 4, 1, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(2, 'Romina', 'Castro', '29222111', 12, 3, 4, 2, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(3, 'Ezequiel', 'Molina', '30333222', 13, 4, 4, 1, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(4, 'Carla', 'Su├írez', '31444333', 14, 5, 4, 2, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(5, 'Bruno', 'Acosta', '32555444', 15, 6, 4, 1, 3, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(6, 'Milagros', 'Rojas', '33666555', 1, 7, 4, 2, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(7, 'Tom├ís', 'Ib├í├▒ez', '34777666', 2, 2, 3, 1, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(8, 'Julieta', 'Herrera', '35888777', 3, 3, 3, 2, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(9, 'Franco', 'Vega', '36999888', 4, 4, 3, 1, 3, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(10, 'Ail├®n', 'N├║├▒ez', '37000999', 5, 5, 2, 2, 1, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(11, 'Gonzalo', 'Paz', '38111888', 6, 6, 2, 1, 2, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(12, 'Micaela', 'Ben├¡tez', '39222777', 7, 7, 2, 2, 3, 1, 'cajero@mostaza.com.ar', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, '👤', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(13, 'G├│mez', 'Alberto Emanuel', NULL, NULL, NULL, 2, NULL, NULL, 1, 'applegomez64@gmail.com', NULL, NULL, 0, 'applegomez64', NULL, '3704-112233', NULL, NULL, '👨‍💼', 'Mostaza Formosa Centro', 'Disponible', 4.95, 2, 1),
(14, 'Romina', 'Castro', '29222111', NULL, NULL, 2, NULL, 1, 1, 'cajero1@mostaza.com.ar', '874655', '2026-09-16 07:58:05', 0, 'cajero1', NULL, '3704-223344', NULL, NULL, '👩‍💼', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(15, 'Martín', 'Paredes', '33444555', NULL, NULL, 3, NULL, 1, 1, 'cocina1@mostaza.com.ar', NULL, NULL, 0, 'cocina1', NULL, '3704-334455', NULL, NULL, '👨‍🍳', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(16, 'Lucas', 'Silveira', '34555666', NULL, NULL, 3, NULL, 1, 1, 'cocina2@mostaza.com.ar', NULL, NULL, 0, 'cocina2', NULL, '3704-445566', NULL, NULL, '👨‍🍳', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(17, 'Franco', 'Morales', '35666777', NULL, NULL, 4, NULL, 1, 1, 'mesero1@mostaza.com.ar', NULL, NULL, 0, 'mesero1', NULL, '3704-556677', NULL, NULL, '🤵', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(18, 'Ezequiel', 'Molina', '36777888', NULL, NULL, 4, NULL, 1, 1, 'mesero2@mostaza.com.ar', NULL, NULL, 0, 'mesero2', NULL, '3704-667788', NULL, NULL, '🤵', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 2),
(19, 'Ramiro', 'González', '36852147', NULL, NULL, 6, NULL, 1, 1, 'mirianeliza991@gmail.com', '646283', '2026-10-02 14:19:05', 0, 'delivery1', NULL, '3704-551122', 'Moto Honda Wave 110cc', 'A123BCD', '🛵', 'Mostaza Formosa Centro', 'Desconectado', 4.95, 9, 1),
(20, 'Lucas', 'Benítez', '38411902', NULL, NULL, 6, NULL, 1, 1, 'delivery2@mostaza.com.ar', NULL, NULL, 0, 'delivery2', NULL, '3704-662233', 'Moto Yamaha YBR 125cc', 'A045EFG', '🏍️', 'Mostaza Formosa Centro', 'Disponible', 4.95, 1, 1),
(21, 'Mateo', 'Silveira', '40231876', NULL, NULL, 6, NULL, 1, 1, 'delivery3@mostaza.com.ar', NULL, NULL, 0, 'delivery3', NULL, '3704-995566', 'Moto Gilera Smash 110', 'A654NOP', '⚡', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1),
(22, 'Ramiro', 'González', '36852147', NULL, NULL, 6, NULL, 1, 1, 'delivery1@mostaza.com.ar', NULL, NULL, 0, 'delivery1', NULL, '3704-551122', 'Moto Honda Wave 110cc', 'A123BCD', '🛵', 'Mostaza Formosa Centro', 'Disponible', 4.95, 0, 1);

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
(1, 'FAC-B-0001-00000001', 'Factura B', 10, 1, 1, NULL, 'Mesa 4 (Sal├│n)', '0', '2026-08-25 01:22:58', 'efectivo', 23800.00, 0.00, 19669.42, 4130.58, 23800.00, 30000.00, 6200.00, '30-71234567-9', 'Mostaza SA', '74018963360203', '2026-09-04', 'Emitida'),
(2, 'FAC-B-0001-00000002', 'Factura B', 11, 1, 1, 4, 'G├│mez Alberto Emanuel', '03704848212', '2026-08-25 08:14:52', 'efectivo', 31800.00, 5225.00, 25904.96, 5440.04, 31345.00, 31345.00, 0.00, '30-71234567-9', 'Mostaza SA', '74018964112113', '2026-09-04', 'Emitida'),
(3, 'FAC-B-0001-00000003', 'Factura B', 12, 1, 1, 4, 'G├│mez Alberto Emanuel', '03704848212', '2026-08-25 08:22:20', 'efectivo', 6700.00, 313.00, 6109.09, 1282.91, 7392.00, 7392.00, 0.00, '30-71234567-9', 'Mostaza SA', '74052801638193', '2026-09-04', 'Emitida'),
(4, 'FAC-B-0001-00000004', 'Factura B', 13, 1, 1, 7, 'Camila Riquelme', '3704847532', '2026-08-25 08:27:39', 'efectivo', 17000.00, 0.00, 14752.07, 3097.93, 17850.00, 17850.00, 0.00, '30-71234567-9', 'Mostaza SA', '74088942488493', '2026-09-04', 'Emitida'),
(5, 'FAC-B-0001-00000005', 'Factura B', 14, 1, 13, NULL, 'Consumidor Final', '0', '2026-08-25 08:50:55', 'efectivo', 9800.00, 0.00, 8099.17, 1700.83, 9800.00, 20000.00, 10200.00, '30-71234567-9', 'Mostaza SA', '74026604138633', '2026-09-04', 'Emitida'),
(6, 'FAC-B-0001-00000006', 'Factura B', 15, 1, 1, NULL, 'Juan Perez', '0', '2026-08-25 17:55:45', 'efectivo', 18500.00, 0.00, 15289.26, 3210.74, 18500.00, 20000.00, 1500.00, '30-71234567-9', 'Mostaza SA', '74074848532573', '2026-09-04', 'Emitida'),
(7, 'FAC-B-0001-00000007', 'Factura B', 16, 1, 1, NULL, 'Maria Rodriguez', '0', '2026-08-25 17:55:45', 'transferencia', 24600.00, 0.00, 20330.58, 4269.42, 24600.00, 24600.00, 0.00, '30-71234567-9', 'Mostaza SA', '74045018314863', '2026-09-04', 'Emitida'),
(8, 'FAC-B-0001-00000008', 'Factura B', 17, 1, 1, NULL, 'Carlos Lopez', '0', '2026-08-25 17:55:45', 'tarjeta', 31200.00, 0.00, 25785.12, 5414.88, 31200.00, 31200.00, 0.00, '30-71234567-9', 'Mostaza SA', '74086826591083', '2026-09-04', 'Emitida'),
(9, 'FAC-B-0001-00000009', 'Factura B', 19, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-25 19:46:10', 'efectivo', 10000.00, 0.00, 8264.46, 1735.54, 10000.00, 10000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74071446480433', '2026-09-05', 'Emitida'),
(10, 'FAC-B-0001-00000010', 'Factura B', 20, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'efectivo', 21500.00, 0.00, 18760.33, 3939.67, 22700.00, 25000.00, 3500.00, '30-71234567-9', 'Mostaza SA', '74092012303653', '2026-09-05', 'Emitida'),
(11, 'FAC-B-0001-00000011', 'Factura B', 21, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'transferencia', 18900.00, 0.00, 16611.57, 3488.43, 20100.00, 18900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74066551396403', '2026-09-05', 'Emitida'),
(12, 'FAC-B-0001-00000012', 'Factura B', 22, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'mercadopago', 14500.00, 0.00, 11983.47, 2516.53, 14500.00, 14500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74094634603563', '2026-09-05', 'Emitida'),
(13, 'FAC-B-0001-00000013', 'Factura B', 23, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-25 19:46:41', 'tarjeta', 32000.00, 0.00, 26446.28, 5553.72, 32000.00, 32000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74057467745943', '2026-09-05', 'Emitida'),
(14, 'FAC-B-0001-00000014', 'Factura B', 24, 1, 1, 4, 'G├│mez Alberto Emanuel', '03704848212', '2026-08-25 19:51:09', 'tarjeta', 2100.00, 0.00, 2814.05, 590.95, 3405.00, 3405.00, 0.00, '30-71234567-9', 'Mostaza SA', '74011612174393', '2026-09-05', 'Emitida'),
(15, 'FAC-B-0001-00000015', 'Factura B', 25, 1, 13, NULL, 'Consumidor Final', '0', '2026-08-26 00:55:07', 'mercadopago', 7900.00, 0.00, 6528.93, 1371.07, 7900.00, 7900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74036829929183', '2026-09-05', 'Emitida'),
(16, 'FAC-B-0001-00000016', 'Factura B', 26, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'efectivo', 21500.00, 0.00, 18760.33, 3939.67, 22700.00, 25000.00, 3500.00, '30-71234567-9', 'Mostaza SA', '74061725645133', '2026-09-05', 'Emitida'),
(17, 'FAC-B-0001-00000017', 'Factura B', 27, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'transferencia', 18900.00, 0.00, 16611.57, 3488.43, 20100.00, 18900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74051488583173', '2026-09-05', 'Emitida'),
(18, 'FAC-B-0001-00000018', 'Factura B', 28, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'mercadopago', 14500.00, 0.00, 11983.47, 2516.53, 14500.00, 14500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74026853920733', '2026-09-05', 'Emitida'),
(19, 'FAC-B-0001-00000019', 'Factura B', 29, 1, 1, 4, 'G├│mez Alberto Emanuel', '0', '2026-08-26 01:08:02', 'tarjeta', 32000.00, 0.00, 26446.28, 5553.72, 32000.00, 32000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74077350069893', '2026-09-05', 'Emitida'),
(20, 'FAC-B-0001-00000020', 'Factura B', 30, 1, 1, 4, 'G├│mez Alberto Emanuel', '3704848212', '2026-08-26 01:28:25', 'transferencia', 10300.00, 0.00, 9504.13, 1995.87, 11500.00, 11500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74052737130083', '2026-09-05', 'Emitida'),
(21, 'FAC-B-0001-00000021', 'Factura B', 31, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-08-26 17:38:44', 'transferencia', 9300.00, 0.00, 9061.98, 1903.02, 10965.00, 10965.00, 0.00, '30-71234567-9', 'Mostaza SA', '74094348858603', '2026-09-05', 'Emitida'),
(22, 'FAC-B-0001-00000022', 'Factura B', 33, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-14 02:36:13', 'mercadopago', 15000.00, 0.00, 12396.69, 2603.31, 15000.00, 15000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74092215817393', '2026-09-24', 'Emitida'),
(23, 'FAC-B-0001-00000023', 'Factura B', 34, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-14 02:50:55', 'mercadopago', 2800.00, 0.00, 3305.79, 694.21, 4000.00, 4000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74039972752193', '2026-09-24', 'Emitida'),
(24, 'FAC-B-0001-00000024', 'Factura B', 36, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-14 03:11:20', 'tarjeta', 2800.00, 0.00, 2314.05, 485.95, 2800.00, 2800.00, 0.00, '30-71234567-9', 'Mostaza SA', '74015492264023', '2026-09-24', 'Emitida'),
(25, 'FAC-B-0001-00000025', 'Factura B', 37, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-14 09:38:14', 'efectivo', 7900.00, 0.00, 6528.93, 1371.07, 7900.00, 7900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74079484186073', '2026-09-24', 'Emitida'),
(26, 'FAC-B-0001-00000026', 'Factura B', 38, 1, 1, NULL, 'Cliente MP Test', '20123456789', '2026-09-15 01:32:45', 'mercadopago', 12500.00, 0.00, 10330.58, 2169.42, 12500.00, 12500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74067933895713', '2026-09-25', 'Emitida'),
(27, 'FAC-B-0001-00000027', 'Factura B', 39, 1, 1, NULL, 'Cliente Tarjeta Test', '27334455661', '2026-09-15 01:32:45', 'tarjeta', 14000.00, 0.00, 11570.25, 2429.75, 14000.00, 14000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74075239094593', '2026-09-25', 'Emitida'),
(28, 'FAC-B-0001-00000028', 'Factura B', 40, 1, 1, NULL, 'Cliente Transferencia', '20445566778', '2026-09-15 01:32:45', 'transferencia', 5500.00, 0.00, 4545.45, 954.55, 5500.00, 5500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74068470185553', '2026-09-25', 'Emitida'),
(29, 'FAC-B-0001-00000029', 'Factura B', 41, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-15 08:05:07', 'mercadopago', 8200.00, 0.00, 7768.60, 1631.40, 9400.00, 9400.00, 0.00, '30-71234567-9', 'Mostaza SA', '74024443918803', '2026-09-25', 'Emitida'),
(30, 'FAC-B-0001-00000030', 'Factura B', 42, 1, 1, NULL, 'JUAN PEREZ', '38123456', '2026-09-15 09:35:57', 'tarjeta', 6500.00, 0.00, 5371.90, 1128.10, 6500.00, 6500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74030024150923', '2026-09-25', 'Emitida'),
(31, 'FAC-B-0001-00000031', 'Factura B', 43, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-15 10:21:18', 'efectivo', 3800.00, 722.00, 2700.83, 567.17, 3268.00, 3268.00, 0.00, '30-71234567-9', 'Mostaza SA', '74038478227783', '2026-09-25', 'Emitida'),
(32, 'FAC-B-0001-00000032', 'Factura B', 44, 1, 1, 4, 'Betosixseven67 G├│mez', '3704848212', '2026-09-15 10:30:19', 'efectivo', 22700.00, 4540.00, 16000.00, 3360.00, 19360.00, 19360.00, 0.00, '30-71234567-9', 'Mostaza SA', '74099396755603', '2026-09-25', 'Emitida'),
(33, 'FAC-B-0001-00000033', 'Factura B', 45, 1, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-15 10:43:07', 'efectivo', 19600.00, 225.00, 16012.40, 3362.60, 19375.00, 20000.00, 625.00, '30-71234567-9', 'Mostaza SA', '74010171512263', '2026-09-25', 'Emitida'),
(34, 'FAC-B-0001-00000034', 'Factura B', 47, 6, 15, NULL, 'Consumidor Final', '0', '2026-09-16 02:39:09', 'efectivo', 12500.00, 0.00, 10330.58, 2169.42, 12500.00, 12500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74086864845663', '2026-09-26', 'Emitida'),
(35, 'FAC-B-0001-00000035', 'Factura B', 48, 7, 15, NULL, 'Consumidor Final', '0', '2026-09-16 02:40:20', 'efectivo', 12500.00, 0.00, 10330.58, 2169.42, 12500.00, 12500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74058196649913', '2026-09-26', 'Emitida'),
(36, 'FAC-B-0001-00000036', 'Factura B', 49, 8, 16, NULL, 'Consumidor Final', '0', '2026-09-16 02:40:20', 'mercadopago', 8900.00, 0.00, 7355.37, 1544.63, 8900.00, 8900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74072534234463', '2026-09-26', 'Emitida'),
(37, 'FAC-B-0001-00000037', 'Factura B', 50, 9, 15, NULL, 'Consumidor Final', '0', '2026-09-16 02:42:06', 'efectivo', 12500.00, 0.00, 10330.58, 2169.42, 12500.00, 12500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74085348303533', '2026-09-26', 'Emitida'),
(38, 'FAC-B-0001-00000038', 'Factura B', 51, 10, 16, NULL, 'Consumidor Final', '0', '2026-09-16 02:42:06', 'mercadopago', 8900.00, 0.00, 7355.37, 1544.63, 8900.00, 8900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74017698141053', '2026-09-26', 'Emitida'),
(39, 'FAC-B-0001-00000039', 'Factura B', 52, 11, 15, NULL, 'Consumidor Final', '0', '2026-09-16 02:43:25', 'efectivo', 12500.00, 0.00, 10330.58, 2169.42, 12500.00, 12500.00, 0.00, '30-71234567-9', 'Mostaza SA', '74099213731783', '2026-09-26', 'Emitida'),
(40, 'FAC-B-0001-00000040', 'Factura B', 53, 12, 16, NULL, 'Consumidor Final', '0', '2026-09-16 02:43:25', 'mercadopago', 8900.00, 0.00, 7355.37, 1544.63, 8900.00, 8900.00, 0.00, '30-71234567-9', 'Mostaza SA', '74087717470733', '2026-09-26', 'Emitida'),
(41, 'FAC-B-0001-00000041', 'Factura B', 54, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-18 09:45:30', 'efectivo', 6300.00, 0.00, 6198.35, 1301.65, 7500.00, 10000.00, 2500.00, '30-71234567-9', 'Mostaza SA', '74062406002003', '2026-09-28', 'Emitida'),
(42, 'FAC-B-0001-00000042', 'Factura B', 55, 13, 13, NULL, 'Consumidor Final', '0', '2026-09-18 21:46:07', 'efectivo', 8400.00, 0.00, 6942.15, 1457.85, 8400.00, 10000.00, 1600.00, '30-71234567-9', 'Mostaza SA', '74097638478903', '2026-09-29', 'Emitida'),
(43, 'FAC-B-0001-00000043', 'Factura B', 56, 13, 13, NULL, 'Consumidor Final', '0', '2026-09-18 21:46:22', 'efectivo', 8400.00, 0.00, 6942.15, 1457.85, 8400.00, 10000.00, 1600.00, '30-71234567-9', 'Mostaza SA', '74090198514263', '2026-09-29', 'Emitida'),
(44, 'FAC-B-0001-00000044', 'Factura B', 57, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-23 08:35:19', 'efectivo', 9800.00, 0.00, 9090.91, 1909.09, 11000.00, 11000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74018765358863', '2026-10-03', 'Emitida'),
(45, 'FAC-B-0001-00000045', 'Factura B', 58, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-23 08:44:11', 'mercadopago', 4600.00, 0.00, 3801.65, 798.35, 4600.00, 4600.00, 0.00, '30-71234567-9', 'Mostaza SA', '74068196011943', '2026-10-03', 'Emitida'),
(46, 'FAC-B-0001-00000046', 'Factura B', 59, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-23 08:46:07', 'efectivo', 5200.00, 0.00, 4297.52, 902.48, 5200.00, 5200.00, 0.00, '30-71234567-9', 'Mostaza SA', '74092622409493', '2026-10-03', 'Emitida'),
(47, 'FAC-B-0001-00000047', 'Factura B', 60, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-23 09:00:59', 'mercadopago', 10800.00, 2160.00, 8132.23, 1707.77, 9840.00, 9840.00, 0.00, '30-71234567-9', 'Mostaza SA', '74046929049083', '2026-10-03', 'Emitida'),
(48, 'FAC-B-0001-00000048', 'Factura B', 61, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-23 10:33:30', 'efectivo', 20900.00, 4754.00, 14335.54, 3010.46, 17346.00, 20000.00, 2654.00, '30-71234567-9', 'Mostaza SA', '74085496634753', '2026-10-03', 'Emitida'),
(49, 'FAC-B-0001-00000049', 'Factura B', 62, 13, 17, NULL, 'Consumidor Final', '0', '2026-09-23 10:43:50', 'efectivo', 6800.00, 0.00, 5619.83, 1180.17, 6800.00, 6800.00, 0.00, '30-71234567-9', 'Mostaza SA', '74062796855523', '2026-10-03', 'Emitida'),
(50, 'FAC-B-0001-00000050', 'Factura B', 63, 13, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-09-25 08:09:50', 'efectivo', 9800.00, 0.00, 9090.91, 1909.09, 11000.00, 11000.00, 0.00, '30-71234567-9', 'Mostaza SA', '74068876237003', '2026-10-05', 'Emitida'),
(51, 'FAC-B-0001-00000051', 'Factura B', 64, 13, 13, NULL, 'Consumidor Final', '0', '2026-09-25 08:20:17', 'efectivo', 10200.00, 0.00, 8429.75, 1770.25, 10200.00, 10200.00, 0.00, '30-71234567-9', 'Mostaza SA', '74071759522623', '2026-10-05', 'Emitida'),
(52, 'FAC-B-0001-00000052', 'Factura B', 65, 14, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-10-02 09:21:24', 'efectivo', 11800.00, 2643.00, 9047.11, 1899.89, 10947.00, 20000.00, 9053.00, '30-71234567-9', 'Mostaza SA', '74036230252933', '2026-10-12', 'Emitida'),
(53, 'FAC-B-0001-00000053', 'Factura B', 66, 14, 1, 4, 'Beto18 G├│mez', '3704848212', '2026-10-02 09:29:04', 'efectivo', 9800.00, 0.00, 8099.17, 1700.83, 9800.00, 9800.00, 0.00, '30-71234567-9', 'Mostaza SA', '74089814919783', '2026-10-12', 'Emitida'),
(54, 'FAC-B-0001-00000054', 'Factura B', 67, 14, 17, NULL, 'Consumidor Final', '0', '2026-10-02 09:29:55', 'efectivo', 5200.00, 0.00, 4297.52, 902.48, 5200.00, 5200.00, 0.00, '30-71234567-9', 'Mostaza SA', '74069349686883', '2026-10-12', 'Emitida'),
(55, 'FAC-B-0001-00000055', 'Factura B', 68, 14, 13, NULL, 'Consumidor Final', '0', '2026-10-02 09:40:50', 'efectivo', 18100.00, 0.00, 14958.68, 3141.32, 18100.00, 18100.00, 0.00, '30-71234567-9', 'Mostaza SA', '74044141997313', '2026-10-12', 'Emitida');

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
(7, 'Lechuga (kg)', 900.00, 4, 75, 50, 'Verduler├¡a'),
(8, 'Tomate (kg)', 1100.00, 4, 60, 50, 'Verduler├¡a'),
(9, 'Papa para fritas (kg)', 700.00, 4, 410, 150, 'Acompa├▒amiento'),
(10, 'Cebolla (kg)', 650.00, 4, 95, 40, 'Verduler├¡a'),
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
(1, 1, 1, 4, 'ocupada', 1),
(2, 2, 1, 4, 'disponible', 1),
(3, 3, 1, 6, 'disponible', 1),
(4, 4, 1, 4, 'disponible', 1),
(5, 5, 1, 4, 'disponible', 1),
(6, 6, 1, 6, 'disponible', 1),
(7, 7, 1, 4, 'disponible', 1),
(8, 8, 1, 4, 'disponible', 1),
(9, 9, 2, 2, 'disponible', 1),
(10, 10, 2, 2, 'disponible', 1),
(11, 11, 2, 4, 'ocupada', 1),
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
-- Estructura de tabla para la tabla `pedido_auditoria`
--

CREATE TABLE `pedido_auditoria` (
  `idauditoria` int(11) NOT NULL,
  `idpedido` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `rol_responsable` varchar(50) NOT NULL,
  `idempleado` int(11) DEFAULT NULL,
  `nombre_responsable` varchar(100) NOT NULL,
  `detalles` text DEFAULT NULL,
  `ip_origen` varchar(45) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedido_auditoria`
--

INSERT INTO `pedido_auditoria` (`idauditoria`, `idpedido`, `estado_anterior`, `estado_nuevo`, `accion`, `rol_responsable`, `idempleado`, `nombre_responsable`, `detalles`, `ip_origen`, `fecha_hora`) VALUES
(1, 46, 'Recibido', 'Recibido', 'Venta Registrada en Caja', 'Cajero', 13, 'Alberto Emanuel Gómez', 'Pago en Efectivo $15000', '127.0.0.1', '2026-09-16 00:41:12'),
(2, 46, 'Recibido', 'En Preparación', 'Comanda Ingresada a Cocina', 'Cocinero', 15, 'Martín Paredes', 'Iniciada preparación en estación plancha', '127.0.0.1', '2026-09-16 00:41:12'),
(3, 46, 'En Preparación', 'Listo para Despacho', 'Elaboración Completada', 'Cocinero', 15, 'Martín Paredes', 'Comanda lista y empaquetada para delivery', '127.0.0.1', '2026-09-16 00:41:12'),
(4, 46, 'Listo para Despacho', 'En Camino', 'Despacho y Salida de Reparto', 'Repartidor', 19, 'Ramiro González', 'En viaje en Moto Honda Wave (Patente A123BCD)', '127.0.0.1', '2026-09-16 00:41:13'),
(5, 46, 'En Camino', 'Entregado', 'Entrega Confirmada al Cliente', 'Repartidor', 19, 'Ramiro González', 'Entregado con firma conforme en puerta', '127.0.0.1', '2026-09-16 00:41:13'),
(6, 47, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 15, 'Alberto Gómez', 'Terminal: Caja 1 | Método: efectivo | Total: $12500 | Modalidad: takeaway', '127.0.0.1', '2026-09-16 02:39:09'),
(7, 48, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 15, 'Alberto Gómez', 'Terminal: Caja 1 | Método: efectivo | Total: $12500 | Modalidad: takeaway', '127.0.0.1', '2026-09-16 02:40:20'),
(8, 49, 'Recibido', 'Recibido', 'Venta Registrada en Caja 2 - Express / Salón', 'Cajero', 16, 'Romina Castro', 'Terminal: Caja 2 | Método: mercadopago | Total: $8900 | Modalidad: salon | Mesa 5', '127.0.0.1', '2026-09-16 02:40:20'),
(9, 50, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 15, 'Alberto Gómez', 'Terminal: Caja 1 | Método: efectivo | Total: $12500 | Modalidad: takeaway', '127.0.0.1', '2026-09-16 02:42:06'),
(10, 51, 'Recibido', 'Recibido', 'Venta Registrada en Caja 2 - Express / Salón', 'Cajero', 16, 'Romina Castro', 'Terminal: Caja 2 | Método: mercadopago | Total: $8900 | Modalidad: salon | Mesa 5', '127.0.0.1', '2026-09-16 02:42:06'),
(11, 50, 'Recibido', 'En Preparacion', 'Comanda En Preparacion', 'Cocinero', 11, 'Martín Paredes', 'Comanda elaborada por Martín Paredes', '127.0.0.1', '2026-09-16 02:42:07'),
(12, 50, 'En Preparacion', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 11, 'Martín Paredes', 'Comanda elaborada por Martín Paredes', '127.0.0.1', '2026-09-16 02:42:07'),
(13, 50, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 13, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '127.0.0.1', '2026-09-16 02:42:07'),
(14, 50, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 13, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '127.0.0.1', '2026-09-16 02:42:07'),
(15, 52, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 15, 'Alberto Gómez', 'Terminal: Caja 1 | Método: efectivo | Total: $12500 | Modalidad: takeaway', '127.0.0.1', '2026-09-16 02:43:25'),
(16, 53, 'Recibido', 'Recibido', 'Venta Registrada en Caja 2 - Express / Salón', 'Cajero', 16, 'Romina Castro', 'Terminal: Caja 2 | Método: mercadopago | Total: $8900 | Modalidad: salon | Mesa 5', '127.0.0.1', '2026-09-16 02:43:25'),
(17, 52, 'Recibido', 'En Preparacion', 'Comanda En Preparacion', 'Cocinero', 11, 'Martín Paredes', 'Comanda elaborada por Martín Paredes', '127.0.0.1', '2026-09-16 02:43:25'),
(18, 52, 'En Preparacion', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 11, 'Martín Paredes', 'Comanda elaborada por Martín Paredes', '127.0.0.1', '2026-09-16 02:43:25'),
(19, 52, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 13, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '127.0.0.1', '2026-09-16 02:43:25'),
(20, 52, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 13, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '127.0.0.1', '2026-09-16 02:43:26'),
(21, 54, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: delivery | Pago: efectivo | Total: $7500', '::1', '2026-09-18 09:45:30'),
(22, 55, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 13, 'G├│mez Alberto Emanuel', 'Terminal: Caja 1 | Método: efectivo | Total: $8400 | Modalidad: salon | Mesa 2', '192.168.1.237', '2026-09-18 21:46:07'),
(23, 56, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 13, 'G├│mez Alberto Emanuel', 'Terminal: Caja 1 | Método: efectivo | Total: $8400 | Modalidad: salon | Mesa 2', '192.168.1.237', '2026-09-18 21:46:22'),
(24, 3, 'Recibido', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '192.168.1.43', '2026-09-23 03:11:01'),
(25, 26, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.215', '2026-09-23 08:29:33'),
(26, 3, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-09-23 08:29:55'),
(27, 27, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-09-23 08:29:58'),
(28, 3, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.215', '2026-09-23 08:30:19'),
(29, 57, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: delivery | Pago: efectivo | Total: $11000', '::1', '2026-09-23 08:35:19'),
(30, 57, 'Recibido', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '10.0.12.121', '2026-09-23 08:35:57'),
(31, 57, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-09-23 08:36:52'),
(32, 57, 'En Camino', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-09-23 08:36:57'),
(33, 57, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.215', '2026-09-23 08:37:23'),
(34, 30, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-09-23 08:39:25'),
(35, 30, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.215', '2026-09-23 08:39:31'),
(36, 27, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.215', '2026-09-23 08:39:34'),
(37, 58, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: salon | Pago: mercadopago | Total: $4600', '::1', '2026-09-23 08:44:11'),
(38, 59, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: salon | Pago: efectivo | Total: $5200', '::1', '2026-09-23 08:46:07'),
(39, 59, 'Recibido', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '10.0.12.121', '2026-09-23 08:46:32'),
(40, 60, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: delivery | Pago: mercadopago | Total: $9840', '::1', '2026-09-23 09:00:59'),
(41, 60, 'Recibido', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '10.0.12.121', '2026-09-23 09:01:59'),
(42, 60, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-09-23 09:16:29'),
(43, 60, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.215', '2026-09-23 09:17:55'),
(44, 61, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: delivery | Pago: efectivo | Total: $17346', '::1', '2026-09-23 10:33:31'),
(45, 61, 'Recibido', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '10.0.12.215', '2026-09-23 10:35:03'),
(46, 61, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.121', '2026-09-23 10:38:48'),
(47, 61, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.12.121', '2026-09-23 10:39:02'),
(48, 62, 'Recibido', 'Recibido', 'Comanda de Salón tomada en Mesa #6', 'Mesero', 17, 'Franco Morales', 'Terminal: Caja 1 | Método: efectivo | Total: $6800 | Modalidad: salon | Mesa 6', '10.0.12.121', '2026-09-23 10:43:50'),
(49, 63, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: delivery | Pago: efectivo | Total: $11000', '::1', '2026-09-25 08:09:50'),
(50, 63, 'Recibido', 'En Preparación', 'Comanda En Preparación', 'Cocinero', 16, 'Lucas Silveira', 'Comanda tomada para elaboración por Lucas Silveira', '10.0.12.215', '2026-09-25 08:13:26'),
(51, 63, 'En Preparación', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 16, 'Lucas Silveira', 'Comanda elaborada y empaquetada por Lucas Silveira', '10.0.12.215', '2026-09-25 08:13:38'),
(52, 63, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 19, 'Ramiro González', 'El repartidor Ramiro González retiró el pedido y se dirige al domicilio del cliente.', '10.0.9.37', '2026-09-25 08:14:15'),
(53, 63, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 19, 'Ramiro González', 'Pedido entregado con éxito por Ramiro González.', '10.0.9.37', '2026-09-25 08:15:29'),
(54, 24, 'En Preparación', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 16, 'Lucas Silveira', 'Comanda elaborada y empaquetada por Lucas Silveira', '10.0.12.215', '2026-09-25 08:17:10'),
(55, 64, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 13, 'G├│mez Alberto Emanuel', 'Terminal: Caja 1 | Método: efectivo | Total: $10200 | Modalidad: salon | Mesa 2', '10.0.5.77', '2026-09-25 08:20:17'),
(56, 65, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: delivery | Pago: efectivo | Total: $10947', '::1', '2026-10-02 09:21:24'),
(57, 65, 'Recibido', 'En Preparación', 'Comanda En Preparación', 'Cocinero', 15, 'Martín Paredes', 'Comanda tomada para elaboración por Martín Paredes', '10.0.12.121', '2026-10-02 09:23:06'),
(58, 65, 'En Preparación', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '10.0.12.121', '2026-10-02 09:24:15'),
(59, 65, 'Listo para Despacho', 'En Camino', 'Despachado y en Viaje a Domicilio', 'Repartidor', 20, 'Lucas Benítez', 'El repartidor Lucas Benítez retiró el pedido y se dirige al domicilio del cliente.', '10.0.12.215', '2026-10-02 09:25:13'),
(60, 65, 'En Camino', 'Entregado', 'Entrega Finalizada en Puerta', 'Repartidor', 20, 'Lucas Benítez', 'Pedido entregado con éxito por Lucas Benítez.', '10.0.12.215', '2026-10-02 09:25:42'),
(61, 66, 'Recibido', 'Recibido', 'Pedido Creado en Tienda Web', 'Cliente Web', NULL, 'Beto18 G├│mez', 'Pedido ingresado vía Tienda Online | Modalidad: salon | Pago: efectivo | Total: $9800', '::1', '2026-10-02 09:29:04'),
(62, 67, 'Recibido', 'Recibido', 'Comanda de Salón tomada en Mesa #1', 'Mesero', 17, 'Franco Morales', 'Terminal: Caja 1 | Método: efectivo | Total: $5200 | Modalidad: salon | Mesa 1', '10.0.9.37', '2026-10-02 09:29:55'),
(63, 66, 'Recibido', 'En Preparación', 'Comanda En Preparación', 'Cocinero', 15, 'Martín Paredes', 'Comanda tomada para elaboración por Martín Paredes', '10.0.12.121', '2026-10-02 09:31:26'),
(64, 66, 'En Preparación', 'Listo para Despacho', 'Comanda Listo para Despacho', 'Cocinero', 15, 'Martín Paredes', 'Comanda elaborada y empaquetada por Martín Paredes', '10.0.12.121', '2026-10-02 09:31:29'),
(65, 66, 'Listo para Despacho', 'Entregado', 'Comanda Entregado', 'Mesero', 17, 'Franco Morales', 'Comanda servida en mesa por Franco Morales', '10.0.9.37', '2026-10-02 09:31:45'),
(66, 68, 'Recibido', 'Recibido', 'Venta Registrada en Caja 1 - Principal / Mostrador', 'Cajero', 13, 'G├│mez Alberto Emanuel', 'Terminal: Caja 1 | Método: efectivo | Total: $18100 | Modalidad: salon | Mesa 11', '10.0.5.77', '2026-10-02 09:40:50');

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
  `numero_pedido_usuario` int(11) DEFAULT NULL,
  `motivo_cancelacion` varchar(255) DEFAULT NULL,
  `cancelado_por` varchar(50) DEFAULT NULL,
  `hora_completado_cocina` datetime DEFAULT NULL,
  `idempleado_cajero` int(11) DEFAULT NULL,
  `cajero_nombre` varchar(100) DEFAULT NULL,
  `idempleado_mesero` int(11) DEFAULT NULL,
  `mesero_nombre` varchar(100) DEFAULT NULL,
  `idempleado_cocinero` int(11) DEFAULT NULL,
  `cocinero_nombre` varchar(100) DEFAULT NULL,
  `idempleado_repartidor` int(11) DEFAULT NULL,
  `hora_recibido` datetime DEFAULT NULL,
  `hora_cocina_inicio` datetime DEFAULT NULL,
  `hora_cocina_listo` datetime DEFAULT NULL,
  `hora_despacho_camino` datetime DEFAULT NULL,
  `hora_entregado` datetime DEFAULT NULL,
  `duracion_cocina_minutos` int(11) DEFAULT NULL,
  `duracion_entrega_minutos` int(11) DEFAULT NULL,
  `duracion_total_minutos` int(11) DEFAULT NULL,
  `numero_caja` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido_web`
--

INSERT INTO `pedido_web` (`idpedido`, `idusuario`, `fecha`, `total`, `estado`, `tipo_entrega`, `direccion_envio`, `telefono_contacto`, `sucursal`, `numero_mesa`, `metodo_pago`, `estado_pago`, `subtotal`, `costo_envio`, `descuento`, `codigo_cupon`, `propina`, `puntos_usados`, `puntos_ganados`, `paga_con`, `vuelto`, `notas`, `comprobante_transferencia`, `repartidor_asignado`, `idempleado`, `idarqueo`, `comprobante_archivo`, `latitud`, `longitud`, `referencia_entrega`, `numero_pedido_usuario`, `motivo_cancelacion`, `cancelado_por`, `hora_completado_cocina`, `idempleado_cajero`, `cajero_nombre`, `idempleado_mesero`, `mesero_nombre`, `idempleado_cocinero`, `cocinero_nombre`, `idempleado_repartidor`, `hora_recibido`, `hora_cocina_inicio`, `hora_cocina_listo`, `hora_despacho_camino`, `hora_entregado`, `duracion_cocina_minutos`, `duracion_entrega_minutos`, `duracion_total_minutos`, `numero_caja`) VALUES
(2, 5, '2026-07-31 08:28:59', 11800.00, 'Cancelado', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, 'Ramiro Gonz├ílez', NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Cliente no atiende en domicilio', 'Delivery (Ramiro González)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(3, 5, '2026-07-31 09:53:19', 40100.00, 'Entregado', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, 'Ramiro González', NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, '2026-09-23 03:11:01', NULL, NULL, NULL, NULL, 15, 'Martín Paredes', 19, NULL, NULL, '2026-09-23 03:11:01', '2026-09-23 08:29:55', '2026-09-23 08:30:19', 77357, 1, 77677, 1),
(4, 4, '2026-07-31 10:01:04', 9800.00, 'Recibido', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(5, 4, '2026-08-17 19:59:16', 5200.00, 'Entregado', 'salon', 'B┬░ El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 4, 'efectivo', 'Aprobado', 5200.00, 0.00, 0.00, NULL, 0.00, 0, 52, 5200.00, 0.00, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(6, 4, '2026-08-18 08:27:38', 8755.00, 'Entregado', 'salon', 'B┬░ El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 4, 'efectivo', 'Aprobado', 10300.00, 0.00, 2060.00, 'DIBU20', 515.00, 0, 87, 8755.00, 0.00, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(7, 4, '2026-08-19 11:26:55', 8690.00, 'Entregado', 'salon', 'B┬░ El Quebranto mz1 cs 35', '03704848212', 'Mostaza Nueva C├│rdoba (Bv. Illia 450)', 1, 'efectivo', 'Aprobado', 7900.00, 0.00, 0.00, NULL, 790.00, 0, 86, 8690.00, 0.00, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(10, NULL, '2026-08-25 01:22:58', 23800.00, 'Entregado', 'salon', NULL, NULL, 'Mostaza Central', 4, 'efectivo', 'Aprobado', 23800.00, 0.00, 0.00, NULL, 0.00, 0, 0, 30000.00, 6200.00, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(11, 4, '2026-08-25 08:14:52', 31345.00, 'Recibido', 'salon', 'B┬░ El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 7, 'efectivo', 'Aprobado', 31800.00, 0.00, 5225.00, 'DIBU20', 4770.00, 225, 313, 31345.00, 0.00, '', '', NULL, NULL, 1, NULL, NULL, NULL, NULL, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(12, 4, '2026-08-25 08:22:20', 7392.00, 'Recibido', 'salon', 'B┬░ El Quebranto mz1 cs 35', '03704848212', 'Mostaza Palermo (Av. Santa Fe 3200)', 13, 'efectivo', 'Aprobado', 6700.00, 0.00, 313.00, NULL, 1005.00, 313, 73, 7392.00, 0.00, '', '', NULL, NULL, 1, NULL, NULL, NULL, NULL, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(13, 7, '2026-08-25 08:27:39', 17850.00, 'Recibido', 'salon', 'El Porvenir', '3704847532', 'Mostaza Palermo (Av. Santa Fe 3200)', 17, 'efectivo', 'Aprobado', 17000.00, 0.00, 0.00, NULL, 850.00, 0, 178, 17850.00, 0.00, '', '', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(14, NULL, '2026-08-25 08:50:55', 9800.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 10, 'efectivo', 'Aprobado', 9800.00, 0.00, 0.00, NULL, 0.00, 0, 0, 20000.00, 10200.00, 'Venta POS Escritorio (SALON)', NULL, NULL, 13, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(15, NULL, '2026-08-25 17:55:45', 18500.00, 'Recibido', 'delivery', 'Av. Santa Fe 3400, CABA', '1155443322', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 18500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 20000.00, 1500.00, 'Prueba Efectivo con Vuelto', NULL, NULL, NULL, 1, NULL, -34.58760000, -58.41230000, 'Piso 3 Depto A (timbre negro)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(16, NULL, '2026-08-25 17:55:45', 24600.00, 'Recibido', 'delivery', 'Av. Callao 1450, CABA', '1166778899', 'Mostaza Central', NULL, 'transferencia', 'Aprobado', 24600.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, 'Prueba Transferencia', 'TRANSF-OP-98765432', NULL, NULL, 1, 'uploads/comprobantes/comp_test_123.jpg', -34.59320000, -58.39120000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(17, NULL, '2026-08-25 17:55:45', 31200.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Palermo', NULL, 'tarjeta', 'Aprobado', 31200.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, 'Tarjeta en 3 cuota(s) ┬À Auth: #9A8B7C6D', NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(19, 4, '2026-08-25 19:46:10', 10000.00, 'Recibido', 'delivery', 'Av Santa Fe 1200, CABA', '1122334455', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 10000.00, 0.00, 0.00, NULL, 0.00, 0, 100, 10000.00, 0.00, 'Prueba final', NULL, NULL, NULL, 1, NULL, -34.60370000, -58.38150000, 'Piso 1', 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(20, 4, '2026-08-25 19:46:41', 22700.00, 'Recibido', 'delivery', 'Av. Santa Fe 3400', '1145678901', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 21500.00, 1200.00, 0.00, NULL, 0.00, 0, 215, 25000.00, 3500.00, 'Test checkout efectivo', NULL, NULL, NULL, 1, NULL, -34.58700000, -58.41200000, 'Piso 2 B', 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(21, 4, '2026-08-25 19:46:41', 20100.00, 'Recibido', 'delivery', 'Av. Callao 1500', '1145678901', 'Mostaza Central', NULL, 'transferencia', 'Aprobado', 18900.00, 1200.00, 0.00, NULL, 0.00, 0, 189, 18900.00, 0.00, 'Test checkout transferencia', 'TRF-123456', NULL, NULL, 1, NULL, -34.59300000, -58.39100000, 'Piso 2 B', 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(22, 4, '2026-08-25 19:46:41', 14500.00, 'Recibido', 'takeaway', '', '1145678901', 'Mostaza Palermo', NULL, 'mercadopago', 'Aprobado', 14500.00, 0.00, 0.00, NULL, 0.00, 0, 145, 14500.00, 0.00, 'Test checkout mercadopago', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(23, 4, '2026-08-25 19:46:41', 32000.00, 'Entregado', 'salon', '', '1145678901', 'Mostaza Central', 3, 'tarjeta', 'Aprobado', 32000.00, 0.00, 0.00, NULL, 0.00, 0, 320, 32000.00, 0.00, 'Test checkout tarjeta', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(24, 4, '2026-09-22 23:30:43', 9600.00, 'Listo para Despacho', 'delivery', 'Fontana 1250, Formosa Capital', '3704-667788', 'Mostaza Central (Av. Corrientes 1250)', 1, 'efectivo', 'Aprobado', 2100.00, 1200.00, 0.00, NULL, 105.00, 0, 34, 10000.00, 400.00, 'Tarjeta en 1 cuota(s) ┬À Auth: #705BE046', '', NULL, NULL, 1, NULL, -26.18000000, -58.17000000, 'Timbre Mostaza', 12, NULL, NULL, '2026-09-25 08:17:10', NULL, NULL, NULL, NULL, 16, 'Lucas Silveira', NULL, NULL, '2026-09-22 23:30:43', '2026-09-25 08:17:10', NULL, NULL, 3406, NULL, NULL, 1),
(25, NULL, '2026-08-26 00:55:07', 7900.00, 'Recibido', 'salon', 'Entrega en Domicilio Mostaza', NULL, 'Mostaza Central', 8, 'mercadopago', 'Aprobado', 7900.00, 0.00, 0.00, NULL, 0.00, 0, 0, 7900.00, 0.00, 'Venta POS Escritorio (SALON)', NULL, NULL, 13, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(26, 4, '2026-09-22 23:30:43', 12500.00, 'Entregado', 'delivery', 'Av. González Lelong 850, Formosa Capital', '3704-554433', 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 21500.00, 1200.00, 0.00, NULL, 500.00, 0, 215, 15000.00, 2500.00, 'Test checkout efectivo', NULL, 'Ramiro González', NULL, 1, NULL, -26.17200000, -58.17400000, 'Portón negro de rejas - Casa 12', 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 19, NULL, NULL, NULL, '2026-09-22 23:30:43', '2026-09-23 08:29:33', NULL, 538, 538, 1),
(27, 4, '2026-09-22 23:30:43', 14200.00, 'Entregado', 'delivery', 'Av. Gutnisky 2200, Formosa Capital', '3704-112244', 'Mostaza Central', NULL, 'mercadopago', 'Aprobado', 18900.00, 1200.00, 0.00, NULL, 0.00, 0, 189, 14200.00, 0.00, 'Test checkout transferencia', 'TRF-123456', 'Ramiro González', NULL, 1, NULL, -26.19540000, -58.18820000, 'Frente al Hospital de Alta Complejidad', 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 19, NULL, NULL, '2026-09-22 23:30:43', '2026-09-23 08:29:58', '2026-09-23 08:39:34', NULL, 9, 548, 1),
(28, 4, '2026-08-26 01:08:02', 14500.00, 'Recibido', 'takeaway', '', '1145678901', 'Mostaza Palermo', NULL, 'mercadopago', 'Aprobado', 14500.00, 0.00, 0.00, NULL, 0.00, 0, 145, 14500.00, 0.00, 'Test checkout mercadopago', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(29, 4, '2026-08-26 01:08:02', 32000.00, 'Entregado', 'salon', '', '1145678901', 'Mostaza Central', 3, 'tarjeta', 'Aprobado', 32000.00, 0.00, 0.00, NULL, 0.00, 0, 320, 32000.00, 0.00, 'Test checkout tarjeta', NULL, NULL, NULL, 1, NULL, NULL, NULL, 'Piso 2 B', 16, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(30, 4, '2026-09-22 23:30:43', 8900.00, 'Entregado', 'delivery', 'Av. 25 de Mayo 450, Formosa Capital', '3704-889900', 'Mostaza Formosa Centro (Av. 25 de Mayo 150)', 1, 'efectivo', 'Aprobado', 10300.00, 1200.00, 0.00, NULL, 0.00, 0, 115, 10000.00, 1100.00, '', '', 'Ramiro González', NULL, 1, NULL, -26.18340000, -58.17720000, 'Piso 2 Depto B - Timbre Blanco', 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 19, NULL, NULL, '2026-09-22 23:30:43', '2026-09-23 08:39:25', '2026-09-23 08:39:31', NULL, 1, 548, 1),
(31, 4, '2026-08-26 17:38:44', 10965.00, 'Entregado', 'delivery', 'Palmetler, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro (Av. 25 de Mayo 150)', 1, 'transferencia', 'Aprobado', 9300.00, 1200.00, 0.00, NULL, 465.00, 0, 109, NULL, NULL, '', '', 'Franco Morales', NULL, 1, NULL, -49.54322300, -23.91004200, '', 18, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(32, 4, '2026-08-27 01:06:00', 15700.00, 'Entregado', 'delivery', 'Av. 25 de Mayo 450, Formosa Capital', '3704123456', 'Mostaza Formosa Centro', NULL, 'efectivo', 'Aprobado', 14500.00, 1200.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, 'Alberto G├│mez', NULL, NULL, NULL, -26.18340000, -58.17720000, 'Edificio Torre 1 Depto 4B', 17, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(33, 4, '2026-09-14 02:36:13', 15000.00, 'Recibido', 'takeaway', '', '', 'Mostaza Formosa Centro', NULL, 'mercadopago', 'Aprobado', 15000.00, 0.00, 0.00, NULL, 0.00, 0, 150, NULL, NULL, 'Horario retiro: 15 a 20 minutos | Mercado Pago Aprobado · Operación #MP-889319726', '', NULL, NULL, 1, NULL, NULL, NULL, '', 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(34, 4, '2026-09-14 02:50:55', 4000.00, 'Entregado', 'delivery', 'Palmetler, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro (Av. 25 de Mayo 150)', 1, 'mercadopago', 'Aprobado', 2800.00, 1200.00, 0.00, NULL, 0.00, 0, 40, NULL, NULL, 'Mercado Pago Aprobado · Operación #MP-406716978', '987654432', 'Felipe', NULL, 1, NULL, -26.18520000, -58.17560000, '', 21, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(36, 4, '2026-09-14 03:11:20', 2800.00, 'Entregado', 'salon', 'Palmetler, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro (Av. 25 de Mayo 150)', 1, 'tarjeta', 'Aprobado', 2800.00, 0.00, 0.00, NULL, 0.00, 0, 28, NULL, NULL, 'Tarjeta en 1 cuota(s) · Auth: #AUTH-VISA-793995', '', NULL, NULL, 1, NULL, -26.18520000, -58.17560000, '', 22, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(37, 4, '2026-09-14 09:38:14', 7900.00, 'Listo para Despacho', 'takeaway', 'Palmetler, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro (Av. 25 de Mayo 150)', 1, 'efectivo', 'Pendiente de Cobro', 7900.00, 0.00, 0.00, NULL, 0.00, 0, 79, 7900.00, 0.00, 'Horario retiro: Lo antes posible (15 - 20 min) | Abono en efectivo contra entrega / en mostrador', '', NULL, NULL, 1, NULL, -26.18520000, -58.17560000, '', 23, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(38, NULL, '2026-09-15 01:32:45', 12500.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'mercadopago', 'Aprobado', 12500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 12500.00, 0.00, 'Venta POS Mostrador/Takeaway | Mercado Pago Aprobado · Operación #MP-987654321', 'Mercado Pago: #MP-987654321', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(39, NULL, '2026-09-15 01:32:45', 14000.00, 'Entregado', 'salon', NULL, NULL, 'Mostaza Central', 4, 'tarjeta', 'Aprobado', 14000.00, 0.00, 0.00, NULL, 0.00, 0, 0, 14000.00, 0.00, 'Venta POS Salón Mesa 4 | Tarjeta POS en 3 cuota(s) · Auth: #AUTH-POS-778899', NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(40, NULL, '2026-09-15 01:32:45', 5500.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'transferencia', 'Aprobado', 5500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 5500.00, 0.00, 'Venta POS Mostrador/Takeaway | Transferencia Bancaria · Comprobante: TRANSF-20260915-1234', 'TRANSF-20260915-1234', NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(41, 4, '2026-09-15 08:05:07', 9400.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'mercadopago', 'Aprobado', 8200.00, 1200.00, 0.00, NULL, 0.00, 0, 94, NULL, NULL, 'Mercado Pago Aprobado · Operación #MP-863372312', 'Mercado Pago: #MP-863372312', 'Franco Morales', NULL, 1, NULL, -26.14465100, -58.15705000, '', 24, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(42, NULL, '2026-09-15 09:35:57', 6500.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'tarjeta', 'Aprobado', 6500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 6500.00, 0.00, 'Venta generada en Terminal Cajero POS | Tarjeta (terminada en 9010 · JUAN PEREZ) en 3 cuota(s) · Auth: #AUTH-VISA-987654', NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(43, 4, '2026-09-15 10:21:18', 3268.00, 'Recibido', 'salon', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro', 5, 'efectivo', 'Pendiente de Cobro', 3800.00, 0.00, 722.00, NULL, 190.00, 722, 32, 3268.00, 0.00, 'Abono en efectivo contra entrega / en mostrador', '', NULL, NULL, 1, NULL, -26.18520000, -58.17560000, '', 25, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(44, 4, '2026-09-15 10:30:19', 19360.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro', 1, 'efectivo', 'Pendiente de Cobro', 22700.00, 1200.00, 4540.00, 'DIBU20', 0.00, 0, 193, 19360.00, 0.00, 'Abono en efectivo contra entrega / en mostrador', '', 'Franco Morales', NULL, 1, NULL, -26.18520000, -58.17560000, '', 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(45, 4, '2026-09-15 10:43:07', 19375.00, 'Entregado', 'salon', 'B┬░ El Quebranto mz1 cs 35', '3704848212', 'Mostaza Formosa Centro', 1, 'efectivo', 'Pendiente de Cobro', 19600.00, 0.00, 225.00, NULL, 0.00, 225, 193, 20000.00, 625.00, 'Abono en efectivo contra entrega / en mostrador', '', NULL, NULL, 1, NULL, -26.18520000, -58.17560000, '', 27, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(46, NULL, '2026-09-22 22:30:43', 15000.00, 'Entregado', 'delivery', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 0.00, 0.00, 0.00, NULL, 0.00, 0, 0, NULL, NULL, NULL, NULL, 'Ramiro González', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-16 00:41:12', 13, 'Alberto Emanuel Gómez', NULL, NULL, 15, 'Martín Paredes', 19, '2026-09-16 00:41:12', '2026-09-16 00:41:12', '2026-09-16 00:41:12', '2026-09-16 00:41:13', '2026-09-22 22:30:43', 1, 1, 1, 1),
(47, NULL, '2026-09-16 02:39:09', 12500.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 12500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 12500.00, 0.00, 'Venta generada en Terminal Caja 1 - Principal / Mostrador', NULL, NULL, 15, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, 'Alberto Gómez', NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:39:09', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(48, NULL, '2026-09-16 02:40:20', 12500.00, 'Recibido', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 12500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 12500.00, 0.00, 'Venta generada en Terminal Caja 1 - Principal / Mostrador', NULL, NULL, 15, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 15, 'Alberto Gómez', NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:40:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(49, NULL, '2026-09-16 02:40:20', 8900.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 5, 'mercadopago', 'Aprobado', 8900.00, 0.00, 0.00, NULL, 0.00, 0, 0, 8900.00, 0.00, 'Venta generada en Terminal Caja 2 - Express / Salón | Mercado Pago Aprobado · Operación #MP-571498949', 'Mercado Pago: #MP-571498949', NULL, 16, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 16, 'Romina Castro', NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:40:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(50, NULL, '2026-09-16 02:42:06', 12500.00, 'Entregado', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 12500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 12500.00, 0.00, 'Venta generada en Terminal Caja 1 - Principal / Mostrador', NULL, 'Ramiro González', 15, 9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:42:07', 15, 'Alberto Gómez', NULL, NULL, 11, 'Martín Paredes', 13, '2026-09-16 02:42:06', '2026-09-16 02:42:07', '2026-09-16 02:42:07', '2026-09-16 02:42:07', '2026-09-16 02:42:07', 1, 1, 1, 1),
(51, NULL, '2026-09-16 02:42:06', 8900.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 5, 'mercadopago', 'Aprobado', 8900.00, 0.00, 0.00, NULL, 0.00, 0, 0, 8900.00, 0.00, 'Venta generada en Terminal Caja 2 - Express / Salón | Mercado Pago Aprobado · Operación #MP-985821434', 'Mercado Pago: #MP-985821434', NULL, 16, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 16, 'Romina Castro', NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:42:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(52, NULL, '2026-09-16 02:43:25', 12500.00, 'Entregado', 'takeaway', NULL, NULL, 'Mostaza Central', NULL, 'efectivo', 'Aprobado', 12500.00, 0.00, 0.00, NULL, 0.00, 0, 0, 12500.00, 0.00, 'Venta generada en Terminal Caja 1 - Principal / Mostrador', NULL, 'Ramiro González', 15, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:43:25', 15, 'Alberto Gómez', NULL, NULL, 11, 'Martín Paredes', 13, '2026-09-16 02:43:25', '2026-09-16 02:43:25', '2026-09-16 02:43:25', '2026-09-16 02:43:25', '2026-09-16 02:43:26', 1, 1, 1, 1),
(53, NULL, '2026-09-16 02:43:25', 8900.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 5, 'mercadopago', 'Aprobado', 8900.00, 0.00, 0.00, NULL, 0.00, 0, 0, 8900.00, 0.00, 'Venta generada en Terminal Caja 2 - Express / Salón | Mercado Pago Aprobado · Operación #MP-931367772', 'Mercado Pago: #MP-931367772', NULL, 16, 12, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 16, 'Romina Castro', NULL, NULL, NULL, NULL, NULL, '2026-09-16 02:43:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2),
(54, 4, '2026-09-22 23:30:43', 7500.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'efectivo', 'Pendiente de Cobro', 6300.00, 1200.00, 0.00, NULL, 0.00, 0, 75, 10000.00, 2500.00, 'Abono en efectivo contra entrega / en mostrador', '', 'Ramiro González', NULL, 13, NULL, -26.14471500, -58.15662100, '', 28, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-18 09:45:30', NULL, NULL, NULL, '2026-09-22 23:30:43', NULL, NULL, NULL, 1),
(55, NULL, '2026-09-18 21:46:07', 8400.00, 'Entregado', 'salon', NULL, NULL, 'Mostaza Central', 2, 'efectivo', 'Aprobado', 8400.00, 0.00, 0.00, NULL, 0.00, 0, 0, 10000.00, 1600.00, 'Venta POS Mostrador/Salón (SALON)', NULL, NULL, 13, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 13, 'G├│mez Alberto Emanuel', NULL, NULL, NULL, NULL, NULL, '2026-09-18 21:46:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(56, NULL, '2026-09-18 21:46:22', 8400.00, 'Entregado', 'salon', NULL, NULL, 'Mostaza Central', 2, 'efectivo', 'Aprobado', 8400.00, 0.00, 0.00, NULL, 0.00, 0, 0, 10000.00, 1600.00, 'Venta POS Mostrador/Salón (SALON)', NULL, NULL, 13, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 13, 'G├│mez Alberto Emanuel', NULL, NULL, NULL, NULL, NULL, '2026-09-18 21:46:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(57, 4, '2026-09-23 08:35:19', 11000.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'efectivo', 'Pendiente de Cobro', 9800.00, 1200.00, 0.00, NULL, 0.00, 0, 110, 11000.00, 0.00, 'Abono en efectivo contra entrega / en mostrador', '', 'Ramiro González', NULL, 13, NULL, -26.14462000, -58.15690800, '', 29, NULL, NULL, '2026-09-23 08:35:57', NULL, NULL, NULL, NULL, 15, 'Martín Paredes', 19, '2026-09-23 08:35:19', NULL, '2026-09-23 08:35:57', '2026-09-23 08:36:57', '2026-09-23 08:37:23', 1, 1, 2, 1),
(58, 4, '2026-09-23 08:44:11', 4600.00, 'Entregado', 'salon', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro', 1, 'mercadopago', 'Aprobado', 4600.00, 0.00, 0.00, NULL, 0.00, 0, 46, NULL, NULL, 'Mercado Pago Aprobado · Operación #MP-536335357', 'Mercado Pago: #MP-536335357', NULL, NULL, 13, NULL, -26.18520000, -58.17560000, '', 30, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-23 08:44:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(59, 4, '2026-09-23 08:46:07', 5200.00, 'Entregado', 'salon', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro', 6, 'efectivo', 'Pendiente de Cobro', 5200.00, 0.00, 0.00, NULL, 0.00, 0, 52, 5200.00, 0.00, 'Abono en efectivo contra entrega / en mostrador', '', NULL, NULL, 13, NULL, -26.18520000, -58.17560000, '', 31, NULL, NULL, '2026-09-23 08:46:32', NULL, NULL, NULL, NULL, 15, 'Martín Paredes', NULL, '2026-09-23 08:46:07', NULL, '2026-09-23 08:46:32', NULL, NULL, 1, NULL, NULL, 1),
(60, 4, '2026-09-23 09:00:58', 9840.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'mercadopago', 'Aprobado', 10800.00, 1200.00, 2160.00, 'DIBU20', 0.00, 0, 98, NULL, NULL, 'Mercado Pago Aprobado · Operación #MP-582428905', 'Mercado Pago: #MP-582428905', 'Ramiro González', NULL, 13, NULL, -26.14475800, -58.15669000, '', 32, NULL, NULL, '2026-09-23 09:01:59', NULL, NULL, NULL, NULL, 15, 'Martín Paredes', 19, '2026-09-23 09:00:59', NULL, '2026-09-23 09:01:59', '2026-09-23 09:16:29', '2026-09-23 09:17:55', 1, 1, 16, 1),
(61, 4, '2026-09-23 10:33:30', 17346.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'efectivo', 'Pendiente de Cobro', 20900.00, 1200.00, 4754.00, 'DIBU20', 0.00, 574, 173, 20000.00, 2654.00, 'Abono en efectivo contra entrega / en mostrador', '', 'Ramiro González', NULL, 13, NULL, -26.14470400, -58.15670500, '', 33, NULL, NULL, '2026-09-23 10:35:03', NULL, NULL, NULL, NULL, 15, 'Martín Paredes', 19, '2026-09-23 10:33:31', NULL, '2026-09-23 10:35:03', '2026-09-23 10:38:48', '2026-09-23 10:39:02', 1, 1, 5, 1),
(62, NULL, '2026-09-23 10:43:50', 6800.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 6, 'efectivo', 'Aprobado', 6800.00, 0.00, 0.00, NULL, 0.00, 0, 0, 6800.00, 0.00, 'Venta generada en Terminal Caja 1 - Principal / Mostrador', NULL, NULL, 17, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 17, 'Franco Morales', 17, 'Franco Morales', NULL, NULL, NULL, '2026-09-23 10:43:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(63, 4, '2026-09-25 08:09:50', 11000.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'efectivo', 'Pendiente de Cobro', 9800.00, 1200.00, 0.00, NULL, 0.00, 0, 110, 11000.00, 0.00, 'Abono en efectivo contra entrega / en mostrador', '', 'Ramiro González', NULL, 13, NULL, -26.14473700, -58.15671700, '', 34, NULL, NULL, '2026-09-25 08:13:38', NULL, NULL, NULL, NULL, 16, 'Lucas Silveira', 19, '2026-09-25 08:09:50', '2026-09-25 08:13:26', '2026-09-25 08:13:38', '2026-09-25 08:14:15', '2026-09-25 08:15:29', 1, 1, 5, 1),
(64, NULL, '2026-09-25 08:20:17', 10200.00, 'Entregado', 'salon', NULL, NULL, 'Mostaza Central', 2, 'efectivo', 'Aprobado', 10200.00, 0.00, 0.00, NULL, 0.00, 0, 0, 10200.00, 0.00, 'Venta POS Mostrador/Salón (SALON)', NULL, NULL, 13, 13, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 13, 'G├│mez Alberto Emanuel', NULL, NULL, NULL, NULL, NULL, '2026-09-25 08:20:17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(65, 4, '2026-10-02 09:21:24', 10947.00, 'Entregado', 'delivery', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Costanera Formosa', 1, 'efectivo', 'Pendiente de Cobro', 11800.00, 1200.00, 2643.00, 'DIBU20', 590.00, 283, 109, 20000.00, 9053.00, 'Abono en efectivo contra entrega / en mostrador', '', 'Lucas Benítez', NULL, 14, NULL, -26.14470900, -58.15665900, '', 35, NULL, NULL, '2026-10-02 09:24:15', NULL, NULL, NULL, NULL, 15, 'Martín Paredes', 20, '2026-10-02 09:21:24', '2026-10-02 09:23:06', '2026-10-02 09:24:15', '2026-10-02 09:25:13', '2026-10-02 09:25:42', 1, 1, 4, 1),
(66, 4, '2026-10-02 09:29:04', 9800.00, 'Entregado', 'salon', 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212', 'Mostaza Formosa Centro', 1, 'efectivo', 'Pendiente de Cobro', 9800.00, 0.00, 0.00, NULL, 0.00, 0, 98, 9800.00, 0.00, 'Abono en efectivo contra entrega / en mostrador', '', NULL, NULL, 14, NULL, -26.18520000, -58.17560000, '', 36, NULL, NULL, '2026-10-02 09:31:29', NULL, NULL, 17, 'Franco Morales', 15, 'Martín Paredes', NULL, '2026-10-02 09:29:04', '2026-10-02 09:31:26', '2026-10-02 09:31:29', NULL, '2026-10-02 09:31:45', 1, 1, 2, 1),
(67, NULL, '2026-10-02 09:29:55', 5200.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 1, 'efectivo', 'Aprobado', 5200.00, 0.00, 0.00, NULL, 0.00, 0, 0, 5200.00, 0.00, 'Venta generada en Terminal Caja 1 - Principal / Mostrador', NULL, NULL, 17, 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 17, 'Franco Morales', 17, 'Franco Morales', NULL, NULL, NULL, '2026-10-02 09:29:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1),
(68, NULL, '2026-10-02 09:40:50', 18100.00, 'Recibido', 'salon', NULL, NULL, 'Mostaza Central', 11, 'efectivo', 'Aprobado', 18100.00, 0.00, 0.00, NULL, 0.00, 0, 0, 18100.00, 0.00, 'Venta POS Mostrador/Salón (SALON)', NULL, NULL, 13, 14, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 13, 'G├│mez Alberto Emanuel', NULL, NULL, NULL, NULL, NULL, '2026-10-02 09:40:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1);

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
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ingredientes` text DEFAULT NULL,
  `alergenos` varchar(255) DEFAULT 'Contiene gluten, huevo, l├ícteos',
  `calorias` int(11) DEFAULT 650,
  `tamano_opciones` varchar(255) DEFAULT 'Simple, Doble (+ $1.500), Triple (+ $2.800)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`idproducto`, `nombre`, `descripcion`, `precio`, `imagen`, `categoria`, `activo`, `ingredientes`, `alergenos`, `calorias`, `tamano_opciones`) VALUES
(1, 'Hambre de la Cuarta | Provolone y Bacon', 'Cuarto de libra con provolone gratinado y bacon crocante', 9800.00, 'cardex.jpg', 'exclusivos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(2, 'La Cuarta | Ahumada', 'Cuarto de libra con salsa ahumada de la casa', 9300.00, 'card1.jpg', 'exclusivos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(3, 'La Cuarta | Merlo', 'Cuarto de libra especial estilo Merlo', 9500.00, 'merlo.jpg', 'exclusivos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(4, 'Hambre de la Cuarta | Provolone y Bacon', 'Combo con papas y bebida', 11800.00, 'cardex.jpg', 'megacombos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(5, 'La Cuarta | Ahumada', 'Combo con papas y bebida', 11300.00, 'card1.jpg', 'megacombos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(6, 'La Cuarta | Merlo', 'Combo con papas y bebida', 11500.00, 'merlo.jpg', 'megacombos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(7, 'Doble Mega Big Bang Cheddar', 'Doble carne, doble cheddar, combo con papas y bebida', 12800.00, 'doblwcheddar.jpg', 'megacombos', 1, 'Doble medall├│n de carne vacuna a la plancha, triple feta de queso cheddar fundido, aderezo cheddar especial Mostaza y pan de papa.', 'Gluten, L├ícteos, Soja', 910, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(8, 'Doble Mega Boom', 'Doble carne con salsa boom, combo con papas y bebida', 12600.00, 'boom.jpg', 'megacombos', 1, 'Doble medall├│n de carne vacuna a la plancha, triple feta de queso cheddar fundido, aderezo cheddar especial Mostaza y pan de papa.', 'Gluten, L├ícteos, Soja', 910, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(9, 'Doble Cuarto a Fondo', 'Doble cuarto de libra a puro sabor, combo con papas y bebida', 13200.00, 'fondo.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(10, 'Doble Mega Taste', 'Doble carne receta Taste, combo con papas y bebida', 12400.00, 'taste1.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(11, 'Doble Mega Cuarto Bacon', 'Doble cuarto de libra con bacon, combo con papas y bebida', 12900.00, 'taste2.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(12, 'Doble Mega Cuarto', 'Doble cuarto de libra cl├ísico, combo con papas y bebida', 12000.00, 'cuarto.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(13, 'Pollo Crispy Bacon', 'Pollo crocante con bacon y salsa especial', 8600.00, 'pollobacon.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(14, 'Pollo Crispy', 'Cl├ísico sandwich de pollo crocante', 7900.00, 'pollocrispy.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(15, 'Pollo Crispy Cheddar', 'Pollo crocante con cheddar fundido', 8300.00, 'pollocheddar.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(16, 'Nuggets de Pollo x6', '6 unidades de nuggets de pollo', 4800.00, 'x6.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(17, 'Nuggets de Pollo x10', '10 unidades de nuggets de pollo', 6900.00, 'x10.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(18, 'Nuggets Bacon & Cheddar x10', '10 nuggets rellenos de bacon y cheddar', 7900.00, 'nuggetachedar.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(19, 'Balde de Nuggets', 'Balde grande para compartir', 12500.00, 'balde.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(20, 'Ensalada Palta y Huevo Grill', 'Con pollo grillado, palta y huevo', 6800.00, 'ensaladapalta1.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(21, 'Ensalada Palta y Huevo Veggie', 'Opci├│n veggie con palta y huevo', 6500.00, 'ensaladapalta2.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(22, 'Ensalada Caesar Crispy', 'Con pollo crocante, verdes y aderezo caesar', 6900.00, 'Caesar 2.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(23, 'Ensalada Caesar Grill', 'Con pollo grillado, verdes y aderezo caesar', 6900.00, 'x10.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(24, 'Wrap Cheddar & Pollo', 'Wrap de pollo con cheddar fundido', 6200.00, 'wrap.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(25, 'Cero Veggie Cheese', 'Hamburguesa veggie con cheddar', 8200.00, 'card3.jpg', 'paravos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(26, 'Cero Veggie', 'Hamburguesa 100% veggie', 7800.00, 'cerovegiee.jpg', 'paravos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(27, 'Mega Cuarto Doble sin TACC', 'Opci├│n apta cel├¡acos', 10800.00, 'sintacc.jpg', 'paravos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(28, 'Cajita Mostaza H├®roes Cl├ísica', 'Hamburguesa chica + papas + juguete sorpresa', 5200.00, 'cajita1.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(29, 'Cajita Mostaza H├®roes Nuggets', 'Nuggets + papas + juguete sorpresa', 5200.00, 'cajita2.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(30, 'Cajita Mostaza H├®roes Cheese', 'Hamburguesa con cheddar + papas + juguete sorpresa', 5400.00, 'cajita3.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(31, 'Cajita Mostaza H├®roes Doble', 'Doble hamburguesa chica + papas + juguete sorpresa', 5900.00, 'cajita4.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(32, 'Cajita Mostaza H├®roes Deluxe', 'Hamburguesa + papas + postre + juguete sorpresa', 6300.00, 'cajita5.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(33, 'Tostado Queso & Jam├│n', 'Tostado cl├ísico de jam├│n y queso', 3800.00, 'tostado.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(34, 'Tostado XL Queso & Jam├│n', 'Versi├│n XL del tostado de jam├│n y queso', 4600.00, 'tostadoxl.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(35, 'Sandwich Huevo & Palta', 'Sandwich de huevo con palta', 4200.00, 'huevopalta.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(36, 'Sandwich Huevo & Bacon', 'Sandwich de huevo con bacon', 4400.00, 'huevobacon.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(37, 'Medialunas', 'Porci├│n de medialunas', 2200.00, 'medialunas1.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(38, 'Medialunas Jam├│n & Queso', 'Medialunas rellenas de jam├│n y queso', 2800.00, 'medialunas2.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(39, 'Alfajor de Chocolate', 'Alfajor ba├▒ado en chocolate', 1800.00, 'alfajor.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(40, 'Bud├¡n de Banana', 'Porci├│n de bud├¡n de banana casero', 2100.00, 'card7.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(41, 'Shake Capuccino Frozen', 'Shake helado sabor capuccino', 3600.00, 'shake.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(42, 'Cono ARG', 'Cono helado edici├│n Argentina', 2500.00, 'Conoarg.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(43, 'Cono Cl├ísico', 'Cono helado cl├ísico', 2200.00, 'conoclasi.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(44, 'Hambre de la Cuarta | Provolone y Bacon', 'Cuarto de libra con provolone gratinado y bacon crocante', 9800.00, 'cardex.jpg', 'exclusivos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(45, 'La Cuarta | Ahumada', 'Cuarto de libra con salsa ahumada de la casa', 9300.00, 'card1.jpg', 'exclusivos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(46, 'La Cuarta | Merlo', 'Cuarto de libra especial estilo Merlo', 9500.00, 'merlo.jpg', 'exclusivos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(47, 'Hambre de la Cuarta | Provolone y Bacon', 'Combo con papas y bebida', 11800.00, 'cardex.jpg', 'megacombos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(48, 'La Cuarta | Ahumada', 'Combo con papas y bebida', 11300.00, 'card1.jpg', 'megacombos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(49, 'La Cuarta | Merlo', 'Combo con papas y bebida', 11500.00, 'merlo.jpg', 'megacombos', 1, 'Carne 100% vacuna seleccionada, queso provolone fundido, tiras de bacon crocante, salsa ahumada Mostaza y pan brioche artesanal tostado.', 'Gluten, L├ícteos, Huevo, Soja', 820, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(50, 'Doble Mega Big Bang Cheddar', 'Doble carne, doble cheddar, combo con papas y bebida', 12800.00, 'doblwcheddar.jpg', 'megacombos', 1, 'Doble medall├│n de carne vacuna a la plancha, triple feta de queso cheddar fundido, aderezo cheddar especial Mostaza y pan de papa.', 'Gluten, L├ícteos, Soja', 910, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(51, 'Doble Mega Boom', 'Doble carne con salsa boom, combo con papas y bebida', 12600.00, 'boom.jpg', 'megacombos', 1, 'Doble medall├│n de carne vacuna a la plancha, triple feta de queso cheddar fundido, aderezo cheddar especial Mostaza y pan de papa.', 'Gluten, L├ícteos, Soja', 910, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(52, 'Doble Cuarto a Fondo', 'Doble cuarto de libra a puro sabor, combo con papas y bebida', 13200.00, 'fondo.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(53, 'Doble Mega Taste', 'Doble carne receta Taste, combo con papas y bebida', 12400.00, 'taste1.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(54, 'Doble Mega Cuarto Bacon', 'Doble cuarto de libra con bacon, combo con papas y bebida', 12900.00, 'taste2.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(55, 'Doble Mega Cuarto', 'Doble cuarto de libra cl├ísico, combo con papas y bebida', 12000.00, 'cuarto.jpg', 'megacombos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(56, 'Pollo Crispy Bacon', 'Pollo crocante con bacon y salsa especial', 8600.00, 'pollobacon.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(57, 'Pollo Crispy', 'Cl├ísico sandwich de pollo crocante', 7900.00, 'pollocrispy.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(58, 'Pollo Crispy Cheddar', 'Pollo crocante con cheddar fundido', 8300.00, 'pollocheddar.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(59, 'Nuggets de Pollo x6', '6 unidades de nuggets de pollo', 4800.00, 'x6.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(60, 'Nuggets de Pollo x10', '10 unidades de nuggets de pollo', 6900.00, 'x10.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(61, 'Nuggets Bacon & Cheddar x10', '10 nuggets rellenos de bacon y cheddar', 7900.00, 'nuggetachedar.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(62, 'Balde de Nuggets', 'Balde grande para compartir', 12500.00, 'balde.jpg', 'pollo', 1, 'Suprema de pechuga de pollo rebozada ultra crujiente, lechuga fresca de estaci├│n, mayonesa suave y pan de s├®samo.', 'Gluten, Huevo, Mostaza', 640, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(63, 'Ensalada Palta y Huevo Grill', 'Con pollo grillado, palta y huevo', 6800.00, 'ensaladapalta1.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(64, 'Ensalada Palta y Huevo Veggie', 'Opci├│n veggie con palta y huevo', 6500.00, 'ensaladapalta2.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(65, 'Ensalada Caesar Crispy', 'Con pollo crocante, verdes y aderezo caesar', 6900.00, 'Caesar 2.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(66, 'Ensalada Caesar Grill', 'Con pollo grillado, verdes y aderezo caesar', 6900.00, 'x10.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(67, 'Wrap Cheddar & Pollo', 'Wrap de pollo con cheddar fundido', 6200.00, 'wrap.jpg', 'ensaladas', 1, 'Mix de hojas verdes frescas, queso parmesano en hebras, croutons dorados, pollo grillado y dressing Caesar tradicional.', 'L├ícteos, Gluten, Pescado (anchoas en aderezo)', 420, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(68, 'Cero Veggie Cheese', 'Hamburguesa veggie con cheddar', 8200.00, 'card3.jpg', 'paravos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(69, 'Cero Veggie', 'Hamburguesa 100% veggie', 7800.00, 'cerovegiee.jpg', 'paravos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(70, 'Mega Cuarto Doble sin TACC', 'Opci├│n apta cel├¡acos', 10800.00, 'sintacc.jpg', 'paravos', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(71, 'Cajita Mostaza H├®roes Cl├ísica', 'Hamburguesa chica + papas + juguete sorpresa', 5200.00, 'cajita1.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(72, 'Cajita Mostaza H├®roes Nuggets', 'Nuggets + papas + juguete sorpresa', 5200.00, 'cajita2.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(73, 'Cajita Mostaza H├®roes Cheese', 'Hamburguesa con cheddar + papas + juguete sorpresa', 5400.00, 'cajita3.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(74, 'Cajita Mostaza H├®roes Doble', 'Doble hamburguesa chica + papas + juguete sorpresa', 5900.00, 'cajita4.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(75, 'Cajita Mostaza H├®roes Deluxe', 'Hamburguesa + papas + postre + juguete sorpresa', 6300.00, 'cajita5.jpg', 'cajitas', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(76, 'Tostado Queso & Jam├│n', 'Tostado cl├ísico de jam├│n y queso', 3800.00, 'tostado.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(77, 'Tostado XL Queso & Jam├│n', 'Versi├│n XL del tostado de jam├│n y queso', 4600.00, 'tostadoxl.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(78, 'Sandwich Huevo & Palta', 'Sandwich de huevo con palta', 4200.00, 'huevopalta.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(79, 'Sandwich Huevo & Bacon', 'Sandwich de huevo con bacon', 4400.00, 'huevobacon.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(80, 'Medialunas', 'Porci├│n de medialunas', 2200.00, 'medialunas1.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(81, 'Medialunas Jam├│n & Queso', 'Medialunas rellenas de jam├│n y queso', 2800.00, 'medialunas2.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(82, 'Alfajor de Chocolate', 'Alfajor ba├▒ado en chocolate', 1800.00, 'alfajor.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(83, 'Bud├¡n de Banana', 'Porci├│n de bud├¡n de banana casero', 2100.00, 'card7.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(84, 'Shake Capuccino Frozen', 'Shake helado sabor capuccino', 3600.00, 'shake.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(85, 'Cono ARG', 'Cono helado edici├│n Argentina', 2500.00, 'Conoarg.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)'),
(86, 'Cono Cl├ísico', 'Cono helado cl├ísico', 2200.00, 'conoclasi.jpg', 'postres', 1, NULL, 'Contiene gluten, huevo, l├ícteos', 650, 'Simple, Doble (+ $1.500), Triple (+ $2.800)');

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
  `estado_entrega` varchar(20) NOT NULL DEFAULT 'Al d├¡a'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`idproveedor`, `nombre`, `fk_id_telefono`, `fk_id_email`, `fk_id_direccion`, `rubro`, `ultima_entrega`, `estado_entrega`) VALUES
(1, 'Frigor├¡fico El Norte├▒o', 9, 12, 8, 'C├írnicos', '2026-08-01', 'Demorado'),
(2, 'Panificadora San Jos├®', 10, 13, 9, 'Panader├¡a', '2026-08-05', 'Al d├¡a'),
(3, 'Distribuidora de Bebidas del Sur', 11, 14, 5, 'Bebidas', '2026-07-28', 'Atrasado'),
(4, 'Verduler├¡a Central', 12, 15, 6, 'Verduler├¡a', '2026-08-04', 'Al d├¡a'),
(5, 'Packaging Express SRL', 13, 1, 3, 'Packaging', '2026-08-03', 'Al d├¡a');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta`
--

CREATE TABLE `receta` (
  `idreceta` int(11) NOT NULL,
  `idproducto` int(11) DEFAULT NULL,
  `nombre_plato` varchar(150) NOT NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'Hamburguesas',
  `tiempo_preparacion_min` int(11) NOT NULL DEFAULT 5,
  `dificultad` varchar(30) NOT NULL DEFAULT 'Media',
  `ingredientes_lista` text NOT NULL,
  `pasos_preparacion` text NOT NULL,
  `temperatura_coccion` varchar(50) DEFAULT 'Plancha a 180┬░C'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `receta`
--

INSERT INTO `receta` (`idreceta`, `idproducto`, `nombre_plato`, `categoria`, `tiempo_preparacion_min`, `dificultad`, `ingredientes_lista`, `pasos_preparacion`, `temperatura_coccion`) VALUES
(1, NULL, 'Mega Cuarto Doble con Cheddar', 'Hamburguesas', 4, 'Media', '2x Medall├│n de carne vacuna (120g c/u), 2x Fetas de queso cheddar, 1x Pan brioche con s├®samo, 20g Salsa Mostaza, 15g Cebolla picada fina, 2x Rodajas de pepinillo agridulce.', '1. Tostar ambas caras del pan brioche en la tostadora durante 35 segundos.\n2. Colocar los 2 medallones de carne en la plancha a 180┬░C y salpimentar al dar vuelta (2 min por lado).\n3. Colocar una feta de cheddar sobre cada medall├│n 30 segundos antes de retirar para derretir.\n4. Untar 10g de salsa Mostaza en la base y 10g en la corona.\n5. Montar carne, queso, cebolla y pepinillos.\n6. Cerrar corona y empaquetar en papel t├®rmico.', 'Plancha a 180┬░C / Tostadora a 200┬░C'),
(2, NULL, 'Pollo Crispy Bacon & Cheddar', 'Pollo', 5, 'Media', '1x Filet de pechuga rebozada crispy (140g), 2x Tiras de panceta ahumada dorada, 1x Feta de queso cheddar, 1x Pan de papa, 15g Mayonesa suave, 20g Lechuga capuchina cortada.', '1. Fre├¡r el filet de pollo en freidora a 175┬░C durante 3 minutos y medio hasta dorado crujiente.\n2. Tostar pan de papa durante 30 segundos.\n3. Calentar las tiras de panceta en plancha durante 45 segundos.\n4. Untar mayonesa en la base, colocar lechuga fresca.\n5. Colocar el pollo crujiente, el cheddar fundido y la panceta arriba.\n6. Cerrar y etiquetar comanda.', 'Freidora a 175┬░C / Plancha a 160┬░C'),
(3, NULL, 'Papas Fritas Mega Cheddar & Bacon', 'Acompa├▒amientos', 3, 'F├ícil', '250g Papas bast├│n congeladas premium, 40g Salsa de queso cheddar caliente, 20g Panceta crispy picada, 5g Sal fina.', '1. Sumergir el canasto de papas en freidora a 180┬░C durante 2 minutos y 45 segundos.\n2. Escurrir durante 15 segundos y salar uniformemente en la batea.\n3. Servir en bandeja grande Mostaza.\n4. Salsear en zigzag con la pistola de cheddar caliente a 65┬░C.\n5. Espolvorear la lluvia de panceta crocante.', 'Freidora a 180┬░C / Dispensador de cheddar a 65┬░C'),
(4, NULL, 'Ensalada Caesar con Pollo Grill', 'Ensaladas', 4, 'F├ícil', '120g Mix de lechuga romana y capuchina, 100g Tiras de pollo a la plancha tibio, 25g Queso parmesano rallado en hebras, 20g Croutons saborizados, 35g Dressing Caesar en pote.', '1. Lavar y centrifugar el mix de hojas verdes.\n2. Disponer las hojas en el bowl transparente de ensaladas.\n3. Grilllar las tiras de pechuga durante 3 minutos y cortar en bocados.\n4. Distribuir el pollo sobre las hojas verdes.\n5. Decorar con croutons y abundante parmesano en hebras.\n6. Adjuntar pote de salsa Caesar y tenedor.', 'Grill a 175┬░C');

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
  `origen` varchar(50) DEFAULT 'App Cajero',
  `nombre_empleado` varchar(100) NOT NULL DEFAULT 'Empleado Mostaza',
  `rol` varchar(50) NOT NULL DEFAULT 'Cajero'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `registro_turno_empleado`
--

INSERT INTO `registro_turno_empleado` (`idregistro`, `fk_id_empleado`, `fk_id_turno`, `fecha`, `hora_entrada`, `hora_salida`, `horas_trabajadas`, `estado`, `observaciones`, `origen`, `nombre_empleado`, `rol`) VALUES
(1, 13, 1, '2026-08-24', '2026-08-24 10:36:44', '2026-08-25 08:49:13', 22.21, 'Finalizado', 'Cierre autom├ítico por nuevo inicio', 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(2, 13, 1, '2026-08-25', '2026-08-25 08:49:13', '2026-08-25 08:57:30', 0.14, 'Finalizado', 'Turno finalizado desde App Cajero', 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(3, 13, 1, '2026-08-26', '2026-08-26 00:52:16', '2026-08-26 00:55:41', 0.06, 'Finalizado', 'Turno finalizado desde App Cajero', 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(6, 1, 1, '2026-09-14', '2026-09-14 19:38:20', '2026-10-02 08:30:18', 420.87, 'Finalizado', 'Fichada de ingreso y turno activo | Cierre automático', 'App Delivery Oficial', 'Ramiro González', 'Delivery'),
(7, 13, 1, '2026-09-15', '2026-09-15 08:10:37', '2026-09-18 21:44:28', 85.56, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(8, 1, 1, '2026-09-15', '2026-09-15 10:00:56', '2026-10-02 08:30:18', 406.49, 'Finalizado', 'Fichada de ingreso y turno activo | Cierre automático', 'App Delivery Oficial', 'Ramiro González', 'Delivery'),
(9, 1, 1, '2026-09-15', '2026-09-15 10:06:55', '2026-10-02 08:30:18', 406.39, 'Finalizado', 'Fichada de ingreso y turno activo | Cierre automático', 'App Delivery Oficial', 'Franco Morales', 'Delivery'),
(10, 19, 1, '2026-09-16', '2026-09-16 00:43:12', '2026-09-23 03:06:05', 170.38, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(11, 22, 1, '2026-09-18', '2026-09-18 09:28:03', '2026-09-18 10:22:48', 0.91, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(12, 22, 1, '2026-09-18', '2026-09-18 10:22:48', '2026-09-18 10:23:02', 0.00, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(13, 22, 1, '2026-09-18', '2026-09-18 10:23:02', NULL, NULL, 'Activo', NULL, 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(14, 13, 1, '2026-09-18', '2026-09-18 21:44:28', '2026-09-25 08:19:04', 154.58, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(15, 19, 1, '2026-09-23', '2026-09-23 03:06:05', '2026-09-23 03:07:01', 0.00, 'Finalizado', NULL, 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(16, 15, 1, '2026-09-23', '2026-09-23 03:10:43', '2026-09-23 03:12:05', 0.02, 'Finalizado', 'Cierre de turno desde Cocina KDS (Martín Paredes)', 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(17, 17, 1, '2026-09-23', '2026-09-23 03:19:14', '2026-09-23 10:13:24', 6.90, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(18, 19, 1, '2026-09-23', '2026-09-23 08:27:14', '2026-09-23 09:16:05', 0.81, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(19, 15, 1, '2026-09-23', '2026-09-23 08:32:50', '2026-09-23 10:34:33', 2.03, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(20, 19, 1, '2026-09-23', '2026-09-23 09:16:05', '2026-09-23 10:35:32', 1.32, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(21, 17, 1, '2026-09-23', '2026-09-23 10:13:24', '2026-09-23 10:42:43', 0.49, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(22, 15, 1, '2026-09-23', '2026-09-23 10:34:33', '2026-09-25 08:49:57', 46.26, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(23, 19, 1, '2026-09-23', '2026-09-23 10:35:32', '2026-09-25 08:04:47', 45.49, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(24, 17, 1, '2026-09-23', '2026-09-23 10:42:43', '2026-09-25 08:08:26', 45.43, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(25, 19, 1, '2026-09-25', '2026-09-25 08:04:47', '2026-09-25 08:26:39', 0.35, 'Finalizado', NULL, 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(26, 17, 1, '2026-09-25', '2026-09-25 08:08:26', '2026-09-25 08:10:24', 0.03, 'Finalizado', 'Cierre de turno desde App Mesero (Franco Morales)', 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(27, 16, 1, '2026-09-25', '2026-09-25 08:12:07', '2026-09-25 08:26:47', 0.24, 'Finalizado', 'Cierre de turno desde Cocina KDS (Lucas Silveira)', 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(28, 13, 1, '2026-09-25', '2026-09-25 08:19:04', '2026-10-02 09:33:10', 169.24, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(29, 15, 1, '2026-09-25', '2026-09-25 08:49:57', '2026-10-02 08:40:19', 167.84, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(30, 18, 1, '2026-09-25', '2026-09-25 08:53:16', NULL, NULL, 'Activo', NULL, 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(31, 19, 1, '2026-10-02', '2026-10-02 08:25:13', NULL, NULL, 'Activo', NULL, 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(32, 1, 1, '2026-10-02', '2026-10-02 08:30:18', NULL, NULL, 'Activo', NULL, 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(33, 15, 1, '2026-10-02', '2026-10-02 08:40:19', '2026-10-02 09:04:08', 0.40, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(34, 20, 1, '2026-10-02', '2026-10-02 08:45:21', '2026-10-02 09:23:47', 0.64, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(35, 16, 1, '2026-10-02', '2026-10-02 08:49:33', NULL, NULL, 'Activo', NULL, 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(36, 15, 1, '2026-10-02', '2026-10-02 09:04:08', NULL, NULL, 'Activo', NULL, 'App Cocina', 'Empleado Mostaza', 'Cajero'),
(37, 17, 1, '2026-10-02', '2026-10-02 09:04:42', '2026-10-02 09:48:11', 0.72, 'Finalizado', 'Cierre automático por nuevo inicio', 'App Mesero', 'Empleado Mostaza', 'Cajero'),
(38, 20, 1, '2026-10-02', '2026-10-02 09:23:47', NULL, NULL, 'Activo', NULL, 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(39, 13, 1, '2026-10-02', '2026-10-02 09:33:10', NULL, NULL, 'Activo', NULL, 'App Cajero', 'Empleado Mostaza', 'Cajero'),
(40, 21, 1, '2026-10-02', '2026-10-02 09:42:42', NULL, NULL, 'Activo', NULL, 'App Delivery', 'Empleado Mostaza', 'Cajero'),
(41, 17, 1, '2026-10-02', '2026-10-02 09:48:11', NULL, NULL, 'Activo', NULL, 'App Mesero', 'Empleado Mostaza', 'Cajero');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `repartidor`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `repartidor` (
`idrepartidor` int(11)
,`idempleado` int(11)
,`nombre` varchar(201)
,`dni` varchar(20)
,`telefono` varchar(50)
,`vehiculo` varchar(100)
,`patente` varchar(20)
,`pin` varchar(10)
,`avatar` varchar(50)
,`sucursal_base` varchar(100)
,`activo` tinyint(1)
,`estado_servicio` enum('Disponible','En Reparto','En Descanso','Desconectado')
,`calificacion` decimal(3,2)
,`viajes_totales` int(11)
,`email` varchar(150)
);

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
(5, 'Administrador'),
(6, 'Repartidor');

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
(1, 'Sal├│n Principal', '├ürea central del restaurante frente al mostrador', 'fa-utensils', 1),
(2, 'Terraza Exterior', 'Mesas al aire libre con vista a la avenida', 'fa-sun', 2),
(3, 'Planta Alta / VIP', 'Zona exclusiva y climatizada en primer piso', 'fa-crown', 3),
(4, 'Barra & Boxes', 'Boxes privados y sillas de barra alta', 'fa-martini-glass-citrus', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_ingrediente`
--

CREATE TABLE `solicitud_ingrediente` (
  `idsolicitud` int(11) NOT NULL,
  `fk_id_empleado` int(11) DEFAULT NULL,
  `solicitante_nombre` varchar(100) NOT NULL DEFAULT 'Cocinero de Turno',
  `nombre_insumo` varchar(150) NOT NULL,
  `cantidad_solicitada` decimal(10,2) NOT NULL,
  `unidad_medida` varchar(30) NOT NULL DEFAULT 'unidades',
  `prioridad` enum('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
  `motivo` varchar(255) DEFAULT 'Stock agot├índose en el turno',
  `fecha_solicitud` datetime DEFAULT current_timestamp(),
  `estado` enum('Pendiente','Aprobada','Rechazada','Entregada') NOT NULL DEFAULT 'Pendiente',
  `respuesta_admin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitud_ingrediente`
--

INSERT INTO `solicitud_ingrediente` (`idsolicitud`, `fk_id_empleado`, `solicitante_nombre`, `nombre_insumo`, `cantidad_solicitada`, `unidad_medida`, `prioridad`, `motivo`, `fecha_solicitud`, `estado`, `respuesta_admin`) VALUES
(1, NULL, 'Martín Paredes', 'Panceta / Bacon Ahumado', 10.00, 'kg', 'Urgente', 'Reposición preventiva de turno', '2026-10-02 09:39:56', 'Pendiente', NULL);

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
(1, 'Medall├│n de Carne Vacuna 120g', 'Carne', 'unidades', 280.00, 50.00, 950.00, 'Frigor├¡fico El Norte├▒o', '2026-10-02 09:40:50'),
(2, 'Pechuga de Pollo Crispy', 'Carne', 'unidades', 180.00, 30.00, 820.00, 'Distribuidora Av├¡cola', '2026-08-16 18:46:24'),
(3, 'Pan de Hamburguesa con S├®samo', 'Panader├¡a', 'unidades', 350.00, 80.00, 150.00, 'Panificadora San Jos├®', '2026-10-02 09:40:50'),
(4, 'Queso Cheddar en Fetas', 'L├ícteos', 'kg', 45.00, 10.00, 3200.00, 'L├ícteos La Seren├¡sima', '2026-08-16 18:46:24'),
(5, 'Panceta / Bacon Ahumado', 'Fiambrer├¡a', 'kg', 28.00, 8.00, 4100.00, 'Frigor├¡fico El Norte├▒o', '2026-08-16 18:46:24'),
(6, 'Papas para Fritas McCain', 'Guarnici├│n', 'kg', 120.00, 25.00, 750.00, 'Distribuidora Central', '2026-08-16 18:46:24'),
(7, 'Lechuga Repollada Fresca', 'Verduler├¡a', 'kg', 22.00, 5.00, 600.00, 'Verduler├¡a Central', '2026-08-16 18:46:24'),
(8, 'Tomates Redondos Seleccionados', 'Verduler├¡a', 'kg', 26.00, 6.00, 850.00, 'Verduler├¡a Central', '2026-08-16 18:46:24'),
(9, 'Cebolla Morada', 'Verduler├¡a', 'kg', 18.00, 5.00, 500.00, 'Verduler├¡a Central', '2026-08-16 18:46:24'),
(10, 'Salsa Especial Mostaza', 'Aderezos', 'litros', 35.00, 8.00, 1800.00, 'Salsas Argentinas SA', '2026-08-16 18:46:24'),
(11, 'Gaseosa Coca-Cola 500ml', 'Bebidas', 'unidades', 230.00, 40.00, 700.00, 'Distribuidora del Sur', '2026-10-02 09:40:50'),
(12, 'Caja / Packaging Mostaza', 'Descartables', 'unidades', 620.00, 100.00, 85.00, 'Packaging Express SRL', '2026-10-02 09:38:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `idsucursal` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `fk_id_direccion` int(11) DEFAULT NULL,
  `fk_id_ciudad` int(11) DEFAULT NULL,
  `direccion` varchar(255) NOT NULL DEFAULT 'Av. 25 de Mayo 150',
  `telefono` varchar(50) NOT NULL DEFAULT '3704-432100',
  `horario_atencion` varchar(100) NOT NULL DEFAULT 'Lunes a Domingo de 10:00 a 00:00',
  `latitud` decimal(10,8) DEFAULT -26.18520000,
  `longitud` decimal(11,8) DEFAULT -58.17560000,
  `ciudad` varchar(100) DEFAULT 'Formosa',
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`idsucursal`, `nombre`, `fk_id_direccion`, `fk_id_ciudad`, `direccion`, `telefono`, `horario_atencion`, `latitud`, `longitud`, `ciudad`, `activo`) VALUES
(1, 'Mostaza Formosa Centro', 1, 1, 'Av. 25 de Mayo 150', '(0370) 443-2100', 'Lunes a Domingo de 08:00 a 02:00', -26.18520000, -58.17560000, 'Formosa Capital', 1),
(2, 'Mostaza Costanera Formosa', 1, 1, 'Av. Costanera y 25 de Mayo (Paseo Costanero)', '(0370) 443-2101', 'Lunes a Domingo de 11:00 a 03:00', -26.18120000, -58.16950000, 'Formosa Capital', 1),
(3, 'Mostaza Paseo Sarmiento', 1, 1, 'Av. Gobernador Gutnisky 2500 (Paseo Sarmiento)', '(0370) 443-2102', 'Lunes a Domingo de 10:00 a 00:00', -26.19650000, -58.20400000, 'Formosa Capital', 1),
(4, 'Mostaza Obelisco', 1, 1, 'Av. Corrientes 1002 (esq. Carlos Pellegrini)', '(011) 4328-1122', 'Abierto las 24 horas', -34.60370000, -58.38160000, 'CABA', 1),
(5, 'Mostaza Palermo Soho', 1, 1, 'Av. Santa Fe 3253', '(011) 4825-9988', 'Lunes a Domingo de 08:00 a 02:00', -34.58800000, -58.41100000, 'CABA', 1),
(6, 'Mostaza Puerto Madero', 1, 1, 'Juana Manso 1180', '(011) 5277-3344', 'Lunes a Domingo de 10:00 a 01:00', -34.61350000, -58.36250000, 'CABA', 1),
(7, 'Mostaza Florida Peatonal', 1, 1, 'Florida 465', '(011) 4394-5566', 'Lunes a Domingo de 08:00 a 23:00', -34.60190000, -58.37520000, 'CABA', 1),
(8, 'Mostaza Abasto Shopping', 1, 1, 'Av. Corrientes 3247 (Nivel 3 Patio de Comidas)', '(011) 4959-3400', 'Lunes a Domingo de 10:00 a 00:00', -34.60330000, -58.41090000, 'CABA', 1),
(9, 'Mostaza Aeroparque Jorge Newbery', 1, 1, 'Av. Costanera Rafael Obligado s/n (Hall Central)', '(011) 4771-5522', 'Abierto las 24 horas', -34.55870000, -58.41720000, 'CABA', 1),
(10, 'Mostaza Belgrano Cabildo', 1, 1, 'Av. Cabildo 2230', '(011) 4781-3322', 'Lunes a Domingo de 08:00 a 01:00', -34.56150000, -58.45600000, 'CABA', 1),
(11, 'Mostaza Unicenter Shopping', 1, 1, 'Paraná 3745 (Patio de Comidas Nivel 3), Martínez', '(011) 4733-1100', 'Lunes a Domingo de 10:00 a 00:00', -34.50900000, -58.52300000, 'GBA Norte', 1),
(12, 'Mostaza Plaza Oeste Shopping', 1, 1, 'Av. Juan Manuel de Rosas 658, Morón', '(011) 4489-7000', 'Lunes a Domingo de 10:00 a 23:30', -34.63600000, -58.62500000, 'GBA Oeste', 1),
(13, 'Mostaza Quilmes Centro', 1, 1, 'Peatonal Rivadavia 210, Quilmes', '(011) 4253-1144', 'Lunes a Domingo de 08:00 a 01:00', -34.72140000, -58.25410000, 'GBA Sur', 1),
(14, 'Mostaza Nueva Córdoba', 1, 1, 'Av. Hipólito Yrigoyen 325, Córdoba', '(0351) 421-5500', 'Lunes a Domingo de 08:00 a 02:00', -31.42390000, -64.18650000, 'Córdoba Capital', 1),
(15, 'Mostaza Patio Olmos Shopping', 1, 1, 'Av. Vélez Sarsfield 361, Córdoba', '(0351) 570-4100', 'Lunes a Domingo de 10:00 a 00:00', -31.41960000, -64.18820000, 'Córdoba Capital', 1),
(16, 'Mostaza Villa Carlos Paz', 1, 1, 'Av. 9 de Julio 120, Villa Carlos Paz', '(03541) 431-200', 'Lunes a Domingo de 09:00 a 03:00', -31.42150000, -64.49800000, 'Córdoba', 1),
(17, 'Mostaza Rosario Peatonal Córdoba', 1, 1, 'Peatonal Córdoba 1045, Rosario', '(0341) 425-7788', 'Lunes a Domingo de 08:00 a 02:00', -32.94650000, -60.63850000, 'Rosario', 1),
(18, 'Mostaza Alto Rosario Shopping', 1, 1, 'Junín 501, Rosario', '(0341) 410-6500', 'Lunes a Domingo de 10:00 a 00:00', -32.93040000, -60.66280000, 'Rosario', 1),
(19, 'Mostaza Santa Fe Peatonal San Martín', 1, 1, 'San Martín 2450, Santa Fe Capital', '(0342) 456-1122', 'Lunes a Domingo de 08:30 a 01:00', -31.64500000, -60.70800000, 'Santa Fe Capital', 1),
(20, 'Mostaza Mendoza Peatonal San Martín', 1, 1, 'San Martín y Sarmiento, Mendoza Capital', '(0261) 429-3300', 'Lunes a Domingo de 08:00 a 02:00', -32.88950000, -68.84100000, 'Mendoza Capital', 1),
(21, 'Mostaza Mendoza Plaza Shopping', 1, 1, 'Av. Acceso Este 3280, Guaymallén, Mendoza', '(0261) 449-0100', 'Lunes a Domingo de 10:00 a 00:00', -32.89950000, -68.80200000, 'Mendoza', 1),
(22, 'Mostaza Resistencia Peatonal', 1, 1, 'Peatonal Juan Domingo Perón 180, Resistencia', '(0362) 445-8800', 'Lunes a Domingo de 08:00 a 02:00', -27.45150000, -58.98670000, 'Resistencia', 1),
(23, 'Mostaza Corrientes Peatonal Junín', 1, 1, 'Junín 1150, Corrientes Capital', '(0379) 442-9900', 'Lunes a Domingo de 08:00 a 02:00', -27.46900000, -58.83400000, 'Corrientes Capital', 1),
(24, 'Mostaza Posadas Costanera', 1, 1, 'Av. Costanera y Monseñor de Andrea, Posadas', '(0376) 443-7711', 'Lunes a Domingo de 10:00 a 02:00', -27.36200000, -55.89200000, 'Posadas', 1),
(25, 'Mostaza Tucumán Peatonal Muñecas', 1, 1, 'Muñecas 180, San Miguel de Tucumán', '(0381) 431-5566', 'Lunes a Domingo de 08:00 a 01:30', -26.83000000, -65.20400000, 'Tucumán', 1),
(26, 'Mostaza Salta Peatonal Alberdi', 1, 1, 'Alberdi 145, Salta Capital', '(0387) 422-3344', 'Lunes a Domingo de 08:30 a 02:00', -24.78900000, -65.41100000, 'Salta Capital', 1),
(27, 'Mostaza Jujuy Peatonal Belgrano', 1, 1, 'Belgrano 680, San Salvador de Jujuy', '(0388) 424-5588', 'Lunes a Domingo de 08:30 a 01:00', -24.18560000, -65.29950000, 'San Salvador de Jujuy', 1),
(28, 'Mostaza Mar del Plata Peatonal', 1, 1, 'San Martín 2302 (esq. Corrientes), Mar del Plata', '(0223) 495-2233', 'Lunes a Domingo de 08:00 a 03:00', -38.00150000, -57.54500000, 'Mar del Plata', 1),
(29, 'Mostaza Bariloche Centro', 1, 1, 'Mitre 250, San Carlos de Bariloche', '(0294) 443-8899', 'Lunes a Domingo de 09:00 a 02:00', -41.13400000, -71.30600000, 'Bariloche', 1),
(30, 'Mostaza Neuquén Alto Comahue', 1, 1, 'Dr. Ramón 355 (Patio de Comidas), Neuquén Capital', '(0299) 449-3300', 'Lunes a Domingo de 10:00 a 00:00', -38.94200000, -68.05800000, 'Neuquén', 1),
(31, 'Mostaza Formosa Centro', 1, 5, 'Av. 25 de Mayo 150', '(0370) 443-2100', 'Lunes a Domingo de 08:00 a 02:00', -26.18520000, -58.17560000, 'Formosa Capital', 1),
(32, 'Mostaza Costanera Formosa', 2, 5, 'Av. Costanera y 25 de Mayo (Paseo Costanero)', '(0370) 443-2101', 'Lunes a Domingo de 11:00 a 03:00', -26.18120000, -58.16950000, 'Formosa Capital', 1),
(33, 'Mostaza Paseo Sarmiento', 3, 5, 'Av. Gobernador Gutnisky 2500 (Paseo Sarmiento)', '(0370) 443-2102', 'Lunes a Domingo de 10:00 a 00:00', -26.19650000, -58.20400000, 'Formosa Capital', 1);

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
(1, '06:00:00', '14:00:00', 'Turno Ma├▒ana/Apertura', 'Lun a Vie'),
(2, '14:00:00', '22:00:00', 'Turno Tarde', 'Mar a S├íb'),
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
(4, 'Beto18', 'G├│mez', '3704848212', 'B┬░ El Quebranto mz1 cs 35', 'applegomez64@gmail.com', '$2y$10$j56Ycz5rQS2IgkDbEWTRN.P4b1ODz2r/qhfIaLpuR/QhGyKTEnPq.', NULL, NULL, 0, 1, '2026-07-31 08:21:16', 207, 'Avenida Presidente Doctor Raúl Alfonsín, Municipio de Formosa', '3704848212'),
(5, 'Vicky', 'Marroco', '3705148612', 'Clorinda', 'victoriamarrocco95@gmail.com', '$2y$10$KgPxL42VFvKMooOqUaH1buarO.fz0/PbJoQBSP.Zi9I.btFLNLWDS', NULL, NULL, 0, 1, '2026-07-31 08:24:31', 0, NULL, NULL),
(6, 'Mirian', 'G├│mez', '03704232251', 'B┬░ El Quebranto mz1 cs 35', 'mirianeliza991@gmail.com', '', NULL, NULL, 0, 1, '2026-08-16 20:23:47', 0, NULL, NULL),
(7, 'Camilasixseven', 'Riquelme', '3704847532', 'El Porvenir', 'cmilasanches@gmail.com', '', NULL, NULL, 0, 1, '2026-08-25 08:25:27', 178, 'El Porvenir', '3704847532');

-- --------------------------------------------------------

--
-- Estructura para la vista `repartidor`
--
DROP TABLE IF EXISTS `repartidor`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `repartidor`  AS SELECT `empleado`.`idempleado` AS `idrepartidor`, `empleado`.`idempleado` AS `idempleado`, concat(`empleado`.`nombre`,' ',`empleado`.`apellido`) AS `nombre`, `empleado`.`dni` AS `dni`, `empleado`.`telefono` AS `telefono`, `empleado`.`vehiculo` AS `vehiculo`, `empleado`.`patente` AS `patente`, `empleado`.`token` AS `pin`, `empleado`.`avatar` AS `avatar`, `empleado`.`sucursal_base` AS `sucursal_base`, `empleado`.`activo` AS `activo`, `empleado`.`estado_servicio` AS `estado_servicio`, `empleado`.`calificacion` AS `calificacion`, `empleado`.`viajes_totales` AS `viajes_totales`, `empleado`.`email` AS `email` FROM `empleado` WHERE `empleado`.`fk_id_rol` = 6 ;

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
-- Indices de la tabla `caja_retiro`
--
ALTER TABLE `caja_retiro`
  ADD PRIMARY KEY (`idretiro`),
  ADD KEY `idx_retiro_arqueo` (`idarqueo`);

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
-- Indices de la tabla `pedido_auditoria`
--
ALTER TABLE `pedido_auditoria`
  ADD PRIMARY KEY (`idauditoria`),
  ADD KEY `idx_auditoria_pedido` (`idpedido`),
  ADD KEY `idx_auditoria_fecha` (`fecha_hora`),
  ADD KEY `idx_auditoria_rol` (`rol_responsable`),
  ADD KEY `idx_auditoria_empleado` (`idempleado`);

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
-- Indices de la tabla `receta`
--
ALTER TABLE `receta`
  ADD PRIMARY KEY (`idreceta`);

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
-- Indices de la tabla `solicitud_ingrediente`
--
ALTER TABLE `solicitud_ingrediente`
  ADD PRIMARY KEY (`idsolicitud`);

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
  MODIFY `idarqueo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `caja_retiro`
--
ALTER TABLE `caja_retiro`
  MODIFY `idretiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `iddetalle_factura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido_web`
--
ALTER TABLE `detalle_pedido_web`
  MODIFY `iddetalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

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
  MODIFY `idempleado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `encabezadoticket`
--
ALTER TABLE `encabezadoticket`
  MODIFY `idencabezadoTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `idfactura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

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
-- AUTO_INCREMENT de la tabla `pedido_auditoria`
--
ALTER TABLE `pedido_auditoria`
  MODIFY `idauditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT de la tabla `pedido_web`
--
ALTER TABLE `pedido_web`
  MODIFY `idpedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

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
-- AUTO_INCREMENT de la tabla `receta`
--
ALTER TABLE `receta`
  MODIFY `idreceta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `registro_turno_empleado`
--
ALTER TABLE `registro_turno_empleado`
  MODIFY `idregistro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `resumenticket`
--
ALTER TABLE `resumenticket`
  MODIFY `idresumenTicket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `idrol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `seccion_mesa`
--
ALTER TABLE `seccion_mesa`
  MODIFY `idseccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `solicitud_ingrediente`
--
ALTER TABLE `solicitud_ingrediente`
  MODIFY `idsolicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `stock_insumo`
--
ALTER TABLE `stock_insumo`
  MODIFY `idinsumo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `idsucursal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
