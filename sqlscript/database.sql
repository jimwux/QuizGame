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
    longitud DECIMAL(10,6)
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
    FOREIGN KEY (id_categoria) REFERENCES categoria(id),
    FOREIGN KEY (id_creador) REFERENCES usuarios(id)
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
    id_dificultad INT DEFAULT NULL,
    puntaje INT NOT NULL DEFAULT 0,
    tiempo_promedio_respuesta FLOAT DEFAULT NULL,
    fecha_partida DATETIME NOT NULL,
    FOREIGN KEY (id_partida) REFERENCES partida(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_categoria) REFERENCES categoria(id),
    FOREIGN KEY (id_dificultad) REFERENCES dificultad(id)
);

-- Tabla estadistica_respuestas_usuario
DROP TABLE IF EXISTS estadistica_respuestas_usuario;
CREATE TABLE estadistica_respuestas_usuario (
                                                id_usuario INT PRIMARY KEY,
                                                total_partidas_jugadas INT NOT NULL DEFAULT 0,
                                                total_intentos INT NOT NULL DEFAULT 0,
                                                total_correctas INT NOT NULL DEFAULT 0,
                                                porcentaje_general FLOAT DEFAULT 0,
                                                tiempo_promedio_respuesta FLOAT DEFAULT NULL,
                                                nivel_calculado INT DEFAULT NULL,
                                                FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

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
    UNIQUE KEY (id_usuario, id_pregunta) -- AGREGAR ESTA LÍNEA para evitar duplicados si un usuario responde la misma pregunta en diferentes partidas o recargas.
);






-- INSERTS POR ORDEN
-- TODO: Todas las tablas tienen que empezar con id 1 SI O SI, sino no anda
    -- Usar esta query en caso de que no funque: ALTER TABLE nombreTabla AUTO_INCREMENT = 1;
-- Agregar primero por lo menos 10 INSERTS de Usuarios sin incluir el de ustedes

-- Usuarios
INSERT INTO usuarios (
    nombre_completo, año_nacimiento, sexo, pais, ciudad, mail, usuario, password,
    foto_perfil, activo, token_validacion, latitud, longitud
) VALUES
      ('Valentina Gómez', 1995, 'F', 'Argentina', 'Rosario', 'valen.gomez@gmail.com', 'valeng', 'pass1234', 'valen.jpg', 1, 'tk1abc', -32.9587, -60.6939),
      ('Lucas Pereira', 1990, 'M', 'Uruguay', 'Montevideo', 'lucas.pereira@gmail.com', 'lucasp', 'pass5678', 'lucas.jpg', 1, 'tk2def', -34.9011, -56.1645),
      ('Martina López', 1988, 'F', 'Chile', 'Santiago', 'marti.lopez@gmail.com', 'martinal', 'pass4321', 'martina.jpg', 1, 'tk3ghi', -33.4489, -70.6693),
      ('Joaquín Torres', 1997, 'M', 'Argentina', 'Córdoba', 'joaquin.t@gmail.com', 'joatorres', 'securepass', 'joaquin.jpg', 1, 'tk4jkl', -31.4201, -64.1888),
      ('Camila Rivas', 1992, 'F', 'Paraguay', 'Asunción', 'camirivas@gmail.com', 'camir', 'camipass', 'camila.jpg', 1, 'tk5mno', -25.2637, -57.5759),
      ('Diego Fernández', 1985, 'M', 'España', 'Madrid', 'diego.fernandez@gmail.com', 'diegof', 'clave123', 'diego.jpg', 1, 'tk6pqr', 40.4168, -3.7038),
      ('Lucía Méndez', 2000, 'F', 'México', 'Guadalajara', 'lucia.m@gmail.com', 'luciam', 'passlucia', 'lucia.jpg', 1, 'tk7stu', 20.6597, -103.3496),
      ('Andrés Molina', 1993, 'M', 'Colombia', 'Bogotá', 'andres.molina@gmail.com', 'andmol', 'molina123', 'andres.jpg', 1, 'tk8vwx', 4.7110, -74.0721),
      ('Florencia Soto', 1996, 'F', 'Argentina', 'Mendoza', 'flor.soto@gmail.com', 'flors', 'florpass', 'florencia.jpg', 1, 'tk9yz1', -32.8895, -68.8458),
      ('Tomás Herrera', 1989, 'M', 'Perú', 'Lima', 'tomas.h@gmail.com', 'tomh', 'tompass', 'tomas.jpg', 1, 'tk10zab', -12.0464, -77.0428);

-- Categoria
INSERT INTO categoria (nombre, color) VALUES
                                          ('Deporte', '#FF8C00'), -- Naranja para deporte
                                          ('Historia', '#8B4513'), -- Marrón tierra para historia
                                          ('Ciencia', '#00BFFF'), -- Azul cielo para ciencia
                                          ('Entretenimiento', '#FFD700'), -- Dorado para entretenimiento
                                          ('Geografía', '#228B22'), -- Verde bosque para geografía
                                          ('Arte', '#9932CC'), -- Púrpura para arte
                                          ('Tecnología', '#FF4500'), -- Naranja rojizo para tecnología
                                          ('Música', '#FF69B4'), -- Rosa vibrante para música
                                          ('Literatura', '#A0522D'), -- Marrón claro para literatura
                                          ('Gastronomía', '#DAA520'); -- Dorado oscuro para gastronomía

-- Preguntas
INSERT INTO pregunta (texto, id_categoria, id_creador, estado, veces_mostrada, veces_respondida_correctamente) VALUES
-- Deporte
('¿Cuántos jugadores tiene un equipo de fútbol en la cancha?', 1, 1, 'activa', 0, 0),
('¿Quién es considerado el mejor tenista de todos los tiempos?', 1, 2, 'activa', 0, 0),
('¿Qué país ganó la Copa del Mundo de 2022?', 1, 3, 'activa', 0, 0),
('¿En qué deporte se utiliza un "birdie"?', 1, 4, 'activa', 0, 0),
('¿Cuántos puntos vale un touchdown en fútbol americano?', 1, 5, 'activa', 0, 0),
('¿Cuál es el deporte nacional de Japón?', 1, 1, 'activa', 0, 0),
('¿Qué atleta es conocido como "La Bala" en el atletismo?', 1, 2, 'activa', 0, 0),
('¿Cuántos aros tiene una cancha de baloncesto?', 1, 3, 'activa', 0, 0),
('¿Quién es el máximo goleador en la historia de la Champions League?', 1, 4, 'activa', 0, 0),
('¿Qué significa "KO" en boxeo?', 1, 5, 'activa', 0, 0),
('¿Cuál es la duración de un partido de rugby?', 1, 1, 'activa', 0, 0),
('¿Quién es el "Rey del balompié"?', 1, 2, 'activa', 0, 0),
('¿En qué deporte se utiliza la "espada" como arma?', 1, 3, 'activa', 0, 0),
('¿Qué país es famoso por su equipo de hockey sobre hielo?', 1, 4, 'activa', 0, 0),
('¿Cuál es el nombre del estadio del Real Madrid?', 1, 5, 'activa', 0, 0),

-- Historia
('¿En qué año cayó el Muro de Berlín?', 2, 1, 'activa', 0, 0),
('¿Quién fue el primer presidente de los Estados Unidos?', 2, 2, 'activa', 0, 0),
('¿Cuándo comenzó la Primera Guerra Mundial?', 2, 3, 'activa', 0, 0),
('¿Quién escribió "El Diario de Ana Frank"?', 2, 4, 'activa', 0, 0),
('¿Qué civilización construyó las pirámides de Egipto?', 2, 5, 'activa', 0, 0),
('¿En qué año llegó Cristóbal Colón a América?', 2, 1, 'activa', 0, 0),
('¿Quién fue Cleopatra?', 2, 2, 'activa', 0, 0),
('¿Cuál fue la causa principal de la Segunda Guerra Mundial?', 2, 3, 'activa', 0, 0),
('¿Qué emperador romano legalizó el cristianismo?', 2, 4, 'activa', 0, 0),
('¿En qué siglo vivió William Shakespeare?', 2, 5, 'activa', 0, 0),
('¿Quién fue el líder de la Revolución Cubana?', 2, 1, 'activa', 0, 0),
('¿En qué año se firmó la Declaración de Independencia de Argentina?', 2, 2, 'activa', 0, 0),
('¿Cuál fue la Gran Muralla China?', 2, 3, 'activa', 0, 0),
('¿Quién fue el primer hombre en pisar la Luna?', 2, 4, 'activa', 0, 0),
('¿Qué evento marcó el inicio de la Revolución Francesa?', 2, 5, 'activa', 0, 0),

-- Ciencia
('¿Cuál es el planeta más grande de nuestro sistema solar?', 3, 1, 'activa', 0, 0),
('¿Cuál es la fórmula química del agua?', 3, 2, 'activa', 0, 0),
('¿Qué gas respiramos los seres humanos?', 3, 3, 'activa', 0, 0),
('¿Quién formuló la teoría de la relatividad?', 3, 4, 'activa', 0, 0),
('¿Cuál es el hueso más largo del cuerpo humano?', 3, 5, 'activa', 0, 0),
('¿Qué tipo de energía produce el sol?', 3, 1, 'activa', 0, 0),
('¿Cuál es el elemento más abundante en la Tierra?', 3, 2, 'activa', 0, 0),
('¿Cuántos estados de la materia existen?', 3, 3, 'activa', 0, 0),
('¿Qué es un agujero negro?', 3, 4, 'activa', 0, 0),
('¿Quién inventó la bombilla eléctrica?', 3, 5, 'activa', 0, 0),
('¿Cuál es la velocidad de la luz?', 3, 1, 'activa', 0, 0),
('¿Qué parte de la planta realiza la fotosíntesis?', 3, 2, 'activa', 0, 0),
('¿Qué estudia la biología?', 3, 3, 'activa', 0, 0),
('¿Cuál es la fuerza que nos mantiene pegados a la Tierra?', 3, 4, 'activa', 0, 0),
('¿Qué significa ADN?', 3, 5, 'activa', 0, 0),

-- Entretenimiento
('¿Quién es el actor que interpreta a Iron Man en el MCU?', 4, 1, 'activa', 0, 0),
('¿Cuál es la película más taquillera de todos los tiempos?', 4, 2, 'activa', 0, 0),
('¿Qué serie de televisión está ambientada en Westeros?', 4, 3, 'activa', 0, 0),
('¿Quién es la cantante de "Hello"?', 4, 4, 'activa', 0, 0),
('¿En qué año se lanzó el primer videojuego "Super Mario Bros."?', 4, 5, 'activa', 0, 0),
('¿Quién dirigió la película "Titanic"?', 4, 1, 'activa', 0, 0),
('¿Cuál es el nombre del personaje principal en "Harry Potter"?', 4, 2, 'activa', 0, 0),
('¿Qué banda lanzó el álbum "Thriller"?', 4, 3, 'activa', 0, 0),
('¿Quién escribió "Alicia en el País de las Maravillas"?', 4, 4, 'activa', 0, 0),
('¿Cuál es el nombre del famoso ratón de Disney?', 4, 5, 'activa', 0, 0),
('¿Qué actor interpretó a James Bond más veces?', 4, 1, 'activa', 0, 0),
('¿Cuál es el nombre del superhéroe con martillo?', 4, 2, 'activa', 0, 0),
('¿Qué programa de televisión ganó más premios Emmy?', 4, 3, 'activa', 0, 0),
('¿Quién es la reina del pop?', 4, 4, 'activa', 0, 0),
('¿En qué ciudad se celebra el Festival de Cine de Cannes?', 4, 5, 'activa', 0, 0),

-- Geografía
('¿Cuál es el río más largo del mundo?', 5, 1, 'activa', 0, 0),
('¿Cuál es la capital de Francia?', 5, 2, 'activa', 0, 0),
('¿Qué océano es el más grande?', 5, 3, 'activa', 0, 0),
('¿En qué continente se encuentra el desierto del Sahara?', 5, 4, 'activa', 0, 0),
('¿Cuál es la montaña más alta del mundo?', 5, 5, 'activa', 0, 0),
('¿Qué país es conocido como la "Tierra del Sol Naciente"?', 5, 1, 'activa', 0, 0),
('¿Cuál es el lago más profundo del mundo?', 5, 2, 'activa', 0, 0),
('¿Qué país tiene la mayor población del mundo?', 5, 3, 'activa', 0, 0),
('¿Cuál es el nombre del estrecho que separa Asia de América?', 5, 4, 'activa', 0, 0),
('¿En qué país se encuentra la Gran Barrera de Coral?', 5, 5, 'activa', 0, 0),

-- Arte
('¿Quién pintó la Mona Lisa?', 6, 1, 'activa', 0, 0),
('¿En qué museo se encuentra la Venus de Milo?', 6, 2, 'activa', 0, 0),
('¿Qué movimiento artístico se caracteriza por el uso de colores brillantes y pinceladas audaces?', 6, 3, 'activa', 0, 0),
('¿Quién esculpió el David?', 6, 4, 'activa', 0, 0),
('¿Cuál es el nombre del famoso cuadro de Edvard Munch?', 6, 5, 'activa', 0, 0),

-- Tecnología
('¿Qué significa HTML?', 7, 1, 'activa', 0, 0),
('¿Quién es el cofundador de Apple?', 7, 2, 'activa', 0, 0),
('¿Qué es un algoritmo?', 7, 3, 'activa', 0, 0),
('¿Cuál es el sistema operativo más utilizado en computadoras de escritorio?', 7, 4, 'activa', 0, 0),
('¿Qué es la inteligencia artificial?', 7, 5, 'activa', 0, 0),

-- Música
('¿Quién es conocido como el "Rey del Pop"?', 8, 1, 'activa', 0, 0),
('¿Cuántas notas tiene una escala diatónica?', 8, 2, 'activa', 0, 0),
('¿Cuál es el instrumento musical más grande del mundo?', 8, 3, 'activa', 0, 0),
('¿Quién compuso "Las Cuatro Estaciones"?', 8, 4, 'activa', 0, 0),
('¿Qué género musical se originó en Jamaica?', 8, 5, 'activa', 0, 0),

-- Literatura
('¿Quién escribió "Don Quijote de la Mancha"?', 9, 1, 'activa', 0, 0),
('¿Cuál es la obra más famosa de William Shakespeare?', 9, 2, 'activa', 0, 0),
('¿Qué tipo de literatura es "Cien años de soledad"?', 9, 3, 'activa', 0, 0),
('¿Quién es el autor de "El Principito"?', 9, 4, 'activa', 0, 0),
('¿Qué novela comienza con la frase "Era el mejor de los tiempos, era el peor de los tiempos..."?', 9, 5, 'activa', 0, 0),

-- Gastronomía
('¿Cuál es el ingrediente principal del sushi?', 10, 1, 'activa', 0, 0),
('¿De qué país es originaria la pizza?', 10, 2, 'activa', 0, 0),
('¿Qué tipo de queso se usa tradicionalmente en la salsa pesto?', 10, 3, 'activa', 0, 0),
('¿Cuál es la especia más cara del mundo?', 10, 4, 'activa', 0, 0),
('¿Qué bebida se conoce como el "oro líquido" de México?', 10, 5, 'activa', 0, 0),
('¿Qué es el umami?', 10, 1, 'activa', 0, 0),
('¿De qué país es el plato "Pad Thai"?', 10, 2, 'activa', 0, 0),
('¿Cuál es el principal ingrediente de la tortilla española?', 10, 3, 'activa', 0, 0),
('¿Qué tipo de pasta tiene forma de mariposa?', 10, 4, 'activa', 0, 0),
('¿Cuál es la bebida alcohólica más consumida en el mundo?', 10, 5, 'activa', 0, 0),
('¿Qué fruta es el ingrediente principal del guacamole?', 10, 1, 'activa', 0, 0),
('¿De dónde es originario el café?', 10, 2, 'activa', 0, 0),
('¿Qué carne es la base del "pastor" en los tacos al pastor?', 10, 3, 'activa', 0, 0),
('¿Cuál es el nombre del famoso chef británico conocido por sus programas de cocina?', 10, 4, 'activa', 0, 0),
('¿Qué ingrediente le da el color amarillo al curry?', 10, 5, 'activa', 0, 0);

-- Respuestas
INSERT INTO respuesta (id_pregunta, texto, es_correcta) VALUES
-- Respuestas para Deporte (id_categoria: 1)
(1, '11', 1), (1, '10', 0), (1, '9', 0), (1, '12', 0),
(2, 'Roger Federer', 0), (2, 'Rafael Nadal', 0), (2, 'Novak Djokovic', 1), (2, 'Pete Sampras', 0),
(3, 'Argentina', 1), (3, 'Francia', 0), (3, 'Brasil', 0), (3, 'Alemania', 0),
(4, 'Golf', 1), (4, 'Tenis', 0), (4, 'Bádminton', 0), (4, 'Hockey', 0),
(5, '6', 1), (5, '7', 0), (5, '3', 0), (5, '1', 0),
(6, 'Judo', 0), (6, 'Sumo', 1), (6, 'Kendo', 0), (6, 'Karate', 0),
(7, 'Usain Bolt', 1), (7, 'Carl Lewis', 0), (7, 'Jesse Owens', 0), (7, 'Michael Johnson', 0),
(8, '2', 1), (8, '1', 0), (8, '3', 0), (8, '4', 0),
(9, 'Lionel Messi', 0), (9, 'Cristiano Ronaldo', 1), (9, 'Raúl González', 0), (9, 'Karim Benzema', 0),
(10, 'Knock Out', 1), (10, 'King Off', 0), (10, 'Keep On', 0), (10, 'Kick Off', 0),
(11, '80 minutos', 1), (11, '90 minutos', 0), (11, '60 minutos', 0), (11, '70 minutos', 0),
(12, 'Pelé', 1), (12, 'Maradona', 0), (12, 'Cruyff', 0), (12, 'Di Stéfano', 0),
(13, 'Esgrima', 1), (13, 'Judo', 0), (13, 'Karate', 0), (13, 'Taekwondo', 0),
(14, 'Canadá', 1), (14, 'Estados Unidos', 0), (14, 'Rusia', 0), (14, 'Suecia', 0),
(15, 'Camp Nou', 0), (15, 'Santiago Bernabéu', 1), (15, 'Wanda Metropolitano', 0), (15, 'Allianz Arena', 0),

-- Respuestas para Historia (id_categoria: 2)
(16, '1989', 1), (16, '1991', 0), (16, '1987', 0), (16, '1990', 0),
(17, 'Thomas Jefferson', 0), (17, 'George Washington', 1), (17, 'Abraham Lincoln', 0), (17, 'John Adams', 0),
(18, '1914', 1), (18, '1939', 0), (18, '1918', 0), (18, '1900', 0),
(19, 'Ana Frank', 1), (19, 'Otto Frank', 0), (19, 'Miep Gies', 0), (19, 'Margot Frank', 0),
(20, 'Romanos', 0), (20, 'Griegos', 0), (20, 'Egipcios', 1), (20, 'Mesopotámicos', 0),
(21, '1492', 1), (21, '1500', 0), (21, '1488', 0), (21, '1510', 0),
(22, 'Reina de Egipto', 1), (22, 'Emperatriz Romana', 0), (22, 'Princesa Griega', 0), (22, 'Faraona', 0),
(23, 'Invasión de Polonia', 1), (23, 'Ataque a Pearl Harbor', 0), (23, 'Crisis del 29', 0), (23, 'Tratado de Versalles', 0),
(24, 'Nerón', 0), (24, 'Constantino I', 1), (24, 'Julio César', 0), (24, 'Augusto', 0),
(25, 'XVI', 1), (25, 'XV', 0), (25, 'XVII', 0), (25, 'XIV', 0),
(26, 'Fidel Castro', 1), (26, 'Che Guevara', 0), (26, 'Raúl Castro', 0), (26, 'Camilo Cienfuegos', 0),
(27, '1816', 1), (27, '1810', 0), (27, '1820', 0), (27, '1825', 0),
(28, 'Una muralla defensiva', 1), (28, 'Una montaña', 0), (28, 'Un río', 0), (28, 'Una ciudad antigua', 0),
(29, 'Neil Armstrong', 1), (29, 'Buzz Aldrin', 0), (29, 'Yuri Gagarin', 0), (29, 'Michael Collins', 0),
(30, 'La toma de la Bastilla', 1), (30, 'El Juramento del Juego de Pelota', 0), (30, 'La Marcha sobre Versalles', 0), (30, 'La Ejecución de Luis XVI', 0),

-- Respuestas para Ciencia (id_categoria: 3)
(31, 'Júpiter', 1), (31, 'Saturno', 0), (31, 'Neptuno', 0), (31, 'Urano', 0),
(32, 'H2O', 1), (32, 'CO2', 0), (32, 'NaCl', 0), (32, 'O2', 0),
(33, 'Oxígeno', 1), (33, 'Nitrógeno', 0), (33, 'Dióxido de carbono', 0), (33, 'Hidrógeno', 0),
(34, 'Isaac Newton', 0), (34, 'Albert Einstein', 1), (34, 'Stephen Hawking', 0), (34, 'Galileo Galilei', 0),
(35, 'Fémur', 1), (35, 'Tibia', 0), (35, 'Húmero', 0), (35, 'Peroné', 0),
(36, 'Energía solar', 1), (36, 'Energía eólica', 0), (36, 'Energía nuclear', 0), (36, 'Energía geotérmica', 0),
(37, 'Oxígeno', 0), (37, 'Silicio', 0), (37, 'Hierro', 1), (37, 'Aluminio', 0),
(38, '3', 0), (38, '4', 0), (38, 'Plasma', 1), (38, 'Condensado de Bose-Einstein', 0),
(39, 'Región del espacio con una gravedad tan fuerte que nada puede escapar', 1), (39, 'Una galaxia', 0), (39, 'Un planeta', 0), (39, 'Una nebulosa', 0),
(40, 'Thomas Edison', 1), (40, 'Nikola Tesla', 0), (40, 'Benjamin Franklin', 0), (40, 'Alessandro Volta', 0),
(41, '299,792,458 metros por segundo', 1), (41, '300,000 kilómetros por segundo', 0), (41, '150,000 millas por segundo', 0), (41, 'Un millón de kilómetros por segundo', 0),
(42, 'Hojas', 1), (42, 'Raíces', 0), (42, 'Flores', 0), (42, 'Tallos', 0),
(43, 'Los seres vivos', 1), (43, 'Los planetas', 0), (43, 'Los minerales', 0), (43, 'Las rocas', 0),
(44, 'Gravedad', 1), (44, 'Magnetismo', 0), (44, 'Fricción', 0), (44, 'Electromagnetismo', 0),
(45, 'Ácido Desoxirribonucleico', 1), (45, 'Ácido Dinitrobencénico', 0), (45, 'Ácido Deoxirribonucleico', 0), (45, 'Ácido Ribonucleico', 0),

-- Respuestas para Entretenimiento (id_categoria: 4)
(46, 'Chris Evans', 0), (46, 'Robert Downey Jr.', 1), (46, 'Mark Ruffalo', 0), (46, 'Chris Hemsworth', 0),
(47, 'Avatar', 1), (47, 'Avengers: Endgame', 0), (47, 'Titanic', 0), (47, 'Star Wars: El despertar de la Fuerza', 0),
(48, 'The Walking Dead', 0), (48, 'Game of Thrones', 1), (48, 'Breaking Bad', 0), (48, 'Friends', 0),
(49, 'Rihanna', 0), (49, 'Beyoncé', 0), (49, 'Adele', 1), (49, 'Taylor Swift', 0),
(50, '1985', 1), (50, '1980', 0), (50, '1990', 0), (50, '1995', 0),
(51, 'Steven Spielberg', 0), (51, 'James Cameron', 1), (51, 'Christopher Nolan', 0), (51, 'Martin Scorsese', 0),
(52, 'Ron Weasley', 0), (52, 'Harry Potter', 1), (52, 'Hermione Granger', 0), (52, 'Draco Malfoy', 0),
(53, 'Queen', 0), (53, 'The Beatles', 0), (53, 'Michael Jackson', 1), (53, 'Madonna', 0),
(54, 'Lewis Carroll', 1), (54, 'J.K. Rowling', 0), (54, 'Roald Dahl', 0), (54, 'Hans Christian Andersen', 0),
(55, 'Pato Donald', 0), (55, 'Mickey Mouse', 1), (55, 'Goofy', 0), (55, 'Pluto', 0),
(56, 'Sean Connery', 1), (56, 'Daniel Craig', 0), (56, 'Roger Moore', 0), (56, 'Pierce Brosnan', 0),
(57, 'Capitán América', 0), (57, 'Thor', 1), (57, 'Hulk', 0), (57, 'Spider-Man', 0),
(58, 'Friends', 0), (58, 'Los Simpson', 0), (58, 'Game of Thrones', 1), (58, 'Seinfeld', 0),
(59, 'Madonna', 1), (59, 'Britney Spears', 0), (59, 'Lady Gaga', 0), (59, 'Whitney Houston', 0),
(60, 'París', 0), (60, 'Berlín', 0), (60, 'Cannes', 1), (60, 'Venecia', 0),

-- Respuestas para Geografía (id_categoria: 5)
(61, 'Amazonas', 1), (61, 'Nilo', 0), (61, 'Yangtsé', 0), (61, 'Misisipi', 0),
(62, 'Londres', 0), (62, 'Roma', 0), (62, 'París', 1), (62, 'Madrid', 0),
(63, 'Atlántico', 0), (63, 'Índico', 0), (63, 'Pacífico', 1), (63, 'Ártico', 0),
(64, 'Asia', 0), (64, 'América', 0), (64, 'África', 1), (64, 'Oceanía', 0),
(65, 'K2', 0), (65, 'Monte Everest', 1), (65, 'Kilimanjaro', 0), (65, 'Aconcagua', 0),
(66, 'China', 0), (66, 'Corea del Sur', 0), (66, 'Japón', 1), (66, 'Tailandia', 0),
(67, 'Mar Caspio', 0), (67, 'Lago Baikal', 1), (67, 'Lago Superior', 0), (67, 'Lago Victoria', 0),
(68, 'India', 0), (68, 'Estados Unidos', 0), (68, 'China', 1), (68, 'Indonesia', 0),
(69, 'Estrecho de Magallanes', 0), (69, 'Estrecho de Bering', 1), (69, 'Estrecho de Gibraltar', 0), (69, 'Estrecho de Malaca', 0),
(70, 'Brasil', 0), (70, 'Australia', 1), (70, 'Sudáfrica', 0), (70, 'Indonesia', 0),

-- Respuestas para Arte (id_categoria: 6)
(71, 'Vincent van Gogh', 0), (71, 'Leonardo da Vinci', 1), (71, 'Pablo Picasso', 0), (71, 'Michelangelo', 0),
(72, 'Museo del Louvre', 1), (72, 'Museo Británico', 0), (72, 'Museo del Prado', 0), (72, 'Galería Uffizi', 0),
(73, 'Cubismo', 0), (73, 'Impresionismo', 1), (73, 'Surrealismo', 0), (73, 'Barroco', 0),
(74, 'Donatello', 0), (74, 'Miguel Ángel', 1), (74, 'Rodin', 0), (74, 'Bernini', 0),
(75, 'La Noche Estrellada', 0), (75, 'El Grito', 1), (75, 'Guernica', 0), (75, 'La persistencia de la memoria', 0),

-- Respuestas para Tecnología (id_categoria: 7)
(76, 'Hyper Text Markup Language', 1), (76, 'High Tech Modern Language', 0), (76, 'Home Tool Markup Language', 0), (76, 'Hyperlink and Text Markup Language', 0),
(77, 'Bill Gates', 0), (77, 'Steve Jobs', 1), (77, 'Mark Zuckerberg', 0), (77, 'Jeff Bezos', 0),
(78, 'Una secuencia de pasos para resolver un problema', 1), (78, 'Un programa de computadora', 0), (78, 'Un lenguaje de programación', 0), (78, 'Una base de datos', 0),
(79, 'macOS', 0), (79, 'Linux', 0), (79, 'Windows', 1), (79, 'Android', 0),
(80, 'Simulación de inteligencia humana por máquinas', 1), (80, 'Un tipo de robot', 0), (80, 'Un nuevo lenguaje de programación', 0), (80, 'Una red neuronal', 0),

-- Respuestas para Música (id_categoria: 8)
(81, 'Elvis Presley', 0), (81, 'Michael Jackson', 1), (81, 'Freddy Mercury', 0), (81, 'Prince', 0),
(82, '5', 0), (82, '7', 1), (82, '8', 0), (82, '6', 0),
(83, 'Órgano de tubos', 1), (83, 'Piano', 0), (83, 'Batería', 0), (83, 'Guitarra', 0),
(84, 'Ludwig van Beethoven', 0), (84, 'Antonio Vivaldi', 1), (84, 'Wolfgang Amadeus Mozart', 0), (84, 'Johann Sebastian Bach', 0),
(85, 'Reggaeton', 0), (85, 'Salsa', 0), (85, 'Reggae', 1), (85, 'Cumbia', 0),

-- Respuestas para Literatura (id_categoria: 9)
(86, 'Gabriel García Márquez', 0), (86, 'Miguel de Cervantes Saavedra', 1), (86, 'Federico García Lorca', 0), (86, 'Jorge Luis Borges', 0),
(87, 'Romeo y Julieta', 1), (87, 'Hamlet', 0), (87, 'Macbeth', 0), (87, 'Otelo', 0),
(88, 'Realismo mágico', 1), (88, 'Ciencia ficción', 0), (88, 'Fantasía', 0), (88, 'Novela histórica', 0),
(89, 'Antoine de Saint-Exupéry', 1), (89, 'J.K. Rowling', 0), (89, 'Lewis Carroll', 0), (89, 'Julio Verne', 0),
(90, 'Historia de dos ciudades', 1), (90, 'Orgullo y prejuicio', 0), (90, 'Moby Dick', 0), (90, 'Grandes esperanzas', 0),

-- Respuestas para Gastronomía (id_categoria: 10)
(91, 'Pescado', 0), (91, 'Arroz', 1), (91, 'Algas', 0), (91, 'Verduras', 0),
(92, 'Grecia', 0), (92, 'Italia', 1), (92, 'España', 0), (92, 'Francia', 0),
(93, 'Cheddar', 0), (93, 'Parmesano', 1), (93, 'Mozzarella', 0), (93, 'Gouda', 0),
(94, 'Azafrán', 1), (94, 'Vainilla', 0), (94, 'Cardamomo', 0), (94, 'Canela', 0),
(95, 'Cerveza', 0), (95, 'Tequila', 1), (95, 'Vino', 0), (95, 'Mezcal', 0),
(96, 'Quinto sabor básico', 1), (96, 'Una especia', 0), (96, 'Un tipo de plato', 0), (96, 'Una técnica de cocción', 0),
(97, 'China', 0), (97, 'Vietnam', 0), (97, 'Tailandia', 1), (97, 'Corea', 0),
(98, 'Harina', 0), (98, 'Patata', 1), (98, 'Huevo', 0), (98, 'Cebolla', 0),
(99, 'Farfalle', 1), (99, 'Spaghetti', 0), (99, 'Macarrones', 0), (99, 'Penner', 0),
(100, 'Vodka', 0), (100, 'Cerveza', 1), (100, 'Vino', 0), (100, 'Ron', 0),
(101, 'Mango', 0), (101, 'Aguacate', 1), (101, 'Tomate', 0), (101, 'Cebolla', 0),
(102, 'Brasil', 0), (102, 'Etiopía', 1), (102, 'Colombia', 0), (102, 'Vietnam', 0),
(103, 'Res', 0), (103, 'Cerdo', 1), (103, 'Pollo', 0), (103, 'Cordero', 0),
(104, 'Gordon Ramsay', 1), (104, 'Jamie Oliver', 0), (104, 'Wolfgang Puck', 0), (104, 'Anthony Bourdain', 0),
(105, 'Cúrcuma', 1), (105, 'Pimentón', 0), (105, 'Comino', 0), (105, 'Jengibre', 0);