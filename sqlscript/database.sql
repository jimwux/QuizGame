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
    id_respuesta             INT,
    respondida_correctamente BOOLEAN,
    estado_pregunta          ENUM('mostrada', 'respondida', 'actual') DEFAULT NULL, -- Nuevo estado
    orden_pregunta           INT,
    FOREIGN KEY (id_partida) REFERENCES partida (id),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta (id),
    FOREIGN KEY (id_respuesta) REFERENCES respuesta (id)
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
    id_respuesta INT(11), -- Puede ser NULL si la respuesta no fue registrada o fue timeout
    es_correcta  BOOLEAN NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
    FOREIGN KEY (id_pregunta) REFERENCES pregunta (id),
    FOREIGN KEY (id_respuesta) REFERENCES respuesta (id),
    UNIQUE KEY usuario_pregunta_unica (id_usuario, id_pregunta)
);

CREATE TABLE historial_respuestas (
                                      id           INT AUTO_INCREMENT PRIMARY KEY,
                                      id_usuario   INT NOT NULL,
                                      id_pregunta  INT NOT NULL,
                                      id_respuesta INT,
                                      es_correcta  BOOLEAN NOT NULL,
                                      fecha        DATETIME DEFAULT CURRENT_TIMESTAMP,
                                      FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
                                      FOREIGN KEY (id_pregunta) REFERENCES pregunta (id),
                                      FOREIGN KEY (id_respuesta) REFERENCES respuesta (id)
);



-- INSERTS POR ORDEN
-- TODO: Todas las tablas tienen que empezar con id 1 SI O SI, sino no anda
-- Usar esta query en caso de que no funque: ALTER TABLE nombreTabla AUTO_INCREMENT = 1;
-- Agregar primero por lo menos 10 INSERTS de Usuarios sin incluir el de ustedes
-- Usar la base de datos
USE
juegopreguntas;

-- Insertar usuarios (con fechas actualizadas)
INSERT INTO usuarios (nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password, rol, fecha_creacion)
VALUES ('Juan Pérez', 1990, 'Masculino', 'Argentina', 'Buenos Aires', 'juan@gmail.com', 'juanperez', '123456',
        'jugador', '2023-11-15 10:00:00'),
       ('Ana González', 1995, 'Femenino', 'México', 'Ciudad de México', 'ana@gmail.com', 'anagonzalez', '123456',
        'jugador', '2023-12-20 11:30:00'),
       ('Carlos López', 1988, 'Masculino', 'España', 'Madrid', 'carlos@gmail.com', 'carloslopez', '123456', 'jugador'
       , '2024-01-10 12:45:00'),
       ('Lucía Martínez', 2000, 'Femenino', 'Colombia', 'Bogotá', 'lucia@gmail.com', 'luciamartinez', '123456',
        'jugador', '2024-02-05 14:00:00'),
       ('Pedro Sánchez', 1992, 'Masculino', 'Chile', 'Santiago', 'pedro@gmail.com', 'pedrosanchez', '123456',
        'jugador', '2024-03-25 16:20:00');

-- Insertar categorías
INSERT INTO categoria (nombre, color)
VALUES ('Geografía', '#3498db'),
       ('Matemáticas', '#e74c3c'),
       ('Ciencia', '#2ecc71'),
       ('Historia', '#f1c40f'),
       ('Deportes', '#9b59b6');

-- Insertar dificultades
INSERT INTO dificultad (descripcion)
VALUES ('Fácil'),
       ('Medio'),
       ('Difícil');

-- Insertar preguntas y respuestas (con fechas actualizadas)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES ('¿Cuántos continentes hay en el mundo?', 1, 1, 'activa', '2024-04-01 18:00:00'),
       ('¿Cuánto es 2 + 2?', 2, 2, 'activa', '2024-04-02 19:10:00'),
       ('¿De qué color es el cielo en un día despejado?', 3, 3, 'activa', '2024-05-03 20:20:00'),
       ('¿En qué año comenzó la Segunda Guerra Mundial?', 4, 4, 'activa', '2024-05-04 21:30:00'),
       ('¿Cuántos jugadores hay en un equipo de fútbol?', 5, 5, 'activa', '2024-06-05 22:40:00');

-- Insertar respuestas correctas e incorrectas (Cada pregunta tiene 4 opciones)
INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES (1, '5', 0),
       (1, '6', 0),
       (1, '7', 0),
       (1, '7', 1),
       (2, '3', 0),
       (2, '4', 1),
       (2, '5', 0),
       (2, '6', 0),
       (3, 'Rojo', 0),
       (3, 'Azul', 1),
       (3, 'Verde', 0),
       (3, 'Amarillo', 0),
       (4, '1929', 0),
       (4, '1935', 0),
       (4, '1939', 1),
       (4, '1942', 0),
       (5, '9', 0),
       (5, '10', 0),
       (5, '11', 1),
       (5, '12', 0);

-- Insertar sugerencias de preguntas con estado
INSERT INTO sugerencia_pregunta (id_usuario, texto, id_categoria, estado, fecha_sugerencia) VALUES
                                                                                                (1, '¿Cuánto es 5 + 3?', 2, 'pendiente', '2025-01-10 15:00:00'),
                                                                                                (2, '¿Cuál es la capital de España?', 1, 'pendiente', '2025-02-12 16:00:00'),
                                                                                                (3, '¿Cuántos planetas hay en el sistema solar?', 3, 'pendiente', '2025-03-14 17:00:00'),
                                                                                                (4, '¿Quién escribió "Romeo y Julieta"?', 4, 'pendiente', '2025-04-16 18:00:00'),
                                                                                                (5, '¿Qué deporte se juega con una pelota de tenis?', 5, 'pendiente', '2025-05-18 19:00:00');

-- Insertar respuestas para cada pregunta sugerida
INSERT INTO sugerencia_respuesta (id_sugerencia, texto, es_correcta) VALUES
-- Respuestas para "¿Cuánto es 5 + 3?"
(1, '6', 0), (1, '7', 0), (1, '8', 1), (1, '9', 0),
-- Respuestas para "¿Cuál es la capital de España?"
(2, 'Barcelona', 0), (2, 'Madrid', 1), (2, 'Valencia', 0), (2, 'Sevilla', 0),
-- Respuestas para "¿Cuántos planetas hay en el sistema solar?"
(3, '7', 0), (3, '8', 1), (3, '9', 0), (3, '10', 0),
-- Respuestas para "¿Quién escribió 'Romeo y Julieta'?"
(4, 'Miguel de Cervantes', 0), (4, 'William Shakespeare', 1), (4, 'Jorge Luis Borges', 0), (4, 'Mario Vargas Llosa', 0),
-- Respuestas para "¿Qué deporte se juega con una pelota de tenis?"
(5, 'Fútbol', 0), (5, 'Baloncesto', 0), (5, 'Tenis', 1), (5, 'Voleibol', 0);

-- ---------------------------------------------------------------- --
-- ------------- NUEVOS DATOS GENERADOS A CONTINUACIÓN ------------ --
-- ---------------------------------------------------------------- --

-- MÁS USUARIOS
INSERT INTO usuarios (nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password, rol, fecha_creacion)
VALUES
    ('David Schmidt', 1985, 'Masculino', 'Alemania', 'Berlín', 'david@example.com', 'davidschmidt', 'pass123', 'jugador', '2024-08-11 09:15:00'),
    ('Maria Silva', 2012, 'Femenino', 'Brasil', 'São Paulo', 'maria@example.com', 'mariasilva', 'pass123', 'jugador', '2024-09-22 14:05:00'),
    ('Kenji Tanaka', 2018, 'Masculino', 'Japón', 'Tokio', 'kenji@example.com', 'kenjitanaka', 'pass123', 'jugador', '2024-10-30 21:00:00'),
    ('Fatima Al-Fassi', 1996, 'Femenino', 'Marruecos', 'Rabat', 'fatima@example.com', 'fatimaalfassi', 'pass123', 'jugador', '2024-11-18 13:45:00'),
    ('Liam Murphy', 1999, 'Masculino', 'Irlanda', 'Dublin', 'liam@example.com', 'liammurphy', 'pass123', 'jugador', '2025-01-07 11:20:00'),
    ('Isabella Costa', 1991, 'Femenino', 'Argentina', 'Córdoba', 'isabella@example.com', 'isabellacosta', 'pass123', 'jugador', '2025-02-19 18:55:00'),
    ('Javier Rodriguez', 1989, 'Masculino', 'México', 'Guadalajara', 'javier@example.com', 'javierrodriguez', 'pass123', 'jugador', '2025-03-23 12:10:00'),
    ('Elena Petrova', 2017, 'Femenino', 'Rusia', 'Moscú', 'elena@example.com', 'elenapetrova', 'pass123', 'jugador', '2025-04-14 08:30:00'),
    ('Ahmed Khan', 1994, 'Masculino', 'India', 'Mumbai', 'ahmed@example.com', 'ahmedkhan', 'pass123', 'jugador', '2025-05-09 17:00:00'),
    ('Sofia Rossi', 1998, 'Femenino', 'Italia', 'Roma', 'sofia@example.com', 'sofiarossi', 'pass123', 'jugador', '2025-06-01 19:40:00');


-- MÁS PARTIDAS (con fechas actualizadas y más recientes)
    INSERT INTO partida (id_usuario, fecha, puntaje, finalizada)
VALUES
    (1, '2024-11-10 15:30:00', 350, 1),
    (2, '2024-12-12 18:00:00', 520, 1),
    (3, '2025-01-15 20:15:00', 180, 1),
    (4, '2025-02-01 11:00:00', 600, 1),
    (5, '2025-03-03 16:45:00', 410, 1),
    (6, '2025-04-05 19:00:00', 250, 1),
    (7, '2025-05-10 22:30:00', 730, 1),
    (8, '2025-05-20 13:00:00', 330, 1),
    (9, '2025-05-28 17:20:00', 480, 1),
    (10, '2025-06-05 21:00:00', 550, 1),
    (1, '2025-06-15 10:00:00', 450, 1),
    (2, '2025-06-18 14:30:00', 290, 1),
    (11, '2025-06-20 20:53:31', 0, 1),
    (11, '2025-06-21 20:53:36', 408, 1),
    (12, '2025-06-22 18:00:00', 680, 1);


-- MÁS PREGUNTAS Y RESPUESTAS (con fechas actualizadas)
-- Geografía (Categoría 1)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cuál es el río más largo del mundo?', 1, 12, 'activa', '2024-09-10 10:00:00'),
    ('¿En qué país se encuentra la Torre Eiffel?', 1, 12, 'activa', '2024-10-11 11:00:00'),
    ('¿Cuál es la capital de Australia?', 1, 12, 'activa', '2024-11-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (6, 'Nilo', 0), (6, 'Amazonas', 1), (6, 'Misisipi', 0), (6, 'Yangtsé', 0),
    (7, 'Italia', 0), (7, 'España', 0), (7, 'Alemania', 0), (7, 'Francia', 1),
    (8, 'Sídney', 0), (8, 'Melbourne', 0), (8, 'Canberra', 1), (8, 'Perth', 0);

-- Matemáticas (Categoría 2)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cuánto es 12 x 12?', 2, 12, 'activa', '2024-12-10 10:00:00'),
    ('¿Cuál es la raíz cuadrada de 81?', 2, 12, 'activa', '2025-01-11 11:00:00'),
    ('¿Cuántos lados tiene un hexágono?', 2, 12, 'activa', '2025-02-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (9, '140', 0), (9, '144', 1), (9, '124', 0), (9, '154', 0),
    (10, '8', 0), (10, '9', 1), (10, '10', 0), (10, '7', 0),
    (11, '5', 0), (11, '6', 1), (11, '7', 0), (11, '8', 0);

-- Ciencia (Categoría 3)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cuál es el símbolo químico del oro?', 3, 12, 'activa', '2025-03-10 10:00:00'),
    ('¿Qué planeta es conocido como el Planeta Rojo?', 3, 12, 'activa', '2025-03-11 11:00:00'),
    ('¿Cuál es la fórmula química del agua?', 3, 12, 'activa', '2025-03-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (12, 'Ag', 0), (12, 'Au', 1), (12, 'O', 0), (12, 'H', 0),
    (13, 'Venus', 0), (13, 'Júpiter', 0), (13, 'Marte', 1), (13, 'Saturno', 0),
    (14, 'CO2', 0), (14, 'O2', 0), (14, 'H2O', 1), (14, 'NaCl', 0);

-- Historia (Categoría 4)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Quién fue el primer presidente de los Estados Unidos?', 4, 12, 'activa', '2025-04-10 10:00:00'),
    ('¿En qué año cayó el Muro de Berlín?', 4, 12, 'activa', '2025-04-11 11:00:00'),
    ('¿Qué civilización construyó Machu Picchu?', 4, 12, 'activa', '2025-04-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (15, 'Abraham Lincoln', 0), (15, 'Thomas Jefferson', 0), (15, 'George Washington', 1), (15, 'John Adams', 0),
    (16, '1985', 0), (16, '1989', 1), (16, '1991', 0), (16, '1993', 0),
    (17, 'Azteca', 0), (17, 'Maya', 0), (17, 'Inca', 1), (17, 'Egipcia', 0);

-- Deportes (Categoría 5)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cada cuántos años se celebran los Juegos Olímpicos de Verano?', 5, 12, 'activa', '2025-05-10 10:00:00'),
    ('¿Qué país ganó la primera Copa Mundial de Fútbol en 1930?', 5, 12, 'activa', '2025-05-11 11:00:00'),
    ('¿Quién es considerado el mejor jugador de baloncesto de todos los tiempos?', 5, 12, 'activa', '2025-05-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (18, '2', 0), (18, '3', 0), (18, '4', 1), (18, '5', 0),
    (19, 'Argentina', 0), (19, 'Brasil', 0), (19, 'Uruguay', 1), (19, 'Italia', 0),
    (20, 'LeBron James', 0), (20, 'Kareem Abdul-Jabbar', 0), (20, 'Michael Jordan', 1), (20, 'Magic Johnson', 0);

-- Insertar un resumen de partida para las partidas existentes
INSERT INTO resumen_partida (id_partida, id_usuario, cantidad_correctas, cantidad_intentos, puntaje, fecha_partida)
SELECT p.id, p.id_usuario,
       (p.puntaje / 100) AS cantidad_correctas, -- Estimación
       5 AS cantidad_intentos, -- Estimación
       p.puntaje, p.fecha
FROM partida p
WHERE NOT EXISTS (SELECT 1 FROM resumen_partida rp WHERE rp.id_partida = p.id);

-- MÁS PARTIDAS (con fechas actualizadas y más recientes)
INSERT INTO partida (id_usuario, fecha, puntaje, finalizada)
VALUES
    (1, '2024-11-10 15:30:00', 350, 1),
    (2, '2024-12-12 18:00:00', 520, 1),
    (3, '2025-01-15 20:15:00', 180, 1),
    (4, '2025-02-01 11:00:00', 600, 1),
    (5, '2025-03-03 16:45:00', 410, 1),
    (6, '2025-04-05 19:00:00', 250, 1),
    (7, '2025-05-10 22:30:00', 730, 1),
    (8, '2025-05-20 13:00:00', 330, 1),
    (9, '2025-05-28 17:20:00', 480, 1),
    (10, '2025-06-05 21:00:00', 550, 1),
    (1, '2025-06-15 10:00:00', 450, 1),
    (2, '2025-06-18 14:30:00', 290, 1),
    (11, '2025-06-20 20:53:31', 0, 1),
    (11, '2025-06-21 20:53:36', 408, 1),
    (12, '2025-06-22 18:00:00', 680, 1);


-- MÁS PREGUNTAS Y RESPUESTAS (con fechas actualizadas)
-- Geografía (Categoría 1)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cuál es el río más largo del mundo?', 1, 12, 'activa', '2024-09-10 10:00:00'),
    ('¿En qué país se encuentra la Torre Eiffel?', 1, 12, 'activa', '2024-10-11 11:00:00'),
    ('¿Cuál es la capital de Australia?', 1, 12, 'activa', '2024-11-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (6, 'Nilo', 0), (6, 'Amazonas', 1), (6, 'Misisipi', 0), (6, 'Yangtsé', 0),
    (7, 'Italia', 0), (7, 'España', 0), (7, 'Alemania', 0), (7, 'Francia', 1),
    (8, 'Sídney', 0), (8, 'Melbourne', 0), (8, 'Canberra', 1), (8, 'Perth', 0);

-- Matemáticas (Categoría 2)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cuánto es 12 x 12?', 2, 12, 'activa', '2024-12-10 10:00:00'),
    ('¿Cuál es la raíz cuadrada de 81?', 2, 12, 'activa', '2025-01-11 11:00:00'),
    ('¿Cuántos lados tiene un hexágono?', 2, 12, 'activa', '2025-02-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (9, '140', 0), (9, '144', 1), (9, '124', 0), (9, '154', 0),
    (10, '8', 0), (10, '9', 1), (10, '10', 0), (10, '7', 0),
    (11, '5', 0), (11, '6', 1), (11, '7', 0), (11, '8', 0);

-- Ciencia (Categoría 3)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cuál es el símbolo químico del oro?', 3, 12, 'activa', '2025-03-10 10:00:00'),
    ('¿Qué planeta es conocido como el Planeta Rojo?', 3, 12, 'activa', '2025-03-11 11:00:00'),
    ('¿Cuál es la fórmula química del agua?', 3, 12, 'activa', '2025-03-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (12, 'Ag', 0), (12, 'Au', 1), (12, 'O', 0), (12, 'H', 0),
    (13, 'Venus', 0), (13, 'Júpiter', 0), (13, 'Marte', 1), (13, 'Saturno', 0),
    (14, 'CO2', 0), (14, 'O2', 0), (14, 'H2O', 1), (14, 'NaCl', 0);

-- Historia (Categoría 4)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Quién fue el primer presidente de los Estados Unidos?', 4, 12, 'activa', '2025-04-10 10:00:00'),
    ('¿En qué año cayó el Muro de Berlín?', 4, 12, 'activa', '2025-04-11 11:00:00'),
    ('¿Qué civilización construyó Machu Picchu?', 4, 12, 'activa', '2025-04-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (15, 'Abraham Lincoln', 0), (15, 'Thomas Jefferson', 0), (15, 'George Washington', 1), (15, 'John Adams', 0),
    (16, '1985', 0), (16, '1989', 1), (16, '1991', 0), (16, '1993', 0),
    (17, 'Azteca', 0), (17, 'Maya', 0), (17, 'Inca', 1), (17, 'Egipcia', 0);

-- Deportes (Categoría 5)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, fecha_creacion)
VALUES
    ('¿Cada cuántos años se celebran los Juegos Olímpicos de Verano?', 5, 12, 'activa', '2025-05-10 10:00:00'),
    ('¿Qué país ganó la primera Copa Mundial de Fútbol en 1930?', 5, 12, 'activa', '2025-05-11 11:00:00'),
    ('¿Quién es considerado el mejor jugador de baloncesto de todos los tiempos?', 5, 12, 'activa', '2025-05-12 12:00:00');

INSERT INTO respuesta (id_pregunta, texto, es_correcta)
VALUES
    (18, '2', 0), (18, '3', 0), (18, '4', 1), (18, '5', 0),
    (19, 'Argentina', 0), (19, 'Brasil', 0), (19, 'Uruguay', 1), (19, 'Italia', 0),
    (20, 'LeBron James', 0), (20, 'Kareem Abdul-Jabbar', 0), (20, 'Michael Jordan', 1), (20, 'Magic Johnson', 0);

-- Insertar un resumen de partida para las partidas existentes
INSERT INTO resumen_partida (id_partida, id_usuario, cantidad_correctas, cantidad_intentos, puntaje, fecha_partida)
SELECT p.id, p.id_usuario,
       (p.puntaje / 100) AS cantidad_correctas, -- Estimación
       5 AS cantidad_intentos, -- Estimación
       p.puntaje, p.fecha
FROM partida p
WHERE NOT EXISTS (SELECT 1 FROM resumen_partida rp WHERE rp.id_partida = p.id);