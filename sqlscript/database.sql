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

-- Tabla partida_pregunta
DROP TABLE IF EXISTS partida_pregunta;
CREATE TABLE partida_pregunta (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  id_partida INT NOT NULL,
                                  id_pregunta INT NOT NULL,
                                  id_respuesta INT,
                                  respondida_correctamente BOOLEAN,
                                  orden_pregunta INT,
                                  FOREIGN KEY (id_partida) REFERENCES partida(id),
                                  FOREIGN KEY (id_pregunta) REFERENCES pregunta(id),
                                  FOREIGN KEY (id_respuesta) REFERENCES respuesta(id)
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

-- Tabla resumen_partida (sin estado)
DROP TABLE IF EXISTS resumen_partida;
CREATE TABLE resumen_partida (
                                 id_resumen INT AUTO_INCREMENT PRIMARY KEY,
                                 id_partida INT NOT NULL,
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

-- Tabla respuesta
DROP TABLE IF EXISTS pregunta_usuario;
CREATE TABLE pregunta_usuario (
                          id INT(11) PRIMARY KEY AUTO_INCREMENT,
                          id_usuario INT(11) NOT NULL,
                          id_pregunta INT(11) NOT NULL,
                          id_respuesta INT(11) NOT NULL,
                          es_correcta INT(11) NOT NULL
);