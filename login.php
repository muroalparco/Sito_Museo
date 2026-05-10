<?php
// ============================================================
//  Login — Museo Storico Severi
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Già loggato → redirect home
if (isLogged()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$pageTitle = 'Accedi';
$error     = '';
$success   = $_GET['registered'] ?? false;

// Gestione POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token di sicurezza non valido. Riprova.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            $error = 'Inserisci email e password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Formato email non valido.';
        } else {
            $result = loginUtente($email, $password);
            if ($result['success']) {
                $redirect = match($result['ruolo']) {
                    'amministratore' => SITE_URL . '/pages/admin/dashboard.php',
                    'operatore'      => SITE_URL . '/pages/admin/dashboard.php',
                    default          => SITE_URL . '/index.php',
                };
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Accedi</span>
  </div>
</div>

<!-- ══════════ LOGIN FORM ══════════ -->
<main class="flex-1 flex items-center justify-center py-16 px-4">
  <div class="w-full max-w-md fade-up">

    <!-- Card -->
    <div class="bg-white rounded-xl shadow-xl overflow-hidden border border-avorio-dark">

      <!-- Header card -->
      <div class="bg-antracite px-8 py-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4">
          <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle cx="50" cy="50" r="48" fill="none" stroke="#C9A84C" stroke-width="3"/>
            <polygon points="50,12 88,82 12,82" fill="none" stroke="#C9A84C" stroke-width="3"/>
            <polygon points="50,30 72,72 28,72" fill="#C9A84C" opacity=".25"/>
            <text x="50" y="70" text-anchor="middle" font-family="serif" font-size="18" font-weight="bold" fill="#C9A84C">MSS</text>
          </svg>
        </div>
        <h1 class="font-display text-avorio text-2xl font-bold">Accedi al Museo</h1>
        <p class="text-gray-400 text-sm font-body mt-1">Inserisci le tue credenziali</p>
      </div>

      <!-- Form body -->
      <div class="px-8 py-8">

        <?php if ($success): ?>
        <div class="alert-success p-4 rounded mb-6 text-sm font-body">
          ✅ Registrazione completata. Puoi effettuare il login.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert-error p-4 rounded mb-6 text-sm font-body">
          ⚠️ <?= clean($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <!-- Email -->
          <div class="mb-5">
            <label for="email" class="block text-sm font-body font-bold text-antracite mb-1">
              Email <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
              </span>
              <input type="email" id="email" name="email"
                     value="<?= clean($_POST['email'] ?? '') ?>"
                     required autocomplete="email"
                     placeholder="nome@esempio.it"
                     class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro transition-colors" />
            </div>
          </div>

          <!-- Password -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-1">
              <label for="password" class="block text-sm font-body font-bold text-antracite">
                Password <span class="text-red-400">*</span>
              </label>
              <a href="pages/recupero_password.php" class="text-xs text-acciaio hover:text-oro transition-colors font-body">
                Password dimenticata?
              </a>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </span>
              <input type="password" id="password" name="password"
                     required autocomplete="current-password"
                     placeholder="••••••••"
                     class="w-full pl-10 pr-12 py-3 border border-gray-200 rounded-lg font-body text-sm focus:outline-none focus:border-oro focus:ring-1 focus:ring-oro transition-colors" />
              <!-- Toggle visibilità password -->
              <button type="button" id="togglePw"
                      class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-oro transition-colors"
                      onclick="document.getElementById('password').type = document.getElementById('password').type === 'password' ? 'text' : 'password'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Submit -->
          <button type="submit"
                  class="btn-oro w-full py-3 rounded-lg font-body text-sm uppercase tracking-widest">
            Accedi
          </button>
        </form>

        <!-- Divider -->
        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-white px-4 text-xs text-gray-400 font-body">oppure</span>
          </div>
        </div>

        <!-- Link registrazione -->
        <p class="text-center text-sm font-body text-gray-500">
          Non hai un account?
          <a href="registrazione.php" class="text-oro font-bold hover:underline">Registrati gratuitamente</a>
        </p>

      </div>
    </div>

    <!-- Nota visitatori anonimi -->
    <p class="text-center text-xs text-gray-400 mt-4 font-body">
      Puoi anche
      <a href="<?= SITE_URL ?>/pages/esposizioni.php" class="text-acciaio hover:text-oro transition-colors">
        esplorare le mostre
      </a>
      senza registrarti.
    </p>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
