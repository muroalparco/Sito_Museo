<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (isLogged()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$pageTitle = 'Verifica email';
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$fromLogin = ($_GET['from'] ?? '') === 'login';
$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $codice = trim($_POST['codice'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errore = 'Inserisci un indirizzo email valido.';
        } elseif (!preg_match('/^\d{6}$/', $codice)) {
            $errore = 'Inserisci il codice di verifica di 6 cifre.';
        } else {
            $result = verificaCodiceEmail($email, $codice);
            if ($result['success']) {
                header('Location: ' . SITE_URL . '/login.php?verified=1');
                exit;
            }
            $errore = $result['message'];
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Verifica email</span>
  </div>
</div>

<main class="flex-1 flex items-center justify-center py-16 px-4 bg-avorio">
  <div class="w-full max-w-md fade-up">
    <section class="bg-white rounded-xl shadow-xl overflow-hidden border border-avorio-dark">
      <div class="bg-antracite px-8 py-8 text-center">
        <img src="<?= SITE_URL ?>/img/logo.png" alt="Logo Museo Storico Severi" class="h-20 w-auto mx-auto mb-4 object-contain">
        <h1 class="font-display text-avorio text-2xl font-bold">Verifica la tua email</h1>
        <p class="text-gray-400 text-sm font-body mt-1">Inserisci il codice di 6 cifre ricevuto via email.</p>
      </div>

      <div class="px-8 py-8">
        <?php if ($fromLogin): ?>
          <div class="alert-error p-4 rounded mb-6 text-sm font-body">
            ⚠️ Il tuo account non è ancora verificato. Inserisci il codice ricevuto via email; dopo la verifica potrai effettuare il login.
          </div>
        <?php endif; ?>

        <?php if ($errore): ?>
          <div class="alert-error p-4 rounded mb-6 text-sm font-body">⚠️ <?= clean($errore) ?></div>
        <?php endif; ?>

        <?php if ($successo): ?>
          <div class="alert-success p-4 rounded mb-6 text-sm font-body">✅ <?= clean($successo) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-5" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <div>
            <label for="email" class="block text-sm font-body font-bold text-antracite mb-1">Email</label>
            <input type="email" id="email" name="email" value="<?= clean($email) ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>

          <div>
            <label for="codice" class="block text-sm font-body font-bold text-antracite mb-1">Codice di verifica</label>
            <input type="text" id="codice" name="codice" inputmode="numeric" pattern="\d{6}" maxlength="6" required placeholder="123456" class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-center tracking-[0.5em] text-xl font-bold focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
          </div>

          <button type="submit" class="btn-oro w-full px-7 py-3 rounded font-body text-sm uppercase tracking-wide">
            Verifica account
          </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
          Hai già verificato?
          <a href="<?= SITE_URL ?>/login.php" class="text-oro font-bold hover:underline">Accedi</a>
        </p>
      </div>
    </section>
  </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
