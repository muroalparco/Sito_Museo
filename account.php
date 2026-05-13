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

// Ultimi 5 ordini con conteggio biglietti
$ordini = $pdo->prepare(
    "SELECT o.id_ordine, o.data_acquisto, o.importo_totale,
            COUNT(b.id_biglietto) AS num_biglietti
     FROM Ordini o
     LEFT JOIN Biglietti b ON b.id_ordine = o.id_ordine
     WHERE o.id_utente = ?
     GROUP BY o.id_ordine
     ORDER BY o.data_acquisto DESC
     LIMIT 5"
);
$ordini->execute([$_SESSION['utente_id']]);
$ultimiOrdini = $ordini->fetchAll();

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
            }
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

$ruoloLabel = ['visitatore' => 'Visitatore', 'operatore' => 'Operatore', 'cassiere' => 'Cassiere', 'amministratore' => 'Amministratore'];

include __DIR__ . '/header.php';
?>

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
          <?= $utente['ruolo'] === 'amministratore' ? 'bg-oro text-antracite' : ($utente['ruolo'] === 'operatore' ? 'bg-acciaio text-white' : 'bg-gray-600 text-white') ?>">
          <?= $ruoloLabel[$utente['ruolo']] ?>
        </span>
      </div>
      <div class="ml-auto hidden md:flex flex-col items-end text-right">
        <span class="text-gray-500 text-xs font-body uppercase tracking-wide">Membro dal</span>
        <span class="text-oro font-display text-lg"><?= date('M Y', strtotime($utente['data_registrazione'])) ?></span>
      </div>
    </div>
  </div>
</section>

<!-- contenuto dei tabs + msg errore o todo bien-->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

  <?php if ($successMsg): ?>
  <div class="alert-success p-4 rounded mb-6 text-sm font-body fade-up">✅ <?= clean($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
  <div class="alert-error p-4 rounded mb-6 text-sm font-body fade-up">⚠️ <?= clean($errorMsg) ?></div>
  <?php endif; ?>

  <div class="grid lg:grid-cols-3 gap-8">

    <!-- sidebar e nav in sezioni -->
    <aside class="lg:col-span-1">
      <nav class="bg-white rounded-xl shadow border border-avorio-dark overflow-hidden">
        <div class="bg-avorio-dark px-5 py-3 border-b border-oro border-opacity-20">
          <span class="text-xs font-body font-bold uppercase tracking-widest text-antracite-light">Sezioni</span>
        </div>
        <?php foreach ([
          ['profilo','👤','Il mio profilo'],
          ['sicurezza','🔐','Sicurezza'],
          ['ordini','🎟️','I miei ordini'],
        ] as $tab): ?>
        <button onclick="showTab('<?= $tab[0] ?>')"
                id="tab-btn-<?= $tab[0] ?>"
                class="tab-btn w-full flex items-center gap-3 px-5 py-4 text-sm font-body text-left hover:bg-avorio transition-colors border-b border-avorio-dark last:border-0">
          <span class="text-lg"><?= $tab[1] ?></span>
          <span><?= $tab[2] ?></span>
        </button>
        <?php endforeach; ?>
        <a href="<?= SITE_URL ?>/logout.php"
           class="w-full flex items-center gap-3 px-5 py-4 text-sm font-body text-red-500 hover:bg-red-50 transition-colors">
          <span class="text-lg">🚪</span> <span>Logout</span>
        </a>
      </nav>
    </aside>

    <!-- main -->
    <div class="lg:col-span-2 space-y-6">

      <!-- sezione profilo -->
      <div id="tab-profilo" class="tab-content">
        <div class="bg-white rounded-xl shadow border border-avorio-dark">
          <div class="px-4 sm:px-6 py-4 border-b border-avorio-dark">
            <h2 class="font-display text-xl font-semibold text-antracite">Il mio profilo</h2>
          </div>
          <form method="POST" class="px-4 sm:px-6 py-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_profile">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">Nome</label>
                <input type="text" name="nome" value="<?= clean($utente['nome']) ?>"
                       required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
              </div>
              <div>
                <label class="block text-sm font-body font-bold text-antracite mb-1">Cognome</label>
                <input type="text" name="cognome" value="<?= clean($utente['cognome']) ?>"
                       required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
              </div>
            </div>

            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Email</label>
              <input type="email" name="email" value="<?= clean($utente['email']) ?>"
                     required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
            </div>

            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1">Ruolo</label>
              <input type="text" value="<?= $ruoloLabel[$utente['ruolo']] ?>"
                     disabled class="w-full px-4 py-3 border border-gray-100 bg-gray-50 rounded-lg font-body text-sm text-gray-400 cursor-not-allowed"/>
            </div>

            <button type="submit" class="btn-oro w-full sm:w-auto px-6 py-2.5 rounded font-body text-sm uppercase tracking-wide">
              Salva modifiche
            </button>
          </form>
        </div>
      </div>

      <!-- sezione sicurezza -->
      <div id="tab-sicurezza" class="tab-content hidden">
        <div class="bg-white rounded-xl shadow border border-avorio-dark">
          <div class="px-4 sm:px-6 py-4 border-b border-avorio-dark">
            <h2 class="font-display text-xl font-semibold text-antracite">Cambia password</h2>
          </div>
          <form method="POST" class="px-4 sm:px-6 py-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="change_password">

            <?php foreach ([
              ['pw_attuale','Password attuale','current-password'],
              ['pw_nuova','Nuova password','new-password'],
              ['pw_conferma','Conferma nuova password','new-password'],
            ] as $f): ?>
            <div>
              <label class="block text-sm font-body font-bold text-antracite mb-1"><?= $f[1] ?></label>
              <input type="password" name="<?= $f[0] ?>" autocomplete="<?= $f[2] ?>" required
                     class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro"/>
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
        <div class="bg-white rounded-xl shadow border border-avorio-dark">
          <div class="px-6 py-4 border-b border-avorio-dark flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold text-antracite">I miei ordini</h2>
            <a href="<?= SITE_URL ?>/ordini.php" class="text-xs text-oro hover:underline font-body">Vedi tutti →</a>
          </div>

          <?php if (empty($ultimiOrdini)): ?>
          <div class="px-6 py-12 text-center">
            <div class="text-5xl mb-4">🎟️</div>
            <p class="text-gray-400 font-body text-sm">Nessun ordine ancora.</p>
            <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro inline-block mt-4 px-6 py-2.5 rounded font-body text-sm uppercase tracking-wide">
              Scopri le mostre
            </a>
          </div>
          <?php else: ?>
          <div class="divide-y divide-avorio-dark">
            <?php foreach ($ultimiOrdini as $ord): ?>
            <div class="px-6 py-4 flex items-center justify-between gap-4">
              <div>
                <div class="font-body text-sm font-bold text-antracite">Ordine #<?= (int)$ord['id_ordine'] ?></div>
                <div class="text-xs text-gray-400 mt-0.5">
                  <?= date('d/m/Y H:i', strtotime($ord['data_acquisto'])) ?>
                  · <?= (int)$ord['num_biglietti'] ?> bigliett<?= $ord['num_biglietti'] == 1 ? 'o' : 'i' ?>
                </div>
              </div>
              <div class="text-right flex-shrink-0">
                <div class="font-display text-lg font-bold text-oro">€<?= number_format($ord['importo_totale'], 2, ',', '.') ?></div>
                <a href="ordine_dettaglio.php?id=<?= (int)$ord['id_ordine'] ?>"
                   class="text-xs text-acciaio hover:text-oro transition-colors font-body">Dettagli →</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</main>

<script>
function showTab(name) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
  document.querySelectorAll('.tab-btn').forEach(el => {
    el.classList.remove('bg-avorio', 'text-oro', 'font-bold', 'border-l-2', 'border-oro');
  });
  document.getElementById('tab-' + name).classList.remove('hidden');
  const btn = document.getElementById('tab-btn-' + name);
  if (btn) btn.classList.add('bg-avorio', 'text-oro', 'font-bold', 'border-l-2', 'border-oro');
}
// Mostra tab profilo di default
showTab('profilo');
</script>

<?php include __DIR__ . '/footer.php'; ?>
