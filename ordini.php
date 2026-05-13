<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$pageTitle = 'I miei ordini';
$pdo = getDB();

$stmt = $pdo->prepare(" 
    SELECT 
        o.id_ordine,
        o.codice_recupero,
        o.data_acquisto,
        o.importo_totale,
        o.stato_pagamento,
        COUNT(b.id_biglietto) AS numero_biglietti,
        GROUP_CONCAT(DISTINCT e.titolo SEPARATOR ', ') AS esposizioni
    FROM Ordini o
    LEFT JOIN Biglietti b ON b.id_ordine = o.id_ordine
    LEFT JOIN Fasce_Orarie f ON b.id_fascia = f.id_fascia
    LEFT JOIN Esposizioni e ON f.id_esposizione = e.id_esposizione
    WHERE o.id_utente = ?
    GROUP BY o.id_ordine, o.codice_recupero, o.data_acquisto, o.importo_totale, o.stato_pagamento
    ORDER BY o.data_acquisto DESC
");

$stmt->execute([$_SESSION['utente_id']]);
$ordini = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">I miei ordini</span>
  </div>
</div>

<main class="flex-1 bg-avorio py-10 sm:py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <section class="mb-8 sm:mb-10">
      <h1 class="font-display text-3xl sm:text-4xl md:text-5xl font-bold text-antracite mb-4">
        I miei ordini
      </h1>
      <p class="text-gray-600 font-body text-base sm:text-lg max-w-2xl">
        Consulta lo storico dei tuoi acquisti e delle prenotazioni effettuate al Museo Storico Severi.
      </p>
    </section>

    <?php if (empty($ordini)): ?>
      <div class="bg-white rounded-2xl shadow-md border border-avorio-dark p-6 sm:p-10 text-center">
        <div class="text-5xl mb-4">🎟️</div>
        <h2 class="font-display text-2xl font-semibold text-antracite mb-4">
          Nessun ordine presente
        </h2>
        <p class="text-gray-600 mb-6">
          Non hai ancora effettuato prenotazioni o acquisti.
        </p>
        <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro inline-block w-full sm:w-auto px-6 py-3 rounded-xl transition text-center">
          Scopri le esposizioni
        </a>
      </div>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($ordini as $ordine): ?>
          <article class="bg-white rounded-2xl shadow-md border border-avorio-dark p-5 sm:p-6 md:p-8 hover:shadow-lg transition">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                  <span class="bg-acciaio text-white px-3 py-1 rounded-full text-sm font-semibold">
                    Ordine #<?= clean((string)$ordine['id_ordine']) ?>
                  </span>
                  <span class="bg-oro/20 text-antracite px-3 py-1 rounded-full text-sm">
                    <?= clean((string)$ordine['numero_biglietti']) ?> biglietto/i
                  </span>
                  <?php
                    $statoPagamento = $ordine['stato_pagamento'] ?? 'Pagato';
                    $statoClass = $statoPagamento === 'Pagato' ? 'bg-green-100 text-green-800' : ($statoPagamento === 'Non pagato' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700');
                  ?>
                  <span class="<?= $statoClass ?> px-3 py-1 rounded-full text-sm">
                    <?= clean($statoPagamento) ?>
                  </span>
                </div>

                <h2 class="font-display text-xl sm:text-2xl font-semibold text-antracite mb-2 break-words">
                  <?= !empty($ordine['esposizioni']) ? clean($ordine['esposizioni']) : 'Biglietto museo' ?>
                </h2>

                <p class="text-gray-600 text-sm sm:text-base">
                  Data acquisto:
                  <strong><?= date('d/m/Y H:i', strtotime($ordine['data_acquisto'])) ?></strong>
                </p>
                <p class="text-gray-500 text-sm mt-1">
                  Codice recupero: <strong><?= clean($ordine['codice_recupero']) ?></strong>
                </p>
              </div>

              <div class="md:text-right shrink-0">
                <p class="text-sm uppercase tracking-widest text-gray-500 mb-1">Totale</p>
                <p class="font-display text-3xl font-bold text-oro mb-4">
                  € <?= number_format((float)$ordine['importo_totale'], 2, ',', '.') ?>
                </p>
                <a href="<?= SITE_URL ?>/biglietti.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-outline inline-block px-5 py-2 rounded text-sm">
                  Vedi biglietti
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
