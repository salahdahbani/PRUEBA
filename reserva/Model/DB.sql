CREATE DATABASE IF NOT EXISTS reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservas;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS reserva_evento;
DROP TABLE IF EXISTS reserva;

CREATE TABLE reserva (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_huesped     VARCHAR(120) NOT NULL,
    email_huesped      VARCHAR(150) NOT NULL,
    nombre_alojamiento VARCHAR(150) NOT NULL,
    fecha_entrada      DATE NOT NULL,
    fecha_salida       DATE NOT NULL,
    estado             ENUM('pendiente','confirmada','cancelada') NOT NULL DEFAULT 'pendiente',
    importe            DECIMAL(10,2) NOT NULL,
    notas              TEXT NULL,
    fecha_creacion     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_entrada, fecha_salida),
    INDEX idx_huesped (nombre_huesped),
    CONSTRAINT chk_fechas CHECK (fecha_salida > fecha_entrada),
    CONSTRAINT chk_importe CHECK (importe >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reserva_evento (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserva_id     INT UNSIGNED NOT NULL,
    tipo           VARCHAR(30)  NOT NULL,
    descripcion    VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reserva (reserva_id),
    CONSTRAINT fk_evento_reserva FOREIGN KEY (reserva_id)
        REFERENCES reserva (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reserva
    (id, nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion)
VALUES
    (1,  'Lucía Fernández',  'lucia.fernandez@example.com', 'Hotel Alhambra Centro', '2026-11-05', '2026-11-08', 'confirmada', 420.00, 'Habitación con vistas',             '2026-10-01 10:15:00'),
    (2,  'Marcos Ortega',    'marcos.ortega@example.com',   'Apartamentos Mar Azul', '2026-11-12', '2026-11-19', 'pendiente',  735.50, NULL,                                '2026-10-03 18:40:00'),
    (3,  'Elena Ruiz',       'elena.ruiz@example.com',      'Casa Rural El Olivar',  '2026-12-26', '2026-12-30', 'confirmada', 560.00, 'Llegada tarde, después de las 22h', '2026-10-05 09:05:00'),
    (4,  'Javier Molina',    'javier.molina@example.com',   'Hotel Alhambra Centro', '2026-10-20', '2026-10-22', 'cancelada',  280.00, 'Cancelada por el cliente',          '2026-09-28 12:00:00'),
    (5,  'Carmen Soto',      'carmen.soto@example.com',     'Hostal La Plaza',       '2026-11-01', '2026-11-03', 'pendiente',  130.00, NULL,                                '2026-10-08 16:20:00'),
    (6,  'Pablo Navarro',    'pablo.navarro@example.com',   'Apartamentos Mar Azul', '2027-01-10', '2027-01-17', 'confirmada', 890.00, 'Pide cuna',                         '2026-10-09 11:30:00'),
    (7,  'Sofía Gil',        'sofia.gil@example.com',       'Casa Rural El Olivar',  '2026-11-20', '2026-11-22', 'cancelada',  310.00, NULL,                                '2026-10-10 20:10:00'),
    (8,  'Andrés Castillo',  'andres.castillo@example.com', 'Hotel Gran Vía',        '2026-12-05', '2026-12-08', 'pendiente',  465.00, 'Factura a nombre de empresa',       '2026-10-12 08:45:00'),
    (9,  'Marta Delgado',    'marta.delgado@example.com',   'Hostal La Plaza',       '2026-12-15', '2026-12-18', 'confirmada', 195.00, NULL,                                '2026-10-13 13:00:00'),
    (10, 'Raúl Herrera',     'raul.herrera@example.com',    'Hotel Gran Vía',        '2027-02-14', '2027-02-16', 'pendiente',  350.00, 'Escapada de San Valentín',          '2026-10-14 19:25:00');

INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion) VALUES
    (1,  'creada',     'Reserva creada',     '2026-10-01 10:15:00'),
    (1,  'confirmada', 'Reserva confirmada', '2026-10-01 11:00:00'),
    (2,  'creada',     'Reserva creada',     '2026-10-03 18:40:00'),
    (3,  'creada',     'Reserva creada',     '2026-10-05 09:05:00'),
    (3,  'confirmada', 'Reserva confirmada', '2026-10-05 12:30:00'),
    (4,  'creada',     'Reserva creada',     '2026-09-28 12:00:00'),
    (4,  'cancelada',  'Reserva cancelada',  '2026-10-02 09:10:00'),
    (5,  'creada',     'Reserva creada',     '2026-10-08 16:20:00'),
    (6,  'creada',     'Reserva creada',     '2026-10-09 11:30:00'),
    (6,  'confirmada', 'Reserva confirmada', '2026-10-09 15:45:00'),
    (7,  'creada',     'Reserva creada',     '2026-10-10 20:10:00'),
    (7,  'cancelada',  'Reserva cancelada',  '2026-10-11 08:00:00'),
    (8,  'creada',     'Reserva creada',     '2026-10-12 08:45:00'),
    (9,  'creada',     'Reserva creada',     '2026-10-13 13:00:00'),
    (9,  'confirmada', 'Reserva confirmada', '2026-10-13 17:20:00'),
    (10, 'creada',     'Reserva creada',     '2026-10-14 19:25:00');