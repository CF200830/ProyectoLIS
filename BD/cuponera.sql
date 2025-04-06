-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS `cuponera`;
USE `cuponera`;

-- Crear tabla rubro
CREATE TABLE IF NOT EXISTS `rubro` (
    `id_rubro` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_rubro` VARCHAR(50) NOT NULL UNIQUE
);

-- Crear tabla empresa
CREATE TABLE IF NOT EXISTS `empresa` (
    `id_empresa` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_empresa` VARCHAR(100) NOT NULL,
    `codigo_empresa` CHAR(6) NOT NULL UNIQUE,
    `nombre_contacto` VARCHAR(100) NOT NULL,
    `direccion` TEXT NOT NULL,
    `telefono` VARCHAR(20) NOT NULL,
    `correo` VARCHAR(100) NOT NULL,
    `id_rubro` INT NOT NULL,
    `porcentaje_comision` DECIMAL(5, 2) NOT NULL,
    FOREIGN KEY (`id_rubro`) REFERENCES `rubro`(`id_rubro`)
);

-- Crear tabla usuario
CREATE TABLE IF NOT EXISTS `usuario` (
    `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `apellido` VARCHAR(100) NOT NULL,
    `correo` VARCHAR(100) NOT NULL UNIQUE,
    `contraseña` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(20) NOT NULL,
    `direccion` TEXT NOT NULL,
    `dui` VARCHAR(10) NOT NULL UNIQUE,
    `tipo_usuario` ENUM('cliente', 'proveedor') NOT NULL,
    `verificado` TINYINT(1) DEFAULT 0,
    `token_verificacion` VARCHAR(255)
);

-- Crear tabla empleado
CREATE TABLE IF NOT EXISTS `empleado` (
    `id_empleado` INT AUTO_INCREMENT PRIMARY KEY,
    `id_empresa` INT NOT NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `apellido` VARCHAR(100) NOT NULL,
    `correo` VARCHAR(100) NOT NULL UNIQUE,
    `contraseña` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(20) NOT NULL,
    `direccion` TEXT NOT NULL,
    `dui` VARCHAR(10) NOT NULL UNIQUE,
    `tipo_usuario` ENUM('administrador', 'vendedor') NOT NULL,
    `verificado` TINYINT(1) DEFAULT 0,
    `token_verificacion` VARCHAR(255),
    FOREIGN KEY (`id_empresa`) REFERENCES `empresa`(`id_empresa`)
);

-- Crear tabla oferta
CREATE TABLE IF NOT EXISTS `oferta` (
    `id_oferta` INT AUTO_INCREMENT PRIMARY KEY,
    `id_empresa` INT NOT NULL,
    `titulo` VARCHAR(100) NOT NULL,
    `precio_regular` DECIMAL(10, 2) NOT NULL,
    `precio_oferta` DECIMAL(10, 2) NOT NULL,
    `fecha_inicio` DATE NOT NULL,
    `fecha_fin` DATE NOT NULL,
    `fecha_limite` DATE NOT NULL,
    `cantidad_limite` INT NOT NULL,
    `descripcion` TEXT NOT NULL,
    `estado` ENUM('activo', 'inactivo', 'rechazado') NOT NULL,
    `justificacion_rechazo` TEXT,
    FOREIGN KEY (`id_empresa`) REFERENCES `empresa`(`id_empresa`)
);

-- Crear tabla cupon
CREATE TABLE IF NOT EXISTS `cupon` (
    `id_cupon` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_cupon` VARCHAR(20) NOT NULL UNIQUE,
    `id_oferta` INT NOT NULL,
    `id_cliente` INT NOT NULL,
    `fecha_compra` DATETIME NOT NULL,
    `estado` ENUM('activo', 'utilizado', 'caducado') NOT NULL,
    FOREIGN KEY (`id_oferta`) REFERENCES `oferta`(`id_oferta`),
    FOREIGN KEY (`id_cliente`) REFERENCES `usuario`(`id_usuario`)
);

-- Crear tabla compra
CREATE TABLE IF NOT EXISTS `compra` (
    `id_compra` INT AUTO_INCREMENT PRIMARY KEY,
    `id_cliente` INT NOT NULL,
    `fecha_compra` DATETIME NOT NULL,
    `total` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`id_cliente`) REFERENCES `usuario`(`id_usuario`)
);

-- Crear tabla detalle_compra
CREATE TABLE IF NOT EXISTS `detalle_compra` (
    `id_detalle_compra` INT AUTO_INCREMENT PRIMARY KEY,
    `id_compra` INT NOT NULL,
    `id_cupon` INT,
    `cantidad` INT NOT NULL,
    `precio_unitario` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`id_compra`) REFERENCES `compra`(`id_compra`),
    FOREIGN KEY (`id_cupon`) REFERENCES `cupon`(`id_cupon`)
);
DATOS INGRESADOS
 -- Tabla rubro
INSERT INTO `rubro` (`nombre_rubro`) VALUES 
('Tecnología'), 
('Restaurantes');

-- Tabla empresa
INSERT INTO `empresa` (`nombre_empresa`, `codigo_empresa`, `nombre_contacto`, `direccion`, `telefono`, `correo`, `id_rubro`, `porcentaje_comision`) VALUES 
('TechNova S.A.', 'TEC001', 'Laura Pérez', 'San Salvador, El Salvador', '2222-3333', 'contacto@technova.com', 1, 10.00),
('Delicias Express', 'RES002', 'Carlos Gómez', 'Santa Tecla, El Salvador', '2345-6789', 'info@delicias.com', 2, 12.50);

-- Tabla usuario
INSERT INTO `usuario` (`nombre`, `apellido`, `correo`, `contraseña`, `telefono`, `direccion`, `dui`, `tipo_usuario`, `verificado`, `token_verificacion`) VALUES 
('María', 'Ramírez', 'maria.ramirez@gmail.com', 'pass1234', '7890-1234', 'Col. Escalón', '01234567-8', 'cliente', 1, NULL),
('Juan', 'Hernández', 'juan.hdz@hotmail.com', 'segura456', '7564-3210', 'Soyapango', '12345678-9', 'cliente', 0, 'abc123token');

-- Tabla empleado
INSERT INTO `empleado` (`id_empresa`, `nombre`, `apellido`, `correo`, `contraseña`, `telefono`, `direccion`, `dui`, `tipo_usuario`, `verificado`, `token_verificacion`) VALUES 
(1, 'Ana', 'Lopez', 'ana.lopez@technova.com', 'clave123', '2211-3344', 'San Salvador', '23456789-0', 'administrador', 1, NULL),
(2, 'Luis', 'Martínez', 'luis.mtz@delicias.com', 'ventas456', '2233-4455', 'Santa Tecla', '34567890-1', 'vendedor', 0, 'token123xyz');

-- Tabla oferta
INSERT INTO `oferta` (`id_empresa`, `titulo`, `precio_regular`, `precio_oferta`, `fecha_inicio`, `fecha_fin`, `fecha_limite`, `cantidad_limite`, `descripcion`, `estado`, `justificacion_rechazo`) VALUES 
(1, 'Combo Gamer', 500.00, 399.99, '2025-04-01', '2025-04-30', '2025-04-25', 100, 'Incluye teclado, mouse y audífonos gamer.', 'activo', NULL),
(2, 'Menú Ejecutivo 2x1', 12.00, 6.00, '2025-04-01', '2025-04-15', '2025-04-10', 50, 'Promoción en almuerzos ejecutivos.', 'activo', NULL);

-- Tabla cupon
INSERT INTO `cupon` (`codigo_cupon`, `id_oferta`, `id_cliente`, `fecha_compra`, `estado`) VALUES 
('CUPON001', 1, 1, NOW(), 'activo'),
('CUPON002', 2, 2, NOW(), 'utilizado');

-- Tabla compra
INSERT INTO `compra` (`id_cliente`, `fecha_compra`, `total`) VALUES 
(1, NOW(), 399.99),
(2, NOW(), 12.00);

-- Tabla detalle_compra
INSERT INTO `detalle_compra` (`id_compra`, `id_cupon`, `cantidad`, `precio_unitario`) VALUES 
(1, 1, 1, 399.99),
(2, 2, 1, 12.00);