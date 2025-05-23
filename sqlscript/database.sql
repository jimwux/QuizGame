CREATE DATABASE JuegoPreguntas;
USE JuegoPreguntas;

DROP TABLE IF EXISTS `usuarios`;
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
                          lat FLOAT,
                          lng FLOAT
); ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;