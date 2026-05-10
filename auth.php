<?php
// ============================================================
//  Funzioni di autenticazione — Museo Storico Severi
// ============================================================
require_once __DIR__ . '/db.php';

/* ── Login ─────────────────────────────────────────────── */
function loginUtente(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM Utenti WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $utente = $stmt->fetch();

    if (!$utente || !password_verify($password, $utente['password_hash'])) {
        return ['success' => false, 'message' => 'Email o password non corretti.'];
    }

    session_regenerate_id(true);
    $_SESSION['utente_id']    = $utente['id_utente'];
    $_SESSION['utente_nome']  = $utente['nome'];
    $_SESSION['utente_email'] = $utente['email'];
    $_SESSION['utente_ruolo'] = $utente['ruolo'];

    return ['success' => true, 'ruolo' => $utente['ruolo']];
}

/* ── Normalizzazione risposta sicurezza ────────────────── */
function normalizzaRispostaSicurezza(string $risposta): string {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $risposta)), 'UTF-8');
}

/* ── Registrazione ──────────────────────────────────────── */
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

    $ins = $pdo->prepare(" 
        INSERT INTO Utenti
        (nome, cognome, email, password_hash, domanda_sicurezza, risposta_sicurezza_hash, ruolo)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $ins->execute([
        $nome,
        $cognome,
        $email,
        $passwordHash,
        $domanda_sicurezza,
        $rispostaHash,
        'visitatore'
    ]);

    return ['success' => true, 'message' => 'Registrazione completata. Puoi effettuare il login.'];
}

/* ── Logout ─────────────────────────────────────────────── */
function logoutUtente(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ── Helpers ────────────────────────────────────────────── */
function isLogged(): bool { return isset($_SESSION['utente_id']); }
function isAdmin(): bool { return ($_SESSION['utente_ruolo'] ?? '') === 'amministratore'; }
function isOperatore(): bool { return in_array($_SESSION['utente_ruolo'] ?? '', ['operatore', 'amministratore'], true); }

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLogged()) {
        header('Location: ' . SITE_URL . '/' . $redirect);
        exit;
    }
}

/* ── CSRF token ─────────────────────────────────────────── */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* ── Sanitize input ─────────────────────────────────────── */
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
