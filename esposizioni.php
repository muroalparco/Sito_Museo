<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Esposizioni';

// Filtro per stato
$stato  = in_array($_GET['stato'] ?? '', ['Bozza','Pubblicata','Conclusa','Annullata'])
          ? $_GET['stato'] : null;

try {
    $pdo = getDB();
    if ($stato) {
        $stmt = $pdo->prepare("SELECT * FROM Esposizioni WHERE stato = ? ORDER BY data_inizio DESC");
        $stmt->execute([$stato]);
    } else {
        $stmt = $pdo->query("SELECT * FROM Esposizioni ORDER BY FIELD(stato,'Pubblicata','Bozza','Conclusa','Annullata'), data_inizio DESC");
    }
    $esposizioni = $stmt->fetchAll();
} catch (Exception $e) {
    $esposizioni = [];
}

$statiLabel = [
    'Pubblicata' => ['text'=>'In corso',   'class'=>'bg-green-100 text-green-800'],
    'Bozza'      => ['text'=>'Bozza',      'class'=>'bg-yellow-100 text-yellow-800'],
    'Conclusa'   => ['text'=>'Conclusa',   'class'=>'bg-gray-100 text-gray-600'],
    'Annullata'  => ['text'=>'Annullata',  'class'=>'bg-red-100 text-red-700'],
];
$icone = ['🏺','⚔️','🏰','🎨','🖼️','🗿','📜','🪙'];
$i = 0;

include __DIR__ . '/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Esposizioni</span>
  </div>
</div>

<!-- Page hero -->
<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Museo Storico Severi</p>
    <h1 class="font-display text-avorio text-4xl font-bold">Le nostre Esposizioni</h1>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<!-- Filtri -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-4">
  <div class="max-w-7xl mx-auto px-4 flex flex-wrap gap-3 items-center font-body text-sm">
    <span class="text-antracite-light font-bold">Filtra per:</span>
    <?php foreach ([null=>'Tutte', 'Pubblicata'=>'In corso', 'Conclusa'=>'Concluse'] as $val => $label): ?>
    <a href="?<?= $val ? 'stato='.urlencode($val) : '' ?>"
       class="px-4 py-1.5 rounded-full border transition-colors <?= ($stato === $val) ? 'bg-oro text-antracite border-oro font-bold' : 'border-gray-300 text-antracite hover:border-oro hover:text-oro' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <?php if (empty($esposizioni)): ?>
  <div class="text-center py-24">
    <div class="text-6xl mb-6">🏛️</div>
    <p class="text-gray-400 font-body">Nessuna esposizione trovata.</p>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach ($esposizioni as $esp): ?>
    <article class="bg-white rounded-xl shadow hover:shadow-xl transition-all duration-300 overflow-hidden border border-avorio-dark group hover:-translate-y-1">
      <div class="h-1.5 bg-oro"></div>
      <div class="p-7">
        <div class="flex items-start justify-between gap-3 mb-4">
          <span class="text-4xl"><?= $icone[$i++ % count($icone)] ?></span>
          <span class="text-xs px-2 py-1 rounded-full font-body font-bold <?= $statiLabel[$esp['stato']]['class'] ?>">
            <?= $statiLabel[$esp['stato']]['text'] ?>
          </span>
        </div>
        <h2 class="font-display text-xl font-bold text-antracite group-hover:text-oro transition-colors mb-3">
          <?= clean($esp['titolo']) ?>
        </h2>
        <p class="text-sm text-gray-500 leading-relaxed mb-5 line-clamp-3">
          <?= clean($esp['descrizione'] ?? 'Scopri questa affascinante esposizione al Museo Storico Severi.') ?>
        </p>
        <div class="border-t border-avorio-dark pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="text-xs text-acciaio font-body">
            <div><?= date('d/m/Y', strtotime($esp['data_inizio'])) ?></div>
            <div>→ <?= date('d/m/Y', strtotime($esp['data_fine'])) ?></div>
          </div>
          <?php if ($esp['stato'] === 'Pubblicata'): ?>
          <a href="esposizione.php?id=<?= (int)$esp['id_esposizione'] ?>"
             class="btn-oro px-4 py-2 rounded text-xs font-body uppercase tracking-wide">
            Prenota
          </a>
          <?php else: ?>
          <a href="esposizione.php?id=<?= (int)$esp['id_esposizione'] ?>"
             class="btn-outline px-4 py-2 rounded text-xs font-body uppercase tracking-wide">
            Dettagli
          </a>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
