<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if (isLogged()) {
    header('Location: ' . SITE_URL . '/account.php');
    exit;
}

$pageTitle = 'Verifica email - Museo Storico Severi';
$pdo = getDB();
$email = trim($_POST['email'] ?? $_GET['email'] ?? '');
$errore = '';
$messaggio = '';
$successo = false;
$fromLogin = ($_GET['from'] ?? '') === 'login';
$registered = ($_GET['registered'] ?? '') === '1';
$mailStatus = $_GET['mail'] ?? '';

function caricaUtenteDaEmail(PDO $pdo, string $email): ?array {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $colonnaCodiceVerifica = colonnaCodiceVerificaEmail($pdo);
    $stmt = $pdo->prepare("SELECT id_utente, nome, cognome, email, email_verificata, {$colonnaCodiceVerifica} AS codice_verifica, codice_verifica_scadenza FROM Utenti WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $utente = $stmt->fetch();
    return $utente ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errore = 'Token di sicurezza non valido. Riprova.';
    } else {
        $azione = $_POST['azione'] ?? 'verifica';
        $utente = caricaUtenteDaEmail($pdo, $email);

        if (!$utente) {
            $errore = 'Non ho trovato un account associato a questa email.';
        } elseif ((int)$utente['email_verificata'] === 1) {
            header('Location: ' . SITE_URL . '/login.php?verified=1&email=' . urlencode($utente['email']));
            exit;
        } elseif ($azione === 'reinvia') {
            $nuovoCodice = generaCodice6();
            $scadenza = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $colonnaCodiceVerifica = colonnaCodiceVerificaEmail($pdo);
            $stmt = $pdo->prepare("UPDATE Utenti SET {$colonnaCodiceVerifica} = ?, codice_verifica_scadenza = ? WHERE id_utente = ?");
            $stmt->execute([$nuovoCodice, $scadenza, (int)$utente['id_utente']]);

            $emailInviata = inviaEmailVerificaAccount($utente['email'], $utente['nome'], $nuovoCodice);

            if ($emailInviata) {
                $messaggio = 'Ti abbiamo inviato un nuovo codice di verifica. Controlla anche la cartella spam.';
            } else {
                $errore = 'Il codice è stato rigenerato, ma la mail non è partita. Controlla SMTP/PHPMailer oppure riprova più tardi.';
            }
        } else {
            $codice = trim($_POST['codice'] ?? '');

            if (!preg_match('/^\d{6}$/', $codice)) {
                $errore = 'Inserisci il codice di verifica a 6 cifre.';
            } elseif (empty($utente['codice_verifica']) || $utente['codice_verifica'] !== $codice) {
                $errore = 'Codice non corretto. Puoi riprovare oppure rigenerare un nuovo codice.';
            } elseif (!empty($utente['codice_verifica_scadenza']) && strtotime($utente['codice_verifica_scadenza']) < time()) {
                $errore = 'Codice scaduto. Rigenera un nuovo codice.';
            } else {
                $colonnaCodiceVerifica = colonnaCodiceVerificaEmail($pdo);
                $stmt = $pdo->prepare("UPDATE Utenti SET email_verificata = 1, {$colonnaCodiceVerifica} = NULL, codice_verifica_scadenza = NULL WHERE id_utente = ?");
                $stmt->execute([(int)$utente['id_utente']]);
                header('Location: ' . SITE_URL . '/login.php?verified=1&email=' . urlencode($utente['email']));
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
        <span class="text-antracite">Verifica email</span>
    </div>
</div>

<main class="min-h-screen bg-avorio py-12 px-4">
    <div class="max-w-xl mx-auto">
        <section class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 md:p-10">
            <div class="text-center mb-8">
                <img src="<?= SITE_URL ?>/img/logo.svg" alt="Logo Museo Storico Severi" class="h-20 w-auto mx-auto mb-6 object-contain">
                <h1 class="font-display text-3xl md:text-4xl font-bold text-antracite mb-3">Verifica il tuo account</h1>
                <p class="text-gray-600">
                    Inserisci il codice a 6 cifre ricevuto via email. Se non è arrivato, puoi rigenerarlo.
                </p>
            </div>

            <?php if ($registered): ?>
                <div class="mb-6 rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm leading-relaxed">
                    ✅ Registrazione completata. Inserisci qui il codice a 6 cifre ricevuto via email per attivare l'account.
                    <?php if ($mailStatus === 'failed'): ?>
                        <br><strong>Nota:</strong> la mail potrebbe non essere partita. Puoi rigenerare un nuovo codice da questa pagina.
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($fromLogin): ?>
                <div class="mb-6 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 text-sm leading-relaxed">
                    Il tuo account non è ancora verificato. Completa la verifica per poter accedere.
                </div>
            <?php endif; ?>

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

            <?php if (!$successo): ?>
                <form method="POST" action="<?= SITE_URL ?>/verifica_email.php" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="azione" value="verifica">

                    <div>
                        <label for="email" class="block text-sm font-semibold text-antracite mb-2">Email</label>
                        <input type="email" id="email" name="email" required value="<?= clean($email) ?>" placeholder="nome@email.com"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <div>
                        <label for="codice" class="block text-sm font-semibold text-antracite mb-2">Codice di verifica</label>
                        <input type="text" id="codice" name="codice" required inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="123456"
                               class="w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-800 tracking-[0.35em] text-center font-bold focus:outline-none focus:ring-2 focus:ring-oro focus:border-oro">
                    </div>

                    <button type="submit" class="w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                        Verifica account
                    </button>
                </form>

                <form method="POST" action="<?= SITE_URL ?>/verifica_email.php" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="azione" value="reinvia">
                    <input type="hidden" name="email" value="<?= clean($email) ?>">
                    <button type="submit" class="w-full border-2 border-oro text-oro font-semibold px-6 py-3 rounded-xl hover:bg-oro hover:text-antracite transition">
                        Rigenera e invia un nuovo codice
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="block text-center w-full bg-oro text-antracite font-semibold px-6 py-3 rounded-xl hover:opacity-90 transition">
                    Vai al login
                </a>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
