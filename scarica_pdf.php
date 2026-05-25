<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ordine_pdf.php';

$pdo = getDB();
$codice = strtoupper(trim($_GET['codice'] ?? ''));

function rispondiErrorePdf(string $messaggio, int $codiceHttp = 400): void {
    http_response_code($codiceHttp);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $messaggio;
    exit;
}

if ($codice === '') {
    rispondiErrorePdf('Codice ordine mancante.', 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM Ordini WHERE codice_recupero = ? LIMIT 1');
    $stmt->execute([$codice]);
    $ordine = $stmt->fetch();

    if (!$ordine) {
        rispondiErrorePdf('Ordine non trovato.', 404);
    }
    if (!ordineAutorizzato($pdo, $ordine)) {
        rispondiErrorePdf('Non sei autorizzato a scaricare questo ordine.', 403);
    }

    $stmtInfo = $pdo->prepare(" 
        SELECT
            MIN(b.tipo) AS tipo,
            MIN(b.data_validita) AS data_validita,
            COALESCE(MAX(e.titolo), 'Museo Storico Severi') AS titolo_percorso,
            GROUP_CONCAT(DISTINCT so.nome SEPARATOR ', ') AS servizi_descrizione,
            COUNT(DISTINCT b.id_biglietto) AS quantita
        FROM Biglietti b
        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia
        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione
        LEFT JOIN Biglietti_Servizi bs ON bs.id_biglietto = b.id_biglietto
        LEFT JOIN Servizi_Opzionali so ON so.id_servizio = bs.id_servizio
        WHERE b.id_ordine = ?
    ");
    $stmtInfo->execute([(int)$ordine['id_ordine']]);
    $info = $stmtInfo->fetch() ?: [];

    $stmtCodici = $pdo->prepare('SELECT codice_univoco FROM Biglietti WHERE id_ordine = ? ORDER BY id_biglietto ASC');
    $stmtCodici->execute([(int)$ordine['id_ordine']]);
    $codici = $stmtCodici->fetchAll(PDO::FETCH_COLUMN);

    $ordinePdf = array_merge($ordine, [
        'tipo' => $info['tipo'] ?? 'base',
        'data_validita' => $info['data_validita'] ?? '',
        'titolo_percorso' => $info['titolo_percorso'] ?? 'Museo Storico Severi',
        'servizi_descrizione' => $info['servizi_descrizione'] ?? '',
        'quantita' => (int)($info['quantita'] ?? count($codici)),
    ]);

    $pdf = creaPdfOrdine($ordinePdf, $codici);
    $nomeFile = 'biglietti_' . preg_replace('/[^A-Z0-9_-]/i', '', $ordine['codice_recupero']) . '.pdf';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $nomeFile . '"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    echo $pdf;
    exit;
} catch (Throwable $e) {
    rispondiErrorePdf('Errore durante la generazione del PDF.', 500);
}
