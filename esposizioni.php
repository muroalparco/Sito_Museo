<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Esposizioni';

$statiAmmessi = isAdmin()
    ? ['Bozza','Pubblicata','Conclusa','Annullata']
    : ['Pubblicata','Conclusa','Annullata'];

// Filtro per stato: le bozze sono visibili solo agli amministratori
$statoRichiesto = $_GET['stato'] ?? '';
$stato = in_array($statoRichiesto, $statiAmmessi, true) ? $statoRichiesto : null;

function esposizioniPaginaSupportaEmoji(PDO $pdo): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Esposizioni LIKE 'emoji'");
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

try {
    $pdo = getDB();
    $colonneEsposizioni = esposizioniPaginaSupportaEmoji($pdo) ? '*' : 'id_esposizione, titolo, descrizione, data_inizio, data_fine, stato';
    if ($stato) {
        $stmt = $pdo->prepare("SELECT {$colonneEsposizioni} FROM Esposizioni WHERE stato = ? ORDER BY data_inizio DESC");
        $stmt->execute([$stato]);
    } else {
        if (isAdmin()) {
            $stmt = $pdo->query("SELECT {$colonneEsposizioni} FROM Esposizioni ORDER BY FIELD(stato,'Pubblicata','Bozza','Conclusa','Annullata'), data_inizio DESC");
        } else {
            $stmt = $pdo->prepare("SELECT {$colonneEsposizioni} FROM Esposizioni WHERE stato <> 'Bozza' ORDER BY FIELD(stato,'Pubblicata','Conclusa','Annullata'), data_inizio DESC");
            $stmt->execute();
        }
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

<!-- Filtri e recupero ordine -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-4">
  <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 font-body text-sm">
    <div class="flex flex-wrap gap-3 items-center">
      <span class="text-antracite-light font-bold">Filtra per:</span>
      <?php
        $filtri = [
          ['val' => null, 'label' => 'Tutte'],
          ['val' => 'Pubblicata', 'label' => 'In corso'],
          ['val' => 'Conclusa', 'label' => 'Concluse'],
        ];
        if (isAdmin()) {
          $filtri[] = ['val' => 'Bozza', 'label' => 'Bozze'];
        }
        foreach ($filtri as $f):
          $val = $f['val'];
          $isActive = ($stato === $val);
      ?>
      <a href="<?= $val ? '?stato='.urlencode((string)$val) : 'esposizioni.php' ?>"
         class="px-4 py-1.5 rounded-full border transition-colors <?= $isActive ? 'bg-oro text-antracite border-oro font-bold' : 'border-gray-300 text-antracite hover:border-oro hover:text-oro' ?>">
        <?= $f['label'] ?>
      </a>
      <?php endforeach; ?>
    </div>
    <a href="<?= SITE_URL ?>/recupera_ordine.php" class="btn-outline px-5 py-2 rounded text-center">
      Vuoi recuperare il tuo ordine?
    </a>
  </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <!-- Card grande: ingresso solo museo -->
  <section class="mb-12">
    <article class="bg-white rounded-2xl shadow-xl overflow-hidden border border-avorio-dark">
      <div class="grid md:grid-cols-3">
        <div class="bg-antracite text-avorio p-8 md:p-10 flex flex-col justify-center">
          <div class="text-5xl mb-5">🏛️</div>
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ingresso singolo</p>
          <h2 class="font-display text-3xl font-bold mb-3">Visita solo il museo</h2>
          <p class="text-gray-300 text-sm leading-relaxed">Acquista un biglietto base per accedere alle sale permanenti del Museo Storico Severi, senza prenotare una esposizione specifica.</p>
        </div>
        <div class="md:col-span-2 p-8 md:p-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div>
            <h3 class="font-display text-2xl font-bold text-antracite mb-3">Biglietto Museo</h3>
            <p class="text-gray-500 leading-relaxed max-w-2xl">Scegli la data della visita, la tariffa e gli eventuali servizi opzionali come audioguida, visita guidata o catalogo.</p>
          </div>
          <a href="<?= SITE_URL ?>/prenota.php?tipo=base" class="btn-oro px-7 py-3 rounded font-body text-sm uppercase tracking-wide text-center whitespace-nowrap">
            Acquista ingresso
          </a>
        </div>
      </div>
    </article>
  </section>

  <?php if (empty($esposizioni)): ?>
  <div class="text-center py-24">
    <div class="text-6xl mb-6">🏛️</div>
    <p class="text-gray-400 font-body">Nessuna esposizione trovata.</p>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
    <?php foreach ($esposizioni as $esp): ?>
    <?php
      $icona = $esp['emoji'] ?? $icone[$i++ % count($icone)];
      $dataInizio = strtotime($esp['data_inizio']);
      $dataFine = strtotime($esp['data_fine']);
      $meseInizio = strtoupper(date('M', $dataInizio));
      $giornoInizio = date('d', $dataInizio);
    ?>
    <article class="relative bg-white rounded-[1.75rem] shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-avorio-dark group hover:-translate-y-2">
      <div class="absolute top-0 left-0 right-0 h-28 bg-antracite overflow-hidden">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 25% 20%, #C9A84C 0, transparent 22%), radial-gradient(circle at 75% 40%, #C9A84C 0, transparent 18%), repeating-linear-gradient(135deg, #C9A84C 0, #C9A84C 1px, transparent 0, transparent 38%); background-size: auto, auto, 22px 22px;"></div>
      </div>

      <div class="relative p-6 pt-7">
        <div class="flex items-start justify-between gap-4 mb-7">
          <div class="w-20 h-20 -mt-3 rounded-2xl bg-avorio border-4 border-white shadow-lg flex items-center justify-center text-4xl group-hover:scale-105 transition-transform duration-300">
            <?= clean($icona) ?>
          </div>

          <div class="text-right">
            <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/95 shadow-sm text-[11px] font-body font-bold uppercase tracking-wide <?= $statiLabel[$esp['stato']]['class'] ?>">
              <?= $statiLabel[$esp['stato']]['text'] ?>
            </div>
            <div class="mt-3 bg-oro text-antracite rounded-2xl shadow-md inline-flex flex-col items-center justify-center text-center min-w-[74px] h-[58px] px-3">
              <div class="font-display text-2xl font-bold leading-none text-center"><?= $giornoInizio ?></div>
              <div class="text-[10px] uppercase tracking-widest font-body font-bold text-center mt-1"><?= $meseInizio ?></div>
            </div>
          </div>
        </div>

        <div class="mb-5">
          <p class="text-oro text-[11px] uppercase tracking-[0.22em] font-body mb-2">Percorso espositivo</p>
          <h2 class="font-display text-2xl font-bold text-antracite group-hover:text-oro transition-colors leading-tight min-h-[3.5rem]">
            <?= clean($esp['titolo']) ?>
          </h2>
        </div>

        <p class="text-sm text-gray-500 leading-relaxed mb-6 line-clamp-4 min-h-[5.5rem]">
          <?= clean($esp['descrizione'] ?? 'Scopri questa affascinante esposizione al Museo Storico Severi.') ?>
        </p>

        <div class="bg-avorio rounded-2xl border border-avorio-dark p-4 mb-6">
          <div class="flex items-center justify-between gap-3 text-xs font-body text-antracite">
            <div>
              <div class="text-gray-400 uppercase tracking-widest mb-1">Dal</div>
              <div class="font-bold"><?= date('d/m/Y', $dataInizio) ?></div>
            </div>
            <div class="h-px flex-1 bg-oro/40"></div>
            <div class="text-right">
              <div class="text-gray-400 uppercase tracking-widest mb-1">Al</div>
              <div class="font-bold"><?= date('d/m/Y', $dataFine) ?></div>
            </div>
          </div>
        </div>

        <div class="relative border-t border-dashed border-oro/50 pt-5 flex items-center justify-between gap-4">
          <span class="absolute -top-3 -left-9 w-6 h-6 rounded-full bg-avorio"></span>
          <span class="absolute -top-3 -right-9 w-6 h-6 rounded-full bg-avorio"></span>

          <div class="text-xs text-acciaio font-body">
            <span class="block uppercase tracking-widest text-gray-400">Biglietto mostra</span>
            <span class="font-bold text-antracite">Museo Storico Severi</span>
          </div>

          <?php if ($esp['stato'] === 'Pubblicata'): ?>
          <a href="prenota.php?id=<?= (int)$esp['id_esposizione'] ?>"
             class="btn-oro px-5 py-2.5 rounded-full text-xs font-body uppercase tracking-wide text-center whitespace-nowrap shadow-md hover:shadow-lg">
            Prenota
          </a>
          <?php else: ?>
          <span class="btn-outline px-5 py-2.5 rounded-full text-xs font-body uppercase tracking-wide text-center opacity-60 cursor-not-allowed whitespace-nowrap">
            Non prenotabile
          </span>
          <?php endif; ?>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
