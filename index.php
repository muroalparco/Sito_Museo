<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Benvenuti';

$esposizioni = [];
$statistiche = [
    'esposizioni_attive' => 0,
    'servizi_extra'      => 0,
];

function indexEsposizioniSupportaEmoji(PDO $pdo): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Esposizioni LIKE 'emoji'");
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

try {
    $pdo = getDB();

    // Ultime esposizioni pubblicate
    $colonneEsposizioni = indexEsposizioniSupportaEmoji($pdo)
        ? 'id_esposizione, titolo, descrizione, emoji, data_inizio, data_fine'
        : 'id_esposizione, titolo, descrizione, data_inizio, data_fine';
    $stmt = $pdo->query(
        "SELECT {$colonneEsposizioni}
         FROM Esposizioni
         WHERE stato = 'Pubblicata'
         ORDER BY data_inizio DESC
         LIMIT 4"
    );
    $esposizioni = $stmt->fetchAll();

    // Numeri dinamici mostrati nella fascia statistiche della home.
    // Le esposizioni attive sono quelle pubblicate, cioè visibili agli utenti.
    $stmtStats = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM Esposizioni WHERE stato = 'Pubblicata') AS esposizioni_attive,
            (SELECT COUNT(*) FROM Servizi_Opzionali) AS servizi_extra"
    );
    $rowStats = $stmtStats->fetch();

    if ($rowStats) {
        $statistiche['esposizioni_attive'] = (int) $rowStats['esposizioni_attive'];
        $statistiche['servizi_extra']      = (int) $rowStats['servizi_extra'];
    }
} catch (Exception $e) {
    $esposizioni = [];
}

// Icone periodo storico usate solo come fallback se il database non ha ancora la colonna emoji.
$icone = ['🏺','⚔️','🏰','🎨','🖼️'];
$i = 0;

$areaGestionale = null;
if (isLogged()) {
    if (isTester()) {
        $areaGestionale = ['url' => 'admin.php', 'label' => 'Pannello tester'];
    } elseif (isAdmin()) {
        $areaGestionale = ['url' => 'admin.php', 'label' => 'Vista amministratore'];
    } elseif (isOperatore()) {
        $areaGestionale = ['url' => 'valida_biglietti.php', 'label' => 'Valida biglietti'];
    } elseif (isCassiere()) {
        $areaGestionale = ['url' => 'cassa.php', 'label' => 'Cassa'];
    }
}

$homeLogoLcpInline = 'data:image/webp;base64,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';
include __DIR__ . '/header.php';
?>

<!-- hero principale  -->
<section class="relative bg-antracite overflow-hidden">
  <!-- sfondo decorativo -->
  <div class="absolute inset-0 opacity-10"
       style="background-image: repeating-linear-gradient(45deg, #C9A84C 0, #C9A84C 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 md:py-28 lg:py-36">
    <div class="grid md:grid-cols-2 gap-10 md:gap-12 items-center">

      <!-- testo hero -->
      <div>
        <p class="fade-up text-oro font-body text-sm uppercase tracking-widest mb-3">
          Museo Storico Severi
        </p>
        <h1 class="fade-up delay-1 font-display text-avorio text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
          Viaggia attraverso<br>
          <span class="text-oro italic">la storia</span><br>
          dell'umanità
        </h1>
        <p class="fade-up delay-2 text-gray-300 font-body text-base sm:text-lg leading-relaxed mb-8 sm:mb-10 max-w-md">
          Dalle grandi civiltà dell'antichità al Rinascimento italiano, scopri secoli di arte, cultura e innovazione nelle nostre mostre permanenti e temporanee.
        </p>
        <div class="fade-up delay-3 flex flex-col sm:flex-row gap-4 text-center">
          <a href="esposizioni.php"
             class="btn-oro px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-block text-center">
            Scopri le mostre
          </a>
          <a href="info.php"
             class="btn-outline px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-block text-center">
            Biglietti & Info
          </a>
          <?php if ($areaGestionale): ?>
          <a href="<?= clean($areaGestionale['url']) ?>"
             class="bg-white text-antracite px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-flex items-center justify-center text-center font-bold hover:bg-avorio transition-colors">
            <?= clean($areaGestionale['label']) ?>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- logo decorativo -->
      <div class="flex justify-center items-center order-first md:order-none">
        <div class="relative h-52 sm:h-64 md:h-72 lg:h-80 w-full flex items-center justify-center">
          <img 
            src="<?= $homeLogoLcpInline ?>"
            width="208"
            height="208"
            alt="Logo Museo Storico Severi" 
            class="h-full w-auto object-contain drop-shadow-hero"
            decoding="async"
            loading="eager"
            fetchpriority="high"
          >
        </div>
      </div>

    </div>
  </div>

  <!-- onda decorativa -->
  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0 60 C360 0, 1080 0, 1440 60 L1440 60 L0 60 Z" fill="#F5F0E8"/>
    </svg>
  </div>
</section>

<!-- dati sito -->
<div class="bg-avorio-dark py-8 border-y border-oro border-opacity-30">
  <div class="max-w-5xl mx-auto px-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <?php foreach ([
      [$statistiche['esposizioni_attive'], 'Esposizioni attive'],
      ['10.000+', 'Visitatori l\'anno'],
      [$statistiche['servizi_extra'], 'Servizi extra'],
      ['2020', 'Anno di fondazione'],
    ] as $s): ?>
    <div>
      <div class="font-display text-3xl font-bold text-oro"><?= clean((string) $s[0]) ?></div>
      <div class="font-body text-xs text-antracite-light uppercase tracking-wide mt-1"><?= clean($s[1]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- esposizioni in evidenza  -->
<section class="py-14 sm:py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="text-center mb-12">
    <p class="text-oro font-body text-xs uppercase tracking-widest mb-2">Le nostre mostre</p>
    <h2 class="font-display text-2xl sm:text-3xl md:text-4xl text-antracite font-bold">Esposizioni in corso</h2>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>

  <?php if (empty($esposizioni)): ?>
  <p class="text-center text-gray-400 py-12">Nessuna esposizione disponibile al momento.</p>
  <?php else: ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach ($esposizioni as $idx => $esp): ?>
    <article class="bg-white rounded-lg shadow hover:shadow-xl transition-shadow overflow-hidden group border border-avorio-dark">
      <!-- intestazione colorata -->
      <div class="h-2 bg-oro"></div>

      <div class="p-6">
        <div class="text-3xl mb-3"><?= clean($esp['emoji'] ?? $icone[$i++ % count($icone)]) ?></div>
        <h3 class="font-display text-lg font-semibold text-antracite group-hover:text-oro transition-colors mb-2">
          <?= clean($esp['titolo']) ?>
        </h3>
        <p class="text-sm text-gray-500 leading-relaxed mb-4 line-clamp-3">
          <?= clean($esp['descrizione'] ?? 'Scopri questa affascinante esposizione.') ?>
        </p>
        <div class="text-xs text-acciaio font-body mb-4">
          <?= date('d/m/Y', strtotime($esp['data_inizio'])) ?> →
          <?= date('d/m/Y', strtotime($esp['data_fine'])) ?>
        </div>
        <a href="prenota.php?id=<?= (int)$esp['id_esposizione'] ?>"
           class="text-oro text-xs font-bold uppercase tracking-wide hover:underline">
          Prenota →
        </a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <div class="text-center mt-10">
    <a href="esposizioni.php"
       class="btn-outline px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-block text-center">
      Tutte le esposizioni
    </a>
  </div>
  <?php endif; ?>
</section>

<!-- informazioni di contatto e percorso visita -->
<section class="bg-antracite py-14 sm:py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <p class="text-oro font-body text-xs uppercase tracking-widest mb-2">Pianifica la visita</p>
      <h2 class="font-display text-3xl text-avorio font-bold">Come visitarci</h2>
      <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php foreach ([
        ['📋','Scegli la mostra','Esplora le esposizioni e seleziona quella che ti incuriosisce di più.'],
        ['🎟️','Acquista il biglietto','Scegli la fascia oraria, la categoria di riduzione e i servizi opzionali.'],
        ['🏛️','Vivi l\'esperienza','Presentati all\'ingresso con il tuo codice biglietto e goditi la visita.'],
      ] as $step): ?>
      <div class="text-center">
        <div class="text-4xl mb-4"><?= $step[0] ?></div>
        <h3 class="font-display text-oro text-xl mb-3"><?= clean($step[1]) ?></h3>
        <p class="text-gray-400 font-body text-sm leading-relaxed"><?= clean($step[2]) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-12">
      <a href="esposizioni.php"
         class="btn-oro px-10 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">
        Prenota ora
      </a>
    </div>
  </div>
</section>

<!-- citazione -->
<div class="py-16 bg-avorio">
  <div class="max-w-3xl mx-auto text-center px-4">
    <div class="text-oro text-5xl font-display leading-none mb-4">"</div>
    <blockquote class="font-display text-2xl md:text-3xl text-antracite italic leading-relaxed mb-6">
      Un popolo che ignora il proprio passato non saprà costruire il proprio futuro.
    </blockquote>
    <cite class="font-body text-sm text-acciaio uppercase tracking-widest">— Giulio Cesare</cite>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
