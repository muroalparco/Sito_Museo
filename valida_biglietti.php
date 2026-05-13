<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireLogin();

if (!isOperatore()) {
    http_response_code(403);
    $pageTitle = 'Accesso negato';
    include __DIR__ . '/header.php';
    ?>
    <main class="flex-1 flex items-center justify-center py-12 sm:py-16 px-4">
      <section class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden text-center">
        <div class="bg-antracite px-8 py-8">
          <div class="text-5xl mb-4">⛔</div>
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area riservata</p>
          <h1 class="font-display text-avorio text-3xl font-bold">Accesso negato</h1>
        </div>
        <div class="p-8">
          <p class="text-gray-600 text-sm leading-relaxed mb-6">
            Questa sezione è riservata agli operatori.
          </p>
          <a href="<?= SITE_URL ?>/index.php" class="btn-outline px-6 py-3 rounded inline-block text-sm uppercase tracking-wide">Torna alla home</a>
        </div>
      </section>
    </main>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}

$pageTitle = 'Valida biglietti';
$pdo = getDB();
$errore = '';
$successo = '';
$biglietto = null;
$codice = strtoupper(trim($_POST['codice'] ?? $_GET['codice'] ?? ''));
$azione = $_POST['azione'] ?? '';

function cercaBigliettoPerCodice(PDO $pdo, string $codice): ?array {
    $stmt = $pdo->prepare("\n        SELECT\n            b.*,\n            o.codice_recupero,\n            o.nome_cliente,\n            o.email_cliente,\n            cr.nome AS categoria,\n            f.data AS data_fascia,\n            f.ora_ingresso,\n            e.titolo AS esposizione\n        FROM Biglietti b\n        INNER JOIN Ordini o ON o.id_ordine = b.id_ordine\n        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n        WHERE b.codice_univoco = ?\n        LIMIT 1\n    ");
    $stmt->execute([$codice]);
    $ticket = $stmt->fetch();

    return $ticket ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } elseif ($codice === '') {
        $errore = 'Inserisci il numero del ticket.';
    } else {
        try {
            $biglietto = cercaBigliettoPerCodice($pdo, $codice);

            if (!$biglietto) {
                $errore = 'Nessun biglietto trovato con questo numero ticket.';
            } elseif ($azione === 'valida') {
                if ($biglietto['stato'] === 'Utilizzato') {
                    $errore = 'Questo biglietto è già stato usato.';
                } elseif ($biglietto['stato'] === 'Annullato') {
                    $errore = 'Questo biglietto è annullato e non può essere validato.';
                } elseif ($biglietto['stato'] === 'Non pagato') {
                    $errore = 'Questo biglietto non è ancora pagato e non può essere validato.';
                } else {
                    $stmt = $pdo->prepare("\n                        UPDATE Biglietti\n                        SET stato = 'Utilizzato', data_utilizzo = NOW()\n                        WHERE id_biglietto = ? AND stato = 'Valido'\n                        LIMIT 1\n                    ");
                    $stmt->execute([(int) $biglietto['id_biglietto']]);

                    if ($stmt->rowCount() > 0) {
                        $successo = 'Biglietto validato correttamente. Ora risulta utilizzato.';
                    } else {
                        $errore = 'Non è stato possibile validare il biglietto. Potrebbe essere già stato usato.';
                    }

                    $biglietto = cercaBigliettoPerCodice($pdo, $codice);
                }
            }
        } catch (Exception $e) {
            $errore = 'Errore durante la ricerca o la validazione del biglietto.';
        }
    }
} elseif ($codice !== '') {
    try {
        $biglietto = cercaBigliettoPerCodice($pdo, $codice);
        if (!$biglietto) {
            $errore = 'Nessun biglietto trovato con questo numero ticket.';
        }
    } catch (Exception $e) {
        $errore = 'Errore durante la ricerca del biglietto.';
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Valida biglietti</span>
  </div>
</div>

<main class="flex-1 py-12 sm:py-16 px-4 bg-avorio">
  <div class="max-w-5xl mx-auto space-y-8">
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden fade-up">
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="text-5xl mb-4">✅</div>
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area operatori</p>
        <h1 class="font-display text-avorio text-3xl font-bold">Valida biglietti</h1>
      </div>

      <div class="p-8">
        <?php if ($errore): ?>
          <div class="alert-error p-4 rounded mb-6 text-sm">⚠️ <?= clean($errore) ?></div>
        <?php endif; ?>

        <?php if ($successo): ?>
          <div class="bg-green-100 border border-green-200 text-green-800 p-4 rounded mb-6 text-sm">✅ <?= clean($successo) ?></div>
        <?php endif; ?>

        <p class="text-gray-600 text-sm leading-relaxed mb-6">
          Inserisci il numero del ticket stampato sul biglietto. Non inserire il codice ordine.
        </p>

        <form method="POST" class="grid md:grid-cols-[1fr_auto] gap-4 items-end">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="azione" value="cerca">
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Numero ticket</label>
            <input type="text" name="codice" value="<?= clean($codice) ?>" placeholder="TKT-XXXXXXXX" required
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm uppercase focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>
          <button type="submit" class="btn-oro px-8 py-3 rounded-lg font-body text-sm uppercase tracking-widest">
            Cerca ticket
          </button>
        </form>
      </div>
    </section>

    <?php if ($biglietto): ?>
      <?php
        $stato = $biglietto['stato'];
        $totaleTicket = (float) $biglietto['prezzo_lordo'] - (float) $biglietto['sconto_applicato'];
        $badgeClass = 'bg-gray-100 text-gray-700';
        if ($stato === 'Valido') {
            $badgeClass = 'bg-green-100 text-green-800';
        } elseif ($stato === 'Utilizzato') {
            $badgeClass = 'bg-blue-100 text-blue-800';
        } elseif ($stato === 'Annullato') {
            $badgeClass = 'bg-red-100 text-red-800';
        } elseif ($stato === 'Non pagato') {
            $badgeClass = 'bg-yellow-100 text-yellow-800';
        }
      ?>
      <section class="bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
        <div class="h-2 bg-oro"></div>
        <div class="p-6 md:p-8">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 mb-8">
            <div>
              <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Numero ticket</p>
              <h2 class="font-display text-3xl font-bold text-antracite mb-2"><?= clean($biglietto['codice_univoco']) ?></h2>
              <p class="text-sm text-gray-600">
                Ordine: <strong><?= clean($biglietto['codice_recupero']) ?></strong>
              </p>
            </div>
            <div class="md:text-right">
              <span class="inline-block px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide <?= $badgeClass ?>">
                <?= clean($stato) ?>
              </span>
              <?php if (!empty($biglietto['data_utilizzo'])): ?>
                <p class="text-xs text-gray-500 mt-2">
                  Usato il <?= date('d/m/Y H:i', strtotime($biglietto['data_utilizzo'])) ?>
                </p>
              <?php endif; ?>
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-6 text-sm text-gray-600">
            <div class="space-y-3">
              <p><strong>Acquirente:</strong> <?= clean($biglietto['nome_cliente'] ?? '—') ?></p>
              <p><strong>Email:</strong> <?= clean($biglietto['email_cliente'] ?? '—') ?></p>
              <p><strong>Tipo:</strong> <?= $biglietto['tipo'] === 'base' ? 'Ingresso Museo' : 'Esposizione' ?></p>
              <p><strong>Categoria:</strong> <?= clean($biglietto['categoria'] ?? '—') ?></p>
            </div>
            <div class="space-y-3">
              <p><strong>Percorso:</strong> <?= clean($biglietto['esposizione'] ?? 'Museo Storico Severi') ?></p>
              <p>
                <strong>Data validità:</strong>
                <?= date('d/m/Y', strtotime($biglietto['data_validita'])) ?>
                <?= !empty($biglietto['ora_ingresso']) ? ' alle ' . clean(substr($biglietto['ora_ingresso'], 0, 5)) : '' ?>
              </p>
              <p><strong>Prezzo:</strong> € <?= number_format($totaleTicket, 2, ',', '.') ?></p>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-avorio-dark flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <?php if ($stato === 'Valido'): ?>
              <p class="text-sm text-gray-600">Il biglietto è valido e può essere segnato come utilizzato.</p>
              <form method="POST" onsubmit="return confirm('Confermi la validazione di questo biglietto?');">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="azione" value="valida">
                <input type="hidden" name="codice" value="<?= clean($biglietto['codice_univoco']) ?>">
                <button type="submit" class="btn-oro px-8 py-3 rounded-lg font-body text-sm uppercase tracking-widest">
                  Valida
                </button>
              </form>
            <?php elseif ($stato === 'Utilizzato'): ?>
              <p class="text-sm text-blue-800 bg-blue-100 border border-blue-200 rounded-lg p-4 w-full">
                Questo biglietto è già stato usato e non può essere validato di nuovo.
              </p>
            <?php elseif ($stato === 'Non pagato'): ?>
              <p class="text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-lg p-4 w-full">
                Questo biglietto non è ancora pagato. Deve essere saldato in cassa prima di diventare valido.
              </p>
            <?php else: ?>
              <p class="text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg p-4 w-full">
                Questo biglietto è annullato e non può essere validato.
              </p>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
