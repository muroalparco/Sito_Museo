ALTER TABLE Utenti
ADD domanda_sicurezza VARCHAR(100) NULL AFTER password_hash,
ADD risposta_sicurezza_hash VARCHAR(255) NULL AFTER domanda_sicurezza;
