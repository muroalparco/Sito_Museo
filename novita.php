<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Novità';

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT titolo, descrizione, data_inizio, data_fine, stato
                         FROM Esposizioni
                         ORDER BY data_inizio DESC
                         LIMIT 6");
    $novita = $stmt->fetchAll();
} catch (Exception $e) {
    $novita = [];
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Novità</span>
  </div>
</div>

<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Museo Storico Severi</p>
    <h1 class="font-display text-avorio text-4xl font-bold">Novità dal museo</h1>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <section class="grid lg:grid-cols-3 gap-8 mb-14">
    <article class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden border border-avorio-dark">
      <div class="h-2 bg-oro"></div>
      <div class="p-8 md:p-10">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">In evidenza</p>
        <h2 class="font-display text-3xl font-bold text-antracite mb-4">Nuove esperienze di visita e percorsi tematici</h2>
        <p class="text-gray-500 leading-relaxed mb-6">Il Museo Storico Severi aggiorna periodicamente la propria offerta con esposizioni, attività didattiche e servizi pensati per rendere la visita più accessibile, ordinata e coinvolgente.</p>
        <a href="<?= SITE_URL ?>/info.php" class="btn-oro inline-block px-6 py-3 rounded font-body text-sm uppercase tracking-wide">Scopri le informazioni</a>
      </div>
    </article>

    <aside class="bg-antracite rounded-xl shadow p-8 text-avorio">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Comunicazioni</p>
      <h3 class="font-display text-2xl font-bold mb-4">Avvisi rapidi</h3>
      <ul class="space-y-4 text-sm text-gray-300">
        <li class="border-l-2 border-oro pl-4">Prenotazione consigliata per gruppi e scuole.</li>
        <li class="border-l-2 border-oro pl-4">Riduzioni disponibili per studenti, bambini e senior.</li>
        <li class="border-l-2 border-oro pl-4">Servizi opzionali acquistabili insieme al biglietto.</li>
      </ul>
    </aside>
  </section>

  <section>
    <div class="flex items-end justify-between gap-4 mb-6">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Aggiornamenti</p>
        <h2 class="font-display text-3xl font-bold text-antracite">Ultime esposizioni</h2>
      </div>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="hidden sm:inline-block btn-outline px-5 py-2 rounded text-sm font-body">Tutte le esposizioni</a>
    </div>

    <?php if (empty($novita)): ?>
      <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">Nessuna novità disponibile. Verifica la connessione al database.</div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($novita as $n): ?>
          <article class="bg-white rounded-xl shadow p-7 border border-avorio-dark hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between gap-3 mb-4">
              <span class="text-xs px-3 py-1 rounded-full bg-avorio-dark text-antracite font-bold"><?= clean($n['stato']) ?></span>
              <span class="text-xs text-acciaio"><?= date('d/m/Y', strtotime($n['data_inizio'])) ?></span>
            </div>
            <h3 class="font-display text-xl font-bold text-antracite mb-3"><?= clean($n['titolo']) ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed mb-5"><?= clean($n['descrizione'] ?? '') ?></p>
            <div class="text-xs text-gray-400">Fino al <?= date('d/m/Y', strtotime($n['data_fine'])) ?></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
