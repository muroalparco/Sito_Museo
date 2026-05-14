<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Pagamento';
$pdo = getDB();
$errore = '';
$ordine = null;
$formPagamento = null;
$pagamentoOrdineEsistente = null;
$errorePagamento = '';
$bigliettiCreati = [];
$emailOrdineDaInviare = null;

function generaCodiceOrdine(PDO $pdo): string {
    do {
        $codice = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT id_ordine FROM Ordini WHERE codice_recupero = ? LIMIT 1');
        $stmt->execute([$codice]);
    } while ($stmt->fetch());
    return $codice;
}

function generaCodiceBiglietto(PDO $pdo): string {
    do {
        $codice = 'TKT-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt = $pdo->prepare('SELECT id_biglietto FROM Biglietti WHERE codice_univoco = ? LIMIT 1');
        $stmt->execute([$codice]);
    } while ($stmt->fetch());
    return $codice;
}

function colonnaEsiste(PDO $pdo, string $tabella, string $colonna): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabella` LIKE ?");
        $stmt->execute([$colonna]);
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function idCategoriaDocente(PDO $pdo): ?int {
    try {
        $stmt = $pdo->prepare("SELECT id_categoria FROM Categorie_Riduzione WHERE nome = 'Docente accompagnatore' LIMIT 1");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int)$id;
    } catch (Throwable $e) {
        return null;
    }
}

function normalizzaInputPagamento(array $input): array {
    $dati = $input;
    if (isset($dati['servizi']) && !is_array($dati['servizi'])) {
        $dati['servizi'] = [$dati['servizi']];
    }
    if (!isset($dati['servizi'])) {
        $dati['servizi'] = [];
    }
    return $dati;
}

function preparaOrdine(PDO $pdo, array $dati): array {
    $tipo = $dati['tipo'] ?? '';
    $nomeCliente = trim($dati['nome_cliente'] ?? '');
    $emailCliente = trim($dati['email_cliente'] ?? '');
    $idTariffa = (int)($dati['id_tariffa'] ?? 0);
    $prenotazioneDocente = ($dati['prenotazione_docente'] ?? '') === '1';
    $metodoPagamento = $dati['metodo_pagamento'] ?? 'carta';

    if (!in_array($metodoPagamento, ['contanti', 'carta', 'paypal'], true)) {
        throw new RuntimeException('Metodo di pagamento non valido.');
    }

    $quantitaRichiesta = (int)($dati['quantita'] ?? 1);
    $quantitaStudenti = $prenotazioneDocente
        ? max(1, (int)($dati['quantita_studenti'] ?? $quantitaRichiesta))
        : max(1, min(20, $quantitaRichiesta));
    $numeroDocenti = $prenotazioneDocente ? max(0, (int)($dati['numero_docenti'] ?? 0)) : 0;
    $quantita = $prenotazioneDocente ? ($quantitaStudenti + $numeroDocenti) : $quantitaStudenti;

    $nomeScuola = trim($dati['nome_scuola'] ?? '');
    $codiceMeccanografico = strtoupper(trim($dati['codice_meccanografico'] ?? ''));
    $indirizzoScuola = trim($dati['indirizzo_scuola'] ?? '');
    $cittaScuola = trim($dati['citta_scuola'] ?? '');
    $telefonoScuola = trim($dati['telefono_scuola'] ?? '');
    $classeScuola = trim($dati['classe_scuola'] ?? '');
    $noteScuola = trim($dati['note_scuola'] ?? '');

    $serviziIds = array_values(array_unique(array_map('intval', $dati['servizi'] ?? [])));

    if (!in_array($tipo, ['base', 'esposizione'], true)) {
        throw new RuntimeException('Tipo biglietto non valido.');
    }
    if ($nomeCliente === '' || !filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Inserisci nome, cognome ed email validi.');
    }
    if ($prenotazioneDocente && ($nomeScuola === '' || $cittaScuola === '' || $classeScuola === '')) {
        throw new RuntimeException('Inserisci almeno nome scuola, città e classe/sezione.');
    }
    if ($idTariffa <= 0) {
        throw new RuntimeException('Seleziona una tariffa valida.');
    }

    $stmtTariffa = $pdo->prepare(" 
        SELECT t.*, cr.nome AS categoria, cr.percentuale_sconto
        FROM Tariffe t
        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
        WHERE t.id_tariffa = ? AND t.tipo_biglietto = ?
        LIMIT 1
    ");
    $stmtTariffa->execute([$idTariffa, $tipo]);
    $tariffa = $stmtTariffa->fetch();

    if (!$tariffa) {
        throw new RuntimeException('Tariffa non valida per il biglietto selezionato.');
    }

    if (strcasecmp(trim((string)$tariffa['categoria']), 'Docente accompagnatore') === 0) {
        throw new RuntimeException('La tariffa docente accompagnatore è riservata alla prenotazione classe e viene gestita automaticamente.');
    }

    $stmtPrezzoIntero = $pdo->prepare(" 
        SELECT t.prezzo
        FROM Tariffe t
        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
        WHERE t.tipo_biglietto = ? AND cr.nome = 'Intero'
        LIMIT 1
    ");
    $stmtPrezzoIntero->execute([$tipo]);
    $prezzoIntero = $stmtPrezzoIntero->fetchColumn();
    if ($prezzoIntero === false) {
        $stmtMax = $pdo->prepare('SELECT MAX(prezzo) FROM Tariffe WHERE tipo_biglietto = ?');
        $stmtMax->execute([$tipo]);
        $prezzoIntero = $stmtMax->fetchColumn();
    }

    $prezzoLordo = (float)$prezzoIntero;
    $prezzoFinale = (float)$tariffa['prezzo'];
    $scontoApplicato = max(0, $prezzoLordo - $prezzoFinale);
    $idCategoria = (int)$tariffa['id_categoria'];
    $idCategoriaDocente = idCategoriaDocente($pdo);
    $idFascia = null;
    $dataValidita = null;
    $titoloPercorso = 'Museo Storico Severi';

    if ($tipo === 'esposizione') {
        $idEsposizione = (int)($dati['id_esposizione'] ?? 0);
        $idFascia = (int)($dati['id_fascia'] ?? 0);
        if ($idEsposizione <= 0 || $idFascia <= 0) {
            throw new RuntimeException('Seleziona una fascia oraria valida.');
        }

        $stmtFascia = $pdo->prepare(" 
            SELECT f.*, e.titolo
            FROM Fasce_Orarie f
            JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione
            WHERE f.id_fascia = ? AND f.id_esposizione = ? AND e.stato = 'Pubblicata'
            LIMIT 1
        ");
        $stmtFascia->execute([$idFascia, $idEsposizione]);
        $fascia = $stmtFascia->fetch();
        if (!$fascia) {
            throw new RuntimeException('La fascia oraria selezionata non è disponibile.');
        }

        $stmtVenduti = $pdo->prepare("SELECT COUNT(*) FROM Biglietti WHERE id_fascia = ? AND stato <> 'Annullato'");
        $stmtVenduti->execute([$idFascia]);
        $venduti = (int)$stmtVenduti->fetchColumn();
        $postiDisponibili = (int)$fascia['capienza_massima'] - $venduti;
        if (!$prenotazioneDocente && $quantita > $postiDisponibili) {
            throw new RuntimeException('Posti insufficienti nella fascia selezionata. Posti disponibili: ' . max(0, $postiDisponibili));
        }
        $dataValidita = $fascia['data'];
        $titoloPercorso = $fascia['titolo'];
    } else {
        $dataValidita = $dati['data_visita'] ?? '';
        if (!$dataValidita || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataValidita)) {
            throw new RuntimeException('Seleziona una data visita valida.');
        }
    }

    $servizi = [];
    if (!empty($serviziIds)) {
        $placeholders = implode(',', array_fill(0, count($serviziIds), '?'));
        $stmtServizi = $pdo->prepare("SELECT id_servizio, nome, prezzo FROM Servizi_Opzionali WHERE id_servizio IN ($placeholders)");
        $stmtServizi->execute($serviziIds);
        $servizi = $stmtServizi->fetchAll();
    }

    $totaleServiziSingolo = array_reduce($servizi, fn($sum, $s) => $sum + (float)$s['prezzo'], 0.0);
    $totaleStudenti = ($prezzoFinale + $totaleServiziSingolo) * $quantitaStudenti;
    $totaleDocenti = $prenotazioneDocente ? ($totaleServiziSingolo * $numeroDocenti) : 0.0;
    $totale = $totaleStudenti + $totaleDocenti;

    return compact(
        'tipo', 'nomeCliente', 'emailCliente', 'idTariffa', 'prenotazioneDocente', 'metodoPagamento',
        'quantitaStudenti', 'numeroDocenti', 'quantita', 'nomeScuola', 'codiceMeccanografico',
        'indirizzoScuola', 'cittaScuola', 'telefonoScuola', 'classeScuola', 'noteScuola',
        'servizi', 'prezzoLordo', 'prezzoFinale', 'scontoApplicato', 'idCategoria', 'idCategoriaDocente',
        'idFascia', 'dataValidita', 'totale', 'titoloPercorso'
    );
}

function creaOrdineConBiglietti(PDO $pdo, array $datiOrdine, string $statoPagamento, string $statoBiglietto): array {
    $codiceRecupero = generaCodiceOrdine($pdo);
    $idUtente = isLogged() ? (int)$_SESSION['utente_id'] : null;

    $pdo->beginTransaction();
    try {
        $campiOrdine = ['id_utente', 'codice_recupero', 'nome_cliente', 'email_cliente', 'importo_totale', 'stato_pagamento'];
        $valoriOrdine = [$idUtente, $codiceRecupero, $datiOrdine['nomeCliente'], $datiOrdine['emailCliente'], $datiOrdine['totale'], $statoPagamento];

        if (colonnaEsiste($pdo, 'Ordini', 'metodo_pagamento')) {
            $campiOrdine[] = 'metodo_pagamento';
            $valoriOrdine[] = $datiOrdine['metodoPagamento'];
        }

        $extraOrdine = [
            'prenotazione_docente' => $datiOrdine['prenotazioneDocente'] ? 1 : 0,
            'nome_scuola' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['nomeScuola'] : null,
            'codice_meccanografico' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['codiceMeccanografico'] : null,
            'indirizzo_scuola' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['indirizzoScuola'] : null,
            'citta_scuola' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['cittaScuola'] : null,
            'telefono_scuola' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['telefonoScuola'] : null,
            'classe_scuola' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['classeScuola'] : null,
            'quantita_studenti' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['quantitaStudenti'] : null,
            'numero_docenti' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['numeroDocenti'] : null,
            'note_scuola' => $datiOrdine['prenotazioneDocente'] ? $datiOrdine['noteScuola'] : null,
        ];

        foreach ($extraOrdine as $campo => $valore) {
            if (colonnaEsiste($pdo, 'Ordini', $campo)) {
                $campiOrdine[] = $campo;
                $valoriOrdine[] = $valore;
            }
        }

        $placeholdersOrdine = implode(',', array_fill(0, count($campiOrdine), '?'));
        $sqlOrdine = 'INSERT INTO Ordini (' . implode(',', $campiOrdine) . ') VALUES (' . $placeholdersOrdine . ')';
        $stmtOrdine = $pdo->prepare($sqlOrdine);
        $stmtOrdine->execute($valoriOrdine);
        $idOrdine = (int)$pdo->lastInsertId();

        $stmtBiglietto = $pdo->prepare(" 
            INSERT INTO Biglietti
            (codice_univoco, id_ordine, tipo, data_validita, id_fascia, id_categoria, prezzo_lordo, sconto_applicato, stato)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtBS = $pdo->prepare(" 
            INSERT INTO Biglietti_Servizi (id_biglietto, id_servizio, prezzo_snapshot)
            VALUES (?, ?, ?)
        ");

        $codici = [];
        for ($i = 0; $i < $datiOrdine['quantitaStudenti']; $i++) {
            $codiceBiglietto = generaCodiceBiglietto($pdo);
            $stmtBiglietto->execute([
                $codiceBiglietto,
                $idOrdine,
                $datiOrdine['tipo'],
                $datiOrdine['dataValidita'],
                $datiOrdine['idFascia'],
                $datiOrdine['idCategoria'],
                $datiOrdine['prezzoLordo'],
                $datiOrdine['scontoApplicato'],
                $statoBiglietto
            ]);
            $idBiglietto = (int)$pdo->lastInsertId();
            foreach ($datiOrdine['servizi'] as $servizio) {
                $stmtBS->execute([$idBiglietto, (int)$servizio['id_servizio'], (float)$servizio['prezzo']]);
            }
            $codici[] = $codiceBiglietto;
        }

        for ($i = 0; $i < $datiOrdine['numeroDocenti']; $i++) {
            $codiceBiglietto = generaCodiceBiglietto($pdo);
            $stmtBiglietto->execute([
                $codiceBiglietto,
                $idOrdine,
                $datiOrdine['tipo'],
                $datiOrdine['dataValidita'],
                $datiOrdine['idFascia'],
                $datiOrdine['idCategoriaDocente'],
                0.00,
                0.00,
                $statoBiglietto
            ]);
            $idBiglietto = (int)$pdo->lastInsertId();
            foreach ($datiOrdine['servizi'] as $servizio) {
                $stmtBS->execute([$idBiglietto, (int)$servizio['id_servizio'], (float)$servizio['prezzo']]);
            }
            $codici[] = $codiceBiglietto;
        }

        $pdo->commit();

        $ordine = [
            'id_ordine' => $idOrdine,
            'codice_recupero' => $codiceRecupero,
            'nome_cliente' => $datiOrdine['nomeCliente'],
            'email_cliente' => $datiOrdine['emailCliente'],
            'importo_totale' => $datiOrdine['totale'],
            'stato_pagamento' => $statoPagamento,
            'metodo_pagamento' => $datiOrdine['metodoPagamento'],
            'quantita' => $datiOrdine['quantita'],
            'quantita_studenti' => $datiOrdine['quantitaStudenti'],
            'numero_docenti' => $datiOrdine['numeroDocenti'],
            'tipo' => $datiOrdine['tipo'],
            'prenotazione_docente' => $datiOrdine['prenotazioneDocente'],
            'nome_scuola' => $datiOrdine['nomeScuola'],
            'classe_scuola' => $datiOrdine['classeScuola'],
            'data_validita' => $datiOrdine['dataValidita'],
            'titolo_percorso' => $datiOrdine['titoloPercorso'],
            'servizi_descrizione' => implode(', ', array_map(fn($s) => $s['nome'] ?? '', $datiOrdine['servizi'])),
        ];

        return ['ordine' => $ordine, 'codici' => $codici];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function creaPayloadPagamento(array $dati): string {
    unset($dati['csrf_token']);
    return base64_encode(json_encode($dati, JSON_UNESCAPED_UNICODE));
}

function leggiPayloadPagamento(string $payload): array {
    $json = base64_decode($payload, true);
    if ($json === false) {
        throw new RuntimeException('Dati pagamento non validi.');
    }
    $dati = json_decode($json, true);
    if (!is_array($dati)) {
        throw new RuntimeException('Dati pagamento non validi.');
    }
    return normalizzaInputPagamento($dati);
}


function cartaLuhnValida(string $numero): bool {
    // Pagamento simulato: accetta numeri realistici da 13 a 19 cifre.
    // Non richiede il controllo Luhn, così le carte di test/fittizie non vengono respinte sempre.
    if (!preg_match('/^\d{13,19}$/', $numero)) {
        return false;
    }

    // Evita solo valori chiaramente finti, per esempio 0000000000000000 o 1111111111111111.
    return !preg_match('/^(\d)\1{12,18}$/', $numero);
}

function scadenzaCartaValida(string $scadenza): bool {
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $scadenza, $m)) {
        return false;
    }
    $mese = (int)$m[1];
    $anno = 2000 + (int)$m[2];
    $ultimoGiorno = strtotime(sprintf('%04d-%02d-01 +1 month -1 day 23:59:59', $anno, $mese));
    return $ultimoGiorno !== false && $ultimoGiorno >= time();
}

function validaPagamentoSimulato(string $metodo, array $input): void {
    if ($metodo === 'carta') {
        $numeroCarta = preg_replace('/\D+/', '', $input['numero_carta'] ?? '');
        $titolare = trim($input['titolare'] ?? '');
        $scadenza = trim($input['scadenza'] ?? '');
        $cvv = preg_replace('/\D+/', '', $input['cvv'] ?? '');

        if ($titolare === '') {
            throw new RuntimeException('Inserisci il titolare della carta.');
        }
        if (!cartaLuhnValida($numeroCarta)) {
            throw new RuntimeException('Numero carta non valido. Puoi correggere i dati oppure generare l’ordine come non pagato.');
        }
        if (!scadenzaCartaValida($scadenza)) {
            throw new RuntimeException('Scadenza carta non valida o già passata. Puoi correggere i dati oppure generare l’ordine come non pagato.');
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            throw new RuntimeException('CVV non valido. Puoi correggere i dati oppure generare l’ordine come non pagato.');
        }
    } elseif ($metodo === 'paypal') {
        $paypalEmail = trim($input['paypal_email'] ?? '');
        if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Inserisci un indirizzo email PayPal valido. Puoi correggerlo oppure generare l’ordine come non pagato.');
        }
    } else {
        throw new RuntimeException('Metodo di pagamento non valido.');
    }
}

function creaFormPagamentoDaDati(array $dati, array $datiOrdine): array {
    return [
        'metodo' => $datiOrdine['metodoPagamento'],
        'payload' => creaPayloadPagamento($dati),
        'totale' => $datiOrdine['totale'],
        'nome' => $datiOrdine['nomeCliente'],
        'email' => $datiOrdine['emailCliente'],
        'percorso' => $datiOrdine['titoloPercorso'],
    ];
}

function caricaOrdineUtenteDaPagare(PDO $pdo, int $idOrdine): ?array {
    if (!isLogged()) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT
            o.*,
            (SELECT COUNT(*) FROM Biglietti b WHERE b.id_ordine = o.id_ordine) AS quantita,
            (SELECT MIN(b.tipo) FROM Biglietti b WHERE b.id_ordine = o.id_ordine) AS tipo,
            (SELECT MIN(b.data_validita) FROM Biglietti b WHERE b.id_ordine = o.id_ordine) AS data_validita,
            (SELECT GROUP_CONCAT(b.codice_univoco ORDER BY b.id_biglietto SEPARATOR ',') FROM Biglietti b WHERE b.id_ordine = o.id_ordine) AS codici_biglietti,
            COALESCE((
                SELECT GROUP_CONCAT(DISTINCT e.titolo SEPARATOR ', ')
                FROM Biglietti b
                LEFT JOIN Fasce_Orarie f ON b.id_fascia = f.id_fascia
                LEFT JOIN Esposizioni e ON f.id_esposizione = e.id_esposizione
                WHERE b.id_ordine = o.id_ordine
            ), 'Museo Storico Severi') AS titolo_percorso
        FROM Ordini o
        WHERE o.id_ordine = ?
          AND o.id_utente = ?
        LIMIT 1
    ");
    $stmt->execute([$idOrdine, (int)$_SESSION['utente_id']]);
    $ordine = $stmt->fetch();
    return $ordine ?: null;
}

function marcaOrdinePagato(PDO $pdo, int $idOrdine, string $metodo): void {
    $pdo->beginTransaction();
    try {
        if (colonnaEsiste($pdo, 'Ordini', 'metodo_pagamento')) {
            $stmt = $pdo->prepare("UPDATE Ordini SET stato_pagamento = 'Pagato', metodo_pagamento = ? WHERE id_ordine = ?");
            $stmt->execute([$metodo, $idOrdine]);
        } else {
            $stmt = $pdo->prepare("UPDATE Ordini SET stato_pagamento = 'Pagato' WHERE id_ordine = ?");
            $stmt->execute([$idOrdine]);
        }

        $stmt = $pdo->prepare("UPDATE Biglietti SET stato = 'Valido' WHERE id_ordine = ? AND stato = 'Non pagato'");
        $stmt->execute([$idOrdine]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function codiciDaOrdine(array $ordine): array {
    $codici = trim((string)($ordine['codici_biglietti'] ?? ''));
    if ($codici === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode(',', $codici))));
}

function inviaEmailOrdineDopoRisposta(array $ordine, array $codici): void {
    // L'invio email e la creazione del PDF non devono rallentare la pagina di pagamento.
    // Se il server supporta fastcgi_finish_request(), la pagina viene mostrata subito
    // e l'email viene gestita dopo la risposta al browser.
    if (function_exists('session_write_close')) {
        @session_write_close();
    }

    if (function_exists('fastcgi_finish_request')) {
        @fastcgi_finish_request();
    } else {
        @ob_flush();
        @flush();
    }

    try {
        @set_time_limit(20);
        require_once __DIR__ . '/ordine_pdf.php';
        require_once __DIR__ . '/app_mailer.php';

        $tmpBase = tempnam(sys_get_temp_dir(), 'mss_ordine_');
        if ($tmpBase === false) {
            throw new RuntimeException('Impossibile creare il file temporaneo PDF.');
        }

        $tmpPdf = $tmpBase . '.pdf';
        @rename($tmpBase, $tmpPdf);
        file_put_contents($tmpPdf, creaPdfOrdine($ordine, $codici));
        inviaEmailConfermaOrdine($ordine, $codici, $tmpPdf);
        @unlink($tmpPdf);
    } catch (Throwable $e) {
        $dir = __DIR__ . '/mail_debug';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/mail_error_log.txt',
            '[' . date('Y-m-d H:i:s') . '] Conferma ordine rimandata/fallita: ' . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ordine'])) {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }

    $ordineDaPagare = caricaOrdineUtenteDaPagare($pdo, (int)($_GET['ordine'] ?? 0));
    if (!$ordineDaPagare) {
        $errore = 'Ordine non trovato oppure non associato al tuo account.';
    } elseif (($ordineDaPagare['stato_pagamento'] ?? '') === 'Pagato') {
        header('Location: ' . SITE_URL . '/biglietti.php?codice=' . urlencode($ordineDaPagare['codice_recupero']));
        exit;
    } else {
        $pagamentoOrdineEsistente = $ordineDaPagare;
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errore = 'Richiesta non valida. Torna alla pagina esposizioni e avvia una nuova prenotazione.';
} elseif (($_POST['paga_ordine'] ?? '') === '1') {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $idOrdine = (int)($_POST['id_ordine'] ?? 0);
        $metodo = 'carta';
        $ordineDaPagare = caricaOrdineUtenteDaPagare($pdo, $idOrdine);

        if (!$ordineDaPagare) {
            $errore = 'Ordine non trovato oppure non associato al tuo account.';
        } elseif (($ordineDaPagare['stato_pagamento'] ?? '') === 'Pagato') {
            $ordine = $ordineDaPagare;
        } else {
            try {
                validaPagamentoSimulato($metodo, $_POST);
                marcaOrdinePagato($pdo, $idOrdine, $metodo);
                $ordine = caricaOrdineUtenteDaPagare($pdo, $idOrdine) ?: $ordineDaPagare;
                $ordine['stato_pagamento'] = 'Pagato';
                $ordine['metodo_pagamento'] = $metodo;
                $emailOrdineDaInviare = ['ordine' => $ordine, 'codici' => codiciDaOrdine($ordine)];
            } catch (Throwable $e) {
                $errorePagamento = $e->getMessage();
                $pagamentoOrdineEsistente = $ordineDaPagare;
                $pagamentoOrdineEsistente['metodo_pagamento'] = $metodo;
            }
        }
    }
} elseif (($_POST['genera_non_pagato'] ?? '') === '1') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        try {
            $dati = leggiPayloadPagamento($_POST['payload'] ?? '');
            $datiOrdine = preparaOrdine($pdo, $dati);
            $result = creaOrdineConBiglietti($pdo, $datiOrdine, 'Non pagato', 'Non pagato');
            $ordine = $result['ordine'];
            $bigliettiCreati = $result['codici'];
            $emailOrdineDaInviare = ['ordine' => $ordine, 'codici' => $bigliettiCreati];
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }
} elseif (($_POST['conferma_pagamento'] ?? '') === '1') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        try {
            $dati = leggiPayloadPagamento($_POST['payload'] ?? '');
            $datiOrdine = preparaOrdine($pdo, $dati);
            $metodo = $datiOrdine['metodoPagamento'];
            $formPagamento = creaFormPagamentoDaDati($dati, $datiOrdine);

            validaPagamentoSimulato($metodo, $_POST);

            $result = creaOrdineConBiglietti($pdo, $datiOrdine, 'Pagato', 'Valido');
            $ordine = $result['ordine'];
            $bigliettiCreati = $result['codici'];
            $formPagamento = null;
            $emailOrdineDaInviare = ['ordine' => $ordine, 'codici' => $bigliettiCreati];
        } catch (Throwable $e) {
            $errorePagamento = $e->getMessage();
            if (empty($formPagamento)) {
                $errore = $e->getMessage();
            }
        }
    }
} elseif (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $errore = 'Token di sicurezza non valido. Riprova.';
} else {
    try {
        $dati = normalizzaInputPagamento($_POST);
        $datiOrdine = preparaOrdine($pdo, $dati);

        if ($datiOrdine['metodoPagamento'] === 'contanti') {
            $result = creaOrdineConBiglietti($pdo, $datiOrdine, 'Non pagato', 'Non pagato');
            $ordine = $result['ordine'];
            $bigliettiCreati = $result['codici'];
            $emailOrdineDaInviare = ['ordine' => $ordine, 'codici' => $bigliettiCreati];
        } else {
            $formPagamento = creaFormPagamentoDaDati($dati, $datiOrdine);
        }
    } catch (Throwable $e) {
        $errore = $e->getMessage();
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Pagamento</span>
  </div>
</div>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <?php if ($errore): ?>
    <div class="bg-white rounded-2xl shadow border border-avorio-dark p-8 text-center">
      <div class="text-5xl mb-4">⚠️</div>
      <h1 class="font-display text-3xl font-bold text-antracite mb-4">Pagamento non completato</h1>
      <div class="alert-error p-4 rounded text-sm mb-6 text-left">Errore: <?= clean($errore) ?></div>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline px-6 py-3 rounded inline-block">Torna alle esposizioni</a>
    </div>

  <?php elseif ($formPagamento): ?>
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="text-5xl mb-4"><?= $formPagamento['metodo'] === 'paypal' ? '🅿️' : '💳' ?></div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Pagamento simulato</p>
        <h1 class="font-display text-avorio text-3xl font-bold">
          <?= $formPagamento['metodo'] === 'paypal' ? 'Accesso PayPal' : 'Dati carta di credito' ?>
        </h1>
      </div>

      <div class="p-8 md:p-10">
        <?php if ($errorePagamento): ?>
          <div class="alert-error p-4 rounded text-sm mb-6" role="alert">
            ⚠️ <?= clean($errorePagamento) ?>
          </div>
        <?php endif; ?>

        <div class="bg-avorio rounded-xl p-5 mb-8 text-sm text-gray-600">
          <p><strong>Acquirente:</strong> <?= clean($formPagamento['nome']) ?> · <?= clean($formPagamento['email']) ?></p>
          <p><strong>Percorso:</strong> <?= clean($formPagamento['percorso']) ?></p>
          <p><strong>Totale:</strong> € <?= number_format((float)$formPagamento['totale'], 2, ',', '.') ?></p>
        </div>

        <form method="POST" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="conferma_pagamento" value="1">
          <input type="hidden" name="payload" value="<?= clean($formPagamento['payload']) ?>">

          <?php if ($formPagamento['metodo'] === 'carta'): ?>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Titolare carta</label>
              <input type="text" name="titolare" required placeholder="Mario Rossi" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Numero carta</label>
              <input type="text" name="numero_carta" required placeholder="4111 1111 1111 1111" maxlength="23" inputmode="numeric" autocomplete="cc-number" class="js-card-number w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">Scadenza</label>
                <input type="text" name="scadenza" required placeholder="MM/AA" maxlength="5" inputmode="numeric" autocomplete="cc-exp" class="js-card-expiry w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
              </div>
              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">CVV</label>
                <input type="text" name="cvv" required placeholder="123" maxlength="4" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
              </div>
            </div>
          <?php else: ?>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Email PayPal</label>
              <input type="email" name="paypal_email" required placeholder="nome@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
          <?php endif; ?>

          <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" class="btn-oro flex-1 px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
              Continua e completa pagamento
            </button>
          </div>
        </form>

        <?php if ($errorePagamento): ?>
          <form method="POST" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="genera_non_pagato" value="1">
            <input type="hidden" name="payload" value="<?= clean($formPagamento['payload']) ?>">
            <button type="submit" class="btn-outline w-full px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
              Genera biglietti come non pagati
            </button>
          </form>
          <p class="text-xs text-gray-500 mt-3 text-center">
            Puoi correggere i dati qui sopra oppure creare l’ordine non pagato e saldarlo più tardi dai tuoi ordini.
          </p>
        <?php endif; ?>
      </div>
    </section>

  <?php elseif ($pagamentoOrdineEsistente): ?>
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="text-5xl mb-4">💳</div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Pagamento ordine esistente</p>
        <h1 class="font-display text-avorio text-3xl font-bold">Salda ordine non pagato</h1>
      </div>

      <div class="p-8 md:p-10">
        <?php if ($errorePagamento): ?>
          <div class="alert-error p-4 rounded text-sm mb-6" role="alert">
            ⚠️ <?= clean($errorePagamento) ?>
          </div>
        <?php endif; ?>

        <div class="bg-avorio rounded-xl p-5 mb-8 text-sm text-gray-600">
          <p><strong>Ordine:</strong> <?= clean($pagamentoOrdineEsistente['codice_recupero']) ?></p>
          <p><strong>Acquirente:</strong> <?= clean($pagamentoOrdineEsistente['nome_cliente'] ?? '') ?> · <?= clean($pagamentoOrdineEsistente['email_cliente'] ?? '') ?></p>
          <p><strong>Totale:</strong> € <?= number_format((float)($pagamentoOrdineEsistente['importo_totale'] ?? 0), 2, ',', '.') ?></p>
        </div>

        <form method="POST" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="paga_ordine" value="1">
          <input type="hidden" name="id_ordine" value="<?= (int)$pagamentoOrdineEsistente['id_ordine'] ?>">

          <input type="hidden" name="metodo_pagamento" value="carta">

          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-3">Metodo di pagamento</label>
            <div class="border border-avorio-dark rounded-xl p-4 bg-white">
              <span class="block font-bold text-antracite text-sm">Carta di credito</span>
              <span class="block text-xs text-gray-500 mt-1">Pagamento simulato. Inserisci i dati della carta per saldare l’ordine.</span>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Titolare carta</label>
              <input type="text" name="titolare" required placeholder="Mario Rossi" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Numero carta</label>
              <input type="text" name="numero_carta" required placeholder="4111 1111 1111 1111" maxlength="23" inputmode="numeric" autocomplete="cc-number" class="js-card-number w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Scadenza</label>
              <input type="text" name="scadenza" required placeholder="MM/AA" maxlength="5" inputmode="numeric" autocomplete="cc-exp" class="js-card-expiry w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">CVV</label>
              <input type="text" name="cvv" required placeholder="123" maxlength="4" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
          </div>

          <button type="submit" class="btn-oro w-full px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
            Paga ordine
          </button>
        </form>
      </div>
    </section>

  <?php elseif ($ordine): ?>
    <?php $pagato = ($ordine['stato_pagamento'] === 'Pagato'); ?>
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="text-5xl mb-4"><?= $pagato ? '✅' : '💶' ?></div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">
          <?= $pagato ? 'Pagamento riuscito' : 'Pagamento da completare' ?>
        </p>
        <h1 class="font-display text-avorio text-3xl font-bold">
          <?= $pagato ? 'Ordine confermato' : 'Ordine emesso' ?>
        </h1>
      </div>
      <div class="p-8 md:p-10 text-center">
        <?php if (!$pagato): ?>
          <div class="alert-error p-4 rounded text-sm mb-6 text-left">
            I biglietti sono stati creati con stato <strong>Non pagato</strong>. Diventeranno validi dopo il saldo dall’area ordini o in cassa.
          </div>
        <?php endif; ?>

        <p class="text-gray-600 mb-4">Conserva questo codice: ti servirà per recuperare i biglietti anche senza login.</p>
        <div class="inline-block bg-avorio border-2 border-dashed border-oro rounded-xl px-8 py-5 mb-6">
          <div class="text-xs uppercase tracking-widest text-gray-500 font-body mb-1">Codice ordine</div>
          <div class="font-display text-3xl text-antracite font-bold tracking-wide"><?= clean($ordine['codice_recupero']) ?></div>
        </div>

        <div class="grid sm:grid-cols-4 gap-4 mb-8 text-left">
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Biglietti</div>
            <div class="font-bold text-antracite text-xl"><?= (int)$ordine['quantita'] ?></div>
          </div>
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Metodo</div>
            <div class="font-bold text-antracite text-xl"><?= clean(ucfirst($ordine['metodo_pagamento'])) ?></div>
          </div>
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Stato</div>
            <div class="font-bold text-antracite text-xl"><?= clean($ordine['stato_pagamento']) ?></div>
          </div>
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Totale</div>
            <div class="font-display text-oro font-bold text-xl">€ <?= number_format((float)$ordine['importo_totale'], 2, ',', '.') ?></div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a href="<?= SITE_URL ?>/biglietti.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-oro px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
            Vedi biglietti
          </a>
          <a href="<?= SITE_URL ?>/scarica_pdf.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-outline px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
            Scarica PDF
          </a>
          <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
            Torna alle esposizioni
          </a>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>


<script>
(function () {
  function formattaNumeroCarta(input) {
    var soloNumeri = input.value.replace(/\D/g, '').slice(0, 19);
    input.value = soloNumeri.replace(/(.{4})(?=.)/g, '$1 ');
  }

  function formattaScadenzaCarta(input) {
    var soloNumeri = input.value.replace(/\D/g, '').slice(0, 4);
    if (soloNumeri.length > 2) {
      input.value = soloNumeri.slice(0, 2) + '/' + soloNumeri.slice(2);
    } else {
      input.value = soloNumeri;
    }
  }

  document.querySelectorAll('.js-card-number').forEach(function (input) {
    formattaNumeroCarta(input);
    input.addEventListener('input', function () {
      formattaNumeroCarta(input);
    });
  });

  document.querySelectorAll('.js-card-expiry').forEach(function (input) {
    formattaScadenzaCarta(input);
    input.addEventListener('input', function () {
      formattaScadenzaCarta(input);
    });
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>

<?php
if (is_array($emailOrdineDaInviare)) {
    inviaEmailOrdineDopoRisposta($emailOrdineDaInviare['ordine'], $emailOrdineDaInviare['codici']);
}
?>
