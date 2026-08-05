CREATE DATABASE IF NOT EXISTS GoFarmingBD;
USE GoFarmingBD;

CREATE TABLE Usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Plantas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(150),
    foto_url VARCHAR(500),
    frequencia_rega VARCHAR(100),
    access_token_plantid VARCHAR(255),
    criada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id) ON DELETE CASCADE
);

CREATE TABLE Regas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_planta INT NOT NULL,
    data_prevista DATE NOT NULL,
    data_regada DATETIME DEFAULT NULL,
    status ENUM('pendente', 'concluida') DEFAULT 'pendente',
    FOREIGN KEY (id_planta) REFERENCES Plantas(id) ON DELETE CASCADE
);

