<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$pdo = getDB();
$idOrdine = (int)($_GET['id'] ?? 0);
if ($idOrdine <= 0) {
    header('Location: ' . SITE_URL . '/ordini.php');
    exit;
}

$sql = isAdmin()
    ? 'SELECT codice_recupero FROM Ordini WHERE id_ordine = ? LIMIT 1'
    : 'SELECT codice_recupero FROM Ordini WHERE id_ordine = ? AND id_utente = ? LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute(isAdmin() ? [$idOrdine] : [$idOrdine, $_SESSION['utente_id']]);
$codice = $stmt->fetchColumn();

if (!$codice) {
    header('Location: ' . SITE_URL . '/ordini.php');
    exit;
}

header('Location: ' . SITE_URL . '/biglietti.php?codice=' . urlencode($codice));
exit;
