-- ============================================================
-- schema_web.sql
-- Tablas adicionales para el sitio de clientes de Mostaza.
-- Importar DESPUÉS de rest__2_.sql (usa la misma base `rest`).
-- ============================================================

USE `rest`;

-- --------------------------------------------------------
-- Tabla `usuario_web`
-- Clientes que se registran en la página pública.
-- El login es SOLO por email + token (no se guarda contraseña).
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
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idusuario`),
  UNIQUE KEY `email_unico` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `producto`
-- Catálogo público (lo que se muestra en cada categoría del sitio).
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
-- Tabla `pedido_web`
-- Encabezado de cada pedido confirmado desde el carrito.
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pedido_web` (
  `idpedido` INT(11) NOT NULL AUTO_INCREMENT,
  `idusuario` INT(11) NOT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total` DECIMAL(10,2) NOT NULL,
  `estado` VARCHAR(30) NOT NULL DEFAULT 'Recibido',
  PRIMARY KEY (`idpedido`),
  KEY `idx_usuario` (`idusuario`),
  CONSTRAINT `fk_pedidoweb_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario_web` (`idusuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `detalle_pedido_web`
-- Líneas de cada pedido (productos del catálogo o personalizados).
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `detalle_pedido_web` (
  `iddetalle` INT(11) NOT NULL AUTO_INCREMENT,
  `idpedido` INT(11) NOT NULL,
  `idproducto` INT(11) DEFAULT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `precio_unitario` DECIMAL(10,2) NOT NULL,
  `cantidad` INT(11) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`iddetalle`),
  KEY `idx_pedido` (`idpedido`),
  CONSTRAINT `fk_detalleweb_pedido` FOREIGN KEY (`idpedido`) REFERENCES `pedido_web` (`idpedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalleweb_producto` FOREIGN KEY (`idproducto`) REFERENCES `producto` (`idproducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SEED: catálogo de productos (a partir del sitio demo_restaurante)
-- ============================================================

INSERT INTO `producto` (`nombre`, `descripcion`, `precio`, `imagen`, `categoria`) VALUES
-- Exclusivos
('Hambre de la Cuarta | Provolone y Bacon', 'Cuarto de libra con provolone gratinado y bacon crocante', 9800.00, 'cardex.jpg', 'exclusivos'),
('La Cuarta | Ahumada', 'Cuarto de libra con salsa ahumada de la casa', 9300.00, 'card1.jpg', 'exclusivos'),
('La Cuarta | Merlo', 'Cuarto de libra especial estilo Merlo', 9500.00, 'merlo.jpg', 'exclusivos'),

-- Mega Combos
('Hambre de la Cuarta | Provolone y Bacon', 'Combo con papas y bebida', 11800.00, 'cardex.jpg', 'megacombos'),
('La Cuarta | Ahumada', 'Combo con papas y bebida', 11300.00, 'card1.jpg', 'megacombos'),
('La Cuarta | Merlo', 'Combo con papas y bebida', 11500.00, 'merlo.jpg', 'megacombos'),
('Doble Mega Big Bang Cheddar', 'Doble carne, doble cheddar, combo con papas y bebida', 12800.00, 'doblwcheddar.jpg', 'megacombos'),
('Doble Mega Boom', 'Doble carne con salsa boom, combo con papas y bebida', 12600.00, 'boom.jpg', 'megacombos'),
('Doble Cuarto a Fondo', 'Doble cuarto de libra a puro sabor, combo con papas y bebida', 13200.00, 'fondo.jpg', 'megacombos'),
('Doble Mega Taste', 'Doble carne receta Taste, combo con papas y bebida', 12400.00, 'taste1.jpg', 'megacombos'),
('Doble Mega Cuarto Bacon', 'Doble cuarto de libra con bacon, combo con papas y bebida', 12900.00, 'taste2.jpg', 'megacombos'),
('Doble Mega Cuarto', 'Doble cuarto de libra clásico, combo con papas y bebida', 12000.00, 'cuarto.jpg', 'megacombos'),

-- Pollo
('Pollo Crispy Bacon', 'Pollo crocante con bacon y salsa especial', 8600.00, 'pollobacon.jpg', 'pollo'),
('Pollo Crispy', 'Clásico sandwich de pollo crocante', 7900.00, 'pollocrispy.jpg', 'pollo'),
('Pollo Crispy Cheddar', 'Pollo crocante con cheddar fundido', 8300.00, 'pollocheddar.jpg', 'pollo'),
('Nuggets de Pollo x6', '6 unidades de nuggets de pollo', 4800.00, 'x6.jpg', 'pollo'),
('Nuggets de Pollo x10', '10 unidades de nuggets de pollo', 6900.00, 'x10.jpg', 'pollo'),
('Nuggets Bacon & Cheddar x10', '10 nuggets rellenos de bacon y cheddar', 7900.00, 'nuggetachedar.jpg', 'pollo'),
('Balde de Nuggets', 'Balde grande para compartir', 12500.00, 'balde.jpg', 'pollo'),

-- Ensaladas
('Ensalada Palta y Huevo Grill', 'Con pollo grillado, palta y huevo', 6800.00, 'ensaladapalta1.jpg', 'ensaladas'),
('Ensalada Palta y Huevo Veggie', 'Opción veggie con palta y huevo', 6500.00, 'ensaladapalta2.jpg', 'ensaladas'),
('Ensalada Caesar Crispy', 'Con pollo crocante, verdes y aderezo caesar', 6900.00, 'Caesar 2.jpg', 'ensaladas'),
('Ensalada Caesar Grill', 'Con pollo grillado, verdes y aderezo caesar', 6900.00, 'x10.jpg', 'ensaladas'),
('Wrap Cheddar & Pollo', 'Wrap de pollo con cheddar fundido', 6200.00, 'wrap.jpg', 'ensaladas'),

-- Para Vos (opciones especiales)
('Cero Veggie Cheese', 'Hamburguesa veggie con cheddar', 8200.00, 'card3.jpg', 'paravos'),
('Cero Veggie', 'Hamburguesa 100% veggie', 7800.00, 'cerovegiee.jpg', 'paravos'),
('Mega Cuarto Doble sin TACC', 'Opción apta celíacos', 10800.00, 'sintacc.jpg', 'paravos'),

-- Cajitas (menú infantil)
('Cajita Mostaza Héroes Clásica', 'Hamburguesa chica + papas + juguete sorpresa', 5200.00, 'cajita1.jpg', 'cajitas'),
('Cajita Mostaza Héroes Nuggets', 'Nuggets + papas + juguete sorpresa', 5200.00, 'cajita2.jpg', 'cajitas'),
('Cajita Mostaza Héroes Cheese', 'Hamburguesa con cheddar + papas + juguete sorpresa', 5400.00, 'cajita3.jpg', 'cajitas'),
('Cajita Mostaza Héroes Doble', 'Doble hamburguesa chica + papas + juguete sorpresa', 5900.00, 'cajita4.jpg', 'cajitas'),
('Cajita Mostaza Héroes Deluxe', 'Hamburguesa + papas + postre + juguete sorpresa', 6300.00, 'cajita5.jpg', 'cajitas'),

-- Postres y Cafetería
('Tostado Queso & Jamón', 'Tostado clásico de jamón y queso', 3800.00, 'tostado.jpg', 'postres'),
('Tostado XL Queso & Jamón', 'Versión XL del tostado de jamón y queso', 4600.00, 'tostadoxl.jpg', 'postres'),
('Sandwich Huevo & Palta', 'Sandwich de huevo con palta', 4200.00, 'huevopalta.jpg', 'postres'),
('Sandwich Huevo & Bacon', 'Sandwich de huevo con bacon', 4400.00, 'huevobacon.jpg', 'postres'),
('Medialunas', 'Porción de medialunas', 2200.00, 'medialunas1.jpg', 'postres'),
('Medialunas Jamón & Queso', 'Medialunas rellenas de jamón y queso', 2800.00, 'medialunas2.jpg', 'postres'),
('Alfajor de Chocolate', 'Alfajor bañado en chocolate', 1800.00, 'alfajor.jpg', 'postres'),
('Budín de Banana', 'Porción de budín de banana casero', 2100.00, 'card7.jpg', 'postres'),
('Shake Capuccino Frozen', 'Shake helado sabor capuccino', 3600.00, 'shake.jpg', 'postres'),
('Cono ARG', 'Cono helado edición Argentina', 2500.00, 'Conoarg.jpg', 'postres'),
('Cono Clásico', 'Cono helado clásico', 2200.00, 'conoclasi.jpg', 'postres');
