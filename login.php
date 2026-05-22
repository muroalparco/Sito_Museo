<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// già loggato → redirect home
if (isLogged()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$pageTitle = 'Accedi';
$error     = '';
$registered = $_GET['registered'] ?? '';
$verified = ($_GET['verified'] ?? '') === '1';
$mailStatus = $_GET['mail'] ?? '';
$emailRegistrata = trim($_GET['email'] ?? '');

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
                    'amministratore' => SITE_URL . '/account.php',
                    'operatore'      => SITE_URL . '/account.php',
                    'cassiere'       => SITE_URL . '/account.php',
                    'tester'         => SITE_URL . '/account.php',
                    default          => SITE_URL . '/index.php',
                };
                header('Location: ' . $redirect);
                exit;
            } elseif (!empty($result['verification_required'])) {
                header('Location: ' . SITE_URL . '/verifica_email.php?email=' . urlencode($result['email'] ?? $email) . '&from=login');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Accedi</span>
  </div>
</div>

<!-- form di login-->
<main class="auth-page flex-1 flex items-center justify-center py-10 sm:py-16 px-4">
  <div class="auth-card w-full max-w-md fade-up">

    <!-- card -->
    <div class="bg-white rounded-xl shadow-xl overflow-hidden border border-avorio-dark">

      <!-- header card -->
      <div class="bg-antracite px-5 sm:px-8 py-6 sm:py-8 text-center">
        <img 
          src="<?= SITE_URL ?>/img/logo.svg" 
          alt="Logo Museo Storico Severi" 
          class="w-16 h-16 object-contain mx-auto mb-4"
        >
        <h1 class="font-display text-avorio text-xl sm:text-2xl font-bold">Accedi al Museo</h1>
        <p class="text-gray-400 text-sm font-body mt-1">Inserisci le tue credenziali</p>
      </div>

      <!-- form body -->
      <div class="px-5 sm:px-8 py-6 sm:py-8">

        <?php if ($verified): ?>
        <div class="alert-success floating-alert p-4 rounded mb-6 text-sm font-body leading-relaxed" role="status">
          ✅ Email verificata correttamente. Ora puoi effettuare il login.
        </div>
        <?php elseif ($registered === 'verify'): ?>
        <div class="alert-success floating-alert p-4 rounded mb-6 text-sm font-body leading-relaxed" role="status">
          ✅ Registrazione completata. Prima di accedere devi confermare l’account con il codice ricevuto via email.
          <?php if ($mailStatus === 'failed'): ?>
            <br><strong>Nota:</strong> la mail potrebbe non essere partita. Puoi rigenerare un nuovo codice dalla pagina di verifica.
          <?php endif; ?>
          <?php if ($emailRegistrata): ?>
            <br><a class="underline font-bold" href="<?= SITE_URL ?>/verifica_email.php?email=<?= urlencode($emailRegistrata) ?>">Vai alla verifica email</a>
          <?php endif; ?>
        </div>
        <?php elseif ($registered): ?>
        <div class="alert-success floating-alert p-4 rounded mb-6 text-sm font-body" role="status">
          ✅ Registrazione completata. Controlla la tua email per verificare l’account.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert-error floating-alert p-4 rounded mb-6 text-sm font-body" role="alert">
          ⚠️ <?= clean($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <!-- email -->
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

          <!-- password -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-1">
              <label for="password" class="block text-sm font-body font-bold text-antracite">
                Password <span class="text-red-400">*</span>
              </label>
              <a href="recupero_password.php" class="text-xs text-acciaio hover:text-oro transition-colors font-body">
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
              <button
                type="button"
                id="togglePw"
                class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-oro focus:outline-none transition-colors"
                onclick="togglePasswordVisibility('password', this)"
                aria-label="Mostra password"
                title="Mostra password"
              >
                👁️
              </button>
            </div>
          </div>

          <!-- invia -->
          <button type="submit"
                  class="btn-oro w-full py-3 rounded-lg font-body text-sm uppercase tracking-widest">
            Accedi
          </button>
        </form>

        <!-- divisore -->
        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-white px-4 text-xs text-gray-400 font-body">oppure</span>
          </div>
        </div>

        <!-- link per registrazione -->
        <p class="text-center text-sm font-body text-gray-500">
          Non hai un account?
          <a href="registrazione.php" class="text-oro font-bold hover:underline">Registrati gratuitamente</a>
        </p>

      </div>
    </div>

    <!-- se non vuoi loggare -->
    <p class="text-center text-xs text-gray-400 mt-4 font-body">
      Puoi anche
      <a href="<?= SITE_URL ?>/esposizioni.php" class="text-acciaio hover:text-oro transition-colors">
        esplorare le mostre
      </a>
      senza registrarti.
    </p>
  </div>
</main>

<script>
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.textContent = isHidden ? '🙈' : '👁️';
    button.setAttribute('aria-label', isHidden ? 'Nascondi password' : 'Mostra password');
    button.setAttribute('title', isHidden ? 'Nascondi password' : 'Mostra password');
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
