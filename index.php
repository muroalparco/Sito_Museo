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

try {
    $pdo = getDB();

    // Ultime esposizioni pubblicate
    $stmt = $pdo->query(
        "SELECT id_esposizione, titolo, descrizione, data_inizio, data_fine
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

// Icone periodo storico
$icone = ['🏺','⚔️','🏰','🎨','🖼️'];
$i = 0;

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
          <?php if (isAdmin()): ?>
          <a href="admin.php"
             class="bg-white text-antracite px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-flex items-center justify-center text-center font-bold hover:bg-avorio transition-colors">
            Vista amministratore
          </a>
          <?php endif; ?>
          <?php if (isOperatore() && !isAdmin()): ?>
          <a href="valida_biglietti.php"
             class="bg-oro text-antracite px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-flex items-center justify-center text-center font-bold hover:bg-oro-dark transition-colors">
            Valida biglietti
          </a>
          <?php endif; ?>
          <?php if (isCassiere()): ?>
          <a href="cassa.php"
             class="bg-oro text-antracite px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-flex items-center justify-center text-center font-bold hover:bg-oro-dark transition-colors">
            Cassa
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- logo decorativo -->
      <div class="flex justify-center items-center fade-up delay-2 order-first md:order-none">
        <div class="relative h-52 sm:h-64 md:h-72 lg:h-80 w-full flex items-center justify-center">
          <img 
            src="<?= SITE_URL ?>/img/logo.png" 
            alt="Logo Museo Storico Severi" 
            class="h-full w-auto object-contain drop-shadow-[0_0_22px_rgba(201,168,76,0.35)]"
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
<section class="bg-avorio-dark py-8 border-y border-oro border-opacity-30">
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
</section>

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
        <div class="text-3xl mb-3"><?= $icone[$i++ % count($icone)] ?></div>
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
<section class="py-16 bg-avorio">
  <div class="max-w-3xl mx-auto text-center px-4">
    <div class="text-oro text-5xl font-display leading-none mb-4">"</div>
    <blockquote class="font-display text-2xl md:text-3xl text-antracite italic leading-relaxed mb-6">
      Un popolo che ignora il proprio passato non saprà costruire il proprio futuro.
    </blockquote>
    <cite class="font-body text-sm text-acciaio uppercase tracking-widest">— Giulio Cesare</cite>
  </div>
</section>

<?php include __DIR__ . '/footer.php'; ?>
