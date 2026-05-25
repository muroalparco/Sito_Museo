<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$pageTitle = 'Il mio account';
$pdo       = getDB();

// Dati utente aggiornati dal DB
$stmt = $pdo->prepare('SELECT * FROM Utenti WHERE id_utente = ?');
$stmt->execute([$_SESSION['utente_id']]);
$utente = $stmt->fetch();
$saldoUtente = saldoUtenteCorrente($pdo, (int)$_SESSION['utente_id']);

// Ultimi 5 ordini con conteggio biglietti
$ordini = $pdo->prepare(
    "SELECT o.id_ordine, o.data_acquisto, o.importo_totale, o.stato_pagamento,
            COALESCE(o.stato_rimborso, 'Nessuno') AS stato_rimborso,
            COUNT(b.id_biglietto) AS num_biglietti
     FROM Ordini o
     LEFT JOIN Biglietti b ON b.id_ordine = o.id_ordine
     WHERE o.id_utente = ?
     GROUP BY o.id_ordine, o.data_acquisto, o.importo_totale, o.stato_pagamento, o.stato_rimborso
     ORDER BY o.data_acquisto DESC
     LIMIT 5"
);
$ordini->execute([$_SESSION['utente_id']]);
$ultimiOrdini = $ordini->fetchAll();


// Dati riepilogo dashboard utente: usa solo le tabelle/campi esistenti, senza modificare il database.
$dashboardStats = [
    'biglietti_validi' => 0,
    'ordini_totali' => 0,
    'ordini_pagati' => 0,
    'rimborsi_attivi' => 0,
];

$stmtStats = $pdo->prepare(
    "SELECT
        COUNT(DISTINCT o.id_ordine) AS ordini_totali,
        SUM(CASE WHEN o.stato_pagamento = 'Pagato' THEN 1 ELSE 0 END) AS ordini_pagati,
        SUM(CASE WHEN COALESCE(o.stato_rimborso, 'Nessuno') = 'Richiesto' THEN 1 ELSE 0 END) AS rimborsi_attivi
     FROM Ordini o
     WHERE o.id_utente = ?"
);
$stmtStats->execute([$_SESSION['utente_id']]);
$ordineStats = $stmtStats->fetch() ?: [];
$dashboardStats['ordini_totali'] = (int)($ordineStats['ordini_totali'] ?? 0);
$dashboardStats['ordini_pagati'] = (int)($ordineStats['ordini_pagati'] ?? 0);
$dashboardStats['rimborsi_attivi'] = (int)($ordineStats['rimborsi_attivi'] ?? 0);

$stmtBigliettiValidi = $pdo->prepare(
    "SELECT COUNT(*)
     FROM Biglietti b
     INNER JOIN Ordini o ON o.id_ordine = b.id_ordine
     WHERE o.id_utente = ?
       AND b.stato = 'Valido'
       AND o.stato_pagamento = 'Pagato'
       AND COALESCE(o.stato_rimborso, 'Nessuno') <> 'Accettato'"
);
$stmtBigliettiValidi->execute([$_SESSION['utente_id']]);
$dashboardStats['biglietti_validi'] = (int)$stmtBigliettiValidi->fetchColumn();

$stmtProssimaVisita = $pdo->prepare(
    "SELECT
        o.id_ordine,
        COUNT(b2.id_biglietto) AS num_biglietti_ordine,
        b.data_validita,
        f.ora_ingresso,
        COALESCE(e.titolo, 'Ingresso museo') AS titolo_esposizione
     FROM Biglietti b
     INNER JOIN Ordini o ON o.id_ordine = b.id_ordine
     LEFT JOIN Fasce_Orarie f ON f.id_fascia = b.id_fascia
     LEFT JOIN Esposizioni e ON e.id_esposizione = f.id_esposizione
     LEFT JOIN Biglietti b2 ON b2.id_ordine = o.id_ordine
     WHERE o.id_utente = ?
       AND b.stato = 'Valido'
       AND o.stato_pagamento = 'Pagato'
       AND COALESCE(o.stato_rimborso, 'Nessuno') <> 'Accettato'
       AND b.data_validita >= CURDATE()
     GROUP BY o.id_ordine, b.data_validita, f.ora_ingresso, e.titolo
     ORDER BY b.data_validita ASC, f.ora_ingresso ASC
     LIMIT 1"
);
$stmtProssimaVisita->execute([$_SESSION['utente_id']]);
$prossimaVisita = $stmtProssimaVisita->fetch();
$ultimoOrdineDashboard = $ultimiOrdini[0] ?? null;

$notificheAccount = [];
if ($prossimaVisita) {
    $giorniAllaVisita = (int)floor((strtotime($prossimaVisita['data_validita']) - strtotime(date('Y-m-d'))) / 86400);
    if ($giorniAllaVisita === 0) {
        $notificheAccount[] = ['📍', 'La tua visita è oggi', 'Mostra il QR code all’ingresso e conserva il biglietto digitale.'];
    } elseif ($giorniAllaVisita > 0 && $giorniAllaVisita <= 7) {
        $notificheAccount[] = ['📅', 'Visita in arrivo', 'La prossima visita è tra ' . $giorniAllaVisita . ' giorn' . ($giorniAllaVisita === 1 ? 'o' : 'i') . '.'];
    }
}
if ($dashboardStats['rimborsi_attivi'] > 0) {
    $notificheAccount[] = ['↩️', 'Rimborso in valutazione', 'Hai ' . $dashboardStats['rimborsi_attivi'] . ' richiesta/e di rimborso in attesa di esito.'];
}
if ($ultimoOrdineDashboard && ($ultimoOrdineDashboard['stato_pagamento'] ?? '') !== 'Pagato') {
    $notificheAccount[] = ['💳', 'Pagamento da completare', 'Il tuo ultimo ordine risulta ancora da saldare.'];
}
if ($dashboardStats['biglietti_validi'] > 0) {
    $notificheAccount[] = ['🎟️', 'Biglietti disponibili', 'Hai ' . $dashboardStats['biglietti_validi'] . ' biglietto/i validi nel tuo account.'];
}
$statoVisitaDashboard = [
    'icona' => '🏛️',
    'titolo' => 'Nessuna visita programmata',
    'testo' => 'Scopri le esposizioni disponibili e prenota il tuo prossimo percorso nel museo.',
    'azione' => 'Scopri le mostre',
    'link' => SITE_URL . '/esposizioni.php',
    'classe' => 'neutral',
];
if ($prossimaVisita) {
    $giorni = (int)floor((strtotime((string)$prossimaVisita['data_validita']) - strtotime(date('Y-m-d'))) / 86400);
    if ($giorni === 0) {
        $statoVisitaDashboard = [
            'icona' => '📍',
            'titolo' => 'La tua visita è oggi',
            'testo' => 'Presentati all’ingresso con il QR code del biglietto. Orario: ' . ($prossimaVisita['ora_ingresso'] ? date('H:i', strtotime((string)$prossimaVisita['ora_ingresso'])) : 'ingresso libero') . '.',
            'azione' => 'Apri biglietti',
            'link' => SITE_URL . '/ordine_dettaglio.php?id=' . (int)$prossimaVisita['id_ordine'],
            'classe' => 'today',
        ];
    } elseif ($giorni === 1) {
        $statoVisitaDashboard = [
            'icona' => '⏰',
            'titolo' => 'Visita prevista per domani',
            'testo' => 'Controlla data, orario e numero di biglietti. Tieni pronto il QR code per l’ingresso.',
            'azione' => 'Visualizza dettagli',
            'link' => SITE_URL . '/ordine_dettaglio.php?id=' . (int)$prossimaVisita['id_ordine'],
            'classe' => 'soon',
        ];
    } else {
        $statoVisitaDashboard = [
            'icona' => '📅',
            'titolo' => 'Prossima visita tra ' . $giorni . ' giorni',
            'testo' => (string)$prossimaVisita['titolo_esposizione'] . ' · ' . date('d/m/Y', strtotime((string)$prossimaVisita['data_validita'])) . '.',
            'azione' => 'Apri promemoria',
            'link' => SITE_URL . '/ordine_dettaglio.php?id=' . (int)$prossimaVisita['id_ordine'],
            'classe' => 'planned',
        ];
    }
}

$notificheAccount = array_slice($notificheAccount, 0, 3);

// Gestione aggiornamento profilo
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Token di sicurezza non valido.';
    } elseif ($_POST['action'] === 'update_profile') {
        $nome    = trim($_POST['nome']    ?? '');
        $cognome = trim($_POST['cognome'] ?? '');
        $email   = trim($_POST['email']   ?? '');

        if (!$nome || !$cognome || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Controlla i dati inseriti.';
        } else {
            // Controlla email duplicata (escludi utente corrente)
            $check = $pdo->prepare('SELECT id_utente FROM Utenti WHERE email = ? AND id_utente != ?');
            $check->execute([$email, $_SESSION['utente_id']]);
            if ($check->fetch()) {
                $errorMsg = 'Email già in uso da un altro account.';
            } else {
                $upd = $pdo->prepare('UPDATE Utenti SET nome=?, cognome=?, email=? WHERE id_utente=?');
                $upd->execute([$nome, $cognome, $email, $_SESSION['utente_id']]);
                $_SESSION['utente_nome']  = $nome;
                $_SESSION['utente_email'] = $email;
                $successMsg = 'Profilo aggiornato con successo.';
                // Rileggi utente
                $stmt->execute([$_SESSION['utente_id']]);
                $utente = $stmt->fetch();
$saldoUtente = saldoUtenteCorrente($pdo, (int)$_SESSION['utente_id']);
            }
        }
    } elseif ($_POST['action'] === 'ricarica_portafoglio') {
        // La ricarica non viene più accreditata direttamente da questa pagina:
        // deve passare dal pagamento simulato dedicato al portafoglio.
        $importo = (float)str_replace(',', '.', $_POST['importo'] ?? '0');
        $metodo = $_POST['metodo_pagamento'] ?? 'carta';
        if ($importo <= 0 || $importo > 500 || !in_array($metodo, ['carta', 'paypal'], true)) {
            $errorMsg = 'Inserisci un importo valido tra 1 e 500 euro e scegli carta o PayPal.';
        } else {
            $_SESSION['wallet_topup_prefill'] = [
                'importo' => number_format($importo, 2, '.', ''),
                'metodo' => $metodo,
                'created_at' => time(),
            ];
            header('Location: ' . SITE_URL . '/pagamento.php?ricarica_portafoglio=1');
            exit;
        }
    } elseif ($_POST['action'] === 'change_password') {
        $attuale  = $_POST['pw_attuale']  ?? '';
        $nuova    = $_POST['pw_nuova']    ?? '';
        $conferma = $_POST['pw_conferma'] ?? '';

        if (!password_verify($attuale, $utente['password_hash'])) {
            $errorMsg = 'La password attuale non è corretta.';
        } elseif (strlen($nuova) < 8) {
            $errorMsg = 'La nuova password deve avere almeno 8 caratteri.';
        } elseif ($nuova !== $conferma) {
            $errorMsg = 'Le nuove password non coincidono.';
        } else {
            $hash = password_hash($nuova, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd  = $pdo->prepare('UPDATE Utenti SET password_hash=? WHERE id_utente=?');
            $upd->execute([$hash, $_SESSION['utente_id']]);
            $successMsg = 'Password aggiornata con successo.';
        }
    }
}

$ruoloLabel = ['visitatore' => 'Visitatore', 'operatore' => 'Operatore', 'cassiere' => 'Cassiere', 'amministratore' => 'Amministratore', 'tester' => 'Tester'];

include __DIR__ . '/header.php';
?>

<style>
  @media (min-width: 1024px) {
    .account-tab-area { min-height: 560px; }
    .account-tab-area > .tab-content > .bg-white { min-height: 560px; }
  }

  .account-dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.25rem;
    background:
      radial-gradient(circle at top right, rgba(142, 197, 232, 0.22), transparent 34%),
      linear-gradient(135deg, #102744 0%, #193a5c 55%, #102744 100%);
    color: #fffdf5;
    box-shadow: 0 18px 40px rgba(16, 39, 68, 0.18);
  }

  .account-dashboard-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background-image: linear-gradient(135deg, rgba(255,255,255,.06) 8.33%, transparent 8.33%, transparent 50%, rgba(255,255,255,.06) 50%, rgba(255,255,255,.06) 58.33%, transparent 58.33%, transparent 100%);
    background-size: 18px 18px;
    opacity: .22;
    pointer-events: none;
  }

  .account-dashboard-hero > * { position: relative; z-index: 1; }

  .account-dashboard-stat {
    border: 1px solid rgba(142, 197, 232, 0.22);
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    transition: transform .18s ease, box-shadow .18s ease;
  }

  .account-dashboard-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 16px 34px rgba(16, 39, 68, 0.10);
  }

  .account-dashboard-icon {
    width: 2.75rem;
    height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: #e8f5fc;
    font-size: 1.35rem;
  }

  .account-visit-card {
    border: 1px solid rgba(142, 197, 232, 0.28);
    background:
      radial-gradient(circle at top left, rgba(215, 168, 79, 0.13), transparent 30%),
      #ffffff;
  }

  .account-order-row {
    transition: background .16s ease;
  }

  .account-order-row:hover {
    background: #f8fbfd;
  }

  .account-notification-card {
    border: 1px solid rgba(142, 197, 232, .25);
    background: linear-gradient(180deg, #ffffff, #fbfdff);
  }

  .account-visit-status {
    border: 1px solid rgba(142, 197, 232, 0.32);
    background: linear-gradient(135deg, #ffffff, #f5fbff);
  }

  .account-visit-status.today {
    background: linear-gradient(135deg, #e8f7ee, #ffffff);
    border-color: rgba(22, 101, 52, .18);
  }

  .account-visit-status.soon,
  .account-visit-status.planned {
    background: linear-gradient(135deg, #eef8ff, #ffffff);
  }

  .account-timeline-step {
    position: relative;
    padding-left: 2.25rem;
  }

  .account-timeline-step::before {
    content: "";
    position: absolute;
    left: .62rem;
    top: 1.65rem;
    bottom: -.8rem;
    width: 2px;
    background: #dbeaf3;
  }

  .account-timeline-step:last-child::before { display: none; }

  .account-timeline-dot {
    position: absolute;
    left: 0;
    top: .05rem;
    width: 1.35rem;
    height: 1.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #102744;
    color: #fffdf5;
    font-size: .72rem;
    font-weight: 900;
  }


  .account-action-card {
    border: 1px solid rgba(142, 197, 232, 0.28);
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }

  .account-action-card:hover,
  .account-action-card:focus-visible {
    transform: translateY(-2px);
    border-color: rgba(142, 197, 232, 0.62);
    box-shadow: 0 16px 34px rgba(16, 39, 68, 0.10);
    outline: none;
  }

  .account-section-back {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border: 1px solid rgba(142, 197, 232, 0.36);
    border-radius: 999px;
    padding: .45rem .8rem;
    color: #102744;
    background: #f5fbff;
    font-size: .78rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .account-section-back:hover,
  .account-section-back:focus-visible {
    background: #e8f5fc;
    outline: 2px solid rgba(142, 197, 232, 0.38);
    outline-offset: 2px;
  }


  .account-section-card {
    overflow: hidden;
    border: 1px solid rgba(142,197,232,.26);
    border-radius: 1.35rem;
    background: #fff;
    box-shadow: 0 16px 38px rgba(16, 39, 68, .08);
  }

  .account-section-hero {
    background: linear-gradient(135deg, #102744, #193a5c);
    color: #fffdf5;
    padding: 1.25rem 1.5rem;
  }

  .account-section-hero p { color: rgba(255,253,245,.76); }

  .account-form-panel {
    background: linear-gradient(180deg, #ffffff, #fbfdff);
  }

  .account-pretty-input {
    border: 1px solid #dbeaf3 !important;
    background: #f9fcff;
    border-radius: 1rem !important;
  }

  .account-pretty-input:focus {
    border-color: #8EC5E8 !important;
    box-shadow: 0 0 0 3px rgba(142,197,232,.22) !important;
  }

  .account-password-tip,
  .account-wallet-tip {
    border: 1px solid rgba(142,197,232,.24);
    background: #f7fbff;
    color: #334155;
    border-radius: 1rem;
  }

  .account-orders-modern {
    display: grid;
    gap: .85rem;
  }

  .account-order-modern-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    border: 1px solid rgba(142,197,232,.24);
    border-radius: 1.15rem;
    background: linear-gradient(180deg,#fff,#fbfdff);
    padding: 1rem;
    transition: transform .16s ease, box-shadow .16s ease;
  }

  .account-order-modern-row:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(16,39,68,.08);
  }

  .account-order-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: .25rem .6rem;
    font-size: .72rem;
    font-weight: 800;
    background: #e8f5fc;
    color: #102744;
  }

  @media (max-width: 640px) {
    .account-section-hero { padding: 1rem; }
    .account-order-modern-row { grid-template-columns: 1fr; }
  }

  @media (max-width: 640px) {
    .account-dashboard-hero { border-radius: 1rem; }
    .account-dashboard-stat { padding: 1rem !important; }
    .account-dashboard-icon { width: 2.35rem; height: 2.35rem; border-radius: .85rem; }
  }
</style>

<!-- Breadcrumb -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Il mio account</span>
  </div>
</div>

<!-- header account -->
<section class="bg-antracite py-12">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 sm:gap-6 fade-up text-center sm:text-left">
      <!-- Avatar -->
      <div class="w-20 h-20 rounded-full bg-oro flex items-center justify-center text-antracite font-bold text-3xl font-display flex-shrink-0">
        <?= strtoupper(substr($utente['nome'], 0, 1)) ?>
      </div>
      <div>
        <h1 class="font-display text-avorio text-2xl md:text-3xl font-bold">
          <?= clean($utente['nome']) ?> <?= clean($utente['cognome']) ?>
        </h1>
        <p class="text-gray-400 font-body text-sm mt-1 break-all"><?= clean($utente['email']) ?></p>
        <span class="inline-block mt-2 px-3 py-1 text-xs font-body font-bold uppercase tracking-wide rounded-full
          <?= in_array($utente['ruolo'], ['amministratore','tester'], true) ? 'bg-oro text-antracite' : ($utente['ruolo'] === 'operatore' ? 'bg-acciaio text-white' : 'bg-gray-600 text-white') ?>">
          <?= clean($ruoloLabel[$utente['ruolo']] ?? $utente['ruolo']) ?>
        </span>
      </div>
      <div class="ml-auto hidden md:flex items-stretch gap-3 text-right">
        <div class="rounded-2xl border border-oro/30 bg-antracite-light/60 px-4 py-3">
          <span class="block text-gray-500 text-xs font-body uppercase tracking-wide">Saldo portafoglio</span>
          <span class="block text-oro font-display text-xl font-bold">€ <?= number_format((float)$saldoUtente, 2, ',', '.') ?></span>
        </div>
        <div class="rounded-2xl border border-avorio/10 bg-antracite-light/40 px-4 py-3">
          <span class="block text-gray-500 text-xs font-body uppercase tracking-wide">Membro dal</span>
          <span class="block text-oro font-display text-lg"><?= date('M Y', strtotime($utente['data_registrazione'])) ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- contenuto dei tabs + msg errore o todo bien-->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

  <?php if ($successMsg): ?>
  <div class="alert-success floating-alert p-4 rounded mb-6 text-sm font-body fade-up" role="status"> <?= clean($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
  <div class="alert-error floating-alert p-4 rounded mb-6 text-sm font-body fade-up" role="alert"> <?= clean($errorMsg) ?></div>
  <?php endif; ?>

  <div class="space-y-6 account-tab-area">

      <!-- dashboard utente -->
      <div id="tab-dashboard" class="tab-content">
        <div class="space-y-6">
          <section class="account-dashboard-hero p-5 sm:p-6 lg:p-7">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
              <div>
                <p class="text-xs uppercase tracking-[0.22em] text-acciaio font-body font-bold mb-2">Area personale</p>
                <h2 class="font-display text-2xl sm:text-3xl font-bold leading-tight">Ciao, <?= clean($utente['nome']) ?>.</h2>
                <p class="text-sm sm:text-base text-avorio/80 mt-2 max-w-2xl">Da qui controlli biglietti, ordini, portafoglio virtuale e richieste di rimborso in un unico riepilogo.</p>
              </div>
              <div class="rounded-2xl p-4 min-w-[210px]" style="background: rgba(255,255,255,.94); border: 1px solid rgba(255,255,255,.72); box-shadow: 0 14px 30px rgba(7, 23, 40, .18);">
                <p class="text-xs uppercase tracking-widest mb-1" style="color: #335b82;">Saldo disponibile</p>
                <p class="font-display text-3xl font-bold" style="color: #102744;">€ <?= number_format((float)$saldoUtente, 2, ',', '.') ?></p>
                <button type="button" data-account-tab="portafoglio" aria-pressed="false" class="inline-flex mt-3 px-4 py-2 rounded-lg bg-acciaio text-antracite text-xs font-body font-bold uppercase tracking-wide hover:bg-oro transition-colors">Ricarica saldo</button>
              </div>
            </div>
          </section>

          <section class="account-visit-status <?= clean($statoVisitaDashboard['classe']) ?> rounded-2xl shadow-sm p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" aria-label="Stato visita">
            <div class="flex items-start gap-3">
              <span class="inline-flex w-12 h-12 rounded-2xl bg-avorio items-center justify-center text-2xl" aria-hidden="true"><?= clean($statoVisitaDashboard['icona']) ?></span>
              <div>
                <p class="text-xs uppercase tracking-widest text-oro font-body font-bold mb-1">Stato visita</p>
                <h3 class="font-display text-xl font-bold text-antracite"><?= clean($statoVisitaDashboard['titolo']) ?></h3>
                <p class="text-sm text-gray-600 mt-1"><?= clean($statoVisitaDashboard['testo']) ?></p>
              </div>
            </div>
            <a href="<?= clean($statoVisitaDashboard['link']) ?>" class="btn-outline rounded px-4 py-2 text-xs uppercase tracking-wide text-center shrink-0"><?= clean($statoVisitaDashboard['azione']) ?></a>
          </section>

          <?php if (!empty($notificheAccount)): ?>
            <section class="grid grid-cols-1 md:grid-cols-<?= min(3, max(1, count($notificheAccount))) ?> gap-3" aria-label="Notifiche account">
              <?php foreach ($notificheAccount as $notifica): ?>
                <article class="account-notification-card rounded-2xl p-4 flex items-start gap-3 shadow-sm">
                  <span class="inline-flex w-10 h-10 rounded-2xl bg-avorio items-center justify-center text-lg" aria-hidden="true"><?= $notifica[0] ?></span>
                  <div>
                    <h3 class="font-body font-bold text-antracite text-sm"><?= clean($notifica[1]) ?></h3>
                    <p class="text-xs text-gray-500 mt-1"><?= clean($notifica[2]) ?></p>
                  </div>
                </article>
              <?php endforeach; ?>
            </section>
          <?php endif; ?>

          <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php foreach ([
              ['🎟️', 'Biglietti validi', $dashboardStats['biglietti_validi'], 'Ancora utilizzabili'],
              ['📦', 'Ordini totali', $dashboardStats['ordini_totali'], 'Prenotazioni effettuate'],
              ['✅', 'Ordini pagati', $dashboardStats['ordini_pagati'], 'Pagamenti completati'],
              ['↩️', 'Rimborsi', $dashboardStats['rimborsi_attivi'], 'Richieste in attesa'],
            ] as $stat): ?>
              <article class="account-dashboard-stat rounded-2xl p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                  <span class="account-dashboard-icon" aria-hidden="true"><?= $stat[0] ?></span>
                  <span class="font-display text-3xl font-bold text-antracite"><?= (int)$stat[2] ?></span>
                </div>
                <h3 class="font-body font-bold text-antracite mt-4"><?= clean($stat[1]) ?></h3>
                <p class="text-xs text-gray-500 mt-1"><?= clean($stat[3]) ?></p>
              </article>
            <?php endforeach; ?>
          </section>

          <section class="grid grid-cols-1 gap-6">
            <div class="account-visit-card rounded-2xl shadow-sm overflow-hidden">
              <div class="px-5 py-4 border-b border-avorio-dark flex items-center justify-between gap-3">
                <h3 class="font-display text-xl font-semibold text-antracite">La tua prossima visita</h3>
                <span class="text-xs uppercase tracking-wide text-oro font-body font-bold">Promemoria</span>
              </div>
              <div class="p-5">
                <?php if ($prossimaVisita): ?>
                  <p class="text-xs uppercase tracking-widest text-gray-400 font-body mb-2">Ordine #<?= (int)$prossimaVisita['id_ordine'] ?></p>
                  <h4 class="font-display text-2xl font-bold text-antracite"><?= clean($prossimaVisita['titolo_esposizione']) ?></h4>
                  <div class="grid sm:grid-cols-3 gap-3 mt-5">
                    <div class="rounded-xl bg-avorio p-3">
                      <p class="text-xs text-gray-500">Data</p>
                      <p class="font-body font-bold text-antracite"><?= date('d/m/Y', strtotime($prossimaVisita['data_validita'])) ?></p>
                    </div>
                    <div class="rounded-xl bg-avorio p-3">
                      <p class="text-xs text-gray-500">Orario</p>
                      <p class="font-body font-bold text-antracite"><?= $prossimaVisita['ora_ingresso'] ? date('H:i', strtotime($prossimaVisita['ora_ingresso'])) : 'Libero' ?></p>
                    </div>
                    <div class="rounded-xl bg-avorio p-3">
                      <p class="text-xs text-gray-500">Biglietti</p>
                      <p class="font-body font-bold text-antracite"><?= (int)$prossimaVisita['num_biglietti_ordine'] ?></p>
                    </div>
                  </div>
                  <a href="<?= SITE_URL ?>/ordine_dettaglio.php?id=<?= (int)$prossimaVisita['id_ordine'] ?>" class="btn-oro inline-flex mt-5 px-5 py-2.5 rounded font-body text-sm uppercase tracking-wide">Visualizza biglietti</a>
                <?php else: ?>
                  <div class="text-center py-6">
                    <div class="text-5xl mb-3" aria-hidden="true">🏛️</div>
                    <h4 class="font-display text-xl font-bold text-antracite">Nessuna visita programmata</h4>
                    <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">Scopri le esposizioni disponibili e prenota il tuo prossimo percorso nel museo.</p>
                    <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro inline-flex mt-5 px-5 py-2.5 rounded font-body text-sm uppercase tracking-wide">Scopri le mostre</a>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-avorio-dark p-5">
              <h3 class="font-display text-xl font-semibold text-antracite mb-4">Stato ultimo ordine</h3>
              <?php if ($ultimoOrdineDashboard): ?>
                <?php
                  $ultimoRimborsato = strcasecmp((string)($ultimoOrdineDashboard['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0;
                  $ultimoRimborsoRichiesto = strcasecmp((string)($ultimoOrdineDashboard['stato_rimborso'] ?? 'Nessuno'), 'Richiesto') === 0;
                  $ultimoPagato = ($ultimoOrdineDashboard['stato_pagamento'] ?? '') === 'Pagato';
                ?>
                <p class="text-sm text-gray-500 mb-4">Ordine #<?= (int)$ultimoOrdineDashboard['id_ordine'] ?> · € <?= number_format((float)$ultimoOrdineDashboard['importo_totale'], 2, ',', '.') ?></p>
                <div class="space-y-4">
                  <div class="account-timeline-step">
                    <span class="account-timeline-dot">✓</span>
                    <p class="font-body font-bold text-antracite text-sm">Ordine creato</p>
                    <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($ultimoOrdineDashboard['data_acquisto'])) ?></p>
                  </div>
                  <div class="account-timeline-step">
                    <span class="account-timeline-dot"><?= $ultimoPagato ? '✓' : '!' ?></span>
                    <p class="font-body font-bold text-antracite text-sm"><?= $ultimoPagato ? 'Pagamento completato' : 'Pagamento da completare' ?></p>
                    <p class="text-xs text-gray-500"><?= clean($ultimoOrdineDashboard['stato_pagamento'] ?? 'In attesa') ?></p>
                  </div>
                  <div class="account-timeline-step">
                    <span class="account-timeline-dot"><?= $ultimoRimborsato ? '×' : '✓' ?></span>
                    <p class="font-body font-bold text-antracite text-sm"><?= $ultimoRimborsato ? 'Ordine rimborsato' : 'Biglietti nello storico' ?></p>
                    <p class="text-xs text-gray-500"><?= $ultimoRimborsato ? 'I biglietti non sono più utilizzabili.' : ((int)$ultimoOrdineDashboard['num_biglietti'] . ' bigliett' . ((int)$ultimoOrdineDashboard['num_biglietti'] === 1 ? 'o' : 'i')) ?></p>
                  </div>
                  <?php if ($ultimoRimborsoRichiesto): ?>
                  <div class="account-timeline-step">
                    <span class="account-timeline-dot">…</span>
                    <p class="font-body font-bold text-antracite text-sm">Rimborso in valutazione</p>
                    <p class="text-xs text-gray-500">Riceverai l’esito via email.</p>
                  </div>
                  <?php endif; ?>
                </div>
                <a href="<?= SITE_URL ?>/ordine_dettaglio.php?id=<?= (int)$ultimoOrdineDashboard['id_ordine'] ?>" class="inline-flex mt-5 text-sm text-oro font-body font-bold hover:underline">Apri dettaglio ordine →</a>
              <?php else: ?>
                <div class="mss-empty-state compact">
                  <span aria-hidden="true">🧭</span>
                  <strong>Nessun ordine recente</strong>
                  <p>Quando effettuerai una prenotazione, qui vedrai l’avanzamento dell’ordine.</p>
                </div>
              <?php endif; ?>
            </div>
          </section>

          <section class="bg-white rounded-2xl shadow-sm border border-avorio-dark overflow-hidden">
            <div class="px-5 py-4 border-b border-avorio-dark flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <h3 class="font-display text-xl font-semibold text-antracite">Ultimi ordini</h3>
              <a href="<?= SITE_URL ?>/ordini.php" class="text-xs text-oro hover:underline font-body font-bold uppercase tracking-wide">Vedi tutti →</a>
            </div>
            <?php if (empty($ultimiOrdini)): ?>
              <div class="px-5 py-8">
                <div class="mss-empty-state compact">
                  <span aria-hidden="true">🎟️</span>
                  <strong>Nessun ordine ancora</strong>
                  <p>Scopri le esposizioni e prenota la tua prima visita.</p>
                  <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro inline-flex mt-3 px-5 py-2 rounded text-sm font-bold">Scopri le mostre</a>
                </div>
              </div>
            <?php else: ?>
              <div class="divide-y divide-avorio-dark">
                <?php foreach (array_slice($ultimiOrdini, 0, 3) as $ord): ?>
                  <?php $ordineRimborsatoDash = strcasecmp((string)($ord['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0; ?>
                  <a href="<?= SITE_URL ?>/ordine_dettaglio.php?id=<?= (int)$ord['id_ordine'] ?>" class="account-order-row flex items-center justify-between gap-4 px-5 py-4">
                    <div class="min-w-0">
                      <p class="font-body text-sm font-bold text-antracite">Ordine #<?= (int)$ord['id_ordine'] ?></p>
                      <p class="text-xs text-gray-400 mt-0.5"><?= date('d/m/Y H:i', strtotime($ord['data_acquisto'])) ?> · <?= (int)$ord['num_biglietti'] ?> bigliett<?= $ord['num_biglietti'] == 1 ? 'o' : 'i' ?></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                      <p class="font-display text-lg font-bold text-oro">€ <?= number_format((float)$ord['importo_totale'], 2, ',', '.') ?></p>
                      <p class="text-xs <?= $ordineRimborsatoDash ? 'text-red-700' : 'text-gray-500' ?> font-bold"><?= $ordineRimborsatoDash ? 'Rimborsato' : clean($ord['stato_pagamento']) ?></p>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>

          <section class="bg-white rounded-2xl shadow-sm border border-avorio-dark overflow-hidden">
            <div class="px-5 py-4 border-b border-avorio-dark">
              <p class="text-xs uppercase tracking-[0.20em] text-acciaio font-body font-bold mb-1">Azioni rapide</p>
              <h3 class="font-display text-xl font-semibold text-antracite">Cosa vuoi fare?</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 p-5">
              <?php foreach ([
                ['profilo', '👤', 'Profilo personale', 'Modifica nome, cognome ed email.'],
                ['portafoglio', '💰', 'Portafoglio virtuale', 'Controlla il saldo e ricarica.'],
                ['ordini', '📦', 'I miei ordini', 'Apri ordini, biglietti e ricevute.'],
                ['sicurezza', '🔐', 'Sicurezza', 'Aggiorna la password dell’account.'],
              ] as $azione): ?>
                <button type="button" data-account-tab="<?= $azione[0] ?>" aria-pressed="false" class="account-action-card rounded-2xl p-5 text-left shadow-sm">
                  <span class="account-dashboard-icon" aria-hidden="true"><?= $azione[1] ?></span>
                  <span class="block font-body font-bold text-antracite mt-4"><?= clean($azione[2]) ?></span>
                  <span class="block text-xs text-gray-500 mt-1"><?= clean($azione[3]) ?></span>
                </button>
              <?php endforeach; ?>
            </div>
            <div class="px-5 py-4 border-t border-avorio-dark flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between bg-avorio/40">
              <a href="<?= SITE_URL ?>/logout.php" class="inline-flex justify-center rounded-xl px-4 py-2 text-sm font-body font-bold text-red-600 hover:bg-red-50 transition-colors">Logout</a>
              <a href="<?= SITE_URL ?>/elimina_account.php" class="inline-flex justify-center rounded-xl px-4 py-2 text-sm font-body font-bold text-red-800 bg-red-100 hover:bg-red-200 transition-colors">Elimina account</a>
            </div>
          </section>
        </div>
      </div>

      <!-- sezione profilo -->
      <div id="tab-profilo" class="tab-content hidden">
        <div class="account-section-card">
          <div class="account-section-hero flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div><p class="text-xs uppercase tracking-widest font-bold mb-1">Dati utente</p><h2 class="font-display text-2xl font-semibold">Il mio profilo</h2></div>
            <button type="button" data-account-tab="dashboard" aria-pressed="false" class="account-section-back">← Dashboard</button>
          </div>
          <form method="POST" class="account-form-panel px-4 sm:px-6 py-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_profile">
            <div class="account-wallet-tip p-4 text-sm">Aggiorna i dati principali del tuo account. L’email viene usata per conferme ordine, recupero password e comunicazioni sui rimborsi.</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">Nome</label>
                <input type="text" name="nome" value="<?= clean($utente['nome']) ?>"
                       required class="w-full px-4 py-3 account-pretty-input border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
              </div>
              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">Cognome</label>
                <input type="text" name="cognome" value="<?= clean($utente['cognome']) ?>"
                       required class="w-full px-4 py-3 account-pretty-input border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
              </div>
            </div>

            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Email</label>
              <input type="email" name="email" value="<?= clean($utente['email']) ?>"
                     required class="w-full px-4 py-3 account-pretty-input border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
            </div>

            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Ruolo</label>
              <input type="text" value="<?= clean($ruoloLabel[$utente['ruolo']] ?? $utente['ruolo']) ?>"
                     disabled class="w-full px-4 py-3 account-pretty-input border border-gray-100 bg-gray-50 rounded-lg font-body text-sm text-gray-400 cursor-not-allowed"/>
            </div>

            <button type="submit" class="btn-oro w-full sm:w-auto px-6 py-2.5 rounded font-body text-sm uppercase tracking-wide">
              Salva modifiche
            </button>
          </form>
        </div>
      </div>



      <!-- sezione portafoglio -->
      <div id="tab-portafoglio" class="tab-content hidden">
        <div class="account-section-card">
          <div class="account-section-hero flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-widest font-bold mb-1">Saldo e ricariche</p>
              <h2 class="font-display text-2xl font-semibold">Portafoglio virtuale</h2>
              <p class="text-sm mt-1">Usa il saldo per pagare biglietti e servizi opzionali.</p>
            </div>
            <button type="button" data-account-tab="dashboard" aria-pressed="false" class="account-section-back">← Dashboard</button>
          </div>
          <div class="account-form-panel px-4 sm:px-6 py-6 space-y-6">
            <div class="wallet-balance-card rounded-2xl bg-antracite text-avorio p-6">
              <p class="text-xs uppercase tracking-widest text-oro mb-2">Saldo disponibile</p>
              <p class="font-display text-4xl font-bold">€ <?= number_format((float)$saldoUtente, 2, ',', '.') ?></p>
            </div>

            <form method="POST" action="<?= SITE_URL ?>/pagamento.php" class="wallet-recharge-form space-y-5 bg-white rounded-2xl border border-avorio-dark p-4 sm:p-5">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="avvia_ricarica_portafoglio" value="1">

              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">Importo ricarica</label>
                <input type="number" name="importo" min="1" max="500" step="0.01" required placeholder="25.00"
                       class="w-full px-4 py-3 account-pretty-input border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
              </div>

              <div class="payment-method-picker">
                <span class="block text-sm font-body font-bold text-antracite mb-2">Metodo di ricarica</span>
                <div class="payment-method-grid wallet-payment-grid">
                  <label class="payment-method-option">
                    <input type="radio" name="metodo_pagamento" value="carta" checked required>
                    <span>
                      <strong>Carta di credito</strong>
                      <small>Pagamento simulato con dati carta.</small>
                    </span>
                  </label>
                  <label class="payment-method-option">
                    <input type="radio" name="metodo_pagamento" value="paypal" required>
                    <span>
                      <strong>PayPal</strong>
                      <small>Simulazione accesso PayPal.</small>
                    </span>
                  </label>
                </div>
              </div>

              <button type="submit" class="btn-oro w-full px-6 py-3 rounded font-body text-sm uppercase tracking-wide">Vai al pagamento</button>
              <p class="text-xs text-gray-500">La ricarica viene accreditata solo dopo il pagamento simulato.</p>
            </form>
          </div>
        </div>
      </div>

      <!-- sezione sicurezza -->
      <div id="tab-sicurezza" class="tab-content hidden">
        <div class="account-section-card">
          <div class="account-section-hero flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div><p class="text-xs uppercase tracking-widest font-bold mb-1">Sicurezza account</p><h2 class="font-display text-2xl font-semibold">Cambia password</h2><p class="text-sm mt-1">Scegli una password sicura e diversa da quelle già usate.</p></div>
            <button type="button" data-account-tab="dashboard" aria-pressed="false" class="account-section-back">← Dashboard</button>
          </div>
          <form method="POST" class="account-form-panel px-4 sm:px-6 py-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="account-password-tip p-4 text-sm">Consiglio: usa almeno 8 caratteri, alternando lettere, numeri e simboli. Non condividere mai la password con altri.</div>

            <?php foreach ([
              ['pw_attuale','Password attuale','current-password'],
              ['pw_nuova','Nuova password','new-password'],
              ['pw_conferma','Conferma nuova password','new-password'],
            ] as $f): ?>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1"><?= $f[1] ?></label>
              <input type="password" name="<?= $f[0] ?>" autocomplete="<?= $f[2] ?>" required
                     class="w-full px-4 py-3 account-pretty-input border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-oro w-full sm:w-auto px-6 py-2.5 rounded font-body text-sm uppercase tracking-wide">
              Aggiorna password
            </button>
          </form>
        </div>
      </div>

      <!-- sez ordini -->
      <div id="tab-ordini" class="tab-content hidden">
        <div class="account-section-card">
          <div class="account-section-hero flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div><p class="text-xs uppercase tracking-widest font-bold mb-1">Storico prenotazioni</p><h2 class="font-display text-2xl font-semibold">I miei ordini</h2><p class="text-sm mt-1">Consulta biglietti, ricevute, pagamenti e rimborsi.</p></div>
            <div class="flex items-center gap-3">
              <a href="<?= SITE_URL ?>/ordini.php" class="text-xs text-oro hover:underline font-body">Vedi tutti →</a>
              <button type="button" data-account-tab="dashboard" aria-pressed="false" class="account-section-back">← Dashboard</button>
            </div>
          </div>

          <?php if (empty($ultimiOrdini)): ?>
          <div class="px-6 py-12">
            <div class="mss-empty-state">
              <span aria-hidden="true">📦</span>
              <strong>Nessun ordine ancora</strong>
              <p>Quando prenoterai una visita, troverai qui biglietti, ricevute e stato dei pagamenti.</p>
              <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro inline-flex mt-4 px-6 py-2.5 rounded font-body text-sm uppercase tracking-wide">Scopri le mostre</a>
            </div>
          </div>
          <?php else: ?>
          <div class="account-orders-modern p-4 sm:p-6">
            <?php foreach ($ultimiOrdini as $ord): ?>
            <div class="account-order-modern-row">
              <div>
                <div class="font-body text-sm font-bold text-antracite flex items-center gap-2"><span class="account-order-badge">ORDINE</span> #<?= (int)$ord['id_ordine'] ?></div>
                <div class="text-xs text-gray-400 mt-0.5">
                  <?= date('d/m/Y H:i', strtotime($ord['data_acquisto'])) ?>
                  · <?= (int)$ord['num_biglietti'] ?> bigliett<?= $ord['num_biglietti'] == 1 ? 'o' : 'i' ?>
                </div>
              </div>
              <div class="text-right flex-shrink-0">
                <div class="font-display text-lg font-bold text-oro">€<?= number_format($ord['importo_totale'], 2, ',', '.') ?></div>
                <?php $ordineRimborsato = strcasecmp((string)($ord['stato_rimborso'] ?? 'Nessuno'), 'Accettato') === 0; ?>
                <?php if ($ordineRimborsato): ?>
                  <div class="text-xs text-red-700 font-bold mt-1">Rimborsato</div>
                <?php endif; ?>
                <div class="flex flex-col items-end gap-1">
                  <a href="ordine_dettaglio.php?id=<?= (int)$ord['id_ordine'] ?>"
                     class="text-xs text-acciaio hover:text-oro transition-colors font-body">Dettagli →</a>
                  <?php if (!$ordineRimborsato && ($ord['stato_pagamento'] ?? '') === 'Non pagato'): ?>
                    <a href="<?= SITE_URL ?>/pagamento.php?ordine=<?= (int)$ord['id_ordine'] ?>"
                       class="text-xs text-oro hover:underline font-body font-bold">Paga →</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

  </div>
</main>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(function (el) {
      el.classList.add('hidden');
    });

    document.querySelectorAll('[data-account-tab]').forEach(function (el) {
      el.classList.remove('ring-2', 'ring-acciaio');
      el.setAttribute('aria-pressed', 'false');
    });

    var panel = document.getElementById('tab-' + name);
    if (panel) panel.classList.remove('hidden');

    document.querySelectorAll('[data-account-tab="' + name + '"]').forEach(function (btn) {
      btn.classList.add('ring-2', 'ring-acciaio');
      btn.setAttribute('aria-pressed', 'true');
    });

    if (name !== 'dashboard' && panel) {
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  document.querySelectorAll('[data-account-tab]').forEach(function (button) {
    button.addEventListener('click', function () {
      showTab(this.getAttribute('data-account-tab'));
    });
  });

  window.showTab = showTab;
  showTab('dashboard');
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
