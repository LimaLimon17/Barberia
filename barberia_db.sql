-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 02:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `barberia_db`
--
CREATE DATABASE IF NOT EXISTS `barberia_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `barberia_db`;

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ActualizarPorcentajeProducto` (IN `pIdProducto` INT, IN `pPorcentajeVenta` DECIMAL(5,2), IN `pPorcentajeBarbero` DECIMAL(5,2), IN `pIdAdmin` INT, IN `pIP` VARCHAR(50))   BEGIN
    DECLARE vCostoCompra DECIMAL(10,2) DEFAULT NULL;
    DECLARE vNuevoPrecio DECIMAL(10,2);

    IF pPorcentajeVenta < 10 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El porcentaje de venta mínimo es 10%';
    END IF;

    IF pPorcentajeBarbero >= pPorcentajeVenta THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La comisión del barbero no puede igualar o superar el porcentaje de venta';
    END IF;

    SET vCostoCompra = (SELECT `CostoCompra` FROM `Productos` WHERE `IdProducto` = pIdProducto LIMIT 1);

    IF vCostoCompra IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no encontrado';
    END IF;

    SET vNuevoPrecio = ROUND(vCostoCompra * (1 + pPorcentajeVenta / 100), 2);

    UPDATE `HistorialPorcentajeProductos`
    SET `FechaFin` = CURDATE()
    WHERE `IdProducto` = pIdProducto AND `FechaFin` IS NULL;

    INSERT INTO `HistorialPorcentajeProductos`
        (`IdProducto`, `PorcentajeVenta`, `PorcentajeBarbero`, `PrecioVenta`, `FechaInicio`, `FechaFin`, `EstadoA`, `FechaA`, `UsuarioA`)
    VALUES
        (pIdProducto, pPorcentajeVenta, pPorcentajeBarbero, vNuevoPrecio, CURDATE(), NULL, 1, NOW(), pIdAdmin);

    UPDATE `Productos`
    SET `PorcentajeVenta` = pPorcentajeVenta,
        `PorcentajeBarbero` = pPorcentajeBarbero,
        `PrecioVenta` = vNuevoPrecio
    WHERE `IdProducto` = pIdProducto;

    CALL `sp_RegistrarAuditoria`(
        'Productos',
        CAST(pIdProducto AS CHAR),
        'U',
        'PorcentajeVenta|PorcentajeBarbero|PrecioVenta',
        NULL,
        CONCAT(pPorcentajeVenta, '|', pPorcentajeBarbero, '|', vNuevoPrecio),
        pIdAdmin,
        pIP,
        'Admin actualizó porcentajes del producto'
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AuditoriaLoginExitoso` (IN `pIdUsuario` INT, IN `pIP` VARCHAR(50))   BEGIN
    DECLARE vExisteUsuario INT DEFAULT 0;

    SELECT COUNT(*) INTO vExisteUsuario
    FROM `Usuarios`
    WHERE `IdUsuario` = pIdUsuario;

    IF vExisteUsuario = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no encontrado para auditoría de login exitoso';
    END IF;

    CALL `sp_RegistrarAuditoria`('Usuarios', CAST(pIdUsuario AS CHAR), 'LOGIN_EXITOSO', 'Autenticacion', NULL, 'Acceso concedido', pIdUsuario, pIP, 'Inicio de sesión correcto');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AuditoriaLoginFallido` (IN `pCorreo` VARCHAR(100), IN `pIP` VARCHAR(50))   BEGIN
    CALL `sp_RegistrarAuditoria`('Usuarios', NULL, 'LOGIN_FALLIDO', 'Autenticacion', NULL, pCorreo, NULL, pIP, 'Intento de inicio de sesión fallido');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_DesactivarBarbero` (IN `pIdBarbero` INT, IN `pIdAdmin` INT, IN `pIP` VARCHAR(50))   BEGIN
    DECLARE vIdUsuario INT DEFAULT NULL;

    SET vIdUsuario = (SELECT `IdUsuario` FROM `Barberos` WHERE `IdBarbero` = pIdBarbero LIMIT 1);

    IF vIdUsuario IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barbero no encontrado';
    END IF;

    UPDATE `Usuarios`
    SET `EstadoA` = 0
    WHERE `IdUsuario` = vIdUsuario;

    UPDATE `Barberos`
    SET `EstadoA` = 0
    WHERE `IdBarbero` = pIdBarbero;

    CALL `sp_RegistrarAuditoria`('Barberos', CAST(pIdBarbero AS CHAR), 'U', 'EstadoA', '1', '0', pIdAdmin, pIP, 'Admin desactivó barbero');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_EditarPerfilBarbero` (IN `pIdBarbero` INT, IN `pNombre1` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pNombre2` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pApellido1` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pApellido2` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pCorreo` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pFechaIngreso` DATE, IN `pIdAdmin` INT, IN `pIP` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci)   BEGIN
    DECLARE vIdUsuario      INT          DEFAULT NULL;
    DECLARE vCorreoAnterior VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE vFechaAnterior  DATE;
    DECLARE vExiste         INT          DEFAULT 0;

    SELECT COUNT(*) INTO vExiste
    FROM `Barberos` b
    INNER JOIN `Usuarios` u ON u.`IdUsuario` = b.`IdUsuario`
    WHERE b.`IdBarbero` = pIdBarbero;

    IF vExiste = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Barbero no encontrado';
    END IF;

    SELECT u.`IdUsuario`, u.`Correo`, b.`FechaIngreso`
    INTO vIdUsuario, vCorreoAnterior, vFechaAnterior
    FROM `Usuarios` u
    INNER JOIN `Barberos` b ON u.`IdUsuario` = b.`IdUsuario`
    WHERE b.`IdBarbero` = pIdBarbero;

    SELECT COUNT(*) INTO vExiste
    FROM `Usuarios`
    WHERE `Correo` = pCorreo AND `IdUsuario` <> vIdUsuario;

    IF vExiste > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El correo ya existe';
    END IF;

    IF pFechaIngreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser futura';
    END IF;

    UPDATE `Usuarios`
    SET `Nombre1`   = pNombre1,
        `Nombre2`   = pNombre2,
        `Apellido1` = pApellido1,
        `Apellido2` = pApellido2,
        `Correo`    = pCorreo
    WHERE `IdUsuario` = vIdUsuario;

    UPDATE `Barberos`
    SET `FechaIngreso` = pFechaIngreso
    WHERE `IdBarbero` = pIdBarbero;

    IF NOT (vCorreoAnterior <=> pCorreo) THEN
        CALL `sp_RegistrarAuditoria`('Usuarios', CAST(vIdUsuario AS CHAR), 'U', 'Correo',
            vCorreoAnterior, pCorreo, pIdAdmin, pIP, 'Admin modificó correo del barbero');
    END IF;

    IF NOT (vFechaAnterior <=> pFechaIngreso) THEN
        CALL `sp_RegistrarAuditoria`('Barberos', CAST(pIdBarbero AS CHAR), 'U', 'FechaIngreso',
            CAST(vFechaAnterior AS CHAR), CAST(pFechaIngreso AS CHAR), pIdAdmin, pIP,
            'Admin modificó fecha de ingreso');
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegistrarAuditoria` (IN `pTablaNombre` VARCHAR(50), IN `pRegistroId` VARCHAR(50), IN `pAccion` VARCHAR(50), IN `pCampo` VARCHAR(100), IN `pValorAnterior` LONGTEXT, IN `pValorNuevo` LONGTEXT, IN `pUsuarioA` INT, IN `pDireccionIP` VARCHAR(50), IN `pDetalles` VARCHAR(500))   BEGIN
    DECLARE vUsuarioA INT DEFAULT NULL;
    DECLARE vExisteUsuario INT DEFAULT 0;

    IF pUsuarioA IS NOT NULL THEN
        SELECT COUNT(*) INTO vExisteUsuario
        FROM `Usuarios`
        WHERE `IdUsuario` = pUsuarioA;

        IF vExisteUsuario > 0 THEN
            SET vUsuarioA = pUsuarioA;
        END IF;
    END IF;

    INSERT INTO `AuditoriaGeneral`
        (`TablaNombre`, `RegistroId`, `Accion`, `Campo`, `ValorAnterior`, `ValorNuevo`, `UsuarioA`, `FechaA`, `DireccionIP`, `Detalles`)
    VALUES
        (pTablaNombre, pRegistroId, pAccion, pCampo, pValorAnterior, pValorNuevo, vUsuarioA, NOW(), pDireccionIP, pDetalles);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegistrarBarbero` (IN `pNombre1` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pNombre2` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pApellido1` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pApellido2` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pCorreo` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pContrasena` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN `pFechaIngreso` DATE, IN `pIdAdmin` INT, IN `pIP` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, OUT `pIdBarberoNuevo` INT)   BEGIN
    DECLARE vIdUsuario    INT DEFAULT NULL;
    DECLARE vIdRolBarbero INT DEFAULT NULL;
    DECLARE vExiste       INT DEFAULT 0;

    SELECT COUNT(*) INTO vExiste
    FROM `Usuarios`
    WHERE `Correo` = pCorreo;

    IF vExiste > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El correo ya está registrado en el sistema';
    END IF;

    IF pFechaIngreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser posterior a hoy';
    END IF;

    SET vIdRolBarbero = (SELECT `IdRol` FROM `Roles` WHERE `Nombre` = 'Barbero' LIMIT 1);

    IF vIdRolBarbero IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No existe el rol Barbero';
    END IF;

    INSERT INTO `Usuarios` (`IdRol`, `Nombre1`, `Nombre2`, `Apellido1`, `Apellido2`, `Correo`, `Contraseña`, `EstadoA`, `FechaA`, `UsuarioA`)
    VALUES (vIdRolBarbero, pNombre1, pNombre2, pApellido1, pApellido2, pCorreo, pContrasena, 1, NOW(), pIdAdmin);

    SET vIdUsuario = LAST_INSERT_ID();

    INSERT INTO `Barberos` (`IdUsuario`, `FechaIngreso`, `EstadoA`, `FechaA`, `UsuarioA`)
    VALUES (vIdUsuario, pFechaIngreso, 1, NOW(), pIdAdmin);

    SET pIdBarberoNuevo = LAST_INSERT_ID();

    CALL `sp_RegistrarAuditoria`('Barberos', CAST(pIdBarberoNuevo AS CHAR), 'I', 'Registro completo',
        NULL, pCorreo, pIdAdmin, pIP, 'Admin registró nuevo barbero');
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `auditoriageneral`
--

CREATE TABLE `auditoriageneral` (
  `IdAuditoria` int(11) NOT NULL,
  `TablaNombre` varchar(50) DEFAULT NULL,
  `RegistroId` varchar(50) DEFAULT NULL,
  `Accion` varchar(50) DEFAULT NULL,
  `Campo` varchar(100) DEFAULT NULL,
  `ValorAnterior` longtext DEFAULT NULL,
  `ValorNuevo` longtext DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `DireccionIP` varchar(50) DEFAULT NULL,
  `Detalles` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `auditoriageneral`
--

INSERT INTO `auditoriageneral` VALUES(1, 'Clientes', '8464349', 'U', 'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA', '8464349|Mario|Luigi|33737373|luigi@gmail.com|1', '8464349|Mario|Luigi|33737373|luigi@gmail.com|1', 1, '2026-06-28 08:10:23', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(2, 'Reservas', '7', 'I', 'IdReserva|IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva', NULL, '7|8464349|1|2026-06-29|10:00:00|12:15:00|60.00|30.00|Pendiente', 1, '2026-06-28 08:10:23', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(3, 'ReservaServicios', '9', 'I', 'IdReservaServicio|IdServicio|IdReserva|EstadoA', NULL, '9|1|7|1', 1, '2026-06-28 08:10:23', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(4, 'Reservas', '7', 'U', 'IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva|FechaPagoAnt', '8464349|1|2026-06-29|10:00:00|12:15:00|60.00|30.00|Pendiente|1', '8464349|1|2026-06-29|10:00:00|12:15:00|60.00|30.00|Confirmada|2026-06-28 16:10:26|1', 1, '2026-06-28 08:10:26', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(5, 'Pagos', '11', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '11|7|Anticipo|30.00|2026-06-28 16:10:26|QR|Pagado', 1, '2026-06-28 08:10:26', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(6, 'Clientes', '8464349', 'U', 'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA', '8464349|Mario|Luigi|33737373|luigi@gmail.com|1', '8464349|Mario|Luigi|33737373|luigi@gmail.com|1', 1, '2026-06-28 08:11:11', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(7, 'Reservas', '8', 'I', 'IdReserva|IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva', NULL, '8|8464349|1|2026-06-29|14:00:00|14:40:00|20.00|10.00|Pendiente', 1, '2026-06-28 08:11:11', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(8, 'ReservaServicios', '10', 'I', 'IdReservaServicio|IdServicio|IdReserva|EstadoA', NULL, '10|10|8|1', 1, '2026-06-28 08:11:11', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(9, 'Reservas', '8', 'U', 'IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva|FechaPagoAnt', '8464349|1|2026-06-29|14:00:00|14:40:00|20.00|10.00|Pendiente|1', '8464349|1|2026-06-29|14:00:00|14:40:00|20.00|10.00|Confirmada|2026-06-28 16:11:13|1', 1, '2026-06-28 08:11:13', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(10, 'Pagos', '12', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '12|8|Anticipo|10.00|2026-06-28 16:11:13|QR|Pagado', 1, '2026-06-28 08:11:13', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(11, 'Clientes', '8464349', 'U', 'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA', '8464349|Mario|Luigi|33737373|luigi@gmail.com|1', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', 1, '2026-06-28 08:15:59', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(12, 'Reservas', '9', 'I', 'IdReserva|IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva', NULL, '9|8464349|1|2026-06-29|15:00:00|16:15:00|40.00|20.00|Pendiente', 1, '2026-06-28 08:15:59', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(13, 'ReservaServicios', '11', 'I', 'IdReservaServicio|IdServicio|IdReserva|EstadoA', NULL, '11|8|9|1', 1, '2026-06-28 08:15:59', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(14, 'ReservaServicios', '12', 'I', 'IdReservaServicio|IdServicio|IdReserva|EstadoA', NULL, '12|10|9|1', 1, '2026-06-28 08:15:59', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(15, 'Reservas', '9', 'U', 'IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva|FechaPagoAnt', '8464349|1|2026-06-29|15:00:00|16:15:00|40.00|20.00|Pendiente|1', '8464349|1|2026-06-29|15:00:00|16:15:00|40.00|20.00|Expirada|1', 1, '2026-06-28 08:31:00', NULL, NULL);
INSERT INTO `auditoriageneral` VALUES(16, 'Reservas', '7', 'U', 'IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva|FechaPagoAnt', '8464349|1|2026-06-29|10:00:00|12:15:00|60.00|30.00|Confirmada|2026-06-28 16:10:26|1', '8464349|1|2026-06-29|10:00:00|12:15:00|60.00|30.00|Completada|2026-06-28 16:10:26|1', 1, '2026-06-29 12:53:41', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(17, 'Clientes', '8464349', 'U', 'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', 2, '2026-06-29 13:19:22', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(18, 'Pagos', '13', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '13|4|Total|36.00|2026-06-29 21:19:22|QR|Pagado', 2, '2026-06-29 13:19:22', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(19, 'Pagos', '14', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '14|7|Saldo|30.00|2026-06-29 21:21:22|QR|Pagado', 2, '2026-06-29 13:21:22', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(20, 'Pagos', '15', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '15|3|Total|72.00|2026-06-29 21:21:22|QR|Pagado', 2, '2026-06-29 13:21:22', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(21, 'Clientes', '8464349', 'U', 'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', 2, '2026-06-29 13:31:12', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(22, 'Pagos', '16', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '16|5|Total|24.00|2026-06-29 21:31:12|Efectivo|Pagado', 2, '2026-06-29 13:31:12', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(23, 'Clientes', '8464349', 'U', 'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', '8464349|Mario|Luigi|733636363|luigi@gmail.com|1', 2, '2026-06-29 13:32:35', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(24, 'Reservas', '10', 'I', 'IdReserva|IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva', NULL, '10|8464349|1|2026-06-29|14:45:00|15:45:00|30.00|30.00|Confirmada', 2, '2026-06-29 13:32:35', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(25, 'ReservaServicios', '13', 'I', 'IdReservaServicio|IdServicio|IdReserva|EstadoA', NULL, '13|3|10|1', 2, '2026-06-29 13:32:36', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(26, 'Pagos', '17', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '17|10|Total|30.00|2026-06-29 21:32:36|Efectivo|Pagado', 2, '2026-06-29 13:32:36', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(27, 'Reservas', '10', 'U', 'IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva|FechaPagoAnt', '8464349|1|2026-06-29|14:45:00|15:45:00|30.00|30.00|Confirmada|1', '8464349|1|2026-06-29|14:45:00|15:45:00|30.00|30.00|Completada|1', 2, '2026-06-29 13:32:43', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(28, 'Pagos', '18', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '18|10|Saldo|0.00|2026-06-29 21:32:56|Efectivo|Pagado', 2, '2026-06-29 13:32:56', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(29, 'Pagos', '19', 'I', 'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago', NULL, '19|6|Total|36.00|2026-06-29 21:32:56|Efectivo|Pagado', 2, '2026-06-29 13:32:56', '127.0.0.1', NULL);
INSERT INTO `auditoriageneral` VALUES(30, 'Barberos', '7', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 13:52:29', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(31, 'Barberos', '7', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 13:52:33', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(32, 'Barberos', '7', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 13:52:53', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(33, 'Barberos', '10', 'U', 'EstadoA', '1', '0', 1, '2026-06-29 13:53:04', '127.0.0.1', 'Admin desactivó barbero');
INSERT INTO `auditoriageneral` VALUES(34, 'Barberos', '10', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 13:53:20', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(35, 'Barberos', '10', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 13:53:24', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(36, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 13:55:56', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(37, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:04:12', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(38, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:04:17', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(39, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:14:57', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(40, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:27:01', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(41, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:27:12', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(42, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:27:20', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(43, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:27:25', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(44, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:44:46', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(45, 'Usuarios', '2', 'CAMBIO_PASSWORD', 'Contraseña', NULL, 'Cambiado por el propio usuario', 2, '2026-06-29 14:45:02', '127.0.0.1', 'Barbero actualizó su propia contraseña');
INSERT INTO `auditoriageneral` VALUES(46, 'Barberos', '1', 'CONSULTA_PERFIL', 'Perfil', NULL, NULL, 2, '2026-06-29 14:53:11', '127.0.0.1', 'Barbero consultó su propio perfil');
INSERT INTO `auditoriageneral` VALUES(47, 'Barberos', '1', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 16:12:47', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(48, 'Barberos', '1', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 16:14:59', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(49, 'Barberos', '1', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 16:20:21', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(50, 'Barberos', '1', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 16:24:26', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(51, 'Barberos', '1', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 16:28:01', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(52, 'Barberos', '1', 'CONSULTA_ADMIN', 'Perfil', NULL, NULL, 1, '2026-06-29 16:28:19', '127.0.0.1', 'Admin consultó perfil del barbero');
INSERT INTO `auditoriageneral` VALUES(53, 'Barberos', '11', 'I', 'Registro completo', NULL, 'rulio@gmail.com', 1, '2026-06-29 16:33:16', '127.0.0.1', 'Admin registró nuevo barbero');

-- --------------------------------------------------------

--
-- Table structure for table `barberos`
--

CREATE TABLE `barberos` (
  `IdBarbero` int(11) NOT NULL,
  `IdUsuario` int(11) NOT NULL,
  `FechaIngreso` date NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `barberos`
--

INSERT INTO `barberos` VALUES(1, 2, '2026-01-05', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(2, 3, '2026-01-06', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(3, 4, '2026-01-07', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(4, 5, '2026-01-08', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(5, 6, '2026-01-09', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(6, 7, '2026-01-10', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(7, 8, '2026-01-11', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(8, 9, '2026-01-12', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(9, 10, '2026-01-13', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(10, 11, '2026-01-14', 0, '2026-06-26 10:56:43', 1);
INSERT INTO `barberos` VALUES(11, 12, '2026-06-29', 1, '2026-06-29 16:33:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categorias`
--

CREATE TABLE `categorias` (
  `IdCategoria` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `DuracionMinimaMinutos` int(11) NOT NULL,
  `DuracionMaximaMinutos` int(11) NOT NULL,
  `PrecioMin` decimal(10,2) NOT NULL,
  `PrecioMax` decimal(10,2) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categorias`
--

INSERT INTO `categorias` VALUES(1, 'Cortes Clásicos', 25, 35, 20.00, 20.00, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `categorias` VALUES(2, 'Cortes Modernos', 45, 60, 30.00, 30.00, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `categorias` VALUES(3, 'Ondulación', 120, 240, 60.00, 90.00, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `categorias` VALUES(4, 'Tinte de pelo', 30, 30, 50.00, 50.00, 1, '2026-06-26 10:56:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `CI` varchar(20) NOT NULL,
  `Nombre1` varchar(50) NOT NULL,
  `Apellido1` varchar(50) NOT NULL,
  `Telefono` varchar(20) DEFAULT NULL,
  `Correo` varchar(100) DEFAULT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` VALUES('1000001', 'Juan', 'Mamani', '71234561', 'juan@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000002', 'Pedro', 'Quispe', '71234562', 'pedro@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000003', 'Carlos', 'Condori', '71234563', 'carlos@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000004', 'Mario', 'Flores', '71234564', 'mario@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000005', 'Miguel', 'Choque', '71234565', 'miguel@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000006', 'Jose', 'Rojas', '71234566', 'jose@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000007', 'Kevin', 'Perez', '71234567', 'kevin@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000008', 'Luis', 'Arias', '71234568', 'luis@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000009', 'Jorge', 'Vargas', '71234569', 'jorge@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('1000010', 'Diego', 'Lopez', '71234570', 'diego@gmail.com', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `clientes` VALUES('8464349', 'Mario', 'Luigi', '733636363', 'luigi@gmail.com', 1, '2026-06-29 21:32:35', 2);

--
-- Triggers `clientes`
--
DELIMITER $$
CREATE TRIGGER `trg_clientes_insert` AFTER INSERT ON `clientes` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'Clientes', NEW.CI, 'I',
        'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA',
        NULL,
        CONCAT_WS('|', NEW.CI, NEW.Nombre1, NEW.Apellido1, NEW.Telefono, NEW.Correo, NEW.EstadoA),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_clientes_update` AFTER UPDATE ON `clientes` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'Clientes', NEW.CI, 'U',
        'CI|Nombre1|Apellido1|Telefono|Correo|EstadoA',
        CONCAT_WS('|', OLD.CI, OLD.Nombre1, OLD.Apellido1, OLD.Telefono, OLD.Correo, OLD.EstadoA),
        CONCAT_WS('|', NEW.CI, NEW.Nombre1, NEW.Apellido1, NEW.Telefono, NEW.Correo, NEW.EstadoA),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `comisiones`
--

CREATE TABLE `comisiones` (
  `IdComision` int(11) NOT NULL,
  `IdBarbero` int(11) NOT NULL,
  `IdReserva` int(11) DEFAULT NULL,
  `IdVenta` int(11) DEFAULT NULL,
  `TipoComision` char(3) NOT NULL,
  `Fecha` datetime NOT NULL,
  `MontoBase` decimal(10,2) NOT NULL,
  `Porcentaje` decimal(5,2) DEFAULT NULL,
  `MontoComision` decimal(10,2) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comisiones`
--

INSERT INTO `comisiones` VALUES(1, 1, 1, NULL, 'SER', '2026-06-26 10:56:44', 30.00, 50.00, 15.00, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `comisiones` VALUES(2, 2, 2, NULL, 'SER', '2026-06-26 10:56:44', 40.00, 50.00, 20.00, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `comisiones` VALUES(3, 5, 5, NULL, 'AUS', '2026-06-26 10:56:44', 40.00, 50.00, 20.00, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `comisiones` VALUES(4, 1, NULL, 1, 'PRO', '2026-06-26 10:56:44', 24.00, 10.00, 2.40, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `comisiones` VALUES(5, 2, NULL, 2, 'PRO', '2026-06-26 10:56:44', 72.00, 10.00, 7.20, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `comisiones` VALUES(6, 1, NULL, 3, 'PRO', '2026-06-29 21:03:33', 72.00, NULL, 6.00, 1, '2026-06-29 21:03:33', 2);
INSERT INTO `comisiones` VALUES(7, 1, NULL, 4, 'PRO', '2026-06-29 21:19:22', 36.00, NULL, 3.00, 1, '2026-06-29 21:19:22', 2);
INSERT INTO `comisiones` VALUES(8, 1, 7, NULL, 'SER', '2026-06-29 21:21:22', 60.00, 50.00, 30.00, 1, '2026-06-29 21:21:22', 2);
INSERT INTO `comisiones` VALUES(9, 1, NULL, 5, 'PRO', '2026-06-29 21:31:12', 24.00, NULL, 2.00, 1, '2026-06-29 21:31:12', 2);
INSERT INTO `comisiones` VALUES(10, 1, NULL, 6, 'PRO', '2026-06-29 21:32:51', 36.00, NULL, 3.00, 1, '2026-06-29 21:32:51', 2);
INSERT INTO `comisiones` VALUES(11, 1, 10, NULL, 'SER', '2026-06-29 21:32:56', 30.00, 50.00, 15.00, 1, '2026-06-29 21:32:56', 2);

-- --------------------------------------------------------

--
-- Table structure for table `detalleventa`
--

CREATE TABLE `detalleventa` (
  `IdDetalleVenta` int(11) NOT NULL,
  `IdVenta` int(11) NOT NULL,
  `IdProducto` int(11) NOT NULL,
  `Cantidad` int(11) NOT NULL,
  `PrecioUnitario` decimal(10,2) NOT NULL,
  `ComisionBarbero` decimal(10,2) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detalleventa`
--

INSERT INTO `detalleventa` VALUES(1, 1, 1, 1, 24.00, 2.40, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `detalleventa` VALUES(2, 2, 2, 1, 30.00, 3.00, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `detalleventa` VALUES(3, 2, 3, 1, 42.00, 4.20, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `detalleventa` VALUES(4, 3, 4, 2, 36.00, 6.00, 1, '2026-06-29 21:03:33', 2);
INSERT INTO `detalleventa` VALUES(5, 4, 4, 1, 36.00, 3.00, 1, '2026-06-29 21:19:22', 2);
INSERT INTO `detalleventa` VALUES(6, 5, 1, 1, 24.00, 2.00, 1, '2026-06-29 21:31:12', 2);
INSERT INTO `detalleventa` VALUES(7, 6, 9, 2, 18.00, 3.00, 1, '2026-06-29 21:32:51', 2);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historialporcentajeproductos`
--

CREATE TABLE `historialporcentajeproductos` (
  `IdHistorial` int(11) NOT NULL,
  `IdProducto` int(11) NOT NULL,
  `PorcentajeVenta` decimal(5,2) NOT NULL,
  `PorcentajeBarbero` decimal(5,2) NOT NULL,
  `PrecioVenta` decimal(10,2) NOT NULL,
  `FechaInicio` date NOT NULL,
  `FechaFin` date DEFAULT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `historialporcentajeproductos`
--

INSERT INTO `historialporcentajeproductos` VALUES(1, 1, 20.00, 10.00, 24.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(2, 2, 20.00, 10.00, 30.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(3, 3, 20.00, 10.00, 42.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(4, 4, 20.00, 10.00, 36.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(5, 5, 20.00, 10.00, 12.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(6, 6, 20.00, 10.00, 96.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(7, 7, 20.00, 10.00, 420.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(8, 8, 20.00, 10.00, 24.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(9, 9, 20.00, 10.00, 18.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `historialporcentajeproductos` VALUES(10, 10, 20.00, 10.00, 30.00, '2026-06-26', NULL, 1, '2026-06-26 10:56:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `horarios`
--

CREATE TABLE `horarios` (
  `IdHorario` int(11) NOT NULL,
  `DiaSemana` varchar(20) NOT NULL,
  `HoraEntrada` time DEFAULT NULL,
  `HoraSalida` time DEFAULT NULL,
  `DiaDescanso` int(11) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `horarios`
--

INSERT INTO `horarios` VALUES(1, 'Lunes', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(2, 'Martes', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(3, 'Miércoles', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(4, 'Jueves', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(5, 'Viernes', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(6, 'Sábado', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(7, 'Domingo', '10:00:00', '22:00:00', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `horarios` VALUES(8, 'Lunes', NULL, NULL, 1, 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horarios` VALUES(9, 'Martes', NULL, NULL, 1, 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horarios` VALUES(10, 'Miércoles', NULL, NULL, 1, 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horarios` VALUES(11, 'Jueves', NULL, NULL, 1, 1, '2026-06-29 23:22:57', 1);

-- --------------------------------------------------------

--
-- Table structure for table `horariosbarberos`
--

CREATE TABLE `horariosbarberos` (
  `IdHorarioBarbero` int(11) NOT NULL,
  `IdBarbero` int(11) NOT NULL,
  `IdHorario` int(11) NOT NULL,
  `FechaInicio` date NOT NULL,
  `FechaFin` date NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `horariosbarberos`
--

INSERT INTO `horariosbarberos` VALUES(1, 1, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(2, 1, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(3, 1, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(4, 1, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(5, 1, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(6, 1, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(7, 1, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(8, 2, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(9, 2, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(10, 2, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(11, 2, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(12, 2, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(13, 2, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(14, 2, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(15, 3, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(16, 3, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(17, 3, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(18, 3, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(19, 3, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(20, 3, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(21, 3, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(22, 4, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(23, 4, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(24, 4, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(25, 4, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(26, 4, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(27, 4, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(28, 4, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(29, 5, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(30, 5, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(31, 5, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(32, 5, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(33, 5, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(34, 5, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(35, 5, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(36, 6, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(37, 6, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(38, 6, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(39, 6, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(40, 6, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(41, 6, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(42, 6, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(43, 7, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(44, 7, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(45, 7, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(46, 7, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(47, 7, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(48, 7, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(49, 7, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(50, 8, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(51, 8, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(52, 8, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(53, 8, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(54, 8, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(55, 8, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(56, 8, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(57, 9, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(58, 9, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(59, 9, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(60, 9, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(61, 9, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(62, 9, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(63, 9, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(64, 10, 7, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(65, 10, 4, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(66, 10, 1, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(67, 10, 2, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(68, 10, 3, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(69, 10, 6, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(70, 10, 5, '2026-06-21', '2026-06-27', 1, '2026-06-27 17:57:53', 1);
INSERT INTO `horariosbarberos` VALUES(128, 1, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(129, 1, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(130, 1, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(131, 1, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(132, 1, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(133, 1, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(134, 1, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(135, 2, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(136, 2, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(137, 2, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(138, 2, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(139, 2, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(140, 2, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(141, 2, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(142, 3, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(143, 3, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(144, 3, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(145, 3, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(146, 3, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(147, 3, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(148, 3, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(149, 4, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(150, 4, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(151, 4, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(152, 4, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(153, 4, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(154, 4, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(155, 4, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(156, 5, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(157, 5, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(158, 5, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(159, 5, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(160, 5, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(161, 5, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(162, 5, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(163, 6, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(164, 6, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(165, 6, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(166, 6, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(167, 6, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(168, 6, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(169, 6, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(170, 7, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(171, 7, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(172, 7, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(173, 7, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(174, 7, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(175, 7, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(176, 7, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(177, 8, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(178, 8, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(179, 8, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(180, 8, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(181, 8, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(182, 8, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(183, 8, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(184, 9, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(185, 9, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(186, 9, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(187, 9, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(188, 9, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(189, 9, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(190, 9, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(191, 10, 7, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(192, 10, 4, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(193, 10, 1, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(194, 10, 2, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(195, 10, 3, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(196, 10, 6, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(197, 10, 5, '2026-06-28', '2026-07-04', 1, '2026-06-27 17:59:02', 1);
INSERT INTO `horariosbarberos` VALUES(255, 1, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(256, 1, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(257, 1, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(258, 1, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(259, 1, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(260, 1, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(261, 1, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(262, 2, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(263, 2, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(264, 2, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(265, 2, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(266, 2, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(267, 2, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(268, 2, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(269, 3, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(270, 3, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(271, 3, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(272, 3, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(273, 3, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(274, 3, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(275, 3, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(276, 4, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(277, 4, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(278, 4, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(279, 4, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(280, 4, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(281, 4, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(282, 4, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(283, 5, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(284, 5, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(285, 5, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(286, 5, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(287, 5, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(288, 5, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(289, 5, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(290, 6, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(291, 6, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(292, 6, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(293, 6, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(294, 6, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(295, 6, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(296, 6, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(297, 7, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(298, 7, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(299, 7, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(300, 7, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(301, 7, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(302, 7, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(303, 7, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(304, 8, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(305, 8, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(306, 8, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(307, 8, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(308, 8, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(309, 8, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(310, 8, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(311, 9, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(312, 9, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(313, 9, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(314, 9, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(315, 9, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(316, 9, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(317, 9, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(318, 10, 7, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(319, 10, 4, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(320, 10, 1, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(321, 10, 2, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(322, 10, 3, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(323, 10, 6, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(324, 10, 5, '2026-07-05', '2026-07-11', 1, '2026-06-27 17:59:18', 1);
INSERT INTO `horariosbarberos` VALUES(325, 1, 8, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(326, 1, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(327, 1, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(328, 1, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(329, 1, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(330, 1, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(331, 1, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(332, 2, 1, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(333, 2, 9, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(334, 2, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(335, 2, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(336, 2, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(337, 2, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(338, 2, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(339, 3, 1, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(340, 3, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(341, 3, 10, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(342, 3, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(343, 3, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(344, 3, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(345, 3, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(346, 4, 1, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(347, 4, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(348, 4, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(349, 4, 11, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(350, 4, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(351, 4, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(352, 4, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(353, 5, 8, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(354, 5, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(355, 5, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(356, 5, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(357, 5, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(358, 5, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(359, 5, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:57', 1);
INSERT INTO `horariosbarberos` VALUES(360, 6, 1, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(361, 6, 9, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(362, 6, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(363, 6, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(364, 6, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(365, 6, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(366, 6, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(367, 7, 1, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(368, 7, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(369, 7, 10, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(370, 7, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(371, 7, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(372, 7, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(373, 7, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(374, 8, 1, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(375, 8, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(376, 8, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(377, 8, 11, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(378, 8, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(379, 8, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(380, 8, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(381, 9, 8, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(382, 9, 2, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(383, 9, 3, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(384, 9, 4, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(385, 9, 5, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(386, 9, 6, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(387, 9, 7, '2026-06-29', '2026-07-05', 1, '2026-06-29 23:22:58', 1);
INSERT INTO `horariosbarberos` VALUES(388, 11, 1, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);
INSERT INTO `horariosbarberos` VALUES(389, 11, 2, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);
INSERT INTO `horariosbarberos` VALUES(390, 11, 3, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);
INSERT INTO `horariosbarberos` VALUES(391, 11, 4, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);
INSERT INTO `horariosbarberos` VALUES(392, 11, 5, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);
INSERT INTO `horariosbarberos` VALUES(393, 11, 6, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);
INSERT INTO `horariosbarberos` VALUES(394, 11, 7, '2026-06-29', '2026-07-05', 1, '2026-06-30 00:33:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` VALUES(1, 'default', '{\"uuid\":\"8b5891e5-30e0-412d-8bfe-124fbb9ad8f2\",\"displayName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\ExpirarReservaPendiente\\\":2:{s:43:\\\"\\u0000App\\\\Jobs\\\\ExpirarReservaPendiente\\u0000idReserva\\\";i:6;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2026-06-28 18:49:36.262315\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1782671679,\"delay\":897}', 0, NULL, 1782672576, 1782671679);
INSERT INTO `jobs` VALUES(2, 'default', '{\"uuid\":\"a79962f4-0bdb-4dcc-be8f-17ae411dbb13\",\"displayName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\ExpirarReservaPendiente\\\":2:{s:43:\\\"\\u0000App\\\\Jobs\\\\ExpirarReservaPendiente\\u0000idReserva\\\";i:7;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2026-06-28 16:25:23.220000\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1782663023,\"delay\":900}', 0, NULL, 1782663923, 1782663023);
INSERT INTO `jobs` VALUES(3, 'default', '{\"uuid\":\"b572800d-36e6-4b5f-8bc9-9c58c3feddae\",\"displayName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\ExpirarReservaPendiente\\\":2:{s:43:\\\"\\u0000App\\\\Jobs\\\\ExpirarReservaPendiente\\u0000idReserva\\\";i:8;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2026-06-28 16:26:11.976286\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1782663072,\"delay\":899}', 0, NULL, 1782663971, 1782663072);
INSERT INTO `jobs` VALUES(4, 'default', '{\"uuid\":\"3d08b6c9-a16c-466a-be10-b082107f17cd\",\"displayName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\ExpirarReservaPendiente\",\"command\":\"O:32:\\\"App\\\\Jobs\\\\ExpirarReservaPendiente\\\":2:{s:43:\\\"\\u0000App\\\\Jobs\\\\ExpirarReservaPendiente\\u0000idReserva\\\";i:9;s:5:\\\"delay\\\";O:25:\\\"Illuminate\\\\Support\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2026-06-28 16:30:59.906214\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"batchId\":null},\"createdAt\":1782663359,\"delay\":900}', 0, NULL, 1782664259, 1782663359);

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lotes`
--

CREATE TABLE `lotes` (
  `IdLote` int(11) NOT NULL,
  `IdProducto` int(11) NOT NULL,
  `CantidadRecibida` int(11) NOT NULL,
  `CostoUnitario` decimal(10,2) NOT NULL,
  `FechaIngreso` datetime NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lotes`
--

INSERT INTO `lotes` VALUES(1, 1, 50, 20.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(2, 2, 40, 25.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(3, 3, 30, 35.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(4, 4, 25, 30.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(5, 5, 100, 10.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(6, 6, 15, 80.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(7, 7, 8, 350.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(8, 8, 40, 20.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(9, 9, 50, 15.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `lotes` VALUES(10, 10, 60, 25.00, '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` VALUES(1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES(2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` VALUES(3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` VALUES(4, '2026_06_18_144455_create_personal_access_tokens_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pagos`
--

CREATE TABLE `pagos` (
  `IdPago` int(11) NOT NULL,
  `IdReserva` int(11) DEFAULT NULL,
  `IdVenta` int(11) DEFAULT NULL,
  `TipoPago` varchar(20) NOT NULL,
  `Monto` decimal(10,2) NOT NULL,
  `FechaPago` datetime DEFAULT NULL,
  `MetodoPago` varchar(50) NOT NULL,
  `EstadoPago` varchar(20) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pagos`
--

INSERT INTO `pagos` VALUES(1, 1, NULL, 'ANT', 15.00, '2026-06-26 10:56:44', 'QR', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(2, 2, NULL, 'ANT', 20.00, '2026-06-26 10:56:44', 'QR', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(3, 3, NULL, 'ANT', 10.00, '2026-06-26 10:56:44', 'QR', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(4, 4, NULL, 'ANT', 15.00, '2026-06-26 10:56:44', 'QR', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(5, 5, NULL, 'ANT', 40.00, '2026-06-26 10:56:44', 'QR', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(6, 1, NULL, 'FIN', 15.00, '2026-06-26 10:56:44', 'Efectivo', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(7, 2, NULL, 'FIN', 20.00, '2026-06-26 10:56:44', 'QR', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(8, NULL, 1, 'VEN', 24.00, '2026-06-26 10:56:44', 'Efectivo', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(9, NULL, 2, 'VEN', 72.00, '2026-06-26 10:56:44', 'Efectivo', 'Pagado', 1, '2026-06-26 10:56:44', 1);
INSERT INTO `pagos` VALUES(10, 6, NULL, 'Total', 130.00, '2026-06-28 18:34:42', 'QR', 'Pagado', 1, '2026-06-28 18:34:42', 2);
INSERT INTO `pagos` VALUES(11, 7, NULL, 'Anticipo', 30.00, '2026-06-28 16:10:26', 'QR', 'Pagado', 1, '2026-06-28 16:10:26', 1);
INSERT INTO `pagos` VALUES(12, 8, NULL, 'Anticipo', 10.00, '2026-06-28 16:11:13', 'QR', 'Pagado', 1, '2026-06-28 16:11:13', 1);
INSERT INTO `pagos` VALUES(13, NULL, 4, 'Total', 36.00, '2026-06-29 21:19:22', 'QR', 'Pagado', 1, '2026-06-29 21:19:22', 2);
INSERT INTO `pagos` VALUES(14, 7, NULL, 'Saldo', 30.00, '2026-06-29 21:21:22', 'QR', 'Pagado', 1, '2026-06-29 21:21:22', 2);
INSERT INTO `pagos` VALUES(15, NULL, 3, 'Total', 72.00, '2026-06-29 21:21:22', 'QR', 'Pagado', 1, '2026-06-29 21:21:22', 2);
INSERT INTO `pagos` VALUES(16, NULL, 5, 'Total', 24.00, '2026-06-29 21:31:12', 'Efectivo', 'Pagado', 1, '2026-06-29 21:31:12', 2);
INSERT INTO `pagos` VALUES(17, 10, NULL, 'Total', 30.00, '2026-06-29 21:32:36', 'Efectivo', 'Pagado', 1, '2026-06-29 21:32:36', 2);
INSERT INTO `pagos` VALUES(18, 10, NULL, 'Saldo', 0.00, '2026-06-29 21:32:56', 'Efectivo', 'Pagado', 1, '2026-06-29 21:32:56', 2);
INSERT INTO `pagos` VALUES(19, NULL, 6, 'Total', 36.00, '2026-06-29 21:32:56', 'Efectivo', 'Pagado', 1, '2026-06-29 21:32:56', 2);

--
-- Triggers `pagos`
--
DELIMITER $$
CREATE TRIGGER `trg_pagos_insert` AFTER INSERT ON `pagos` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'Pagos', NEW.IdPago, 'I',
        'IdPago|IdReserva|IdVenta|TipoPago|Monto|FechaPago|MetodoPago|EstadoPago',
        NULL,
        CONCAT_WS('|', NEW.IdPago, NEW.IdReserva, NEW.IdVenta, NEW.TipoPago, NEW.Monto, NEW.FechaPago, NEW.MetodoPago, NEW.EstadoPago),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pagos_update` AFTER UPDATE ON `pagos` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'Pagos', NEW.IdPago, 'U',
        'TipoPago|Monto|FechaPago|MetodoPago|EstadoPago|EstadoA',
        CONCAT_WS('|', OLD.TipoPago, OLD.Monto, OLD.FechaPago, OLD.MetodoPago, OLD.EstadoPago, OLD.EstadoA),
        CONCAT_WS('|', NEW.TipoPago, NEW.Monto, NEW.FechaPago, NEW.MetodoPago, NEW.EstadoPago, NEW.EstadoA),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` VALUES(9, 'App\\Models\\User', 1, 'auth_token', 'e482a0f759ff74a614c7054e4cf6f836b131ca06a6dabe382033c585176c13b4', '[\"*\"]', '2026-06-30 08:35:46', NULL, '2026-06-30 06:59:34', '2026-06-30 08:35:46');

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `IdProducto` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `CostoCompra` decimal(10,2) NOT NULL,
  `PrecioVenta` decimal(10,2) NOT NULL,
  `PorcentajeVenta` decimal(5,2) NOT NULL,
  `PorcentajeBarbero` decimal(5,2) NOT NULL,
  `StockActual` int(11) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` VALUES(1, 'Gel Fijador', 20.00, 24.00, 20.00, 10.00, 29, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(2, 'Cera para Cabello', 25.00, 30.00, 20.00, 10.00, 25, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(3, 'Shampoo Anticaida', 35.00, 42.00, 20.00, 10.00, 20, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(4, 'Aceite para Barba', 30.00, 36.00, 20.00, 10.00, 15, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(5, 'Peine Profesional', 10.00, 12.00, 20.00, 10.00, 50, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(6, 'Tijera Profesional', 80.00, 96.00, 20.00, 10.00, 10, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(7, 'Máquina de Corte', 350.00, 420.00, 20.00, 10.00, 5, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(8, 'Loción After Shave', 20.00, 24.00, 20.00, 10.00, 20, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(9, 'Cepillo para Barba', 15.00, 18.00, 20.00, 10.00, 23, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `productos` VALUES(10, 'Pomada Capilar', 25.00, 30.00, 20.00, 10.00, 30, 1, '2026-06-26 10:56:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `registros`
--

CREATE TABLE `registros` (
  `IdRegistro` int(11) NOT NULL,
  `IdBarbero` int(11) NOT NULL,
  `Fecha` date NOT NULL,
  `HoraInicio` time DEFAULT NULL,
  `HoraFin` time DEFAULT NULL,
  `Observacion` varchar(255) DEFAULT NULL,
  `Ausencia` tinyint(1) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registros`
--

INSERT INTO `registros` VALUES(1, 1, '2026-06-23', '12:00:00', '13:00:00', NULL, 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `registros` VALUES(2, 2, '2026-06-23', '13:00:00', '14:00:00', 'Turno tardío asignado', 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `registros` VALUES(3, 3, '2026-06-23', '12:00:00', '13:00:00', NULL, 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `registros` VALUES(4, 1, '2026-06-24', '12:00:00', '13:00:00', NULL, 0, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `registros` VALUES(5, 2, '2026-06-24', '12:00:00', '13:00:00', NULL, 0, 1, '2026-06-26 10:56:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reservas`
--

CREATE TABLE `reservas` (
  `IdReserva` int(11) NOT NULL,
  `IdCliente` varchar(20) NOT NULL,
  `IdBarbero` int(11) NOT NULL,
  `FechaCita` date NOT NULL,
  `HoraInicio` time NOT NULL,
  `HoraFin` time NOT NULL,
  `CostoTotal` decimal(10,2) NOT NULL,
  `MontoAnticipo` decimal(10,2) NOT NULL,
  `FechaPagoAnticipo` datetime DEFAULT NULL,
  `MetodoPagoAnticipo` varchar(50) DEFAULT NULL,
  `EstadoReserva` varchar(30) NOT NULL,
  `HoraAusente` datetime DEFAULT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservas`
--

INSERT INTO `reservas` VALUES(1, '1000001', 1, '2026-06-23', '10:00:00', '10:45:00', 30.00, 15.00, '2026-06-26 10:56:43', 'QR', 'Completada', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservas` VALUES(2, '1000002', 2, '2026-06-23', '10:00:00', '10:55:00', 40.00, 20.00, '2026-06-26 10:56:43', 'QR', 'Completada', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservas` VALUES(3, '1000003', 3, '2026-06-23', '14:00:00', '14:45:00', 20.00, 10.00, '2026-06-26 10:56:43', 'QR', 'Confirmada', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservas` VALUES(4, '1000004', 4, '2026-06-24', '10:00:00', '10:45:00', 30.00, 15.00, '2026-06-26 10:56:43', 'QR', 'Confirmada', NULL, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservas` VALUES(5, '1000005', 5, '2026-06-24', '10:00:00', '11:10:00', 80.00, 40.00, '2026-06-26 10:56:43', 'QR', 'Ausente', '2026-06-26 10:56:43', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservas` VALUES(6, '8464349', 1, '2026-06-28', '11:00:00', '15:10:00', 130.00, 130.00, NULL, NULL, 'Confirmada', NULL, 1, '2026-06-28 18:34:35', 2);
INSERT INTO `reservas` VALUES(7, '8464349', 1, '2026-06-29', '10:00:00', '12:15:00', 60.00, 30.00, '2026-06-28 16:10:26', NULL, 'Completada', NULL, 1, '2026-06-28 16:10:23', 1);
INSERT INTO `reservas` VALUES(8, '8464349', 1, '2026-06-29', '14:00:00', '14:40:00', 20.00, 10.00, '2026-06-28 16:11:13', NULL, 'Confirmada', NULL, 1, '2026-06-28 16:11:11', 1);
INSERT INTO `reservas` VALUES(9, '8464349', 1, '2026-06-29', '15:00:00', '16:15:00', 40.00, 20.00, NULL, NULL, 'Expirada', NULL, 1, '2026-06-28 16:15:59', 1);
INSERT INTO `reservas` VALUES(10, '8464349', 1, '2026-06-29', '14:45:00', '15:45:00', 30.00, 30.00, NULL, NULL, 'Completada', NULL, 1, '2026-06-29 21:32:35', 2);

--
-- Triggers `reservas`
--
DELIMITER $$
CREATE TRIGGER `trg_reservas_insert` AFTER INSERT ON `reservas` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'Reservas', NEW.IdReserva, 'I',
        'IdReserva|IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva',
        NULL,
        CONCAT_WS('|', NEW.IdReserva, NEW.IdCliente, NEW.IdBarbero, NEW.FechaCita, NEW.HoraInicio, NEW.HoraFin, NEW.CostoTotal, NEW.MontoAnticipo, NEW.EstadoReserva),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_reservas_update` AFTER UPDATE ON `reservas` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'Reservas', NEW.IdReserva, 'U',
        'IdCliente|IdBarbero|FechaCita|HoraInicio|HoraFin|CostoTotal|MontoAnticipo|EstadoReserva|FechaPagoAnticipo|MetodoPagoAnticipo|EstadoA',
        CONCAT_WS('|', OLD.IdCliente, OLD.IdBarbero, OLD.FechaCita, OLD.HoraInicio, OLD.HoraFin, OLD.CostoTotal, OLD.MontoAnticipo, OLD.EstadoReserva, OLD.FechaPagoAnticipo, OLD.MetodoPagoAnticipo, OLD.EstadoA),
        CONCAT_WS('|', NEW.IdCliente, NEW.IdBarbero, NEW.FechaCita, NEW.HoraInicio, NEW.HoraFin, NEW.CostoTotal, NEW.MontoAnticipo, NEW.EstadoReserva, NEW.FechaPagoAnticipo, NEW.MetodoPagoAnticipo, NEW.EstadoA),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reservaservicios`
--

CREATE TABLE `reservaservicios` (
  `IdReservaServicio` int(11) NOT NULL,
  `IdReserva` int(11) NOT NULL,
  `IdServicio` int(11) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservaservicios`
--

INSERT INTO `reservaservicios` VALUES(1, 1, 1, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservaservicios` VALUES(2, 2, 2, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservaservicios` VALUES(3, 3, 3, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservaservicios` VALUES(4, 4, 4, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservaservicios` VALUES(5, 5, 5, 1, '2026-06-26 10:56:43', 1);
INSERT INTO `reservaservicios` VALUES(6, 6, 5, 1, '2026-06-28 18:34:35', 2);
INSERT INTO `reservaservicios` VALUES(7, 6, 6, 1, '2026-06-28 18:34:35', 2);
INSERT INTO `reservaservicios` VALUES(8, 6, 7, 1, '2026-06-28 18:34:35', 2);
INSERT INTO `reservaservicios` VALUES(9, 7, 1, 1, '2026-06-28 16:10:23', 1);
INSERT INTO `reservaservicios` VALUES(10, 8, 10, 1, '2026-06-28 16:11:11', 1);
INSERT INTO `reservaservicios` VALUES(11, 9, 8, 1, '2026-06-28 16:15:59', 1);
INSERT INTO `reservaservicios` VALUES(12, 9, 10, 1, '2026-06-28 16:15:59', 1);
INSERT INTO `reservaservicios` VALUES(13, 10, 3, 1, '2026-06-29 21:32:36', 2);

--
-- Triggers `reservaservicios`
--
DELIMITER $$
CREATE TRIGGER `trg_reservaservicios_insert` AFTER INSERT ON `reservaservicios` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'ReservaServicios', NEW.IdReservaServicio, 'I',
        'IdReservaServicio|IdServicio|IdReserva|EstadoA',
        NULL,
        CONCAT_WS('|', NEW.IdReservaServicio, NEW.IdServicio, NEW.IdReserva, NEW.EstadoA),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_reservaservicios_update` AFTER UPDATE ON `reservaservicios` FOR EACH ROW BEGIN
    CALL sp_RegistrarAuditoria(
        'ReservaServicios', NEW.IdReservaServicio, 'U',
        'IdServicio|IdReserva|EstadoA',
        CONCAT_WS('|', OLD.IdServicio, OLD.IdReserva, OLD.EstadoA),
        CONCAT_WS('|', NEW.IdServicio, NEW.IdReserva, NEW.EstadoA),
        NEW.UsuarioA, @v_auditoria_ip, NULL
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `IdRol` int(11) NOT NULL,
  `Nombre` varchar(50) NOT NULL,
  `Descripcion` varchar(255) DEFAULT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` VALUES(1, 'Administrador', 'Control total del sistema', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `roles` VALUES(2, 'Barbero', 'Gestiona reservas, horarios y servicios', 1, '2026-06-26 10:56:43', 1);

-- --------------------------------------------------------

--
-- Table structure for table `servicios`
--

CREATE TABLE `servicios` (
  `IdServicio` int(11) NOT NULL,
  `IdCategoria` int(11) NOT NULL,
  `Nombre` varchar(100) NOT NULL,
  `FotoURL` varchar(255) DEFAULT NULL,
  `Precio` decimal(10,2) NOT NULL,
  `DuracionMinutos` int(11) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `servicios`
--

INSERT INTO `servicios` VALUES(1, 3, 'Taper Fade Ondulado', 'https://haircutday.com/b/wp-content/uploads/2024/01/Diseno-sin-titulo31-300x300.png', 60.00, 120, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(2, 1, 'Degradado Bajo', 'https://haircutday.com/b/wp-content/uploads/2024/01/Diseno-sin-titulo30-300x300.png', 20.00, 35, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(3, 2, 'Degradado Alto', 'https://haircutday.com/b/wp-content/uploads/2024/01/HCD-BEAUTY18-300x300.png', 30.00, 45, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(4, 3, 'French Crop', 'https://haircutday.com/b/wp-content/uploads/2024/01/HCD-BEAUTY16-300x300.png', 80.00, 140, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(5, 1, 'Corte Buzz', 'https://agendapro.com/blog/wp-content/uploads/sites/2/2024/07/guia_corte_low_fade_perfecto-1024x1024.jpg', 20.00, 25, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(6, 1, 'Flequillo', 'https://agendapro.com/blog/wp-content/uploads/sites/2/2024/07/pexels-tma-management-2975165-20487214-edited-4.webp', 20.00, 30, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(7, 3, 'Wolf Cut', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSmYn-xT5MZ_Fu7LIMCTpNU1iIhMWbw1gc1fluCqRgyHQ&s=10', 90.00, 180, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(8, 1, 'Mullet', 'https://haircutday.com/b/wp-content/uploads/2024/01/Diseno-sin-titulo39-300x300.png', 20.00, 35, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(9, 2, 'Burst Fade', 'https://haircutday.com/b/wp-content/uploads/2024/01/Diseno-sin-titulo32-300x300.png', 30.00, 60, 1, '2026-06-18 11:51:58', 1);
INSERT INTO `servicios` VALUES(10, 1, 'Corte Clasico', 'https://i.pinimg.com/236x/7d/f6/57/7df6573a44f3eed7dd5cb00af38a2db5.jpg', 20.00, 25, 1, '2026-06-18 11:51:58', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `IdUsuario` int(11) NOT NULL,
  `IdRol` int(11) NOT NULL,
  `Nombre1` varchar(50) NOT NULL,
  `Nombre2` varchar(50) DEFAULT NULL,
  `Apellido1` varchar(50) NOT NULL,
  `Apellido2` varchar(50) DEFAULT NULL,
  `Correo` varchar(100) NOT NULL,
  `Contraseña` varchar(255) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` VALUES(1, 1, 'Ricardo', 'Alejandro', 'Limachi', 'Flores', 'admin@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(2, 2, 'Carlos', 'Andres', 'Mamani', 'Juanito', 'barbero1@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(3, 2, 'Luis', 'Fernando', 'Condori', 'Flores', 'barbero2@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(4, 2, 'Jorge', 'Alberto', 'Choque', 'Rojas', 'barbero3@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(5, 2, 'Miguel', 'Angel', 'Perez', 'Mendoza', 'barbero4@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(6, 2, 'Juan', 'Carlos', 'Vargas', 'Lopez', 'barbero5@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(7, 2, 'Diego', 'Alejandro', 'Arias', 'Gomez', 'barbero6@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(8, 2, 'Kevin', 'Eduardo', 'Flores', 'Mamani', 'barbero7@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(9, 2, 'Pedro', 'Jose', 'Quispe', 'Torrez', 'barbero8@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(10, 2, 'Mario', 'Fernando', 'Gutierrez', 'Rojas', 'barbero9@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 1, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(11, 2, 'Jose', 'Miguel', 'Choque', 'Perez', 'barbero10@barberia.com', '$2y$10$on.nBJXMxNVW7qxB1o0uIeEP2xpH4BrW/BofjNOd3t5kkU0uu7vG2', 0, '2026-06-26 10:56:43', 1);
INSERT INTO `usuarios` VALUES(12, 2, 'Miguel', NULL, 'Rulio', NULL, 'rulio@gmail.com', '12345678', 1, '2026-06-29 16:33:16', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ventas`
--

CREATE TABLE `ventas` (
  `IdVenta` int(11) NOT NULL,
  `IdBarbero` int(11) NOT NULL,
  `IdCliente` varchar(20) DEFAULT NULL,
  `IdReserva` int(11) DEFAULT NULL,
  `Fecha` datetime NOT NULL,
  `MontoTotal` decimal(10,2) NOT NULL,
  `EstadoA` int(11) DEFAULT NULL,
  `FechaA` datetime DEFAULT NULL,
  `UsuarioA` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ventas`
--

INSERT INTO `ventas` VALUES(1, 1, '1000001', 1, '2026-06-26 10:56:44', 24.00, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `ventas` VALUES(2, 2, '1000003', NULL, '2026-06-26 10:56:44', 72.00, 1, '2026-06-26 10:56:44', 1);
INSERT INTO `ventas` VALUES(3, 1, '8464349', 7, '2026-06-29 21:03:33', 72.00, 1, '2026-06-29 21:03:33', 2);
INSERT INTO `ventas` VALUES(4, 1, '8464349', NULL, '2026-06-29 21:19:22', 36.00, 1, '2026-06-29 21:19:22', 2);
INSERT INTO `ventas` VALUES(5, 1, '8464349', NULL, '2026-06-29 21:31:12', 24.00, 1, '2026-06-29 21:31:12', 2);
INSERT INTO `ventas` VALUES(6, 1, '8464349', 10, '2026-06-29 21:32:51', 36.00, 1, '2026-06-29 21:32:51', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditoriageneral`
--
ALTER TABLE `auditoriageneral`
  ADD PRIMARY KEY (`IdAuditoria`),
  ADD KEY `UsuarioA` (`UsuarioA`);

--
-- Indexes for table `barberos`
--
ALTER TABLE `barberos`
  ADD PRIMARY KEY (`IdBarbero`),
  ADD UNIQUE KEY `uq_barberos_idusuario` (`IdUsuario`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`IdCategoria`),
  ADD UNIQUE KEY `uq_categorias_nombre` (`Nombre`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`CI`),
  ADD UNIQUE KEY `uq_clientes_correo` (`Correo`);

--
-- Indexes for table `comisiones`
--
ALTER TABLE `comisiones`
  ADD PRIMARY KEY (`IdComision`),
  ADD UNIQUE KEY `uq_comisiones_reserva_tipo` (`IdReserva`,`TipoComision`),
  ADD UNIQUE KEY `uq_comisiones_venta_tipo` (`IdVenta`,`TipoComision`),
  ADD KEY `IdBarbero` (`IdBarbero`);

--
-- Indexes for table `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD PRIMARY KEY (`IdDetalleVenta`),
  ADD UNIQUE KEY `uq_detalleventa_venta_producto` (`IdVenta`,`IdProducto`),
  ADD KEY `IdProducto` (`IdProducto`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `historialporcentajeproductos`
--
ALTER TABLE `historialporcentajeproductos`
  ADD PRIMARY KEY (`IdHistorial`),
  ADD KEY `IdProducto` (`IdProducto`);

--
-- Indexes for table `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`IdHorario`),
  ADD UNIQUE KEY `uq_horarios_dia_entrada_salida` (`DiaSemana`,`HoraEntrada`,`HoraSalida`);

--
-- Indexes for table `horariosbarberos`
--
ALTER TABLE `horariosbarberos`
  ADD PRIMARY KEY (`IdHorarioBarbero`),
  ADD UNIQUE KEY `uq_horariosbarberos_barbero_horario_fechas` (`IdBarbero`,`IdHorario`,`FechaInicio`,`FechaFin`),
  ADD KEY `IdHorario` (`IdHorario`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lotes`
--
ALTER TABLE `lotes`
  ADD PRIMARY KEY (`IdLote`),
  ADD KEY `IdProducto` (`IdProducto`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`IdPago`),
  ADD UNIQUE KEY `uq_pagos_reserva_tipo` (`IdReserva`,`TipoPago`),
  ADD UNIQUE KEY `uq_pagos_venta_tipo` (`IdVenta`,`TipoPago`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`IdProducto`),
  ADD UNIQUE KEY `uq_productos_nombre` (`Nombre`);

--
-- Indexes for table `registros`
--
ALTER TABLE `registros`
  ADD PRIMARY KEY (`IdRegistro`),
  ADD KEY `IdBarbero` (`IdBarbero`);

--
-- Indexes for table `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`IdReserva`),
  ADD UNIQUE KEY `uq_reservas_barbero_fecha_hora` (`IdBarbero`,`FechaCita`,`HoraInicio`),
  ADD KEY `IdCliente` (`IdCliente`);

--
-- Indexes for table `reservaservicios`
--
ALTER TABLE `reservaservicios`
  ADD PRIMARY KEY (`IdReservaServicio`),
  ADD UNIQUE KEY `uq_reservaservicios_reserva_servicio` (`IdReserva`,`IdServicio`),
  ADD KEY `IdServicio` (`IdServicio`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`IdRol`),
  ADD UNIQUE KEY `uq_roles_nombre` (`Nombre`);

--
-- Indexes for table `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`IdServicio`),
  ADD UNIQUE KEY `uq_servicios_nombre` (`Nombre`),
  ADD KEY `IdCategoria` (`IdCategoria`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`IdUsuario`),
  ADD UNIQUE KEY `uq_usuarios_correo` (`Correo`),
  ADD KEY `IdRol` (`IdRol`);

--
-- Indexes for table `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`IdVenta`),
  ADD KEY `IdBarbero` (`IdBarbero`),
  ADD KEY `IdCliente` (`IdCliente`),
  ADD KEY `IdReserva` (`IdReserva`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auditoriageneral`
--
ALTER TABLE `auditoriageneral`
  MODIFY `IdAuditoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `barberos`
--
ALTER TABLE `barberos`
  MODIFY `IdBarbero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `categorias`
--
ALTER TABLE `categorias`
  MODIFY `IdCategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `comisiones`
--
ALTER TABLE `comisiones`
  MODIFY `IdComision` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `detalleventa`
--
ALTER TABLE `detalleventa`
  MODIFY `IdDetalleVenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `historialporcentajeproductos`
--
ALTER TABLE `historialporcentajeproductos`
  MODIFY `IdHistorial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `horarios`
--
ALTER TABLE `horarios`
  MODIFY `IdHorario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `horariosbarberos`
--
ALTER TABLE `horariosbarberos`
  MODIFY `IdHorarioBarbero` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=395;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lotes`
--
ALTER TABLE `lotes`
  MODIFY `IdLote` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pagos`
--
ALTER TABLE `pagos`
  MODIFY `IdPago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `IdProducto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `registros`
--
ALTER TABLE `registros`
  MODIFY `IdRegistro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservas`
--
ALTER TABLE `reservas`
  MODIFY `IdReserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reservaservicios`
--
ALTER TABLE `reservaservicios`
  MODIFY `IdReservaServicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `IdRol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `servicios`
--
ALTER TABLE `servicios`
  MODIFY `IdServicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `IdUsuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ventas`
--
ALTER TABLE `ventas`
  MODIFY `IdVenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auditoriageneral`
--
ALTER TABLE `auditoriageneral`
  ADD CONSTRAINT `auditoriageneral_ibfk_1` FOREIGN KEY (`UsuarioA`) REFERENCES `usuarios` (`IdUsuario`);

--
-- Constraints for table `barberos`
--
ALTER TABLE `barberos`
  ADD CONSTRAINT `barberos_ibfk_1` FOREIGN KEY (`IdUsuario`) REFERENCES `usuarios` (`IdUsuario`);

--
-- Constraints for table `comisiones`
--
ALTER TABLE `comisiones`
  ADD CONSTRAINT `comisiones_ibfk_1` FOREIGN KEY (`IdBarbero`) REFERENCES `barberos` (`IdBarbero`),
  ADD CONSTRAINT `comisiones_ibfk_2` FOREIGN KEY (`IdReserva`) REFERENCES `reservas` (`IdReserva`),
  ADD CONSTRAINT `comisiones_ibfk_3` FOREIGN KEY (`IdVenta`) REFERENCES `ventas` (`IdVenta`);

--
-- Constraints for table `detalleventa`
--
ALTER TABLE `detalleventa`
  ADD CONSTRAINT `detalleventa_ibfk_1` FOREIGN KEY (`IdVenta`) REFERENCES `ventas` (`IdVenta`),
  ADD CONSTRAINT `detalleventa_ibfk_2` FOREIGN KEY (`IdProducto`) REFERENCES `productos` (`IdProducto`);

--
-- Constraints for table `historialporcentajeproductos`
--
ALTER TABLE `historialporcentajeproductos`
  ADD CONSTRAINT `historialporcentajeproductos_ibfk_1` FOREIGN KEY (`IdProducto`) REFERENCES `productos` (`IdProducto`);

--
-- Constraints for table `horariosbarberos`
--
ALTER TABLE `horariosbarberos`
  ADD CONSTRAINT `horariosbarberos_ibfk_1` FOREIGN KEY (`IdBarbero`) REFERENCES `barberos` (`IdBarbero`),
  ADD CONSTRAINT `horariosbarberos_ibfk_2` FOREIGN KEY (`IdHorario`) REFERENCES `horarios` (`IdHorario`);

--
-- Constraints for table `lotes`
--
ALTER TABLE `lotes`
  ADD CONSTRAINT `lotes_ibfk_1` FOREIGN KEY (`IdProducto`) REFERENCES `productos` (`IdProducto`);

--
-- Constraints for table `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`IdReserva`) REFERENCES `reservas` (`IdReserva`),
  ADD CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`IdVenta`) REFERENCES `ventas` (`IdVenta`);

--
-- Constraints for table `registros`
--
ALTER TABLE `registros`
  ADD CONSTRAINT `registros_ibfk_1` FOREIGN KEY (`IdBarbero`) REFERENCES `barberos` (`IdBarbero`);

--
-- Constraints for table `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`IdCliente`) REFERENCES `clientes` (`CI`),
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`IdBarbero`) REFERENCES `barberos` (`IdBarbero`);

--
-- Constraints for table `reservaservicios`
--
ALTER TABLE `reservaservicios`
  ADD CONSTRAINT `reservaservicios_ibfk_1` FOREIGN KEY (`IdReserva`) REFERENCES `reservas` (`IdReserva`),
  ADD CONSTRAINT `reservaservicios_ibfk_2` FOREIGN KEY (`IdServicio`) REFERENCES `servicios` (`IdServicio`);

--
-- Constraints for table `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `servicios_ibfk_1` FOREIGN KEY (`IdCategoria`) REFERENCES `categorias` (`IdCategoria`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`IdRol`) REFERENCES `roles` (`IdRol`);

--
-- Constraints for table `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`IdBarbero`) REFERENCES `barberos` (`IdBarbero`),
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`IdCliente`) REFERENCES `clientes` (`CI`),
  ADD CONSTRAINT `ventas_ibfk_3` FOREIGN KEY (`IdReserva`) REFERENCES `reservas` (`IdReserva`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
