<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Recupera ordine';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $codice = strtoupper(trim($_POST['codice'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        if ($codice === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errore = 'Inserisci codice ordine ed email usata per l’acquisto.';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT codice_recupero FROM Ordini WHERE codice_recupero = ? AND email_cliente = ? LIMIT 1');
            $stmt->execute([$codice, $email]);
            if (!$stmt->fetch()) {
                $errore = 'Ordine non trovato con questi dati.';
            } else {
                $_SESSION['ordini_recuperati'] = $_SESSION['ordini_recuperati'] ?? [];
                if (!in_array($codice, $_SESSION['ordini_recuperati'], true)) {
                    $_SESSION['ordini_recuperati'][] = $codice;
                }
                header('Location: ' . SITE_URL . '/biglietti.php?codice=' . urlencode($codice));
                exit;
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Recupera ordine</span>
  </div>
</div>

<main class="flex-1 flex items-center justify-center py-12 sm:py-16 px-4">
  <section class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden fade-up">
    <div class="bg-antracite px-8 py-8 text-center">
      <div class="text-5xl mb-4"></div>
      <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Biglietti</p>
      <h1 class="font-display text-avorio text-3xl font-bold">Recupera il tuo ordine</h1>
    </div>

    <div class="p-8">
      <?php if ($errore): ?>
        <div class="alert-error p-4 rounded mb-6 text-sm"> <?= clean($errore) ?></div>
      <?php endif; ?>

      <p class="text-gray-600 text-sm leading-relaxed mb-6">
        Inserisci il codice ordine e l’email usata per l’acquisto. Questo evita che un ordine possa essere recuperato solo indovinando il codice.
      </p>

      <form method="POST" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div>
          <label class="block text-sm font-body font-bold text-antracite mb-1">Codice ordine</label>
          <input type="text" name="codice" placeholder="ORD-XXXXXXXX" required
                 class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm uppercase focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
        </div>
        <div>
          <label class="block text-sm font-body font-bold text-antracite mb-1">Email acquisto</label>
          <input type="email" name="email" placeholder="email@example.com" required
                 class="w-full px-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro">
        </div>
        <button type="submit" class="btn-oro w-full py-3 rounded-lg font-body text-sm uppercase tracking-widest">
          Recupera biglietti
        </button>
      </form>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
