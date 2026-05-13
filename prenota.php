<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pdo = getDB();
$pageTitle = 'Prenota';

$idEsposizione = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = ($idEsposizione > 0) ? 'esposizione' : 'base';
$errore = '';
$esposizione = null;
$fasce = [];

try {
    if ($tipo === 'esposizione') {
        $stmt = $pdo->prepare("SELECT * FROM Esposizioni WHERE id_esposizione = ? AND stato = 'Pubblicata' LIMIT 1");
        $stmt->execute([$idEsposizione]);
        $esposizione = $stmt->fetch();
        if (!$esposizione) {
            $errore = 'Esposizione non disponibile per la prenotazione.';
        } else {
            $stmtF = $pdo->prepare("\n                SELECT\n                    f.*,\n                    (f.capienza_massima - COALESCE(SUM(CASE WHEN b.stato <> 'Annullato' THEN 1 ELSE 0 END), 0)) AS posti_disponibili\n                FROM Fasce_Orarie f\n                LEFT JOIN Biglietti b ON b.id_fascia = f.id_fascia\n                WHERE f.id_esposizione = ?\n                GROUP BY f.id_fascia\n                ORDER BY f.data ASC, f.ora_ingresso ASC\n            ");
            $stmtF->execute([$idEsposizione]);
            $fasce = $stmtF->fetchAll();
        }
    }

    $tariffeStmt = $pdo->prepare("\n        SELECT t.id_tariffa, t.tipo_biglietto, t.prezzo, cr.id_categoria, cr.nome AS categoria, cr.percentuale_sconto, cr.documento_richiesto\n        FROM Tariffe t\n        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria\n        WHERE t.tipo_biglietto = ?\n        ORDER BY t.prezzo DESC\n    ");
    $tariffeStmt->execute([$tipo]);
    $tariffe = $tariffeStmt->fetchAll();

    $serviziStmt = $pdo->query("SELECT id_servizio, nome, descrizione, prezzo FROM Servizi_Opzionali ORDER BY prezzo ASC");
    $servizi = $serviziStmt->fetchAll();
} catch (Exception $e) {
    $errore = 'Errore nel caricamento dei dati di prenotazione.';
    $tariffe = [];
    $servizi = [];
}

$linkPrenotazioneDocente = SITE_URL . '/prenota_docente.php' . ($tipo === 'esposizione' ? '?id=' . (int)$idEsposizione : '?tipo=base');

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <a href="<?= SITE_URL ?>/esposizioni.php" class="hover:text-oro transition-colors">Esposizioni</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Prenota</span>
  </div>
</div>

<section class="bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Prenotazione</p>
    <h1 class="font-display text-avorio text-4xl font-bold">
      <?= $tipo === 'base' ? 'Ingresso al Museo' : clean($esposizione['titolo'] ?? 'Esposizione') ?>
    </h1>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
  </div>
</section>

<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <?php if ($errore): ?>
    <div class="alert-error p-5 rounded mb-8 font-body">⚠️ <?= clean($errore) ?></div>
    <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline px-6 py-3 rounded inline-block">Torna alle esposizioni</a>
  <?php elseif (empty($tariffe)): ?>
    <div class="alert-error p-5 rounded mb-8 font-body">⚠️ Nessuna tariffa disponibile per questo tipo di biglietto.</div>
  <?php else: ?>

  <div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 bg-white rounded-2xl shadow border border-avorio-dark overflow-hidden">
      <div class="bg-antracite px-6 py-5">
        <h2 class="font-display text-2xl text-avorio font-bold">Dati della prenotazione</h2>
        <p class="text-gray-400 text-sm mt-1">Non è necessario effettuare il login per acquistare.</p>
      </div>

      <div class="px-6 py-4 bg-avorio border-b border-avorio-dark flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <p class="font-body font-bold text-antracite text-sm">Sei un docente e vuoi portare la tua classe?</p>
          <p class="text-xs text-gray-500 mt-1">Usa la prenotazione dedicata alle classi, senza limite massimo di biglietti.</p>
        </div>
        <a href="<?= $linkPrenotazioneDocente ?>" class="btn-outline px-5 py-2 rounded text-sm text-center whitespace-nowrap">
          Prenota per la classe
        </a>
      </div>

      <form action="<?= SITE_URL ?>/pagamento.php" method="POST" class="p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="tipo" value="<?= clean($tipo) ?>">
        <?php if ($tipo === 'esposizione'): ?>
          <input type="hidden" name="id_esposizione" value="<?= (int)$idEsposizione ?>">
        <?php endif; ?>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Nome e cognome <span class="text-red-500">*</span></label>
            <input type="text" name="nome_cliente" required value="<?= clean(trim(($_SESSION['utente_nome'] ?? '') . ' ' . ($_SESSION['utente_cognome'] ?? ''))) ?>"
                   placeholder="Mario Rossi"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email_cliente" required value="<?= clean($_SESSION['utente_email'] ?? '') ?>"
                   placeholder="nome@email.it"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>
        </div>

        <?php if ($tipo === 'esposizione'): ?>
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Fascia oraria <span class="text-red-500">*</span></label>
            <?php if (empty($fasce)): ?>
              <div class="alert-error p-4 rounded text-sm">Non ci sono fasce orarie disponibili per questa esposizione.</div>
            <?php else: ?>
              <select name="id_fascia" required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
                <option value="">Seleziona una fascia</option>
                <?php foreach ($fasce as $f): ?>
                  <?php $posti = max(0, (int)$f['posti_disponibili']); ?>
                  <option value="<?= (int)$f['id_fascia'] ?>" <?= $posti <= 0 ? 'disabled' : '' ?>>
                    <?= date('d/m/Y', strtotime($f['data'])) ?> - <?= substr($f['ora_ingresso'], 0, 5) ?> · <?= $posti ?> posti disponibili
                  </option>
                <?php endforeach; ?>
              </select>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Data visita <span class="text-red-500">*</span></label>
            <input type="date" name="data_visita" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>
        <?php endif; ?>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Tariffa <span class="text-red-500">*</span></label>
            <select name="id_tariffa" required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
              <option value="">Seleziona una tariffa</option>
              <?php foreach ($tariffe as $t): ?>
                <option value="<?= (int)$t['id_tariffa'] ?>">
                  <?= clean($t['categoria']) ?> - € <?= number_format((float)$t['prezzo'], 2, ',', '.') ?>
                  <?= $t['documento_richiesto'] ? ' · documento: '.clean($t['documento_richiesto']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-body font-bold text-antracite mb-1">Numero posti <span class="text-red-500">*</span></label>
            <input type="number" name="quantita" min="1" max="20" value="1" required
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>
        </div>

        <div>
          <label class="block text-sm font-body font-bold text-antracite mb-3">Servizi opzionali</label>
          <?php if (empty($servizi)): ?>
            <p class="text-sm text-gray-500">Nessun servizio opzionale disponibile.</p>
          <?php else: ?>
            <div class="grid sm:grid-cols-2 gap-3">
              <?php foreach ($servizi as $s): ?>
                <label class="flex gap-3 items-start border border-avorio-dark rounded-xl p-4 hover:border-oro transition cursor-pointer">
                  <input type="checkbox" name="servizi[]" value="<?= (int)$s['id_servizio'] ?>" class="mt-1">
                  <span>
                    <span class="block font-bold text-antracite text-sm"><?= clean($s['nome']) ?> · € <?= number_format((float)$s['prezzo'], 2, ',', '.') ?></span>
                    <span class="block text-xs text-gray-500 mt-1"><?= clean($s['descrizione'] ?? '') ?></span>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="border-t border-avorio-dark pt-6">
          <label class="block text-sm font-body font-bold text-antracite mb-3">Metodo di pagamento <span class="text-red-500">*</span></label>
          <div class="grid sm:grid-cols-3 gap-3">
            <label class="flex gap-3 items-start border border-avorio-dark rounded-xl p-4 hover:border-oro transition cursor-pointer bg-white">
              <input type="radio" name="metodo_pagamento" value="contanti" required class="mt-1">
              <span>
                <span class="block font-bold text-antracite text-sm">Contanti in cassa</span>
                <span class="block text-xs text-gray-500 mt-1">L'ordine viene emesso, ma i biglietti restano non pagati fino al saldo.</span>
              </span>
            </label>
            <label class="flex gap-3 items-start border border-avorio-dark rounded-xl p-4 hover:border-oro transition cursor-pointer bg-white">
              <input type="radio" name="metodo_pagamento" value="carta" required class="mt-1" checked>
              <span>
                <span class="block font-bold text-antracite text-sm">Carta di credito</span>
                <span class="block text-xs text-gray-500 mt-1">Pagamento simulato con dati carta.</span>
              </span>
            </label>
            <label class="flex gap-3 items-start border border-avorio-dark rounded-xl p-4 hover:border-oro transition cursor-pointer bg-white">
              <input type="radio" name="metodo_pagamento" value="paypal" required class="mt-1">
              <span>
                <span class="block font-bold text-antracite text-sm">PayPal</span>
                <span class="block text-xs text-gray-500 mt-1">Simulazione accesso PayPal.</span>
              </span>
            </label>
          </div>
        </div>

        <div class="border-t border-avorio-dark pt-6 flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
          <p class="text-xs text-gray-500 max-w-md">Premendo conferma accederai a una pagina di pagamento simulato. Al termine riceverai il codice ordine per recuperare i biglietti.</p>
          <button type="submit" class="btn-oro px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
            Conferma e paga
          </button>
        </div>
      </form>
    </section>

    <aside class="bg-white rounded-2xl shadow border border-avorio-dark p-6 h-fit">
      <h2 class="font-display text-2xl font-bold text-antracite mb-4">Riepilogo</h2>
      <?php if ($tipo === 'esposizione'): ?>
        <p class="text-sm text-gray-500 mb-4"><?= clean($esposizione['descrizione'] ?? '') ?></p>
        <div class="text-xs text-acciaio font-body border-t border-avorio-dark pt-4">
          <div>Periodo: <?= date('d/m/Y', strtotime($esposizione['data_inizio'])) ?> → <?= date('d/m/Y', strtotime($esposizione['data_fine'])) ?></div>
        </div>
      <?php else: ?>
        <p class="text-sm text-gray-500 mb-4">Il biglietto base consente l'accesso alle collezioni permanenti del museo nella data selezionata.</p>
      <?php endif; ?>
      <div class="mt-5 rounded-xl bg-avorio p-4 text-sm text-gray-600">
        Conserva il codice ordine: sarà necessario per visualizzare e stampare i biglietti anche senza account.
      </div>
    </aside>
  </div>

  <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
