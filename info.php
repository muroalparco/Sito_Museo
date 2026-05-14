<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Info & Tariffe';

try {
    $pdo = getDB();
    $tariffeStmt = $pdo->query("SELECT t.tipo_biglietto, cr.nome AS categoria, cr.percentuale_sconto, cr.documento_richiesto, t.prezzo
                                FROM Tariffe t
                                JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
                                ORDER BY FIELD(t.tipo_biglietto,'base','esposizione'), t.prezzo DESC");
    $tariffe = $tariffeStmt->fetchAll();

    $serviziStmt = $pdo->query("SELECT nome, descrizione, prezzo FROM Servizi_Opzionali ORDER BY prezzo ASC");
    $servizi = $serviziStmt->fetchAll();
} catch (Exception $e) {
    $tariffe = [];
    $servizi = [];
}

$tariffeBase = array_values(array_filter($tariffe, fn($t) => ($t['tipo_biglietto'] ?? '') === 'base'));
$tariffeEsposizione = array_values(array_filter($tariffe, fn($t) => ($t['tipo_biglietto'] ?? '') === 'esposizione'));

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Info & Tariffe</span>
  </div>
</div>

<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Organizza la visita</p>
    <h1 class="font-display text-avorio text-4xl font-bold">Informazioni & Tariffe</h1>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
    <article class="bg-white rounded-xl shadow p-7 border border-avorio-dark">
      <div class="text-3xl mb-3">🕘</div>
      <h2 class="font-display text-xl font-bold text-antracite mb-2">Orari</h2>
      <p class="text-gray-500 text-sm leading-relaxed">Dal martedì alla domenica, dalle 9:00 alle 18:00. Ultimo ingresso consigliato alle 17:00.</p>
    </article>
    <article class="bg-white rounded-xl shadow p-7 border border-avorio-dark">
      <div class="text-3xl mb-3">📍</div>
      <h2 class="font-display text-xl font-bold text-antracite mb-2">Dove siamo</h2>
      <p class="text-gray-500 text-sm leading-relaxed">Via Luigi Pettinati 46, 35128 Padova. Il museo è raggiungibile con mezzi pubblici e dispone di accesso facilitato.</p>
    </article>
    <article class="bg-white rounded-xl shadow p-7 border border-avorio-dark">
      <div class="text-3xl mb-3">🎧</div>
      <h2 class="font-display text-xl font-bold text-antracite mb-2">Servizi</h2>
      <p class="text-gray-500 text-sm leading-relaxed">Audioguide, visite guidate, cataloghi e servizi aggiuntivi possono essere associati al biglietto.</p>
    </article>
  </section>

  <section class="mb-12">
    <div class="flex items-end justify-between gap-4 mb-6">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Biglietteria</p>
        <h2 class="font-display text-3xl font-bold text-antracite">Tariffe disponibili</h2>
        <p class="text-gray-500 text-sm mt-2">A sinistra trovi i prezzi per il solo ingresso al museo; a destra quelli per le esposizioni.</p>
      </div>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="hidden sm:inline-block btn-outline px-5 py-2 rounded text-sm font-body">Vedi esposizioni</a>
    </div>

    <?php if (empty($tariffe)): ?>
      <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">Tariffe non disponibili. Verifica la connessione al database.</div>
    <?php else: ?>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <article class="bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
          <div class="bg-antracite px-6 py-5">
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Ingresso</p>
            <h3 class="font-display text-2xl text-avorio font-bold">Solo museo</h3>
            <p class="text-gray-400 text-sm mt-2">Tariffe base valide per la visita ordinaria al museo.</p>
          </div>

          <div class="divide-y divide-avorio-dark">
            <?php if (empty($tariffeBase)): ?>
              <div class="p-6 text-gray-500 text-sm">Nessuna tariffa base disponibile.</div>
            <?php else: ?>
              <?php foreach ($tariffeBase as $t): ?>
                <div class="p-6 flex items-start justify-between gap-4 hover:bg-avorio transition-colors">
                  <div>
                    <h4 class="font-display text-xl font-bold text-antracite"><?= clean($t['categoria']) ?></h4>
                    <p class="text-sm text-gray-500 mt-1">Riduzione: <?= clean($t['percentuale_sconto']) ?>%</p>
                    <?php if (!empty($t['documento_richiesto'])): ?>
                      <p class="text-xs text-acciaio mt-1">Documento richiesto: <?= clean($t['documento_richiesto']) ?></p>
                    <?php endif; ?>
                  </div>
                  <div class="text-right shrink-0">
                    <div class="text-2xl font-bold text-oro">€ <?= number_format((float)$t['prezzo'], 2, ',', '.') ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article>

        <article class="bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
          <div class="bg-antracite px-6 py-5">
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Ingresso</p>
            <h3 class="font-display text-2xl text-avorio font-bold">Esposizioni</h3>
            <p class="text-gray-400 text-sm mt-2">Tariffe dedicate alla visita delle esposizioni temporanee.</p>
          </div>

          <div class="divide-y divide-avorio-dark">
            <?php if (empty($tariffeEsposizione)): ?>
              <div class="p-6 text-gray-500 text-sm">Nessuna tariffa esposizione disponibile.</div>
            <?php else: ?>
              <?php foreach ($tariffeEsposizione as $t): ?>
                <div class="p-6 flex items-start justify-between gap-4 hover:bg-avorio transition-colors">
                  <div>
                    <h4 class="font-display text-xl font-bold text-antracite"><?= clean($t['categoria']) ?></h4>
                    <p class="text-sm text-gray-500 mt-1">Riduzione: <?= clean($t['percentuale_sconto']) ?>%</p>
                    <?php if (!empty($t['documento_richiesto'])): ?>
                      <p class="text-xs text-acciaio mt-1">Documento richiesto: <?= clean($t['documento_richiesto']) ?></p>
                    <?php endif; ?>
                  </div>
                  <div class="text-right shrink-0">
                    <div class="text-2xl font-bold text-oro">€ <?= number_format((float)$t['prezzo'], 2, ',', '.') ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article>
      </div>

      <div class="mt-6 sm:hidden">
        <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline inline-block px-5 py-2 rounded text-sm font-body">Vedi esposizioni</a>
      </div>
    <?php endif; ?>
  </section>

  <section>
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Esperienza</p>
    <h2 class="font-display text-3xl font-bold text-antracite mb-6">Servizi opzionali</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (empty($servizi)): ?>
        <div class="bg-white rounded-xl shadow p-8 text-gray-500 md:col-span-3">Servizi non disponibili.</div>
      <?php else: ?>
        <?php foreach ($servizi as $s): ?>
          <article class="bg-white rounded-xl shadow p-7 border border-avorio-dark hover:shadow-lg transition-shadow">
            <h3 class="font-display text-xl font-bold text-antracite mb-2"><?= clean($s['nome']) ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed mb-4"><?= clean($s['descrizione'] ?? '') ?></p>
            <div class="text-oro font-bold">€ <?= number_format((float)$s['prezzo'], 2, ',', '.') ?></div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
