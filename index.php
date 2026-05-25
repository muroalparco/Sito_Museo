<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Benvenuti';

$esposizioni = [];
$statistiche = [
    'esposizioni_attive' => 0,
    'servizi_extra'      => 0,
    'visitatori_anno'    => 0,
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
            (SELECT COUNT(*) FROM Servizi_Opzionali) AS servizi_extra,
            (
                SELECT COUNT(*)
                FROM Biglietti b
                INNER JOIN Ordini o ON o.id_ordine = b.id_ordine
                WHERE o.stato_pagamento = 'Pagato'
                  AND COALESCE(o.stato_rimborso, 'Nessuno') <> 'Accettato'
                  AND YEAR(COALESCE(b.data_validita, o.data_acquisto)) = YEAR(CURDATE())
            ) AS visitatori_anno"
    );
    $rowStats = $stmtStats->fetch();

    if ($rowStats) {
        $statistiche['esposizioni_attive'] = (int) $rowStats['esposizioni_attive'];
        $statistiche['servizi_extra']      = (int) $rowStats['servizi_extra'];
        $statistiche['visitatori_anno']    = (int) $rowStats['visitatori_anno'];
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

<style>
  .home-stats-section {
    background: linear-gradient(180deg, #eef7fd 0%, #ffffff 100%);
    padding: 2.6rem 1rem 2.8rem;
    border-top: 1px solid rgba(142, 197, 232, .18);
  }

  .home-stats-wrap {
    max-width: 1120px;
    margin: 0 auto;
    transform: none;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
  }

  .home-stat-card {
    min-height: 118px;
    display: flex;
    align-items: flex-start;
    gap: .9rem;
    padding: 1.05rem;
    border-radius: 1.15rem;
    background: rgba(255, 255, 255, .96);
    border: 1px solid rgba(142, 197, 232, .42);
    box-shadow: 0 14px 34px rgba(16, 39, 68, .10);
  }

  .home-stat-icon {
    width: 2.55rem;
    height: 2.55rem;
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: linear-gradient(135deg, #e4f5ff, #fff7e6);
    font-size: 1.25rem;
  }

  .home-stat-card strong {
    display: block;
    font-family: Georgia, serif;
    font-size: clamp(1.55rem, 2.5vw, 2.05rem);
    line-height: 1;
    color: #102744;
  }

  .home-stat-card p {
    margin: .35rem 0 .2rem;
    color: #1c5d8b;
    font-size: .72rem;
    line-height: 1.25;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 900;
  }

  .home-stat-card small {
    display: block;
    color: #5f7286;
    font-size: .78rem;
    line-height: 1.35;
  }

  @media (max-width: 900px) {
    .home-stats-wrap { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }

  @media (max-width: 540px) {
    .home-stats-section { padding: 1.5rem .9rem 1.8rem; }
    .home-stats-wrap {
      transform: none;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: .75rem;
    }
    .home-stat-card {
      min-height: 132px;
      flex-direction: column;
      gap: .55rem;
      padding: .85rem;
    }
    .home-stat-icon { width: 2.2rem; height: 2.2rem; border-radius: .85rem; }
    .home-stat-card strong { font-size: 1.55rem; }
    .home-stat-card p { font-size: .65rem; letter-spacing: .06em; }
    .home-stat-card small { font-size: .72rem; }
  }
</style>

<main id="main-content" class="flex-1">

<!-- hero principale  -->
<section class="relative bg-antracite overflow-hidden">
  <!-- sfondo decorativo -->
  <div class="absolute inset-0 opacity-10"
       style="background-image: repeating-linear-gradient(45deg, #8EC5E8 0, #8EC5E8 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 md:py-24 lg:py-28">
    <div class="max-w-4xl">

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

    </div>
  </div>
</section>

<!-- dati sito -->
<section class="home-stats-section" aria-label="Numeri del museo">
  <div class="home-stats-wrap">
    <?php foreach ([
      ['🏛️', $statistiche['esposizioni_attive'], 'Esposizioni attive', 'Mostre pubblicate e prenotabili'],
      ['👥', number_format((int) $statistiche['visitatori_anno'], 0, ',', '.'), 'Visitatori quest’anno', 'Biglietti pagati nell’anno corrente'],
      ['🎧', $statistiche['servizi_extra'], 'Servizi extra', 'Opzioni aggiuntive per la visita'],
      ['⭐', '2020', 'Anno di fondazione', 'Il punto di partenza del progetto'],
    ] as $s): ?>
    <article class="home-stat-card">
      <span class="home-stat-icon" aria-hidden="true"><?= clean($s[0]) ?></span>
      <div>
        <strong><?= clean((string) $s[1]) ?></strong>
        <p><?= clean($s[2]) ?></p>
        <small><?= clean($s[3]) ?></small>
      </div>
    </article>
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
