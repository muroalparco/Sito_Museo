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
            $stmtF = $pdo->prepare("
                SELECT
                    f.*,
                    (f.capienza_massima - COALESCE(SUM(CASE WHEN b.stato <> 'Annullato' THEN 1 ELSE 0 END), 0)) AS posti_disponibili
                FROM Fasce_Orarie f
                LEFT JOIN Biglietti b ON b.id_fascia = f.id_fascia
                WHERE f.id_esposizione = ?
                GROUP BY f.id_fascia
                ORDER BY f.data ASC, f.ora_ingresso ASC
            ");
            $stmtF->execute([$idEsposizione]);
            $fasce = $stmtF->fetchAll();
        }
    }

    $tariffeStmt = $pdo->prepare("
        SELECT t.id_tariffa, t.tipo_biglietto, t.prezzo, cr.id_categoria, cr.nome AS categoria, cr.percentuale_sconto, cr.documento_richiesto
        FROM Tariffe t
        JOIN Categorie_Riduzione cr ON cr.id_categoria = t.id_categoria
        WHERE t.tipo_biglietto = ?
          AND LOWER(cr.nome) <> 'docente accompagnatore'
        ORDER BY t.prezzo DESC
    ");
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

<section class="booking-hero bg-antracite py-14">
  <div class="max-w-7xl mx-auto px-4 fade-up">
    <div class="grid lg:grid-cols-[1fr_auto] gap-8 items-end">
      <div>
        <p class="text-acciaio text-xs uppercase tracking-widest font-body mb-3 font-bold">Prenotazione online</p>
        <h1 class="font-display text-avorio text-4xl md:text-5xl font-bold leading-tight">
          <?= $tipo === 'base' ? 'Ingresso al Museo' : clean($esposizione['titolo'] ?? 'Esposizione') ?>
        </h1>
        <p class="mt-4 max-w-2xl font-body" style="color: rgba(255, 253, 245, 0.9);">Scegli data, fascia oraria, categorie e servizi opzionali. Il sistema genera un ordine con biglietti digitali e QR code.</p>
      </div>
      <a href="<?= $linkPrenotazioneDocente ?>" class="btn-outline px-5 py-3 rounded text-sm text-center bg-white/5 border-acciaio text-avorio hover:bg-acciaio hover:text-antracite">
        👩‍🏫 Prenota per la classe
      </a>
    </div>
  </div>
</section>

<main class="booking-shell px-4 sm:px-6 lg:px-8 py-12">
  <div class="max-w-7xl mx-auto">
  <?php if ($errore): ?>
    <div class="alert-error p-5 rounded mb-8 font-body"> <?= clean($errore) ?></div>
    <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-outline px-6 py-3 rounded inline-block">Torna alle esposizioni</a>
  <?php elseif (empty($tariffe)): ?>
    <div class="alert-error p-5 rounded mb-8 font-body"> Nessuna tariffa disponibile per questo tipo di biglietto.</div>
  <?php else: ?>

  <div class="grid lg:grid-cols-[minmax(0,1fr)_360px] gap-8">
    <section class="booking-panel">
      <div class="booking-panel-header">
        <div>
          <p class="booking-kicker">Percorso guidato</p>
          <h2 class="booking-panel-title">Crea la tua prenotazione</h2>
          <p class="booking-panel-text">Non è necessario effettuare il login per acquistare. Se hai un account, troverai l’ordine anche nella tua area personale.</p>
        </div>
      </div>

      <form action="<?= SITE_URL ?>/pagamento.php" method="POST" class="booking-form">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="tipo" value="<?= clean($tipo) ?>">
        <?php if ($tipo === 'esposizione'): ?>
          <input type="hidden" name="id_esposizione" value="<?= (int)$idEsposizione ?>">
        <?php endif; ?>

        <section class="booking-section">
          <h3 class="booking-section-title"><span class="booking-section-badge">👤</span>Dati visitatore</h3>
          <div class="grid sm:grid-cols-2 gap-4">
            <div class="booking-field">
              <label>Nome e cognome <span class="text-red-500">*</span></label>
              <input type="text" name="nome_cliente" required value="<?= clean(trim(($_SESSION['utente_nome'] ?? '') . ' ' . ($_SESSION['utente_cognome'] ?? ''))) ?>" placeholder="Mario Rossi">
            </div>
            <div class="booking-field">
              <label>Email <span class="text-red-500">*</span></label>
              <input type="email" name="email_cliente" required value="<?= clean($_SESSION['utente_email'] ?? '') ?>" placeholder="nome@email.it">
            </div>
          </div>
        </section>

        <section class="booking-section">
          <h3 class="booking-section-title"><span class="booking-section-badge">📅</span><?= $tipo === 'esposizione' ? 'Scegli fascia oraria' : 'Scegli data visita' ?></h3>
          <?php if ($tipo === 'esposizione'): ?>
            <?php if (empty($fasce)): ?>
              <div class="alert-error p-4 rounded text-sm">Non ci sono fasce orarie disponibili per questa esposizione.</div>
            <?php else: ?>
              <div class="booking-time-grid">
                <?php foreach ($fasce as $f): ?>
                  <?php $posti = max(0, (int)$f['posti_disponibili']); ?>
                  <label class="booking-time-card <?= $posti <= 0 ? 'is-disabled' : '' ?>">
                    <input type="radio" name="id_fascia" value="<?= (int)$f['id_fascia'] ?>" <?= $posti <= 0 ? 'disabled' : 'required' ?>>
                    <span class="booking-time-date"><?= date('d/m/Y', strtotime($f['data'])) ?></span>
                    <span class="booking-time-hour"><?= substr($f['ora_ingresso'], 0, 5) ?></span>
                    <span class="booking-time-seats"><?= $posti > 0 ? $posti . ' posti disponibili' : 'Posti esauriti' ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="booking-field">
              <label>Data visita <span class="text-red-500">*</span></label>
              <input type="date" name="data_visita" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
            </div>
          <?php endif; ?>
        </section>

        <section class="booking-section">
          <h3 class="booking-section-title"><span class="booking-section-badge">🎟️</span>Biglietti per categoria</h3>
          <p class="text-sm text-gray-500 mb-4">Puoi creare un unico ordine con categorie diverse. Inserisci 0 nelle categorie che non ti servono.</p>
          <div class="grid sm:grid-cols-2 gap-3">
            <?php foreach ($tariffe as $t): ?>
              <article class="booking-ticket-card">
                <span class="booking-ticket-name"><?= clean($t['categoria']) ?></span>
                <span class="booking-ticket-meta">
                  € <?= number_format((float)$t['prezzo'], 2, ',', '.') ?>
                  <?= $t['documento_richiesto'] ? ' · documento: '.clean($t['documento_richiesto']) : '' ?>
                </span>
                <label class="sr-only" for="tariffa-<?= (int)$t['id_tariffa'] ?>">Quantità <?= clean($t['categoria']) ?></label>
                <input id="tariffa-<?= (int)$t['id_tariffa'] ?>" type="number" name="tariffa_quantita[<?= (int)$t['id_tariffa'] ?>]" min="0" max="50" value="0" inputmode="numeric">
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <section class="booking-section">
          <h3 class="booking-section-title"><span class="booking-section-badge">✨</span>Servizi opzionali</h3>
          <?php if (empty($servizi)): ?>
            <p class="text-sm text-gray-500">Nessun servizio opzionale disponibile.</p>
          <?php else: ?>
            <div class="grid sm:grid-cols-2 gap-3">
              <?php foreach ($servizi as $s): ?>
                <label class="booking-service-card">
                  <input type="checkbox" name="servizi[]" value="<?= (int)$s['id_servizio'] ?>">
                  <span class="booking-service-name"><?= clean($s['nome']) ?> · € <?= number_format((float)$s['prezzo'], 2, ',', '.') ?></span>
                  <span class="booking-service-desc"><?= clean($s['descrizione'] ?? '') ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="booking-section">
          <h3 class="booking-section-title"><span class="booking-section-badge">💳</span>Metodo di pagamento</h3>
          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <label class="booking-payment-card">
              <input type="radio" name="metodo_pagamento" value="contanti" required>
              <span class="booking-payment-name">Contanti</span>
              <span class="booking-payment-desc">Pagamento in cassa.</span>
            </label>
            <label class="booking-payment-card">
              <input type="radio" name="metodo_pagamento" value="carta" required checked>
              <span class="booking-payment-name">Carta</span>
              <span class="booking-payment-desc">Pagamento simulato.</span>
            </label>
            <label class="booking-payment-card">
              <input type="radio" name="metodo_pagamento" value="paypal" required>
              <span class="booking-payment-name">PayPal</span>
              <span class="booking-payment-desc">Accesso simulato.</span>
            </label>
            <?php if (isLogged()): ?>
            <label class="booking-payment-card">
              <input type="radio" name="metodo_pagamento" value="saldo" required>
              <span class="booking-payment-name">Saldo</span>
              <span class="booking-payment-desc">Portafoglio: € <?= number_format(saldoUtenteCorrente(), 2, ',', '.') ?></span>
            </label>
            <?php endif; ?>
          </div>
        </section>

        <div class="booking-submit-bar">
          <p class="text-xs text-gray-500 max-w-md">Premendo conferma accederai a una pagina di pagamento simulato. Al termine riceverai il codice ordine per recuperare i biglietti.</p>
          <button type="submit" class="btn-oro px-7 py-3 rounded font-body text-sm uppercase tracking-wide">Conferma e paga</button>
        </div>
      </form>
    </section>

    <aside class="booking-summary">
      <div class="booking-summary-top">
        <p class="booking-kicker text-acciaio">Riepilogo</p>
        <h2 class="booking-summary-title"><?= $tipo === 'base' ? 'Ingresso singolo' : clean($esposizione['titolo'] ?? 'Esposizione') ?></h2>
      </div>
      <div class="booking-summary-body">
        <?php if ($tipo === 'esposizione'): ?>
          <p class="text-sm text-gray-600 mb-4"><?= clean($esposizione['descrizione'] ?? '') ?></p>
          <div class="booking-summary-row"><span>Dal</span><strong><?= date('d/m/Y', strtotime($esposizione['data_inizio'])) ?></strong></div>
          <div class="booking-summary-row"><span>Al</span><strong><?= date('d/m/Y', strtotime($esposizione['data_fine'])) ?></strong></div>
        <?php else: ?>
          <p class="text-sm text-gray-600 mb-4">Il biglietto base consente l'accesso alle collezioni permanenti del museo nella data selezionata.</p>
        <?php endif; ?>
        <div class="mt-5 rounded-xl bg-avorio p-4 text-sm text-gray-600">
          Conserva il codice ordine: sarà necessario per visualizzare e stampare i biglietti anche senza account.
        </div>
      </div>
    </aside>
  </div>

  <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
