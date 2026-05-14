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

  PRIMARY KEY (id_utente),
  UNIQUE KEY uq_utenti_email (email),
  KEY idx_utenti_ruolo (ruolo),
  KEY idx_utenti_email_verificata (email_verificata)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Esposizioni (
  id_esposizione INT NOT NULL AUTO_INCREMENT,
  titolo VARCHAR(150) NOT NULL,
  descrizione TEXT NULL,
  emoji VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '🏛️',
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
  UNIQUE KEY uq_ordini_codice_recupero (codice_recupero),
  KEY idx_ordini_utente (id_utente),
  KEY idx_ordini_email_cliente (email_cliente),
  KEY idx_ordini_stato_pagamento (stato_pagamento),
  KEY idx_ordini_data_acquisto (data_acquisto),

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
