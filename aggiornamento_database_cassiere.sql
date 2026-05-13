-- Aggiunge il ruolo Cassiere al sito Museo Storico Severi
ALTER TABLE Utenti
MODIFY ruolo ENUM('visitatore','operatore','cassiere','amministratore') NOT NULL DEFAULT 'visitatore';

-- Utente cassiere di esempio opzionale: password = password
-- Esegui solo se vuoi creare un account cassiere di test.
-- INSERT INTO Utenti (nome, cognome, email, password_hash, domanda_sicurezza, risposta_sicurezza_hash, ruolo, email_verificata)
-- VALUES ('Carla', 'Bianchi', 'cassiere@email.com', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'Colore preferito?', '$2y$12$fs9J0.gYxwVv.ObI0fGzAOO2AslJ0icVnCzBlPYcT/OmscQRcE6OS', 'cassiere', 1);
