DROP
DATABASE IF EXISTS juegopreguntas;
CREATE
DATABASE IF NOT EXISTS juegopreguntas;
USE
juegopreguntas;

-- Tabla usuarios
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo  VARCHAR(255) NOT NULL,
    año_nacimiento   INT          NOT NULL,
    sexo             VARCHAR(50)  NOT NULL,
    pais             VARCHAR(100) NOT NULL,
    ciudad           VARCHAR(100) NOT NULL,
    mail             VARCHAR(255) NOT NULL UNIQUE,
    usuario          VARCHAR(100) NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    foto_perfil      VARCHAR(255),
    activo           TINYINT(1) DEFAULT 0,
    token_validacion VARCHAR(255),
    latitud          DECIMAL(10, 6),
    longitud         DECIMAL(10, 6),
    rol              VARCHAR(50)  NOT NULL DEFAULT 'jugador',
    fecha_creacion   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla categoria
DROP TABLE IF EXISTS categoria;
CREATE TABLE categoria
(
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    color  VARCHAR(7)   NOT NULL -- formato #RRGGBB
);

-- Tabla dificultad
DROP TABLE IF EXISTS dificultad;
CREATE TABLE dificultad
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(100) NOT NULL
);

-- Tabla pregunta
DROP TABLE IF EXISTS pregunta;
CREATE TABLE pregunta
(
    id                             INT AUTO_INCREMENT PRIMARY KEY,
    texto                          TEXT NOT NULL,
    id_categoria                   INT  NOT NULL,
    id_creador                     INT  NOT NULL,
    estado                         ENUM('activa', 'inactiva', 'pendiente') DEFAULT 'pendiente',
    veces_mostrada                 INT           DEFAULT 0,
    veces_respondida_correctamente INT           DEFAULT 0,
    id_dificultad                  INT  NOT NULL DEFAULT 2, -- ← dificultad media por defecto
    fecha_creacion     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categoria (id),
    FOREIGN KEY (id_creador) REFERENCES usuarios (id),
    FOREIGN KEY (id_dificultad) REFERENCES dificultad (id)
);

-- Tabla respuesta
DROP TABLE IF EXISTS respuesta;
CREATE TABLE respuesta
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta INT          NOT NULL,
    texto       VARCHAR(255) NOT NULL,
    es_correcta BOOLEAN      NOT NULL,
    FOREIGN KEY (id_pregunta) REFERENCES pregunta (id) ON DELETE CASCADE
);

-- Tabla partida (simplificada, sin tipo)
DROP TABLE IF EXISTS partida;
CREATE TABLE partida
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha      DATETIME DEFAULT CURRENT_TIMESTAMP,
    puntaje    INT      DEFAULT 0,
    finalizada BOOLEAN  DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
);

-- Tabla partida_pregunta (MODIFICADA)
DROP TABLE IF EXISTS partida_pregunta;
CREATE TABLE partida_pregunta
(
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    id_partida               INT NOT NULL,
    id_pregunta              INT NOT NULL,
    respondida_correctamente BOOLEAN,
    estado_pregunta          ENUM('mostrada', 'respondida', 'actual') DEFAULT NULL, -- Nuevo estado
    orden_pregunta           INT,
    FOREIGN KEY (id_partida) REFERENCES partida (id),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta (id),
    UNIQUE KEY (id_partida, id_pregunta)                                            -- AGREGAR ESTA LÍNEA
);

-- Tabla reporte_pregunta
DROP TABLE IF EXISTS reporte_pregunta;
CREATE TABLE reporte_pregunta
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_pregunta   INT NOT NULL,
    id_usuario    INT NOT NULL,
    motivo        TEXT,
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado        ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
    FOREIGN KEY (id_pregunta) REFERENCES pregunta (id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id)
);

-- Tabla sugerencia_pregunta
DROP TABLE IF EXISTS sugerencia_pregunta;
CREATE TABLE sugerencia_pregunta
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT  NOT NULL,
    texto        TEXT NOT NULL,
    id_categoria INT  NOT NULL,
    estado ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_sugerencia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
    FOREIGN KEY (id_categoria) REFERENCES categoria (id)
);

-- Tabla sugerencia_respuesta
DROP TABLE IF EXISTS sugerencia_respuesta;
CREATE TABLE sugerencia_respuesta
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    id_sugerencia INT  NOT NULL,
    texto         TEXT NOT NULL,
    es_correcta   BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_sugerencia) REFERENCES sugerencia_pregunta (id)
);


-- Tabla resumen_partida
DROP TABLE IF EXISTS resumen_partida;
CREATE TABLE resumen_partida
(
    id_resumen                INT AUTO_INCREMENT PRIMARY KEY,
    id_partida                INT      NOT NULL UNIQUE, -- Una partida solo tiene un resumen
    id_usuario                INT      NOT NULL,
    cantidad_correctas        INT      NOT NULL DEFAULT 0,
    cantidad_intentos         INT      NOT NULL DEFAULT 0,
    id_categoria              INT               DEFAULT NULL,
    puntaje                   INT      NOT NULL DEFAULT 0,
    tiempo_promedio_respuesta FLOAT             DEFAULT NULL,
    fecha_partida             DATETIME NOT NULL,
    FOREIGN KEY (id_partida) REFERENCES partida (id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
    FOREIGN KEY (id_categoria) REFERENCES categoria (id)
);

-- Tabla pregunta_usuario
DROP TABLE IF EXISTS pregunta_usuario;
CREATE TABLE pregunta_usuario
(
    id           INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_usuario   INT(11) NOT NULL,
    id_pregunta  INT(11) NOT NULL,
    es_correcta  BOOLEAN DEFAULT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta (id),
    UNIQUE KEY usuario_pregunta_unica (id_usuario, id_pregunta)
);

CREATE TABLE historial_respuestas (
     id           INT AUTO_INCREMENT PRIMARY KEY,
     id_usuario   INT NOT NULL,
     id_pregunta  INT NOT NULL,
     es_correcta  BOOLEAN NOT NULL,
     fecha        DATETIME DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
     FOREIGN KEY (id_pregunta) REFERENCES pregunta (id)
);



-- INSERTS POR ORDEN
-- TODO: Todas las tablas tienen que empezar con id 1 SI O SI, sino no anda
-- Usar esta query en caso de que no funque: ALTER TABLE nombreTabla AUTO_INCREMENT = 1;
-- Agregar primero por lo menos 10 INSERTS de Usuarios sin incluir el de ustedes
-- Usar la base de datos
USE
juegopreguntas;

-- USUARIOS
INSERT INTO usuarios (id, nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password, rol, fecha_creacion)
VALUES
    (1, 'Juan Pérez', 1990, 'Masculino', 'Argentina', 'Buenos Aires', 'juan@gmail.com', 'juanperez', '123456', 'jugador', '2023-11-15 10:00:00'),
    (2, 'Ana González', 1995, 'Femenino', 'México', 'Ciudad de México', 'ana@gmail.com', 'anagonzalez', '123456', 'jugador', '2023-12-20 11:30:00'),
    (3, 'Carlos López', 1988, 'Masculino', 'España', 'Madrid', 'carlos@gmail.com', 'carloslopez', '123456', 'jugador', '2024-01-10 12:45:00'),
    (4, 'Lucía Martínez', 2000, 'Femenino', 'Colombia', 'Bogotá', 'lucia@gmail.com', 'luciamartinez', '123456', 'jugador', '2024-02-05 14:00:00'),
    (5, 'Pedro Sánchez', 1992, 'Masculino', 'Chile', 'Santiago', 'pedro@gmail.com', 'pedrosanchez', '123456', 'jugador', '2024-03-25 16:20:00'),
    (6, 'Maria Silva', 2012, 'Femenino', 'Brasil', 'São Paulo', 'maria@example.com', 'mariasilva', 'pass123', 'jugador', '2024-09-22 14:05:00'),
    (7, 'Kenji Tanaka', 2018, 'Masculino', 'Japón', 'Tokio', 'kenji@example.com', 'kenjitanaka', 'pass123', 'jugador', '2024-10-30 21:00:00'),
    (8, 'Fatima Al-Fassi', 1996, 'Femenino', 'Marruecos', 'Rabat', 'fatima@example.com', 'fatimaalfassi', 'pass123', 'jugador', '2024-11-18 13:45:00');

-- CATEGORÍAS
INSERT INTO categoria (id, nombre, color)
VALUES
    (1, 'Geografía', '#3498db'),    -- Azul
    (2, 'Matemáticas', '#e74c3c'),  -- Rojo
    (3, 'Ciencia', '#2ecc71'),      -- Verde
    (4, 'Historia', '#f1c40f'),     -- Amarillo
    (5, 'Deportes', '#9b59b6');

-- DIFICULTADES
INSERT INTO dificultad (id, descripcion) VALUES
                                       (1, 'Fácil'),
                                       (2, 'Media'),
                                       (3, 'Difícil');

-- PREGUNTAS (3 por categoría)
INSERT INTO pregunta (id, texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    (1, '¿Cuál es la capital de Francia?', 1, 1, 'activa', NOW()),
    (2, '¿En qué continente está Egipto?', 1, 1, 'activa', NOW()),
    (3, '¿Qué país tiene más fronteras con otros países?', 1, 1, 'activa', NOW()),
    (4, '¿Cuánto es 5 + 3?', 2, 1, 'activa', NOW()),
    (5, '¿Cuál es la raíz cuadrada de 81?', 2, 1, 'activa', NOW()),
    (6, '¿Cuántos lados tiene un hexágono?', 2, 1, 'activa', NOW()),
    (7, '¿Cuál es el símbolo químico del oro?', 3, 1, 'activa', NOW()),
    (8, '¿Qué planeta es conocido como el "Planeta Rojo"?', 3, 1, 'activa', NOW()),
    (9, '¿Cuál es la fórmula química del agua?', 3, 1, 'activa', NOW()),
    (10, '¿Quién fue el primer presidente de los Estados Unidos?', 4, 1, 'activa', NOW()),
    (11, '¿En qué año cayó el Muro de Berlín?', 4, 1, 'activa', NOW()),
    (12, '¿Qué civilización construyó Machu Picchu?', 4, 1, 'activa', NOW()),
    (13, '¿Cada cuántos años se celebran los Juegos Olímpicos?', 5, 1, 'activa', NOW()),
    (14, '¿Qué país ganó la primera Copa Mundial de Fútbol en 1930?', 5, 1, 'activa', NOW()),
    (15, '¿Qué deporte practica Serena Williams?', 5, 1, 'activa', NOW());

-- RESPUESTAS (4 por pregunta, 60 en total)
INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
-- P1
(1, 'París', 1), (1, 'Berlín', 0), (1, 'Londres', 0), (1, 'Roma', 0),
-- P2
(2, 'África', 1), (2, 'Asia', 0), (2, 'Europa', 0), (2, 'Oceanía', 0),
-- P3
(3, 'China', 0), (3, 'Brasil', 0), (3, 'Rusia', 1), (3, 'India', 0),
-- P4
(4, '7', 0), (4, '8', 1), (4, '6', 0), (4, '9', 0),
-- P5
(5, '9', 1), (5, '8', 0), (5, '7', 0), (5, '10', 0),
-- P6
(6, '5', 0), (6, '6', 1), (6, '7', 0), (6, '8', 0),
-- P7
(7, 'Ag', 0), (7, 'Au', 1), (7, 'Fe', 0), (7, 'Cu', 0),
-- P8
(8, 'Marte', 1), (8, 'Venus', 0), (8, 'Júpiter', 0), (8, 'Saturno', 0),
-- P9
(9, 'H2O', 1), (9, 'CO2', 0), (9, 'NaCl', 0), (9, 'O2', 0),
-- P10
(10, 'George Washington', 1), (10, 'Thomas Jefferson', 0), (10, 'Abraham Lincoln', 0), (10, 'John Adams', 0),
-- P11
(11, '1985', 0), (11, '1987', 0), (11, '1989', 1), (11, '1991', 0),
-- P12
(12, 'Azteca', 0), (12, 'Maya', 0), (12, 'Inca', 1), (12, 'Egipcia', 0),
-- P13
(13, 'Cada 2', 0), (13, 'Cada 3', 0), (13, 'Cada 4', 1), (13, 'Cada 5', 0),
-- P14
(14, 'Argentina', 0), (14, 'Brasil', 0), (14, 'Uruguay', 1), (14, 'Italia', 0),
-- P15
(15, 'Baloncesto', 0), (15, 'Natación', 0), (15, 'Atletismo', 0), (15, 'Tenis', 1);

-- SUGERENCIAS
INSERT INTO sugerencia_pregunta (id, id_usuario, texto, id_categoria, estado, fecha_sugerencia)
VALUES
    (1, 1, '¿Cuánto es 5 + 3?', 2, 'pendiente', '2025-01-10 15:00:00'),
    (2, 2, '¿Cuál es la capital de España?', 1, 'pendiente', '2025-02-12 16:00:00'),
    (3, 3, '¿Cuántos planetas hay en el sistema solar?', 3, 'pendiente', '2025-03-14 17:00:00'),
    (4, 4, '¿Quién escribió "Romeo y Julieta"?', 4, 'pendiente', '2025-04-16 18:00:00'),
    (5, 5, '¿Qué deporte se juega con una pelota de tenis?', 5, 'pendiente', '2025-05-18 19:00:00');

-- RESPUESTAS DE LAS SUGERENCIAS
INSERT INTO sugerencia_respuesta (id_sugerencia, texto, es_correcta) VALUES
                                                                         (1, '6', 0), (1, '7', 0), (1, '8', 1), (1, '9', 0),
                                                                         (2, 'Barcelona', 0), (2, 'Madrid', 1), (2, 'Valencia', 0), (2, 'Sevilla', 0),
                                                                         (3, '7', 0), (3, '8', 1), (3, '9', 0), (3, '10', 0),
                                                                         (4, 'Miguel de Cervantes', 0), (4, 'William Shakespeare', 1), (4, 'Borges', 0), (4, 'Vargas Llosa', 0),
                                                                         (5, 'Fútbol', 0), (5, 'Baloncesto', 0), (5, 'Tenis', 1), (5, 'Voleibol', 0);

-- PARTIDAS PARA GRÁFICOS
INSERT INTO partida (id, id_usuario, fecha, puntaje, finalizada) VALUES
                                                                     (1, 1, '2025-06-01 10:00:00', 300, 1),
                                                                     (2, 2, '2025-06-02 11:00:00', 450, 1),
                                                                     (3, 3, '2025-06-03 12:00:00', 520, 1),
                                                                     (4, 4, '2025-06-04 13:00:00', 100, 1),
                                                                     (5, 5, '2025-06-05 14:00:00', 200, 1),
                                                                     (6, 6, '2025-06-06 15:00:00', 350, 1),
                                                                     (7, 7, '2025-06-07 16:00:00', 400, 1),
                                                                     (8, 8, '2025-06-08 17:00:00', 270, 1);

-- RESUMENES DE LAS PARTIDAS
INSERT INTO resumen_partida (id_partida, id_usuario, cantidad_correctas, cantidad_intentos, puntaje, fecha_partida)
SELECT id, id_usuario, (puntaje / 100), 5, puntaje, fecha
FROM partida;
