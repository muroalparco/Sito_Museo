<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireAdmin();
$pdo = getDB();

$tipo = strtolower(trim($_GET['tipo'] ?? 'ordini'));

$exports = [
    'ordini' => [
        'filename' => 'ordini_museo_storico_severi.csv',
        'headers' => ['ID ordine','Codice recupero','Cliente','Email','Data acquisto','Importo totale','Stato pagamento','Metodo pagamento','Prenotazione classe','Stato rimborso','Biglietti'],
        'sql' => "SELECT o.id_ordine, o.codice_recupero,
                       COALESCE(CONCAT(u.nome, ' ', u.cognome), o.nome_cliente, 'Ospite') AS cliente,
                       COALESCE(u.email, o.email_cliente, '') AS email,
                       o.data_acquisto, o.importo_totale, o.stato_pagamento, o.metodo_pagamento,
                       CASE WHEN o.prenotazione_docente = 1 THEN 'Sì' ELSE 'No' END AS prenotazione_classe,
                       COALESCE(o.stato_rimborso, 'Nessuno') AS stato_rimborso,
                       COUNT(b.id_biglietto) AS biglietti
                FROM Ordini o
                LEFT JOIN Utenti u ON u.id_utente = o.id_utente
                LEFT JOIN Biglietti b ON b.id_ordine = o.id_ordine
                GROUP BY o.id_ordine, o.codice_recupero, cliente, email, o.data_acquisto, o.importo_totale, o.stato_pagamento, o.metodo_pagamento, o.prenotazione_docente, o.stato_rimborso
                ORDER BY o.data_acquisto DESC"
    ],
    'biglietti' => [
        'filename' => 'biglietti_museo_storico_severi.csv',
        'headers' => ['ID biglietto','Codice biglietto','ID ordine','Cliente','Tipo','Esposizione','Data validità','Ora ingresso','Categoria','Prezzo lordo','Sconto','Stato','Data utilizzo'],
        'sql' => "SELECT b.id_biglietto, b.codice_univoco, b.id_ordine,
                       COALESCE(CONCAT(u.nome, ' ', u.cognome), o.nome_cliente, 'Ospite') AS cliente,
                       b.tipo,
                       COALESCE(e.titolo, 'Ingresso museo') AS esposizione,
                       b.data_validita,
                       COALESCE(TIME_FORMAT(f.ora_ingresso, '%H:%i'), '') AS ora_ingresso,
                       COALESCE(c.nome, '') AS categoria,
                       b.prezzo_lordo, b.sconto_applicato, b.stato,
                       COALESCE(b.data_utilizzo, '') AS data_utilizzo
                FROM Biglietti b
                INNER JOIN Ordini o ON o.id_ordine = b.id_ordine
                LEFT JOIN Utenti u ON u.id_utente = o.id_utente
                LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia
                LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione
                LEFT JOIN Categorie_Riduzione c ON c.id_categoria = b.id_categoria
                ORDER BY b.data_validita DESC, b.id_biglietto DESC"
    ],
    'rimborsi' => [
        'filename' => 'rimborsi_museo_storico_severi.csv',
        'headers' => ['ID ordine','Codice recupero','Cliente','Email','Importo','Richiesta','Stato rimborso','Data richiesta','Data esito','Motivo'],
        'sql' => "SELECT o.id_ordine, o.codice_recupero,
                       COALESCE(CONCAT(u.nome, ' ', u.cognome), o.nome_cliente, 'Ospite') AS cliente,
                       COALESCE(u.email, o.email_cliente, '') AS email,
                       o.importo_totale,
                       CASE WHEN o.richiesta_rimborso = 1 THEN 'Sì' ELSE 'No' END AS richiesta,
                       COALESCE(o.stato_rimborso, 'Nessuno') AS stato_rimborso,
                       COALESCE(o.data_richiesta_rimborso, '') AS data_richiesta_rimborso,
                       COALESCE(o.data_esito_rimborso, '') AS data_esito_rimborso,
                       COALESCE(o.motivo_rimborso, '') AS motivo_rimborso
                FROM Ordini o
                LEFT JOIN Utenti u ON u.id_utente = o.id_utente
                WHERE o.richiesta_rimborso = 1 OR COALESCE(o.stato_rimborso, 'Nessuno') <> 'Nessuno'
                ORDER BY COALESCE(o.data_esito_rimborso, o.data_richiesta_rimborso, o.data_acquisto) DESC"
    ],
    'utenti' => [
        'filename' => 'utenti_museo_storico_severi.csv',
        'headers' => ['ID utente','Nome','Cognome','Email','Ruolo','Email verificata','Data registrazione','Saldo portafoglio','Ordini'],
        'sql' => "SELECT u.id_utente, u.nome, u.cognome, u.email, u.ruolo,
                       CASE WHEN u.email_verificata = 1 THEN 'Sì' ELSE 'No' END AS email_verificata,
                       u.data_registrazione, u.saldo_utente,
                       COUNT(o.id_ordine) AS ordini
                FROM Utenti u
                LEFT JOIN Ordini o ON o.id_utente = u.id_utente
                GROUP BY u.id_utente, u.nome, u.cognome, u.email, u.ruolo, u.email_verificata, u.data_registrazione, u.saldo_utente
                ORDER BY u.data_registrazione DESC"
    ],
    'esposizioni' => [
        'filename' => 'esposizioni_museo_storico_severi.csv',
        'headers' => ['ID esposizione','Titolo','Descrizione','Data inizio','Data fine','Stato','Fasce orarie','Biglietti'],
        'sql' => "SELECT e.id_esposizione, e.titolo, e.descrizione, e.data_inizio, e.data_fine, e.stato,
                       COUNT(DISTINCT f.id_fascia) AS fasce_orarie,
                       COUNT(DISTINCT b.id_biglietto) AS biglietti
                FROM Esposizioni e
                LEFT JOIN Fasce_Orarie f ON f.id_esposizione = e.id_esposizione
                LEFT JOIN Biglietti b ON b.id_fascia = f.id_fascia
                GROUP BY e.id_esposizione, e.titolo, e.descrizione, e.data_inizio, e.data_fine, e.stato
                ORDER BY e.data_inizio DESC"
    ],
    'tariffe' => [
        'filename' => 'tariffe_museo_storico_severi.csv',
        'headers' => ['ID tariffa','Tipo biglietto','Categoria','Percentuale sconto','Prezzo'],
        'sql' => "SELECT t.id_tariffa, t.tipo_biglietto, c.nome AS categoria, c.percentuale_sconto, t.prezzo
                FROM Tariffe t
                INNER JOIN Categorie_Riduzione c ON c.id_categoria = t.id_categoria
                ORDER BY t.tipo_biglietto, c.percentuale_sconto"
    ],
    'servizi' => [
        'filename' => 'servizi_museo_storico_severi.csv',
        'headers' => ['ID servizio','Nome','Descrizione','Prezzo','Acquisti collegati'],
        'sql' => "SELECT s.id_servizio, s.nome, s.descrizione, s.prezzo, COUNT(bs.id_biglietto) AS acquisti_collegati
                FROM Servizi_Opzionali s
                LEFT JOIN Biglietti_Servizi bs ON bs.id_servizio = s.id_servizio
                GROUP BY s.id_servizio, s.nome, s.descrizione, s.prezzo
                ORDER BY s.nome"
    ],
];

if (!isset($exports[$tipo])) {
    http_response_code(400);
    echo 'Tipo di esportazione non valido.';
    exit;
}

$export = $exports[$tipo];
$stmt = $pdo->query($export['sql']);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, $export['headers'], ';');

while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $cleanRow = array_map(function ($value) {
        if ($value === null) return '';
        return is_string($value) ? preg_replace('/\s+/', ' ', trim($value)) : $value;
    }, $row);
    fputcsv($out, $cleanRow, ';');
}

fclose($out);
exit;
