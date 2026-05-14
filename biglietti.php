<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Biglietti';
$pdo = getDB();
$codice = strtoupper(trim($_GET['codice'] ?? ''));
$ordine = null;
$biglietti = [];
$errore = '';

if ($codice === '') {
    $errore = 'Nessun codice ordine indicato.';
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Ordini WHERE codice_recupero = ? LIMIT 1");
        $stmt->execute([$codice]);
        $ordine = $stmt->fetch();

        if (!$ordine) {
            $errore = 'Codice ordine non trovato.';
        } else {
            $stmtB = $pdo->prepare("\n                SELECT\n                    b.*,\n                    cr.nome AS categoria,\n                    f.data AS data_fascia,\n                    f.ora_ingresso,\n                    e.titolo AS esposizione,\n                    GROUP_CONCAT(CONCAT(so.nome, ' (€ ', FORMAT(bs.prezzo_snapshot, 2), ')') SEPARATOR ', ') AS servizi\n                FROM Biglietti b\n                LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n                LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n                LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n                LEFT JOIN Biglietti_Servizi bs ON bs.id_biglietto = b.id_biglietto\n                LEFT JOIN Servizi_Opzionali so ON so.id_servizio = bs.id_servizio\n                WHERE b.id_ordine = ?\n                GROUP BY b.id_biglietto\n                ORDER BY b.id_biglietto ASC\n            ");
            $stmtB->execute([(int)$ordine['id_ordine']]);
            $biglietti = $stmtB->fetchAll();
        }
    } catch (Exception $e) {
        $errore = 'Errore durante il recupero dell\'ordine.';
    }
}


$isOrdineClasse = $ordine && (
    !empty($ordine['prenotazione_docente']) ||
    !empty($ordine['nome_scuola']) ||
    !empty($ordine['numero_docenti'])
);

$pdfFilename = $ordine
    ? 'biglietti_' . preg_replace('/[^A-Z0-9_-]/i', '', $ordine['codice_recupero']) . '.pdf'
    : 'biglietti.pdf';

include __DIR__ . '/header.php';
?>

<style>
  /* Usato sia per la stampa sia per il PDF scaricato direttamente. */
  @media print {
    .no-pdf, .pdf-only-message { display: none !important; }
  }


  .ticket-card {
    break-inside: avoid;
    page-break-inside: avoid;
  }

  .ticket-page {
    break-inside: avoid;
    page-break-inside: avoid;
  }

  .pdf-page-break {
    display: none;
  }

  @media print {
    .pdf-page-break {
      display: block;
      height: 0;
      page-break-after: always;
      break-after: page;
    }
  }

</style>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3 print:hidden no-pdf">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Biglietti</span>
  </div>
</div>

<main id="biglietti-pdf-area" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <?php if ($errore): ?>
    <div class="bg-white rounded-2xl shadow border border-avorio-dark p-8 text-center">
      <div class="text-5xl mb-4">🔎</div>
      <h1 class="font-display text-3xl font-bold text-antracite mb-4">Ordine non trovato</h1>
      <div class="alert-error p-4 rounded text-sm mb-6 text-left">⚠️ <?= clean($errore) ?></div>
      <a href="<?= SITE_URL ?>/recupera_ordine.php" class="btn-outline px-6 py-3 rounded inline-block">Riprova</a>
    </div>
  <?php else: ?>
    <section class="mb-8 bg-white rounded-2xl shadow border border-avorio-dark p-6 md:p-8">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ordine recuperato</p>
          <h1 class="font-display text-3xl font-bold text-antracite mb-2">Biglietti ordine <?= clean($ordine['codice_recupero']) ?></h1>
          <p class="text-gray-600 text-sm">Acquirente: <strong><?= clean($ordine['nome_cliente'] ?? '—') ?></strong> · <?= clean($ordine['email_cliente'] ?? '—') ?></p>
          <p class="text-gray-500 text-sm mt-1">Data acquisto: <?= date('d/m/Y H:i', strtotime($ordine['data_acquisto'])) ?></p>
          <?php if ($isOrdineClasse): ?>
            <div class="mt-4 bg-avorio rounded-xl p-4 text-sm text-gray-600 space-y-1">
              <p><strong>Scuola:</strong> <?= clean($ordine['nome_scuola'] ?? '—') ?></p>
              <?php if (!empty($ordine['codice_meccanografico'])): ?>
                <p><strong>Codice meccanografico:</strong> <?= clean($ordine['codice_meccanografico']) ?></p>
              <?php endif; ?>
              <p>
                <?php if (!empty($ordine['classe_scuola'])): ?>
                  <strong>Classe:</strong> <?= clean($ordine['classe_scuola']) ?>
                <?php endif; ?>
                <?php if (!empty($ordine['citta_scuola'])): ?>
                  <?= !empty($ordine['classe_scuola']) ? ' · ' : '' ?><strong>Città:</strong> <?= clean($ordine['citta_scuola']) ?>
                <?php endif; ?>
              </p>
              <p>
                <strong>Studenti:</strong> <?= (int)($ordine['quantita_studenti'] ?? 0) ?>
                · <strong>Docenti accompagnatori:</strong> <?= (int)($ordine['numero_docenti'] ?? 0) ?>
              </p>
              <?php if (!empty($ordine['note_scuola'])): ?>
                <p><strong>Note:</strong> <?= clean($ordine['note_scuola']) ?></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="md:text-right">
          <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Totale</p>
          <p class="font-display text-3xl font-bold text-oro">€ <?= number_format((float)$ordine['importo_totale'], 2, ',', '.') ?></p>
          <div class="mt-3 flex flex-wrap gap-2 md:justify-end print:hidden no-pdf">
            <button type="button" onclick="window.print()" class="btn-outline px-5 py-2 rounded text-sm">Stampa</button>
            <a href="<?= SITE_URL ?>/scarica_pdf.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-oro px-5 py-2 rounded text-sm inline-block">Scarica PDF</a>
          </div>
        </div>
      </div>
    </section>

    <?php $pagineBiglietti = array_chunk($biglietti, 4); ?>
    <section class="ticket-pages space-y-6">
      <?php foreach ($pagineBiglietti as $indicePagina => $bigliettiPagina): ?>
        <div class="ticket-page grid md:grid-cols-2 gap-6">
          <?php foreach ($bigliettiPagina as $b): ?>
            <?php
              $totaleTicket = (float)$b['prezzo_lordo'] - (float)$b['sconto_applicato'];
              $categoriaBiglietto = $b['categoria'] ?? '';
              if ($isOrdineClasse && $categoriaBiglietto === '' && (float)$b['prezzo_lordo'] == 0.0 && (float)$b['sconto_applicato'] == 0.0) {
                  $categoriaBiglietto = 'Docente accompagnatore';
              }
            ?>
            <article class="ticket-card bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden break-inside-avoid">
              <div class="h-2 bg-oro"></div>
              <div class="p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                  <div>
                    <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Codice biglietto</p>
                    <h2 class="font-display text-2xl font-bold text-antracite"><?= clean($b['codice_univoco']) ?></h2>
                  </div>
                  <?php
                    $ticketBadge = $b['stato'] === 'Valido' ? 'bg-green-100 text-green-800' : ($b['stato'] === 'Non pagato' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600');
                  ?>
                  <span class="px-3 py-1 rounded-full text-xs font-bold <?= $ticketBadge ?>">
                    <?= clean($b['stato']) ?>
                  </span>
                </div>

                <div class="space-y-3 text-sm text-gray-600">
                  <div><strong>Tipo:</strong> <?= $b['tipo'] === 'base' ? 'Ingresso Museo' : 'Esposizione' ?></div>
                  <div><strong>Percorso:</strong> <?= clean($b['esposizione'] ?? 'Museo Storico Severi') ?></div>
                  <div><strong>Data:</strong> <?= date('d/m/Y', strtotime($b['data_validita'])) ?><?= $b['ora_ingresso'] ? ' alle ' . clean(substr($b['ora_ingresso'], 0, 5)) : '' ?></div>
                  <div><strong>Categoria:</strong> <?= clean($categoriaBiglietto ?: '—') ?></div>
                  <div><strong>Servizi:</strong> <?= clean($b['servizi'] ?? 'Nessun servizio opzionale') ?></div>
                </div>

                <div class="mt-6 pt-5 border-t border-avorio-dark flex items-center justify-between">
                  <span class="text-xs text-gray-500">Presenta questo codice all'ingresso</span>
                  <span class="font-display text-xl font-bold text-oro">€ <?= number_format($totaleTicket, 2, ',', '.') ?></span>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($indicePagina < count($pagineBiglietti) - 1): ?>
          <div class="pdf-page-break html2pdf__page-break"></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</main>


<?php include __DIR__ . '/footer.php'; ?>
