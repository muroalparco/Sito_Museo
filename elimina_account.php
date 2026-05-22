<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (!isLogged()) {
    header('Location: ' . SITE_URL . '/registrazione.php');
    exit;
}

$pageTitle = 'Elimina account - Museo Storico Severi';
$pdo = getDB();
$errore = '';

$stmt = $pdo->prepare('SELECT id_utente, nome, cognome, email FROM Utenti WHERE id_utente = ? LIMIT 1');
$stmt->execute([$_SESSION['utente_id']]);
$utente = $stmt->fetch();

if (!$utente) {
    logoutUtente();
    header('Location: ' . SITE_URL . '/registrazione.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $nomeUtente = trim($_POST['nome_utente'] ?? '');
        $conferma = trim($_POST['conferma'] ?? '');

        if (strcasecmp($nomeUtente, $utente['email']) !== 0) {
            $errore = 'Per eliminare l\'account devi inserire esattamente la tua email.';
        } elseif ($conferma !== 'CONFERMA') {
            $errore = 'Per confermare devi scrivere esattamente CONFERMA.';
        } else {
            $delete = $pdo->prepare('DELETE FROM Utenti WHERE id_utente = ?');
            $delete->execute([(int)$utente['id_utente']]);

            logoutUtente();
            header('Location: ' . SITE_URL . '/registrazione.php?account_deleted=1');
            exit;
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <a href="<?= SITE_URL ?>/account.php" class="hover:text-oro transition-colors">Il mio account</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Elimina account</span>
  </div>
</div>

<main class="min-h-screen bg-avorio py-12 px-4">
  <div class="max-w-2xl mx-auto">
    <section class="bg-white rounded-2xl shadow-lg border border-red-100 overflow-hidden">
      <div class="bg-red-900 px-8 py-8 text-center">
        <h1 class="font-display text-avorio text-3xl font-bold">Elimina account</h1>
        <p class="text-red-100 text-sm mt-2">Questa azione è definitiva e non può essere annullata.</p>
      </div>

      <div class="p-8 space-y-6">
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-4">
          <p class="font-bold mb-2">Attenzione</p>
          <p class="text-sm leading-relaxed">
            Stai per eliminare l'account associato a <strong><?= clean($utente['email']) ?></strong>.
            Potrai eliminare esclusivamente il tuo account personale.
          </p>
        </div>

        <?php if ($errore): ?>
          <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3">
            <?= clean($errore) ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div>
            <label for="nome_utente" class="block text-sm font-bold text-antracite mb-2">
              Inserisci la tua email
            </label>
            <input
              type="email"
              id="nome_utente"
              name="nome_utente"
              required
              placeholder="<?= clean($utente['email']) ?>"
              class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-500"
            >
          </div>

          <div>
            <label for="conferma" class="block text-sm font-bold text-antracite mb-2">
              Scrivi CONFERMA
            </label>
            <input
              type="text"
              id="conferma"
              name="conferma"
              required
              placeholder="CONFERMA"
              class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-500"
            >
          </div>

          <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <a href="<?= SITE_URL ?>/account.php" class="btn-outline text-center px-6 py-3 rounded-xl flex-1">
              Annulla
            </a>
            <button type="submit" class="bg-red-700 text-white font-bold px-6 py-3 rounded-xl hover:bg-red-800 transition flex-1">
              Elimina definitivamente
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
