-- ============================================================
-- DATABASE COMPLETO BIGLIETTERIA MUSEO
-- Unione di:
-- 1) biglietteria_museo.sql
-- 2) aggiornamento_database.sql
-- 3) aggiornamento_database_docenti.sql
--
-- Questo file è eseguibile direttamente su MySQL / MariaDB.
-- Importandolo vengono ricreate tutte le tabelle e inseriti i dati demo.
-- ATTENZIONE: le tabelle esistenti con gli stessi nomi vengono eliminate.
-- ============================================================

CREATE DATABASE IF NOT EXISTS biglietteria_museo
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE biglietteria_museo;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Biglietti_Servizi;
DROP TABLE IF EXISTS Biglietti;
DROP TABLE IF EXISTS Ordini;
DROP TABLE IF EXISTS Servizi_Opzionali;
DROP TABLE IF EXISTS Tariffe;
DROP TABLE IF EXISTS Categorie_Riduzione;
DROP TABLE IF EXISTS Fasce_Orarie;
DROP TABLE IF EXISTS Esposizioni;
DROP TABLE IF EXISTS Utenti;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- 1. UTENTI
-- ------------------------------------------------------------
CREATE TABLE Utenti (
  id_utente     INT            NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(50)    NOT NULL,
  cognome       VARCHAR(50)    NOT NULL,
  email         VARCHAR(100)   NOT NULL,
  password_hash VARCHAR(255)   NOT NULL,
  domanda_sicurezza VARCHAR(100) NULL,
  risposta_sicurezza_hash VARCHAR(255) NULL,
  ruolo         ENUM('visitatore','operatore','cassiere','amministratore') NOT NULL DEFAULT 'visitatore',
  email_verificata TINYINT(1) NOT NULL DEFAULT 0,
  codice_verifica_email CHAR(6) NULL,
  codice_verifica_scadenza DATETIME NULL,
  data_registrazione DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id_utente),
  UNIQUE KEY uq_email (email)
);

-- ------------------------------------------------------------
-- 2. ESPOSIZIONI
-- ------------------------------------------------------------
CREATE TABLE Esposizioni (
  id_esposizione INT           NOT NULL AUTO_INCREMENT,
  titolo         VARCHAR(150)  NOT NULL,
  descrizione    TEXT,
  data_inizio    DATE          NOT NULL,
  data_fine      DATE          NOT NULL,
  stato          ENUM('Bozza','Pubblicata','Conclusa','Annullata') NOT NULL DEFAULT 'Bozza',

  PRIMARY KEY (id_esposizione),
  CONSTRAINT chk_date_esposizione CHECK (data_fine >= data_inizio)
);

-- ------------------------------------------------------------
-- 3. FASCE ORARIE
-- ------------------------------------------------------------
CREATE TABLE Fasce_Orarie (
  id_fascia        INT      NOT NULL AUTO_INCREMENT,
  id_esposizione   INT      NOT NULL,
  data             DATE     NOT NULL,
  ora_ingresso     TIME     NOT NULL,
  capienza_massima SMALLINT NOT NULL,

  PRIMARY KEY (id_fascia),
  UNIQUE KEY uq_fascia (id_esposizione, data, ora_ingresso),

  CONSTRAINT fk_fascia_esposizione
    FOREIGN KEY (id_esposizione)
    REFERENCES Esposizioni (id_esposizione)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_capienza_fascia CHECK (capienza_massima > 0)
);

-- ------------------------------------------------------------
-- 4. CATEGORIE RIDUZIONE
-- ------------------------------------------------------------
CREATE TABLE Categorie_Riduzione (
  id_categoria       INT           NOT NULL AUTO_INCREMENT,
  nome               VARCHAR(80)   NOT NULL,
  percentuale_sconto DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  documento_richiesto VARCHAR(150),

  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_nome_categoria (nome),

  CONSTRAINT chk_sconto CHECK (percentuale_sconto >= 0 AND percentuale_sconto <= 100)
);

-- ------------------------------------------------------------
-- 5. TARIFFE
-- ------------------------------------------------------------
CREATE TABLE Tariffe (
  id_tariffa     INT                           NOT NULL AUTO_INCREMENT,
  tipo_biglietto ENUM('base','esposizione')    NOT NULL,
  id_categoria   INT                           NOT NULL,
  prezzo         DECIMAL(8,2)                  NOT NULL,

  PRIMARY KEY (id_tariffa),
  UNIQUE KEY uq_tariffa (tipo_biglietto, id_categoria),

  CONSTRAINT fk_tariffa_categoria
    FOREIGN KEY (id_categoria)
    REFERENCES Categorie_Riduzione (id_categoria)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_prezzo CHECK (prezzo >= 0)
);

-- ------------------------------------------------------------
-- 6. SERVIZI OPZIONALI
-- ------------------------------------------------------------
CREATE TABLE Servizi_Opzionali (
  id_servizio  INT           NOT NULL AUTO_INCREMENT,
  nome         VARCHAR(100)  NOT NULL,
  descrizione  TEXT,
  prezzo       DECIMAL(8,2)  NOT NULL,

  PRIMARY KEY (id_servizio),
  UNIQUE KEY uq_nome_servizio (nome),

  CONSTRAINT chk_prezzo_servizio CHECK (prezzo >= 0)
);

-- ------------------------------------------------------------
-- 7. ORDINI
-- Include anche i campi aggiunti dagli aggiornamenti:
-- codice recupero, pagamento simulato, prenotazione docente e dati scuola.
-- ------------------------------------------------------------
CREATE TABLE Ordini (
  id_ordine      INT           NOT NULL AUTO_INCREMENT,
  id_utente      INT           NULL,
  codice_recupero VARCHAR(20)  NOT NULL,
  nome_cliente   VARCHAR(120)  NULL,
  email_cliente  VARCHAR(120)  NULL,
  data_acquisto  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  importo_totale DECIMAL(10,2) NOT NULL,
  stato_pagamento ENUM('In attesa','Pagato','Annullato','Non pagato') NOT NULL DEFAULT 'Pagato',
  metodo_pagamento ENUM('contanti','carta','paypal') NOT NULL DEFAULT 'carta',

  prenotazione_docente TINYINT(1) NOT NULL DEFAULT 0,
  nome_scuola VARCHAR(150) NULL,
  codice_meccanografico VARCHAR(20) NULL,
  indirizzo_scuola VARCHAR(200) NULL,
  citta_scuola VARCHAR(100) NULL,
  telefono_scuola VARCHAR(30) NULL,
  classe_scuola VARCHAR(50) NULL,
  quantita_studenti INT NULL,
  numero_docenti INT NULL DEFAULT 0,
  note_scuola TEXT NULL,

  PRIMARY KEY (id_ordine),
  UNIQUE KEY uq_codice_recupero (codice_recupero),

  CONSTRAINT fk_ordine_utente
    FOREIGN KEY (id_utente)
    REFERENCES Utenti (id_utente)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

  CONSTRAINT chk_importo CHECK (importo_totale >= 0),
  CONSTRAINT chk_quantita_studenti CHECK (quantita_studenti IS NULL OR quantita_studenti >= 0),
  CONSTRAINT chk_numero_docenti CHECK (numero_docenti IS NULL OR numero_docenti >= 0)
);

-- ------------------------------------------------------------
-- 8. BIGLIETTI
-- ------------------------------------------------------------
CREATE TABLE Biglietti (
  id_biglietto      INT           NOT NULL AUTO_INCREMENT,
  codice_univoco    VARCHAR(36)   NOT NULL,
  id_ordine         INT           NOT NULL,
  tipo              ENUM('base','esposizione') NOT NULL,
  data_validita     DATE          NOT NULL,
  id_fascia         INT           NULL,
  id_categoria      INT           NULL,
  prezzo_lordo      DECIMAL(8,2)  NOT NULL,
  sconto_applicato  DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  stato             ENUM('Valido','Utilizzato','Annullato','Non pagato') NOT NULL DEFAULT 'Valido',
  data_utilizzo     DATETIME,

  PRIMARY KEY (id_biglietto),
  UNIQUE KEY uq_codice (codice_univoco),

  CONSTRAINT fk_biglietto_ordine
    FOREIGN KEY (id_ordine)
    REFERENCES Ordini (id_ordine)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_biglietto_fascia
    FOREIGN KEY (id_fascia)
    REFERENCES Fasce_Orarie (id_fascia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_biglietto_categoria
    FOREIGN KEY (id_categoria)
    REFERENCES Categorie_Riduzione (id_categoria)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_fascia_esposizione CHECK (
    (tipo = 'esposizione' AND id_fascia IS NOT NULL) OR
    (tipo = 'base'        AND id_fascia IS NULL)
  ),

  CONSTRAINT chk_prezzo_lordo CHECK (prezzo_lordo >= 0),
  CONSTRAINT chk_sconto_biglietto CHECK (sconto_applicato >= 0)
);

-- ------------------------------------------------------------
-- 9. BIGLIETTI_SERVIZI
-- ------------------------------------------------------------
CREATE TABLE Biglietti_Servizi (
  id_biglietto     INT          NOT NULL,
  id_servizio      INT          NOT NULL,
  prezzo_snapshot  DECIMAL(8,2) NOT NULL,

  PRIMARY KEY (id_biglietto, id_servizio),

  CONSTRAINT fk_bs_biglietto
    FOREIGN KEY (id_biglietto)
    REFERENCES Biglietti (id_biglietto)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_bs_servizio
    FOREIGN KEY (id_servizio)
    REFERENCES Servizi_Opzionali (id_servizio)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_prezzo_snapshot CHECK (prezzo_snapshot >= 0)
);

-- ============================================================
-- INDICI
-- ============================================================
CREATE INDEX idx_biglietti_data_tipo ON Biglietti (data_validita, tipo, stato);
CREATE INDEX idx_biglietti_fascia ON Biglietti (id_fascia, stato);
CREATE INDEX idx_ordini_utente ON Ordini (id_utente);
CREATE INDEX idx_esposizioni_stato ON Esposizioni (stato, data_inizio, data_fine);
CREATE INDEX idx_fasce_esposizione_data ON Fasce_Orarie (id_esposizione, data);

-- ============================================================
-- DATI DI ESEMPIO
-- Password per gli utenti di esempio: password
-- ============================================================

INSERT INTO Utenti
(nome, cognome, email, password_hash, domanda_sicurezza, risposta_sicurezza_hash, ruolo, email_verificata, codice_verifica_email, codice_verifica_scadenza)
VALUES
('Luca', 'Rossi', 'luca.rossi@email.com', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'Colore preferito?', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'visitatore', 1, NULL, NULL),
('Giulia', 'Bianchi', 'giulia.bianchi@email.com', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'Colore preferito?', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'visitatore', 1, NULL, NULL),
('Marco', 'Verdi', 'marco.verdi@email.com', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'Colore preferito?', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'operatore', 1, NULL, NULL),
('Carla', 'Bianchi', 'cassiere@email.com', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'Colore preferito?', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'cassiere', 1, NULL, NULL),
('Anna', 'Neri', 'anna.neri@email.com', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'Colore preferito?', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'amministratore', 1, NULL, NULL);

INSERT INTO Categorie_Riduzione
(nome, percentuale_sconto, documento_richiesto)
VALUES
('Intero', 0, NULL),
('Studente', 20, 'Tessera studente'),
('Senior', 30, 'Documento identità'),
('Bambino', 50, 'Documento età'),
('Docente accompagnatore', 100, 'Attestazione scuola');

INSERT INTO Esposizioni
(titolo, descrizione, data_inizio, data_fine, stato)
VALUES
('Antico Egitto', 'Civiltà egizia, faraoni, geroglifici e reperti funerari.', '2026-05-15', '2026-08-31', 'Pubblicata'),
('Impero Romano', 'Espansione, cultura, vita quotidiana e grandi opere dell''Impero Romano.', '2026-05-20', '2026-09-30', 'Pubblicata'),
('Medioevo Europeo', 'Castelli, cavalieri, vita nei borghi e trasformazioni sociali medievali.', '2026-06-01', '2026-10-31', 'Pubblicata'),
('Rinascimento Italiano', 'Arte, scienza e innovazione nel Rinascimento italiano.', '2026-06-15', '2026-11-30', 'Pubblicata'),
('Arte Contemporanea', 'Installazioni moderne, linguaggi multimediali e sperimentazioni digitali.', '2026-09-01', '2026-12-31', 'Bozza'),
('Archeologia del Mediterraneo', 'Percorso sui reperti e sulle rotte culturali del Mediterraneo antico.', '2026-02-01', '2026-04-30', 'Conclusa');

INSERT INTO Fasce_Orarie
(id_esposizione, data, ora_ingresso, capienza_massima)
VALUES
(1, '2026-05-20', '09:00:00', 50),
(1, '2026-05-20', '11:00:00', 50),
(1, '2026-05-21', '15:00:00', 45),
(2, '2026-05-22', '10:00:00', 60),
(2, '2026-05-22', '16:00:00', 60),
(3, '2026-06-05', '14:00:00', 40),
(3, '2026-06-06', '10:00:00', 40),
(4, '2026-06-20', '16:00:00', 45),
(4, '2026-06-21', '11:00:00', 45);

INSERT INTO Tariffe
(tipo_biglietto, id_categoria, prezzo)
VALUES
('base', 1, 10.00),
('base', 2, 8.00),
('base', 3, 7.00),
('base', 4, 5.00),
('base', 5, 0.00),
('esposizione', 1, 15.00),
('esposizione', 2, 12.00),
('esposizione', 3, 10.50),
('esposizione', 4, 8.00),
('esposizione', 5, 0.00);

INSERT INTO Servizi_Opzionali
(nome, descrizione, prezzo)
VALUES
('Audioguida', 'Guida audio multilingua.', 3.50),
('Visita guidata', 'Tour con guida esperta.', 8.00),
('Catalogo mostra', 'Libro illustrato della mostra.', 12.00);

INSERT INTO Ordini
(id_utente, codice_recupero, nome_cliente, email_cliente, importo_totale, stato_pagamento, metodo_pagamento, prenotazione_docente)
VALUES
(1, 'ORD-DEMO0001', 'Luca Rossi', 'luca.rossi@email.com', 23.50, 'Pagato', 'carta', 0),
(NULL, 'ORD-DEMO0002', 'Visitatore Demo', 'demo@email.com', 10.00, 'Pagato', 'contanti', 0);

INSERT INTO Biglietti
(codice_univoco, id_ordine, tipo, data_validita, id_fascia, id_categoria, prezzo_lordo, sconto_applicato, stato)
VALUES
('TKT-DEMO0001', 1, 'esposizione', '2026-05-20', 1, 2, 15.00, 3.00, 'Valido'),
('TKT-DEMO0002', 2, 'base', '2026-05-20', NULL, 1, 10.00, 0.00, 'Valido');

INSERT INTO Biglietti_Servizi
(id_biglietto, id_servizio, prezzo_snapshot)
VALUES
(1, 1, 3.50),
(1, 2, 8.00);

-- ============================================================
-- QUERY DI CONTROLLO UTILI, NON ESEGUITE AUTOMATICAMENTE
-- Togli il commento se vuoi usarle.
-- ============================================================

-- SELECT * FROM Utenti;
-- SELECT * FROM Esposizioni;
-- SELECT * FROM Tariffe;
-- SELECT * FROM Servizi_Opzionali;
-- SELECT * FROM Ordini;
-- SELECT * FROM Biglietti;

