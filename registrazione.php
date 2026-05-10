<?php
// ============================================================
//  Registrazione — Museo Storico Severi
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLogged()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$pageTitle = 'Registrati';
$error     = '';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token di sicurezza non valido. Riprova.';
    } else {
        $nome     = trim($_POST['nome']     ?? '');
        $cognome  = trim($_POST['cognome']  ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');
        $terms    = isset($_POST['terms']);

        // Validazione
        if (!$nome)                           $errors['nome']     = 'Il nome è obbligatorio.';
        if (!$cognome)                        $errors['cognome']  = 'Il cognome è obbligatorio.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
                                               $errors['email']    = 'Email non valida.';
        if (strlen($password) < 8)             $errors['password'] = 'La password deve avere almeno 8 caratteri.';
        if ($password !== $confirm)            $errors['confirm']  = 'Le password non coincidono.';
        if (!$terms)                           $errors['terms']    = 'Devi accettare i termini di servizio.';

        if (empty($errors)) {
            $result = registraUtente($nome, $cognome, $email, $password);
            if ($result['success']) {
                header('Location: ' . SITE_URL . '/pages/login.php?registered=1');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Helper per class campo con errore
function fieldClass(string $field, array $errors): string {
    return isset($errors[$field])
        ? 'border-red-400 focus:border-red-400 focus:ring-red-200'
        : 'border-gray-200 focus:border-oro focus:ring-1 focus:ring-oro';
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Registrati</span>
  </div>
</div>

<!-- ══════════ REGISTRATION FORM ══════════ -->
<main class="flex-1 flex items-center justify-center py-16 px-4">
  <div class="w-full max-w-lg fade-up">

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
        <h1 class="font-display text-avorio text-2xl font-bold">Registrati al Museo</h1>
        <p class="text-gray-400 text-sm font-body mt-1">Crea il tuo account gratuitamente</p>
      </div>

      <div class="px-8 py-8">

        <?php if ($error): ?>
        <div class="alert-error p-4 rounded mb-6 text-sm font-body">⚠️ <?= clean($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

          <!-- Nome + Cognome -->
          <div class="grid grid-cols-2 gap-4 mb-5">
            <div>
              <label for="nome" class="block text-sm font-body font-bold text-antracite mb-1">
                Nome <span class="text-red-400">*</span>
              </label>
              <input type="text" id="nome" name="nome"
                     value="<?= clean($_POST['nome'] ?? '') ?>"
                     required placeholder="Mario"
                     class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('nome', $errors) ?>"/>
              <?php if (isset($errors['nome'])): ?>
              <p class="text-red-500 text-xs mt-1"><?= clean($errors['nome']) ?></p>
              <?php endif; ?>
            </div>
            <div>
              <label for="cognome" class="block text-sm font-body font-bold text-antracite mb-1">
                Cognome <span class="text-red-400">*</span>
              </label>
              <input type="text" id="cognome" name="cognome"
                     value="<?= clean($_POST['cognome'] ?? '') ?>"
                     required placeholder="Rossi"
                     class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('cognome', $errors) ?>"/>
              <?php if (isset($errors['cognome'])): ?>
              <p class="text-red-500 text-xs mt-1"><?= clean($errors['cognome']) ?></p>
              <?php endif; ?>
            </div>
          </div>

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
                     required autocomplete="email" placeholder="nome@esempio.it"
                     class="w-full pl-10 pr-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('email', $errors) ?>"/>
            </div>
            <?php if (isset($errors['email'])): ?>
            <p class="text-red-500 text-xs mt-1"><?= clean($errors['email']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Password -->
          <div class="mb-5">
            <label for="password" class="block text-sm font-body font-bold text-antracite mb-1">
              Password <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </span>
              <input type="password" id="password" name="password"
                     required autocomplete="new-password" placeholder="Almeno 8 caratteri"
                     class="w-full pl-10 pr-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('password', $errors) ?>"
                     oninput="checkStrength(this.value)"/>
            </div>
            <!-- Barra forza password -->
            <div class="h-1 w-full bg-gray-200 rounded mt-2">
              <div id="strengthBar" class="h-1 rounded transition-all duration-300" style="width:0%"></div>
            </div>
            <p id="strengthText" class="text-xs text-gray-400 mt-1 font-body"></p>
            <?php if (isset($errors['password'])): ?>
            <p class="text-red-500 text-xs mt-1"><?= clean($errors['password']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Conferma password -->
          <div class="mb-6">
            <label for="confirm" class="block text-sm font-body font-bold text-antracite mb-1">
              Conferma password <span class="text-red-400">*</span>
            </label>
            <div class="relative">
              <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </span>
              <input type="password" id="confirm" name="confirm"
                     required autocomplete="new-password" placeholder="Ripeti la password"
                     class="w-full pl-10 pr-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('confirm', $errors) ?>"/>
            </div>
            <?php if (isset($errors['confirm'])): ?>
            <p class="text-red-500 text-xs mt-1"><?= clean($errors['confirm']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Terms -->
          <div class="mb-6">
            <label class="flex items-start gap-3 cursor-pointer">
              <input type="checkbox" name="terms" id="terms"
                     <?= isset($_POST['terms']) ? 'checked' : '' ?>
                     class="mt-1 w-4 h-4 accent-[#C9A84C]" />
              <span class="text-sm text-gray-600 font-body">
                Ho letto e accetto i
                <a href="<?= SITE_URL ?>/pages/termini.php" class="text-oro hover:underline">Termini di servizio</a>
                e la
                <a href="<?= SITE_URL ?>/pages/privacy.php" class="text-oro hover:underline">Privacy Policy</a>.
                <span class="text-red-400">*</span>
              </span>
            </label>
            <?php if (isset($errors['terms'])): ?>
            <p class="text-red-500 text-xs mt-1 ml-7"><?= clean($errors['terms']) ?></p>
            <?php endif; ?>
          </div>

          <!-- Submit -->
          <button type="submit"
                  class="btn-oro w-full py-3 rounded-lg font-body text-sm uppercase tracking-widest">
            Crea account
          </button>
        </form>

        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-white px-4 text-xs text-gray-400 font-body">hai già un account?</span>
          </div>
        </div>

        <p class="text-center text-sm font-body text-gray-500">
          <a href="login.php" class="text-oro font-bold hover:underline">Accedi qui</a>
        </p>

      </div>
    </div>

  </div>
</main>

<script>
function checkStrength(pw) {
  const bar  = document.getElementById('strengthBar');
  const text = document.getElementById('strengthText');
  let score  = 0;
  if (pw.length >= 8)  score++;
  if (pw.length >= 12) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;

  const levels = [
    { pct: '20%', color: '#ef4444', label: 'Molto debole' },
    { pct: '40%', color: '#f97316', label: 'Debole' },
    { pct: '60%', color: '#eab308', label: 'Discreta' },
    { pct: '80%', color: '#22c55e', label: 'Forte' },
    { pct: '100%', color: '#16a34a', label: 'Molto forte' },
  ];
  const l = levels[Math.max(0, Math.min(score - 1, 4))];
  bar.style.width    = pw.length ? l.pct : '0%';
  bar.style.background = l.color;
  text.textContent   = pw.length ? l.label : '';
  text.style.color   = l.color;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
