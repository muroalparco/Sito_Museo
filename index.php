<?php
// ============================================================
//  Homepage — Museo Storico Severi
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Benvenuti';

// Ultime esposizioni pubblicate
try {
    $pdo  = getDB();
    $stmt = $pdo->query(
        "SELECT id_esposizione, titolo, descrizione, data_inizio, data_fine
         FROM Esposizioni
         WHERE stato = 'Pubblicata'
         ORDER BY data_inizio DESC
         LIMIT 4"
    );
    $esposizioni = $stmt->fetchAll();
} catch (Exception $e) {
    $esposizioni = [];
}

// Icone periodo storico (mapping semplice)
$icone = ['🏺','⚔️','🏰','🎨','🖼️'];
$i = 0;

include __DIR__ . '/header.php';
?>

<!-- ══════════ HERO ══════════ -->
<section class="relative bg-antracite overflow-hidden">
  <!-- Sfondo decorativo -->
  <div class="absolute inset-0 opacity-10"
       style="background-image: repeating-linear-gradient(45deg, #C9A84C 0, #C9A84C 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-28 md:py-36">
    <div class="grid md:grid-cols-2 gap-12 items-center">

      <!-- Testo hero -->
      <div>
        <p class="fade-up text-oro font-body text-sm uppercase tracking-widest mb-3">
          Museo Storico Severi
        </p>
        <h1 class="fade-up delay-1 font-display text-avorio text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6">
          Viaggia attraverso<br>
          <span class="text-oro italic">la storia</span><br>
          dell'umanità
        </h1>
        <p class="fade-up delay-2 text-gray-300 font-body text-lg leading-relaxed mb-10 max-w-md">
          Dalle grandi civiltà dell'antichità al Rinascimento italiano, scopri secoli di arte, cultura e innovazione nelle nostre mostre permanenti e temporanee.
        </p>
        <div class="fade-up delay-3 flex flex-wrap gap-4">
          <a href="esposizioni.php"
             class="btn-oro px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">
            Scopri le mostre
          </a>
          <a href="info.php"
             class="btn-outline px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">
            Biglietti & Info
          </a>
        </div>
      </div>

      <!-- SVG decorativo lato destro -->
      <div class="hidden md:flex justify-center items-center fade-up delay-2">
        <div class="relative w-72 h-72">
          <!-- Anello esterno -->
          <svg viewBox="0 0 300 300" class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <circle cx="150" cy="150" r="140" fill="none" stroke="#C9A84C" stroke-width="1.5" opacity=".4"/>
            <circle cx="150" cy="150" r="110" fill="none" stroke="#C9A84C" stroke-width="1" opacity=".25"/>
            <!-- Piramide centrale grande -->
            <polygon points="150,30 270,240 30,240" fill="none" stroke="#C9A84C" stroke-width="2"/>
            <polygon points="150,70 230,220 70,220" fill="#C9A84C" opacity=".12"/>
            <!-- Sole / occhio -->
            <circle cx="150" cy="130" r="18" fill="none" stroke="#C9A84C" stroke-width="1.5" opacity=".6"/>
            <circle cx="150" cy="130" r="6" fill="#C9A84C" opacity=".8"/>
            <!-- Righe ornamentali -->
            <line x1="70" y1="260" x2="230" y2="260" stroke="#C9A84C" stroke-width="1" opacity=".3"/>
            <text x="150" y="282" text-anchor="middle" font-family="serif" font-size="11" fill="#C9A84C" opacity=".6" letter-spacing="4">MUSEUM</text>
          </svg>
        </div>
      </div>

    </div>
  </div>

  <!-- Onda decorativa -->
  <div class="absolute bottom-0 left-0 right-0">
    <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0 60 C360 0, 1080 0, 1440 60 L1440 60 L0 60 Z" fill="#F5F0E8"/>
    </svg>
  </div>
</section>

<!-- ══════════ STATS STRIP ══════════ -->
<section class="bg-avorio-dark py-8 border-y border-oro border-opacity-30">
  <div class="max-w-5xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
    <?php foreach ([
      ['5','Esposizioni attive'],
      ['10.000+','Visitatori l\'anno'],
      ['3','Servizi extra'],
      ['1987','Anno di fondazione'],
    ] as $s): ?>
    <div>
      <div class="font-display text-3xl font-bold text-oro"><?= $s[0] ?></div>
      <div class="font-body text-xs text-antracite-light uppercase tracking-wide mt-1"><?= $s[1] ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══════════ ESPOSIZIONI IN EVIDENZA ══════════ -->
<section class="py-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="text-center mb-12">
    <p class="text-oro font-body text-xs uppercase tracking-widest mb-2">Le nostre mostre</p>
    <h2 class="font-display text-3xl md:text-4xl text-antracite font-bold">Esposizioni in corso</h2>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>

  <?php if (empty($esposizioni)): ?>
  <p class="text-center text-gray-400 py-12">Nessuna esposizione disponibile al momento.</p>
  <?php else: ?>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    <?php foreach ($esposizioni as $idx => $esp): ?>
    <article class="bg-white rounded-lg shadow hover:shadow-xl transition-shadow overflow-hidden group border border-avorio-dark">
      <!-- Intestazione colorata -->
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
        <a href="esposizione.php?id=<?= (int)$esp['id_esposizione'] ?>"
           class="text-oro text-xs font-bold uppercase tracking-wide hover:underline">
          Scopri di più →
        </a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <div class="text-center mt-10">
    <a href="esposizioni.php"
       class="btn-outline px-8 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">
      Tutte le esposizioni
    </a>
  </div>
  <?php endif; ?>
</section>

<!-- ══════════ COME VISITARE ══════════ -->
<section class="bg-antracite py-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <p class="text-oro font-body text-xs uppercase tracking-widest mb-2">Pianifica la visita</p>
      <h2 class="font-display text-3xl text-avorio font-bold">Come visitarci</h2>
      <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
      <?php foreach ([
        ['📋','Scegli la mostra','Esplora le esposizioni e seleziona quella che ti incuriosisce di più.'],
        ['🎟️','Acquista il biglietto','Scegli la fascia oraria, la categoria di riduzione e i servizi opzionali.'],
        ['🏛️','Vivi l\'esperienza','Presentati all\'ingresso con il tuo codice biglietto e goditi la visita.'],
      ] as $step): ?>
      <div class="text-center">
        <div class="text-4xl mb-4"><?= $step[0] ?></div>
        <h3 class="font-display text-oro text-xl mb-3"><?= $step[1] ?></h3>
        <p class="text-gray-400 font-body text-sm leading-relaxed"><?= $step[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center mt-12">
      <?php if (!isLogged()): ?>
      <a href="registrazione.php"
         class="btn-oro px-10 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">
        Registrati e prenota
      </a>
      <?php else: ?>
      <a href="esposizioni.php"
         class="btn-oro px-10 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">
        Prenota ora
      </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ══════════ CITAZIONE ══════════ -->
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
