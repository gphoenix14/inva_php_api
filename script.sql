	
-- schema.sql
CREATE DATABASE IF NOT EXISTS api_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE api_demo;
 
CREATE TABLE utenti (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    ruolo      ENUM('admin','utente','moderatore') DEFAULT 'utente',
    attivo     TINYINT(1) DEFAULT 1,
    creato_il  DATETIME DEFAULT CURRENT_TIMESTAMP,
    aggiornato DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
 
CREATE TABLE api_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    utente_id  INT NOT NULL,
    token      VARCHAR(255) NOT NULL UNIQUE,
    scadenza   DATETIME NOT NULL,
    creato_il  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE
);
 
-- Utente admin di esempio
INSERT INTO utenti (nome, email, password, ruolo)
VALUES ('Admin', 'admin@example.com', '$2y$12$hash_bcrypt_qui', 'admin');

