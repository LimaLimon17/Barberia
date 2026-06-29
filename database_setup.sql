CREATE DATABASE IF NOT EXISTS `Barberia_bd`;
USE `Barberia_bd`;

CREATE TABLE `Roles` (
    `IdRol` INT NOT NULL AUTO_INCREMENT,
    `Nombre` VARCHAR(50) NOT NULL,
    `Descripcion` VARCHAR(255) NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdRol`),
    UNIQUE KEY `uq_roles_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Clientes` (
    `CI` VARCHAR(20) NOT NULL,
    `Nombre1` VARCHAR(50) NOT NULL,
    `Apellido1` VARCHAR(50) NOT NULL,
    `Telefono` VARCHAR(20) NULL,
    `Correo` VARCHAR(100) NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`CI`),
    UNIQUE KEY `uq_clientes_correo` (`Correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Categorias` (
    `IdCategoria` INT NOT NULL AUTO_INCREMENT,
    `Nombre` VARCHAR(100) NOT NULL,
    `DuracionMinimaMinutos` INT NOT NULL,
    `DuracionMaximaMinutos` INT NOT NULL,
    `PrecioMin` DECIMAL(10,2) NOT NULL,
    `PrecioMax` DECIMAL(10,2) NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdCategoria`),
    UNIQUE KEY `uq_categorias_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Productos` (
    `IdProducto` INT NOT NULL AUTO_INCREMENT,
    `Nombre` VARCHAR(100) NOT NULL,
    `CostoCompra` DECIMAL(10,2) NOT NULL,
    `PrecioVenta` DECIMAL(10,2) NOT NULL,
    `PorcentajeVenta` DECIMAL(5,2) NOT NULL,
    `PorcentajeBarbero` DECIMAL(5,2) NOT NULL,
    `StockActual` INT NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdProducto`),
    UNIQUE KEY `uq_productos_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `HistorialPorcentajeProductos` (
    `IdHistorial` INT NOT NULL AUTO_INCREMENT,
    `IdProducto` INT NOT NULL,
    `PorcentajeVenta` DECIMAL(5,2) NOT NULL,
    `PorcentajeBarbero` DECIMAL(5,2) NOT NULL,
    `PrecioVenta` DECIMAL(10,2) NOT NULL,
    `FechaInicio` DATE NOT NULL,
    `FechaFin` DATE NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdHistorial`),
    FOREIGN KEY (`IdProducto`) REFERENCES `Productos` (`IdProducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Usuarios` (
    `IdUsuario` INT NOT NULL AUTO_INCREMENT,
    `IdRol` INT NOT NULL,
    `Nombre1` VARCHAR(50) NOT NULL,
    `Nombre2` VARCHAR(50) NULL,
    `Apellido1` VARCHAR(50) NOT NULL,
    `Apellido2` VARCHAR(50) NULL,
    `Correo` VARCHAR(100) NOT NULL,
    `Contraseña` VARCHAR(255) NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdUsuario`),
    UNIQUE KEY `uq_usuarios_correo` (`Correo`),
    FOREIGN KEY (`IdRol`) REFERENCES `Roles` (`IdRol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Servicios` (
    `IdServicio` INT NOT NULL AUTO_INCREMENT,
    `IdCategoria` INT NOT NULL,
    `Nombre` VARCHAR(100) NOT NULL,
    `FotoURL` VARCHAR(255) NULL,
    `Precio` DECIMAL(10,2) NOT NULL,
    `DuracionMinutos` INT NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdServicio`),
    UNIQUE KEY `uq_servicios_nombre` (`Nombre`),
    FOREIGN KEY (`IdCategoria`) REFERENCES `Categorias` (`IdCategoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Lotes` (
    `IdLote` INT NOT NULL AUTO_INCREMENT,
    `IdProducto` INT NOT NULL,
    `CantidadRecibida` INT NOT NULL,
    `CostoUnitario` DECIMAL(10,2) NOT NULL,
    `FechaIngreso` DATETIME NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdLote`),
    FOREIGN KEY (`IdProducto`) REFERENCES `Productos` (`IdProducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Barberos` (
    `IdBarbero` INT NOT NULL AUTO_INCREMENT,
    `IdUsuario` INT NOT NULL,
    `FechaIngreso` DATE NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdBarbero`),
    UNIQUE KEY `uq_barberos_idusuario` (`IdUsuario`),
    FOREIGN KEY (`IdUsuario`) REFERENCES `Usuarios` (`IdUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Horarios` (
    `IdHorario` INT NOT NULL AUTO_INCREMENT,
    `DiaSemana` VARCHAR(20) NOT NULL,
    `HoraEntrada` TIME NULL,
    `HoraSalida` TIME NULL,
    `DiaDescanso` INT NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdHorario`),
    UNIQUE KEY `uq_horarios_dia_entrada_salida` (`DiaSemana`, `HoraEntrada`, `HoraSalida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `HorariosBarberos` (
    `IdHorarioBarbero` INT NOT NULL AUTO_INCREMENT,
    `IdBarbero` INT NOT NULL,
    `IdHorario` INT NOT NULL,
    `FechaInicio` DATE NOT NULL,
    `FechaFin` DATE NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdHorarioBarbero`),
    UNIQUE KEY `uq_horariosbarberos_barbero_horario_fechas` (`IdBarbero`, `IdHorario`, `FechaInicio`, `FechaFin`),
    FOREIGN KEY (`IdBarbero`) REFERENCES `Barberos` (`IdBarbero`),
    FOREIGN KEY (`IdHorario`) REFERENCES `Horarios` (`IdHorario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Registros` (
    `IdRegistro` INT NOT NULL AUTO_INCREMENT,
    `IdBarbero` INT NOT NULL,
    `Fecha` DATE NOT NULL,
    `HoraInicio` TIME NULL,
    `HoraFin` TIME NULL,
    `Observacion` VARCHAR(255) NULL,
    `Ausencia` BOOLEAN NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdRegistro`),
    FOREIGN KEY (`IdBarbero`) REFERENCES `Barberos` (`IdBarbero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Reservas` (
    `IdReserva` INT NOT NULL AUTO_INCREMENT,
    `IdCliente` VARCHAR(20) NOT NULL,
    `IdBarbero` INT NOT NULL,
    `FechaCita` DATE NOT NULL,
    `HoraInicio` TIME NOT NULL,
    `HoraFin` TIME NOT NULL,
    `CostoTotal` DECIMAL(10,2) NOT NULL,
    `MontoAnticipo` DECIMAL(10,2) NOT NULL,
    `FechaPagoAnticipo` DATETIME NULL,
    `MetodoPagoAnticipo` VARCHAR(50) NULL,
    `EstadoReserva` VARCHAR(30) NOT NULL,
    `HoraAusente` DATETIME NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdReserva`),
    UNIQUE KEY `uq_reservas_barbero_fecha_hora` (`IdBarbero`, `FechaCita`, `HoraInicio`),
    FOREIGN KEY (`IdCliente`) REFERENCES `Clientes` (`CI`),
    FOREIGN KEY (`IdBarbero`) REFERENCES `Barberos` (`IdBarbero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ReservaServicios` (
    `IdReservaServicio` INT NOT NULL AUTO_INCREMENT,
    `IdReserva` INT NOT NULL,
    `IdServicio` INT NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdReservaServicio`),
    UNIQUE KEY `uq_reservaservicios_reserva_servicio` (`IdReserva`, `IdServicio`),
    FOREIGN KEY (`IdReserva`) REFERENCES `Reservas` (`IdReserva`),
    FOREIGN KEY (`IdServicio`) REFERENCES `Servicios` (`IdServicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Ventas` (
    `IdVenta` INT NOT NULL AUTO_INCREMENT,
    `IdBarbero` INT NOT NULL,
    `IdCliente` VARCHAR(20) NULL,
    `IdReserva` INT NULL,
    `Fecha` DATETIME NOT NULL,
    `MontoTotal` DECIMAL(10,2) NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdVenta`),
    FOREIGN KEY (`IdBarbero`) REFERENCES `Barberos` (`IdBarbero`),
    FOREIGN KEY (`IdCliente`) REFERENCES `Clientes` (`CI`),
    FOREIGN KEY (`IdReserva`) REFERENCES `Reservas` (`IdReserva`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `DetalleVenta` (
    `IdDetalleVenta` INT NOT NULL AUTO_INCREMENT,
    `IdVenta` INT NOT NULL,
    `IdProducto` INT NOT NULL,
    `Cantidad` INT NOT NULL,
    `PrecioUnitario` DECIMAL(10,2) NOT NULL,
    `ComisionBarbero` DECIMAL(10,2) NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdDetalleVenta`),
    UNIQUE KEY `uq_detalleventa_venta_producto` (`IdVenta`, `IdProducto`),
    FOREIGN KEY (`IdVenta`) REFERENCES `Ventas` (`IdVenta`),
    FOREIGN KEY (`IdProducto`) REFERENCES `Productos` (`IdProducto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Pagos` (
    `IdPago` INT NOT NULL AUTO_INCREMENT,
    `IdReserva` INT NULL,
    `IdVenta` INT NULL,
    `TipoPago` VARCHAR(20) NOT NULL,
    `Monto` DECIMAL(10,2) NOT NULL,
    `FechaPago` DATETIME NULL,
    `MetodoPago` VARCHAR(50) NOT NULL,
    `EstadoPago` VARCHAR(20) NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdPago`),
    UNIQUE KEY `uq_pagos_reserva_tipo` (`IdReserva`, `TipoPago`),
    UNIQUE KEY `uq_pagos_venta_tipo` (`IdVenta`, `TipoPago`),
    FOREIGN KEY (`IdReserva`) REFERENCES `Reservas` (`IdReserva`),
    FOREIGN KEY (`IdVenta`) REFERENCES `Ventas` (`IdVenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `Comisiones` (
    `IdComision` INT NOT NULL AUTO_INCREMENT,
    `IdBarbero` INT NOT NULL,
    `IdReserva` INT NULL,
    `IdVenta` INT NULL,
    `TipoComision` CHAR(3) NOT NULL,
    `Fecha` DATETIME NOT NULL,
    `MontoBase` DECIMAL(10,2) NOT NULL,
    `Porcentaje` DECIMAL(5,2) NULL,
    `MontoComision` DECIMAL(10,2) NOT NULL,
    `EstadoA` INT NULL,
    `FechaA` DATETIME NULL,
    `UsuarioA` INT NULL,
    PRIMARY KEY (`IdComision`),
    UNIQUE KEY `uq_comisiones_reserva_tipo` (`IdReserva`, `TipoComision`),
    UNIQUE KEY `uq_comisiones_venta_tipo` (`IdVenta`, `TipoComision`),
    FOREIGN KEY (`IdBarbero`) REFERENCES `Barberos` (`IdBarbero`),
    FOREIGN KEY (`IdReserva`) REFERENCES `Reservas` (`IdReserva`),
    FOREIGN KEY (`IdVenta`) REFERENCES `Ventas` (`IdVenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `AuditoriaGeneral` (
    `IdAuditoria` INT NOT NULL AUTO_INCREMENT,
    `TablaNombre` VARCHAR(50) NULL,
    `RegistroId` VARCHAR(50) NULL,
    `Accion` VARCHAR(50) NULL,
    `Campo` VARCHAR(100) NULL,
    `ValorAnterior` LONGTEXT NULL,
    `ValorNuevo` LONGTEXT NULL,
    `UsuarioA` INT NULL,
    `FechaA` DATETIME NULL,
    `DireccionIP` VARCHAR(50) NULL,
    `Detalles` VARCHAR(500) NULL,
    PRIMARY KEY (`IdAuditoria`),
    FOREIGN KEY (`UsuarioA`) REFERENCES `Usuarios` (`IdUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
USE `Barberia_bd`;

START TRANSACTION;

INSERT INTO `Roles` (`Nombre`, `Descripcion`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
('Administrador', 'Control total del sistema', 1, NOW(), 1),
('Barbero', 'Gestiona reservas, horarios y servicios', 1, NOW(), 1);

INSERT INTO `Clientes` (`CI`, `Nombre1`, `Apellido1`, `Telefono`, `Correo`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
('1000001', 'Juan', 'Mamani', '71234561', 'juan@gmail.com', 1, NOW(), 1),
('1000002', 'Pedro', 'Quispe', '71234562', 'pedro@gmail.com', 1, NOW(), 1),
('1000003', 'Carlos', 'Condori', '71234563', 'carlos@gmail.com', 1, NOW(), 1),
('1000004', 'Mario', 'Flores', '71234564', 'mario@gmail.com', 1, NOW(), 1),
('1000005', 'Miguel', 'Choque', '71234565', 'miguel@gmail.com', 1, NOW(), 1),
('1000006', 'Jose', 'Rojas', '71234566', 'jose@gmail.com', 1, NOW(), 1),
('1000007', 'Kevin', 'Perez', '71234567', 'kevin@gmail.com', 1, NOW(), 1),
('1000008', 'Luis', 'Arias', '71234568', 'luis@gmail.com', 1, NOW(), 1),
('1000009', 'Jorge', 'Vargas', '71234569', 'jorge@gmail.com', 1, NOW(), 1),
('1000010', 'Diego', 'Lopez', '71234570', 'diego@gmail.com', 1, NOW(), 1);

INSERT INTO `Categorias` (`Nombre`, `DuracionMinimaMinutos`, `DuracionMaximaMinutos`, `PrecioMin`, `PrecioMax`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
('Cortes', 20, 60, 20.00, 80.00, 1, NOW(), 1),
('Barbas', 15, 40, 15.00, 50.00, 1, NOW(), 1),
('Tintes', 30, 120, 50.00, 250.00, 1, NOW(), 1),
('Faciales', 20, 90, 30.00, 150.00, 1, NOW(), 1),
('Peinados', 20, 60, 20.00, 100.00, 1, NOW(), 1),
('Masajes', 30, 90, 50.00, 200.00, 1, NOW(), 1),
('Tratamientos Capilares', 30, 120, 40.00, 300.00, 1, NOW(), 1),
('Cortes Infantiles', 20, 40, 20.00, 50.00, 1, NOW(), 1),
('Premium', 60, 180, 100.00, 500.00, 1, NOW(), 1),
('Paquetes Especiales', 60, 240, 80.00, 600.00, 1, NOW(), 1);

INSERT INTO `Productos` (`Nombre`, `CostoCompra`, `PrecioVenta`, `PorcentajeVenta`, `PorcentajeBarbero`, `StockActual`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
('Gel Fijador', 20.00, 24.00, 20.00, 10.00, 30, 1, NOW(), 1),
('Cera para Cabello', 25.00, 30.00, 20.00, 10.00, 25, 1, NOW(), 1),
('Shampoo Anticaida', 35.00, 42.00, 20.00, 10.00, 20, 1, NOW(), 1),
('Aceite para Barba', 30.00, 36.00, 20.00, 10.00, 18, 1, NOW(), 1),
('Peine Profesional', 10.00, 12.00, 20.00, 10.00, 50, 1, NOW(), 1),
('Tijera Profesional', 80.00, 96.00, 20.00, 10.00, 10, 1, NOW(), 1),
('Máquina de Corte', 350.00, 420.00, 20.00, 10.00, 5, 1, NOW(), 1),
('Loción After Shave', 20.00, 24.00, 20.00, 10.00, 20, 1, NOW(), 1),
('Cepillo para Barba', 15.00, 18.00, 20.00, 10.00, 25, 1, NOW(), 1),
('Pomada Capilar', 25.00, 30.00, 20.00, 10.00, 30, 1, NOW(), 1);

INSERT INTO `HistorialPorcentajeProductos` (`IdProducto`, `PorcentajeVenta`, `PorcentajeBarbero`, `PrecioVenta`, `FechaInicio`, `FechaFin`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 20.00, 10.00, 24.00, CURDATE(), NULL, 1, NOW(), 1),
(2, 20.00, 10.00, 30.00, CURDATE(), NULL, 1, NOW(), 1),
(3, 20.00, 10.00, 42.00, CURDATE(), NULL, 1, NOW(), 1),
(4, 20.00, 10.00, 36.00, CURDATE(), NULL, 1, NOW(), 1),
(5, 20.00, 10.00, 12.00, CURDATE(), NULL, 1, NOW(), 1),
(6, 20.00, 10.00, 96.00, CURDATE(), NULL, 1, NOW(), 1),
(7, 20.00, 10.00, 420.00, CURDATE(), NULL, 1, NOW(), 1),
(8, 20.00, 10.00, 24.00, CURDATE(), NULL, 1, NOW(), 1),
(9, 20.00, 10.00, 18.00, CURDATE(), NULL, 1, NOW(), 1),
(10, 20.00, 10.00, 30.00, CURDATE(), NULL, 1, NOW(), 1);

INSERT INTO `Usuarios` (`IdRol`, `Nombre1`, `Nombre2`, `Apellido1`, `Apellido2`, `Correo`, `Contraseña`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 'Ricardo', 'Alejandro', 'Limachi', 'Flores', 'admin@barberia.com', '123456', 1, NOW(), 1),
(2, 'Carlos', 'Andres', 'Mamani', 'Quispe', 'barbero1@barberia.com', '123456', 1, NOW(), 1),
(2, 'Luis', 'Fernando', 'Condori', 'Flores', 'barbero2@barberia.com', '123456', 1, NOW(), 1),
(2, 'Jorge', 'Alberto', 'Choque', 'Rojas', 'barbero3@barberia.com', '123456', 1, NOW(), 1),
(2, 'Miguel', 'Angel', 'Perez', 'Mendoza', 'barbero4@barberia.com', '123456', 1, NOW(), 1),
(2, 'Juan', 'Carlos', 'Vargas', 'Lopez', 'barbero5@barberia.com', '123456', 1, NOW(), 1),
(2, 'Diego', 'Alejandro', 'Arias', 'Gomez', 'barbero6@barberia.com', '123456', 1, NOW(), 1),
(2, 'Kevin', 'Eduardo', 'Flores', 'Mamani', 'barbero7@barberia.com', '123456', 1, NOW(), 1),
(2, 'Pedro', 'Jose', 'Quispe', 'Torrez', 'barbero8@barberia.com', '123456', 1, NOW(), 1),
(2, 'Mario', 'Fernando', 'Gutierrez', 'Rojas', 'barbero9@barberia.com', '123456', 1, NOW(), 1),
(2, 'Jose', 'Miguel', 'Choque', 'Perez', 'barbero10@barberia.com', '123456', 1, NOW(), 1);

INSERT INTO `Servicios` (`IdCategoria`, `Nombre`, `FotoURL`, `Precio`, `DuracionMinutos`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 'Corte Clasico', 'corte1.jpg', 30.00, 30, 1, NOW(), 1),
(1, 'Degradado', 'corte2.jpg', 40.00, 40, 1, NOW(), 1),
(2, 'Perfilado de Barba', 'barba1.jpg', 20.00, 20, 1, NOW(), 1),
(2, 'Barba Completa', 'barba2.jpg', 30.00, 30, 1, NOW(), 1),
(3, 'Tinte Negro', 'tinte1.jpg', 80.00, 60, 1, NOW(), 1),
(4, 'Limpieza Facial', 'facial1.jpg', 50.00, 45, 1, NOW(), 1),
(5, 'Peinado Ejecutivo', 'peinado1.jpg', 35.00, 30, 1, NOW(), 1),
(7, 'Tratamiento Capilar', 'tratamiento1.jpg', 90.00, 60, 1, NOW(), 1),
(8, 'Corte Infantil', 'ninos1.jpg', 25.00, 25, 1, NOW(), 1),
(9, 'Servicio Premium', 'premium1.jpg', 150.00, 120, 1, NOW(), 1);

INSERT INTO `Lotes` (`IdProducto`, `CantidadRecibida`, `CostoUnitario`, `FechaIngreso`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 50, 20.00, NOW(), 1, NOW(), 1),
(2, 40, 25.00, NOW(), 1, NOW(), 1),
(3, 30, 35.00, NOW(), 1, NOW(), 1),
(4, 25, 30.00, NOW(), 1, NOW(), 1),
(5, 100, 10.00, NOW(), 1, NOW(), 1),
(6, 15, 80.00, NOW(), 1, NOW(), 1),
(7, 8, 350.00, NOW(), 1, NOW(), 1),
(8, 40, 20.00, NOW(), 1, NOW(), 1),
(9, 50, 15.00, NOW(), 1, NOW(), 1),
(10, 60, 25.00, NOW(), 1, NOW(), 1);

INSERT INTO `Barberos` (`IdUsuario`, `FechaIngreso`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(2, '2026-01-05', 1, NOW(), 1),
(3, '2026-01-06', 1, NOW(), 1),
(4, '2026-01-07', 1, NOW(), 1),
(5, '2026-01-08', 1, NOW(), 1),
(6, '2026-01-09', 1, NOW(), 1),
(7, '2026-01-10', 1, NOW(), 1),
(8, '2026-01-11', 1, NOW(), 1),
(9, '2026-01-12', 1, NOW(), 1),
(10, '2026-01-13', 1, NOW(), 1),
(11, '2026-01-14', 1, NOW(), 1);

INSERT INTO `Horarios` (`DiaSemana`, `HoraEntrada`, `HoraSalida`, `DiaDescanso`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
('Lunes', '09:00:00', '18:00:00', 0, 1, NOW(), 1),
('Martes', '09:00:00', '18:00:00', 0, 1, NOW(), 1),
('Miércoles', '09:00:00', '18:00:00', 0, 1, NOW(), 1),
('Jueves', '09:00:00', '18:00:00', 0, 1, NOW(), 1),
('Viernes', '09:00:00', '18:00:00', 0, 1, NOW(), 1),
('Sábado', '09:00:00', '15:00:00', 0, 1, NOW(), 1),
('Domingo', NULL, NULL, 1, 1, NOW(), 1);

INSERT INTO `HorariosBarberos` (`IdBarbero`, `IdHorario`, `FechaInicio`, `FechaFin`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 1, '2026-06-23', '2026-06-23', 1, NOW(), 1),
(1, 2, '2026-06-24', '2026-06-24', 1, NOW(), 1),
(1, 3, '2026-06-25', '2026-06-25', 1, NOW(), 1),
(1, 4, '2026-06-26', '2026-06-26', 1, NOW(), 1),
(1, 5, '2026-06-27', '2026-06-27', 1, NOW(), 1),
(1, 6, '2026-06-28', '2026-06-28', 1, NOW(), 1),
(1, 7, '2026-06-29', '2026-06-29', 1, NOW(), 1),
(2, 1, '2026-06-23', '2026-06-23', 1, NOW(), 1),
(2, 2, '2026-06-24', '2026-06-24', 1, NOW(), 1),
(2, 3, '2026-06-25', '2026-06-25', 1, NOW(), 1),
(2, 4, '2026-06-26', '2026-06-26', 1, NOW(), 1),
(2, 5, '2026-06-27', '2026-06-27', 1, NOW(), 1),
(2, 6, '2026-06-28', '2026-06-28', 1, NOW(), 1),
(2, 7, '2026-06-29', '2026-06-29', 1, NOW(), 1);

INSERT INTO `Registros` (`IdBarbero`, `Fecha`, `HoraInicio`, `HoraFin`, `Observacion`, `Ausencia`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, '2026-06-23', '12:00:00', '13:00:00', NULL, 0, 1, NOW(), 1),
(2, '2026-06-23', '13:00:00', '14:00:00', 'Turno tardío asignado', 0, 1, NOW(), 1),
(3, '2026-06-23', '12:00:00', '13:00:00', NULL, 0, 1, NOW(), 1),
(1, '2026-06-24', '12:00:00', '13:00:00', NULL, 0, 1, NOW(), 1),
(2, '2026-06-24', '12:00:00', '13:00:00', NULL, 0, 1, NOW(), 1);

INSERT INTO `Reservas` (`IdCliente`, `IdBarbero`, `FechaCita`, `HoraInicio`, `HoraFin`, `CostoTotal`, `MontoAnticipo`, `FechaPagoAnticipo`, `MetodoPagoAnticipo`, `EstadoReserva`, `HoraAusente`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
('1000001', 1, '2026-06-23', '09:00:00', '09:45:00', 30.00, 15.00, NOW(), 'QR', 'Completada', NULL, 1, NOW(), 1),
('1000002', 2, '2026-06-23', '10:00:00', '10:55:00', 40.00, 20.00, NOW(), 'QR', 'Completada', NULL, 1, NOW(), 1),
('1000003', 3, '2026-06-23', '14:00:00', '14:45:00', 20.00, 10.00, NOW(), 'QR', 'Confirmada', NULL, 1, NOW(), 1),
('1000004', 4, '2026-06-24', '09:00:00', '09:45:00', 30.00, 15.00, NOW(), 'QR', 'Confirmada', NULL, 1, NOW(), 1),
('1000005', 5, '2026-06-24', '10:00:00', '11:10:00', 80.00, 40.00, NOW(), 'QR', 'Ausente', NOW(), 1, NOW(), 1);

INSERT INTO `ReservaServicios` (`IdReserva`, `IdServicio`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 1, 1, NOW(), 1),
(2, 2, 1, NOW(), 1),
(3, 3, 1, NOW(), 1),
(4, 4, 1, NOW(), 1),
(5, 5, 1, NOW(), 1);

INSERT INTO `Ventas` (`IdBarbero`, `IdCliente`, `IdReserva`, `Fecha`, `MontoTotal`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, '1000001', 1, NOW(), 24.00, 1, NOW(), 1),
(2, '1000003', NULL, NOW(), 72.00, 1, NOW(), 1);

INSERT INTO `DetalleVenta` (`IdVenta`, `IdProducto`, `Cantidad`, `PrecioUnitario`, `ComisionBarbero`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 1, 1, 24.00, 2.40, 1, NOW(), 1),
(2, 2, 1, 30.00, 3.00, 1, NOW(), 1),
(2, 3, 1, 42.00, 4.20, 1, NOW(), 1);

INSERT INTO `Pagos` (`IdReserva`, `IdVenta`, `TipoPago`, `Monto`, `FechaPago`, `MetodoPago`, `EstadoPago`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, NULL, 'ANT', 15.00, NOW(), 'QR', 'Pagado', 1, NOW(), 1),
(2, NULL, 'ANT', 20.00, NOW(), 'QR', 'Pagado', 1, NOW(), 1),
(3, NULL, 'ANT', 10.00, NOW(), 'QR', 'Pagado', 1, NOW(), 1),
(4, NULL, 'ANT', 15.00, NOW(), 'QR', 'Pagado', 1, NOW(), 1),
(5, NULL, 'ANT', 40.00, NOW(), 'QR', 'Pagado', 1, NOW(), 1),
(1, NULL, 'FIN', 15.00, NOW(), 'Efectivo', 'Pagado', 1, NOW(), 1),
(2, NULL, 'FIN', 20.00, NOW(), 'QR', 'Pagado', 1, NOW(), 1),
(NULL, 1, 'VEN', 24.00, NOW(), 'Efectivo', 'Pagado', 1, NOW(), 1),
(NULL, 2, 'VEN', 72.00, NOW(), 'Efectivo', 'Pagado', 1, NOW(), 1);

INSERT INTO `Comisiones` (`IdBarbero`, `IdReserva`, `IdVenta`, `TipoComision`, `Fecha`, `MontoBase`, `Porcentaje`, `MontoComision`, `EstadoA`, `FechaA`, `UsuarioA`) VALUES
(1, 1, NULL, 'SER', NOW(), 30.00, 50.00, 15.00, 1, NOW(), 1),
(2, 2, NULL, 'SER', NOW(), 40.00, 50.00, 20.00, 1, NOW(), 1),
(5, 5, NULL, 'AUS', NOW(), 40.00, 50.00, 20.00, 1, NOW(), 1),
(1, NULL, 1, 'PRO', NOW(), 24.00, 10.00, 2.40, 1, NOW(), 1),
(2, NULL, 2, 'PRO', NOW(), 72.00, 10.00, 7.20, 1, NOW(), 1);

COMMIT;

DROP PROCEDURE IF EXISTS `sp_ActualizarPorcentajeProducto`;
DROP PROCEDURE IF EXISTS `sp_DesactivarBarbero`;
DROP PROCEDURE IF EXISTS `sp_RegistrarBarbero`;
DROP PROCEDURE IF EXISTS `sp_EditarPerfilBarbero`;
DROP PROCEDURE IF EXISTS `sp_RegistrarAuditoria`;

DELIMITER $$

CREATE PROCEDURE `sp_RegistrarAuditoria`(
    IN pTablaNombre VARCHAR(50),
    IN pRegistroId VARCHAR(50),
    IN pAccion VARCHAR(50),
    IN pCampo VARCHAR(100),
    IN pValorAnterior LONGTEXT,
    IN pValorNuevo LONGTEXT,
    IN pUsuarioA INT,
    IN pDireccionIP VARCHAR(50),
    IN pDetalles VARCHAR(500)
)
BEGIN
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
END $$

DROP PROCEDURE IF EXISTS `sp_AuditoriaLoginExitoso`;

DELIMITER $$

CREATE PROCEDURE `sp_AuditoriaLoginExitoso`(
    IN pIdUsuario INT,
    IN pCorreo VARCHAR(100),
    IN pIP VARCHAR(50)
)
BEGIN
    DECLARE vExisteUsuario INT DEFAULT 0;

    SELECT COUNT(*) INTO vExisteUsuario
    FROM `Usuarios`
    WHERE `IdUsuario` = pIdUsuario;

    IF vExisteUsuario = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Usuario no encontrado para auditoría de login exitoso';
    END IF;

    CALL `sp_RegistrarAuditoria`(
        'Usuarios',
        CAST(pIdUsuario AS CHAR),
        'LOGIN_EXITOSO',
        'Autenticacion',
        NULL,
        'Acceso concedido',
        pIdUsuario,
        pIP,
        CONCAT('Correo: ', pCorreo)
    );
END $$

DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_AuditoriaLoginFallido`;

DELIMITER $$

CREATE PROCEDURE `sp_AuditoriaLoginFallido`(
    IN pCorreo VARCHAR(100),
    IN pPassword VARCHAR(255),
    IN pIP VARCHAR(50)
)
BEGIN
    DECLARE vPasswordOculta VARCHAR(255);

    SET vPasswordOculta =
        CONCAT(
            LEFT(pPassword, 1),
            REPEAT('*', GREATEST(LENGTH(pPassword) - 1, 0))
        );

    CALL `sp_RegistrarAuditoria`(
        'Usuarios',
        NULL,
        'LOGIN_FALLIDO',
        'Autenticacion',
        NULL,
        'Acceso denegado',
        NULL,
        pIP,
        CONCAT(
            'Correo: ', pCorreo,
            ' | Password: ', vPasswordOculta,
            ' (', LENGTH(pPassword), ' caracteres)'
        )
    );
END $$

DELIMITER ;

CREATE PROCEDURE `sp_EditarPerfilBarbero`(
    IN pIdBarbero INT,
    IN pNombre1 VARCHAR(50),
    IN pNombre2 VARCHAR(50),
    IN pApellido1 VARCHAR(50),
    IN pApellido2 VARCHAR(50),
    IN pCorreo VARCHAR(100),
    IN pFechaIngreso DATE,
    IN pIdAdmin INT,
    IN pIP VARCHAR(50)
)
BEGIN
    DECLARE vIdUsuario INT DEFAULT NULL;
    DECLARE vCorreoAnterior VARCHAR(100);
    DECLARE vFechaAnterior DATE;
    DECLARE vExiste INT DEFAULT 0;

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
    SET `Nombre1` = pNombre1,
        `Nombre2` = pNombre2,
        `Apellido1` = pApellido1,
        `Apellido2` = pApellido2,
        `Correo` = pCorreo
    WHERE `IdUsuario` = vIdUsuario;

    UPDATE `Barberos`
    SET `FechaIngreso` = pFechaIngreso
    WHERE `IdBarbero` = pIdBarbero;

    IF NOT (vCorreoAnterior <=> pCorreo) THEN
        CALL `sp_RegistrarAuditoria`('Usuarios', CAST(vIdUsuario AS CHAR), 'U', 'Correo', vCorreoAnterior, pCorreo, pIdAdmin, pIP, 'Admin modificó correo del barbero');
    END IF;

    IF NOT (vFechaAnterior <=> pFechaIngreso) THEN
        CALL `sp_RegistrarAuditoria`('Barberos', CAST(pIdBarbero AS CHAR), 'U', 'FechaIngreso', CAST(vFechaAnterior AS CHAR), CAST(pFechaIngreso AS CHAR), pIdAdmin, pIP, 'Admin modificó fecha de ingreso');
    END IF;
END $$

CREATE PROCEDURE `sp_RegistrarBarbero`(
    IN pNombre1 VARCHAR(50),
    IN pNombre2 VARCHAR(50),
    IN pApellido1 VARCHAR(50),
    IN pApellido2 VARCHAR(50),
    IN pCorreo VARCHAR(100),
    IN pContrasena VARCHAR(255),
    IN pFechaIngreso DATE,
    IN pIdAdmin INT,
    IN pIP VARCHAR(50),
    OUT pIdBarberoNuevo INT
)
BEGIN
    DECLARE vIdUsuario INT DEFAULT NULL;
    DECLARE vIdRolBarbero INT DEFAULT NULL;
    DECLARE vExiste INT DEFAULT 0;

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

    CALL `sp_RegistrarAuditoria`('Barberos', CAST(pIdBarberoNuevo AS CHAR), 'I', 'Registro completo', NULL, pCorreo, pIdAdmin, pIP, 'Admin registró nuevo barbero');
END $$

CREATE PROCEDURE `sp_DesactivarBarbero`(
    IN pIdBarbero INT,
    IN pIdAdmin INT,
    IN pIP VARCHAR(50)
)
BEGIN
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
END $$

CREATE PROCEDURE `sp_ActualizarPorcentajeProducto`(
    IN pIdProducto INT,
    IN pPorcentajeVenta DECIMAL(5,2),
    IN pPorcentajeBarbero DECIMAL(5,2),
    IN pIdAdmin INT,
    IN pIP VARCHAR(50)
)
BEGIN
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
END $$

DELIMITER ;