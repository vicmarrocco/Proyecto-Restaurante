-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-06-2026 a las 14:29:49
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
-- Base de datos: `restaurante`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `barrio`
--

CREATE TABLE `barrio` (
  `idbarrio` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `idcaja` int(11) NOT NULL,
  `montoFisico` decimal(10,2) DEFAULT NULL,
  `montoVirtual` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudad`
--

CREATE TABLE `ciudad` (
  `idciudad` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detallemp`
--

CREATE TABLE `detallemp` (
  `iddetalleMp` int(11) NOT NULL,
  `fk_id_mp` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `email`
--

CREATE TABLE `email` (
  `idemail` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `genero`
--

CREATE TABLE `genero` (
  `idgenero` int(11) NOT NULL,
  `genero` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `insumopedido`
--

CREATE TABLE `insumopedido` (
  `idinsumoPedido` int(11) NOT NULL,
  `fk_id_insumo` int(11) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mp`
--

CREATE TABLE `mp` (
  `idmp` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `formato` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pieticket`
--

CREATE TABLE `pieticket` (
  `idpieTicket` int(11) NOT NULL,
  `fk_id_detalleMp` int(11) DEFAULT NULL,
  `vuelto` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `idrol` int(11) NOT NULL,
  `rol` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono`
--

CREATE TABLE `telefono` (
  `idtelefono` int(11) NOT NULL,
  `codigo_de_area` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  MODIFY `idbarrio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `idcaja` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  MODIFY `idciudad` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `idcliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comida`
--
ALTER TABLE `comida`
  MODIFY `idcomida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detallemp`
--
ALTER TABLE `detallemp`
  MODIFY `iddetalleMp` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalleticket`
--
ALTER TABLE `detalleticket`
  MODIFY `iddetalleTicket` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `iddireccion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `email`
--
ALTER TABLE `email`
  MODIFY `idemail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `idempleado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encabezadoticket`
--
ALTER TABLE `encabezadoticket`
  MODIFY `idencabezadoTicket` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `genero`
--
ALTER TABLE `genero`
  MODIFY `idgenero` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insumo`
--
ALTER TABLE `insumo`
  MODIFY `idinsumo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `insumopedido`
--
ALTER TABLE `insumopedido`
  MODIFY `idinsumoPedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mp`
--
ALTER TABLE `mp`
  MODIFY `idmp` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `idpedido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pieticket`
--
ALTER TABLE `pieticket`
  MODIFY `idpieTicket` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `idproveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resumenticket`
--
ALTER TABLE `resumenticket`
  MODIFY `idresumenTicket` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `idrol` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `idsucursal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `telefono`
--
ALTER TABLE `telefono`
  MODIFY `idtelefono` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket`
--
ALTER TABLE `ticket`
  MODIFY `idticket` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `idturnos` int(11) NOT NULL AUTO_INCREMENT;

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
