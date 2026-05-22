<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (isLogged()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$pageTitle = 'Registrati - Museo Storico Severi';
$error     = '';
$errors    = [];

$domandeSicurezza = [
    'primo_animale'      => 'Nome del primo animale domestico',
    'citta_nascita'      => 'Città che vorresti visitare',
    'scuola_elementare'  => 'Nome della scuola elementare',
    'colore_preferito'   => 'Colore preferito'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token di sicurezza non valido. Riprova.';
    } else {
        $nome                = trim($_POST['nome'] ?? '');
        $cognome             = trim($_POST['cognome'] ?? '');
        $email               = trim($_POST['email'] ?? '');
        $password            = trim($_POST['password'] ?? '');
        $confirm             = trim($_POST['confirm'] ?? '');
        $domanda_sicurezza   = trim($_POST['domanda_sicurezza'] ?? '');
        $risposta_sicurezza  = trim($_POST['risposta_sicurezza'] ?? '');

        if (!$nome) {
            $errors['nome'] = 'Il nome è obbligatorio.';
        }

        if (!$cognome) {
            $errors['cognome'] = 'Il cognome è obbligatorio.';
        }

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'La password deve avere almeno 8 caratteri.';
        }

        if ($password !== $confirm) {
            $errors['confirm'] = 'Le password non coincidono.';
        }

        if (!$domanda_sicurezza || !array_key_exists($domanda_sicurezza, $domandeSicurezza)) {
            $errors['domanda_sicurezza'] = 'Scegli una domanda di sicurezza.';
        }

        if (!$risposta_sicurezza) {
            $errors['risposta_sicurezza'] = 'Inserisci la risposta di sicurezza.';
        }

        if (empty($errors)) {
            $result = registraUtente(
                $nome,
                $cognome,
                $email,
                $password,
                $domanda_sicurezza,
                $risposta_sicurezza
            );

            if ($result['success']) {
                $mailParam = !empty($result['email_sent']) ? 'sent' : 'failed';
                header('Location: ' . SITE_URL . '/verifica_email.php?registered=1&email=' . urlencode($email) . '&mail=' . $mailParam);
                exit;
            }

            $error = $result['message'];
        }
    }
}

function fieldClass(string $field, array $errors): string {
    return isset($errors[$field])
        ? 'border-red-400 focus:border-red-400 focus:ring-red-200'
        : 'border-gray-200 focus:border-oro focus:ring-1 focus:ring-oro';
}

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
    <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
        <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
        <span class="mx-2 text-oro">›</span>
        <span class="text-antracite">Registrati</span>
    </div>
</div>

<main class="auth-page flex-1 flex items-center justify-center py-16 px-4 bg-avorio">
    <div class="auth-card w-full max-w-lg fade-up">
        <div class="bg-white rounded-xl shadow-xl overflow-hidden border border-avorio-dark">

            <div class="bg-antracite px-8 py-8 text-center">
                <img
                    src="<?= SITE_URL ?>/img/logo.svg"
                    alt="Logo Museo Storico Severi"
                    class="h-20 w-auto mx-auto mb-4 object-contain"
                >
                <h1 class="font-display text-avorio text-2xl font-bold">Registrati al Museo</h1>
                <p class="text-gray-400 text-sm font-body mt-1">Crea il tuo account gratuitamente</p>
            </div>

            <div class="px-8 py-8">

                <?php if ($error): ?>
                    <div class="alert-error floating-alert p-4 rounded mb-6 text-sm font-body" role="alert">
                        ⚠️ <?= clean($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label for="nome" class="block text-sm font-body font-bold text-antracite mb-1">
                                Nome <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                value="<?= clean($_POST['nome'] ?? '') ?>"
                                required
                                placeholder="Mario"
                                class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('nome', $errors) ?>"
                            >
                            <?php if (isset($errors['nome'])): ?>
                                <p class="text-red-500 text-xs mt-1"><?= clean($errors['nome']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="cognome" class="block text-sm font-body font-bold text-antracite mb-1">
                                Cognome <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                id="cognome"
                                name="cognome"
                                value="<?= clean($_POST['cognome'] ?? '') ?>"
                                required
                                placeholder="Rossi"
                                class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('cognome', $errors) ?>"
                            >
                            <?php if (isset($errors['cognome'])): ?>
                                <p class="text-red-500 text-xs mt-1"><?= clean($errors['cognome']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label for="email" class="block text-sm font-body font-bold text-antracite mb-1">
                            Email <span class="text-red-400">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= clean($_POST['email'] ?? '') ?>"
                            required
                            autocomplete="email"
                            placeholder="nome@esempio.it"
                            class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('email', $errors) ?>"
                        >
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= clean($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-5">
                        <label for="password" class="block text-sm font-body font-bold text-antracite mb-1">
                            Password <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                placeholder="Almeno 8 caratteri"
                                class="w-full px-4 py-3 pr-14 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('password', $errors) ?>"
                                oninput="checkStrength(this.value)"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-oro focus:outline-none"
                                onclick="togglePasswordVisibility('password', this)"
                                aria-label="Mostra password"
                                title="Mostra password"
                            >
                                👁️
                            </button>
                        </div>
                        <div class="h-1 w-full bg-gray-200 rounded mt-2">
                            <div id="strengthBar" class="h-1 rounded transition-all duration-300" style="width:0%"></div>
                        </div>
                        <p id="strengthText" class="text-xs text-gray-400 mt-1 font-body"></p>
                        <?php if (isset($errors['password'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= clean($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-5">
                        <label for="confirm" class="block text-sm font-body font-bold text-antracite mb-1">
                            Conferma password <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="confirm"
                                name="confirm"
                                required
                                autocomplete="new-password"
                                placeholder="Ripeti la password"
                                class="w-full px-4 py-3 pr-14 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('confirm', $errors) ?>"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-oro focus:outline-none"
                                onclick="togglePasswordVisibility('confirm', this)"
                                aria-label="Mostra conferma password"
                                title="Mostra conferma password"
                            >
                                👁️
                            </button>
                        </div>
                        <?php if (isset($errors['confirm'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= clean($errors['confirm']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-5">
                        <label for="domanda_sicurezza" class="block text-sm font-body font-bold text-antracite mb-1">
                            Domanda di sicurezza <span class="text-red-400">*</span>
                        </label>
                        <select
                            id="domanda_sicurezza"
                            name="domanda_sicurezza"
                            required
                            class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('domanda_sicurezza', $errors) ?>"
                        >
                            <option value="">Seleziona una domanda</option>
                            <?php foreach ($domandeSicurezza as $value => $label): ?>
                                <option value="<?= clean($value) ?>" <?= ($_POST['domanda_sicurezza'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= clean($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['domanda_sicurezza'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= clean($errors['domanda_sicurezza']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <label for="risposta_sicurezza" class="block text-sm font-body font-bold text-antracite mb-1">
                            Risposta di sicurezza <span class="text-red-400">*</span>
                        </label>
                        <input
                            type="text"
                            id="risposta_sicurezza"
                            name="risposta_sicurezza"
                            value="<?= clean($_POST['risposta_sicurezza'] ?? '') ?>"
                            required
                            placeholder="Scrivi la tua risposta"
                            class="w-full px-4 py-3 border rounded-lg font-body text-sm focus:outline-none transition-colors <?= fieldClass('risposta_sicurezza', $errors) ?>"
                        >
                        <?php if (isset($errors['risposta_sicurezza'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= clean($errors['risposta_sicurezza']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn-oro w-full py-3 rounded-lg font-body text-sm uppercase tracking-widest">
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
                    <a href="<?= SITE_URL ?>/login.php" class="text-oro font-bold hover:underline">Accedi qui</a>
                </p>
            </div>
        </div>
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

function checkStrength(pw) {
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score = 0;

    if (pw.length >= 8) score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const levels = [
        { pct: '20%', color: '#ef4444', label: 'Molto debole' },
        { pct: '40%', color: '#f97316', label: 'Debole' },
        { pct: '60%', color: '#eab308', label: 'Discreta' },
        { pct: '80%', color: '#22c55e', label: 'Forte' },
        { pct: '100%', color: '#16a34a', label: 'Molto forte' }
    ];

    const level = levels[Math.max(0, Math.min(score - 1, 4))];
    bar.style.width = pw.length ? level.pct : '0%';
    bar.style.background = level.color;
    text.textContent = pw.length ? level.label : '';
    text.style.color = level.color;
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
