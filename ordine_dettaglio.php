<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$pageTitle = 'Dettaglio ordine';
$pdo = getDB();
$idOrdine = (int)($_GET['id'] ?? 0);

if ($idOrdine <= 0) {
    header('Location: ' . SITE_URL . '/ordini.php');
    exit;
}

$sql = isAdmin()
    ? 'SELECT * FROM Ordini WHERE id_ordine = ? LIMIT 1'
    : 'SELECT * FROM Ordini WHERE id_ordine = ? AND id_utente = ? LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute(isAdmin() ? [$idOrdine] : [$idOrdine, $_SESSION['utente_id']]);
$ordine = $stmt->fetch();

if (!$ordine) {
    header('Location: ' . SITE_URL . '/ordini.php');
    exit;
}

$stmtB = $pdo->prepare("\n    SELECT b.*, cr.nome AS categoria, f.data AS data_fascia, f.ora_ingresso, e.titolo AS esposizione\n    FROM Biglietti b\n    LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n    LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n    LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n    WHERE b.id_ordine = ?\n    ORDER BY b.id_biglietto ASC\n");
$stmtB->execute([$idOrdine]);
$biglietti = $stmtB->fetchAll();

$ordineRimborsato = strcasecmp((string)($ordine['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
$rimborsoRichiesto = strcasecmp((string)($ordine['stato_rimborso'] ?? 'Nessuno'), 'Richiesto') === 0;
$pagato = ($ordine['stato_pagamento'] ?? '') === 'Pagato';
$haBigliettiUsati = false;
$haBigliettiScaduti = false;
$oggi = date('Y-m-d');
foreach ($biglietti as $b) {
    if (($b['stato'] ?? '') === 'Utilizzato') $haBigliettiUsati = true;
    if (($b['stato'] ?? '') === 'Valido' && !$ordineRimborsato && (string)$b['data_validita'] < $oggi) $haBigliettiScaduti = true;
}

$timeline = [
    ['✓', 'Ordine creato', date('d/m/Y H:i', strtotime($ordine['data_acquisto'])), true],
    [$pagato ? '✓' : '!', $pagato ? 'Pagamento completato' : 'Pagamento da completare', clean($ordine['stato_pagamento'] ?? 'In attesa'), $pagato],
];
if ($rimborsoRichiesto) {
    $timeline[] = ['…', 'Rimborso in valutazione', 'Riceverai l’esito via email.', false];
} elseif ($ordineRimborsato) {
    $timeline[] = ['×', 'Rimborso accettato', 'I biglietti restano nello storico, ma non sono utilizzabili.', true];
} else {
    $timeline[] = ['✓', 'Biglietti generati', count($biglietti) . ' bigliett' . (count($biglietti) === 1 ? 'o' : 'i'), true];
    $timeline[] = [$haBigliettiUsati ? '✓' : ($haBigliettiScaduti ? '!' : '⏳'), $haBigliettiUsati ? 'Visita effettuata' : ($haBigliettiScaduti ? 'Biglietto scaduto' : 'Visita programmata'), $haBigliettiScaduti ? 'La data di validità è passata.' : 'Mostra il QR code all’ingresso.', $haBigliettiUsati];
}

include __DIR__ . '/header.php';
?>

<style>
  .order-detail-hero { background: linear-gradient(135deg, #102744, #193a5c 55%, #102744); color: #fffdf5; }
  .order-panel { border: 1px solid rgba(142,197,232,.24); background: #fff; }
  .order-timeline-step { position: relative; padding-left: 2.5rem; }
  .order-timeline-step::before { content: ""; position: absolute; left: .68rem; top: 1.7rem; bottom: -1rem; width: 2px; background: #dbeaf3; }
  .order-timeline-step:last-child::before { display: none; }
  .order-timeline-dot { position: absolute; left: 0; top: 0; width: 1.45rem; height: 1.45rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: #102744; color: #fffdf5; font-size: .75rem; font-weight: 900; }
  .order-ticket-row { border: 1px solid rgba(142,197,232,.24); background: linear-gradient(180deg,#fff,#fbfdff); }
  .order-ticket-code { overflow-wrap: anywhere; word-break: break-word; }
  .order-actions a { min-width: 168px; text-align: center; white-space: normal; line-height: 1.25; }
  @media (min-width: 1024px) {
    .order-detail-grid { grid-template-columns: minmax(360px, 0.9fr) minmax(560px, 1.6fr); align-items: start; }
  }
  @media (min-width: 1280px) {
    .order-detail-grid { grid-template-columns: minmax(380px, 0.85fr) minmax(680px, 1.7fr); }
  }
  @media (max-width: 640px) {
    .order-detail-hero h1 { font-size: 1.8rem; }
    .order-actions { width: 100%; }
    .order-actions a { width: 100%; }
  }
</style>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <a href="<?= SITE_URL ?>/account.php" class="hover:text-oro transition-colors">Account</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Dettaglio ordine</span>
  </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
  <section class="order-detail-hero rounded-2xl shadow p-6 md:p-8">
    <p class="text-xs uppercase tracking-widest text-acciaio font-bold mb-2">Dettaglio ordine</p>
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5">
      <div>
        <h1 class="font-display text-3xl md:text-4xl font-bold">Ordine <?= clean($ordine['codice_recupero']) ?></h1>
        <p class="text-avorio/80 mt-2">Creato il <?= date('d/m/Y H:i', strtotime($ordine['data_acquisto'])) ?> · Metodo: <?= clean($ordine['metodo_pagamento'] ?? '—') ?></p>
      </div>
      <div class="bg-white/95 text-antracite rounded-2xl p-4 min-w-[220px]">
        <p class="text-xs uppercase tracking-widest text-gray-500">Totale ordine</p>
        <p class="font-display text-3xl font-bold">€ <?= number_format((float)$ordine['importo_totale'], 2, ',', '.') ?></p>
        <p class="text-xs mt-1 <?= $pagato ? 'text-green-700' : 'text-yellow-700' ?> font-bold"><?= clean($ordine['stato_pagamento'] ?? '—') ?></p>
      </div>
    </div>
  </section>

  <section class="order-detail-grid grid grid-cols-1 gap-6">
    <article class="order-panel rounded-2xl shadow p-6">
      <h2 class="font-display text-2xl font-bold text-antracite mb-5">Timeline ordine</h2>
      <div class="space-y-5">
        <?php foreach ($timeline as $step): ?>
          <div class="order-timeline-step">
            <span class="order-timeline-dot"><?= clean($step[0]) ?></span>
            <h3 class="font-body font-bold text-antracite"><?= clean($step[1]) ?></h3>
            <p class="text-sm text-gray-500 mt-1"><?= $step[2] ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </article>

    <article class="order-panel rounded-2xl shadow p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <h2 class="font-display text-2xl font-bold text-antracite">Biglietti collegati</h2>
        <div class="order-actions flex flex-col sm:flex-row gap-2">
          <a href="<?= SITE_URL ?>/biglietti.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-oro px-4 py-2 rounded text-sm inline-block">Apri biglietti</a>
          <a href="<?= SITE_URL ?>/ricevuta_pdf.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-outline px-4 py-2 rounded text-sm inline-block">Scarica ricevuta</a>
        </div>
      </div>

      <div class="space-y-3">
        <?php foreach ($biglietti as $b): ?>
          <?php
            $stato = $ordineRimborsato ? 'Rimborsato' : (string)$b['stato'];
            if ($stato === 'Valido' && (string)$b['data_validita'] < $oggi) $stato = 'Scaduto';
            $badge = $stato === 'Valido' ? 'bg-green-100 text-green-800' : ($stato === 'Scaduto' ? 'bg-orange-100 text-orange-800' : ($stato === 'Rimborsato' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700'));
          ?>
          <div class="order-ticket-row rounded-2xl p-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
            <div>
              <p class="text-xs uppercase tracking-widest text-gray-400">Codice biglietto</p>
              <h3 class="order-ticket-code font-display text-xl font-bold text-antracite"><?= clean($b['codice_univoco']) ?></h3>
              <p class="text-sm text-gray-500 mt-1"><?= clean($b['esposizione'] ?? 'Ingresso museo') ?> · <?= date('d/m/Y', strtotime($b['data_validita'])) ?><?= $b['ora_ingresso'] ? ' alle ' . clean(substr($b['ora_ingresso'], 0, 5)) : '' ?></p>
            </div>
            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold <?= $badge ?>"><?= clean($stato) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </article>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
