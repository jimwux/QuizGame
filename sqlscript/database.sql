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
    FOREIGN KEY (id_respuesta) REFERENCES respuesta (id),
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

-- Insertar usuarios
INSERT INTO usuarios (nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password, rol)
VALUES ('Juan Pérez', 1990, 'Masculino', 'Argentina', 'Buenos Aires', 'juan@gmail.com', 'juanperez', '123456',
        'jugador'),
       ('Ana González', 1995, 'Femenino', 'México', 'Ciudad de México', 'ana@gmail.com', 'anagonzalez', '123456',
        'jugador'),
       ('Carlos López', 1988, 'Masculino', 'España', 'Madrid', 'carlos@gmail.com', 'carloslopez', '123456', 'jugador'),
       ('Lucía Martínez', 2000, 'Femenino', 'Colombia', 'Bogotá', 'lucia@gmail.com', 'luciamartinez', '123456',
        'jugador'),
       ('Pedro Sánchez', 1992, 'Masculino', 'Chile', 'Santiago', 'pedro@gmail.com', 'pedrosanchez', '123456',
        'jugador');

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

-- Insertar preguntas y respuestas
INSERT INTO pregunta (texto, id_categoria, id_creador, estado)
VALUES ('¿Cuántos continentes hay en el mundo?', 1, 1, 'activa'),
       ('¿Cuánto es 2 + 2?', 2, 2, 'activa'),
       ('¿De qué color es el cielo en un día despejado?', 3, 3, 'activa'),
       ('¿En qué año comenzó la Segunda Guerra Mundial?', 4, 4, 'activa'),
       ('¿Cuántos jugadores hay en un equipo de fútbol?', 5, 5, 'activa');

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
INSERT INTO sugerencia_pregunta (id_usuario, texto, id_categoria, estado) VALUES
                                                                              (1, '¿Cuánto es 5 + 3?', 2, 'pendiente'),
                                                                              (2, '¿Cuál es la capital de España?', 1, 'pendiente'),
                                                                              (3, '¿Cuántos planetas hay en el sistema solar?', 3, 'pendiente'),
                                                                              (4, '¿Quién escribió "Romeo y Julieta"?', 4, 'pendiente'),
                                                                              (5, '¿Qué deporte se juega con una pelota de tenis?', 5, 'pendiente');

-- Insertar respuestas para cada pregunta sugerida
INSERT INTO sugerencia_respuesta (id_sugerencia, texto, es_correcta) VALUES
-- Respuestas para "¿Cuánto es 5 + 3?"
(1, '6', 0),
(1, '7', 0),
(1, '8', 1),
(1, '9', 0),

-- Respuestas para "¿Cuál es la capital de España?"
(2, 'Barcelona', 0),
(2, 'Madrid', 1),
(2, 'Valencia', 0),
(2, 'Sevilla', 0),

-- Respuestas para "¿Cuántos planetas hay en el sistema solar?"
(3, '7', 0),
(3, '8', 1),
(3, '9', 0),
(3, '10', 0),

-- Respuestas para "¿Quién escribió 'Romeo y Julieta'?"
(4, 'Miguel de Cervantes', 0),
(4, 'William Shakespeare', 1),
(4, 'Jorge Luis Borges', 0),
(4, 'Mario Vargas Llosa', 0),

-- Respuestas para "¿Qué deporte se juega con una pelota de tenis?"
(5, 'Fútbol', 0),
(5, 'Baloncesto', 0),
(5, 'Tenis', 1),
(5, 'Voleibol', 0);
