
-- ------------------------------------------------------------
-- 1. UTENTI
-- ------------------------------------------------------------
CREATE TABLE Utenti (
  id_utente     INT            NOT NULL AUTO_INCREMENT,
  nome          VARCHAR(50)    NOT NULL,
  cognome       VARCHAR(50)    NOT NULL,
  email         VARCHAR(100)   NOT NULL,
  password_hash VARCHAR(255)   NOT NULL,
  ruolo         ENUM(
                  'visitatore',
                  'operatore',
                  'amministratore'
                )              NOT NULL DEFAULT 'visitatore',
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
  stato          ENUM(
                   'Bozza',
                   'Pubblicata',
                   'Conclusa',
                   'Annullata'
                 )             NOT NULL DEFAULT 'Bozza',

  PRIMARY KEY (id_esposizione),
  CONSTRAINT chk_date_esposizione CHECK (data_fine >= data_inizio)
);

-- ------------------------------------------------------------
-- 3. FASCE ORARIE
-- ------------------------------------------------------------
CREATE TABLE Fasce_Orarie (
  id_fascia        INT     NOT NULL AUTO_INCREMENT,
  id_esposizione   INT     NOT NULL,
  data             DATE    NOT NULL,
  ora_ingresso     TIME    NOT NULL,
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

  CONSTRAINT chk_sconto CHECK (
    percentuale_sconto >= 0 AND percentuale_sconto <= 100
  )
);

-- ------------------------------------------------------------
-- 5. TARIFFE
-- ------------------------------------------------------------
CREATE TABLE Tariffe (
  id_tariffa    INT            NOT NULL AUTO_INCREMENT,
  tipo_biglietto ENUM(
                   'base',
                   'esposizione'
                 )             NOT NULL,
  id_categoria  INT            NOT NULL,
  prezzo        DECIMAL(8,2)   NOT NULL,

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
-- ------------------------------------------------------------
CREATE TABLE Ordini (
  id_ordine      INT           NOT NULL AUTO_INCREMENT,
  id_utente      INT,
  data_acquisto  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  importo_totale DECIMAL(10,2) NOT NULL,

  PRIMARY KEY (id_ordine),

  CONSTRAINT fk_ordine_utente
    FOREIGN KEY (id_utente)
    REFERENCES Utenti (id_utente)
    ON UPDATE CASCADE
    ON DELETE SET NULL,

  CONSTRAINT chk_importo CHECK (importo_totale >= 0)
);

-- ------------------------------------------------------------
-- 8. BIGLIETTI
-- ------------------------------------------------------------
CREATE TABLE Biglietti (
  id_biglietto   INT           NOT NULL AUTO_INCREMENT,
  codice_univoco CHAR(36)      NOT NULL,
  id_ordine      INT           NOT NULL,
  tipo           ENUM(
                   'base',
                   'esposizione'
                 )             NOT NULL,
  data_validita  DATE          NOT NULL,
  id_fascia      INT,
  id_categoria   INT,
  prezzo_lordo   DECIMAL(8,2)  NOT NULL,
  sconto_applicato DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  stato          ENUM(
                   'Valido',
                   'Utilizzato',
                   'Annullato'
                 )             NOT NULL DEFAULT 'Valido',
  data_utilizzo  DATETIME,

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

  CONSTRAINT chk_prezzo_lordo    CHECK (prezzo_lordo    >= 0),
  CONSTRAINT chk_sconto_biglietto CHECK (sconto_applicato >= 0)
);

-- ------------------------------------------------------------
-- 9. BIGLIETTI_SERVIZI  (tabella ponte many-to-many)
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
--  INDICI aggiuntivi per ottimizzare le query frequenti
-- ============================================================

-- Ricerca biglietti per data (capienza base giornaliera)
CREATE INDEX idx_biglietti_data_tipo
  ON Biglietti (data_validita, tipo, stato);

-- Ricerca biglietti per fascia (calcolo posti liberi)
CREATE INDEX idx_biglietti_fascia
  ON Biglietti (id_fascia, stato);

-- Ricerca ordini per utente (storico acquisti)
CREATE INDEX idx_ordini_utente
  ON Ordini (id_utente);

-- Ricerca esposizioni per stato e periodo
CREATE INDEX idx_esposizioni_stato
  ON Esposizioni (stato, data_inizio, data_fine);

-- Ricerca fasce per esposizione e data
CREATE INDEX idx_fasce_esposizione_data
  ON Fasce_Orarie (id_esposizione, data);

-- INSERT --

-- =========================
-- UTENTI
-- =========================
INSERT INTO Utenti (nome, cognome, email, password_hash, ruolo) VALUES
('Luca', 'Rossi', 'luca.rossi@email.com', 'hash1', 'visitatore'),
('Giulia', 'Bianchi', 'giulia.bianchi@email.com', 'hash2', 'visitatore'),
('Marco', 'Verdi', 'marco.verdi@email.com', 'hash3', 'operatore'),
('Anna', 'Neri', 'anna.neri@email.com', 'hash4', 'amministratore');

-- =========================
-- CATEGORIE RIDUZIONE
-- =========================
INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto) VALUES
('Intero', 0, NULL),
('Studente', 20, 'Tessera studente'),
('Senior', 30, 'Documento identità'),
('Bambino', 50, 'Documento età');

-- =========================
-- ESPOSIZIONI (periodi storici)
-- =========================
INSERT INTO Esposizioni (titolo, descrizione, data_inizio, data_fine, stato) VALUES
('Antico Egitto', 'Civiltà egizia e faraoni', '2025-01-10', '2025-06-30', 'Pubblicata'),
('Impero Romano', 'Espansione e cultura romana', '2025-02-01', '2025-08-31', 'Pubblicata'),
('Medioevo Europeo', 'Castelli e cavalieri', '2025-03-01', '2025-09-30', 'Pubblicata'),
('Rinascimento Italiano', 'Arte e innovazione', '2025-04-01', '2025-10-31', 'Pubblicata'),
('Arte Contemporanea', 'Installazioni moderne', '2025-05-01', '2025-12-31', 'Pubblicata');

-- =========================
-- FASCE ORARIE
-- =========================
INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima) VALUES
(1, '2025-05-10', '09:00:00', 50),
(1, '2025-05-10', '11:00:00', 50),
(2, '2025-05-11', '10:00:00', 60),
(3, '2025-05-12', '14:00:00', 40),
(4, '2025-05-13', '16:00:00', 45);

-- =========================
-- TARIFFE
-- =========================
INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo) VALUES
('base', 1, 10.00),
('base', 2, 8.00),
('base', 3, 7.00),
('base', 4, 5.00),
('esposizione', 1, 15.00),
('esposizione', 2, 12.00),
('esposizione', 3, 10.00),
('esposizione', 4, 8.00);

-- =========================
-- SERVIZI OPZIONALI
-- =========================
INSERT INTO Servizi_Opzionali (nome, descrizione, prezzo) VALUES
('Audioguida', 'Guida audio multilingua', 3.50),
('Visita guidata', 'Tour con guida esperta', 8.00),
('Catalogo mostra', 'Libro illustrato', 12.00);

-- =========================
-- ORDINI
-- =========================
INSERT INTO Ordini (id_utente, importo_totale) VALUES
(1, 18.50),
(2, 12.00),
(1, 25.00);

-- =========================
-- BIGLIETTI
-- =========================
INSERT INTO Biglietti 
(codice_univoco, id_ordine, tipo, data_validita, id_fascia, id_categoria, prezzo_lordo, sconto_applicato, stato)
VALUES
('uuid-1', 1, 'esposizione', '2025-05-10', 1, 2, 15.00, 3.00, 'Valido'),
('uuid-2', 2, 'base', '2025-05-10', NULL, 1, 10.00, 0.00, 'Valido'),
('uuid-3', 3, 'esposizione', '2025-05-11', 3, 3, 15.00, 5.00, 'Utilizzato');

-- =========================
-- BIGLIETTI_SERVIZI
-- =========================
INSERT INTO Biglietti_Servizi (id_biglietto, id_servizio, prezzo_snapshot) VALUES
(1, 1, 3.50),
(1, 2, 8.00),
(3, 1, 3.50);








-- QUERY a --
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


-- QUERY b --
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


-- QUERY c --
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


-- QUERY d --
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