CREATE DATABASE cine_db;
GO

USE cine_db;
GO

CREATE TABLE Generos (
    IdGenero     INT IDENTITY(1,1) PRIMARY KEY,
    Nombre       NVARCHAR(100) NOT NULL,
    Descripcion  NVARCHAR(255) NULL
);
GO

CREATE TABLE Peliculas (
    IdPelicula   INT IDENTITY(1,1) PRIMARY KEY,
    Titulo       NVARCHAR(200) NOT NULL,
    Sinopsis     NVARCHAR(MAX) NULL,
    Clasificacion NVARCHAR(10) NULL,
    DuracionMin  INT NULL,
    Anio         INT NULL,
    IdGenero     INT NULL,
    PosterUrl    NVARCHAR(255) NULL,
    TrailerUrl   NVARCHAR(255) NULL
);
GO

ALTER TABLE Peliculas
ADD CONSTRAINT FK_Peliculas_Generos
FOREIGN KEY (IdGenero) REFERENCES Generos(IdGenero);
GO

CREATE TABLE Usuarios (
    IdUsuario      INT IDENTITY(1,1) PRIMARY KEY,
    Nombre         NVARCHAR(150) NOT NULL,
    Email          NVARCHAR(150) NOT NULL UNIQUE,
    PasswordHash   VARBINARY(64) NOT NULL,
    EsAdmin        BIT NOT NULL DEFAULT 0,
    FechaRegistro  DATETIME2(0) NOT NULL DEFAULT SYSDATETIME()
);
GO

CREATE TABLE Salas (
    IdSala      INT IDENTITY(1,1) PRIMARY KEY,
    NombreSala  NVARCHAR(100) NOT NULL,
    Filas       INT NOT NULL,
    Columnas    INT NOT NULL
);
GO

CREATE TABLE Asientos (
    IdAsiento  INT IDENTITY(1,1) PRIMARY KEY,
    IdSala     INT NOT NULL,
    Fila       NVARCHAR(5) NOT NULL,
    Numero     INT NOT NULL
);
GO

ALTER TABLE Asientos
ADD CONSTRAINT FK_Asientos_Salas
FOREIGN KEY (IdSala) REFERENCES Salas(IdSala);
GO

ALTER TABLE Asientos
ADD CONSTRAINT UQ_Asientos_Sala_Fila_Numero
UNIQUE (IdSala, Fila, Numero);
GO

CREATE TABLE Funciones (
    IdFuncion   INT IDENTITY(1,1) PRIMARY KEY,
    IdPelicula  INT NOT NULL,
    IdSala      INT NOT NULL,
    FechaHora   DATETIME2(0) NOT NULL,
    Precio      DECIMAL(10,2) NOT NULL,
    Idioma      NVARCHAR(10) NOT NULL,   -- SUB, DOB
    Formato     NVARCHAR(10) NOT NULL,   -- 2D, 3D, etc.
    Activa      BIT NOT NULL DEFAULT 1
);
GO

ALTER TABLE Funciones
ADD CONSTRAINT FK_Funciones_Peliculas
FOREIGN KEY (IdPelicula) REFERENCES Peliculas(IdPelicula);
GO

ALTER TABLE Funciones
ADD CONSTRAINT FK_Funciones_Salas
FOREIGN KEY (IdSala) REFERENCES Salas(IdSala);
GO

CREATE TABLE Pagos (
    IdPago        INT IDENTITY(1,1) PRIMARY KEY,
    IdUsuario     INT NOT NULL,
    MontoTotal    DECIMAL(10,2) NOT NULL,
    FechaPago     DATETIME2(0) NOT NULL DEFAULT SYSDATETIME(),
    MetodoPago    NVARCHAR(50) NOT NULL,
    Autorizacion  NVARCHAR(100) NULL
);
GO

ALTER TABLE Pagos
ADD CONSTRAINT FK_Pagos_Usuarios
FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario);
GO

CREATE TABLE Boletos (
    IdBoleto     INT IDENTITY(1,1) PRIMARY KEY,
    IdFuncion    INT NOT NULL,
    IdAsiento    INT NOT NULL,
    IdUsuario    INT NOT NULL,
    IdPago       INT NOT NULL,
    CodigoBoleto NVARCHAR(50) NOT NULL,
    Estado       NVARCHAR(20) NOT NULL DEFAULT N'COMPRADO',
    FechaCompra  DATETIME2(0) NOT NULL DEFAULT SYSDATETIME()
);
GO

ALTER TABLE Boletos
ADD CONSTRAINT FK_Boletos_Funciones
FOREIGN KEY (IdFuncion) REFERENCES Funciones(IdFuncion);
GO

ALTER TABLE Boletos
ADD CONSTRAINT FK_Boletos_Asientos
FOREIGN KEY (IdAsiento) REFERENCES Asientos(IdAsiento);
GO

ALTER TABLE Boletos
ADD CONSTRAINT FK_Boletos_Usuarios
FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario);
GO

ALTER TABLE Boletos
ADD CONSTRAINT FK_Boletos_Pagos
FOREIGN KEY (IdPago) REFERENCES Pagos(IdPago);
GO

ALTER TABLE Boletos
ADD CONSTRAINT UQ_Boletos_Funcion_Asiento
UNIQUE (IdFuncion, IdAsiento);
GO

CREATE TABLE TarjetasCredito (
    IdTarjeta        INT IDENTITY(1,1) PRIMARY KEY,
    IdUsuario        INT NOT NULL,
    Marca            NVARCHAR(20) NOT NULL,   -- VISA / MC / AMEX
    Ultimos4         CHAR(4) NOT NULL,
    NumeroEncriptado VARBINARY(256) NOT NULL,
    FechaRegistro    DATETIME2(0) NOT NULL DEFAULT SYSDATETIME()
);
GO

ALTER TABLE TarjetasCredito
ADD CONSTRAINT FK_Tarjetas_Usuarios
FOREIGN KEY (IdUsuario) REFERENCES Usuarios(IdUsuario);
GO

CREATE MASTER KEY ENCRYPTION BY PASSWORD = 'Clave_Maestra_Cine_2025!';
GO

CREATE CERTIFICATE CertTarjetasCine
WITH SUBJECT = 'Certificado para cifrado de tarjetas en Cine';
GO

CREATE SYMMETRIC KEY SK_TarjetasCine
WITH ALGORITHM = AES_256
ENCRYPTION BY CERTIFICATE CertTarjetasCine;
GO

CREATE PROCEDURE sp_LoginUsuario
    @Email    NVARCHAR(150),
    @Password NVARCHAR(200)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        IdUsuario,
        Nombre,
        EsAdmin
    FROM Usuarios
    WHERE Email = @Email
      AND PasswordHash = HASHBYTES('SHA2_256', @Password);
END;
GO

CREATE PROCEDURE sp_LoginAdmin
    @Email    NVARCHAR(150),
    @Password NVARCHAR(200)
AS
BEGIN
    SET NOCOUNT ON;

    SELECT 
        IdUsuario,
        Nombre,
        EsAdmin
    FROM Usuarios
    WHERE Email = @Email
      AND PasswordHash = HASHBYTES('SHA2_256', @Password)
      AND EsAdmin = 1;
END;
GO

CREATE PROCEDURE sp_InsertTarjetaCredito
    @IdUsuario     INT,
    @NumeroTarjeta NVARCHAR(30),
    @Marca         NVARCHAR(20)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @Ultimos4 CHAR(4) = RIGHT(@NumeroTarjeta, 4);
    DECLARE @Encriptado VARBINARY(256);

    OPEN SYMMETRIC KEY SK_TarjetasCine
        DECRYPTION BY CERTIFICATE CertTarjetasCine;

    SET @Encriptado = ENCRYPTBYKEY(KEY_GUID('SK_TarjetasCine'), @NumeroTarjeta);

    INSERT INTO TarjetasCredito (IdUsuario, Marca, Ultimos4, NumeroEncriptado)
    VALUES (@IdUsuario, @Marca, @Ultimos4, @Encriptado);

    CLOSE SYMMETRIC KEY SK_TarjetasCine;
END;
GO

INSERT INTO Generos (Nombre, Descripcion) VALUES
(N'Acción',          N'Películas con mucha adrenalina.'),
(N'Comedia',         N'Películas para reír.'),
(N'Terror',          N'Películas de miedo.'),
(N'Ciencia ficción', N'Historias futuristas.'),
(N'Animación',       N'Películas animadas.');
GO

INSERT INTO Peliculas
(Titulo, Sinopsis, Clasificacion, DuracionMin, Anio, IdGenero, PosterUrl, TrailerUrl)
VALUES
(N'Avengers: Endgame',
 N'Los Vengadores restantes deben revertir el chasquido de Thanos y salvar al universo.',
 N'B', 181, 2019, 1,
 N'https://m.media-amazon.com/images/I/81ExhpBEbHL._AC_SL1500_.jpg',
 N'https://www.youtube.com/watch?v=TcMBFSGVi1c'),
(N'Spider-Man: No Way Home',
 N'Peter Parker pide ayuda a Doctor Strange para que todos olviden que es Spider-Man, pero algo sale mal.',
 N'B', 148, 2021, 1,
 N'https://m.media-amazon.com/images/I/71niXI3lxlL._AC_SL1500_.jpg',
 N'https://www.youtube.com/watch?v=JfVOs4VSpmA'),
(N'Intensamente',
 N'Riley es una niña guiada por las emociones que viven en su mente: Alegría, Tristeza, Temor, Furia y Desagrado.',
 N'A', 95, 2015, 5,
 N'https://m.media-amazon.com/images/I/61YqzS4U5iL._AC_SL1000_.jpg',
 N'https://www.youtube.com/watch?v=yRUAzGQ3nSY'),
(N'El Conjuro',
 N'Una familia es aterrorizada por una presencia oscura; unos investigadores paranormales intentan ayudarlos.',
 N'B15', 112, 2013, 3,
 N'https://m.media-amazon.com/images/I/71m2qZP8U0L._AC_SL1181_.jpg',
 N'https://www.youtube.com/watch?v=k10ETZ41q5o'),
(N'Matrix',
 N'Neo descubre que el mundo en el que vive es una simulación controlada por máquinas.',
 N'B15', 136, 1999, 4,
 N'https://m.media-amazon.com/images/I/51vpnbwFHrL._AC_.jpg',
 N'https://www.youtube.com/watch?v=vKQi3bBA1y8');
GO

INSERT INTO Salas (NombreSala, Filas, Columnas)
VALUES (N'Sala 1', 4, 5);
GO

DECLARE @IdSala1 INT = SCOPE_IDENTITY();

INSERT INTO Asientos (IdSala, Fila, Numero) VALUES
(@IdSala1, N'A', 1), (@IdSala1, N'A', 2), (@IdSala1, N'A', 3), (@IdSala1, N'A', 4), (@IdSala1, N'A', 5),
(@IdSala1, N'B', 1), (@IdSala1, N'B', 2), (@IdSala1, N'B', 3), (@IdSala1, N'B', 4), (@IdSala1, N'B', 5),
(@IdSala1, N'C', 1), (@IdSala1, N'C', 2), (@IdSala1, N'C', 3), (@IdSala1, N'C', 4), (@IdSala1, N'C', 5),
(@IdSala1, N'D', 1), (@IdSala1, N'D', 2), (@IdSala1, N'D', 3), (@IdSala1, N'D', 4), (@IdSala1, N'D', 5);
GO

INSERT INTO Usuarios (Nombre, Email, PasswordHash, EsAdmin)
VALUES
(N'Cliente Demo', N'cliente@cine.com', HASHBYTES('SHA2_256', N'1234'), 0),
(N'Admin Cine',   N'admin@cine.com',   HASHBYTES('SHA2_256', N'1234'), 1);
GO


INSERT INTO Funciones (IdPelicula, IdSala, FechaHora, Precio, Idioma, Formato, Activa)
VALUES
(1, @IdSala1, '2025-11-19 13:00:00', 85.00, N'DOB', N'2D', 1),
(1, @IdSala1, '2025-11-19 16:30:00', 85.00, N'SUB', N'2D', 1),
(2, @IdSala1, '2025-11-19 14:00:00', 90.00, N'DOB', N'2D', 1),
(3, @IdSala1, '2025-11-19 12:00:00', 75.00, N'DOB', N'2D', 1),
(4, @IdSala1, '2025-11-19 19:00:00', 85.00, N'SUB', N'2D', 1),
(5, @IdSala1, '2025-11-19 16:00:00', 80.00, N'SUB', N'2D', 1);
GO

