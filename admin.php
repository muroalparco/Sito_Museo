<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/app_mailer.php';

requireAdmin();

$pageTitle = 'Vista amministratore';
$pdo = getDB();
$successMsg = '';
$errorMsg = '';
$stati = ['Bozza', 'Pubblicata', 'Conclusa', 'Annullata'];
$tipiBiglietto = ['base', 'esposizione'];
$ruoliDisponibili = ['visitatore', 'operatore', 'cassiere', 'amministratore', 'tester'];
$ruoloLabel = [
    'visitatore' => 'Visitatore',
    'operatore' => 'Operatore',
    'cassiere' => 'Cassiere',
    'amministratore' => 'Amministratore',
    'tester' => 'Tester'
];
$domandeSicurezza = [
    'primo_animale' => 'Nome del primo animale domestico',
    'citta_nascita' => 'Città che vorresti visitare',
    'scuola_elementare' => 'Nome della scuola elementare',
    'colore_preferito' => 'Colore preferito'
];

function normalizzaOraFascia(string $ora): string {
    $ora = trim($ora);
    if (preg_match('/^\d{2}:\d{2}$/', $ora)) {
        return $ora . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $ora)) {
        return $ora;
    }
    throw new RuntimeException('Inserisci un orario valido per la fascia.');
}

function caricaEsposizione(PDO $pdo, int $idEsposizione): array {
    $stmt = $pdo->prepare('SELECT id_esposizione, titolo, data_inizio, data_fine FROM Esposizioni WHERE id_esposizione = ? LIMIT 1');
    $stmt->execute([$idEsposizione]);
    $esposizione = $stmt->fetch();
    if (!$esposizione) {
        throw new RuntimeException('Esposizione non trovata.');
    }
    return $esposizione;
}

function validaDatiFascia(array $esposizione, string $data, string $ora, int $capienza): string {
    if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        throw new RuntimeException('Inserisci un giorno valido per la fascia oraria.');
    }
    if ($data < $esposizione['data_inizio'] || $data > $esposizione['data_fine']) {
        throw new RuntimeException("Il giorno della fascia deve essere compreso nel periodo dell'esposizione.");
    }
    if ($capienza <= 0) {
        throw new RuntimeException('La capienza massima deve essere maggiore di zero.');
    }
    return normalizzaOraFascia($ora);
}

function contaBigliettiFascia(PDO $pdo, int $idFascia): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Biglietti WHERE id_fascia = ? AND stato <> 'Annullato'");
    $stmt->execute([$idFascia]);
    return (int)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Token di sicurezza non valido.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'create_esposizione' || $action === 'update_esposizione') {
                $titolo = trim($_POST['titolo'] ?? '');
                $descrizione = trim($_POST['descrizione'] ?? '');
                $dataInizio = $_POST['data_inizio'] ?? '';
                $dataFine = $_POST['data_fine'] ?? '';
                $stato = $_POST['stato'] ?? 'Bozza';

                if ($titolo === '' || !$dataInizio || !$dataFine || !in_array($stato, $stati, true)) {
                    throw new RuntimeException('Compila correttamente tutti i campi dell\'esposizione.');
                }
                if ($dataFine < $dataInizio) {
                    throw new RuntimeException('La data di fine non può precedere la data di inizio.');
                }

                if ($action === 'create_esposizione') {
                    $stmt = $pdo->prepare('INSERT INTO Esposizioni (titolo, descrizione, data_inizio, data_fine, stato) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$titolo, $descrizione, $dataInizio, $dataFine, $stato]);
                    $successMsg = 'Esposizione creata correttamente.';
                } else {
                    $id = (int)($_POST['id_esposizione'] ?? 0);
                    if ($id <= 0) throw new RuntimeException('ID esposizione non valido.');
                    $stmt = $pdo->prepare('UPDATE Esposizioni SET titolo = ?, descrizione = ?, data_inizio = ?, data_fine = ?, stato = ? WHERE id_esposizione = ?');
                    $stmt->execute([$titolo, $descrizione, $dataInizio, $dataFine, $stato, $id]);
                    $successMsg = 'Esposizione aggiornata correttamente.';
                }
            } elseif ($action === 'create_fascia') {
                $idEsposizione = (int)($_POST['id_esposizione'] ?? 0);
                $data = $_POST['data'] ?? '';
                $ora = $_POST['ora_ingresso'] ?? '';
                $capienza = (int)($_POST['capienza_massima'] ?? 0);

                if ($idEsposizione <= 0) {
                    throw new RuntimeException("Seleziona un'esposizione valida.");
                }

                $esposizione = caricaEsposizione($pdo, $idEsposizione);
                $oraIngresso = validaDatiFascia($esposizione, $data, $ora, $capienza);

                try {
                    $stmt = $pdo->prepare('INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$idEsposizione, $data, $oraIngresso, $capienza]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una fascia per questa esposizione nello stesso giorno e orario.');
                    }
                    throw $e;
                }
                $successMsg = 'Fascia oraria creata correttamente.';
            } elseif ($action === 'update_fascia') {
                $idFascia = (int)($_POST['id_fascia'] ?? 0);
                $idEsposizione = (int)($_POST['id_esposizione'] ?? 0);
                $data = $_POST['data'] ?? '';
                $ora = $_POST['ora_ingresso'] ?? '';
                $capienza = (int)($_POST['capienza_massima'] ?? 0);

                if ($idFascia <= 0 || $idEsposizione <= 0) {
                    throw new RuntimeException('Fascia oraria non valida.');
                }

                $esposizione = caricaEsposizione($pdo, $idEsposizione);
                $oraIngresso = validaDatiFascia($esposizione, $data, $ora, $capienza);
                $bigliettiPrenotati = contaBigliettiFascia($pdo, $idFascia);

                if ($capienza < $bigliettiPrenotati) {
                    throw new RuntimeException('La capienza non può essere inferiore ai biglietti già prenotati per questa fascia.');
                }

                try {
                    $stmt = $pdo->prepare('UPDATE Fasce_Orarie SET data = ?, ora_ingresso = ?, capienza_massima = ? WHERE id_fascia = ? AND id_esposizione = ?');
                    $stmt->execute([$data, $oraIngresso, $capienza, $idFascia, $idEsposizione]);
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una fascia per questa esposizione nello stesso giorno e orario.');
                    }
                    throw $e;
                }
                $successMsg = 'Fascia oraria aggiornata correttamente.';
            } elseif ($action === 'delete_fascia') {
                $idFascia = (int)($_POST['id_fascia'] ?? 0);
                if ($idFascia <= 0) {
                    throw new RuntimeException('Fascia oraria non valida.');
                }

                $bigliettiPrenotati = contaBigliettiFascia($pdo, $idFascia);
                if ($bigliettiPrenotati > 0) {
                    throw new RuntimeException('Non puoi eliminare una fascia con biglietti già prenotati. Puoi modificarne la capienza oppure annullare i biglietti.');
                }

                $stmt = $pdo->prepare('DELETE FROM Fasce_Orarie WHERE id_fascia = ?');
                $stmt->execute([$idFascia]);
                $successMsg = 'Fascia oraria eliminata correttamente.';
            } elseif ($action === 'create_categoria' || $action === 'update_categoria') {
                $nome = trim($_POST['nome'] ?? '');
                $percentualeSconto = (float)str_replace(',', '.', $_POST['percentuale_sconto'] ?? '0');
                $documentoRichiesto = trim($_POST['documento_richiesto'] ?? '');
                $documentoRichiestoDb = $documentoRichiesto !== '' ? $documentoRichiesto : null;

                if ($nome === '' || $percentualeSconto < 0 || $percentualeSconto > 100) {
                    throw new RuntimeException('Compila correttamente la categoria: nome obbligatorio e sconto tra 0 e 100.');
                }

                try {
                    if ($action === 'create_categoria') {
                        $stmt = $pdo->prepare('INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto) VALUES (?, ?, ?)');
                        $stmt->execute([$nome, $percentualeSconto, $documentoRichiestoDb]);
                        $successMsg = 'Categoria creata correttamente. Ora puoi selezionarla nelle tariffe.';
                    } else {
                        $idCategoria = (int)($_POST['id_categoria'] ?? 0);
                        if ($idCategoria <= 0) {
                            throw new RuntimeException('Categoria non valida.');
                        }
                        $stmt = $pdo->prepare('UPDATE Categorie_Riduzione SET nome = ?, percentuale_sconto = ?, documento_richiesto = ? WHERE id_categoria = ?');
                        $stmt->execute([$nome, $percentualeSconto, $documentoRichiestoDb, $idCategoria]);
                        $successMsg = 'Categoria aggiornata correttamente.';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una categoria con questo nome.');
                    }
                    throw $e;
                }
            } elseif ($action === 'delete_categoria') {
                $idCategoria = (int)($_POST['id_categoria'] ?? 0);
                if ($idCategoria <= 0) {
                    throw new RuntimeException('Categoria non valida.');
                }

                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Tariffe WHERE id_categoria = ?');
                $stmt->execute([$idCategoria]);
                $tariffeCollegate = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare('SELECT COUNT(*) FROM Biglietti WHERE id_categoria = ?');
                $stmt->execute([$idCategoria]);
                $bigliettiCollegati = (int)$stmt->fetchColumn();

                if ($tariffeCollegate > 0 || $bigliettiCollegati > 0) {
                    throw new RuntimeException('Non puoi eliminare una categoria già collegata a tariffe o biglietti. Puoi modificarla oppure rimuovere prima le tariffe associate.');
                }

                $stmt = $pdo->prepare('DELETE FROM Categorie_Riduzione WHERE id_categoria = ?');
                $stmt->execute([$idCategoria]);
                $successMsg = 'Categoria eliminata correttamente.';
            } elseif ($action === 'update_user_role') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);
                $nuovoRuolo = $_POST['ruolo'] ?? '';
                $utenteCorrenteId = (int)($_SESSION['utente_id'] ?? 0);

                if ($idUtente <= 0 || !in_array($nuovoRuolo, $ruoliDisponibili, true)) {
                    throw new RuntimeException('Dati utente non validi.');
                }

                if ($idUtente === $utenteCorrenteId) {
                    throw new RuntimeException('Non puoi modificare il tuo ruolo da qui.');
                }

                $stmt = $pdo->prepare('UPDATE Utenti SET ruolo = ? WHERE id_utente = ?');
                $stmt->execute([$nuovoRuolo, $idUtente]);

                $successMsg = 'Ruolo utente aggiornato correttamente.';
            } elseif ($action === 'force_user_password') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);
                $nuovaPassword = trim($_POST['nuova_password'] ?? '');

                if ($idUtente <= 0) {
                    throw new RuntimeException('Utente non valido.');
                }
                if (strlen($nuovaPassword) < 8) {
                    throw new RuntimeException('La nuova password deve avere almeno 8 caratteri.');
                }

                $hash = password_hash($nuovaPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare('UPDATE Utenti SET password_hash = ? WHERE id_utente = ?');
                $stmt->execute([$hash, $idUtente]);
                $successMsg = 'Password utente aggiornata correttamente.';
            } elseif ($action === 'force_user_security') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);
                $domanda = $_POST['domanda_sicurezza'] ?? '';
                $risposta = trim($_POST['risposta_sicurezza'] ?? '');

                if ($idUtente <= 0 || !array_key_exists($domanda, $domandeSicurezza)) {
                    throw new RuntimeException('Domanda di sicurezza non valida.');
                }
                if ($risposta === '') {
                    throw new RuntimeException('Inserisci una nuova risposta di sicurezza.');
                }

                $hashRisposta = password_hash(normalizzaRispostaSicurezza($risposta), PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare('UPDATE Utenti SET domanda_sicurezza = ?, risposta_sicurezza_hash = ? WHERE id_utente = ?');
                $stmt->execute([$domanda, $hashRisposta, $idUtente]);
                $successMsg = 'Domanda e risposta di sicurezza aggiornate correttamente.';
            } elseif ($action === 'delete_user') {
                $idUtente = (int)($_POST['id_utente'] ?? 0);

                if ($idUtente <= 0) {
                    throw new RuntimeException('Utente non valido.');
                }
                if ($idUtente === (int)$_SESSION['utente_id']) {
                    throw new RuntimeException('Non puoi eliminare il tuo account amministratore da questa pagina. Usa la pagina account personale.');
                }

                $stmt = $pdo->prepare('DELETE FROM Utenti WHERE id_utente = ?');
                $stmt->execute([$idUtente]);
                $successMsg = 'Account eliminato correttamente.';
            } elseif ($action === 'accetta_rimborso' || $action === 'rifiuta_rimborso') {
                $idOrdine = (int)($_POST['id_ordine'] ?? 0);
                if ($idOrdine <= 0) {
                    throw new RuntimeException('Ordine non valido.');
                }
                if (!colonnaEsiste($pdo, 'Ordini', 'richiesta_rimborso') || !colonnaEsiste($pdo, 'Ordini', 'stato_rimborso') || !colonnaEsiste($pdo, 'Ordini', 'motivo_rimborso')) {
                    throw new RuntimeException('La gestione rimborsi non è attiva: servono le colonne richiesta_rimborso, stato_rimborso e motivo_rimborso nella tabella Ordini.');
                }

                $stmt = $pdo->prepare("
                    SELECT o.id_ordine, o.id_utente, o.codice_recupero, o.nome_cliente, o.email_cliente,
                           o.importo_totale, o.stato_rimborso, u.nome, u.cognome, u.email AS email_utente
                    FROM Ordini o
                    LEFT JOIN Utenti u ON u.id_utente = o.id_utente
                    WHERE o.id_ordine = ?
                    LIMIT 1
                ");
                $stmt->execute([$idOrdine]);
                $ordineRimborso = $stmt->fetch();
                if (!$ordineRimborso || ($ordineRimborso['stato_rimborso'] ?? '') !== 'Richiesto') {
                    throw new RuntimeException('Richiesta di rimborso non trovata o già gestita.');
                }

                $stmtUsati = $pdo->prepare("SELECT COUNT(*) FROM Biglietti WHERE id_ordine = ? AND stato = 'Utilizzato'");
                $stmtUsati->execute([$idOrdine]);
                if ((int)$stmtUsati->fetchColumn() > 0) {
                    throw new RuntimeException('Questo rimborso non può essere gestito perché uno o più biglietti dell’ordine sono già stati utilizzati.');
                }

                if ($action === 'accetta_rimborso') {
                    $pdo->beginTransaction();
                    try {
                        $campiRimborso = ["stato_rimborso = 'Accettato'"];
                        if (colonnaEsiste($pdo, 'Ordini', 'data_esito_rimborso')) {
                            $campiRimborso[] = 'data_esito_rimborso = NOW()';
                        }
                        $stmt = $pdo->prepare('UPDATE Ordini SET ' . implode(', ', $campiRimborso) . ' WHERE id_ordine = ?');
                        $stmt->execute([$idOrdine]);
                        if ((int)($ordineRimborso['id_utente'] ?? 0) > 0 && colonnaEsiste($pdo, 'Utenti', 'saldo_utente')) {
                            $stmt = $pdo->prepare('UPDATE Utenti SET saldo_utente = saldo_utente + ? WHERE id_utente = ?');
                            $stmt->execute([(float)$ordineRimborso['importo_totale'], (int)$ordineRimborso['id_utente']]);
                        }
                        if (colonnaEsiste($pdo, 'Ordini', 'richiesta_rimborso')) {
                            $stmt = $pdo->prepare('UPDATE Ordini SET richiesta_rimborso = 0 WHERE id_ordine = ?');
                            $stmt->execute([$idOrdine]);
                        }
                        $pdo->commit();
                        $emailRimborsoInviata = function_exists('inviaEmailEsitoRimborso')
                            ? inviaEmailEsitoRimborso($ordineRimborso, 'Accettato')
                            : false;
                        $successMsg = 'Rimborso accettato e importo riaccreditato sul portafoglio utente.';
                        $successMsg .= $emailRimborsoInviata
                            ? ' Email di esito inviata all’utente.'
                            : ' Attenzione: rimborso gestito, ma email di esito non inviata.';
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $e;
                    }
                } else {
                    $campiRimborso = ["stato_rimborso = 'Rifiutato'"];
                    if (colonnaEsiste($pdo, 'Ordini', 'data_esito_rimborso')) {
                        $campiRimborso[] = 'data_esito_rimborso = NOW()';
                    }
                    $stmt = $pdo->prepare('UPDATE Ordini SET ' . implode(', ', $campiRimborso) . ' WHERE id_ordine = ?');
                    $stmt->execute([$idOrdine]);
                    if (colonnaEsiste($pdo, 'Ordini', 'richiesta_rimborso')) {
                        $stmt = $pdo->prepare('UPDATE Ordini SET richiesta_rimborso = 0 WHERE id_ordine = ?');
                        $stmt->execute([$idOrdine]);
                    }
                    $emailRimborsoInviata = function_exists('inviaEmailEsitoRimborso')
                        ? inviaEmailEsitoRimborso($ordineRimborso, 'Rifiutato')
                        : false;
                    $successMsg = 'Rimborso rifiutato.';
                    $successMsg .= $emailRimborsoInviata
                        ? ' Email di esito inviata all’utente.'
                        : ' Attenzione: rimborso gestito, ma email di esito non inviata.';
                }
            } elseif ($action === 'create_tariffa' || $action === 'update_tariffa') {
                $tipoBiglietto = $_POST['tipo_biglietto'] ?? '';
                $idCategoria = (int)($_POST['id_categoria'] ?? 0);
                $prezzo = (float)str_replace(',', '.', $_POST['prezzo'] ?? '0');

                if (!in_array($tipoBiglietto, $tipiBiglietto, true) || $idCategoria <= 0 || $prezzo < 0) {
                    throw new RuntimeException('Compila correttamente tutti i campi della tariffa.');
                }

                try {
                    if ($action === 'create_tariffa') {
                        $stmt = $pdo->prepare('INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo) VALUES (?, ?, ?)');
                        $stmt->execute([$tipoBiglietto, $idCategoria, $prezzo]);
                        $successMsg = 'Tariffa creata correttamente.';
                    } else {
                        $id = (int)($_POST['id_tariffa'] ?? 0);
                        if ($id <= 0) throw new RuntimeException('ID tariffa non valido.');
                        $stmt = $pdo->prepare('UPDATE Tariffe SET tipo_biglietto = ?, id_categoria = ?, prezzo = ? WHERE id_tariffa = ?');
                        $stmt->execute([$tipoBiglietto, $idCategoria, $prezzo, $id]);
                        $successMsg = 'Tariffa aggiornata correttamente.';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') {
                        throw new RuntimeException('Esiste già una tariffa per questa combinazione di tipo biglietto e categoria.');
                    }
                    throw $e;
                }
            } elseif ($action === 'create_servizio' || $action === 'update_servizio') {
                $nome = trim($_POST['nome'] ?? '');
                $descrizione = trim($_POST['descrizione'] ?? '');
                $prezzo = (float)str_replace(',', '.', $_POST['prezzo'] ?? '0');

                if ($nome === '' || $prezzo < 0) {
                    throw new RuntimeException('Compila correttamente tutti i campi del servizio.');
                }

                if ($action === 'create_servizio') {
                    $stmt = $pdo->prepare('INSERT INTO Servizi_Opzionali (nome, descrizione, prezzo) VALUES (?, ?, ?)');
                    $stmt->execute([$nome, $descrizione, $prezzo]);
                    $successMsg = 'Servizio opzionale creato correttamente.';
                } else {
                    $id = (int)($_POST['id_servizio'] ?? 0);
                    if ($id <= 0) throw new RuntimeException('ID servizio non valido.');
                    $stmt = $pdo->prepare('UPDATE Servizi_Opzionali SET nome = ?, descrizione = ?, prezzo = ? WHERE id_servizio = ?');
                    $stmt->execute([$nome, $descrizione, $prezzo, $id]);
                    $successMsg = 'Servizio opzionale aggiornato correttamente.';
                }
            } else {
                throw new RuntimeException('Azione non riconosciuta.');
            }
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

try {
    $esposizioni = $pdo->query("SELECT * FROM Esposizioni ORDER BY FIELD(stato,'Pubblicata','Bozza','Conclusa','Annullata'), data_inizio DESC")->fetchAll();
    $categorie = $pdo->query("
        SELECT
            cr.*,
            COALESCE(tariffe_collegate.numero_tariffe, 0) AS numero_tariffe,
            COALESCE(biglietti_collegati.numero_biglietti, 0) AS numero_biglietti
        FROM Categorie_Riduzione cr
        LEFT JOIN (
            SELECT id_categoria, COUNT(*) AS numero_tariffe
            FROM Tariffe
            GROUP BY id_categoria
        ) tariffe_collegate ON tariffe_collegate.id_categoria = cr.id_categoria
        LEFT JOIN (
            SELECT id_categoria, COUNT(*) AS numero_biglietti
            FROM Biglietti
            WHERE id_categoria IS NOT NULL
            GROUP BY id_categoria
        ) biglietti_collegati ON biglietti_collegati.id_categoria = cr.id_categoria
        ORDER BY cr.nome ASC
    ")->fetchAll();
    $tariffe = $pdo->query("
        SELECT t.*, cr.nome AS categoria
        FROM Tariffe t
        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
        ORDER BY FIELD(t.tipo_biglietto,'base','esposizione'), cr.nome
    ")->fetchAll();
    $servizi = $pdo->query("SELECT * FROM Servizi_Opzionali ORDER BY nome ASC")->fetchAll();
    $utenti = $pdo->query("SELECT id_utente, nome, cognome, email, ruolo, email_verificata, domanda_sicurezza, data_registrazione FROM Utenti ORDER BY data_registrazione DESC, cognome ASC")->fetchAll();
    $fasce = $pdo->query("
        SELECT
            f.*,
            COALESCE(prenotazioni.biglietti_prenotati, 0) AS biglietti_prenotati,
            (f.capienza_massima - COALESCE(prenotazioni.biglietti_prenotati, 0)) AS posti_disponibili
        FROM Fasce_Orarie f
        LEFT JOIN (
            SELECT id_fascia, COUNT(*) AS biglietti_prenotati
            FROM Biglietti
            WHERE stato <> 'Annullato'
            GROUP BY id_fascia
        ) prenotazioni ON prenotazioni.id_fascia = f.id_fascia
        ORDER BY f.data ASC, f.ora_ingresso ASC
    ")->fetchAll();

    $rimborsiRichiesti = [];
    if (colonnaEsiste($pdo, 'Ordini', 'stato_rimborso')) {
        $whereRimborsi = colonnaEsiste($pdo, 'Ordini', 'richiesta_rimborso')
            ? "(o.richiesta_rimborso = 1 OR o.stato_rimborso = 'Richiesto')"
            : "o.stato_rimborso = 'Richiesto'";
        $rimborsiRichiesti = $pdo->query("
            SELECT
                o.id_ordine,
                o.codice_recupero,
                o.nome_cliente,
                o.email_cliente,
                o.importo_totale,
                o.motivo_rimborso,
                o.stato_rimborso,
                o.data_richiesta_rimborso,
                u.nome,
                u.cognome,
                SUM(CASE WHEN b.stato = 'Utilizzato' THEN 1 ELSE 0 END) AS biglietti_usati
            FROM Ordini o
            LEFT JOIN Utenti u ON u.id_utente = o.id_utente
            LEFT JOIN Biglietti b ON b.id_ordine = o.id_ordine
            WHERE {$whereRimborsi}
            GROUP BY o.id_ordine, o.codice_recupero, o.nome_cliente, o.email_cliente, o.importo_totale, o.motivo_rimborso, o.stato_rimborso, o.data_richiesta_rimborso, u.nome, u.cognome
            ORDER BY o.data_richiesta_rimborso DESC, o.data_acquisto DESC
        ")->fetchAll();
    }

    $fascePerEsposizione = [];
    foreach ($fasce as $fascia) {
        $fascePerEsposizione[(int)$fascia['id_esposizione']][] = $fascia;
    }

    $adminDashboard = [
        'tot_biglietti' => 0,
        'incasso' => 0.0,
        'ordini_pagati' => 0,
        'ordini_attesa' => 0,
        'rimborsi_richiesti' => count($rimborsiRichiesti),
        'mostra_top' => '—',
        'pagamenti' => [],
        'vendite_mese' => [],
        'biglietti_esposizione' => [],
        'giorno_top' => null,
        'categoria_top' => null,
        'rimborsi_accettati' => 0,
        'saldo_portafogli' => 0.0,
        'ordini_docente' => 0,
        'log_attivita' => [],
    ];

    $rowDash = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM Biglietti WHERE stato <> 'Annullato') AS tot_biglietti,
            (SELECT COALESCE(SUM(importo_totale), 0) FROM Ordini WHERE stato_pagamento = 'Pagato') AS incasso,
            (SELECT COUNT(*) FROM Ordini WHERE stato_pagamento = 'Pagato') AS ordini_pagati,
            (SELECT COUNT(*) FROM Ordini WHERE stato_pagamento IN ('In attesa','Non pagato')) AS ordini_attesa
    ")->fetch() ?: [];
    $adminDashboard['tot_biglietti'] = (int)($rowDash['tot_biglietti'] ?? 0);
    $adminDashboard['incasso'] = (float)($rowDash['incasso'] ?? 0);
    $adminDashboard['ordini_pagati'] = (int)($rowDash['ordini_pagati'] ?? 0);
    $adminDashboard['ordini_attesa'] = (int)($rowDash['ordini_attesa'] ?? 0);

    $adminDashboard['pagamenti'] = $pdo->query("
        SELECT metodo_pagamento, COUNT(*) AS totale
        FROM Ordini
        GROUP BY metodo_pagamento
        ORDER BY totale DESC
    ")->fetchAll();

    $adminDashboard['vendite_mese'] = $pdo->query("
        SELECT DATE_FORMAT(data_acquisto, '%Y-%m') AS mese, COUNT(*) AS ordini, COALESCE(SUM(importo_totale), 0) AS incasso
        FROM Ordini
        WHERE data_acquisto >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data_acquisto, '%Y-%m')
        ORDER BY mese ASC
    ")->fetchAll();

    $adminDashboard['biglietti_esposizione'] = $pdo->query("
        SELECT COALESCE(e.titolo, 'Ingresso museo') AS esposizione, COUNT(b.id_biglietto) AS totale
        FROM Biglietti b
        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia
        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione
        WHERE b.stato <> 'Annullato'
        GROUP BY COALESCE(e.titolo, 'Ingresso museo')
        ORDER BY totale DESC
        LIMIT 5
    ")->fetchAll();
    if (!empty($adminDashboard['biglietti_esposizione'])) {
        $adminDashboard['mostra_top'] = (string)$adminDashboard['biglietti_esposizione'][0]['esposizione'];
    }

    $adminDashboard['giorno_top'] = $pdo->query("
        SELECT b.data_validita, COUNT(*) AS totale
        FROM Biglietti b
        INNER JOIN Ordini o ON o.id_ordine = b.id_ordine
        WHERE b.stato <> 'Annullato' AND o.stato_pagamento = 'Pagato'
        GROUP BY b.data_validita
        ORDER BY totale DESC, b.data_validita DESC
        LIMIT 1
    ")->fetch() ?: null;

    $adminDashboard['categoria_top'] = $pdo->query("
        SELECT COALESCE(cr.nome, 'Senza categoria') AS categoria, COUNT(*) AS totale
        FROM Biglietti b
        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria
        WHERE b.stato <> 'Annullato'
        GROUP BY COALESCE(cr.nome, 'Senza categoria')
        ORDER BY totale DESC
        LIMIT 1
    ")->fetch() ?: null;

    $adminDashboard['rimborsi_accettati'] = colonnaEsiste($pdo, 'Ordini', 'stato_rimborso')
        ? (int)$pdo->query("SELECT COUNT(*) FROM Ordini WHERE stato_rimborso = 'Accettato'")->fetchColumn()
        : 0;

    $adminDashboard['ordini_docente'] = colonnaEsiste($pdo, 'Ordini', 'prenotazione_docente')
        ? (int)$pdo->query("SELECT COUNT(*) FROM Ordini WHERE prenotazione_docente = 1")->fetchColumn()
        : 0;

    $adminDashboard['saldo_portafogli'] = colonnaEsiste($pdo, 'Utenti', 'saldo_utente')
        ? (float)$pdo->query("SELECT COALESCE(SUM(saldo_utente), 0) FROM Utenti")->fetchColumn()
        : 0.0;

    $logAttivita = [];
    foreach ($pdo->query("
        SELECT data_acquisto AS data_evento,
               CONCAT('Nuovo ordine ', codice_recupero) AS titolo,
               CONCAT(nome_cliente, ' · € ', FORMAT(importo_totale, 2)) AS descrizione,
               '📦' AS icona
        FROM Ordini
        ORDER BY data_acquisto DESC
        LIMIT 5
    ")->fetchAll() as $r) { $logAttivita[] = $r; }

    if (colonnaEsiste($pdo, 'Ordini', 'data_esito_rimborso')) {
        foreach ($pdo->query("
            SELECT COALESCE(data_esito_rimborso, data_richiesta_rimborso, data_acquisto) AS data_evento,
                   CONCAT('Rimborso ', LOWER(stato_rimborso)) AS titolo,
                   CONCAT('Ordine ', codice_recupero, ' · € ', FORMAT(importo_totale, 2)) AS descrizione,
                   '↩️' AS icona
            FROM Ordini
            WHERE stato_rimborso IN ('Richiesto','Accettato','Rifiutato')
            ORDER BY COALESCE(data_esito_rimborso, data_richiesta_rimborso, data_acquisto) DESC
            LIMIT 5
        ")->fetchAll() as $r) { $logAttivita[] = $r; }
    }

    if (colonnaEsiste($pdo, 'Biglietti', 'data_utilizzo')) {
        foreach ($pdo->query("
            SELECT data_utilizzo AS data_evento,
                   'Biglietto validato' AS titolo,
                   codice_univoco AS descrizione,
                   '✅' AS icona
            FROM Biglietti
            WHERE data_utilizzo IS NOT NULL
            ORDER BY data_utilizzo DESC
            LIMIT 5
        ")->fetchAll() as $r) { $logAttivita[] = $r; }
    }

    usort($logAttivita, function ($a, $b) {
        return strtotime((string)($b['data_evento'] ?? '1970-01-01')) <=> strtotime((string)($a['data_evento'] ?? '1970-01-01'));
    });
    $adminDashboard['log_attivita'] = array_slice($logAttivita, 0, 7);


    $adminQualityAlerts = [];
    $adminMaintenanceItems = [];

    $esposizioniSenzaFasce = 0;
    foreach ($esposizioni as $expo) {
        $idExpo = (int)($expo['id_esposizione'] ?? 0);
        if (($expo['stato'] ?? '') === 'Pubblicata' && empty($fascePerEsposizione[$idExpo])) {
            $esposizioniSenzaFasce++;
        }
        if (trim((string)($expo['descrizione'] ?? '')) === '') {
            $adminMaintenanceItems[] = [
                'icona' => '📝',
                'titolo' => 'Descrizione mancante',
                'testo' => 'L’esposizione “' . (string)($expo['titolo'] ?? 'senza titolo') . '” non ha una descrizione completa.',
                'link' => '#admin-esposizioni'
            ];
        }
        if (($expo['stato'] ?? '') === 'Pubblicata' && !empty($expo['data_fine']) && $expo['data_fine'] < date('Y-m-d')) {
            $adminQualityAlerts[] = [
                'tipo' => 'warning',
                'icona' => '⏳',
                'titolo' => 'Mostra conclusa ancora pubblicata',
                'testo' => '“' . (string)($expo['titolo'] ?? 'Esposizione') . '” è terminata: valuta se impostarla come conclusa.',
                'link' => '#admin-esposizioni'
            ];
        }
    }
    if ($esposizioniSenzaFasce > 0) {
        $adminQualityAlerts[] = [
            'tipo' => 'warning',
            'icona' => '📅',
            'titolo' => $esposizioniSenzaFasce . ' esposizion' . ($esposizioniSenzaFasce === 1 ? 'e' : 'i') . ' senza fasce',
            'testo' => 'Aggiungi almeno una fascia oraria per renderle prenotabili.',
            'link' => '#admin-esposizioni'
        ];
    }
    if ((int)$adminDashboard['ordini_attesa'] > 0) {
        $adminQualityAlerts[] = [
            'tipo' => 'info',
            'icona' => '💳',
            'titolo' => $adminDashboard['ordini_attesa'] . ' ordini in attesa',
            'testo' => 'Controlla eventuali pagamenti in cassa o ordini non completati.',
            'link' => '#admin-dashboard'
        ];
    }
    if ((int)$adminDashboard['rimborsi_richiesti'] > 0) {
        $adminQualityAlerts[] = [
            'tipo' => 'danger',
            'icona' => '↩️',
            'titolo' => $adminDashboard['rimborsi_richiesti'] . ' rimbors' . ((int)$adminDashboard['rimborsi_richiesti'] === 1 ? 'o' : 'i') . ' da valutare',
            'testo' => 'Gestisci le richieste aperte dalla sezione rimborsi.',
            'link' => '#admin-rimborsi'
        ];
    }
    if (empty($adminQualityAlerts)) {
        $adminQualityAlerts[] = [
            'tipo' => 'ok',
            'icona' => '✅',
            'titolo' => 'Qualità dati sotto controllo',
            'testo' => 'Non risultano criticità evidenti nei dati principali del gestionale.',
            'link' => '#admin-dashboard'
        ];
    }

    if (empty($tariffe)) {
        $adminMaintenanceItems[] = ['icona' => '💶', 'titolo' => 'Tariffe assenti', 'testo' => 'Configura almeno una tariffa per consentire il calcolo dei prezzi.', 'link' => '#admin-tariffe'];
    }
    if (empty($servizi)) {
        $adminMaintenanceItems[] = ['icona' => '🎧', 'titolo' => 'Servizi opzionali assenti', 'testo' => 'Aggiungi servizi extra per rendere più ricca la visita.', 'link' => '#admin-servizi'];
    }
    $fasceSoldOut = 0;
    foreach ($fasce as $fascia) {
        if ((int)($fascia['posti_disponibili'] ?? 0) <= 0) {
            $fasceSoldOut++;
        }
    }
    if ($fasceSoldOut > 0) {
        $adminMaintenanceItems[] = ['icona' => '🎟️', 'titolo' => $fasceSoldOut . ' fasce senza posti', 'testo' => 'Verifica se aumentare la capienza o aggiungere nuovi orari.', 'link' => '#admin-esposizioni'];
    }
    if (empty($adminMaintenanceItems)) {
        $adminMaintenanceItems[] = ['icona' => '✨', 'titolo' => 'Contenuti completi', 'testo' => 'Le configurazioni principali risultano presenti e pronte all’uso.', 'link' => '#admin-dashboard'];
    }
} catch (Exception $e) {
    $esposizioni = $categorie = $tariffe = $servizi = $utenti = $fasce = $fascePerEsposizione = $rimborsiRichiesti = [];
    $adminDashboard = ['tot_biglietti' => 0, 'incasso' => 0.0, 'ordini_pagati' => 0, 'ordini_attesa' => 0, 'rimborsi_richiesti' => 0, 'mostra_top' => '—', 'pagamenti' => [], 'vendite_mese' => [], 'biglietti_esposizione' => [], 'giorno_top' => null, 'categoria_top' => null, 'rimborsi_accettati' => 0, 'saldo_portafogli' => 0.0, 'ordini_docente' => 0, 'log_attivita' => []];
    $adminQualityAlerts = [];
    $adminMaintenanceItems = [];
    $errorMsg = $errorMsg ?: 'Errore nel caricamento dei dati amministrativi.';
}

include __DIR__ . '/header.php';
?>

<style>
  .admin-dashboard-card { background: linear-gradient(180deg, #ffffff, #fbfdff); border: 1px solid rgba(142,197,232,.28); }
  .admin-dashboard-kpi { transition: transform .18s ease, box-shadow .18s ease; }
  .admin-dashboard-kpi:hover { transform: translateY(-2px); box-shadow: 0 16px 34px rgba(16,39,68,.10); }
  .admin-insight-card { background: linear-gradient(135deg, #102744, #1b4e78); color: #fffdf5; border: 1px solid rgba(142,197,232,.28); }
  .admin-insight-card.alt { background: linear-gradient(135deg, #ffffff, #f5fbff); color: #102744; }
  .admin-activity-dot { width: 2.25rem; height: 2.25rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #e8f5fc; }
  .admin-bar-row { display: grid; grid-template-columns: minmax(105px, 150px) 1fr auto; gap: .75rem; align-items: center; }
  .admin-bar-track { height: .75rem; border-radius: 999px; background: #eef6fb; overflow: hidden; }
  .admin-bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #cfeeff, #8ec5e8); }
  .admin-bar-fill-mid { background: linear-gradient(90deg, #8ec5e8, #3f91c7); }
  .admin-bar-fill-dark { background: linear-gradient(90deg, #5ba7d4, #102744); }
  .admin-sticky-tools {
    position: sticky;
    top: 5.25rem;
    z-index: 45;
    display: grid;
    gap: .8rem;
  }
  .admin-quick-nav {
    padding: 1rem !important;
    background: rgba(255, 255, 255, .97);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }
  .admin-quick-nav-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
  }
  .admin-quick-nav-link {
    min-height: 2.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: .7rem .8rem;
    font-size: .78rem;
    line-height: 1.15;
    letter-spacing: .045em;
    text-align: center;
    white-space: normal;
    word-break: normal;
    border-radius: .85rem;
  }
  .mss-admin-filterbar {
    background: rgba(255,255,255,.97);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }
  @media (max-width: 1180px) {
    .admin-quick-nav-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .admin-quick-nav-link { font-size: .74rem; min-height: 2.75rem; }
  }
  @media (max-width: 900px) {
    .admin-quick-nav-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .admin-sticky-tools { position: static; }
  }

  .admin-quality-hero { background: linear-gradient(180deg, #ffffff, #fbfdff); color: #102744; border: 1px solid rgba(142,197,232,.32); border-radius: 1.75rem !important; overflow: hidden; box-shadow: 0 14px 34px rgba(16,39,68,.08); }
  .admin-quality-card { display:flex; gap: .9rem; align-items:flex-start; border:1px solid rgba(142,197,232,.28); background:#ffffff; color:#102744; border-radius:1.15rem; padding:1rem; text-decoration:none; transition:transform .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease; box-shadow: 0 8px 22px rgba(16,39,68,.055); }
  .admin-quality-card:hover { transform: translateY(-2px); background:#f8fcff; border-color:#8ec5e8; box-shadow: 0 14px 30px rgba(16,39,68,.10); }
  .admin-quality-card span:first-child { width:2.65rem; height:2.65rem; flex:0 0 auto; border-radius:.95rem; display:inline-flex; align-items:center; justify-content:center; background:#e8f5fc; color:#102744; font-size:1.22rem; }
  .admin-quality-card strong { display:block; font-weight:900; }
  .admin-quality-card small { display:block; color:#52677a; margin-top:.18rem; line-height:1.35; }
  .admin-maintenance-panel { background: linear-gradient(180deg, #ffffff, #f8fcff); border:1px solid rgba(142,197,232,.28); box-shadow: 0 12px 28px rgba(16,39,68,.06); }
  .mss-maintenance-card {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    min-height: 8.25rem;
    padding: 1rem;
    border-radius: 1.15rem;
    border: 1px solid rgba(142,197,232,.30);
    background: linear-gradient(180deg, #ffffff, #f7fbfe);
    color: #102744;
    text-decoration: none;
    box-shadow: 0 8px 22px rgba(16,39,68,.055);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .mss-maintenance-card:hover {
    transform: translateY(-2px);
    border-color: #8ec5e8;
    box-shadow: 0 14px 30px rgba(16,39,68,.10);
  }
  .mss-maintenance-card span {
    width: 2.45rem;
    height: 2.45rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: .95rem;
    background: #e8f5fc;
    font-size: 1.12rem;
  }
  .mss-maintenance-card strong { font-weight: 900; line-height: 1.2; }
  .mss-maintenance-card small { color: #52677a; line-height: 1.35; }

  @media (max-width: 640px) { .admin-bar-row { grid-template-columns: 1fr; gap: .35rem; } }
</style>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Vista amministratore</span>
  </div>
</div>

<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area riservata</p>
    <h1 class="font-display text-avorio text-4xl font-bold">Vista amministratore</h1>
    <p class="text-gray-400 mt-3">Gestisci esposizioni, tariffe e servizi opzionali.</p>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<main class="admin-page-main max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-12">
  <?php if ($successMsg): ?>
    <div class="alert-success floating-alert p-4 rounded text-sm font-body" role="status" data-auto-dismiss="true"> <?= clean($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert-error floating-alert p-4 rounded text-sm font-body" role="alert"> <?= clean($errorMsg) ?></div>
  <?php endif; ?>

  <?php
    $adminMenuItems = [
      ['href' => '#admin-dashboard', 'label' => 'Dashboard'],
      ['href' => '#admin-esposizioni', 'label' => 'Esposizioni'],
      ['href' => '#admin-categorie', 'label' => 'Categorie riduzioni'],
      ['href' => '#admin-tariffe', 'label' => 'Tariffe'],
      ['href' => '#admin-servizi', 'label' => 'Servizi'],
      ['href' => '#admin-utenti', 'label' => 'Utenti'],
      ['href' => '#admin-rimborsi', 'label' => 'Rimborsi'],
      ['href' => '#admin-export', 'label' => 'Export CSV'],
    ];
  ?>


  <div class="admin-sticky-tools hidden md:grid">
    <nav class="admin-quick-nav rounded-2xl shadow border border-avorio-dark" aria-label="Menu amministrazione">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-3">Menu amministrazione</p>
      <div class="admin-quick-nav-grid">
        <?php foreach ($adminMenuItems as $item): ?>
          <a href="<?= clean($item['href']) ?>" class="admin-quick-nav-link btn-outline rounded uppercase"><?= clean($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </nav>


  </div>

  <section id="admin-qualita" class="scroll-mt-32 admin-quality-hero p-5 md:p-7">
    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
      <div class="max-w-xl">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Controlli automatici</p>
        <h2 class="font-display text-3xl md:text-4xl font-bold leading-tight">Qualità dati e contenuti</h2>
        <p class="text-gray-600 mt-3 leading-relaxed">Una panoramica immediata di ciò che può bloccare prenotazioni, vendite o gestione. Le card sono generate dai dati già presenti nel database.</p>
      </div>
      <div class="grid sm:grid-cols-2 gap-3 xl:w-[58%]">
        <?php foreach ($adminQualityAlerts as $alert): ?>
          <a href="<?= clean($alert['link'] ?? '#admin-dashboard') ?>" class="admin-quality-card">
            <span aria-hidden="true"><?= clean($alert['icona'] ?? '•') ?></span>
            <span>
              <strong><?= clean($alert['titolo'] ?? 'Avviso') ?></strong>
              <small><?= clean($alert['testo'] ?? '') ?></small>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-maintenance-panel rounded-2xl p-4 md:p-5 mt-5 text-antracite">
      <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-4">
        <div>
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Manutenzione contenuti</p>
          <h3 class="font-display text-2xl font-bold">Cose da completare</h3>
        </div>
        <p class="text-sm text-gray-500">Checklist utile per mantenere il museo pronto e coerente.</p>
      </div>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach (array_slice($adminMaintenanceItems, 0, 6) as $item): ?>
          <a href="<?= clean($item['link'] ?? '#admin-dashboard') ?>" class="mss-maintenance-card">
            <span aria-hidden="true"><?= clean($item['icona'] ?? '•') ?></span>
            <strong><?= clean($item['titolo'] ?? 'Controllo') ?></strong>
            <small><?= clean($item['testo'] ?? '') ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>



  <section id="admin-dashboard" class="scroll-mt-32 admin-dashboard-card rounded-2xl shadow p-5 md:p-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-6">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Cruscotto</p>
        <h2 class="font-display text-2xl md:text-3xl font-bold text-antracite">Dashboard amministratore</h2>
        <p class="text-sm text-gray-500 mt-1">Riepilogo rapido di vendite, biglietti, rimborsi e pagamenti.</p>
      </div>
      <span class="text-xs text-gray-500">Aggiornata automaticamente dai dati del database</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
      <?php foreach ([
        ['🎟️', 'Biglietti', number_format((int)$adminDashboard['tot_biglietti'], 0, ',', '.'), 'Totale emessi'],
        ['💶', 'Incasso', '€ ' . number_format((float)$adminDashboard['incasso'], 2, ',', '.'), 'Ordini pagati'],
        ['✅', 'Pagati', number_format((int)$adminDashboard['ordini_pagati'], 0, ',', '.'), 'Ordini completati'],
        ['⏳', 'In attesa', number_format((int)$adminDashboard['ordini_attesa'], 0, ',', '.'), 'Da completare'],
        ['↩️', 'Rimborsi', number_format((int)$adminDashboard['rimborsi_richiesti'], 0, ',', '.'), 'Richiesti'],
      ] as $kpi): ?>
        <article class="admin-dashboard-kpi bg-white rounded-2xl border border-avorio-dark p-4">
          <div class="flex items-center justify-between gap-3">
            <span class="inline-flex w-11 h-11 rounded-2xl bg-avorio items-center justify-center text-xl" aria-hidden="true"><?= $kpi[0] ?></span>
            <strong class="font-display text-2xl text-antracite text-right"><?= clean((string)$kpi[2]) ?></strong>
          </div>
          <h3 class="font-body font-bold text-antracite mt-3"><?= clean($kpi[1]) ?></h3>
          <p class="text-xs text-gray-500 mt-1"><?= clean($kpi[3]) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
      <?php
        $advancedStats = [
          ['📅', 'Giorno con più visite', $adminDashboard['giorno_top'] ? date('d/m/Y', strtotime($adminDashboard['giorno_top']['data_validita'])) : '—', $adminDashboard['giorno_top'] ? ((int)$adminDashboard['giorno_top']['totale'] . ' biglietti') : 'Nessun dato'],
          ['✅', 'Rimborsi accettati', number_format((int)$adminDashboard['rimborsi_accettati'], 0, ',', '.'), 'Esiti positivi registrati'],
          ['👩‍🏫', 'Prenotazioni classi', number_format((int)$adminDashboard['ordini_docente'], 0, ',', '.'), 'Ordini didattici'],
          ['💰', 'Saldo portafogli', '€ ' . number_format((float)$adminDashboard['saldo_portafogli'], 2, ',', '.'), 'Totale saldo utenti'],
          ['📊', 'Tasso pagati', ($adminDashboard['ordini_pagati'] + $adminDashboard['ordini_attesa']) > 0 ? round($adminDashboard['ordini_pagati'] / max(1, $adminDashboard['ordini_pagati'] + $adminDashboard['ordini_attesa']) * 100) . '%' : '—', 'Ordini completati sul totale'],
        ];
      ?>
      <?php foreach ($advancedStats as $idx => $stat): ?>
        <article class="<?= $idx === 0 ? 'admin-insight-card' : 'admin-insight-card alt' ?> rounded-2xl p-4 shadow-sm">
          <div class="flex items-start gap-3">
            <span class="inline-flex w-10 h-10 rounded-2xl <?= $idx === 0 ? 'bg-white/12' : 'bg-avorio' ?> items-center justify-center text-lg" aria-hidden="true"><?= $stat[0] ?></span>
            <div>
              <p class="text-xs uppercase tracking-widest <?= $idx === 0 ? 'text-acciaio' : 'text-oro' ?> font-bold"><?= clean($stat[1]) ?></p>
              <h3 class="font-display text-xl font-bold mt-1"><?= clean((string)$stat[2]) ?></h3>
              <p class="text-xs <?= $idx === 0 ? 'text-avorio/75' : 'text-gray-500' ?> mt-1"><?= clean((string)$stat[3]) ?></p>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">
      <article class="bg-white rounded-2xl border border-avorio-dark p-5 lg:col-span-1">
        <p class="text-xs uppercase tracking-widest text-oro font-bold mb-2">Mostra più prenotata</p>
        <h3 class="font-display text-xl font-bold text-antracite"><?= clean($adminDashboard['mostra_top']) ?></h3>
        <div class="space-y-3 mt-5">
          <?php $maxExpo = max(1, ...array_map(fn($r) => (int)$r['totale'], $adminDashboard['biglietti_esposizione'] ?: [['totale' => 1]])); ?>
          <?php foreach ($adminDashboard['biglietti_esposizione'] as $row): ?>
            <?php $val = (int)$row['totale']; ?>
            <div class="admin-bar-row text-sm">
              <span class="font-bold text-antracite truncate"><?= clean($row['esposizione']) ?></span>
              <span class="admin-bar-track"><span class="admin-bar-fill block" style="width: <?= max(8, round($val / $maxExpo * 100)) ?>%"></span></span>
              <span class="text-gray-500 font-bold"><?= $val ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="bg-white rounded-2xl border border-avorio-dark p-5">
        <p class="text-xs uppercase tracking-widest text-oro font-bold mb-4">Metodi di pagamento</p>
        <div class="space-y-3">
          <?php $maxPay = max(1, ...array_map(fn($r) => (int)$r['totale'], $adminDashboard['pagamenti'] ?: [['totale' => 1]])); ?>
          <?php foreach ($adminDashboard['pagamenti'] as $row): ?>
            <?php $val = (int)$row['totale']; ?>
            <div class="admin-bar-row text-sm">
              <span class="font-bold text-antracite capitalize"><?= clean($row['metodo_pagamento'] ?: 'non indicato') ?></span>
              <span class="admin-bar-track"><span class="admin-bar-fill admin-bar-fill-mid block" style="width: <?= max(8, round($val / $maxPay * 100)) ?>%"></span></span>
              <span class="text-gray-500 font-bold"><?= $val ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </article>

      <article class="bg-white rounded-2xl border border-avorio-dark p-5">
        <p class="text-xs uppercase tracking-widest text-oro font-bold mb-4">Vendite ultimi mesi</p>
        <div class="space-y-3">
          <?php $maxMese = max(1, ...array_map(fn($r) => (float)$r['incasso'], $adminDashboard['vendite_mese'] ?: [['incasso' => 1]])); ?>
          <?php foreach ($adminDashboard['vendite_mese'] as $row): ?>
            <?php $val = (float)$row['incasso']; ?>
            <div class="admin-bar-row text-sm">
              <span class="font-bold text-antracite"><?= clean(date('m/Y', strtotime($row['mese'] . '-01'))) ?></span>
              <span class="admin-bar-track"><span class="admin-bar-fill admin-bar-fill-dark block" style="width: <?= max(8, round($val / $maxMese * 100)) ?>%"></span></span>
              <span class="text-gray-500 font-bold">€ <?= number_format($val, 0, ',', '.') ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </article>
    </div>
    <article class="bg-white rounded-2xl border border-avorio-dark p-5 mt-6">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-4">
        <div>
          <p class="text-xs uppercase tracking-widest text-oro font-bold mb-1">Monitoraggio</p>
          <h3 class="font-display text-2xl font-bold text-antracite">Ultime attività</h3>
        </div>
        <span class="text-xs text-gray-500">Ordini, rimborsi e validazioni recenti</span>
      </div>
      <?php if (!empty($adminDashboard['log_attivita'])): ?>
        <div class="grid md:grid-cols-2 gap-3">
          <?php foreach ($adminDashboard['log_attivita'] as $attivita): ?>
            <div class="rounded-2xl border border-avorio-dark bg-avorio/40 p-4 flex gap-3 items-start">
              <span class="admin-activity-dot" aria-hidden="true"><?= clean((string)($attivita['icona'] ?? '•')) ?></span>
              <div>
                <h4 class="font-body font-bold text-antracite text-sm"><?= clean((string)($attivita['titolo'] ?? 'Attività')) ?></h4>
                <p class="text-xs text-gray-500 mt-1"><?= clean((string)($attivita['descrizione'] ?? '')) ?></p>
                <?php if (!empty($attivita['data_evento'])): ?>
                  <p class="text-[11px] text-oro font-bold mt-2"><?= date('d/m/Y H:i', strtotime((string)$attivita['data_evento'])) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="mss-empty-state"><span aria-hidden="true">🕘</span><strong>Nessuna attività recente</strong><p>Quando arriveranno ordini, rimborsi o validazioni, li vedrai qui.</p></div>
      <?php endif; ?>
    </article>
  </section>



  <section id="admin-export"

  <section id="admin-export" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Dati amministrativi</p>
        <h2 class="font-display text-2xl text-avorio font-bold">Esportazione CSV</h2>
        <p class="text-gray-400 text-sm mt-1">Scarica i dati principali del gestionale in formato CSV compatibile con Excel e fogli di calcolo.</p>
      </div>
      <span class="inline-flex rounded-full bg-oro/10 border border-oro/30 text-oro px-4 py-2 text-xs font-body font-bold uppercase tracking-wide">Solo amministratori</span>
    </div>

    <div class="p-6 bg-gradient-to-br from-avorio to-white">
      <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php foreach ([
          ['ordini', '📦', 'Ordini', 'Ordini, importi, stati, metodi di pagamento e numero biglietti.'],
          ['biglietti', '🎟️', 'Biglietti', 'Codici biglietto, esposizioni, date, categorie, stati e utilizzi.'],
          ['rimborsi', '↩️', 'Rimborsi', 'Richieste, motivazioni, stati ed esiti dei rimborsi.'],
          ['utenti', '👤', 'Utenti', 'Anagrafica essenziale, ruoli, verifica email, saldo e ordini.'],
          ['esposizioni', '🏛️', 'Esposizioni', 'Mostre, date, stati, fasce orarie e biglietti collegati.'],
          ['tariffe', '💶', 'Tariffe', 'Prezzi per categoria, tipo biglietto e sconti applicati.'],
          ['servizi', '🎧', 'Servizi', 'Servizi opzionali, prezzi e acquisti collegati.'],
        ] as $export): ?>
          <article class="rounded-2xl border border-avorio-dark bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start gap-3">
              <span class="inline-flex w-12 h-12 rounded-2xl bg-avorio items-center justify-center text-2xl" aria-hidden="true"><?= $export[1] ?></span>
              <div class="min-w-0">
                <h3 class="font-display text-xl font-bold text-antracite"><?= clean($export[2]) ?></h3>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed"><?= clean($export[3]) ?></p>
              </div>
            </div>
            <a href="<?= SITE_URL ?>/export_csv.php?tipo=<?= urlencode($export[0]) ?>" class="btn-outline inline-flex items-center justify-center w-full mt-5 px-4 py-3 rounded-lg text-sm font-body font-bold uppercase tracking-wide">Scarica CSV</a>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="mt-5 rounded-2xl border border-acciaio/30 bg-acciaio/10 p-4 text-sm text-antracite">
        <strong>Nota:</strong> i file CSV sono generati in tempo reale dai dati del database e non includono password o informazioni riservate non necessarie.
      </div>
    </div>
  </section>



  <!-- ESPOSIZIONI -->
  <section id="admin-esposizioni" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
        <h2 class="font-display text-2xl text-avorio font-bold">Esposizioni</h2>
      </div>
      <span class="text-gray-400 text-sm">Le bozze sono visibili solo agli amministratori.</span>
    </div>

    <div class="p-6 border-b border-avorio-dark bg-avorio">
      <h3 class="font-display text-xl font-bold text-antracite mb-4">Crea nuova esposizione</h3>
      <form method="POST" class="grid md:grid-cols-6 gap-4 admin-expo-create-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="create_esposizione">
        <input type="text" name="titolo" placeholder="Titolo" required class="admin-title-input px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <div class="admin-date-field">
          <label for="nuova_esposizione_data_inizio" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data inizio</label>
          <input type="date" id="nuova_esposizione_data_inizio" name="data_inizio" required class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm">
        </div>
        <div class="admin-date-field">
          <label for="nuova_esposizione_data_fine" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data fine</label>
          <input type="date" id="nuova_esposizione_data_fine" name="data_fine" required class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm">
        </div>
        <div class="admin-state-field">
          <label for="nuova_esposizione_stato" class="block text-xs font-bold text-gray-500 uppercase mb-1">Stato</label>
          <select id="nuova_esposizione_stato" name="stato" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm" aria-label="Stato esposizione">
            <?php foreach ($stati as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
          </select>
        </div>
        <textarea name="descrizione" placeholder="Descrizione" class="md:col-span-5 px-4 py-3 border border-gray-200 rounded-lg text-sm admin-description-field"></textarea>
        <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea</button></div>
      </form>
    </div>

    <?php if (!empty($esposizioni)): ?>
      <div class="p-6 border-b border-avorio-dark bg-white">
        <label for="cerca-nome-esposizione" class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Cerca esposizione per nome</label>
        <div class="flex flex-col sm:flex-row gap-3">
          <input type="search" id="cerca-nome-esposizione" placeholder="Scrivi il nome dell’esposizione..." autocomplete="off" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-oro/40">
          <button type="button" id="reset-cerca-nome-esposizione" class="btn-outline px-5 py-3 rounded text-xs uppercase tracking-wide">Mostra tutte</button>
        </div>
        <p id="risultati-cerca-nome-esposizione" class="text-xs text-gray-500 mt-2">Puoi filtrare le esposizioni senza scorrere tutta la lista.</p>
        <p id="nessuna-esposizione-trovata" class="hidden text-sm text-red-600 font-bold mt-3">Nessuna esposizione trovata con questo nome.</p>
      </div>
    <?php endif; ?>

    <span id="admin-fasce" class="block scroll-mt-32"></span>
    <div class="divide-y divide-avorio-dark">
      <?php foreach ($esposizioni as $esp): ?>
        <?php $fasceEsp = $fascePerEsposizione[(int)$esp['id_esposizione']] ?? []; ?>
        <article class="admin-exposition-card m-4 md:m-6 rounded-2xl border-2 border-oro/30 bg-white shadow-lg overflow-hidden" id="admin-fasce-<?= (int)$esp['id_esposizione'] ?>" data-title="<?= clean(strtolower($esp['titolo'])) ?>">
          <div class="bg-avorio px-6 py-5 border-b border-oro/30 text-center md:text-left">
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Stai modificando questa esposizione</p>
            <h3 class="font-display text-2xl font-bold text-antracite"><?= clean($esp['titolo']) ?></h3>
            <p class="text-sm text-gray-500 mt-1">Le fasce orarie qui sotto appartengono solo a questa esposizione.</p>
          </div>
          <div class="p-6 space-y-6">
          <form method="POST" class="grid md:grid-cols-7 gap-4 items-start admin-expo-edit-form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_esposizione">
            <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
            <div class="admin-title-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Titolo</label>
              <input type="text" name="titolo" value="<?= clean($esp['titolo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="admin-date-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Inizio</label>
              <input type="date" name="data_inizio" value="<?= clean($esp['data_inizio']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="admin-date-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Fine</label>
              <input type="date" name="data_fine" value="<?= clean($esp['data_fine']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
            </div>
            <div class="admin-state-field">
              <label class="text-xs font-bold text-gray-500 uppercase">Stato</label>
              <select name="stato" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                <?php foreach ($stati as $s): ?>
                  <option value="<?= $s ?>" <?= $esp['stato'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-end justify-end h-full admin-submit-field">
              <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva</button>
            </div>
            <div class="admin-description-wrap">
              <label class="text-xs font-bold text-gray-500 uppercase">Descrizione</label>
              <textarea name="descrizione" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm min-h-20"><?= clean($esp['descrizione'] ?? '') ?></textarea>
            </div>
          </form>

          <div class="rounded-2xl border border-avorio-dark bg-avorio p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2 mb-4">
              <div>
                <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Fasce orarie</p>
                <h4 class="font-display text-lg font-bold text-antracite">Giorni, orari e capienza per questa esposizione</h4>
              </div>
              <span class="text-xs text-gray-500">Periodo esposizione: <?= date('d/m/Y', strtotime($esp['data_inizio'])) ?> → <?= date('d/m/Y', strtotime($esp['data_fine'])) ?></span>
            </div>

            <form method="POST" class="grid md:grid-cols-6 gap-3 items-end mb-5">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action" value="create_fascia">
              <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Data inizio</label>
                <input type="date" value="<?= clean($esp['data_inizio']) ?>" readonly class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-gray-50" aria-label="Data inizio esposizione">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Data fine</label>
                <input type="date" value="<?= clean($esp['data_fine']) ?>" readonly class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-gray-50" aria-label="Data fine esposizione">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Giorno fascia</label>
                <input type="date" name="data" min="<?= clean($esp['data_inizio']) ?>" max="<?= clean($esp['data_fine']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Ora ingresso</label>
                <input type="time" name="ora_ingresso" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white">
              </div>
              <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Capienza massima</label>
                <input type="number" name="capienza_massima" min="1" value="30" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm bg-white">
              </div>
              <div class="md:col-span-1 admin-submit-field">
                <button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Aggiungi fascia</button>
              </div>
            </form>

            <?php if (empty($fasceEsp)): ?>
              <div class="mss-empty-state compact"><span aria-hidden="true">📅</span><strong>Nessuna fascia oraria</strong><p>Aggiungi un giorno e un orario per rendere prenotabile questa esposizione.</p></div>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($fasceEsp as $fascia): ?>
                  <?php
                    $prenotati = (int)$fascia['biglietti_prenotati'];
                    $postiDisponibili = max(0, (int)$fascia['posti_disponibili']);
                  ?>
                  <div class="bg-white border border-avorio-dark rounded-xl p-4">
                    <form method="POST" class="grid md:grid-cols-6 gap-3 items-end admin-inline-form">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="update_fascia">
                      <input type="hidden" name="id_fascia" value="<?= (int)$fascia['id_fascia'] ?>">
                      <input type="hidden" name="id_esposizione" value="<?= (int)$esp['id_esposizione'] ?>">
                      <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Giorno</label>
                        <input type="date" name="data" min="<?= clean($esp['data_inizio']) ?>" max="<?= clean($esp['data_fine']) ?>" value="<?= clean($fascia['data']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      </div>
                      <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Ora</label>
                        <input type="time" name="ora_ingresso" value="<?= clean(substr($fascia['ora_ingresso'], 0, 5)) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      </div>
                      <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Capienza</label>
                        <input type="number" name="capienza_massima" min="<?= max(1, $prenotati) ?>" value="<?= (int)$fascia['capienza_massima'] ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      </div>
                      <div class="text-xs text-gray-500 leading-5">
                        <span class="block font-bold text-antracite">Prenotati: <?= $prenotati ?></span>
                        <span class="block">Disponibili: <?= $postiDisponibili ?></span>
                      </div>
                      <div class="admin-submit-field"><button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva fascia</button></div>
                    </form>
                    <form method="POST" class="mt-3" onsubmit="return confirm('Eliminare questa fascia oraria?');">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="delete_fascia">
                      <input type="hidden" name="id_fascia" value="<?= (int)$fascia['id_fascia'] ?>">
                      <button type="submit" class="text-xs font-bold uppercase tracking-wide text-red-600 hover:underline" <?= $prenotati > 0 ? 'disabled title="Non puoi eliminare una fascia con biglietti già prenotati" style="opacity:.45;cursor:not-allowed"' : '' ?>>Elimina fascia</button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CATEGORIE RIDUZIONE E TARIFFE -->
  <section id="admin-categorie" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Categorie riduzione e tariffe</h2>
    </div>

    <div class="p-6 border-b border-avorio-dark bg-avorio space-y-8">
      <div>
        <h3 class="font-display text-xl font-bold text-antracite mb-2">Categorie riduzione</h3>
        <p class="text-sm text-gray-500 mb-4">Crea nuove categorie, poi selezionale nel menu a tendina delle tariffe.</p>
        <form method="POST" class="grid md:grid-cols-5 gap-4 items-end bg-white border border-avorio-dark rounded-2xl p-4 mb-5">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="create_categoria">
          <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Nome categoria</label>
            <input type="text" name="nome" placeholder="Es. Famiglia, Gruppo, Universitario" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Sconto %</label>
            <input type="number" step="0.01" min="0" max="100" name="percentuale_sconto" value="0" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Documento richiesto</label>
            <input type="text" name="documento_richiesto" placeholder="Facoltativo" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea categoria</button></div>
        </form>

        <?php if (empty($categorie)): ?>
          <div class="mss-empty-state compact"><span aria-hidden="true">🏷️</span><strong>Nessuna categoria</strong><p>Crea una categoria di riduzione per configurare le tariffe.</p></div>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($categorie as $cat): ?>
              <?php
                $tariffeCollegate = (int)($cat['numero_tariffe'] ?? 0);
                $bigliettiCollegati = (int)($cat['numero_biglietti'] ?? 0);
                $categoriaInUso = $tariffeCollegate > 0 || $bigliettiCollegati > 0;
              ?>
              <div class="bg-white border border-avorio-dark rounded-xl p-4">
                <form method="POST" class="grid md:grid-cols-6 gap-3 items-end">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="update_categoria">
                  <input type="hidden" name="id_categoria" value="<?= (int)$cat['id_categoria'] ?>">
                  <div class="md:col-span-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">Nome</label>
                    <input type="text" name="nome" value="<?= clean($cat['nome']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  </div>
                  <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Sconto %</label>
                    <input type="number" step="0.01" min="0" max="100" name="percentuale_sconto" value="<?= clean((string)$cat['percentuale_sconto']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  </div>
                  <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Documento</label>
                    <input type="text" name="documento_richiesto" value="<?= clean($cat['documento_richiesto'] ?? '') ?>" placeholder="Facoltativo" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  </div>
                  <div class="text-xs text-gray-500 leading-5">
                    <span class="block font-bold text-antracite">Tariffe: <?= $tariffeCollegate ?></span>
                    <span class="block">Biglietti: <?= $bigliettiCollegati ?></span>
                  </div>
                  <div class="admin-submit-field"><button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva categoria</button></div>
                </form>
                <form method="POST" class="mt-3" onsubmit="return confirm('Eliminare questa categoria?');">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="delete_categoria">
                  <input type="hidden" name="id_categoria" value="<?= (int)$cat['id_categoria'] ?>">
                  <button type="submit" <?= $categoriaInUso ? 'disabled' : '' ?> class="text-xs uppercase tracking-wide <?= $categoriaInUso ? 'text-gray-300 cursor-not-allowed' : 'text-red-700 hover:underline' ?>">
                    Elimina categoria
                  </button>
                  <?php if ($categoriaInUso): ?>
                    <span class="ml-2 text-xs text-gray-400">Non eliminabile perché già utilizzata.</span>
                  <?php endif; ?>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div id="admin-tariffe" class="border-t border-avorio-dark pt-8 scroll-mt-32">
        <h3 class="font-display text-xl font-bold text-antracite mb-4">Crea nuova tariffa</h3>
        <form method="POST" class="grid md:grid-cols-4 gap-4">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="create_tariffa">
          <select name="tipo_biglietto" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
            <?php foreach ($tipiBiglietto as $tipo): ?><option value="<?= $tipo ?>"><?= ucfirst($tipo) ?></option><?php endforeach; ?>
          </select>
          <select name="id_categoria" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
            <?php foreach ($categorie as $cat): ?><option value="<?= (int)$cat['id_categoria'] ?>"><?= clean($cat['nome']) ?></option><?php endforeach; ?>
          </select>
          <input type="number" step="0.01" min="0" name="prezzo" placeholder="Prezzo" required class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
          <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea tariffa</button></div>
        </form>
      </div>
    </div>

    <div class="divide-y divide-avorio-dark">
      <?php foreach ($tariffe as $t): ?>
        <form method="POST" class="p-6 grid md:grid-cols-5 gap-4 items-end">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="update_tariffa">
          <input type="hidden" name="id_tariffa" value="<?= (int)$t['id_tariffa'] ?>">
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Tipo biglietto</label>
            <select name="tipo_biglietto" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
              <?php foreach ($tipiBiglietto as $tipo): ?>
                <option value="<?= $tipo ?>" <?= $t['tipo_biglietto'] === $tipo ? 'selected' : '' ?>><?= ucfirst($tipo) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Categoria</label>
            <select name="id_categoria" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
              <?php foreach ($categorie as $cat): ?>
                <option value="<?= (int)$cat['id_categoria'] ?>" <?= (int)$t['id_categoria'] === (int)$cat['id_categoria'] ? 'selected' : '' ?>><?= clean($cat['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Prezzo</label>
            <input type="number" step="0.01" min="0" name="prezzo" value="<?= clean((string)$t['prezzo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div class="admin-submit-field"><button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva</button></div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- SERVIZI -->
  <section id="admin-servizi" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Servizi opzionali</h2>
    </div>

    <div class="p-6 border-b border-avorio-dark bg-avorio">
      <h3 class="font-display text-xl font-bold text-antracite mb-4">Crea nuovo servizio</h3>
      <form method="POST" class="grid md:grid-cols-5 gap-4">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="create_servizio">
        <input type="text" name="nome" placeholder="Nome servizio" required class="md:col-span-2 px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <input type="number" step="0.01" min="0" name="prezzo" placeholder="Prezzo" required class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
        <textarea name="descrizione" placeholder="Descrizione" class="md:col-span-1 px-4 py-3 border border-gray-200 rounded-lg text-sm"></textarea>
        <div class="admin-submit-field"><button type="submit" class="btn-oro px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Crea</button></div>
      </form>
    </div>

    <div class="divide-y divide-avorio-dark">
      <?php foreach ($servizi as $s): ?>
        <form method="POST" class="p-6 grid md:grid-cols-5 gap-4 items-start">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="action" value="update_servizio">
          <input type="hidden" name="id_servizio" value="<?= (int)$s['id_servizio'] ?>">
          <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Nome</label>
            <input type="text" name="nome" value="<?= clean($s['nome']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Prezzo</label>
            <input type="number" step="0.01" min="0" name="prezzo" value="<?= clean((string)$s['prezzo']) ?>" required class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="text-xs font-bold text-gray-500 uppercase">Descrizione</label>
            <textarea name="descrizione" class="w-full mt-1 px-4 py-3 border border-gray-200 rounded-lg text-sm"><?= clean($s['descrizione'] ?? '') ?></textarea>
          </div>
          <div class="flex items-end justify-end h-full admin-submit-field">
            <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide admin-small-submit">Salva</button>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>


  <!-- UTENTI -->
  <section id="admin-utenti" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Utenti e ruoli</h2>
      <p class="text-gray-400 text-sm mt-2">Da qui puoi aggiornare ruoli, forzare una nuova password, cambiare domanda di sicurezza o eliminare account.</p>
    </div>

    <div class="p-6 space-y-5 bg-avorio">
      <?php if (empty($utenti)): ?>
        <div class="mss-empty-state"><span aria-hidden="true">👤</span><strong>Nessun utente presente</strong><p>Le nuove registrazioni compariranno in questa sezione.</p></div>
      <?php else: ?>
        <div class="bg-white border border-avorio-dark rounded-2xl shadow-sm p-5 space-y-3">
          <label for="cerca-email-utente" class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Cerca subito un utente per email</label>
          <div class="flex flex-col md:flex-row gap-3">
            <input type="search" id="cerca-email-utente" placeholder="Scrivi una mail, anche parziale..." autocomplete="off" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-oro/40">
            <select id="filtro-ruolo-utente" class="px-4 py-3 border border-gray-200 rounded-lg text-sm" aria-label="Filtra utenti per ruolo">
              <option value="">Tutti i ruoli</option>
              <?php foreach ($ruoliDisponibili as $ruoloFiltro): ?>
                <option value="<?= clean($ruoloFiltro) ?>"><?= clean($ruoloLabel[$ruoloFiltro] ?? $ruoloFiltro) ?></option>
              <?php endforeach; ?>
            </select>
            <select id="filtro-verifica-utente" class="px-4 py-3 border border-gray-200 rounded-lg text-sm" aria-label="Filtra utenti per verifica email">
              <option value="">Tutti</option>
              <option value="1">Email verificate</option>
              <option value="0">Email non verificate</option>
            </select>
            <button type="button" id="reset-cerca-email-utente" class="btn-outline px-5 py-3 rounded text-sm uppercase tracking-wide">Mostra tutti</button>
          </div>
          <p id="risultati-cerca-email-utente" class="text-xs text-gray-500">Puoi filtrare le card degli utenti per email, ruolo e verifica.</p>
        </div>

        <p id="nessun-utente-trovato" class="hidden bg-white border border-avorio-dark rounded-xl p-4 text-sm text-gray-500">Nessun utente trovato con questa email.</p>

        <?php foreach ($utenti as $u): ?>
          <article class="admin-user-card bg-white border border-avorio-dark rounded-2xl shadow-sm overflow-hidden" data-email="<?= clean(strtolower($u['email'])) ?>" data-role="<?= clean($u['ruolo']) ?>" data-verified="<?= (int)$u['email_verificata'] ?>">
            <div class="px-5 py-4 border-b border-avorio-dark flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
              <div>
                <h3 class="font-display text-xl font-bold text-antracite">
                  <?= clean($u['nome']) ?> <?= clean($u['cognome']) ?>
                </h3>
                <p class="text-sm text-gray-500 break-all"><?= clean($u['email']) ?></p>
              </div>
              <div class="flex flex-wrap gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?= in_array($u['ruolo'], ['amministratore','tester'], true) ? 'bg-oro text-antracite' : 'bg-gray-100 text-gray-700' ?>">
                  <?= clean($ruoloLabel[$u['ruolo']] ?? $u['ruolo']) ?>
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?= (int)$u['email_verificata'] === 1 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                  <?= (int)$u['email_verificata'] === 1 ? 'Email verificata' : 'Email non verificata' ?>
                </span>
              </div>
            </div>

            <div class="p-5 grid xl:grid-cols-2 gap-5">
              <?php $isCurrentUser = (int)$u['id_utente'] === (int)($_SESSION['utente_id'] ?? 0); ?>
              <form method="POST" class="bg-avorio rounded-xl border border-oro/20 p-4 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="update_user_role">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <label class="block text-xs font-bold text-gray-500 uppercase">Ruolo utente</label>
                <?php if ($isCurrentUser): ?>
                  <div class="px-4 py-3 border border-oro/30 rounded-lg bg-white text-sm text-gray-600">
                    Il tuo ruolo non può essere modificato da qui.
                  </div>
                <?php else: ?>
                  <div class="flex flex-col sm:flex-row gap-3">
                    <select name="ruolo" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                      <?php foreach ($ruoliDisponibili as $ruolo): ?>
                        <option value="<?= clean($ruolo) ?>" <?= $u['ruolo'] === $ruolo ? 'selected' : '' ?>>
                          <?= clean($ruoloLabel[$ruolo] ?? $ruolo) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Salva ruolo</button>
                  </div>
                <?php endif; ?>
              </form>

              <form method="POST" class="bg-avorio rounded-xl border border-oro/20 p-4 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="force_user_password">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <label class="block text-xs font-bold text-gray-500 uppercase">Forza cambio password</label>
                <div class="flex flex-col sm:flex-row gap-3">
                  <input type="password" name="nuova_password" minlength="8" placeholder="Nuova password" class="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Aggiorna</button>
                </div>
              </form>

              <form method="POST" class="bg-avorio rounded-xl border border-oro/20 p-4 space-y-3 xl:col-span-2">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="force_user_security">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <label class="block text-xs font-bold text-gray-500 uppercase">Forza cambio domanda di sicurezza</label>
                <div class="grid md:grid-cols-3 gap-3">
                  <select name="domanda_sicurezza" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
                    <?php foreach ($domandeSicurezza as $value => $label): ?>
                      <option value="<?= clean($value) ?>" <?= ($u['domanda_sicurezza'] ?? '') === $value ? 'selected' : '' ?>><?= clean($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="risposta_sicurezza" placeholder="Nuova risposta" class="px-4 py-3 border border-gray-200 rounded-lg text-sm">
                  <button type="submit" class="btn-outline px-4 py-3 rounded text-sm uppercase tracking-wide">Aggiorna sicurezza</button>
                </div>
              </form>

              <form method="POST" class="xl:col-span-2 text-right" onsubmit="return confirm('Eliminare definitivamente questo account?');">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>">
                <button type="submit" <?= (int)$u['id_utente'] === (int)$_SESSION['utente_id'] ? 'disabled title="Non puoi eliminare da qui il tuo account amministratore"' : '' ?> class="text-xs font-bold uppercase tracking-wide <?= (int)$u['id_utente'] === (int)$_SESSION['utente_id'] ? 'text-gray-300 cursor-not-allowed' : 'text-red-700 hover:underline' ?>">
                  Elimina account
                </button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section id="admin-rimborsi" class="scroll-mt-32 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden mt-8">
    <div class="bg-antracite px-6 py-5">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Gestione</p>
      <h2 class="font-display text-2xl text-avorio font-bold">Rimborsi richiesti</h2>
    </div>
    <div class="p-6 space-y-4 bg-avorio">
      <?php if (empty($rimborsiRichiesti)): ?>
        <p class="bg-white border border-avorio-dark rounded-xl p-4 text-sm text-gray-600">Non ci sono richieste di rimborso al momento.</p>
      <?php else: ?>
        <?php foreach ($rimborsiRichiesti as $r): ?>
          <article class="bg-white border border-avorio-dark rounded-xl p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
              <div>
                <p class="text-xs uppercase tracking-widest text-oro font-bold mb-1">Ordine <?= clean($r['codice_recupero']) ?></p>
                <h3 class="font-display text-xl font-bold text-antracite"><?= clean(trim(($r['nome'] ?? '') . ' ' . ($r['cognome'] ?? '')) ?: ($r['nome_cliente'] ?? 'Utente')) ?></h3>
                <p class="text-sm text-gray-600 break-all"><?= clean($r['email_cliente'] ?? '') ?></p>
                <p class="text-sm text-gray-600 mt-3"><strong>Motivo:</strong> <?= clean($r['motivo_rimborso'] ?? 'Non indicato') ?></p>
              </div>
              <div class="md:text-right">
                <p class="font-display text-2xl text-oro font-bold">€ <?= number_format((float)$r['importo_totale'], 2, ',', '.') ?></p>
                <p class="text-xs text-gray-600 mt-1">Stato: <strong><?= clean($r['stato_rimborso'] ?? 'Richiesto') ?></strong></p>
                <?php if ((int)($r['biglietti_usati'] ?? 0) > 0): ?>
                  <p class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3 text-left md:text-right">
                    Rimborso bloccato: uno o più biglietti risultano già utilizzati.
                  </p>
                <?php else: ?>
                  <div class="flex flex-col sm:flex-row gap-2 mt-4 md:justify-end">
                    <form method="POST" onsubmit="return confirm('Accettare questo rimborso e riaccreditare il portafoglio utente?');">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="accetta_rimborso">
                      <input type="hidden" name="id_ordine" value="<?= (int)$r['id_ordine'] ?>">
                      <button type="submit" class="btn-oro px-4 py-2 rounded text-xs uppercase tracking-wide">Accetta</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Rifiutare questo rimborso?');">
                      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                      <input type="hidden" name="action" value="rifiuta_rimborso">
                      <input type="hidden" name="id_ordine" value="<?= (int)$r['id_ordine'] ?>">
                      <button type="submit" class="btn-outline px-4 py-2 rounded text-xs uppercase tracking-wide">Rifiuta</button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('cerca-email-utente');
  const reset = document.getElementById('reset-cerca-email-utente');
  const roleFilter = document.getElementById('filtro-ruolo-utente');
  const verifiedFilter = document.getElementById('filtro-verifica-utente');
  const cards = Array.from(document.querySelectorAll('.admin-user-card'));
  const noResults = document.getElementById('nessun-utente-trovato');
  const resultsText = document.getElementById('risultati-cerca-email-utente');

  if (!input || !reset || cards.length === 0) {
    return;
  }

  function filtraUtenti() {
    const ricerca = input.value.trim().toLowerCase();
    const ruolo = roleFilter ? roleFilter.value : '';
    const verifica = verifiedFilter ? verifiedFilter.value : '';
    let visibili = 0;

    cards.forEach(function (card) {
      const email = (card.dataset.email || '').toLowerCase();
      const cardRole = card.dataset.role || '';
      const cardVerified = card.dataset.verified || '';
      const matchEmail = ricerca === '' || email.includes(ricerca);
      const matchRole = ruolo === '' || cardRole === ruolo;
      const matchVerified = verifica === '' || cardVerified === verifica;
      const match = matchEmail && matchRole && matchVerified;
      card.classList.toggle('hidden', !match);
      if (match) {
        visibili++;
      }
    });

    if (noResults) {
      noResults.classList.toggle('hidden', visibili !== 0);
    }
    if (resultsText) {
      resultsText.textContent = 'Utenti visibili: ' + visibili;
    }
  }

  input.addEventListener('input', filtraUtenti);
  if (roleFilter) roleFilter.addEventListener('change', filtraUtenti);
  if (verifiedFilter) verifiedFilter.addEventListener('change', filtraUtenti);
  reset.addEventListener('click', function () {
    input.value = '';
    if (roleFilter) roleFilter.value = '';
    if (verifiedFilter) verifiedFilter.value = '';
    filtraUtenti();
    input.focus();
  });
});
</script>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('cerca-nome-esposizione');
  const reset = document.getElementById('reset-cerca-nome-esposizione');
  const cards = Array.from(document.querySelectorAll('.admin-exposition-card'));
  const noResults = document.getElementById('nessuna-esposizione-trovata');
  const resultsText = document.getElementById('risultati-cerca-nome-esposizione');

  if (!input || !reset || cards.length === 0) {
    return;
  }

  function filtraEsposizioni() {
    const ricerca = input.value.trim().toLowerCase();
    let visibili = 0;

    cards.forEach(function (card) {
      const titolo = (card.dataset.title || '').toLowerCase();
      const match = ricerca === '' || titolo.includes(ricerca);
      card.classList.toggle('hidden', !match);
      if (match) {
        visibili++;
      }
    });

    if (noResults) {
      noResults.classList.toggle('hidden', visibili !== 0);
    }
    if (resultsText) {
      resultsText.textContent = ricerca === ''
        ? 'Puoi filtrare le esposizioni senza scorrere tutta la lista.'
        : 'Esposizioni trovate: ' + visibili;
    }
  }

  input.addEventListener('input', filtraEsposizioni);
  reset.addEventListener('click', function () {
    input.value = '';
    filtraEsposizioni();
    input.focus();
  });
});
</script>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  const openButton = document.getElementById('adminMobileMenuButton');
  const closeButton = document.getElementById('adminMobileMenuClose');
  const panel = document.getElementById('adminMobileMenuPanel');
  const backdrop = document.getElementById('adminMobileBackdrop');
  const links = panel ? Array.from(panel.querySelectorAll('a')) : [];

  if (!openButton || !closeButton || !panel || !backdrop) {
    return;
  }

  function setAdminMenu(open) {
    panel.hidden = !open;
    backdrop.hidden = !open;
    openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('admin-menu-open', open);
  }

  openButton.addEventListener('click', function () {
    setAdminMenu(panel.hidden);
  });

  closeButton.addEventListener('click', function () {
    setAdminMenu(false);
  });

  backdrop.addEventListener('click', function () {
    setAdminMenu(false);
  });

  links.forEach(function (link) {
    link.addEventListener('click', function () {
      setAdminMenu(false);
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setAdminMenu(false);
    }
  });
});
</script>


<script nonce="<?= cspNonce() ?>">
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.floating-alert[data-auto-dismiss="true"]').forEach(function (alertBox) {
      window.setTimeout(function () {
        alertBox.style.transition = 'opacity .35s ease, transform .35s ease';
        alertBox.style.opacity = '0';
        alertBox.style.transform = 'translate(-50%, -8px)';
        window.setTimeout(function () {
          alertBox.remove();
        }, 400);
      }, 3500);
    });
  });
</script>


<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  function humanizeName(value) {
    return String(value || '')
      .replace(/[_-]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .replace(/^./, function (char) { return char.toUpperCase(); });
  }

  document.querySelectorAll('main input, main select, main textarea').forEach(function (field, index) {
    if (field.type === 'hidden') return;
    if (field.getAttribute('aria-label') || field.getAttribute('aria-labelledby')) return;
    if (field.id && document.querySelector('label[for="' + CSS.escape(field.id) + '"]')) return;

    var label = '';
    var parentLabel = field.closest('label');
    if (parentLabel) {
      label = parentLabel.textContent || '';
    }
    if (!label && field.placeholder) {
      label = field.placeholder;
    }
    if (!label && field.name) {
      label = humanizeName(field.name);
    }
    if (!label) {
      label = 'Campo amministrazione ' + (index + 1);
    }

    field.setAttribute('aria-label', label.trim());
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
