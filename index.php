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
        ? 'id_esposizione, titolo, descrizione, emoji, data_inizio, data_fine, stato'
        : 'id_esposizione, titolo, descrizione, data_inizio, data_fine, stato';
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
$icone = ['','','','',''];
$i = 0;

$statiLabel = [
    'Pubblicata' => ['text'=>'In corso',   'class'=>'expo-status--published'],
    'Bozza'      => ['text'=>'Bozza',      'class'=>'expo-status--draft'],
    'Conclusa'   => ['text'=>'Conclusa',   'class'=>'expo-status--ended'],
    'Annullata'  => ['text'=>'Annullata',  'class'=>'expo-status--cancelled'],
];

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

include __DIR__ . '/header.php';
?>

<main id="main-content" class="flex-1">

<!-- hero principale  -->
<section class="relative bg-antracite overflow-hidden">
  <!-- sfondo decorativo -->
  <div class="absolute inset-0 opacity-10"
       style="background-image: repeating-linear-gradient(45deg, #8EC5E8 0, #8EC5E8 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>

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
            src="<?= SITE_URL ?>/img/logo-lcp.webp"
            srcset="<?= SITE_URL ?>/img/logo-lcp.webp 256w, <?= SITE_URL ?>/img/logo-256.webp 144w"
            sizes="(max-width: 767px) 220px, 256px"
            width="256"
            height="192"
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
      <path d="M0 60 C360 0, 1080 0, 1440 60 L1440 60 L0 60 Z" fill="#F7FBFF"/>
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
  <div class="expo-grid">
    <?php foreach ($esposizioni as $esp): ?>
    <?php
      $dataInizio = strtotime($esp['data_inizio']);
      $dataFine = strtotime($esp['data_fine']);
      $meseInizio = strtoupper(date('M', $dataInizio));
      $giornoInizio = date('d', $dataInizio);
      $giorniDurata = max(1, (int)floor(($dataFine - $dataInizio) / 86400) + 1);
      $statoCorrente = $esp['stato'] ?? 'Pubblicata';
      $statoInfo = $statiLabel[$statoCorrente] ?? ['text' => $statoCorrente, 'class' => 'expo-status--neutral'];
    ?>
    <article class="expo-card">
      <div class="expo-card__media">
        <div class="expo-card__pattern"></div>
        <div class="expo-card__kicker">Mostra</div>
        <div class="expo-card__date">
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
          <h3><?= clean($esp['titolo']) ?></h3>
        </div>

        <p class="expo-card__description">
          <?= clean($esp['descrizione'] ?? 'Scopri questa esposizione del Museo Storico Severi.') ?>
        </p>

        <?php if ($statoCorrente === 'Annullata'): ?>
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

          <?php if ($statoCorrente === 'Pubblicata'): ?>
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
      <h2 class="font-display text-3xl md:text-4xl text-avorio font-bold">Pianifica la visita</h2>
      <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <?php foreach ([
        ['','Scegli la mostra','Esplora le esposizioni e seleziona quella che ti incuriosisce di più.'],
        ['','Acquista il biglietto','Scegli la fascia oraria, la categoria di riduzione e i servizi opzionali.'],
        ['','Vivi l\'esperienza','Presentati all\'ingresso con il tuo codice biglietto e goditi la visita.'],
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

</main>

<?php include __DIR__ . '/footer.php'; ?>
