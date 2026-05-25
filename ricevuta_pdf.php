<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ordine_pdf.php';

$pdo = getDB();
$codice = strtoupper(trim($_GET['codice'] ?? ''));

function erroreRicevutaPdf(string $messaggio, int $status = 400): void {
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $messaggio;
    exit;
}

if ($codice === '') {
    erroreRicevutaPdf('Codice ordine mancante.', 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM Ordini WHERE codice_recupero = ? LIMIT 1');
    $stmt->execute([$codice]);
    $ordine = $stmt->fetch();

    if (!$ordine) {
        erroreRicevutaPdf('Ordine non trovato.', 404);
    }
    if (!ordineAutorizzato($pdo, $ordine)) {
        erroreRicevutaPdf('Non sei autorizzato a scaricare la ricevuta di questo ordine.', 403);
    }

    $stmtBiglietti = $pdo->prepare("\n        SELECT\n            b.codice_univoco,\n            b.tipo,\n            b.data_validita,\n            b.prezzo_lordo,\n            b.sconto_applicato,\n            b.stato,\n            cr.nome AS categoria,\n            f.ora_ingresso,\n            COALESCE(e.titolo, 'Ingresso museo') AS esposizione\n        FROM Biglietti b\n        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n        WHERE b.id_ordine = ?\n        ORDER BY b.data_validita ASC, f.ora_ingresso ASC, b.id_biglietto ASC\n    ");
    $stmtBiglietti->execute([(int)$ordine['id_ordine']]);
    $biglietti = $stmtBiglietti->fetchAll();

    $stmtServizi = $pdo->prepare("\n        SELECT so.nome, COUNT(*) AS quantita, COALESCE(SUM(bs.prezzo_snapshot), 0) AS totale\n        FROM Biglietti_Servizi bs\n        INNER JOIN Biglietti b ON b.id_biglietto = bs.id_biglietto\n        INNER JOIN Servizi_Opzionali so ON so.id_servizio = bs.id_servizio\n        WHERE b.id_ordine = ?\n        GROUP BY so.nome\n        ORDER BY so.nome ASC\n    ");
    $stmtServizi->execute([(int)$ordine['id_ordine']]);
    $servizi = $stmtServizi->fetchAll();

    $quantita = count($biglietti);
    $primaData = null;
    $primoOrario = null;
    $percorsi = [];
    $categorie = [];
    foreach ($biglietti as $b) {
        $primaData = $primaData ?: ($b['data_validita'] ?? null);
        $primoOrario = $primoOrario ?: ($b['ora_ingresso'] ?? null);
        $percorsi[(string)$b['esposizione']] = true;
        if (!empty($b['categoria'])) {
            $categorie[(string)$b['categoria']] = true;
        }
    }

    $nomeCliente = (string)($ordine['nome_cliente'] ?? 'Visitatore');
    $emailCliente = (string)($ordine['email_cliente'] ?? '-');
    $metodo = ucfirst((string)($ordine['metodo_pagamento'] ?? 'Non indicato'));
    $statoPagamento = (string)($ordine['stato_pagamento'] ?? 'Non indicato');
    $totale = number_format((float)$ordine['importo_totale'], 2, ',', '.');
    $statoRimborso = (string)($ordine['stato_rimborso'] ?? 'Nessuno');

    $pdf = new PdfOrdineBuilder();
    $pdf->title('Ricevuta digitale');
    $pdf->keyValue('Museo', 'Museo Storico Severi');
    $pdf->keyValue('Documento', 'Ricevuta ordine ' . (string)$ordine['codice_recupero']);
    $pdf->keyValue('Emissione', date('d/m/Y H:i'));

    $pdf->title('Dati ordine');
    $pdf->keyValue('Codice ordine', (string)$ordine['codice_recupero']);
    $pdf->keyValue('Data ordine', date('d/m/Y H:i', strtotime((string)$ordine['data_acquisto'])));
    $pdf->keyValue('Acquirente', $nomeCliente);
    $pdf->keyValue('Email', $emailCliente);
    $pdf->keyValue('Metodo pagamento', $metodo);
    $pdf->keyValue('Stato pagamento', $statoPagamento);
    $pdf->keyValue('Importo totale', 'EUR ' . $totale);

    $pdf->title('Dettaglio visita');
    $pdf->keyValue('Percorso', implode(', ', array_keys($percorsi)) ?: 'Museo Storico Severi', 72);
    if ($primaData) {
        $pdf->keyValue('Data visita', date('d/m/Y', strtotime((string)$primaData)) . ($primoOrario ? ' - ore ' . substr((string)$primoOrario, 0, 5) : ''));
    }
    $pdf->keyValue('Numero biglietti', (string)$quantita);
    $pdf->keyValue('Categorie', implode(', ', array_keys($categorie)) ?: 'Non indicato', 72);

    if (!empty($servizi)) {
        $pdf->title('Servizi opzionali');
        foreach ($servizi as $servizio) {
            $pdf->keyValue(
                (string)$servizio['nome'],
                (int)$servizio['quantita'] . ' x - totale EUR ' . number_format((float)$servizio['totale'], 2, ',', '.')
            );
        }
    }

    if (strcasecmp($statoRimborso, 'Accettato') === 0) {
        $pdf->warning('Ordine rimborsato', 'Il rimborso risulta accettato. Questa ricevuta resta disponibile come documento storico e i biglietti non sono utilizzabili.');
    } elseif ($statoPagamento !== 'Pagato') {
        $pdf->warning('Pagamento non completato', 'La ricevuta documenta la registrazione dell ordine, ma il pagamento non risulta ancora completato.');
    } else {
        $pdf->warning('Promemoria ingresso', 'Conserva il QR code dei biglietti e mostra il ticket all ingresso del museo. Porta eventuali documenti per le riduzioni.');
    }

    $pdf->title('Biglietti collegati');
    foreach ($biglietti as $idx => $b) {
        $statoTicket = strcasecmp($statoRimborso, 'Accettato') === 0 ? 'Rimborsato' : (string)$b['stato'];
        $pdf->ticketRow($idx + 1, (string)$b['codice_univoco'], $statoTicket);
    }

    $pdfBytes = $pdf->output();
    $filename = 'ricevuta_' . preg_replace('/[^A-Z0-9_-]/i', '', (string)$ordine['codice_recupero']) . '.pdf';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfBytes));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $pdfBytes;
    exit;
} catch (Throwable $e) {
    erroreRicevutaPdf('Errore durante la generazione della ricevuta PDF.', 500);
}
