<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Novità';
$novita = [];
$conteggioNovita = 0;

try {
    $pdo = getDB();
    $where = isAdmin() ? '' : "WHERE stato <> 'Bozza'";
    $stmt = $pdo->query("SELECT id_esposizione, titolo, descrizione, data_inizio, data_fine, stato
                         FROM Esposizioni
                         $where
                         ORDER BY FIELD(stato,'Pubblicata','Annullata','Conclusa','Bozza'), data_inizio DESC
                         LIMIT 9");
    $novita = $stmt->fetchAll();
    $stmtCount = $pdo->query("SELECT COUNT(*) AS totale FROM Esposizioni $where");
    $conteggioNovita = (int)($stmtCount->fetch()['totale'] ?? 0);
} catch (Exception $e) {
    $novita = [];
    $conteggioNovita = 0;
}

$primaNovita = $novita[0] ?? null;
$altreNovita = array_slice($novita, 1);

function badgeStatoClass(string $stato): string
{
    return match ($stato) {
        'Pubblicata' => 'bg-green-100 text-green-800 border-green-200',
        'Bozza' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'Conclusa' => 'bg-gray-100 text-gray-700 border-gray-200',
        'Annullata' => 'bg-red-100 text-red-800 border-red-200',
        default => 'bg-avorio-dark text-antracite border-avorio-dark',
    };
}

function labelStatoNovita(string $stato): string
{
    return $stato === 'Pubblicata' ? 'In corso' : $stato;
}

$statiLabel = [
    'Pubblicata' => ['text'=>'In corso',   'class'=>'expo-status--published'],
    'Bozza'      => ['text'=>'Bozza',      'class'=>'expo-status--draft'],
    'Conclusa'   => ['text'=>'Conclusa',   'class'=>'expo-status--ended'],
    'Annullata'  => ['text'=>'Annullata',  'class'=>'expo-status--cancelled'],
];

function fotoNovita(int $index): string
{
    $foto = ['sala-museo', 'galleria-storica', 'dettaglio-opera', 'percorso-museale', 'laboratorio-didattico'];
    return $foto[$index % count($foto)];
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-600 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors underline-offset-4 hover:underline">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Novità</span>
  </div>
</div>

<section class="relative bg-antracite overflow-hidden">
  <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 20% 20%, #8EC5E8 0, transparent 28%), radial-gradient(circle at 80% 10%, #8EC5E8 0, transparent 22%), repeating-linear-gradient(45deg, #8EC5E8 0, #8EC5E8 1px, transparent 0, transparent 44%); background-size: auto, auto, 22px 22px;"></div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
    <div class="grid lg:grid-cols-[1.2fr_0.8fr] gap-10 items-center">
      <div class="fade-up">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-3">Museo Storico Severi</p>
        <h1 class="font-display text-avorio text-4xl md:text-5xl font-bold leading-tight mb-5">Novità dal museo</h1>
        <p class="text-gray-300 leading-relaxed max-w-2xl">Scopri gli aggiornamenti più recenti, le esposizioni in arrivo e i percorsi tematici disponibili per visitatori, gruppi e scuole.</p>
      </div>
      <div class="grid grid-cols-2 gap-4 fade-up delay-1">
        <div class="bg-white/10 border border-oro/30 rounded-2xl p-6 text-center backdrop-blur">
          <div class="font-display text-4xl text-oro font-bold"><?= (int)$conteggioNovita ?></div>
          <div class="text-avorio text-xs uppercase tracking-widest mt-2">Esposizioni</div>
        </div>
        <div class="bg-white/10 border border-oro/30 rounded-2xl p-6 text-center backdrop-blur">
          <div class="font-display text-4xl text-oro font-bold">News</div>
          <div class="text-avorio text-xs uppercase tracking-widest mt-2">Sempre aggiornate</div>
        </div>
      </div>
    </div>
  </div>
</section>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <section class="grid lg:grid-cols-3 gap-8 mb-16">
    <article class="lg:col-span-2 bg-white rounded-2xl shadow-xl overflow-hidden border border-avorio-dark">
      <div class="grid md:grid-cols-[0.9fr_1.1fr]">
        <picture>
          <source srcset="<?= SITE_URL ?>/img/foto/sala-museo.webp 640w, <?= SITE_URL ?>/img/foto/sala-museo@2x.webp 960w" sizes="(max-width: 768px) 100vw, 420px">
          <img src="<?= SITE_URL ?>/img/foto/sala-museo.webp" width="640" height="384" alt="Sala del Museo Storico Severi" class="w-full h-full object-cover min-h-[260px]" loading="eager" decoding="async" fetchpriority="high">
        </picture>
        <div class="p-8 md:p-10">
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Comunicazione</p>
          <h2 class="font-display text-3xl font-bold text-antracite mb-4">Nuove esperienze di visita e percorsi tematici</h2>
          <p class="text-gray-600 leading-relaxed mb-6">Il Museo Storico Severi aggiorna periodicamente la propria offerta con esposizioni, attività didattiche e servizi pensati per rendere la visita più accessibile, ordinata e coinvolgente.</p>
          <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= SITE_URL ?>/info.php" class="btn-oro inline-block px-6 py-3 rounded font-body text-sm uppercase tracking-wide text-center">Info e tariffe</a>
            <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline inline-block px-6 py-3 rounded font-body text-sm uppercase tracking-wide text-center">Vedi esposizioni</a>
          </div>
        </div>
      </div>
    </article>

    <aside class="bg-antracite rounded-2xl shadow-xl p-8 text-avorio border border-oro/20">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Avvisi rapidi</p>
      <h2 class="font-display text-2xl font-bold mb-6">Prima della visita</h2>
      <ul class="space-y-5 text-sm text-gray-300">
        <li><strong class="text-oro">01</strong> Prenotazione consigliata per gruppi e scuole.</li>
        <li><strong class="text-oro">02</strong> Riduzioni disponibili per studenti, bambini e senior.</li>
        <li><strong class="text-oro">03</strong> Servizi opzionali acquistabili insieme al biglietto.</li>
      </ul>
    </aside>
  </section>

  <section>
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Aggiornamenti</p>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-antracite">Ultime esposizioni</h2>
        <p class="text-gray-600 mt-2 max-w-2xl">Le mostre più recenti inserite nel calendario del museo.</p>
      </div>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="sm:inline-block btn-outline px-5 py-2 rounded text-sm font-body text-center">Tutte le esposizioni</a>
    </div>

    <?php if (empty($novita)): ?>
      <div class="bg-white rounded-2xl shadow p-10 text-center border border-avorio-dark">
        <h3 class="font-display text-2xl font-bold text-antracite mb-2">Nessuna novità disponibile</h3>
        <p class="text-gray-600">Verifica la connessione al database oppure inserisci nuove esposizioni dalla vista amministratore.</p>
      </div>
    <?php else: ?>
      <div class="expo-grid">
        <?php foreach ($novita as $esp): ?>
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
              <h3><?= clean($esp['titolo']) ?></h3>
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
              <a href="<?= SITE_URL ?>/prenota.php?id=<?= (int)$esp['id_esposizione'] ?>"
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
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
