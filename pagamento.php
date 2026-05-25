<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Pagamento';
$pdo = getDB();
$errore = '';
$ordine = null;
$formPagamento = null;
$formRicaricaPortafoglio = null;
$ricaricaPortafoglioCompletata = null;
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
    if (function_exists('dbColumnExists')) {
        return dbColumnExists($pdo, $tabella, $colonna);
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabella` LIKE ?");
        $stmt->execute([$colonna]);
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function preparaSupportoPagamentoSaldo(PDO $pdo): void {
    preparaPortafoglioUtente($pdo);

    if ($pdo->inTransaction()) {
        return;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Ordini LIKE 'metodo_pagamento'");
        $colonna = $stmt->fetch();
        $tipo = strtolower((string)($colonna['Type'] ?? ''));

        if ($colonna && strpos($tipo, "'saldo'") === false) {
            $pdo->exec("ALTER TABLE Ordini MODIFY metodo_pagamento ENUM('contanti','carta','paypal','saldo') NOT NULL DEFAULT 'carta'");
        } elseif (!$colonna) {
            $pdo->exec("ALTER TABLE Ordini ADD COLUMN metodo_pagamento ENUM('contanti','carta','paypal','saldo') NOT NULL DEFAULT 'carta'");
        }
    } catch (Throwable $e) {
        // Se l'hosting non permette ALTER, il salvataggio dell'ordine mostrera l'errore reale.
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
    $prenotazioneDocente = ($dati['prenotazione_docente'] ?? '') === '1';
    $metodoPagamento = $dati['metodo_pagamento'] ?? 'carta';

    if (!in_array($metodoPagamento, ['contanti', 'carta', 'paypal', 'saldo'], true)) {
        throw new RuntimeException('Metodo di pagamento non valido.');
    }
    if ($metodoPagamento === 'saldo' && !isLogged()) {
        throw new RuntimeException('Il pagamento con saldo richiede il login.');
    }
    if (!in_array($tipo, ['base', 'esposizione'], true)) {
        throw new RuntimeException('Tipo biglietto non valido.');
    }
    if ($nomeCliente === '' || !filter_var($emailCliente, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Inserisci nome, cognome ed email validi.');
    }

    $nomeScuola = trim($dati['nome_scuola'] ?? '');
    $codiceMeccanografico = strtoupper(trim($dati['codice_meccanografico'] ?? ''));
    $indirizzoScuola = trim($dati['indirizzo_scuola'] ?? '');
    $cittaScuola = trim($dati['citta_scuola'] ?? '');
    $telefonoScuola = trim($dati['telefono_scuola'] ?? '');
    $classeScuola = trim($dati['classe_scuola'] ?? '');
    $noteScuola = trim($dati['note_scuola'] ?? '');

    if ($prenotazioneDocente && ($nomeScuola === '' || $cittaScuola === '' || $classeScuola === '')) {
        throw new RuntimeException('Inserisci almeno nome scuola, città e classe/sezione.');
    }

    $serviziIds = array_values(array_unique(array_map('intval', $dati['servizi'] ?? [])));

    $idTariffa = (int)($dati['id_tariffa'] ?? 0);
    $tariffaQuantitaRaw = $dati['tariffa_quantita'] ?? [];
    if (!is_array($tariffaQuantitaRaw)) {
        $tariffaQuantitaRaw = [];
    }

    $richiesteTariffe = [];
    foreach ($tariffaQuantitaRaw as $id => $qta) {
        $id = (int)$id;
        $qta = (int)$qta;
        if ($id > 0 && $qta > 0) {
            $richiesteTariffe[$id] = min(50, $qta);
        }
    }

    if (empty($richiesteTariffe)) {
        $quantitaRichiesta = (int)($dati['quantita'] ?? 1);
        $quantitaStudenti = $prenotazioneDocente
            ? max(1, (int)($dati['quantita_studenti'] ?? $quantitaRichiesta))
            : max(1, min(20, $quantitaRichiesta));
        if ($idTariffa <= 0) {
            throw new RuntimeException('Seleziona almeno una categoria di biglietto.');
        }
        $richiesteTariffe = [$idTariffa => $quantitaStudenti];
    } else {
        $quantitaStudenti = array_sum($richiesteTariffe);
        if ($quantitaStudenti <= 0) {
            throw new RuntimeException('Seleziona almeno un biglietto.');
        }
        if ($quantitaStudenti > 120) {
            throw new RuntimeException('Puoi acquistare al massimo 120 biglietti per ordine classe.');
        }
        $idTariffa = (int)array_key_first($richiesteTariffe);
    }

    $numeroDocenti = $prenotazioneDocente ? max(0, (int)($dati['numero_docenti'] ?? 0)) : 0;
    $quantita = $quantitaStudenti + $numeroDocenti;

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
    $prezzoLordoDefault = (float)$prezzoIntero;

    $tariffaItems = [];
    $idsTariffe = array_keys($richiesteTariffe);
    $placeholdersTariffe = implode(',', array_fill(0, count($idsTariffe), '?'));
    $stmtTariffe = $pdo->prepare(" 
        SELECT t.*, cr.nome AS categoria, cr.percentuale_sconto
        FROM Tariffe t
        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
        WHERE t.id_tariffa IN ($placeholdersTariffe) AND t.tipo_biglietto = ?
    ");
    $stmtTariffe->execute(array_merge($idsTariffe, [$tipo]));
    $tariffeDb = [];
    foreach ($stmtTariffe->fetchAll() as $rigaTariffa) {
        $tariffeDb[(int)$rigaTariffa['id_tariffa']] = $rigaTariffa;
    }

    foreach ($richiesteTariffe as $idT => $qta) {
        if (!isset($tariffeDb[$idT])) {
            throw new RuntimeException('Una delle tariffe selezionate non è valida.');
        }
        $tariffa = $tariffeDb[$idT];
        if (strcasecmp(trim((string)$tariffa['categoria']), 'Docente accompagnatore') === 0) {
            throw new RuntimeException('La tariffa docente accompagnatore è riservata alla prenotazione classe e viene gestita automaticamente.');
        }
        $prezzoFinaleItem = (float)$tariffa['prezzo'];
        $tariffaItems[] = [
            'id_tariffa' => (int)$tariffa['id_tariffa'],
            'id_categoria' => (int)$tariffa['id_categoria'],
            'categoria' => (string)$tariffa['categoria'],
            'quantita' => (int)$qta,
            'prezzoLordo' => $prezzoLordoDefault,
            'prezzoFinale' => $prezzoFinaleItem,
            'scontoApplicato' => max(0, $prezzoLordoDefault - $prezzoFinaleItem),
        ];
    }

    $idCategoria = (int)$tariffaItems[0]['id_categoria'];
    $prezzoLordo = (float)$tariffaItems[0]['prezzoLordo'];
    $prezzoFinale = (float)$tariffaItems[0]['prezzoFinale'];
    $scontoApplicato = (float)$tariffaItems[0]['scontoApplicato'];
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
    $totaleStudenti = 0.0;
    foreach ($tariffaItems as $item) {
        $totaleStudenti += ((float)$item['prezzoFinale'] + $totaleServiziSingolo) * (int)$item['quantita'];
    }
    $totaleDocenti = $prenotazioneDocente ? ($totaleServiziSingolo * $numeroDocenti) : 0.0;
    $totale = $totaleStudenti + $totaleDocenti;

    return compact(
        'tipo', 'nomeCliente', 'emailCliente', 'idTariffa', 'prenotazioneDocente', 'metodoPagamento',
        'quantitaStudenti', 'numeroDocenti', 'quantita', 'nomeScuola', 'codiceMeccanografico',
        'indirizzoScuola', 'cittaScuola', 'telefonoScuola', 'classeScuola', 'noteScuola',
        'servizi', 'prezzoLordo', 'prezzoFinale', 'scontoApplicato', 'idCategoria', 'idCategoriaDocente',
        'idFascia', 'dataValidita', 'totale', 'titoloPercorso', 'tariffaItems'
    );
}

function creaOrdineConBiglietti(PDO $pdo, array $datiOrdine, string $statoPagamento, string $statoBiglietto): array {
    $codiceRecupero = generaCodiceOrdine($pdo);
    $idUtente = isLogged() ? (int)$_SESSION['utente_id'] : null;

    if (($datiOrdine['metodoPagamento'] ?? '') === 'saldo' && $statoPagamento === 'Pagato') {
        if ($idUtente === null) {
            throw new RuntimeException('Il pagamento con saldo richiede il login.');
        }
        preparaSupportoPagamentoSaldo($pdo);
    }

    $pdo->beginTransaction();
    try {
        if (($datiOrdine['metodoPagamento'] ?? '') === 'saldo' && $statoPagamento === 'Pagato') {
            if ($idUtente === null) {
                throw new RuntimeException('Il pagamento con saldo richiede il login.');
            }
            addebitaSaldoUtente($pdo, (int)$idUtente, (float)$datiOrdine['totale']);
        }
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
        $tariffaItems = $datiOrdine['tariffaItems'] ?? [[
            'quantita' => $datiOrdine['quantitaStudenti'],
            'id_categoria' => $datiOrdine['idCategoria'],
            'prezzoLordo' => $datiOrdine['prezzoLordo'],
            'scontoApplicato' => $datiOrdine['scontoApplicato'],
        ]];

        foreach ($tariffaItems as $item) {
            for ($i = 0; $i < (int)$item['quantita']; $i++) {
                $codiceBiglietto = generaCodiceBiglietto($pdo);
                $stmtBiglietto->execute([
                    $codiceBiglietto,
                    $idOrdine,
                    $datiOrdine['tipo'],
                    $datiOrdine['dataValidita'],
                    $datiOrdine['idFascia'],
                    (int)$item['id_categoria'],
                    (float)$item['prezzoLordo'],
                    (float)$item['scontoApplicato'],
                    $statoBiglietto
                ]);
                $idBiglietto = (int)$pdo->lastInsertId();
                foreach ($datiOrdine['servizi'] as $servizio) {
                    $stmtBS->execute([$idBiglietto, (int)$servizio['id_servizio'], (float)$servizio['prezzo']]);
                }
                $codici[] = $codiceBiglietto;
            }
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
    } elseif ($metodo === 'saldo' || $metodo === 'contanti') {
        return;
    } else {
        throw new RuntimeException('Metodo di pagamento non valido.');
    }
}

function normalizzaImportoRicarica($valore): float {
    $importo = round((float)str_replace(',', '.', (string)$valore), 2);
    if ($importo < 1 || $importo > 500) {
        throw new RuntimeException('Inserisci un importo di ricarica valido tra 1 e 500 euro.');
    }
    return $importo;
}

function validaMetodoRicarica(string $metodo): string {
    $metodo = strtolower(trim($metodo));
    if (!in_array($metodo, ['carta', 'paypal'], true)) {
        throw new RuntimeException('Per la ricarica puoi scegliere solo carta di credito o PayPal.');
    }
    return $metodo;
}

function creaPayloadRicaricaPortafoglio(float $importo, string $metodo): string {
    return base64_encode(json_encode([
        'tipo' => 'ricarica_portafoglio',
        'importo' => number_format($importo, 2, '.', ''),
        'metodo' => $metodo,
    ], JSON_UNESCAPED_UNICODE));
}

function leggiPayloadRicaricaPortafoglio(string $payload): array {
    $json = base64_decode($payload, true);
    if ($json === false) {
        throw new RuntimeException('Dati ricarica non validi.');
    }
    $dati = json_decode($json, true);
    if (!is_array($dati) || ($dati['tipo'] ?? '') !== 'ricarica_portafoglio') {
        throw new RuntimeException('Dati ricarica non validi.');
    }
    $importo = normalizzaImportoRicarica($dati['importo'] ?? 0);
    $metodo = validaMetodoRicarica((string)($dati['metodo'] ?? ''));
    return ['importo' => $importo, 'metodo' => $metodo];
}

function creaFormRicaricaPortafoglio(float $importo, string $metodo): array {
    return [
        'importo' => $importo,
        'metodo' => $metodo,
        'payload' => creaPayloadRicaricaPortafoglio($importo, $metodo),
    ];
}

function completaRicaricaPortafoglio(PDO $pdo, float $importo): array {
    if (!isLogged()) {
        throw new RuntimeException('La ricarica del portafoglio richiede il login.');
    }
    ricaricaSaldoUtente($pdo, (int)$_SESSION['utente_id'], $importo);
    return [
        'importo' => $importo,
        'saldo' => saldoUtenteCorrente($pdo, (int)$_SESSION['utente_id']),
    ];
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

function marcaOrdinePagato(PDO $pdo, array $ordineDaPagare, string $metodo): void {
    $idOrdine = (int)($ordineDaPagare['id_ordine'] ?? 0);
    $idUtente = isLogged() ? (int)$_SESSION['utente_id'] : 0;
    $totale = (float)($ordineDaPagare['importo_totale'] ?? 0);

    if ($idOrdine <= 0) {
        throw new RuntimeException('Ordine non valido.');
    }
    if (strcasecmp((string)($ordineDaPagare['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0) {
        throw new RuntimeException('Questo ordine è stato rimborsato: non è possibile effettuare nuove operazioni.');
    }
    if (!in_array($metodo, ['carta', 'paypal', 'saldo'], true)) {
        throw new RuntimeException('Metodo di pagamento non valido.');
    }
    if ($metodo === 'saldo') {
        if ($idUtente <= 0) {
            throw new RuntimeException('Il pagamento con saldo richiede il login.');
        }
        if ((int)($ordineDaPagare['id_utente'] ?? 0) !== $idUtente) {
            throw new RuntimeException('Ordine non associato al tuo account.');
        }
        preparaSupportoPagamentoSaldo($pdo);
    }

    $pdo->beginTransaction();
    try {
        if ($metodo === 'saldo') {
            addebitaSaldoUtente($pdo, $idUtente, $totale);
        }
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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ricarica_portafoglio'])) {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    try {
        $prefill = $_SESSION['wallet_topup_prefill'] ?? null;
        if (!is_array($prefill) || (time() - (int)($prefill['created_at'] ?? 0)) > 900) {
            throw new RuntimeException('Avvia la ricarica dalla sezione Portafoglio virtuale del tuo account.');
        }
        $importo = normalizzaImportoRicarica($prefill['importo'] ?? 0);
        $metodo = validaMetodoRicarica((string)($prefill['metodo'] ?? 'carta'));
        $formRicaricaPortafoglio = creaFormRicaricaPortafoglio($importo, $metodo);
        unset($_SESSION['wallet_topup_prefill']);
    } catch (Throwable $e) {
        $errore = $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['avvia_ricarica_portafoglio'] ?? '') === '1')) {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        try {
            $importo = normalizzaImportoRicarica($_POST['importo'] ?? 0);
            $metodo = validaMetodoRicarica((string)($_POST['metodo_pagamento'] ?? 'carta'));
            $formRicaricaPortafoglio = creaFormRicaricaPortafoglio($importo, $metodo);
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['conferma_ricarica_portafoglio'] ?? '') === '1')) {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    } elseif (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        try {
            $datiRicarica = leggiPayloadRicaricaPortafoglio($_POST['payload'] ?? '');
            validaPagamentoSimulato($datiRicarica['metodo'], $_POST);
            $ricaricaPortafoglioCompletata = completaRicaricaPortafoglio($pdo, (float)$datiRicarica['importo']);
            $ricaricaPortafoglioCompletata['metodo'] = $datiRicarica['metodo'];
        } catch (Throwable $e) {
            $errorePagamento = $e->getMessage();
            try {
                $datiRicarica = leggiPayloadRicaricaPortafoglio($_POST['payload'] ?? '');
                $formRicaricaPortafoglio = creaFormRicaricaPortafoglio((float)$datiRicarica['importo'], (string)$datiRicarica['metodo']);
            } catch (Throwable $payloadError) {
                $errore = $e->getMessage();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ordine'])) {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }

    $ordineDaPagare = caricaOrdineUtenteDaPagare($pdo, (int)($_GET['ordine'] ?? 0));
    if (!$ordineDaPagare) {
        $errore = 'Ordine non trovato oppure non associato al tuo account.';
    } elseif (strcasecmp((string)($ordineDaPagare['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0) {
        $errore = 'Questo ordine è stato rimborsato: non è possibile effettuare nuove operazioni.';
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
        $metodo = (string)($_POST['metodo_pagamento'] ?? 'carta');
        $ordineDaPagare = caricaOrdineUtenteDaPagare($pdo, $idOrdine);

        if (!$ordineDaPagare) {
            $errore = 'Ordine non trovato oppure non associato al tuo account.';
        } elseif (strcasecmp((string)($ordineDaPagare['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0) {
            $errore = 'Questo ordine è stato rimborsato: non è possibile effettuare nuove operazioni.';
        } elseif (($ordineDaPagare['stato_pagamento'] ?? '') === 'Pagato') {
            $ordine = $ordineDaPagare;
        } else {
            try {
                validaPagamentoSimulato($metodo, $_POST);
                marcaOrdinePagato($pdo, $ordineDaPagare, $metodo);
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

        if ($datiOrdine['metodoPagamento'] === 'saldo') {
            $result = creaOrdineConBiglietti($pdo, $datiOrdine, 'Pagato', 'Valido');
            $ordine = $result['ordine'];
            $bigliettiCreati = $result['codici'];
            $emailOrdineDaInviare = ['ordine' => $ordine, 'codici' => $bigliettiCreati];
        } elseif ($datiOrdine['metodoPagamento'] === 'contanti') {
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
      <div class="text-5xl mb-4"></div>
      <h1 class="font-display text-3xl font-bold text-antracite mb-4">Pagamento non completato</h1>
      <div class="alert-error p-4 rounded text-sm mb-6 text-left">Errore: <?= clean($errore) ?></div>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline px-6 py-3 rounded inline-block">Torna alle esposizioni</a>
    </div>

  <?php elseif ($formRicaricaPortafoglio): ?>
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="text-5xl mb-4"><?= $formRicaricaPortafoglio['metodo'] === 'paypal' ? '🅿' : '' ?></div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ricarica portafoglio</p>
        <h1 class="font-display text-avorio text-3xl font-bold">
          <?= $formRicaricaPortafoglio['metodo'] === 'paypal' ? 'Accesso PayPal' : 'Dati carta di credito' ?>
        </h1>
      </div>

      <div class="p-8 md:p-10">
        <?php if ($errorePagamento): ?>
          <div class="alert-error p-4 rounded text-sm mb-6" role="alert">
             <?= clean($errorePagamento) ?>
          </div>
        <?php endif; ?>

        <div class="bg-avorio rounded-xl p-5 mb-8 text-sm text-gray-600">
          <p><strong>Operazione:</strong> ricarica portafoglio virtuale</p>
          <p><strong>Metodo:</strong> <?= clean($formRicaricaPortafoglio['metodo'] === 'paypal' ? 'PayPal' : 'Carta di credito') ?></p>
          <p><strong>Importo ricarica:</strong> € <?= number_format((float)$formRicaricaPortafoglio['importo'], 2, ',', '.') ?></p>
        </div>

        <form method="POST" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="conferma_ricarica_portafoglio" value="1">
          <input type="hidden" name="payload" value="<?= clean($formRicaricaPortafoglio['payload']) ?>">

          <?php if ($formRicaricaPortafoglio['metodo'] === 'carta'): ?>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Titolare carta</label>
              <input type="text" name="titolare" required data-required="1" placeholder="Mario Rossi" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Numero carta</label>
              <input type="text" name="numero_carta" required data-required="1" placeholder="4111 1111 1111 1111" maxlength="23" inputmode="numeric" autocomplete="cc-number" class="js-card-number w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
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
              Completa ricarica
            </button>
            <a href="<?= SITE_URL ?>/account.php" class="btn-outline flex-1 px-7 py-3 rounded font-body text-sm uppercase tracking-wide text-center">
              Annulla
            </a>
          </div>
        </form>
      </div>
    </section>

  <?php elseif ($ricaricaPortafoglioCompletata): ?>
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-8 py-8 text-center">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ricarica completata</p>
        <h1 class="font-display text-avorio text-3xl font-bold">Portafoglio aggiornato</h1>
      </div>
      <div class="p-8 md:p-10 text-center">
        <p class="text-gray-600 mb-6">La ricarica simulata è stata completata correttamente.</p>
        <div class="grid sm:grid-cols-3 gap-4 mb-8 text-left">
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Importo ricaricato</div>
            <div class="font-display text-oro font-bold text-xl">€ <?= number_format((float)$ricaricaPortafoglioCompletata['importo'], 2, ',', '.') ?></div>
          </div>
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Metodo</div>
            <div class="font-bold text-antracite text-xl"><?= clean($ricaricaPortafoglioCompletata['metodo'] === 'paypal' ? 'PayPal' : 'Carta') ?></div>
          </div>
          <div class="bg-avorio rounded-xl p-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide">Nuovo saldo</div>
            <div class="font-display text-oro font-bold text-xl">€ <?= number_format((float)$ricaricaPortafoglioCompletata['saldo'], 2, ',', '.') ?></div>
          </div>
        </div>
        <a href="<?= SITE_URL ?>/account.php" class="btn-oro px-7 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">Torna al profilo</a>
      </div>
    </section>

  <?php elseif ($formPagamento): ?>
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="text-5xl mb-4"><?= $formPagamento['metodo'] === 'paypal' ? '🅿' : '' ?></div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Pagamento simulato</p>
        <h1 class="font-display text-avorio text-3xl font-bold">
          <?= $formPagamento['metodo'] === 'paypal' ? 'Accesso PayPal' : 'Dati carta di credito' ?>
        </h1>
      </div>

      <div class="p-8 md:p-10">
        <?php if ($errorePagamento): ?>
          <div class="alert-error p-4 rounded text-sm mb-6" role="alert">
             <?= clean($errorePagamento) ?>
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
              <input type="text" name="titolare" required data-required="1" placeholder="Mario Rossi" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Numero carta</label>
              <input type="text" name="numero_carta" required data-required="1" placeholder="4111 1111 1111 1111" maxlength="23" inputmode="numeric" autocomplete="cc-number" class="js-card-number w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
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
        <div class="text-5xl mb-4"></div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Pagamento ordine esistente</p>
        <h1 class="font-display text-avorio text-3xl font-bold">Salda ordine non pagato</h1>
      </div>

      <div class="p-8 md:p-10">
        <?php if ($errorePagamento): ?>
          <div class="alert-error p-4 rounded text-sm mb-6" role="alert">
             <?= clean($errorePagamento) ?>
          </div>
        <?php endif; ?>

        <div class="bg-avorio rounded-xl p-5 mb-8 text-sm text-gray-600">
          <p><strong>Ordine:</strong> <?= clean($pagamentoOrdineEsistente['codice_recupero']) ?></p>
          <p><strong>Acquirente:</strong> <?= clean($pagamentoOrdineEsistente['nome_cliente'] ?? '') ?> · <?= clean($pagamentoOrdineEsistente['email_cliente'] ?? '') ?></p>
          <p><strong>Totale:</strong> € <?= number_format((float)($pagamentoOrdineEsistente['importo_totale'] ?? 0), 2, ',', '.') ?></p>
        </div>

        <?php
          $metodoSceltoOrdine = (string)($pagamentoOrdineEsistente['metodo_pagamento'] ?? 'carta');
          if (!in_array($metodoSceltoOrdine, ['carta', 'paypal', 'saldo'], true)) {
              $metodoSceltoOrdine = 'carta';
          }
        ?>

        <form method="POST" class="space-y-6">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="paga_ordine" value="1">
          <input type="hidden" name="id_ordine" value="<?= (int)$pagamentoOrdineEsistente['id_ordine'] ?>">

          <div class="payment-method-picker">
            <span class="block text-sm font-body font-bold text-antracite mb-3">Metodo di pagamento</span>
            <div class="payment-method-grid">
              <label class="payment-method-option">
                <input type="radio" name="metodo_pagamento" value="carta" required <?= $metodoSceltoOrdine === 'carta' ? 'checked' : '' ?>>
                <span>
                  <strong>Carta di credito</strong>
                  <small>Pagamento simulato con dati carta.</small>
                </span>
              </label>
              <label class="payment-method-option">
                <input type="radio" name="metodo_pagamento" value="paypal" required <?= $metodoSceltoOrdine === 'paypal' ? 'checked' : '' ?>>
                <span>
                  <strong>PayPal</strong>
                  <small>Simulazione accesso PayPal.</small>
                </span>
              </label>
              <label class="payment-method-option">
                <input type="radio" name="metodo_pagamento" value="saldo" required <?= $metodoSceltoOrdine === 'saldo' ? 'checked' : '' ?>>
                <span>
                  <strong>Saldo utente</strong>
                  <small>Saldo disponibile: &euro; <?= number_format(saldoUtenteCorrente($pdo, (int)$_SESSION['utente_id']), 2, ',', '.') ?></small>
                </span>
              </label>
            </div>
          </div>

          <div data-payment-summary="carta">
            <label class="block text-sm font-body font-bold text-antracite mb-3">Metodo di pagamento</label>
            <div class="border border-avorio-dark rounded-xl p-4 bg-white">
              <span class="block font-bold text-antracite text-sm">Carta di credito</span>
              <span class="block text-xs text-gray-500 mt-1">Pagamento simulato. Inserisci i dati della carta per saldare l’ordine.</span>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-4" data-payment-fields="carta">
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
              <input type="text" name="scadenza" required data-required="1" placeholder="MM/AA" maxlength="5" inputmode="numeric" autocomplete="cc-exp" class="js-card-expiry w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">CVV</label>
              <input type="text" name="cvv" required data-required="1" placeholder="123" maxlength="4" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
            </div>
          </div>

          <div class="payment-method-fields" data-payment-fields="paypal" hidden>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Email PayPal</label>
            <input type="email" name="paypal_email" data-required="1" placeholder="nome@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>

          <div class="payment-method-fields payment-wallet-note" data-payment-fields="saldo" hidden>
            <strong>Pagamento immediato con saldo virtuale</strong>
            <span>L'importo verra scalato dal portafoglio e i biglietti saranno subito validi.</span>
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
        <div class="text-5xl mb-4"><?= $pagato ? '' : '' ?></div>
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


<script nonce="<?= cspNonce() ?>">
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

  document.querySelectorAll('form').forEach(function (form) {
    var radios = form.querySelectorAll('input[name="metodo_pagamento"]');
    var panels = form.querySelectorAll('[data-payment-fields]');
    var summaries = form.querySelectorAll('[data-payment-summary]');
    if (!radios.length || !panels.length) return;

    function aggiornaMetodoPagamento() {
      var checked = form.querySelector('input[name="metodo_pagamento"]:checked');
      var metodo = checked ? checked.value : 'carta';

      panels.forEach(function (panel) {
        var active = panel.getAttribute('data-payment-fields') === metodo;
        panel.hidden = !active;
        panel.querySelectorAll('input, select, textarea').forEach(function (field) {
          if (!field.dataset.wasRequired) {
            field.dataset.wasRequired = (field.required || field.dataset.required === '1') ? '1' : '0';
          }
          field.disabled = !active;
          field.required = active && field.dataset.wasRequired === '1';
        });
      });

      summaries.forEach(function (summary) {
        summary.hidden = summary.getAttribute('data-payment-summary') !== metodo;
      });
    }

    radios.forEach(function (radio) {
      radio.addEventListener('change', aggiornaMetodoPagamento);
    });
    aggiornaMetodoPagamento();
  });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>

<?php
if (is_array($emailOrdineDaInviare)) {
    inviaEmailOrdineDopoRisposta($emailOrdineDaInviare['ordine'], $emailOrdineDaInviare['codici']);
}
?>
