<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Novità';

$novita = [];
$conteggioNovita = 0;

function colonnaEsposizioniEsiste(PDO $pdo, string $colonna): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM Esposizioni LIKE ?");
        $stmt->execute([$colonna]);
        return (bool) $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function emojiEsposizioneNovita(array $esposizione): string
{
    $emoji = trim((string)($esposizione['emoji'] ?? ''));
    return $emoji !== '' ? $emoji : '🏛️';
}

try {
    $pdo = getDB();
    $campiEsposizione = 'id_esposizione, titolo, descrizione, data_inizio, data_fine, stato';
    if (colonnaEsposizioniEsiste($pdo, 'emoji')) {
        $campiEsposizione .= ', emoji';
    }

    if (isAdmin()) {
        $stmt = $pdo->query("SELECT $campiEsposizione
                             FROM Esposizioni
                             ORDER BY data_inizio DESC
                             LIMIT 7");

        $stmtCount = $pdo->query("SELECT COUNT(*) AS totale FROM Esposizioni");
    } else {
        $stmt = $pdo->query("SELECT $campiEsposizione
                             FROM Esposizioni
                             WHERE stato <> 'Bozza'
                             ORDER BY data_inizio DESC
                             LIMIT 7");

        $stmtCount = $pdo->query("SELECT COUNT(*) AS totale
                                  FROM Esposizioni
                                  WHERE stato <> 'Bozza'");
    }

    $novita = $stmt->fetchAll();
    $conteggioNovita = (int) ($stmtCount->fetch()['totale'] ?? 0);
} catch (Exception $e) {
    $novita = [];
    $conteggioNovita = 0;
}

$primaNovita = $novita[0] ?? null;
$altreNovita = array_slice($novita, 1);

function badgeStatoClass(string $stato): string
{
    switch ($stato) {
        case 'Pubblicata':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'Bozza':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'Conclusa':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        case 'Annullata':
            return 'bg-red-100 text-red-800 border-red-200';
        default:
            return 'bg-avorio-dark text-antracite border-avorio-dark';
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Novità</span>
  </div>
</div>

<section class="relative bg-antracite overflow-hidden">
  <div class="absolute inset-0 opacity-10"
       style="background-image: radial-gradient(circle at 20% 20%, #C9A84C 0, transparent 28%), radial-gradient(circle at 80% 10%, #C9A84C 0, transparent 22%), repeating-linear-gradient(45deg, #C9A84C 0, #C9A84C 1px, transparent 0, transparent 44%); background-size: auto, auto, 22px 22px;"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20">
    <div class="grid lg:grid-cols-[1.2fr_0.8fr] gap-10 items-center">
      <div class="fade-up">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-3">Museo Storico Severi</p>
        <h1 class="font-display text-avorio text-4xl md:text-5xl font-bold leading-tight mb-5">Novità dal museo</h1>
        <p class="text-gray-300 leading-relaxed max-w-2xl">
          Scopri gli aggiornamenti più recenti, le esposizioni in arrivo e i percorsi tematici disponibili per visitatori, gruppi e scuole.
        </p>
      </div>

      <div class="grid grid-cols-2 gap-4 fade-up delay-1">
        <div class="bg-white/10 border border-oro/30 rounded-2xl p-6 text-center backdrop-blur">
          <div class="font-display text-4xl text-oro font-bold"><?= (int) $conteggioNovita ?></div>
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
        <div class="bg-antracite min-h-[260px] flex items-center justify-center p-10 relative overflow-hidden">
          <div class="absolute inset-0 opacity-20" style="background-image: repeating-linear-gradient(135deg, #C9A84C 0, #C9A84C 1px, transparent 0, transparent 46%); background-size: 24px 24px;"></div>
          <div class="relative text-center">
            <div class="text-7xl mb-4">🏛️</div>
            <div class="text-oro uppercase tracking-[0.25em] text-xs font-body">In evidenza</div>
          </div>
        </div>
        <div class="p-8 md:p-10">
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Comunicazione</p>
          <h2 class="font-display text-3xl font-bold text-antracite mb-4">Nuove esperienze di visita e percorsi tematici</h2>
          <p class="text-gray-500 leading-relaxed mb-6">
            Il Museo Storico Severi aggiorna periodicamente la propria offerta con esposizioni, attività didattiche e servizi pensati per rendere la visita più accessibile, ordinata e coinvolgente.
          </p>
          <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= SITE_URL ?>/info.php" class="btn-oro inline-block px-6 py-3 rounded font-body text-sm uppercase tracking-wide text-center">Info e tariffe</a>
            <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline inline-block px-6 py-3 rounded font-body text-sm uppercase tracking-wide text-center">Vedi esposizioni</a>
          </div>
        </div>
      </div>
    </article>

    <aside class="bg-antracite rounded-2xl shadow-xl p-8 text-avorio border border-oro/20">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Avvisi rapidi</p>
      <h3 class="font-display text-2xl font-bold mb-6">Prima della visita</h3>
      <ul class="space-y-5 text-sm text-gray-300">
        <li class="flex gap-3">
          <span class="text-oro">01</span>
          <span>Prenotazione consigliata per gruppi e scuole.</span>
        </li>
        <li class="flex gap-3">
          <span class="text-oro">02</span>
          <span>Riduzioni disponibili per studenti, bambini e senior.</span>
        </li>
        <li class="flex gap-3">
          <span class="text-oro">03</span>
          <span>Servizi opzionali acquistabili insieme al biglietto.</span>
        </li>
      </ul>
    </aside>
  </section>

  <section>
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
      <div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-1">Aggiornamenti</p>
        <h2 class="font-display text-3xl md:text-4xl font-bold text-antracite">Ultime esposizioni</h2>
        <p class="text-gray-500 mt-2 max-w-2xl">Le mostre più recenti inserite nel calendario del museo.</p>
      </div>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="sm:inline-block btn-outline px-5 py-2 rounded text-sm font-body text-center">Tutte le esposizioni</a>
    </div>

    <?php if (empty($novita)): ?>
      <div class="bg-white rounded-2xl shadow p-10 text-center border border-avorio-dark">
        <div class="text-5xl mb-4">📭</div>
        <h3 class="font-display text-2xl font-bold text-antracite mb-2">Nessuna novità disponibile</h3>
        <p class="text-gray-500">Verifica la connessione al database oppure inserisci nuove esposizioni dalla vista amministratore.</p>
      </div>
    <?php else: ?>

      <?php if ($primaNovita): ?>
        <article class="bg-white rounded-2xl shadow-xl overflow-hidden border border-avorio-dark mb-8 group">
          <div class="grid lg:grid-cols-[0.85fr_1.15fr]">
            <div class="bg-avorio-dark p-10 flex items-center justify-center relative overflow-hidden">
              <div class="absolute inset-0 opacity-40" style="background-image: radial-gradient(circle at 30% 30%, #C9A84C 0, transparent 20%), radial-gradient(circle at 70% 70%, #C9A84C 0, transparent 20%);"></div>
              <div class="relative text-center">
                <div class="text-8xl mb-4"><?= clean(emojiEsposizioneNovita($primaNovita)) ?></div>
                <span class="inline-flex items-center px-4 py-2 rounded-full border text-xs font-bold uppercase tracking-wide <?= badgeStatoClass($primaNovita['stato']) ?>">
                  <?= clean($primaNovita['stato']) ?>
                </span>
              </div>
            </div>
            <div class="p-8 md:p-10">
              <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ultima esposizione inserita</p>
              <h3 class="font-display text-3xl md:text-4xl font-bold text-antracite mb-4 group-hover:text-oro transition-colors">
                <?= clean($primaNovita['titolo']) ?>
              </h3>
              <p class="text-gray-500 leading-relaxed mb-6">
                <?= clean($primaNovita['descrizione'] ?? 'Scopri questa nuova esposizione del museo.') ?>
              </p>
              <div class="flex flex-wrap gap-3 mb-7 text-sm">
                <span class="bg-avorio px-4 py-2 rounded-full text-antracite border border-avorio-dark">
                  Dal <?= date('d/m/Y', strtotime($primaNovita['data_inizio'])) ?>
                </span>
                <span class="bg-avorio px-4 py-2 rounded-full text-antracite border border-avorio-dark">
                  Fino al <?= date('d/m/Y', strtotime($primaNovita['data_fine'])) ?>
                </span>
              </div>
              <?php if ($primaNovita['stato'] === 'Pubblicata'): ?>
                <a href="<?= SITE_URL ?>/prenota.php?id=<?= (int) $primaNovita['id_esposizione'] ?>" class="btn-oro inline-block px-7 py-3 rounded font-body text-sm uppercase tracking-wide">Prenota ora</a>
              <?php else: ?>
                <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline inline-block px-7 py-3 rounded font-body text-sm uppercase tracking-wide">Vedi il calendario</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endif; ?>

      <?php if (!empty($altreNovita)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($altreNovita as $idx => $n): ?>
            <article class="bg-white rounded-2xl shadow border border-avorio-dark hover:shadow-xl hover:-translate-y-1 transition-all overflow-hidden group">
              <div class="h-2 bg-oro"></div>
              <div class="p-7">
                <div class="flex items-center justify-between gap-3 mb-5">
                  <div class="w-12 h-12 rounded-full bg-avorio-dark flex items-center justify-center text-2xl">
                    <?= clean(emojiEsposizioneNovita($n)) ?>
                  </div>
                  <span class="inline-flex items-center px-3 py-1 rounded-full border text-[11px] font-bold uppercase tracking-wide <?= badgeStatoClass($n['stato']) ?>">
                    <?= clean($n['stato']) ?>
                  </span>
                </div>

                <h3 class="font-display text-xl font-bold text-antracite mb-3 group-hover:text-oro transition-colors">
                  <?= clean($n['titolo']) ?>
                </h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-5 line-clamp-4">
                  <?= clean($n['descrizione'] ?? '') ?>
                </p>

                <div class="border-t border-avorio-dark pt-4 flex items-center justify-between gap-3 text-xs text-gray-500">
                  <span><?= date('d/m/Y', strtotime($n['data_inizio'])) ?></span>
                  <span class="text-oro">→</span>
                  <span><?= date('d/m/Y', strtotime($n['data_fine'])) ?></span>
                </div>

                <?php if ($n['stato'] === 'Pubblicata'): ?>
                  <a href="<?= SITE_URL ?>/prenota.php?id=<?= (int) $n['id_esposizione'] ?>" class="mt-5 inline-block text-oro text-xs font-bold uppercase tracking-wide hover:underline">
                    Prenota →
                  </a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
