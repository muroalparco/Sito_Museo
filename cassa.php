<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ordine_pdf.php';

requireCassiere();

$pageTitle = 'Cassa';
$pdo = getDB();

$codiceCercato = strtoupper(trim($_POST['codice'] ?? $_GET['codice'] ?? ''));
$errore = '';
$successo = '';
$ordine = null;
$biglietti = [];
$mailInviata = null;

function cassaOrdineRimborsato(array $ordine): bool {
    return strcasecmp((string)($ordine['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
}

function cassaCaricaOrdine(PDO $pdo, int $idOrdine): ?array {
    $stmt = $pdo->prepare('SELECT * FROM Ordini WHERE id_ordine = ? LIMIT 1');
    $stmt->execute([$idOrdine]);
    $ordine = $stmt->fetch();
    return $ordine ?: null;
}

function cassaTrovaOrdine(PDO $pdo, string $codice): ?array {
    if ($codice === '') {
        return null;
    }

    $codice = strtoupper(trim($codice));

    if (ctype_digit($codice)) {
        $ordine = cassaCaricaOrdine($pdo, (int)$codice);
        if ($ordine) {
            return $ordine;
        }
    }

    $stmt = $pdo->prepare('SELECT * FROM Ordini WHERE UPPER(codice_recupero) = ? LIMIT 1');
    $stmt->execute([$codice]);
    $ordine = $stmt->fetch();
    if ($ordine) {
        return $ordine;
    }

    $stmt = $pdo->prepare('
        SELECT o.*
        FROM Biglietti b
        INNER JOIN Ordini o ON o.id_ordine = b.id_ordine
        WHERE UPPER(b.codice_univoco) = ?
        LIMIT 1
    ');
    $stmt->execute([$codice]);
    $ordine = $stmt->fetch();

    return $ordine ?: null;
}

function cassaCaricaBiglietti(PDO $pdo, int $idOrdine): array {
    $stmt = $pdo->prepare('
        SELECT
            b.*,
            cr.nome AS categoria,
            f.data AS data_fascia,
            f.ora_ingresso,
            e.titolo AS esposizione,
            GROUP_CONCAT(CONCAT(so.nome, " (€ ", FORMAT(bs.prezzo_snapshot, 2), ")") SEPARATOR ", ") AS servizi
        FROM Biglietti b
        LEFT JOIN Categorie_Riduzione cr ON cr.id_categoria = b.id_categoria
        LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia
        LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione
        LEFT JOIN Biglietti_Servizi bs ON bs.id_biglietto = b.id_biglietto
        LEFT JOIN Servizi_Opzionali so ON so.id_servizio = bs.id_servizio
        WHERE b.id_ordine = ?
        GROUP BY b.id_biglietto
        ORDER BY b.id_biglietto ASC
    ');
    $stmt->execute([$idOrdine]);
    return $stmt->fetchAll();
}

function cassaCodiciBiglietti(array $biglietti): array {
    return array_values(array_map(static fn($b) => (string)$b['codice_univoco'], $biglietti));
}

function cassaInviaMailOrdine(array $ordine, array $biglietti): bool {
    $codici = cassaCodiciBiglietti($biglietti);
    $pdfContent = creaPdfOrdine($ordine, $codici);

    $tmpBase = tempnam(sys_get_temp_dir(), 'mss_cassa_ordine_');
    if ($tmpBase === false) {
        return inviaEmailConfermaOrdine($ordine, $codici, '');
    }

    $tmpPdf = $tmpBase . '.pdf';
    @rename($tmpBase, $tmpPdf);
    file_put_contents($tmpPdf, $pdfContent);

    try {
        return inviaEmailConfermaOrdine($ordine, $codici, $tmpPdf);
    } finally {
        @unlink($tmpPdf);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['azione'] ?? '') === 'segna_pagato') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $idOrdine = (int)($_POST['id_ordine'] ?? 0);
        if ($idOrdine <= 0) {
            $errore = 'Ordine non valido.';
        } else {
            try {
                $pdo->beginTransaction();

                $ordineDaPagare = cassaCaricaOrdine($pdo, $idOrdine);
                if (!$ordineDaPagare) {
                    throw new RuntimeException('Ordine non trovato.');
                }

                if (($ordineDaPagare['stato_pagamento'] ?? '') === 'Annullato') {
                    throw new RuntimeException('Non puoi segnare come pagato un ordine annullato.');
                }
                if (cassaOrdineRimborsato($ordineDaPagare)) {
                    throw new RuntimeException('Questo ordine è stato rimborsato: non è possibile effettuare nuove operazioni.');
                }

                $stmt = $pdo->prepare("UPDATE Ordini SET stato_pagamento = 'Pagato', metodo_pagamento = 'contanti' WHERE id_ordine = ?");
                $stmt->execute([$idOrdine]);

                $stmt = $pdo->prepare("UPDATE Biglietti SET stato = 'Valido' WHERE id_ordine = ? AND stato = 'Non pagato'");
                $stmt->execute([$idOrdine]);

                $pdo->commit();

                $ordine = cassaCaricaOrdine($pdo, $idOrdine);
                $biglietti = cassaCaricaBiglietti($pdo, $idOrdine);
                $mailInviata = cassaInviaMailOrdine($ordine, $biglietti);

                $successo = 'Pagamento registrato correttamente. I biglietti ora risultano validi.';
                if ($mailInviata) {
                    $successo .= ' La mail di conferma con il PDF dell\'ordine è stata inviata.';
                } else {
                    $successo .= ' Attenzione: il pagamento è stato registrato, ma la mail non è stata inviata.';
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errore = $e->getMessage();
            }
        }
    }
} elseif ($codiceCercato !== '') {
    try {
        $ordine = cassaTrovaOrdine($pdo, $codiceCercato);
        if (!$ordine) {
            $errore = 'Nessun ordine o biglietto trovato con il codice inserito.';
        } else {
            $biglietti = cassaCaricaBiglietti($pdo, (int)$ordine['id_ordine']);
        }
    } catch (Throwable $e) {
        $errore = 'Errore durante la ricerca dell\'ordine.';
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Cassa</span>
  </div>
</div>

<main class="min-h-screen bg-avorio py-12 px-4">
  <div class="max-w-6xl mx-auto">

    <section class="bg-antracite text-avorio rounded-2xl shadow-xl p-8 md:p-10 mb-8 text-center">
      <img src="<?= SITE_URL ?>/img/logo.svg" alt="Logo Museo Storico Severi" class="h-24 w-auto object-contain mx-auto mb-6">
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Area cassiere</p>
      <h1 class="font-display text-3xl md:text-5xl font-bold mb-4">Gestione pagamenti in cassa</h1>
      <p class="text-gray-300 max-w-2xl mx-auto leading-relaxed">
        Inserisci il codice dell'ordine oppure il codice di un biglietto. Da qui puoi segnare come pagati gli ordini saldati alla cassa.
      </p>
    </section>

    <section class="bg-white rounded-2xl shadow border border-avorio-dark p-6 md:p-8 mb-8">
      <form method="POST" id="cassaSearchForm" class="grid md:grid-cols-[1fr_auto] gap-4 items-end">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="azione" value="cerca">
        <div>
          <label for="codiceCassaInput" class="block text-sm font-bold text-antracite mb-2">Codice ordine o codice biglietto</label>
          <input
            type="text"
            id="codiceCassaInput"
            name="codice"
            value="<?= clean($codiceCercato) ?>"
            placeholder="Es. ORD-ABC12345 oppure TKT-ABC12345"
            required
            class="w-full px-4 py-3 border border-gray-200 rounded-xl font-body text-sm uppercase focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"
          >
        </div>
        <button type="submit" class="btn-oro px-8 py-3 rounded-xl font-body text-sm uppercase tracking-wide">
          Cerca
        </button>
      </form>

      <div class="mt-6 rounded-2xl border border-avorio-dark bg-avorio p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
          <div>
            <h2 class="font-display text-xl font-bold text-antracite">Scansione QR con fotocamera</h2>
            <p class="text-sm text-gray-600 mt-1">Inquadra il QR code del biglietto: l’ordine collegato verrà cercato automaticamente.</p>
          </div>
          <button type="button" id="startQrScan" class="btn-outline px-5 py-2 rounded text-sm">Apri fotocamera</button>
            <button type="button" id="stopQrScan" class="hidden btn-outline px-5 py-2 rounded text-sm">Chiudi fotocamera</button>
        </div>
        <div id="qrReader" class="hidden w-full max-w-md overflow-hidden rounded-xl border border-avorio-dark bg-white"></div>
        <p id="qrScanMsg" class="text-xs text-gray-500 mt-3">Se la scansione automatica non funziona, puoi inserire manualmente il codice ticket o il codice ordine.</p>
      </div>
    </section>

    <?php if ($errore): ?>
      <div class="floating-alert mb-8 rounded-xl bg-red-50 border border-red-200 text-red-700 px-5 py-4" role="alert">
        <?= clean($errore) ?>
      </div>
    <?php endif; ?>

    <?php if ($successo): ?>
      <div class="floating-alert mb-8 rounded-xl bg-green-50 border border-green-200 text-green-700 px-5 py-4" role="status">
        <?= clean($successo) ?>
      </div>
    <?php endif; ?>

    <?php if ($ordine): ?>
      <?php
        $statoPagamento = $ordine['stato_pagamento'] ?? '—';
        $rimborsato = cassaOrdineRimborsato($ordine);
        $pagato = $statoPagamento === 'Pagato';
        $annullato = $statoPagamento === 'Annullato';
        $badge = $rimborsato ? 'bg-red-100 text-red-700' : ($pagato ? 'bg-green-100 text-green-800' : ($annullato ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-800'));
      ?>

      <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden mb-8">
        <div class="h-2 bg-oro"></div>
        <div class="p-6 md:p-8">
          <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">
            <div>
              <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Ordine trovato</p>
              <h2 class="font-display text-3xl font-bold text-antracite mb-3">
                <?= clean($ordine['codice_recupero'] ?? ('#' . $ordine['id_ordine'])) ?>
              </h2>
              <div class="space-y-2 text-sm text-gray-600">
                <p><strong>Acquirente:</strong> <?= clean($ordine['nome_cliente'] ?? '—') ?></p>
                <p><strong>Email:</strong> <?= clean($ordine['email_cliente'] ?? '—') ?></p>
                <p><strong>Data acquisto:</strong> <?= !empty($ordine['data_acquisto']) ? date('d/m/Y H:i', strtotime($ordine['data_acquisto'])) : '—' ?></p>
                <p><strong>Metodo pagamento:</strong> <?= clean(ucfirst($ordine['metodo_pagamento'] ?? '—')) ?></p>
              </div>
            </div>

            <div class="lg:text-right">
              <div class="inline-flex px-4 py-2 rounded-full text-sm font-bold <?= $badge ?> mb-4">
                <?= clean($rimborsato ? 'Rimborsato' : $statoPagamento) ?>
              </div>
              <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Totale</p>
              <p class="font-display text-4xl font-bold text-oro mb-5">
                € <?= number_format((float)($ordine['importo_totale'] ?? 0), 2, ',', '.') ?>
              </p>

              <?php if ($rimborsato): ?>
                <p class="text-red-700 font-bold max-w-xs">Ordine rimborsato: nessuna operazione di cassa disponibile.</p>
              <?php elseif (!$pagato && !$annullato): ?>
                <form method="POST" onsubmit="return confirm('Confermi di voler segnare questo ordine come pagato?');">
                  <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                  <input type="hidden" name="azione" value="segna_pagato">
                  <input type="hidden" name="id_ordine" value="<?= (int)$ordine['id_ordine'] ?>">
                  <button type="submit" class="btn-oro px-7 py-3 rounded-xl font-body text-sm uppercase tracking-wide">
                    Segna come pagato
                  </button>
                </form>
              <?php elseif ($pagato): ?>
                <p class="text-green-700 font-bold">Ordine già pagato.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-white rounded-2xl shadow border border-avorio-dark p-6 md:p-8">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Biglietti collegati</p>
            <h3 class="font-display text-2xl font-bold text-antracite">Dettaglio biglietti</h3>
          </div>
          <span class="bg-avorio px-4 py-2 rounded-full text-sm font-bold text-antracite">
            <?= count($biglietti) ?> biglietto/i
          </span>
        </div>

        <?php if (empty($biglietti)): ?>
          <p class="text-gray-500">Nessun biglietto collegato a questo ordine.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="border-b border-avorio-dark text-left text-gray-500 uppercase tracking-wide text-xs">
                  <th class="py-3 pr-4">Codice</th>
                  <th class="py-3 pr-4">Percorso</th>
                  <th class="py-3 pr-4">Data</th>
                  <th class="py-3 pr-4">Categoria</th>
                  <th class="py-3 pr-4">Stato</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-avorio-dark">
                <?php foreach ($biglietti as $b): ?>
                  <?php
                    $statoB = $rimborsato ? 'Rimborsato' : ($b['stato'] ?? '—');
                    $badgeB = $statoB === 'Valido' ? 'bg-green-100 text-green-800' : ($statoB === 'Non pagato' ? 'bg-yellow-100 text-yellow-800' : ($statoB === 'Utilizzato' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-700'));
                  ?>
                  <tr>
                    <td class="py-4 pr-4 font-bold text-antracite whitespace-nowrap"><?= clean($b['codice_univoco']) ?></td>
                    <td class="py-4 pr-4 text-gray-600"><?= clean($b['esposizione'] ?? 'Museo Storico Severi') ?></td>
                    <td class="py-4 pr-4 text-gray-600 whitespace-nowrap">
                      <?= !empty($b['data_validita']) ? date('d/m/Y', strtotime($b['data_validita'])) : '—' ?>
                      <?= !empty($b['ora_ingresso']) ? ' · ' . clean(substr($b['ora_ingresso'], 0, 5)) : '' ?>
                    </td>
                    <td class="py-4 pr-4 text-gray-600"><?= clean($b['categoria'] ?? '—') ?></td>
                    <td class="py-4 pr-4 whitespace-nowrap">
                      <span class="px-3 py-1 rounded-full text-xs font-bold <?= $badgeB ?>"><?= clean($statoB) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

  </div>
</main>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
  var startBtn = document.getElementById('startQrScan');
  var stopBtn = document.getElementById('stopQrScan');
  var reader = document.getElementById('qrReader');
  var msg = document.getElementById('qrScanMsg');
  var input = document.getElementById('codiceCassaInput');
  var form = document.getElementById('cassaSearchForm');
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
