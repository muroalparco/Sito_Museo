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
          <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area riservata</p>
          <h1 class="font-display text-avorio text-3xl font-bold">Accesso negato</h1>
        </div>
        <div class="p-8">
          <p class="text-gray-600 text-sm leading-relaxed mb-6">Questa sezione è riservata agli operatori.</p>
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
$ordine = null;
$bigliettiOrdine = [];
$codice = strtoupper(trim($_POST['codice'] ?? $_GET['codice'] ?? ''));
$azione = $_POST['azione'] ?? 'cerca';

function cercaBigliettoPerCodice(PDO $pdo, string $codice): ?array {
    $stmt = $pdo->prepare("\n        SELECT\n            b.*,\n            o.codice_recupero,\n            o.nome_cliente,\n            o.email_cliente,\n            o.stato_pagamento,\n            COALESCE(o.stato_rimborso, 'Nessuno') AS stato_rimborso,\n            cr.nome AS categoria,\n            f.data AS data_fascia,\n            f.ora_ingresso,\n            e.titolo AS esposizione\n        FROM Biglietti b\n        INNER JOIN Ordini o ON o.id_ordine = b.id_ordine\n        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n        WHERE b.codice_univoco = ?\n        LIMIT 1\n    ");
    $stmt->execute([$codice]);
    $ticket = $stmt->fetch();
    return $ticket ?: null;
}

function cercaOrdinePerCodice(PDO $pdo, string $codice): ?array {
    $stmt = $pdo->prepare("\n        SELECT id_ordine, codice_recupero, nome_cliente, email_cliente, data_acquisto, importo_totale, stato_pagamento, COALESCE(stato_rimborso, 'Nessuno') AS stato_rimborso\n        FROM Ordini\n        WHERE codice_recupero = ? OR id_ordine = ?\n        LIMIT 1\n    ");
    $idNumerico = ctype_digit($codice) ? (int)$codice : 0;
    $stmt->execute([$codice, $idNumerico]);
    $ordine = $stmt->fetch();
    return $ordine ?: null;
}

function bigliettiOrdine(PDO $pdo, int $idOrdine): array {
    $stmt = $pdo->prepare("\n        SELECT\n            b.*,\n            o.codice_recupero,\n            o.nome_cliente,\n            o.email_cliente,\n            o.stato_pagamento,\n            COALESCE(o.stato_rimborso, 'Nessuno') AS stato_rimborso,\n            cr.nome AS categoria,\n            f.data AS data_fascia,\n            f.ora_ingresso,\n            e.titolo AS esposizione\n        FROM Biglietti b\n        INNER JOIN Ordini o ON o.id_ordine = b.id_ordine\n        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria\n        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia\n        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione\n        WHERE b.id_ordine = ?\n        ORDER BY b.id_biglietto ASC\n    ");
    $stmt->execute([$idOrdine]);
    return $stmt->fetchAll();
}

function ordineRimborsato(array $riga): bool {
    return strcasecmp((string)($riga['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
}

function statoBigliettoVisuale(array $b): string {
    return ordineRimborsato($b) ? 'Rimborsato' : (string)($b['stato'] ?? '—');
}

function puoValidare(array $b): bool {
    return !ordineRimborsato($b)
        && ($b['stato'] ?? '') === 'Valido'
        && ($b['stato_pagamento'] ?? '') === 'Pagato';
}

function caricaDaCodice(PDO $pdo, string $codice, ?array &$biglietto, ?array &$ordine, array &$bigliettiOrdine, string &$errore): void {
    $biglietto = null;
    $ordine = null;
    $bigliettiOrdine = [];

    if ($codice === '') {
        $errore = 'Inserisci il codice ticket oppure il codice ordine.';
        return;
    }

    $ticket = cercaBigliettoPerCodice($pdo, $codice);
    if ($ticket) {
        $biglietto = $ticket;
        return;
    }

    $ordineTrovato = cercaOrdinePerCodice($pdo, $codice);
    if ($ordineTrovato) {
        $ordine = $ordineTrovato;
        $bigliettiOrdine = bigliettiOrdine($pdo, (int)$ordineTrovato['id_ordine']);
        return;
    }

    $errore = 'Nessun biglietto o ordine trovato con questo codice.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        try {
            if ($azione === 'valida_ticket') {
                $idBiglietto = (int)($_POST['id_biglietto'] ?? 0);
                $stmt = $pdo->prepare("\n                    SELECT b.id_biglietto, b.codice_univoco, b.stato, o.stato_pagamento\n                    FROM Biglietti b\n                    INNER JOIN Ordini o ON o.id_ordine = b.id_ordine\n                    WHERE b.id_biglietto = ?\n                    LIMIT 1\n                ");
                $stmt->execute([$idBiglietto]);
                $ticket = $stmt->fetch();
                if (!$ticket) {
                    throw new RuntimeException('Biglietto non trovato.');
                }
                if (!puoValidare($ticket)) {
                    if (ordineRimborsato($ticket)) {
                        throw new RuntimeException('Questo biglietto appartiene a un ordine rimborsato e non può essere validato.');
                    }
                    throw new RuntimeException('Questo biglietto non può essere validato: deve essere pagato e ancora valido.');
                }
                $stmt = $pdo->prepare("UPDATE Biglietti SET stato = 'Utilizzato', data_utilizzo = NOW() WHERE id_biglietto = ? AND stato = 'Valido' LIMIT 1");
                $stmt->execute([$idBiglietto]);
                $successo = $stmt->rowCount() > 0 ? 'Biglietto validato correttamente.' : 'Biglietto non validato: potrebbe essere già stato usato.';
                $codice = strtoupper((string)$ticket['codice_univoco']);
                caricaDaCodice($pdo, $codice, $biglietto, $ordine, $bigliettiOrdine, $errore);
            } elseif ($azione === 'valida_tutti') {
                $idOrdine = (int)($_POST['id_ordine'] ?? 0);
                $ordine = $idOrdine > 0 ? cercaOrdinePerCodice($pdo, (string)$idOrdine) : null;
                if (!$ordine) {
                    throw new RuntimeException('Ordine non trovato.');
                }
                if (ordineRimborsato($ordine)) {
                    throw new RuntimeException('Questo ordine è stato rimborsato: i biglietti non sono più utilizzabili.');
                }
                if (($ordine['stato_pagamento'] ?? '') !== 'Pagato') {
                    throw new RuntimeException('L’ordine non è ancora pagato: non è possibile validare i biglietti.');
                }
                $stmt = $pdo->prepare("UPDATE Biglietti SET stato = 'Utilizzato', data_utilizzo = NOW() WHERE id_ordine = ? AND stato = 'Valido'");
                $stmt->execute([$idOrdine]);
                $validati = $stmt->rowCount();
                $successo = $validati > 0 ? 'Validati ' . $validati . ' biglietti dell’ordine.' : 'Nessun biglietto valido da validare in questo ordine.';
                $codice = $ordine['codice_recupero'];
                $bigliettiOrdine = bigliettiOrdine($pdo, $idOrdine);
            } else {
                caricaDaCodice($pdo, $codice, $biglietto, $ordine, $bigliettiOrdine, $errore);
            }
        } catch (Throwable $e) {
            $errore = $e->getMessage();
        }
    }
} elseif ($codice !== '') {
    try {
        caricaDaCodice($pdo, $codice, $biglietto, $ordine, $bigliettiOrdine, $errore);
    } catch (Throwable $e) {
        $errore = 'Errore durante la ricerca.';
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
  <div class="max-w-6xl mx-auto space-y-8">
    <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden fade-up">
      <div class="bg-antracite px-8 py-8 text-center">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area operatori</p>
        <h1 class="font-display text-avorio text-3xl font-bold">Valida biglietti</h1>
      </div>

      <div class="p-8">
        <?php if ($errore): ?>
          <div class="alert-error floating-alert p-4 rounded mb-6 text-sm" role="alert">⚠️ <?= clean($errore) ?></div>
        <?php endif; ?>

        <?php if ($successo): ?>
          <div class="floating-alert bg-green-100 border border-green-200 text-green-800 p-4 rounded mb-6 text-sm" role="status">✅ <?= clean($successo) ?></div>
        <?php endif; ?>

        <p class="text-gray-600 text-sm leading-relaxed mb-6">
          Inserisci il codice di un singolo ticket oppure il codice ordine. Se inserisci un ordine, vedrai tutti i biglietti collegati e potrai validarli uno alla volta o tutti insieme.
        </p>

        <form method="POST" id="ticketSearchForm" class="grid md:grid-cols-[1fr_auto] gap-4 items-end">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="azione" value="cerca">
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Codice ticket o codice ordine</label>
            <input type="text" id="codiceTicketInput" name="codice" value="<?= clean($codice) ?>" placeholder="TKT-XXXXXXXX oppure ORD-XXXXXXXX" required
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm uppercase focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>
          <button type="submit" class="btn-oro px-8 py-3 rounded-lg font-body text-sm uppercase tracking-widest">Cerca</button>
        </form>

        <div class="mt-6 rounded-2xl border border-avorio-dark bg-avorio p-5">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
              <h2 class="font-display text-xl font-bold text-antracite">Scansione QR con fotocamera</h2>
              <p class="text-sm text-gray-600 mt-1">Inquadra il QR code del biglietto: il codice verrà letto automaticamente.</p>
            </div>
            <button type="button" id="startQrScan" class="btn-outline px-5 py-2 rounded text-sm">Apri fotocamera</button>
            <button type="button" id="stopQrScan" class="hidden btn-outline px-5 py-2 rounded text-sm">Chiudi fotocamera</button>
          </div>
          <div id="qrReader" class="hidden w-full max-w-md overflow-hidden rounded-xl border border-avorio-dark bg-white"></div>
          <p id="qrScanMsg" class="text-xs text-gray-500 mt-3">Se la scansione automatica non funziona, puoi inserire manualmente il codice ticket o il codice ordine.</p>
        </div>
      </div>
    </section>

    <?php if ($ordine): ?>
      <section class="bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
        <div class="h-2 bg-oro"></div>
        <div class="p-6 md:p-8">
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6 mb-6">
            <div>
              <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Ordine trovato</p>
              <h2 class="font-display text-3xl font-bold text-antracite mb-2"><?= clean($ordine['codice_recupero']) ?></h2>
              <p class="text-sm text-gray-600">Acquirente: <strong><?= clean($ordine['nome_cliente'] ?? '—') ?></strong> · <?= clean($ordine['email_cliente'] ?? '—') ?></p>
            </div>
            <div class="md:text-right">
              <span class="inline-block px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide <?= ($ordine['stato_pagamento'] ?? '') === 'Pagato' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                <?= clean($ordine['stato_pagamento'] ?? '—') ?>
              </span>
              <?php if (ordineRimborsato($ordine)): ?>
                <div class="mt-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3 text-left">
                  Ordine rimborsato: i biglietti restano nello storico ma non possono più essere validati.
                </div>
              <?php endif; ?>
              <?php $validabili = array_filter($bigliettiOrdine, 'puoValidare'); ?>
              <?php if (!empty($validabili)): ?>
                <form method="POST" class="mt-4" onsubmit="return confirm('Confermi la validazione di tutti i biglietti validi di questo ordine?');">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="azione" value="valida_tutti">
                  <input type="hidden" name="id_ordine" value="<?= (int)$ordine['id_ordine'] ?>">
                  <button type="submit" class="btn-oro px-5 py-2 rounded text-xs uppercase tracking-wide">Valida tutti</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-avorio-dark text-left text-gray-500 uppercase text-xs tracking-widest">
                  <th class="py-3 pr-4">Ticket</th>
                  <th class="py-3 pr-4">Categoria</th>
                  <th class="py-3 pr-4">Percorso</th>
                  <th class="py-3 pr-4">Stato</th>
                  <th class="py-3 pr-4 text-right">Azione</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-avorio-dark">
                <?php foreach ($bigliettiOrdine as $b): ?>
                  <tr>
                    <td class="py-4 pr-4 font-bold text-antracite whitespace-nowrap"><?= clean($b['codice_univoco']) ?></td>
                    <td class="py-4 pr-4 text-gray-600"><?= clean($b['categoria'] ?? '—') ?></td>
                    <td class="py-4 pr-4 text-gray-600"><?= clean($b['esposizione'] ?? 'Museo Storico Severi') ?></td>
                    <td class="py-4 pr-4 text-gray-600"><?= clean(statoBigliettoVisuale($b)) ?></td>
                    <td class="py-4 pr-4 text-right">
                      <?php if (puoValidare($b)): ?>
                        <form method="POST" onsubmit="return confirm('Validare questo biglietto?');">
                          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                          <input type="hidden" name="azione" value="valida_ticket">
                          <input type="hidden" name="id_biglietto" value="<?= (int)$b['id_biglietto'] ?>">
                          <button type="submit" class="btn-outline px-4 py-2 rounded text-xs uppercase tracking-wide">Valida</button>
                        </form>
                      <?php else: ?>
                        <span class="text-xs text-gray-500">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($biglietto): ?>
      <?php
        $stato = statoBigliettoVisuale($biglietto);
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
              <p class="text-sm text-gray-600">Ordine: <strong><?= clean($biglietto['codice_recupero']) ?></strong></p>
            </div>
            <div class="md:text-right">
              <span class="inline-block px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide <?= $badgeClass ?>"><?= clean($stato) ?></span>
              <?php if (!empty($biglietto['data_utilizzo'])): ?>
                <p class="text-xs text-gray-500 mt-2">Usato il <?= date('d/m/Y H:i', strtotime($biglietto['data_utilizzo'])) ?></p>
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
              <p><strong>Data validità:</strong> <?= date('d/m/Y', strtotime($biglietto['data_validita'])) ?><?= !empty($biglietto['ora_ingresso']) ? ' alle ' . clean(substr($biglietto['ora_ingresso'], 0, 5)) : '' ?></p>
              <p><strong>Prezzo:</strong> € <?= number_format($totaleTicket, 2, ',', '.') ?></p>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t border-avorio-dark flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <?php if (puoValidare($biglietto)): ?>
              <p class="text-sm text-gray-600">Il biglietto è valido e può essere segnato come utilizzato.</p>
              <form method="POST" onsubmit="return confirm('Confermi la validazione di questo biglietto?');">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="azione" value="valida_ticket">
                <input type="hidden" name="id_biglietto" value="<?= (int)$biglietto['id_biglietto'] ?>">
                <button type="submit" class="btn-oro px-8 py-3 rounded-lg font-body text-sm uppercase tracking-widest">Valida</button>
              </form>
            <?php elseif ($stato === 'Rimborsato'): ?>
              <p class="text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg p-4 w-full">Questo biglietto appartiene a un ordine rimborsato: resta nello storico ma non può più essere utilizzato.</p>
            <?php elseif ($stato === 'Utilizzato'): ?>
              <p class="text-sm text-blue-800 bg-blue-100 border border-blue-200 rounded-lg p-4 w-full">Questo biglietto è già stato usato e non può essere validato di nuovo.</p>
            <?php elseif (($biglietto['stato_pagamento'] ?? '') !== 'Pagato' || $stato === 'Non pagato'): ?>
              <p class="text-sm text-yellow-800 bg-yellow-100 border border-yellow-200 rounded-lg p-4 w-full">Questo biglietto non è ancora pagato. Deve essere saldato in cassa prima di diventare valido.</p>
            <?php else: ?>
              <p class="text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg p-4 w-full">Questo biglietto è annullato e non può essere validato.</p>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </div>
</main>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script nonce="<?= cspNonce() ?>">
(function () {
  var startBtn = document.getElementById('startQrScan');
  var stopBtn = document.getElementById('stopQrScan');
  var reader = document.getElementById('qrReader');
  var msg = document.getElementById('qrScanMsg');
  var input = document.getElementById('codiceTicketInput');
  var form = document.getElementById('ticketSearchForm');
  var html5QrCode = null;
  var scannerAttivo = false;

  function estraiCodice(valore) {
    valore = String(valore || '').trim();
    try {
      var url = new URL(valore);
      valore = url.searchParams.get('codice') || url.searchParams.get('ticket') || url.searchParams.get('ordine') || valore;
    } catch (e) {}
    var matchTicket = valore.match(/TKT-[A-Z0-9]{8,20}/i);
    if (matchTicket) return matchTicket[0].toUpperCase();
    var matchOrdine = valore.match(/ORD-[A-Z0-9]{6,24}/i);
    if (matchOrdine) return matchOrdine[0].toUpperCase();
    return valore.length >= 6 ? valore : '';
  }

  function impostaStatoScanner(attivo) {
    scannerAttivo = attivo;
    if (reader) reader.classList.toggle('hidden', !attivo);
    if (stopBtn) stopBtn.classList.toggle('hidden', !attivo);
    if (startBtn) startBtn.classList.toggle('hidden', attivo);
  }

  function fermaScanner() {
    if (html5QrCode && scannerAttivo) {
      html5QrCode.stop().then(function () {
        html5QrCode.clear();
        impostaStatoScanner(false);
      }).catch(function () {
        impostaStatoScanner(false);
      });
    } else {
      impostaStatoScanner(false);
    }
  }

  function codiceTrovato(decodedText) {
    var codiceLetto = estraiCodice(decodedText);
    if (!codiceLetto) return;
    input.value = codiceLetto;
    msg.textContent = 'Codice letto: ' + codiceLetto;
    if (html5QrCode && scannerAttivo) {
      html5QrCode.stop().then(function () {
        html5QrCode.clear();
        impostaStatoScanner(false);
        form.submit();
      }).catch(function () {
        form.submit();
      });
    } else {
      form.submit();
    }
  }

  function avviaScanner() {
    if (!window.isSecureContext) {
      msg.textContent = 'La fotocamera funziona solo con HTTPS. Inserisci il codice manualmente oppure apri la pagina in HTTPS.';
      return;
    }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      msg.textContent = 'Il browser non consente l’accesso alla fotocamera. Controlla i permessi oppure inserisci il codice manualmente.';
      return;
    }
    if (typeof Html5Qrcode === 'undefined') {
      msg.textContent = 'La libreria per leggere il QR non è stata caricata. Ricarica la pagina oppure inserisci il codice manualmente.';
      return;
    }

    msg.textContent = 'Apro la fotocamera... se il browser chiede il permesso, premi Consenti.';
    impostaStatoScanner(true);

    html5QrCode = new Html5Qrcode('qrReader', { verbose: false });
    var config = {
      fps: 10,
      qrbox: function(viewfinderWidth, viewfinderHeight) {
        var minEdge = Math.min(viewfinderWidth, viewfinderHeight);
        var size = Math.floor(minEdge * 0.72);
        return { width: size, height: size };
      },
      aspectRatio: 1.333334
    };

    html5QrCode.start(
      { facingMode: 'environment' },
      config,
      codiceTrovato,
      function () {}
    ).then(function () {
      msg.textContent = 'Scanner attivo: inquadra il QR code del biglietto.';
    }).catch(function () {
      impostaStatoScanner(false);
      msg.textContent = 'Non riesco ad accedere alla fotocamera. Controlla i permessi del browser o inserisci il codice manualmente.';
    });
  }

  if (startBtn) startBtn.addEventListener('click', avviaScanner);
  if (stopBtn) stopBtn.addEventListener('click', fermaScanner);
  window.addEventListener('pagehide', fermaScanner);
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
