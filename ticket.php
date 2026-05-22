<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Biglietto';
$pdo = getDB();
$codice = strtoupper(trim($_GET['codice'] ?? $_GET['ticket'] ?? ''));
$errore = '';
$biglietto = null;

if ($codice === '') {
    $errore = 'Codice biglietto mancante.';
} else {
    $stmt = $pdo->prepare("\n        SELECT\n            b.*,\n            o.codice_recupero, o.stato_pagamento, COALESCE(o.stato_rimborso, 'Nessuno') AS stato_rimborso,\n            cr.nome AS categoria,\n            f.data AS data_fascia, f.ora_ingresso,\n            e.titolo AS esposizione\n        FROM Biglietti b\n        INNER JOIN Ordini o ON o.id_ordine = b.id_ordine\n        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n        WHERE b.codice_univoco = ?\n        LIMIT 1\n    ");
    $stmt->execute([$codice]);
    $biglietto = $stmt->fetch();
    if (!$biglietto) {
        $errore = 'Biglietto non trovato.';
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-600 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Biglietto</span>
  </div>
</div>

<main class="flex-1 py-12 px-4 bg-avorio">
  <section class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden">
    <div class="bg-antracite px-8 py-8 text-center">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Museo Storico Severi</p>
      <h1 class="font-display text-avorio text-3xl font-bold">Biglietto digitale</h1>
    </div>
    <div class="p-8 text-center">
      <?php if ($errore): ?>
        <div class="alert-error p-4 rounded text-sm text-left mb-6">⚠️ <?= clean($errore) ?></div>
        <a href="<?= SITE_URL ?>/index.php" class="btn-outline inline-block px-6 py-3 rounded">Torna alla home</a>
      <?php else: ?>
        <?php
          $ordineRimborsato = strcasecmp((string)($biglietto['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
          $stato = $ordineRimborsato ? 'Rimborsato' : $biglietto['stato'];
          $badge = $stato === 'Valido' ? 'bg-green-100 text-green-800' : ($stato === 'Utilizzato' ? 'bg-blue-100 text-blue-800' : ($stato === 'Non pagato' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'));
        ?>
        <?php if ($ordineRimborsato): ?>
          <div class="mx-auto mb-6 w-[220px] h-[220px] max-w-full rounded-xl border border-red-200 bg-red-50 text-red-700 p-6 flex items-center justify-center text-center text-sm font-bold">
            Biglietto rimborsato<br>non utilizzabile
          </div>
        <?php else: ?>
          <img src="<?= SITE_URL ?>/ticket_qr.php?codice=<?= urlencode($biglietto['codice_univoco']) ?>" width="220" height="220" alt="QR code biglietto <?= clean($biglietto['codice_univoco']) ?>" class="mx-auto mb-6 w-[220px] max-w-full rounded-xl border border-avorio-dark p-3 bg-white">
        <?php endif; ?>
        <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Codice biglietto</p>
        <h2 class="font-display text-3xl font-bold text-antracite mb-3"><?= clean($biglietto['codice_univoco']) ?></h2>
        <span class="inline-flex px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide <?= $badge ?>"><?= clean($stato) ?></span>

        <div class="mt-8 grid sm:grid-cols-2 gap-4 text-left text-sm text-gray-600">
          <div class="bg-avorio rounded-xl p-4"><strong>Percorso:</strong><br><?= clean($biglietto['esposizione'] ?? 'Museo Storico Severi') ?></div>
          <div class="bg-avorio rounded-xl p-4"><strong>Data:</strong><br><?= date('d/m/Y', strtotime($biglietto['data_validita'])) ?><?= !empty($biglietto['ora_ingresso']) ? ' alle ' . clean(substr($biglietto['ora_ingresso'], 0, 5)) : '' ?></div>
          <div class="bg-avorio rounded-xl p-4"><strong>Categoria:</strong><br><?= clean($biglietto['categoria'] ?? '—') ?></div>
          <div class="bg-avorio rounded-xl p-4"><strong>Ordine:</strong><br><?= clean($biglietto['codice_recupero']) ?></div>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
          <a href="<?= SITE_URL ?>/biglietti.php?codice=<?= urlencode($biglietto['codice_recupero']) ?>" class="btn-outline inline-block px-7 py-3 rounded-lg text-sm uppercase tracking-wide">
            Torna ai biglietti
          </a>

          <?php if (isLogged()): ?>
            <a href="<?= SITE_URL ?>/ordini.php" class="btn-outline inline-block px-7 py-3 rounded-lg text-sm uppercase tracking-wide">
              Torna ai miei ordini
            </a>
          <?php endif; ?>

          <?php if ($ordineRimborsato): ?>
            <p class="text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg p-4 w-full sm:w-auto">Ordine rimborsato: nessuna operazione disponibile su questo biglietto.</p>
          <?php endif; ?>

          <?php if (isOperatore() && $stato === 'Valido' && !$ordineRimborsato): ?>
            <a href="<?= SITE_URL ?>/valida_biglietti.php?codice=<?= urlencode($biglietto['codice_univoco']) ?>" class="btn-oro inline-block px-7 py-3 rounded-lg text-sm uppercase tracking-wide">Vai alla validazione</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
