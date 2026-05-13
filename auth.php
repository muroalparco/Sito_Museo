<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/app_mailer.php';

/*  Login  */
function loginUtente(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM Utenti WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $utente = $stmt->fetch();

    if (!$utente || !password_verify($password, $utente['password_hash'])) {
        return ['success' => false, 'message' => 'Email o password non corretti.'];
    }

    if (array_key_exists('email_verificata', $utente) && (int)$utente['email_verificata'] !== 1) {
        return [
            'success' => false,
            'message' => 'Account non ancora verificato. Controlla la tua email e inserisci il codice di conferma nella pagina di verifica.',
            'verification_required' => true,
            'email' => $utente['email']
        ];
    }

    session_regenerate_id(true);
    $_SESSION['utente_id']      = $utente['id_utente'];
    $_SESSION['utente_nome']    = $utente['nome'];
    $_SESSION['utente_cognome'] = $utente['cognome'] ?? '';
    $_SESSION['utente_email']   = $utente['email'];
    $_SESSION['utente_ruolo']   = $utente['ruolo'];

    return ['success' => true, 'ruolo' => $utente['ruolo']];
}

/*  normalizzazione risposta sicurezza  */
function normalizzaRispostaSicurezza(string $risposta): string {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $risposta)), 'UTF-8');
}

function generaCodiceVerificaEmail(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/*  registrazione  */
function registraUtente(
    string $nome,
    string $cognome,
    string $email,
    string $password,
    string $domanda_sicurezza,
    string $risposta_sicurezza
): array {
    $pdo = getDB();

    $check = $pdo->prepare('SELECT id_utente FROM Utenti WHERE email = ? LIMIT 1');
    $check->execute([$email]);

    if ($check->fetch()) {
        return ['success' => false, 'message' => 'Email già registrata.'];
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $rispostaHash = password_hash(normalizzaRispostaSicurezza($risposta_sicurezza), PASSWORD_BCRYPT, ['cost' => 12]);
    $codiceVerifica = generaCodiceVerificaEmail();
    $scadenza = date('Y-m-d H:i:s', strtotime('+24 hours'));

    try {
        $pdo->beginTransaction();

        $ins = $pdo->prepare("
            INSERT INTO Utenti
            (nome, cognome, email, password_hash, domanda_sicurezza, risposta_sicurezza_hash, ruolo, email_verificata, codice_verifica_email, codice_verifica_scadenza)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
        ");

        $ins->execute([
            $nome,
            $cognome,
            $email,
            $passwordHash,
            $domanda_sicurezza,
            $rispostaHash,
            'visitatore',
            $codiceVerifica,
            $scadenza
        ]);

        $emailInviata = inviaEmailVerificaAccount($email, $nome, $codiceVerifica);

        if (!$emailInviata) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Non è stato possibile inviare la mail di verifica. Controlla la configurazione email e riprova.'
            ];
        }

        $pdo->commit();

        return [
            'success' => true,
            'verification_required' => true,
            'email' => $email,
            'message' => 'Registrazione completata. Prima di accedere devi verificare la tua email con il codice ricevuto.'
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Errore registrazione utente: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Errore durante la registrazione. Riprova più tardi.'];
    }
}

function verificaCodiceEmail(string $email, string $codice): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id_utente, email_verificata, codice_verifica_email, codice_verifica_scadenza FROM Utenti WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $utente = $stmt->fetch();

    if (!$utente) {
        return ['success' => false, 'message' => 'Utente non trovato.'];
    }

    if ((int)$utente['email_verificata'] === 1) {
        return ['success' => true, 'message' => 'Email già verificata. Puoi accedere.'];
    }

    if (($utente['codice_verifica_email'] ?? '') !== $codice) {
        return ['success' => false, 'message' => 'Codice di verifica non corretto.'];
    }

    if (!empty($utente['codice_verifica_scadenza']) && strtotime($utente['codice_verifica_scadenza']) < time()) {
        return ['success' => false, 'message' => 'Codice scaduto. Richiedi una nuova registrazione o contatta il museo.'];
    }

    $upd = $pdo->prepare('UPDATE Utenti SET email_verificata = 1, codice_verifica_email = NULL, codice_verifica_scadenza = NULL WHERE id_utente = ?');
    $upd->execute([(int)$utente['id_utente']]);

    return ['success' => true, 'message' => 'Email verificata correttamente. Ora puoi accedere.'];
}

/*  Logout  */
function logoutUtente(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/*  visualizza ruolo  */
function isLogged(): bool { return isset($_SESSION['utente_id']); }
function isAdmin(): bool { return ($_SESSION['utente_ruolo'] ?? '') === 'amministratore'; }
function isOperatore(): bool { return ($_SESSION['utente_ruolo'] ?? '') === 'operatore'; }
function isCassiere(): bool { return ($_SESSION['utente_ruolo'] ?? '') === 'cassiere'; }

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/' . $redirect);
        exit;
    }
}


function requireCassiere(string $redirect = 'index.php'): void {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    if (!isCassiere()) {
        header('Location: ' . SITE_URL . '/' . $redirect);
        exit;
    }
}

function requireAdmin(string $redirect = 'index.php'): void {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/' . $redirect);
        exit;
    }
}

/* token hash csrf */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* per evitare il coso injeection */
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
