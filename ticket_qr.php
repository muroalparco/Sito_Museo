<?php
require_once __DIR__ . '/qr_helper.php';

$codice = strtoupper(trim($_GET['codice'] ?? $_GET['ticket'] ?? ''));
if (!preg_match('/^TKT-[A-Z0-9]{8,20}$/', $codice)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Codice biglietto non valido.';
    exit;
}

$moduleSize = isset($_GET['small']) ? 6 : 9;

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo qrTicketSvg($codice, $moduleSize);
exit;
