<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Esposizioni';

$statiAmmessi = isAdmin()
    ? ['Bozza','Pubblicata','Conclusa','Annullata']
    : ['Pubblicata','Conclusa','Annullata'];
$statoRichiesto = $_GET['stato'] ?? '';
$stato = in_array($statoRichiesto, $statiAmmessi, true) ? $statoRichiesto : null;

try {
    $pdo = getDB();
    if ($stato) {
        $stmt = $pdo->prepare("SELECT id_esposizione, titolo, descrizione, data_inizio, data_fine, stato FROM Esposizioni WHERE stato = ? ORDER BY data_inizio DESC");
        $stmt->execute([$stato]);
    } else {
        if (isAdmin()) {
            $stmt = $pdo->query("SELECT id_esposizione, titolo, descrizione, data_inizio, data_fine, stato FROM Esposizioni ORDER BY FIELD(stato,'Pubblicata','Bozza','Conclusa','Annullata'), data_inizio DESC");
        } else {
            $stmt = $pdo->prepare("SELECT id_esposizione, titolo, descrizione, data_inizio, data_fine, stato FROM Esposizioni WHERE stato <> 'Bozza' ORDER BY FIELD(stato,'Pubblicata','Conclusa','Annullata'), data_inizio DESC");
            $stmt->execute();
        }
    }
    $esposizioni = $stmt->fetchAll();
} catch (Exception $e) {
    $esposizioni = [];
}

$statiLabel = [
    'Pubblicata' => ['text'=>'In corso',   'class'=>'expo-status--published'],
    'Bozza'      => ['text'=>'Bozza',      'class'=>'expo-status--draft'],
    'Conclusa'   => ['text'=>'Conclusa',   'class'=>'expo-status--ended'],
    'Annullata'  => ['text'=>'Annullata',  'class'=>'expo-status--cancelled'],
];

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-600 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors underline-offset-4 hover:underline">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Esposizioni</span>
  </div>
</div>

<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Museo Storico Severi</p>
    <h1 class="font-display text-avorio text-4xl font-bold">Le nostre Esposizioni</h1>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-4">
  <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 font-body text-sm esposizioni-filter-wrap">
    <div class="expo-filter-row flex flex-wrap gap-3 items-center">
      <span class="text-antracite-light font-bold">Filtra per:</span>
      <?php
        $filtri = [
          ['val' => null, 'label' => 'Tutte'],
          ['val' => 'Pubblicata', 'label' => 'In corso'],
          ['val' => 'Conclusa', 'label' => 'Concluse'],
        ];
        if (isAdmin()) $filtri[] = ['val' => 'Bozza', 'label' => 'Bozze'];
        foreach ($filtri as $f):
          $val = $f['val'];
          $isActive = ($stato === $val);
      ?>
      <a href="<?= $val ? '?stato='.urlencode((string)$val) : 'esposizioni.php' ?>"
         class="expo-filter-pill px-4 py-1.5 rounded-full border transition-colors <?= $isActive ? 'bg-oro text-antracite border-oro font-bold' : 'border-gray-300 text-antracite hover:border-oro hover:text-oro' ?>">
        <?= clean($f['label']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <a href="<?= SITE_URL ?>/recupera_ordine.php" class="btn-outline px-5 py-2 rounded text-center">
      Vuoi recuperare il tuo ordine?
    </a>
  </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <section class="mb-12">
    <article class="bg-white rounded-2xl shadow-xl overflow-hidden border border-avorio-dark">
      <div class="grid md:grid-cols-3">
        <div class="bg-antracite text-avorio p-8 md:p-10 flex flex-col justify-center">
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ingresso singolo</p>
          <h2 class="font-display text-3xl font-bold mb-3">Visita solo il museo</h2>
          <p class="text-gray-300 text-sm leading-relaxed">Acquista un biglietto base per accedere alle sale permanenti del Museo Storico Severi, senza prenotare una esposizione specifica.</p>
        </div>
        <div class="md:col-span-2 p-8 md:p-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div>
            <h3 class="font-display text-2xl font-bold text-antracite mb-3">Biglietto Museo</h3>
            <p class="text-gray-600 leading-relaxed max-w-2xl">Scegli la data della visita, la tariffa e gli eventuali servizi opzionali come audioguida, visita guidata o catalogo.</p>
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
    <p class="text-gray-500 font-body">Nessuna esposizione trovata.</p>
  </div>
  <?php else: ?>
  <div class="expo-grid">
    <?php foreach ($esposizioni as $esp): ?>
    <?php
      $dataInizio = strtotime($esp['data_inizio']);
      $dataFine = strtotime($esp['data_fine']);
      $meseInizio = strtoupper(date('M', $dataInizio));
      $giornoInizio = date('d', $dataInizio);
      $giorniDurata = max(1, (int)floor(($dataFine - $dataInizio) / 86400) + 1);
      $statoInfo = $statiLabel[$esp['stato']] ?? ['text' => $esp['stato'], 'class' => 'expo-status--neutral'];
    ?>
    <article class="expo-card">
      <div class="expo-card__media">
        <div class="expo-card__pattern"></div>
        <div class="expo-card__kicker">Mostra</div>
        <div class="expo-card__date" aria-label="Data inizio">
          <span><?= $giornoInizio ?></span>
          <strong><?= $meseInizio ?></strong>
        </div>
        <span class="expo-status <?= $statoInfo['class'] ?>">
          <?= clean($statoInfo['text']) ?>
        </span>
      </div>

      <div class="expo-card__body">
        <div class="expo-card__heading">
          <p>Percorso espositivo</p>
          <h2><?= clean($esp['titolo']) ?></h2>
        </div>

        <p class="expo-card__description">
          <?= clean($esp['descrizione'] ?? 'Scopri questa esposizione del Museo Storico Severi.') ?>
        </p>

        <?php if ($esp['stato'] === 'Annullata'): ?>
          <div class="expo-card__warning">
            Esposizione annullata. Ci scusiamo per il disagio.
          </div>
        <?php endif; ?>

        <dl class="expo-card__details">
          <div>
            <dt>Dal</dt>
            <dd><?= date('d/m/Y', $dataInizio) ?></dd>
          </div>
          <div>
            <dt>Al</dt>
            <dd><?= date('d/m/Y', $dataFine) ?></dd>
          </div>
          <div>
            <dt>Durata</dt>
            <dd><?= $giorniDurata ?> gg</dd>
          </div>
        </dl>

        <div class="expo-card__footer">
          <div>
            <span>Biglietto mostra</span>
            <strong>Museo Storico Severi</strong>
          </div>

          <?php if ($esp['stato'] === 'Pubblicata'): ?>
          <a href="prenota.php?id=<?= (int)$esp['id_esposizione'] ?>"
             class="btn-oro expo-card__action"
             aria-label="Prenota <?= clean($esp['titolo']) ?>">
            Prenota
          </a>
          <?php else: ?>
          <span class="btn-outline expo-card__action expo-card__action--disabled">
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
