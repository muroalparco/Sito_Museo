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
    'citta_nascita'      => 'Città che vorresti visitare',
    'scuola_elementare'  => 'Nome della scuola elementare',
    'colore_preferito'   => 'Colore preferito'
];

function generaCodiceRecuperoPassword(): string {
    return generaCodice6();
}

function caricaUtenteRecupero(PDO $pdo, string $email): ?array {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id_utente, nome, email, domanda_sicurezza, risposta_sicurezza_hash, password_reset_code, password_reset_scadenza FROM Utenti WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $utente = $stmt->fetch();
    return $utente ?: null;
}

function generaInviaCodiceRecupero(PDO $pdo, array $utente): bool {
    $codice = generaCodiceRecuperoPassword();
    $scadenza = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $upd = $pdo->prepare('UPDATE Utenti SET password_reset_code = ?, password_reset_scadenza = ? WHERE id_utente = ?');
    $upd->execute([$codice, $scadenza, (int)$utente['id_utente']]);

    return inviaEmailCodiceRecuperoPassword($utente['email'], $utente['nome'], $codice);
}

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
                $utente = caricaUtenteRecupero($pdo, $email);

                if (!$utente) {
                    $errore = 'Nessun account trovato con questa email.';
                } elseif (empty($utente['domanda_sicurezza']) || empty($utente['risposta_sicurezza_hash'])) {
                    $errore = 'Questo account non ha una domanda di sicurezza impostata.';
                } else {
                    $emailInviata = generaInviaCodiceRecupero($pdo, $utente);
                    if ($emailInviata) {
                        $messaggio = 'Ti abbiamo inviato via email un codice di recupero a 6 cifre. Inseriscilo insieme alla risposta di sicurezza.';
                    } else {
                        $errore = 'Non è stato possibile inviare la mail con il codice. Controlla la configurazione SMTP/PHPMailer e riprova.';
                    }
                    $step = 'verifica';
                }
            }
        }

        if ($azione === 'reinvia_codice_recupero') {
            $utente = caricaUtenteRecupero($pdo, $email);
            if (!$utente) {
                $errore = 'Non ho trovato un account associato a questa email.';
                $step = 'email';
            } else {
                $emailInviata = generaInviaCodiceRecupero($pdo, $utente);
                if ($emailInviata) {
                    $messaggio = 'Nuovo codice inviato. Controlla anche la cartella spam.';
                } else {
                    $errore = 'Il codice è stato rigenerato, ma la mail non è partita. Controlla SMTP/PHPMailer oppure riprova più tardi.';
                }
                $step = 'verifica';
            }
        }

        if ($azione === 'reset_password') {
            $risposta = trim($_POST['risposta_sicurezza'] ?? '');
            $codiceRecupero = trim($_POST['codice_recupero'] ?? '');
            $nuovaPassword = trim($_POST['nuova_password'] ?? '');
            $confermaPassword = trim($_POST['conferma_password'] ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errore = 'Email non valida.';
            } elseif (!$risposta) {
                $errore = 'Inserisci la risposta di sicurezza.';
            } elseif (!preg_match('/^\d{6}$/', $codiceRecupero)) {
                $errore = 'Inserisci il codice di recupero a 6 cifre ricevuto via email.';
            } elseif (strlen($nuovaPassword) < 8) {
                $errore = 'La nuova password deve avere almeno 8 caratteri.';
            } elseif ($nuovaPassword !== $confermaPassword) {
                $errore = 'Le password non coincidono.';
            } else {
                $utente = caricaUtenteRecupero($pdo, $email);

                if (!$utente || empty($utente['risposta_sicurezza_hash'])) {
                    $errore = 'Impossibile verificare i dati inseriti.';
                } elseif (empty($utente['password_reset_code']) || $utente['password_reset_code'] !== $codiceRecupero) {
                    $errore = 'Codice di recupero non corretto.';
                    $step = 'verifica';
                } elseif (!empty($utente['password_reset_scadenza']) && strtotime($utente['password_reset_scadenza']) < time()) {
                    $errore = 'Codice di recupero scaduto. Richiedi un nuovo codice.';
                    $step = 'verifica';
                } elseif (!password_verify(normalizzaRispostaSicurezza($risposta), $utente['risposta_sicurezza_hash'])) {
                    $errore = 'Risposta di sicurezza non corretta.';
                    $step = 'verifica';
                } else {
                    $nuovoHash = password_hash($nuovaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                    $update = $pdo->prepare('UPDATE Utenti SET password_hash = ?, password_reset_code = NULL, password_reset_scadenza = NULL WHERE id_utente = ?');
                    $update->execute([$nuovoHash, (int)$utente['id_utente']]);

                    $messaggio = 'Password aggiornata correttamente. Tra pochi secondi verrai reindirizzato al login.';
                    $step = 'completato';
                }
            }

            if ($errore && $email && $step !== 'email') {
                $utente = caricaUtenteRecupero($pdo, $email);
                if ($utente) {
                    $step = 'verifica';
                }
            }
        }
    }
}


if ($step === 'completato' && $messaggio && !$errore) {
    header('Refresh: 3; url=' . SITE_URL . '/login.php?password_aggiornata=1');
}

include __DIR__ . '/header.php';
?>

<main class="min-h-screen bg-avorio py-12 px-4">
    <div class="max-w-xl mx-auto">
        <section class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 md:p-10">

            <div class="text-center mb-8">
                <img src="<?= SITE_URL ?>/img/logo.svg" alt="Logo Museo Storico Severi" class="h-20 w-auto mx-auto mb-6 object-contain">

                <h1 class="font-display text-3xl md:text-4xl font-bold text-antracite mb-3">
                    Password dimenticata?
                </h1>

                <p class="text-gray-600">
                    Per cambiare password devi confermare sia la risposta di sicurezza sia il codice ricevuto via email.
                </p>
            </div>

            <?php if ($errore): ?>
                <div class="floating-alert mb-6 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm leading-relaxed" role="alert">
                    ⚠️ <?= clean($errore) ?>
                </div>
            <?php endif; ?>

            <?php if ($messaggio): ?>
                <div class="floating-alert mb-6 rounded-xl bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm leading-relaxed" role="status">
                    ✅ <?= clean($messaggio) ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
                <form method="POST" action="<?= SITE_URL ?>/recupero_password.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="azione" value="cerca_email">

                    <div>
                        <label for="email" class="block text-sm font-semibold text-antracite mb-2">Inserisci la tua email</label>
                        <input type="email" id="email" name="email" required placeholder="nome@email.com" value="<?= clean($email) ?>"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <button type="submit" class="w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                        Invia codice di recupero
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
                        <label for="risposta_sicurezza" class="block text-sm font-semibold text-antracite mb-2">Risposta di sicurezza</label>
                        <input type="text" id="risposta_sicurezza" name="risposta_sicurezza" required placeholder="Scrivi la risposta"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <div>
                        <label for="codice_recupero" class="block text-sm font-semibold text-antracite mb-2">Codice ricevuto via email</label>
                        <input type="text" id="codice_recupero" name="codice_recupero" required inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="123456"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 tracking-[0.35em] text-center font-bold focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <div>
                        <label for="nuova_password" class="block text-sm font-semibold text-antracite mb-2">Nuova password</label>
                        <input type="password" id="nuova_password" name="nuova_password" required minlength="8" placeholder="Almeno 8 caratteri"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <div>
                        <label for="conferma_password" class="block text-sm font-semibold text-antracite mb-2">Conferma nuova password</label>
                        <input type="password" id="conferma_password" name="conferma_password" required minlength="8" placeholder="Ripeti la nuova password"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <button type="submit" class="w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                        Cambia password
                    </button>
                </form>

                <form method="POST" action="<?= SITE_URL ?>/recupero_password.php" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="azione" value="reinvia_codice_recupero">
                    <input type="hidden" name="email" value="<?= clean($email) ?>">
                    <button type="submit" class="w-full border-2 border-oro text-oro font-semibold px-6 py-3 rounded-xl hover:bg-oro hover:text-antracite transition">
                        Rigenera e invia un nuovo codice
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($step === 'completato'): ?>
                <a href="<?= SITE_URL ?>/login.php?password_aggiornata=1" class="block text-center w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                    Vai al login
                </a>
                <script>
                    window.setTimeout(function () {
                        window.location.href = '<?= SITE_URL ?>/login.php?password_aggiornata=1';
                    }, 3000);
                </script>
            <?php endif; ?>

            <div class="mt-8 text-center">
                <a href="<?= SITE_URL ?>/login.php" class="text-oro font-semibold hover:underline">Torna al login</a>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
