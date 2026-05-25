<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$pageTitle = 'I miei ordini';
$pdo = getDB();
$successMsg = '';
$errorMsg = '';

$haRichiestaRimborso = colonnaEsiste($pdo, 'Ordini', 'richiesta_rimborso');
$haStatoRimborso = colonnaEsiste($pdo, 'Ordini', 'stato_rimborso');
$haMotivoRimborso = colonnaEsiste($pdo, 'Ordini', 'motivo_rimborso');
$haDataRimborso = colonnaEsiste($pdo, 'Ordini', 'data_richiesta_rimborso');
$rimborsoAttivo = ($haRichiestaRimborso && $haStatoRimborso && $haMotivoRimborso);
// Schema reale atteso su Altervista: richiesta_rimborso, stato_rimborso, motivo_rimborso, data_richiesta_rimborso, data_esito_rimborso.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'richiedi_rimborso') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Token di sicurezza non valido.';
    } else {
        $idOrdine = (int)($_POST['id_ordine'] ?? 0);
        $motivo = trim($_POST['motivo_rimborso'] ?? '');
        try {
            if (!$rimborsoAttivo) {
                throw new RuntimeException('La gestione rimborsi non è attiva: servono le colonne richiesta_rimborso, stato_rimborso e motivo_rimborso nella tabella Ordini.');
            }
            if ($motivo === '') {
                throw new RuntimeException('Inserisci il motivo della richiesta di rimborso.');
            }
            $stmtCheck = $pdo->prepare("SELECT id_ordine FROM Ordini WHERE id_ordine = ? AND id_utente = ? AND stato_pagamento = 'Pagato' LIMIT 1");
            $stmtCheck->execute([$idOrdine, $_SESSION['utente_id']]);
            if (!$stmtCheck->fetch()) {
                throw new RuntimeException('Ordine non rimborsabile o non trovato.');
            }

            $stmtUsati = $pdo->prepare("SELECT COUNT(*) FROM Biglietti WHERE id_ordine = ? AND stato = 'Utilizzato'");
            $stmtUsati->execute([$idOrdine]);
            if ((int)$stmtUsati->fetchColumn() > 0) {
                throw new RuntimeException('Non è possibile richiedere il rimborso: uno o più biglietti di questo ordine sono già stati utilizzati.');
            }

            $campi = ['motivo_rimborso = ?', "stato_rimborso = 'Richiesto'"];
            $valori = [$motivo];
            if ($haRichiestaRimborso) {
                $campi[] = 'richiesta_rimborso = 1';
            }
            if ($haDataRimborso) {
                $campi[] = 'data_richiesta_rimborso = NOW()';
            }
            $valori[] = $idOrdine;
            $valori[] = $_SESSION['utente_id'];
            $stmtRefund = $pdo->prepare('UPDATE Ordini SET ' . implode(', ', $campi) . ' WHERE id_ordine = ? AND id_utente = ?');
            $stmtRefund->execute($valori);
            $successMsg = 'Richiesta di rimborso inviata all’amministrazione. Ti chiediamo di attendere qualche giorno per la verifica e l’esito della richiesta.';
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
        }
    }
}

$selectRimborso = $rimborsoAttivo
    ? (($haRichiestaRimborso ? "o.richiesta_rimborso" : "CASE WHEN COALESCE(o.stato_rimborso,'Nessuno') <> 'Nessuno' THEN 1 ELSE 0 END") . " AS richiesta_rimborso, COALESCE(o.stato_rimborso,'Nessuno') AS stato_rimborso,")
    : "0 AS richiesta_rimborso, 'Nessuno' AS stato_rimborso,";
$groupRimborso = $rimborsoAttivo ? ', o.stato_rimborso' . ($haRichiestaRimborso ? ', o.richiesta_rimborso' : '') : '';

$stmt = $pdo->prepare(" 
    SELECT 
        o.id_ordine,
        o.codice_recupero,
        o.data_acquisto,
        o.importo_totale,
        o.stato_pagamento,
        {$selectRimborso}
        COUNT(b.id_biglietto) AS numero_biglietti,
        SUM(CASE WHEN b.stato = 'Utilizzato' THEN 1 ELSE 0 END) AS biglietti_usati,
        GROUP_CONCAT(DISTINCT e.titolo SEPARATOR ', ') AS esposizioni
    FROM Ordini o
    LEFT JOIN Biglietti b ON b.id_ordine = o.id_ordine
    LEFT JOIN Fasce_Orarie f ON b.id_fascia = f.id_fascia
    LEFT JOIN Esposizioni e ON f.id_esposizione = e.id_esposizione
    WHERE o.id_utente = ?
    GROUP BY o.id_ordine, o.codice_recupero, o.data_acquisto, o.importo_totale, o.stato_pagamento{$groupRimborso}
    ORDER BY o.data_acquisto DESC
");

$stmt->execute([$_SESSION['utente_id']]);
$ordini = $stmt->fetchAll();
$ordineRimborso = null;
$idRimborsoRichiesto = isset($_GET['rimborso']) ? (int)$_GET['rimborso'] : 0;

if ($idRimborsoRichiesto > 0) {
    foreach ($ordini as $ordineCorrente) {
        if (
            (int)$ordineCorrente['id_ordine'] === $idRimborsoRichiesto
            && ($ordineCorrente['stato_pagamento'] ?? '') === 'Pagato'
            && (string)($ordineCorrente['stato_rimborso'] ?? 'Nessuno') === 'Nessuno'
            && (int)($ordineCorrente['biglietti_usati'] ?? 0) === 0
        ) {
            $ordineRimborso = $ordineCorrente;
            break;
        }
    }

    if (!$ordineRimborso && !$errorMsg && !$successMsg) {
        $errorMsg = 'Questo ordine non può essere rimborsato: potrebbe essere già stato richiesto il rimborso oppure uno o più biglietti risultano utilizzati.';
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">I miei ordini</span>
  </div>
</div>

<main class="flex-1 bg-avorio py-10 sm:py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <?php if ($successMsg): ?><div class="alert-success floating-alert p-4 rounded mb-6 text-sm font-body" role="status"><?= clean($successMsg) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert-error floating-alert p-4 rounded mb-6 text-sm font-body" role="alert"><?= clean($errorMsg) ?></div><?php endif; ?>

    <section class="mb-8 sm:mb-10">
      <h1 class="font-display text-3xl sm:text-4xl md:text-5xl font-bold text-antracite mb-4">
        I miei ordini
      </h1>
      <p class="text-gray-600 font-body text-base sm:text-lg max-w-2xl">
        Consulta lo storico dei tuoi acquisti e delle prenotazioni effettuate al Museo Storico Severi.
      </p>
    </section>

    <?php if ($ordineRimborso): ?>
      <section id="richiesta-rimborso" class="refund-panel bg-white rounded-2xl shadow-md border border-avorio-dark p-5 sm:p-6 md:p-8 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
          <div class="min-w-0">
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Richiesta rimborso</p>
            <h2 class="font-display text-2xl font-semibold text-antracite mb-2">
              Ordine #<?= clean((string)$ordineRimborso['id_ordine']) ?>
            </h2>
            <p class="text-sm text-gray-600">
              <?= !empty($ordineRimborso['esposizioni']) ? clean($ordineRimborso['esposizioni']) : 'Biglietto museo' ?>
            </p>
          </div>

          <form method="POST" action="<?= SITE_URL ?>/ordini.php" class="refund-panel__form">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="richiedi_rimborso">
            <input type="hidden" name="id_ordine" value="<?= (int)$ordineRimborso['id_ordine'] ?>">
            <label class="block text-sm font-body font-bold text-antracite mb-2" for="motivo-rimborso">Motivo del rimborso</label>
            <textarea id="motivo-rimborso" name="motivo_rimborso" rows="4" required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro" placeholder="Scrivi qui il motivo della richiesta"></textarea>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center mt-4">
              <button type="submit" class="btn-oro px-6 py-3 rounded text-sm text-center">Invia richiesta</button>
              <a href="<?= SITE_URL ?>/ordini.php" class="btn-outline px-6 py-3 rounded text-sm text-center">Annulla</a>
            </div>
          </form>
        </div>
      </section>
    <?php endif; ?>

    <?php if (empty($ordini)): ?>
      <div class="bg-white rounded-2xl shadow-md border border-avorio-dark p-6 sm:p-10 text-center">
        <div class="text-5xl mb-4"></div>
        <h2 class="font-display text-2xl font-semibold text-antracite mb-4">
          Nessun ordine presente
        </h2>
        <p class="text-gray-600 mb-6">
          Non hai ancora effettuato prenotazioni o acquisti.
        </p>
        <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro inline-block w-full sm:w-auto px-6 py-3 rounded-xl transition text-center">
          Scopri le esposizioni
        </a>
      </div>
    <?php else: ?>
      <div class="space-y-6">
        <?php foreach ($ordini as $ordine): ?>
          <article class="bg-white rounded-2xl shadow-md border border-avorio-dark p-5 sm:p-6 md:p-8 hover:shadow-lg transition">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                  <span class="bg-acciaio text-white px-3 py-1 rounded-full text-sm font-semibold">
                    Ordine #<?= clean((string)$ordine['id_ordine']) ?>
                  </span>
                  <span class="bg-oro/20 text-antracite px-3 py-1 rounded-full text-sm">
                    <?= clean((string)$ordine['numero_biglietti']) ?> biglietto/i
                  </span>
                  <?php
                    $ordineRimborsato = strcasecmp((string)($ordine['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
                    $statoPagamento = $ordineRimborsato ? 'Rimborsato' : ($ordine['stato_pagamento'] ?? 'Pagato');
                    $statoClass = $ordineRimborsato
                        ? 'bg-red-100 text-red-800'
                        : ($statoPagamento === 'Pagato' ? 'bg-green-100 text-green-800' : ($statoPagamento === 'Non pagato' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700'));
                  ?>
                  <span class="<?= $statoClass ?> px-3 py-1 rounded-full text-sm">
                    <?= clean($statoPagamento) ?>
                  </span>
                </div>

                <h2 class="font-display text-xl sm:text-2xl font-semibold text-antracite mb-2 break-words">
                  <?= !empty($ordine['esposizioni']) ? clean($ordine['esposizioni']) : 'Biglietto museo' ?>
                </h2>

                <p class="text-gray-600 text-sm sm:text-base">
                  Data acquisto:
                  <strong><?= date('d/m/Y H:i', strtotime($ordine['data_acquisto'])) ?></strong>
                </p>
                <p class="text-gray-500 text-sm mt-1">
                  Codice recupero: <strong><?= clean($ordine['codice_recupero']) ?></strong>
                </p>
              </div>

              <div class="md:text-right shrink-0">
                <p class="text-sm uppercase tracking-widest text-gray-500 mb-1">Totale</p>
                <p class="font-display text-3xl font-bold text-oro mb-4">
                  € <?= number_format((float)$ordine['importo_totale'], 2, ',', '.') ?>
                </p>
                <div class="flex flex-col sm:flex-row md:flex-col gap-2 md:items-end">
                  <a href="<?= SITE_URL ?>/biglietti.php?codice=<?= urlencode($ordine['codice_recupero']) ?>" class="btn-outline inline-block px-5 py-2 rounded text-sm text-center">
                    Vedi biglietti
                  </a>
                  <?php if ($ordineRimborsato): ?>
                    <span class="inline-block px-4 py-2 rounded bg-red-50 text-xs font-bold text-red-700">Ordine rimborsato: biglietti non utilizzabili</span>
                  <?php elseif (($ordine['stato_pagamento'] ?? '') === 'Non pagato'): ?>
                    <a href="<?= SITE_URL ?>/pagamento.php?ordine=<?= (int)$ordine['id_ordine'] ?>" class="btn-oro inline-block px-5 py-2 rounded text-sm text-center">
                      Paga
                    </a>
                  <?php elseif (($ordine['stato_pagamento'] ?? '') === 'Pagato' && (string)($ordine['stato_rimborso'] ?? 'Nessuno') === 'Nessuno' && (int)($ordine['biglietti_usati'] ?? 0) === 0): ?>
                    <a href="<?= SITE_URL ?>/ordini.php?rimborso=<?= (int)$ordine['id_ordine'] ?>#richiesta-rimborso" class="btn-outline px-4 py-2 rounded text-xs text-center">Richiedi rimborso</a>
                  <?php elseif (($ordine['stato_pagamento'] ?? '') === 'Pagato' && (int)($ordine['biglietti_usati'] ?? 0) > 0): ?>
                    <span class="inline-block px-4 py-2 rounded bg-avorio text-xs font-bold text-antracite">Rimborso non disponibile: biglietti già utilizzati</span>
                  <?php elseif ((string)($ordine['stato_rimborso'] ?? 'Nessuno') !== 'Nessuno'): ?>
                    <span class="inline-block px-4 py-2 rounded bg-avorio text-xs font-bold text-antracite">Rimborso: <?= clean($ordine['stato_rimborso'] ?? 'Richiesto') ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
