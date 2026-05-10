<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (isLogged()) {
    header('Location: ' . SITE_URL . '/account.php');
    exit;
}

$pageTitle = 'Recupero password - Museo Storico Severi';

$errore = '';
$messaggio = '';
$step = 'email';
$utente = null;
$email = trim($_POST['email'] ?? $_GET['email'] ?? '');

$domandeSicurezza = [
    'primo_animale'      => 'Nome del primo animale domestico',
    'citta_nascita'      => 'Città in cui sei nato/a',
    'scuola_elementare'  => 'Nome della scuola elementare',
    'colore_preferito'   => 'Colore preferito'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $azione = $_POST['azione'] ?? 'cerca_email';
        $pdo = getDB();

        if ($azione === 'cerca_email') {
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errore = 'Inserisci un indirizzo email valido.';
            } else {
                $stmt = $pdo->prepare('SELECT id_utente, nome, email, domanda_sicurezza FROM Utenti WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $utente = $stmt->fetch();

                if (!$utente) {
                    $errore = 'Nessun account trovato con questa email.';
                } elseif (empty($utente['domanda_sicurezza'])) {
                    $errore = 'Questo account non ha una domanda di sicurezza impostata.';
                } else {
                    $step = 'verifica';
                }
            }
        }

        if ($azione === 'reset_password') {
            $risposta = trim($_POST['risposta_sicurezza'] ?? '');
            $nuovaPassword = trim($_POST['nuova_password'] ?? '');
            $confermaPassword = trim($_POST['conferma_password'] ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errore = 'Email non valida.';
            } elseif (!$risposta) {
                $errore = 'Inserisci la risposta di sicurezza.';
            } elseif (strlen($nuovaPassword) < 8) {
                $errore = 'La nuova password deve avere almeno 8 caratteri.';
            } elseif ($nuovaPassword !== $confermaPassword) {
                $errore = 'Le password non coincidono.';
            } else {
                $stmt = $pdo->prepare('SELECT id_utente, nome, email, domanda_sicurezza, risposta_sicurezza_hash FROM Utenti WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $utente = $stmt->fetch();

                if (!$utente || empty($utente['risposta_sicurezza_hash'])) {
                    $errore = 'Impossibile verificare i dati inseriti.';
                } elseif (!password_verify(normalizzaRispostaSicurezza($risposta), $utente['risposta_sicurezza_hash'])) {
                    $errore = 'Risposta di sicurezza non corretta.';
                    $step = 'verifica';
                } else {
                    $nuovoHash = password_hash($nuovaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                    $update = $pdo->prepare('UPDATE Utenti SET password_hash = ? WHERE id_utente = ?');
                    $update->execute([$nuovoHash, $utente['id_utente']]);

                    $messaggio = 'Password aggiornata correttamente. Ora puoi effettuare il login.';
                    $step = 'completato';
                }
            }

            if ($errore && $email) {
                $step = 'verifica';
                if (!$utente) {
                    $stmt = $pdo->prepare('SELECT id_utente, nome, email, domanda_sicurezza FROM Utenti WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $utente = $stmt->fetch();
                }
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<main class="min-h-screen bg-avorio py-12 px-4">
    <div class="max-w-xl mx-auto">
        <section class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 md:p-10">

            <div class="text-center mb-8">
                <img
                    src="<?= SITE_URL ?>/img/logo.png"
                    alt="Logo Museo Storico Severi"
                    class="h-20 w-auto mx-auto mb-6 object-contain"
                >

                <h1 class="font-display text-3xl md:text-4xl font-bold text-antracite mb-3">
                    Password dimenticata?
                </h1>

                <p class="text-gray-600">
                    Recupera l’accesso usando la domanda di sicurezza scelta in fase di registrazione.
                </p>
            </div>

            <?php if ($errore): ?>
                <div class="mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3">
                    <?= clean($errore) ?>
                </div>
            <?php endif; ?>

            <?php if ($messaggio): ?>
                <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-700 px-4 py-3">
                    <?= clean($messaggio) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
                <form method="POST" action="<?= SITE_URL ?>/recupero_password.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="azione" value="cerca_email">

                    <div>
                        <label for="email" class="block text-sm font-semibold text-antracite mb-2">
                            Inserisci la tua email
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            placeholder="nome@email.com"
                            value="<?= clean($email) ?>"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro"
                        >
                    </div>

                    <button type="submit" class="w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                        Continua
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($step === 'verifica' && $utente): ?>
                <form method="POST" action="<?= SITE_URL ?>/recupero_password.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="azione" value="reset_password">
                    <input type="hidden" name="email" value="<?= clean($email) ?>">

                    <div class="rounded-xl bg-avorio border border-oro/20 px-4 py-3">
                        <p class="text-sm text-gray-500 mb-1">Domanda di sicurezza</p>
                        <p class="font-semibold text-antracite">
                            <?= clean($domandeSicurezza[$utente['domanda_sicurezza']] ?? $utente['domanda_sicurezza']) ?>
                        </p>
                    </div>

                    <div>
                        <label for="risposta_sicurezza" class="block text-sm font-semibold text-antracite mb-2">
                            Risposta di sicurezza
                        </label>
                        <input
                            type="text"
                            id="risposta_sicurezza"
                            name="risposta_sicurezza"
                            required
                            placeholder="Scrivi la risposta"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro"
                        >
                    </div>

                    <div>
                        <label for="nuova_password" class="block text-sm font-semibold text-antracite mb-2">
                            Nuova password
                        </label>
                        <input
                            type="password"
                            id="nuova_password"
                            name="nuova_password"
                            required
                            minlength="8"
                            placeholder="Almeno 8 caratteri"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro"
                        >
                    </div>

                    <div>
                        <label for="conferma_password" class="block text-sm font-semibold text-antracite mb-2">
                            Conferma nuova password
                        </label>
                        <input
                            type="password"
                            id="conferma_password"
                            name="conferma_password"
                            required
                            minlength="8"
                            placeholder="Ripeti la nuova password"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro"
                        >
                    </div>

                    <button type="submit" class="w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                        Cambia password
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($step === 'completato'): ?>
                <a href="<?= SITE_URL ?>/login.php" class="block text-center w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                    Vai al login
                </a>
            <?php endif; ?>

            <div class="mt-8 text-center">
                <a href="<?= SITE_URL ?>/login.php" class="text-oro font-semibold hover:underline">
                    Torna al login
                </a>
            </div>

        </section>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
