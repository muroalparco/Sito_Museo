SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


CREATE TABLE IF NOT EXISTS Utenti (
  id_utente INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(50) NOT NULL,
  cognome VARCHAR(50) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  domanda_sicurezza VARCHAR(100) NULL,
  risposta_sicurezza_hash VARCHAR(255) NULL,
  ruolo ENUM('visitatore','operatore','cassiere','amministratore','tester') NOT NULL DEFAULT 'visitatore',
  email_verificata TINYINT(1) NOT NULL DEFAULT 0,
  codice_verifica_email CHAR(6) NULL,
  codice_verifica_scadenza DATETIME NULL,
  data_registrazione DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  password_reset_code CHAR(6) NULL,
  password_reset_scadenza DATETIME NULL,
  saldo_utente DECIMAL(10,2) NOT NULL DEFAULT 0.00,

  PRIMARY KEY (id_utente),
  UNIQUE KEY uq_utenti_email (email),
  KEY idx_utenti_ruolo (ruolo),
  KEY idx_utenti_email_verificata (email_verificata)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Esposizioni (
  id_esposizione INT NOT NULL AUTO_INCREMENT,
  titolo VARCHAR(150) NOT NULL,
  descrizione TEXT NULL,
  emoji VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  data_inizio DATE NOT NULL,
  data_fine DATE NOT NULL,
  stato ENUM('Bozza','Pubblicata','Conclusa','Annullata') NOT NULL DEFAULT 'Bozza',

  PRIMARY KEY (id_esposizione),
  KEY idx_esposizioni_stato (stato, data_inizio, data_fine),
  KEY idx_esposizioni_date (data_inizio, data_fine),

  CONSTRAINT chk_esposizioni_date CHECK (data_fine >= data_inizio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Categorie_Riduzione (
  id_categoria INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(80) NOT NULL,
  percentuale_sconto DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  documento_richiesto VARCHAR(150) NULL,

  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorie_nome (nome),

  CONSTRAINT chk_categorie_sconto CHECK (percentuale_sconto >= 0 AND percentuale_sconto <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Tariffe (
  id_tariffa INT NOT NULL AUTO_INCREMENT,
  tipo_biglietto ENUM('base','esposizione') NOT NULL,
  id_categoria INT NOT NULL,
  prezzo DECIMAL(8,2) NOT NULL,

  PRIMARY KEY (id_tariffa),
  UNIQUE KEY uq_tariffe_tipo_categoria (tipo_biglietto, id_categoria),
  KEY idx_tariffe_categoria (id_categoria),

  CONSTRAINT fk_tariffe_categoria
    FOREIGN KEY (id_categoria)
    REFERENCES Categorie_Riduzione (id_categoria)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_tariffe_prezzo CHECK (prezzo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Servizi_Opzionali (
  id_servizio INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descrizione TEXT NULL,
  prezzo DECIMAL(8,2) NOT NULL,

  PRIMARY KEY (id_servizio),
  UNIQUE KEY uq_servizi_nome (nome),

  CONSTRAINT chk_servizi_prezzo CHECK (prezzo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Fasce_Orarie (
  id_fascia INT NOT NULL AUTO_INCREMENT,
  id_esposizione INT NOT NULL,
  data DATE NOT NULL,
  ora_ingresso TIME NOT NULL,
  capienza_massima SMALLINT NOT NULL,

  PRIMARY KEY (id_fascia),
  UNIQUE KEY uq_fasce_esposizione_data_ora (id_esposizione, data, ora_ingresso),
  KEY idx_fasce_esposizione_data (id_esposizione, data),

  CONSTRAINT fk_fasce_esposizione
    FOREIGN KEY (id_esposizione)
    REFERENCES Esposizioni (id_esposizione)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_fasce_capienza CHECK (capienza_massima > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Ordini (
  id_ordine INT NOT NULL AUTO_INCREMENT,
  id_utente INT NULL,
  codice_recupero VARCHAR(20) NOT NULL,
  nome_cliente VARCHAR(120) NULL,
  email_cliente VARCHAR(120) NULL,
  data_acquisto DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  importo_totale DECIMAL(10,2) NOT NULL,
  stato_pagamento ENUM('In attesa','Non pagato','Pagato','Rimborsato','Annullato') NOT NULL DEFAULT 'Pagato',
  metodo_pagamento ENUM('contanti','carta','paypal','saldo') NOT NULL DEFAULT 'carta',
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
  richiesta_rimborso TINYINT(1) NOT NULL DEFAULT 0,
  motivo_rimborso TEXT NULL,
  stato_rimborso ENUM('Nessuno','Richiesto','Accettato','Rifiutato') NOT NULL DEFAULT 'Nessuno',
  data_richiesta_rimborso DATETIME NULL,
  data_esito_rimborso DATETIME NULL,

  PRIMARY KEY (id_ordine),
  UNIQUE KEY uq_ordini_codice_recupero (codice_recupero),
  KEY idx_ordini_utente (id_utente),
  KEY idx_ordini_email_cliente (email_cliente),
  KEY idx_ordini_stato_pagamento (stato_pagamento),
  KEY idx_ordini_data_acquisto (data_acquisto),
  KEY idx_ordini_rimborso (richiesta_rimborso, stato_rimborso),

  CONSTRAINT fk_ordini_utente
    FOREIGN KEY (id_utente)
    REFERENCES Utenti (id_utente)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

  CONSTRAINT chk_ordini_importo CHECK (importo_totale >= 0),
  CONSTRAINT chk_ordini_prenotazione_docente CHECK (prenotazione_docente IN (0,1)),
  CONSTRAINT chk_ordini_quantita_studenti CHECK (quantita_studenti IS NULL OR quantita_studenti >= 0),
  CONSTRAINT chk_ordini_numero_docenti CHECK (numero_docenti IS NULL OR numero_docenti >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Biglietti (
  id_biglietto INT NOT NULL AUTO_INCREMENT,
  codice_univoco VARCHAR(36) NOT NULL,
  id_ordine INT NOT NULL,
  tipo ENUM('base','esposizione') NOT NULL,
  data_validita DATE NOT NULL,
  id_fascia INT NULL,
  id_categoria INT NULL,
  prezzo_lordo DECIMAL(8,2) NOT NULL,
  sconto_applicato DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  stato ENUM('Valido','Utilizzato','Annullato','Non pagato') NOT NULL DEFAULT 'Valido',
  data_utilizzo DATETIME NULL,

  PRIMARY KEY (id_biglietto),
  UNIQUE KEY uq_biglietti_codice_univoco (codice_univoco),
  KEY idx_biglietti_ordine (id_ordine),
  KEY idx_biglietti_data_tipo_stato (data_validita, tipo, stato),
  KEY idx_biglietti_fascia_stato (id_fascia, stato),
  KEY idx_biglietti_categoria (id_categoria),

  CONSTRAINT fk_biglietti_ordine
    FOREIGN KEY (id_ordine)
    REFERENCES Ordini (id_ordine)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_biglietti_fascia
    FOREIGN KEY (id_fascia)
    REFERENCES Fasce_Orarie (id_fascia)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_biglietti_categoria
    FOREIGN KEY (id_categoria)
    REFERENCES Categorie_Riduzione (id_categoria)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_biglietti_fascia_tipo CHECK (
    (tipo = 'esposizione' AND id_fascia IS NOT NULL) OR
    (tipo = 'base' AND id_fascia IS NULL)
  ),
  CONSTRAINT chk_biglietti_prezzo_lordo CHECK (prezzo_lordo >= 0),
  CONSTRAINT chk_biglietti_sconto CHECK (sconto_applicato >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Biglietti_Servizi (
  id_biglietto INT NOT NULL,
  id_servizio INT NOT NULL,
  prezzo_snapshot DECIMAL(8,2) NOT NULL,

  PRIMARY KEY (id_biglietto, id_servizio),
  KEY idx_biglietti_servizi_servizio (id_servizio),

  CONSTRAINT fk_biglietti_servizi_biglietto
    FOREIGN KEY (id_biglietto)
    REFERENCES Biglietti (id_biglietto)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT fk_biglietti_servizi_servizio
    FOREIGN KEY (id_servizio)
    REFERENCES Servizi_Opzionali (id_servizio)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,

  CONSTRAINT chk_biglietti_servizi_prezzo CHECK (prezzo_snapshot >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


SET @db_name = DATABASE();

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE Utenti ADD COLUMN saldo_utente DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    'SELECT ''saldo_utente gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Utenti' AND COLUMN_NAME = 'saldo_utente'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) > 0,
    'UPDATE Utenti SET saldo_utente = saldo_portafoglio WHERE saldo_utente = 0 AND saldo_portafoglio > 0',
    'SELECT ''saldo_portafoglio non presente: nessuna copia necessaria'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Utenti' AND COLUMN_NAME = 'saldo_portafoglio'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE Ordini
  MODIFY metodo_pagamento ENUM('contanti','carta','paypal','saldo') NOT NULL DEFAULT 'carta';

ALTER TABLE Ordini
  MODIFY stato_pagamento ENUM('In attesa','Non pagato','Pagato','Rimborsato','Annullato') NOT NULL DEFAULT 'Pagato';

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE Ordini ADD COLUMN richiesta_rimborso TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT ''richiesta_rimborso gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND COLUMN_NAME = 'richiesta_rimborso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE Ordini ADD COLUMN motivo_rimborso TEXT NULL',
    'SELECT ''motivo_rimborso gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND COLUMN_NAME = 'motivo_rimborso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE Ordini ADD COLUMN stato_rimborso ENUM(''Nessuno'',''Richiesto'',''Accettato'',''Rifiutato'') NOT NULL DEFAULT ''Nessuno''',
    'SELECT ''stato_rimborso gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND COLUMN_NAME = 'stato_rimborso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE Ordini ADD COLUMN data_richiesta_rimborso DATETIME NULL',
    'SELECT ''data_richiesta_rimborso gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND COLUMN_NAME = 'data_richiesta_rimborso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE Ordini ADD COLUMN data_esito_rimborso DATETIME NULL',
    'SELECT ''data_esito_rimborso gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND COLUMN_NAME = 'data_esito_rimborso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

ALTER TABLE Ordini
  MODIFY stato_rimborso ENUM('Nessuno','Richiesto','Approvato','Accettato','Rifiutato') NULL DEFAULT 'Nessuno';

UPDATE Ordini
SET stato_rimborso = 'Accettato'
WHERE stato_rimborso = 'Approvato';

UPDATE Ordini
SET stato_rimborso = 'Nessuno'
WHERE stato_rimborso IS NULL OR stato_rimborso = '';

UPDATE Ordini
SET richiesta_rimborso = 0
WHERE richiesta_rimborso IS NULL;

ALTER TABLE Ordini
  MODIFY stato_rimborso ENUM('Nessuno','Richiesto','Accettato','Rifiutato') NOT NULL DEFAULT 'Nessuno';

SET @sql = (
  SELECT IF(
    COUNT(*) > 0,
    'UPDATE Ordini SET richiesta_rimborso = 1 WHERE rimborso_richiesto = 1 AND richiesta_rimborso = 0',
    'SELECT ''rimborso_richiesto non presente: nessuna copia necessaria'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND COLUMN_NAME = 'rimborso_richiesto'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'CREATE INDEX idx_ordini_rimborso ON Ordini (richiesta_rimborso, stato_rimborso)',
    'SELECT ''idx_ordini_rimborso gia presente'' AS messaggio'
  )
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'Ordini' AND INDEX_NAME = 'idx_ordini_rimborso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO Utenti (nome, cognome, email, password_hash, ruolo, email_verificata, saldo_utente) VALUES
('Luca', 'Rossi', 'luca.rossi@email.com', '$2y$12$pN/XslQLg7HLYgQHtkF4uuGIBJC0Ukr1ID0o5/4uC2bDC6PztHae6', 'visitatore', 1, 0.00),
('Giulia', 'Bianchi', 'giulia.bianchi@email.com', '$2y$12$pN/XslQLg7HLYgQHtkF4uuGIBJC0Ukr1ID0o5/4uC2bDC6PztHae6', 'visitatore', 1, 0.00),
('Marco', 'Verdi', 'marco.verdi@email.com', '$2y$12$pN/XslQLg7HLYgQHtkF4uuGIBJC0Ukr1ID0o5/4uC2bDC6PztHae6', 'operatore', 1, 0.00),
('Carla', 'Gialli', 'carla.gialli@email.com', '$2y$12$pN/XslQLg7HLYgQHtkF4uuGIBJC0Ukr1ID0o5/4uC2bDC6PztHae6', 'cassiere', 1, 0.00),
('Anna', 'Neri', 'anna.neri@email.com', '$2y$12$pN/XslQLg7HLYgQHtkF4uuGIBJC0Ukr1ID0o5/4uC2bDC6PztHae6', 'amministratore', 1, 0.00),
('Test', 'Museo', 'tester@museo.it', '$2y$12$pN/XslQLg7HLYgQHtkF4uuGIBJC0Ukr1ID0o5/4uC2bDC6PztHae6', 'tester', 1, 50.00)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), cognome = VALUES(cognome), ruolo = VALUES(ruolo), email_verificata = 1;

INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto) VALUES
('Intero', 0.00, NULL),
('Studente', 20.00, 'Tessera studente'),
('Senior', 30.00, 'Documento identità'),
('Bambino', 50.00, 'Documento età')
ON DUPLICATE KEY UPDATE percentuale_sconto = VALUES(percentuale_sconto), documento_richiesto = VALUES(documento_richiesto);

INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato)
SELECT 'Antico Egitto', 'Civiltà egizia e faraoni', '🏺', '2026-01-10', '2026-06-30', 'Pubblicata'
WHERE NOT EXISTS (SELECT 1 FROM Esposizioni WHERE titolo = 'Antico Egitto');

UPDATE Esposizioni SET descrizione = 'Civiltà egizia e faraoni', emoji = '🏺', data_inizio = '2026-01-10', data_fine = '2026-06-30', stato = 'Pubblicata' WHERE titolo = 'Antico Egitto';

INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato)
SELECT 'Impero Romano', 'Espansione e cultura romana', '🏛️', '2026-02-01', '2026-08-31', 'Pubblicata'
WHERE NOT EXISTS (SELECT 1 FROM Esposizioni WHERE titolo = 'Impero Romano');

UPDATE Esposizioni SET descrizione = 'Espansione e cultura romana', emoji = '🏛️', data_inizio = '2026-02-01', data_fine = '2026-08-31', stato = 'Pubblicata' WHERE titolo = 'Impero Romano';

INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato)
SELECT 'Medioevo Europeo', 'Castelli e cavalieri', '🏰', '2026-03-01', '2026-09-30', 'Pubblicata'
WHERE NOT EXISTS (SELECT 1 FROM Esposizioni WHERE titolo = 'Medioevo Europeo');

UPDATE Esposizioni SET descrizione = 'Castelli e cavalieri', emoji = '🏰', data_inizio = '2026-03-01', data_fine = '2026-09-30', stato = 'Pubblicata' WHERE titolo = 'Medioevo Europeo';

INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato)
SELECT 'Rinascimento Italiano', 'Arte e innovazione', '🎨', '2026-04-01', '2026-10-31', 'Pubblicata'
WHERE NOT EXISTS (SELECT 1 FROM Esposizioni WHERE titolo = 'Rinascimento Italiano');

UPDATE Esposizioni SET descrizione = 'Arte e innovazione', emoji = '🎨', data_inizio = '2026-04-01', data_fine = '2026-10-31', stato = 'Pubblicata' WHERE titolo = 'Rinascimento Italiano';

INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato)
SELECT 'Arte Contemporanea', 'Installazioni moderne', '🖼️', '2026-05-01', '2026-12-31', 'Pubblicata'
WHERE NOT EXISTS (SELECT 1 FROM Esposizioni WHERE titolo = 'Arte Contemporanea');

UPDATE Esposizioni SET descrizione = 'Installazioni moderne', emoji = '🖼️', data_inizio = '2026-05-01', data_fine = '2026-12-31', stato = 'Pubblicata' WHERE titolo = 'Arte Contemporanea';

INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima)
SELECT id_esposizione, '2026-05-10', '09:00:00', 50 FROM Esposizioni WHERE titolo = 'Antico Egitto'
ON DUPLICATE KEY UPDATE capienza_massima = VALUES(capienza_massima);

INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima)
SELECT id_esposizione, '2026-05-10', '11:00:00', 50 FROM Esposizioni WHERE titolo = 'Antico Egitto'
ON DUPLICATE KEY UPDATE capienza_massima = VALUES(capienza_massima);

INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima)
SELECT id_esposizione, '2026-05-11', '10:00:00', 60 FROM Esposizioni WHERE titolo = 'Impero Romano'
ON DUPLICATE KEY UPDATE capienza_massima = VALUES(capienza_massima);

INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima)
SELECT id_esposizione, '2026-05-12', '14:00:00', 40 FROM Esposizioni WHERE titolo = 'Medioevo Europeo'
ON DUPLICATE KEY UPDATE capienza_massima = VALUES(capienza_massima);

INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima)
SELECT id_esposizione, '2026-05-13', '16:00:00', 45 FROM Esposizioni WHERE titolo = 'Rinascimento Italiano'
ON DUPLICATE KEY UPDATE capienza_massima = VALUES(capienza_massima);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'base', id_categoria, 10.00 FROM Categorie_Riduzione WHERE nome = 'Intero'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'base', id_categoria, 8.00 FROM Categorie_Riduzione WHERE nome = 'Studente'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'base', id_categoria, 7.00 FROM Categorie_Riduzione WHERE nome = 'Senior'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'base', id_categoria, 5.00 FROM Categorie_Riduzione WHERE nome = 'Bambino'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'esposizione', id_categoria, 15.00 FROM Categorie_Riduzione WHERE nome = 'Intero'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'esposizione', id_categoria, 12.00 FROM Categorie_Riduzione WHERE nome = 'Studente'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'esposizione', id_categoria, 10.00 FROM Categorie_Riduzione WHERE nome = 'Senior'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'esposizione', id_categoria, 8.00 FROM Categorie_Riduzione WHERE nome = 'Bambino'
ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo);

INSERT INTO Servizi_Opzionali (nome, descrizione, prezzo) VALUES
('Audioguida', 'Guida audio multilingua', 3.50),
('Visita guidata', 'Tour con guida esperta', 8.00),
('Catalogo mostra', 'Libro illustrato', 12.00)
ON DUPLICATE KEY UPDATE descrizione = VALUES(descrizione), prezzo = VALUES(prezzo);

INSERT INTO Ordini (id_utente, codice_recupero, nome_cliente, email_cliente, importo_totale, stato_pagamento, metodo_pagamento, richiesta_rimborso, stato_rimborso) VALUES
((SELECT id_utente FROM Utenti WHERE email = 'luca.rossi@email.com' LIMIT 1), 'ORD-DEMO001', 'Luca Rossi', 'luca.rossi@email.com', 23.50, 'Pagato', 'carta', 0, 'Nessuno'),
((SELECT id_utente FROM Utenti WHERE email = 'giulia.bianchi@email.com' LIMIT 1), 'ORD-DEMO002', 'Giulia Bianchi', 'giulia.bianchi@email.com', 10.00, 'Pagato', 'paypal', 0, 'Nessuno'),
((SELECT id_utente FROM Utenti WHERE email = 'luca.rossi@email.com' LIMIT 1), 'ORD-DEMO003', 'Luca Rossi', 'luca.rossi@email.com', 13.50, 'Pagato', 'saldo', 0, 'Nessuno')
ON DUPLICATE KEY UPDATE id_utente = VALUES(id_utente), nome_cliente = VALUES(nome_cliente), email_cliente = VALUES(email_cliente), importo_totale = VALUES(importo_totale), stato_pagamento = VALUES(stato_pagamento), metodo_pagamento = VALUES(metodo_pagamento);

INSERT INTO Biglietti (codice_univoco, id_ordine, tipo, data_validita, id_fascia, id_categoria, prezzo_lordo, sconto_applicato, stato) VALUES
('TKT-DEMO0001', (SELECT id_ordine FROM Ordini WHERE codice_recupero = 'ORD-DEMO001' LIMIT 1), 'esposizione', '2026-05-10', (SELECT f.id_fascia FROM Fasce_Orarie f JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione WHERE e.titolo = 'Antico Egitto' AND f.data = '2026-05-10' AND f.ora_ingresso = '09:00:00' LIMIT 1), (SELECT id_categoria FROM Categorie_Riduzione WHERE nome = 'Studente' LIMIT 1), 15.00, 3.00, 'Valido'),
('TKT-DEMO0002', (SELECT id_ordine FROM Ordini WHERE codice_recupero = 'ORD-DEMO002' LIMIT 1), 'base', '2026-05-10', NULL, (SELECT id_categoria FROM Categorie_Riduzione WHERE nome = 'Intero' LIMIT 1), 10.00, 0.00, 'Valido'),
('TKT-DEMO0003', (SELECT id_ordine FROM Ordini WHERE codice_recupero = 'ORD-DEMO003' LIMIT 1), 'esposizione', '2026-05-11', (SELECT f.id_fascia FROM Fasce_Orarie f JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione WHERE e.titolo = 'Impero Romano' AND f.data = '2026-05-11' AND f.ora_ingresso = '10:00:00' LIMIT 1), (SELECT id_categoria FROM Categorie_Riduzione WHERE nome = 'Senior' LIMIT 1), 15.00, 5.00, 'Utilizzato')
ON DUPLICATE KEY UPDATE id_ordine = VALUES(id_ordine), tipo = VALUES(tipo), data_validita = VALUES(data_validita), id_fascia = VALUES(id_fascia), id_categoria = VALUES(id_categoria), prezzo_lordo = VALUES(prezzo_lordo), sconto_applicato = VALUES(sconto_applicato), stato = VALUES(stato);

INSERT IGNORE INTO Biglietti_Servizi (id_biglietto, id_servizio, prezzo_snapshot)
SELECT b.id_biglietto, s.id_servizio, 3.50 FROM Biglietti b JOIN Servizi_Opzionali s ON s.nome = 'Audioguida' WHERE b.codice_univoco = 'TKT-DEMO0001';

INSERT IGNORE INTO Biglietti_Servizi (id_biglietto, id_servizio, prezzo_snapshot)
SELECT b.id_biglietto, s.id_servizio, 8.00 FROM Biglietti b JOIN Servizi_Opzionali s ON s.nome = 'Visita guidata' WHERE b.codice_univoco = 'TKT-DEMO0001';

INSERT IGNORE INTO Biglietti_Servizi (id_biglietto, id_servizio, prezzo_snapshot)
SELECT b.id_biglietto, s.id_servizio, 3.50 FROM Biglietti b JOIN Servizi_Opzionali s ON s.nome = 'Audioguida' WHERE b.codice_univoco = 'TKT-DEMO0003';

SET FOREIGN_KEY_CHECKS = 1;






--- QUERY a ---
SELECT
  e.titolo                          AS esposizione,
  COALESCE(cr.nome, 'Ordinario')    AS categoria,
  COUNT(b.id_biglietto)             AS biglietti_venduti,
  SUM(b.sconto_applicato)           AS totale_sconti

FROM Esposizioni e
JOIN Fasce_Orarie fo
  ON fo.id_esposizione = e.id_esposizione
JOIN Biglietti b
  ON b.id_fascia = fo.id_fascia
  AND b.stato != 'Annullato'
LEFT JOIN Categorie_Riduzione cr
  ON cr.id_categoria = b.id_categoria

WHERE e.stato = 'Pubblicata'
  AND e.data_inizio >= '2025-01-01'
  AND e.data_fine   <= '2025-12-31'

GROUP BY e.id_esposizione, e.titolo, cr.id_categoria, cr.nome
ORDER BY e.titolo, biglietti_venduti DESC;


--- QUERY b ---
SELECT
  e.titolo                                        AS esposizione,
  fo.data,
  fo.ora_ingresso,
  fo.capienza_massima,
  COUNT(b.id_biglietto)                           AS biglietti_venduti,

  ROUND(
    COUNT(b.id_biglietto) * 100.0 / fo.capienza_massima
  , 1)                                            AS perc_riempimento,

  ROUND(
    AVG(b.prezzo_lordo - b.sconto_applicato 
        + COALESCE(servizi.totale_servizi, 0))
  , 2)                                            AS ricavo_medio,

  CASE
    WHEN COUNT(b.id_biglietto) * 100.0 
         / fo.capienza_massima > 80 THEN 'SI'
    ELSE 'NO'
  END                                             AS sopra_80_percent

FROM Fasce_Orarie fo
JOIN Esposizioni e
  ON e.id_esposizione = fo.id_esposizione
LEFT JOIN Biglietti b
  ON b.id_fascia = fo.id_fascia
  AND b.stato != 'Annullato'
LEFT JOIN (
  SELECT id_biglietto, SUM(prezzo_snapshot) AS totale_servizi
  FROM Biglietti_Servizi
  GROUP BY id_biglietto
) servizi ON servizi.id_biglietto = b.id_biglietto

GROUP BY fo.id_fascia, e.titolo, fo.data, fo.ora_ingresso, fo.capienza_massima
ORDER BY perc_riempimento DESC;


--- QUERY c ---
SELECT
  e.titolo                              AS esposizione,

  COALESCE(SUM(
    b.prezzo_lordo - b.sconto_applicato
  ), 0)                                 AS ricavo_biglietti,

  COALESCE(SUM(sv.totale_servizi), 0)   AS ricavo_servizi,

  COALESCE(SUM(
    b.prezzo_lordo - b.sconto_applicato
  ), 0)
  + COALESCE(SUM(sv.totale_servizi), 0) AS ricavo_totale

FROM Esposizioni e
LEFT JOIN Fasce_Orarie fo
  ON fo.id_esposizione = e.id_esposizione
LEFT JOIN Biglietti b
  ON b.id_fascia = fo.id_fascia
  AND b.stato != 'Annullato'
LEFT JOIN (
  SELECT id_biglietto, SUM(prezzo_snapshot) AS totale_servizi
  FROM Biglietti_Servizi
  GROUP BY id_biglietto
) sv ON sv.id_biglietto = b.id_biglietto

GROUP BY e.id_esposizione, e.titolo
ORDER BY ricavo_totale DESC;


--- QUERY d ---
SELECT
  CASE DAYOFWEEK(b.data_validita)
    WHEN 1 THEN 'Domenica'
    WHEN 2 THEN 'Lunedì'
    WHEN 3 THEN 'Martedì'
    WHEN 4 THEN 'Mercoledì'
    WHEN 5 THEN 'Giovedì'
    WHEN 6 THEN 'Venerdì'
    WHEN 7 THEN 'Sabato'
  END                                         AS giorno_settimana,

  COUNT(b.id_biglietto)                       AS totale_biglietti,
  COUNT(DISTINCT b.data_validita)             AS giorni_distinti,

  ROUND(
    COUNT(b.id_biglietto) 
    / COUNT(DISTINCT b.data_validita)
  , 1)                                        AS media_giornaliera

FROM Biglietti b
WHERE b.tipo = 'base'
  AND b.stato != 'Annullato'
  AND b.data_validita BETWEEN '2025-01-01' AND '2025-12-31'

GROUP BY DAYOFWEEK(b.data_validita)
ORDER BY DAYOFWEEK(b.data_validita);
