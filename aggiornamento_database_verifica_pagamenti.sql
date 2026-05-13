ALTER TABLE Utenti MODIFY ruolo ENUM('visitatore','operatore','cassiere','amministratore') NOT NULL DEFAULT 'visitatore';
-- Aggiornamento database per:
-- 1) verifica email con codice a 6 cifre
-- 2) metodo di pagamento
-- 3) stato biglietto Non pagato per ordini in contanti

ALTER TABLE Utenti
ADD COLUMN email_verificata TINYINT(1) NOT NULL DEFAULT 0 AFTER ruolo,
ADD COLUMN codice_verifica_email CHAR(6) NULL AFTER email_verificata,
ADD COLUMN codice_verifica_scadenza DATETIME NULL AFTER codice_verifica_email;

UPDATE Utenti
SET email_verificata = 1
WHERE email_verificata = 0;

ALTER TABLE Ordini
MODIFY stato_pagamento ENUM('In attesa','Pagato','Annullato','Non pagato') NOT NULL DEFAULT 'Pagato',
ADD COLUMN metodo_pagamento ENUM('contanti','carta','paypal') NOT NULL DEFAULT 'carta' AFTER stato_pagamento;

ALTER TABLE Biglietti
MODIFY stato ENUM('Valido','Utilizzato','Annullato','Non pagato') NOT NULL DEFAULT 'Valido';
