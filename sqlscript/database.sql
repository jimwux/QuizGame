DROP DATABASE IF EXISTS juegopreguntas;
CREATE DATABASE IF NOT EXISTS juegopreguntas;
USE juegopreguntas;

-- Tabla usuarios
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          nombre_completo VARCHAR(255) NOT NULL,
                          año_nacimiento INT NOT NULL,
                          sexo VARCHAR(50) NOT NULL,
                          pais VARCHAR(100) NOT NULL,
                          ciudad VARCHAR(100) NOT NULL,
                          mail VARCHAR(255) NOT NULL UNIQUE,
                          usuario VARCHAR(100) NOT NULL UNIQUE,
                          password VARCHAR(255) NOT NULL,
                          foto_perfil VARCHAR(255),
                          activo TINYINT(1) DEFAULT 0,
                          token_validacion VARCHAR(255),
                          latitud DECIMAL(10,6),
                          longitud DECIMAL(10,6),
                          rol VARCHAR(50) NOT NULL DEFAULT 'jugador'
);

-- Tabla categoria
DROP TABLE IF EXISTS categoria;
CREATE TABLE categoria (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           nombre VARCHAR(100) NOT NULL,
                           color VARCHAR(7) NOT NULL -- formato #RRGGBB
);

-- Tabla dificultad
DROP TABLE IF EXISTS dificultad;
CREATE TABLE dificultad (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            descripcion VARCHAR(100) NOT NULL
);

-- Tabla pregunta
DROP TABLE IF EXISTS pregunta;
CREATE TABLE pregunta (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          texto TEXT NOT NULL,
                          id_categoria INT NOT NULL,
                          id_creador INT NOT NULL,
                          estado ENUM('activa', 'inactiva', 'pendiente') DEFAULT 'pendiente',
                          veces_mostrada INT DEFAULT 0,
                          veces_respondida_correctamente INT DEFAULT 0,
                          id_dificultad INT NOT NULL DEFAULT 2, -- ← dificultad media por defecto
                          FOREIGN KEY (id_categoria) REFERENCES categoria(id),
                          FOREIGN KEY (id_creador) REFERENCES usuarios(id),
                          FOREIGN KEY (id_dificultad) REFERENCES dificultad(id)
);

-- Tabla respuesta
DROP TABLE IF EXISTS respuesta;
CREATE TABLE respuesta (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           id_pregunta INT NOT NULL,
                           texto VARCHAR(255) NOT NULL,
                           es_correcta BOOLEAN NOT NULL,
                           FOREIGN KEY (id_pregunta) REFERENCES pregunta(id)
);

-- Tabla partida (simplificada, sin tipo)
DROP TABLE IF EXISTS partida;
CREATE TABLE partida (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         id_usuario INT NOT NULL,
                         fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                         puntaje INT DEFAULT 0,
                         finalizada BOOLEAN DEFAULT FALSE,
                         FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- Tabla partida_pregunta (MODIFICADA)
DROP TABLE IF EXISTS partida_pregunta;
CREATE TABLE partida_pregunta (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  id_partida INT NOT NULL,
                                  id_pregunta INT NOT NULL,
                                  id_respuesta INT,
                                  respondida_correctamente BOOLEAN,
                                  estado_pregunta ENUM('mostrada', 'respondida', 'actual') DEFAULT NULL, -- Nuevo estado
                                  orden_pregunta INT,
                                  FOREIGN KEY (id_partida) REFERENCES partida(id),
                                  FOREIGN KEY (id_pregunta) REFERENCES pregunta(id),
                                  FOREIGN KEY (id_respuesta) REFERENCES respuesta(id),
                                  UNIQUE KEY (id_partida, id_pregunta) -- AGREGAR ESTA LÍNEA
);

-- Tabla reporte_pregunta
DROP TABLE IF EXISTS reporte_pregunta;
CREATE TABLE reporte_pregunta (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  id_pregunta INT NOT NULL,
                                  id_usuario INT NOT NULL,
                                  motivo TEXT,
                                  fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
                                  estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
                                  FOREIGN KEY (id_pregunta) REFERENCES pregunta(id),
                                  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- Tabla sugerencia_pregunta
DROP TABLE IF EXISTS sugerencia_pregunta;
CREATE TABLE sugerencia_pregunta (
                                     id INT AUTO_INCREMENT PRIMARY KEY,
                                     id_usuario INT NOT NULL,
                                     texto TEXT NOT NULL,
                                     opcionA TEXT NOT NULL ,
                                     opcionB TEXT NOT NULL,
                                     opcionC TEXT NOT NULL,
                                     opcionD TEXT NOT NULL,
                                     id_categoria INT NOT NULL,
                                     estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
                                     fecha_sugerencia DATETIME DEFAULT CURRENT_TIMESTAMP,
                                     FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
                                     FOREIGN KEY (id_categoria) REFERENCES categoria(id)
);

-- Tabla resumen_partida
DROP TABLE IF EXISTS resumen_partida;
CREATE TABLE resumen_partida (
                                 id_resumen INT AUTO_INCREMENT PRIMARY KEY,
                                 id_partida INT NOT NULL UNIQUE, -- Una partida solo tiene un resumen
                                 id_usuario INT NOT NULL,
                                 cantidad_correctas INT NOT NULL DEFAULT 0,
                                 cantidad_intentos INT NOT NULL DEFAULT 0,
                                 id_categoria INT DEFAULT NULL,
                                 puntaje INT NOT NULL DEFAULT 0,
                                 tiempo_promedio_respuesta FLOAT DEFAULT NULL,
                                 fecha_partida DATETIME NOT NULL,
                                 FOREIGN KEY (id_partida) REFERENCES partida(id),
                                 FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
                                 FOREIGN KEY (id_categoria) REFERENCES categoria(id)
);

-- -- Tabla estadistica_respuestas_usuario
-- DROP TABLE IF EXISTS estadistica_respuestas_usuario;
-- CREATE TABLE estadistica_respuestas_usuario (
--                                                 id_usuario INT PRIMARY KEY,
--                                                 total_partidas_jugadas INT NOT NULL DEFAULT 0,
--                                                 total_intentos INT NOT NULL DEFAULT 0,
--                                                 total_correctas INT NOT NULL DEFAULT 0,
--                                                 porcentaje_general FLOAT DEFAULT 0,
--                                                 tiempo_promedio_respuesta FLOAT DEFAULT NULL,
--                                                 nivel_calculado INT DEFAULT NULL,
--                                                 FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
-- );

-- Tabla pregunta_usuario
DROP TABLE IF EXISTS pregunta_usuario;
CREATE TABLE pregunta_usuario (
                                  id INT(11) PRIMARY KEY AUTO_INCREMENT,
                                  id_usuario INT(11) NOT NULL,
                                  id_pregunta INT(11) NOT NULL,
                                  id_respuesta INT(11), -- Puede ser NULL si la respuesta no fue registrada o fue timeout
                                  es_correcta BOOLEAN NOT NULL,
                                  FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
                                  FOREIGN KEY (id_pregunta) REFERENCES pregunta(id),
                                  FOREIGN KEY (id_respuesta) REFERENCES respuesta(id),
                                  UNIQUE KEY usuario_pregunta_unica (id_usuario, id_pregunta)
);






-- INSERTS POR ORDEN
-- TODO: Todas las tablas tienen que empezar con id 1 SI O SI, sino no anda
-- Usar esta query en caso de que no funque: ALTER TABLE nombreTabla AUTO_INCREMENT = 1;
-- Agregar primero por lo menos 10 INSERTS de Usuarios sin incluir el de ustedes
-- Usar la base de datos
USE juegopreguntas;

-- Insertar usuarios
INSERT INTO usuarios (nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password, rol) VALUES
                                                                                                             ('Juan Pérez', 1990, 'Masculino', 'Argentina', 'Buenos Aires', 'juan@gmail.com', 'juanperez', '123456', 'jugador'),
                                                                                                             ('Ana González', 1995, 'Femenino', 'México', 'Ciudad de México', 'ana@gmail.com', 'anagonzalez', '123456', 'jugador'),
                                                                                                             ('Carlos López', 1988, 'Masculino', 'España', 'Madrid', 'carlos@gmail.com', 'carloslopez', '123456', 'jugador'),
                                                                                                             ('Lucía Martínez', 2000, 'Femenino', 'Colombia', 'Bogotá', 'lucia@gmail.com', 'luciamartinez', '123456', 'jugador'),
                                                                                                             ('Pedro Sánchez', 1992, 'Masculino', 'Chile', 'Santiago', 'pedro@gmail.com', 'pedrosanchez', '123456', 'jugador');

-- Insertar categorías
INSERT INTO categoria (nombre, color) VALUES
                                          ('Geografía', '#3498db'),
                                          ('Matemáticas', '#e74c3c'),
                                          ('Ciencia', '#2ecc71'),
                                          ('Historia', '#f1c40f'),
                                          ('Deportes', '#9b59b6');

-- Insertar dificultades
INSERT INTO dificultad (descripcion) VALUES
                                         ('Fácil'),
                                         ('Medio'),
                                         ('Difícil');

-- Insertar preguntas y respuestas
-- Insertar 15 preguntas (sin especificar dificultad, usará valor por defecto = 2)
INSERT INTO pregunta (texto, id_categoria, id_creador, estado) VALUES
                                                                   ('¿Cuántos continentes hay en el mundo?', 1, 1, 'activa'),
                                                                   ('¿Cuál es la capital de Francia?', 1, 1, 'activa'),
                                                                   ('¿Qué océano está al este de África?', 1, 1, 'activa'),

                                                                   ('¿Cuánto es 2 + 2?', 2, 2, 'activa'),
                                                                   ('¿Cuánto es 5 x 3?', 2, 2, 'activa'),
                                                                   ('¿Cuál es la raíz cuadrada de 16?', 2, 2, 'activa'),

                                                                   ('¿De qué color es el cielo en un día despejado?', 3, 3, 'activa'),
                                                                   ('¿De qué color son las hojas en otoño?', 3, 3, 'activa'),
                                                                   ('¿Qué color se obtiene al mezclar azul y amarillo?', 3, 3, 'activa'),

                                                                   ('¿En qué año comenzó la Segunda Guerra Mundial?', 4, 4, 'activa'),
                                                                   ('¿Quién fue el primer presidente de Argentina?', 4, 4, 'activa'),
                                                                   ('¿En qué siglo ocurrió la Revolución Francesa?', 4, 4, 'activa'),

                                                                   ('¿Cuántos jugadores hay en un equipo de fútbol?', 5, 5, 'activa'),
                                                                   ('¿Cuántos puntos vale un gol en fútbol?', 5, 5, 'activa'),
                                                                   ('¿Qué deporte se practica en Wimbledon?', 5, 5, 'activa');

-- Pregunta 1: ¿Cuántos continentes hay en el mundo?
INSERT INTO respuesta (id_pregunta, texto, es_correcta) VALUES
                                                            (1, '5', 0),
                                                            (1, '6', 0),
                                                            (1, '7', 1),
                                                            (1, '8', 0),

-- Pregunta 2: ¿Cuál es la capital de Francia?
                                                            (2, 'Madrid', 0),
                                                            (2, 'París', 1),
                                                            (2, 'Roma', 0),
                                                            (2, 'Londres', 0),

-- Pregunta 3: ¿Qué océano está al este de África?
                                                            (3, 'Pacífico', 0),
                                                            (3, 'Índico', 1),
                                                            (3, 'Atlántico', 0),
                                                            (3, 'Ártico', 0),

-- Pregunta 4: ¿Cuánto es 2 + 2?
                                                            (4, '3', 0),
                                                            (4, '4', 1),
                                                            (4, '5', 0),
                                                            (4, '6', 0),

-- Pregunta 5: ¿Cuánto es 5 x 3?
                                                            (5, '8', 0),
                                                            (5, '15', 1),
                                                            (5, '10', 0),
                                                            (5, '20', 0),

-- Pregunta 6: ¿Cuál es la raíz cuadrada de 16?
                                                            (6, '2', 0),
                                                            (6, '4', 1),
                                                            (6, '6', 0),
                                                            (6, '5', 0),

-- Pregunta 7: ¿De qué color es el cielo en un día despejado?
                                                            (7, 'Rojo', 0),
                                                            (7, 'Azul', 1),
                                                            (7, 'Verde', 0),
                                                            (7, 'Amarillo', 0),

-- Pregunta 8: ¿De qué color son las hojas en otoño?
                                                            (8, 'Verde', 0),
                                                            (8, 'Marrón', 1),
                                                            (8, 'Azul', 0),
                                                            (8, 'Gris', 0),

-- Pregunta 9: ¿Qué color se obtiene al mezclar azul y amarillo?
                                                            (9, 'Rojo', 0),
                                                            (9, 'Naranja', 0),
                                                            (9, 'Verde', 1),
                                                            (9, 'Violeta', 0),

-- Pregunta 10: ¿En qué año comenzó la Segunda Guerra Mundial?
                                                            (10, '1929', 0),
                                                            (10, '1935', 0),
                                                            (10, '1939', 1),
                                                            (10, '1942', 0),

-- Pregunta 11: ¿Quién fue el primer presidente de Argentina?
                                                            (11, 'San Martín', 0),
                                                            (11, 'Rivadavia', 1),
                                                            (11, 'Sarmiento', 0),
                                                            (11, 'Belgrano', 0),

-- Pregunta 12: ¿En qué siglo ocurrió la Revolución Francesa?
                                                            (12, 'XV', 1),
                                                            (12, 'XVI', 0),
                                                            (12, 'XVII', 0),
                                                            (12, 'XIV', 0),

-- Pregunta 13: ¿Cuántos jugadores hay en un equipo de fútbol?
                                                            (13, '9', 0),
                                                            (13, '10', 0),
                                                            (13, '11', 1),
                                                            (13, '12', 0),

-- Pregunta 14: ¿Cuántos puntos vale un gol en fútbol?
                                                            (14, '2', 0),
                                                            (14, '3', 0),
                                                            (14, '1', 1),
                                                            (14, '5', 0),

-- Pregunta 15: ¿Qué deporte se practica en Wimbledon?
                                                            (15, 'Golf', 0),
                                                            (15, 'Tenis', 1),
                                                            (15, 'Béisbol', 0),
                                                            (15, 'Rugby', 0);
