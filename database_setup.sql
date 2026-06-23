-- ============================================
-- BARBERIA - Base de Datos Completa
-- ============================================

CREATE DATABASE IF NOT EXISTS Barberia_bd;
USE Barberia_bd;

-- ============================================
-- TABLAS
-- ============================================

CREATE TABLE Roles(
    IdRol INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(50),
    Descripcion VARCHAR(255),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT
);

CREATE TABLE Clientes(
    CI VARCHAR(20) PRIMARY KEY,
    Nombre1 VARCHAR(50),
    Apellido1 VARCHAR(50),
    Telefono INT,
    Correo VARCHAR(100),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT
);

CREATE TABLE Categorias(
    IdCategoria INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100),
    DuracionMinimaMinutos INT,
    DuracionMaximaMinutos INT,
    PrecioMin DECIMAL(10,2),
    PrecioMax DECIMAL(10,2),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT
);

CREATE TABLE Productos(
    IdProducto INT AUTO_INCREMENT PRIMARY KEY,
    Nombre VARCHAR(100),
    CostoCompra DECIMAL(10,2),
    PrecioVenta DECIMAL(10,2),
    StockActual INT,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT
);

CREATE TABLE Porcentaje(
    IdPorcentaje INT AUTO_INCREMENT PRIMARY KEY,
    PorcentajeVenta DECIMAL(5,2),
    PorcentajeBarbero DECIMAL(5,2),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT
);

CREATE TABLE Usuarios(
    IdUsuario INT AUTO_INCREMENT PRIMARY KEY,
    IdRol INT,
    Nombre1 VARCHAR(50),
    Nombre2 VARCHAR(50),
    Apellido1 VARCHAR(50),
    Apellido2 VARCHAR(50),
    Correo VARCHAR(100),
    Contraseña VARCHAR(255),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdRol)
    REFERENCES Roles(IdRol)
);

CREATE TABLE Servicios(
    IdServicio INT AUTO_INCREMENT PRIMARY KEY,
    IdCategoria INT,
    Nombre VARCHAR(100),
    FotoURL VARCHAR(255),
    Precio DECIMAL(10,2),
    DuracionMinutos INT,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdCategoria)
    REFERENCES Categorias(IdCategoria)
);

CREATE TABLE Lotes(
    IdLote INT AUTO_INCREMENT PRIMARY KEY,
    IdProducto INT,
    CantidadRecibida INT,
    CostoUnitario DECIMAL(10,2),
    FechaIngreso DATETIME,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdProducto)
    REFERENCES Productos(IdProducto)
);

CREATE TABLE PorcentajeProductos(
    IdPorcentajeProducto INT AUTO_INCREMENT PRIMARY KEY,
    IdPorcentaje INT,
    IdProducto INT,
    CostoVentaTotal DECIMAL(10,2),
    MontoAñadido DECIMAL(10,2),
    FechaInicio DATE,
    FechaFin DATE,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdPorcentaje)
    REFERENCES Porcentaje(IdPorcentaje),

    FOREIGN KEY(IdProducto)
    REFERENCES Productos(IdProducto)
);

CREATE TABLE Barberos(
    IdBarbero INT AUTO_INCREMENT PRIMARY KEY,
    IdUsuario INT,
    FechaIngreso DATE,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdUsuario)
    REFERENCES Usuarios(IdUsuario)
);

CREATE TABLE Reservas(
    IdReserva INT AUTO_INCREMENT PRIMARY KEY,
    IdCliente VARCHAR(20),
    IdBarbero INT,
    FechaCita DATE,
    HoraInicio TIME,
    HoraFin TIME,
    CostoTotal DECIMAL(10,2),
    MontoAnticipo DECIMAL(10,2),
    EstadoReserva VARCHAR(30),
    FechaPagoAnticipo DATETIME,
    MetodoPagoFinal VARCHAR(50),
    HoraAusente DATETIME,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdCliente)
    REFERENCES Clientes(CI),

    FOREIGN KEY(IdBarbero)
    REFERENCES Barberos(IdBarbero)
);

CREATE TABLE ComisionesSemanales(
    IdComision INT AUTO_INCREMENT PRIMARY KEY,
    IdBarbero INT,
    Semana INT,
    Año INT,
    TotalServicios DECIMAL(10,2),
    ComisionServicios DECIMAL(10,2),
    TotalVentas DECIMAL(10,2),
    ComisionVentas DECIMAL(10,2),
    TotalAusentes DECIMAL(10,2),
    ComisionAusentes DECIMAL(10,2),
    ComisionTotal DECIMAL(10,2),
    EstadoConsolidarSemana BIT,
    FechaConsolidado DATETIME,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdBarbero)
    REFERENCES Barberos(IdBarbero)
);

CREATE TABLE HorariosSemanales(
    IdHorarioSemanal INT AUTO_INCREMENT PRIMARY KEY,
    IdBarbero INT,
    Semana INT,
    Año INT,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdBarbero)
    REFERENCES Barberos(IdBarbero)
);

CREATE TABLE TurnoAlmuerzosTardios(
    IdTurno INT AUTO_INCREMENT PRIMARY KEY,
    IdBarbero INT,
    IdBarberoSustituto INT,
    DiaSustituto VARCHAR(20),
    Semana INT,
    Año INT,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdBarbero)
    REFERENCES Barberos(IdBarbero)
);

CREATE TABLE ReservaServicios(
    IdReservaServicio INT AUTO_INCREMENT PRIMARY KEY,
    IdServicio INT,
    IdReserva INT,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdServicio)
    REFERENCES Servicios(IdServicio),

    FOREIGN KEY(IdReserva)
    REFERENCES Reservas(IdReserva)
);

CREATE TABLE NotaVentas(
    IdNota INT AUTO_INCREMENT PRIMARY KEY,
    IdReserva INT,
    FechaEmision DATETIME,
    MontoServicios DECIMAL(10,2),
    MontoProductos DECIMAL(10,2),
    MontoTotal DECIMAL(10,2),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdReserva)
    REFERENCES Reservas(IdReserva)
);

CREATE TABLE Horarios(
    IdHorario INT AUTO_INCREMENT PRIMARY KEY,
    IdHorarioSemanal INT,
    DiaSemana VARCHAR(20),
    HoraEntrada TIME,
    HoraSalida TIME,
    DiaDescanso BIT,
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdHorarioSemanal)
    REFERENCES HorariosSemanales(IdHorarioSemanal)
);

CREATE TABLE DetalleComisiones(
    IdDetalle INT AUTO_INCREMENT PRIMARY KEY,
    IdComision INT,
    IdReserva INT,
    Fecha DATETIME,
    Monto DECIMAL(10,2),
    Comision DECIMAL(10,2),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdComision)
    REFERENCES ComisionesSemanales(IdComision),

    FOREIGN KEY(IdReserva)
    REFERENCES Reservas(IdReserva)
);

CREATE TABLE VentaProductos(
    IdVenta INT AUTO_INCREMENT PRIMARY KEY,
    IdNota INT,
    IdProducto INT,
    IdPorcentajeProducto INT,
    Cantidad INT,
    PrecioUnitario DECIMAL(10,2),
    MontoTotal DECIMAL(10,2),
    EstadoA BIT,
    FechaA DATETIME,
    UsuarioA INT,

    FOREIGN KEY(IdNota)
    REFERENCES NotaVentas(IdNota),

    FOREIGN KEY(IdProducto)
    REFERENCES Productos(IdProducto),

    FOREIGN KEY(IdPorcentajeProducto)
    REFERENCES PorcentajeProductos(IdPorcentajeProducto)
);

CREATE TABLE AuditoriaGeneral (
    IdAuditoria INT AUTO_INCREMENT PRIMARY KEY,
    TablaNombre VARCHAR(50) NULL,
    RegistroId INT NULL,
    Accion VARCHAR(50) NULL,
    Campo VARCHAR(100) NULL,
    ValorAnterior LONGTEXT NULL,
    ValorNuevo LONGTEXT NULL,
    UsuarioA INT NULL,
    FechaA DATETIME NULL,
    DireccionIP VARCHAR(50) NULL,
    Detalles VARCHAR(500) NULL,

    CONSTRAINT FK_AuditoriaGeneral_Usuarios
        FOREIGN KEY (UsuarioA)
        REFERENCES Usuarios(IdUsuario)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- ============================================
-- DATOS SEED
-- ============================================

INSERT INTO Roles
(Nombre, Descripcion, EstadoA, FechaA, UsuarioA)
VALUES
('Administrador', 'Control total del sistema', 1, NOW(), 1),
('Barbero', 'Gestiona reservas, horarios y servicios', 1, NOW(), 1);

INSERT INTO Clientes
(CI, Nombre1, Apellido1, Telefono, Correo, EstadoA, FechaA, UsuarioA)
VALUES
('1000001','Juan','Mamani',71234561,'juan@gmail.com',1,NOW(),1),
('1000002','Pedro','Quispe',71234562,'pedro@gmail.com',1,NOW(),1),
('1000003','Carlos','Condori',71234563,'carlos@gmail.com',1,NOW(),1),
('1000004','Mario','Flores',71234564,'mario@gmail.com',1,NOW(),1),
('1000005','Miguel','Choque',71234565,'miguel@gmail.com',1,NOW(),1),
('1000006','Jose','Rojas',71234566,'jose@gmail.com',1,NOW(),1),
('1000007','Kevin','Perez',71234567,'kevin@gmail.com',1,NOW(),1),
('1000008','Luis','Arias',71234568,'luis@gmail.com',1,NOW(),1),
('1000009','Jorge','Vargas',71234569,'jorge@gmail.com',1,NOW(),1),
('1000010','Diego','Lopez',71234570,'diego@gmail.com',1,NOW(),1);

INSERT INTO Categorias
(Nombre, DuracionMinimaMinutos, DuracionMaximaMinutos, PrecioMin, PrecioMax, EstadoA, FechaA, UsuarioA)
VALUES
('Cortes',20,60,20.00,80.00,1,NOW(),1),
('Barbas',15,40,15.00,50.00,1,NOW(),1),
('Tintes',30,120,50.00,250.00,1,NOW(),1),
('Faciales',20,90,30.00,150.00,1,NOW(),1),
('Peinados',20,60,20.00,100.00,1,NOW(),1),
('Masajes',30,90,50.00,200.00,1,NOW(),1),
('Tratamientos Capilares',30,120,40.00,300.00,1,NOW(),1),
('Cortes Infantiles',20,40,20.00,50.00,1,NOW(),1),
('Premium',60,180,100.00,500.00,1,NOW(),1),
('Paquetes Especiales',60,240,80.00,600.00,1,NOW(),1);

INSERT INTO Productos
(Nombre, CostoCompra, PrecioVenta, StockActual, EstadoA, FechaA, UsuarioA)
VALUES
('Gel Fijador',20,35,30,1,NOW(),1),
('Cera para Cabello',25,40,25,1,NOW(),1),
('Shampoo Anticaida',35,55,20,1,NOW(),1),
('Aceite para Barba',30,50,18,1,NOW(),1),
('Peine Profesional',10,20,50,1,NOW(),1),
('Tijera Profesional',80,120,10,1,NOW(),1),
('Máquina de Corte',350,500,5,1,NOW(),1),
('Loción After Shave',20,35,20,1,NOW(),1),
('Cepillo para Barba',15,25,25,1,NOW(),1),
('Pomada Capilar',25,45,30,1,NOW(),1);

INSERT INTO Porcentaje
(PorcentajeVenta, PorcentajeBarbero, EstadoA, FechaA, UsuarioA)
VALUES
(10,5,1,NOW(),1),
(12,6,1,NOW(),1),
(15,7,1,NOW(),1),
(18,8,1,NOW(),1),
(20,10,1,NOW(),1),
(22,11,1,NOW(),1),
(25,12,1,NOW(),1),
(28,14,1,NOW(),1),
(30,15,1,NOW(),1),
(35,18,1,NOW(),1);

INSERT INTO Usuarios
(IdRol, Nombre1, Nombre2, Apellido1, Apellido2, Correo, Contraseña, EstadoA, FechaA, UsuarioA)
VALUES
(1,'Ricardo','Alejandro','Limachi','Flores','admin@barberia.com','123456',1,NOW(),1),
(2,'Carlos','Andres','Mamani','Quispe','barbero1@barberia.com','123456',1,NOW(),1),
(2,'Luis','Fernando','Condori','Flores','barbero2@barberia.com','123456',1,NOW(),1),
(2,'Jorge','Alberto','Choque','Rojas','barbero3@barberia.com','123456',1,NOW(),1),
(2,'Miguel','Angel','Perez','Mendoza','barbero4@barberia.com','123456',1,NOW(),1),
(2,'Juan','Carlos','Vargas','Lopez','barbero5@barberia.com','123456',1,NOW(),1),
(2,'Diego','Alejandro','Arias','Gomez','barbero6@barberia.com','123456',1,NOW(),1),
(2,'Kevin','Eduardo','Flores','Mamani','barbero7@barberia.com','123456',1,NOW(),1),
(2,'Pedro','Jose','Quispe','Torrez','barbero8@barberia.com','123456',1,NOW(),1),
(2,'Mario','Fernando','Gutierrez','Rojas','barbero9@barberia.com','123456',1,NOW(),1),
(2,'Jose','Miguel','Choque','Perez','barbero10@barberia.com','123456',1,NOW(),1);

INSERT INTO Servicios
(IdCategoria, Nombre, FotoURL, Precio, DuracionMinutos, EstadoA, FechaA, UsuarioA)
VALUES
(1,'Corte Clasico','corte1.jpg',30,30,1,NOW(),1),
(1,'Degradado','corte2.jpg',40,40,1,NOW(),1),
(2,'Perfilado de Barba','barba1.jpg',20,20,1,NOW(),1),
(2,'Barba Completa','barba2.jpg',30,30,1,NOW(),1),
(3,'Tinte Negro','tinte1.jpg',80,60,1,NOW(),1),
(4,'Limpieza Facial','facial1.jpg',50,45,1,NOW(),1),
(5,'Peinado Ejecutivo','peinado1.jpg',35,30,1,NOW(),1),
(7,'Tratamiento Capilar','tratamiento1.jpg',90,60,1,NOW(),1),
(8,'Corte Infantil','niños1.jpg',25,25,1,NOW(),1),
(9,'Servicio Premium','premium1.jpg',150,120,1,NOW(),1);

INSERT INTO Barberos
(IdUsuario, FechaIngreso, EstadoA, FechaA, UsuarioA)
VALUES
(2,'2026-01-05',1,NOW(),1),
(3,'2026-01-06',1,NOW(),1),
(4,'2026-01-07',1,NOW(),1),
(5,'2026-01-08',1,NOW(),1),
(6,'2026-01-09',1,NOW(),1),
(7,'2026-01-10',1,NOW(),1),
(8,'2026-01-11',1,NOW(),1),
(9,'2026-01-12',1,NOW(),1),
(10,'2026-01-13',1,NOW(),1),
(11,'2026-01-14',1,NOW(),1);

INSERT INTO Lotes
(IdProducto, CantidadRecibida, CostoUnitario, FechaIngreso, EstadoA, FechaA, UsuarioA)
VALUES
(1,50,20,NOW(),1,NOW(),1),
(2,40,25,NOW(),1,NOW(),1),
(3,30,35,NOW(),1,NOW(),1),
(4,25,30,NOW(),1,NOW(),1),
(5,100,10,NOW(),1,NOW(),1),
(6,15,80,NOW(),1,NOW(),1),
(7,8,350,NOW(),1,NOW(),1),
(8,40,20,NOW(),1,NOW(),1),
(9,50,15,NOW(),1,NOW(),1),
(10,60,25,NOW(),1,NOW(),1);

INSERT INTO PorcentajeProductos
(IdPorcentaje, IdProducto, CostoVentaTotal, MontoAñadido, FechaInicio, FechaFin, EstadoA, FechaA, UsuarioA)
VALUES
(1,1,35,15,'2026-01-01','2026-12-31',1,NOW(),1),
(2,2,40,15,'2026-01-01','2026-12-31',1,NOW(),1),
(3,3,55,20,'2026-01-01','2026-12-31',1,NOW(),1),
(4,4,50,20,'2026-01-01','2026-12-31',1,NOW(),1),
(5,5,20,10,'2026-01-01','2026-12-31',1,NOW(),1),
(6,6,120,40,'2026-01-01','2026-12-31',1,NOW(),1),
(7,7,500,150,'2026-01-01','2026-12-31',1,NOW(),1),
(8,8,35,15,'2026-01-01','2026-12-31',1,NOW(),1),
(9,9,25,10,'2026-01-01','2026-12-31',1,NOW(),1),
(10,10,45,20,'2026-01-01','2026-12-31',1,NOW(),1);

INSERT INTO Reservas
(IdCliente, IdBarbero, FechaCita, HoraInicio, HoraFin,
CostoTotal, MontoAnticipo, EstadoReserva,
FechaPagoAnticipo, MetodoPagoFinal,
HoraAusente, EstadoA, FechaA, UsuarioA)
VALUES
('1000001',1,'2026-06-20','09:00:00','09:30:00',30,10,'Confirmada',NOW(),'Efectivo',NULL,1,NOW(),1),
('1000002',2,'2026-06-20','10:00:00','10:40:00',40,10,'Confirmada',NOW(),'QR',NULL,1,NOW(),1),
('1000003',3,'2026-06-20','11:00:00','11:20:00',20,10,'Confirmada',NOW(),'Efectivo',NULL,1,NOW(),1),
('1000004',4,'2026-06-20','12:00:00','12:30:00',30,10,'Confirmada',NOW(),'Tarjeta',NULL,1,NOW(),1),
('1000005',5,'2026-06-20','13:00:00','14:00:00',80,20,'Confirmada',NOW(),'QR',NULL,1,NOW(),1),
('1000006',6,'2026-06-20','14:00:00','14:45:00',50,20,'Confirmada',NOW(),'Efectivo',NULL,1,NOW(),1),
('1000007',7,'2026-06-20','15:00:00','15:30:00',35,10,'Confirmada',NOW(),'QR',NULL,1,NOW(),1),
('1000008',8,'2026-06-20','16:00:00','17:00:00',90,20,'Confirmada',NOW(),'Tarjeta',NULL,1,NOW(),1),
('1000009',9,'2026-06-20','17:00:00','17:25:00',25,10,'Confirmada',NOW(),'Efectivo',NULL,1,NOW(),1),
('1000010',10,'2026-06-20','18:00:00','20:00:00',150,50,'Confirmada',NOW(),'QR',NULL,1,NOW(),1);

INSERT INTO ComisionesSemanales
(IdBarbero, Semana, Año,
TotalServicios, ComisionServicios,
TotalVentas, ComisionVentas,
TotalAusentes, ComisionAusentes,
ComisionTotal, EstadoConsolidarSemana,
FechaConsolidado, EstadoA, FechaA, UsuarioA)
VALUES
(1,25,2026,30,3,20,2,0,0,5,1,NOW(),1,NOW(),1),
(2,25,2026,40,4,30,3,0,0,7,1,NOW(),1,NOW(),1),
(3,25,2026,20,2,20,2,0,0,4,1,NOW(),1,NOW(),1),
(4,25,2026,30,3,15,2,0,0,5,1,NOW(),1,NOW(),1),
(5,25,2026,80,8,25,3,0,0,11,1,NOW(),1,NOW(),1),
(6,25,2026,50,5,30,3,0,0,8,1,NOW(),1,NOW(),1),
(7,25,2026,35,3.5,20,2,0,0,5.5,1,NOW(),1,NOW(),1),
(8,25,2026,90,9,40,4,0,0,13,1,NOW(),1,NOW(),1),
(9,25,2026,25,2.5,15,1.5,0,0,4,1,NOW(),1,NOW(),1),
(10,25,2026,150,15,60,6,0,0,21,1,NOW(),1,NOW(),1);

INSERT INTO HorariosSemanales
(IdBarbero, Semana, Año, EstadoA, FechaA, UsuarioA)
VALUES
(1,25,2026,1,NOW(),1),
(2,25,2026,1,NOW(),1),
(3,25,2026,1,NOW(),1),
(4,25,2026,1,NOW(),1),
(5,25,2026,1,NOW(),1),
(6,25,2026,1,NOW(),1),
(7,25,2026,1,NOW(),1),
(8,25,2026,1,NOW(),1),
(9,25,2026,1,NOW(),1),
(10,25,2026,1,NOW(),1);

INSERT INTO TurnoAlmuerzosTardios
(IdBarbero, IdBarberoSustituto, DiaSustituto,
Semana, Año, EstadoA, FechaA, UsuarioA)
VALUES
(1,2,'Lunes',25,2026,1,NOW(),1),
(2,3,'Martes',25,2026,1,NOW(),1),
(3,4,'Miércoles',25,2026,1,NOW(),1),
(4,5,'Jueves',25,2026,1,NOW(),1),
(5,6,'Viernes',25,2026,1,NOW(),1),
(6,7,'Sábado',25,2026,1,NOW(),1),
(7,8,'Lunes',25,2026,1,NOW(),1),
(8,9,'Martes',25,2026,1,NOW(),1),
(9,10,'Miércoles',25,2026,1,NOW(),1),
(10,1,'Jueves',25,2026,1,NOW(),1);

INSERT INTO ReservaServicios
(IdServicio, IdReserva, EstadoA, FechaA, UsuarioA)
VALUES
(1,1,1,NOW(),1),
(2,2,1,NOW(),1),
(3,3,1,NOW(),1),
(4,4,1,NOW(),1),
(5,5,1,NOW(),1),
(6,6,1,NOW(),1),
(7,7,1,NOW(),1),
(8,8,1,NOW(),1),
(9,9,1,NOW(),1),
(10,10,1,NOW(),1);

INSERT INTO NotaVentas
(IdReserva, FechaEmision, MontoServicios,
MontoProductos, MontoTotal,
EstadoA, FechaA, UsuarioA)
VALUES
(1,NOW(),30,35,65,1,NOW(),1),
(2,NOW(),40,40,80,1,NOW(),1),
(3,NOW(),20,55,75,1,NOW(),1),
(4,NOW(),30,50,80,1,NOW(),1),
(5,NOW(),80,20,100,1,NOW(),1),
(6,NOW(),50,120,170,1,NOW(),1),
(7,NOW(),35,500,535,1,NOW(),1),
(8,NOW(),90,35,125,1,NOW(),1),
(9,NOW(),25,25,50,1,NOW(),1),
(10,NOW(),150,45,195,1,NOW(),1);

INSERT INTO Horarios
(IdHorarioSemanal, DiaSemana, HoraEntrada,
HoraSalida, DiaDescanso,
EstadoA, FechaA, UsuarioA)
VALUES
(1,'Lunes','09:00:00','18:00:00',0,1,NOW(),1),
(2,'Martes','09:00:00','18:00:00',0,1,NOW(),1),
(3,'Miércoles','09:00:00','18:00:00',0,1,NOW(),1),
(4,'Jueves','09:00:00','18:00:00',0,1,NOW(),1),
(5,'Viernes','09:00:00','18:00:00',0,1,NOW(),1),
(6,'Lunes','09:00:00','18:00:00',0,1,NOW(),1),
(7,'Martes','09:00:00','18:00:00',0,1,NOW(),1),
(8,'Miércoles','09:00:00','18:00:00',0,1,NOW(),1),
(9,'Jueves','09:00:00','18:00:00',0,1,NOW(),1),
(10,'Viernes','09:00:00','18:00:00',0,1,NOW(),1);

INSERT INTO DetalleComisiones
(IdComision, IdReserva, Fecha,
Monto, Comision,
EstadoA, FechaA, UsuarioA)
VALUES
(1,1,NOW(),30,3,1,NOW(),1),
(2,2,NOW(),40,4,1,NOW(),1),
(3,3,NOW(),20,2,1,NOW(),1),
(4,4,NOW(),30,3,1,NOW(),1),
(5,5,NOW(),80,8,1,NOW(),1),
(6,6,NOW(),50,5,1,NOW(),1),
(7,7,NOW(),35,3.5,1,NOW(),1),
(8,8,NOW(),90,9,1,NOW(),1),
(9,9,NOW(),25,2.5,1,NOW(),1),
(10,10,NOW(),150,15,1,NOW(),1);

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS - AUDITORÍA
-- ============================================

DELIMITER $$

CREATE PROCEDURE sp_RegistrarAuditoria(
    IN pTablaNombre VARCHAR(50),
    IN pRegistroId INT,
    IN pAccion VARCHAR(50),
    IN pCampo VARCHAR(100),
    IN pValorAnterior LONGTEXT,
    IN pValorNuevo LONGTEXT,
    IN pUsuarioA INT,
    IN pDireccionIP VARCHAR(50),
    IN pDetalles VARCHAR(500)
)
BEGIN
    INSERT INTO AuditoriaGeneral
    (TablaNombre, RegistroId, Accion, Campo, ValorAnterior, ValorNuevo, UsuarioA, FechaA, DireccionIP, Detalles)
    VALUES
    (pTablaNombre, pRegistroId, pAccion, pCampo, pValorAnterior, pValorNuevo, pUsuarioA, NOW(), pDireccionIP, pDetalles);
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_AuditoriaLoginExitoso(
    IN pIdUsuario INT,
    IN pIP VARCHAR(50)
)
BEGIN
    INSERT INTO AuditoriaGeneral
    (TablaNombre, RegistroId, Accion, Campo, ValorAnterior, ValorNuevo, UsuarioA, FechaA, DireccionIP, Detalles)
    VALUES
    ('Usuarios', pIdUsuario, 'LOGIN_EXITOSO', 'Autenticacion', NULL, 'Acceso concedido', pIdUsuario, NOW(), pIP, 'Inicio de sesión correcto');
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_AuditoriaLoginFallido(
    IN pCorreo VARCHAR(100),
    IN pIP VARCHAR(50)
)
BEGIN
    INSERT INTO AuditoriaGeneral
    (TablaNombre, RegistroId, Accion, Campo, ValorAnterior, ValorNuevo, UsuarioA, FechaA, DireccionIP, Detalles)
    VALUES
    ('Usuarios', NULL, 'LOGIN_FALLIDO', 'Autenticacion', NULL, pCorreo, NULL, NOW(), pIP, 'Intento de inicio de sesión fallido');
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_AuditoriaVerPerfilBarbero(
    IN pIdBarbero INT,
    IN pIdUsuario INT,
    IN pIP VARCHAR(50)
)
BEGIN
    INSERT INTO AuditoriaGeneral
    (TablaNombre, RegistroId, Accion, Campo, UsuarioA, FechaA, DireccionIP, Detalles)
    VALUES
    ('Barberos', pIdBarbero, 'CONSULTA', 'Perfil', pIdUsuario, NOW(), pIP, 'Barbero visualizó su perfil');
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_AuditoriaAdminVerPerfilBarbero(
    IN pIdBarbero INT,
    IN pIdAdmin INT,
    IN pIP VARCHAR(50)
)
BEGIN
    INSERT INTO AuditoriaGeneral
    (TablaNombre, RegistroId, Accion, Campo, UsuarioA, FechaA, DireccionIP, Detalles)
    VALUES
    ('Barberos', pIdBarbero, 'CONSULTA_ADMIN', 'Perfil', pIdAdmin, NOW(), pIP, 'Administrador consultó perfil del barbero');
END $$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_EditarPerfilBarbero(
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
    DECLARE vIdUsuario INT;
    DECLARE vCorreoAnterior VARCHAR(100);
    DECLARE vFechaAnterior DATE;

    SELECT u.IdUsuario, u.Correo, b.FechaIngreso
    INTO vIdUsuario, vCorreoAnterior, vFechaAnterior
    FROM Usuarios u
    INNER JOIN Barberos b ON u.IdUsuario = b.IdUsuario
    WHERE b.IdBarbero = pIdBarbero;

    IF EXISTS(
        SELECT * FROM Usuarios
        WHERE Correo = pCorreo AND IdUsuario <> vIdUsuario
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El correo ya existe';
    END IF;

    IF pFechaIngreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser futura';
    END IF;

    UPDATE Usuarios
    SET Nombre1 = pNombre1, Nombre2 = pNombre2,
        Apellido1 = pApellido1, Apellido2 = pApellido2,
        Correo = pCorreo
    WHERE IdUsuario = vIdUsuario;

    UPDATE Barberos
    SET FechaIngreso = pFechaIngreso
    WHERE IdBarbero = pIdBarbero;

    IF vCorreoAnterior <> pCorreo THEN
        CALL sp_RegistrarAuditoria(
            'Usuarios', vIdUsuario, 'UPDATE', 'Correo',
            vCorreoAnterior, pCorreo, pIdAdmin, pIP,
            'Administrador modificó correo del barbero'
        );
    END IF;

    IF vFechaAnterior <> pFechaIngreso THEN
        CALL sp_RegistrarAuditoria(
            'Barberos', pIdBarbero, 'UPDATE', 'FechaIngreso',
            vFechaAnterior, pFechaIngreso, pIdAdmin, pIP,
            'Administrador modificó fecha de ingreso'
        );
    END IF;
END $$

DELIMITER ;

-- EDICION DE DASHBOARD ADMINISTRADOR (Mariscal)
-- CREAR PROCEDIMIENTOS ALMACENADOS DESDE INTERFAZ GRAFICA

-- RegistrarBarbero

CREATE PROCEDURE sp_RegistrarBarbero(
    IN pNombre1     VARCHAR(50),
    IN pNombre2     VARCHAR(50),
    IN pApellido1   VARCHAR(50),
    IN pApellido2   VARCHAR(50),
    IN pCorreo      VARCHAR(100),
    IN pContrasena  VARCHAR(255),
    IN pFechaIngreso DATE,
    IN pIdAdmin     INT,
    IN pIP          VARCHAR(50),
    OUT pIdBarberoNuevo INT
)
BEGIN
    DECLARE vIdUsuario INT;

    IF EXISTS (
        SELECT 1 FROM Usuarios WHERE Correo = pCorreo
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El correo ya está registrado en el sistema';
    END IF;

    IF pFechaIngreso > CURDATE() THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha de ingreso no puede ser posterior a hoy';
    END IF;

    INSERT INTO Usuarios
        (IdRol, Nombre1, Nombre2, Apellido1, Apellido2, Correo, Contraseña, EstadoA, FechaA, UsuarioA)
    VALUES
        (2, pNombre1, pNombre2, pApellido1, pApellido2, pCorreo, pContrasena, 1, NOW(), pIdAdmin);

    SET vIdUsuario = LAST_INSERT_ID();

    INSERT INTO Barberos
        (IdUsuario, FechaIngreso, EstadoA, FechaA, UsuarioA)
    VALUES
        (vIdUsuario, pFechaIngreso, 1, NOW(), pIdAdmin);

    SET pIdBarberoNuevo = LAST_INSERT_ID();

    CALL sp_RegistrarAuditoria(
        'Barberos', pIdBarberoNuevo, 'INSERT', 'Registro completo',
        NULL, pCorreo, pIdAdmin, pIP,
        'Administrador registró nuevo barbero'
    );

END

-- Desactivar Barbero

CREATE PROCEDURE sp_DesactivarBarbero(
    IN pIdBarbero INT,
    IN pIdAdmin   INT,
    IN pIP        VARCHAR(50)
)
BEGIN
    DECLARE vIdUsuario INT;

    SELECT IdUsuario INTO vIdUsuario
    FROM Barberos
    WHERE IdBarbero = pIdBarbero;

    IF vIdUsuario IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Barbero no encontrado';
    END IF;

    UPDATE Usuarios SET EstadoA = 0 WHERE IdUsuario = vIdUsuario;
    UPDATE Barberos SET EstadoA = 0 WHERE IdBarbero = pIdBarbero;

    CALL sp_RegistrarAuditoria(
        'Barberos', pIdBarbero, 'UPDATE', 'EstadoA',
        '1', '0', pIdAdmin, pIP,
        'Administrador desactivó barbero'
    );

END

-- AsignarHorarioSemanal

CREATE PROCEDURE sp_AsignarHorarioSemanal(
    IN pIdBarbero         INT,
    IN pSemana            INT,
    IN pAno               INT,
    IN pDias              JSON,
    IN pIdAdmin           INT,
    IN pIP                VARCHAR(50),
    OUT pIdHorarioSemanal INT
)
BEGIN
    DECLARE vDia      VARCHAR(20);
    DECLARE vEntrada  TIME;
    DECLARE vSalida   TIME;
    DECLARE vDescanso TINYINT;
    DECLARE vHoras    DECIMAL(5,2);
    DECLARE i         INT DEFAULT 0;
    DECLARE vTotal    INT;

    SET vTotal = JSON_LENGTH(pDias);

    IF vTotal = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Debe configurar al menos un día de trabajo';
    END IF;

    INSERT INTO HorariosSemanales
        (IdBarbero, Semana, Año, EstadoA, FechaA, UsuarioA)
    VALUES
        (pIdBarbero, pSemana, pAno, 1, NOW(), pIdAdmin);

    SET pIdHorarioSemanal = LAST_INSERT_ID();

    WHILE i < vTotal DO
        SET vDia      = JSON_UNQUOTE(JSON_EXTRACT(pDias, CONCAT('$[', i, '].dia')));
        SET vEntrada  = JSON_UNQUOTE(JSON_EXTRACT(pDias, CONCAT('$[', i, '].hora_entrada')));
        SET vSalida   = JSON_UNQUOTE(JSON_EXTRACT(pDias, CONCAT('$[', i, '].hora_salida')));
        SET vDescanso = JSON_EXTRACT(pDias, CONCAT('$[', i, '].dia_descanso'));

        IF vDescanso = 0 THEN
            SET vHoras = (TIME_TO_SEC(vSalida) - TIME_TO_SEC(vEntrada)) / 3600 - 1;

            IF vHoras < 8 THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Cada día laboral debe tener mínimo 8 horas efectivas de trabajo';
            END IF;
        END IF;

        INSERT INTO Horarios
            (IdHorarioSemanal, DiaSemana, HoraEntrada, HoraSalida, DiaDescanso, EstadoA, FechaA, UsuarioA)
        VALUES
            (pIdHorarioSemanal, vDia, vEntrada, vSalida, vDescanso, 1, NOW(), pIdAdmin);

        SET i = i + 1;
    END WHILE;

    CALL sp_RegistrarAuditoria(
        'HorariosSemanales', pIdHorarioSemanal, 'INSERT', 'Horario asignado',
        NULL, CONCAT('Semana ', pSemana, ' - Año ', pAno),
        pIdAdmin, pIP, 'Administrador asignó horario semanal al barbero'
    );

END

--GenerarHorarioSemanal

CREATE PROCEDURE sp_GenerarHorarioSemana(
    IN pSemana  INT,
    IN pAno     INT,
    IN pIdAdmin INT,
    IN pIP      VARCHAR(50)
)
BEGIN
    DECLARE vIdBarbero        INT;
    DECLARE vIdBarberoAlmuerzo INT DEFAULT NULL;
    DECLARE vUltimoAlmuerzo   INT DEFAULT NULL;
    DECLARE vDiaDescanso      VARCHAR(20);
    DECLARE vContador         INT DEFAULT 0;
    DECLARE vFin              INT DEFAULT 0;

    -- Días disponibles para descanso FIFO (lunes a jueves)
    DECLARE vDias VARCHAR(100) DEFAULT 'Lunes,Martes,Miércoles,Jueves';

    -- Cursor: barberos activos ordenados por antigüedad (más antiguo primero = FIFO)
    DECLARE cur CURSOR FOR
        SELECT b.IdBarbero
        FROM Barberos b
        INNER JOIN Usuarios u ON b.IdUsuario = u.IdUsuario
        WHERE b.EstadoA = 1 AND u.EstadoA = 1
        ORDER BY b.FechaIngreso ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET vFin = 1;

    -- Verificar si ya existe la semana generada
    IF EXISTS (
        SELECT 1 FROM HorariosSemanales
        WHERE Semana = pSemana AND Año = pAno AND EstadoA = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ya existe un horario generado para esta semana';
    END IF;

    -- Buscar quién tuvo turno de almuerzo tardío la semana anterior
    SELECT IdBarbero INTO vUltimoAlmuerzo
    FROM TurnoAlmuerzosTardios
    WHERE Semana = pSemana - 1 AND Año = pAno AND EstadoA = 1
    LIMIT 1;

    -- Seleccionar barbero para almuerzo tardío esta semana
    -- (aleatorio entre activos, excluyendo al de la semana pasada)
    SELECT b.IdBarbero INTO vIdBarberoAlmuerzo
    FROM Barberos b
    INNER JOIN Usuarios u ON b.IdUsuario = u.IdUsuario
    WHERE b.EstadoA = 1 AND u.EstadoA = 1
      AND b.IdBarbero != COALESCE(vUltimoAlmuerzo, -1)
    ORDER BY RAND()
    LIMIT 1;

    -- Si todos los barberos ya rotaron (solo queda uno y es el último),
    -- reiniciar la rotación
    IF vIdBarberoAlmuerzo IS NULL THEN
        SELECT b.IdBarbero INTO vIdBarberoAlmuerzo
        FROM Barberos b
        INNER JOIN Usuarios u ON b.IdUsuario = u.IdUsuario
        WHERE b.EstadoA = 1 AND u.EstadoA = 1
        ORDER BY RAND()
        LIMIT 1;
    END IF;

    -- Registrar turno de almuerzo tardío
    INSERT INTO TurnoAlmuerzosTardios
        (IdBarbero, IdBarberoSustituto, DiaSustituto, Semana, Año, EstadoA, FechaA, UsuarioA)
    VALUES
        (vIdBarberoAlmuerzo, vIdBarberoAlmuerzo, 'Todos', pSemana, pAno, 1, NOW(), pIdAdmin);

    -- Asignar días de descanso FIFO: recorrer barberos por antigüedad
    OPEN cur;

    recorrer: LOOP
        FETCH cur INTO vIdBarbero;
        IF vFin THEN LEAVE recorrer; END IF;

        SET vContador = vContador + 1;

        -- Asignar día según posición FIFO (1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves)
        CASE vContador
            WHEN 1 THEN SET vDiaDescanso = 'Lunes';
            WHEN 2 THEN SET vDiaDescanso = 'Martes';
            WHEN 3 THEN SET vDiaDescanso = 'Miércoles';
            WHEN 4 THEN SET vDiaDescanso = 'Jueves';
            ELSE SET vDiaDescanso = NULL;
        END CASE;

        -- Crear registro semanal para este barbero
        INSERT INTO HorariosSemanales
            (IdBarbero, Semana, Año, EstadoA, FechaA, UsuarioA)
        VALUES
            (vIdBarbero, pSemana, pAno, 1, NOW(), pIdAdmin);

        -- Registrar auditoría
        CALL sp_RegistrarAuditoria(
            'HorariosSemanales', LAST_INSERT_ID(), 'INSERT', 'Semana generada',
            NULL, CONCAT('Semana ', pSemana, ' Año ', pAno),
            pIdAdmin, pIP, CONCAT('FIFO descanso: ', COALESCE(vDiaDescanso, 'Sin descanso asignado'))
        );

    END LOOP;

    CLOSE cur;

END


