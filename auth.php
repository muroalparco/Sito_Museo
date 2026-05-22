<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/app_mailer.php';

function generaCodice6(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function colonnaCodiceVerificaEmail(PDO $pdo): string {
    static $colonna = null;

    if ($colonna !== null) {
        return $colonna;
    }

    $candidate = ['codice_verifica_email', 'codice_verifica'];

    foreach ($candidate as $nomeColonna) {
        try {
            $stmt = $pdo->prepare("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'Utenti'
                  AND COLUMN_NAME = ?
                LIMIT 1
            ");
            $stmt->execute([$nomeColonna]);
            if ($stmt->fetchColumn()) {
                $colonna = $nomeColonna;
                return $colonna;
            }
        } catch (Throwable $e) {
            // Alcuni hosting limitano INFORMATION_SCHEMA: proviamo con SHOW COLUMNS.
        }

        try {
            $stmt = $pdo->prepare('SHOW COLUMNS FROM Utenti LIKE ?');
            $stmt->execute([$nomeColonna]);
            if ($stmt->fetch()) {
                $colonna = $nomeColonna;
                return $colonna;
            }
        } catch (Throwable $e) {
            // Se anche SHOW COLUMNS non è disponibile, passiamo al test SELECT.
        }

        try {
            $pdo->query('SELECT `' . $nomeColonna . '` FROM Utenti LIMIT 1');
            $colonna = $nomeColonna;
            return $colonna;
        } catch (Throwable $e) {
            // Colonna non presente: proviamo la successiva.
        }
    }

    // Il database unificato aggiornato usa codice_verifica_email.
    $colonna = 'codice_verifica_email';
    return $colonna;
}

/* Login */
function loginUtente(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM Utenti WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $utente = $stmt->fetch();

    if (!$utente || !password_verify($password, $utente['password_hash'])) {
        return ['success' => false, 'message' => 'Email o password non corretti.'];
    }

    // Se la colonna esiste ed è a 0, l'utente non può ancora accedere.
    if (array_key_exists('email_verificata', $utente) && (int)$utente['email_verificata'] !== 1) {
        return [
            'success' => false,
            'verification_required' => true,
            'email' => $utente['email'],
            'message' => 'Account non ancora verificato.'
        ];
    }

    session_regenerate_id(true);
    $_SESSION['utente_id']    = $utente['id_utente'];
    $_SESSION['utente_nome']  = $utente['nome'];
    $_SESSION['utente_email'] = $utente['email'];
    $_SESSION['utente_ruolo'] = $utente['ruolo'];

    return ['success' => true, 'ruolo' => $utente['ruolo']];
}

/* Normalizzazione risposta sicurezza */
function normalizzaRispostaSicurezza(string $risposta): string {
    return mb_strtolower(trim(preg_replace('/\s+/', ' ', $risposta)), 'UTF-8');
}

/* Registrazione con verifica email */
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
    $codiceVerifica = generaCodice6();
    $scadenza = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $colonnaCodiceVerifica = colonnaCodiceVerificaEmail($pdo);

    $ins = $pdo->prepare(" 
        INSERT INTO Utenti 
(nome, cognome, email, password_hash, domanda_sicurezza, risposta_sicurezza_hash, ruolo, email_verificata, {$colonnaCodiceVerifica}, codice_verifica_scadenza)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $ins->execute([
        $nome,
        $cognome,
        $email,
        $passwordHash,
        $domanda_sicurezza,
        $rispostaHash,
        'visitatore',
        0,
        $codiceVerifica,
        $scadenza
    ]);

    // La registrazione deve rimanere valida anche se il server mail non funziona.
    $emailInviata = inviaEmailVerificaAccount($email, $nome, $codiceVerifica);

    return [
        'success' => true,
        'message' => 'Registrazione completata. Prima di accedere devi confermare l’account con il codice ricevuto via email.',
        'verification_required' => true,
        'email' => $email,
        'email_sent' => $emailInviata
    ];
}

/* Logout */
function logoutUtente(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* Ruoli */
function isLogged(): bool { return isset($_SESSION['utente_id']); }
function ruoloCorrente(): string { return $_SESSION['utente_ruolo'] ?? ''; }
function isTester(): bool { return ruoloCorrente() === 'tester'; }
function isAdmin(): bool { return in_array(ruoloCorrente(), ['amministratore', 'tester'], true); }
function isOperatore(): bool { return in_array(ruoloCorrente(), ['operatore', 'tester'], true); }
function isCassiere(): bool { return in_array(ruoloCorrente(), ['cassiere', 'tester'], true); }

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLogged()) {
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



/* Helpers database / sicurezza ordini / portafoglio */
if (!function_exists('colonnaEsiste')) {
    function colonnaEsiste(PDO $pdo, string $tabella, string $colonna): bool {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tabella` LIKE ?");
            $stmt->execute([$colonna]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return true;
            }
        } catch (Throwable $e) {
            // Proviamo il fallback sotto: su alcuni hosting SHOW COLUMNS può fallire in modo non bloccante.
        }

        try {
            $stmt = $pdo->query("SELECT `$colonna` FROM `$tabella` LIMIT 1");
            return $stmt !== false;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('preparaPortafoglioUtente')) {
    function preparaPortafoglioUtente(PDO $pdo): void {
        static $done = false;
        if ($done) return;

        // Il sito usa SOLO Utenti.saldo_utente.
        // Se su vecchie versioni era stato usato saldo_portafoglio, copiamo il valore una sola volta
        // quando saldo_utente è ancora a zero. Non aggiungiamo nuove colonne inutili.
        try {
            if ($pdo->inTransaction()) {
                if (!colonnaEsiste($pdo, 'Utenti', 'saldo_utente')) {
                    throw new RuntimeException('Il portafoglio virtuale non e ancora configurato nel database.');
                }
                $done = true;
                return;
            }

            if (!colonnaEsiste($pdo, 'Utenti', 'saldo_utente')) {
                try {
                    $pdo->exec("ALTER TABLE Utenti ADD COLUMN saldo_utente DECIMAL(10,2) NOT NULL DEFAULT 0.00");
                } catch (Throwable $e) {
                    // Se il DB non permette ALTER, l'errore reale emergerà nelle query successive.
                }
            }

            if (colonnaEsiste($pdo, 'Utenti', 'saldo_utente') && colonnaEsiste($pdo, 'Utenti', 'saldo_portafoglio')) {
                $pdo->exec("UPDATE Utenti SET saldo_utente = saldo_portafoglio WHERE saldo_utente = 0 AND saldo_portafoglio > 0");
            }

            $done = colonnaEsiste($pdo, 'Utenti', 'saldo_utente');
        } catch (Throwable $e) {
            // Non blocchiamo il sito: le funzioni di pagamento gestiranno eventuali errori.
        }
    }
}

if (!function_exists('saldoUtenteCorrente')) {
    function saldoUtenteCorrente(?PDO $pdo = null, ?int $idUtente = null): float {
        if ($idUtente === null) {
            $idUtente = (int)($_SESSION['utente_id'] ?? 0);
        }
        if ($idUtente <= 0) return 0.0;

        try {
            $pdo = $pdo ?: getDB();
            preparaPortafoglioUtente($pdo);
            $stmt = $pdo->prepare('SELECT saldo_utente FROM Utenti WHERE id_utente = ? LIMIT 1');
            $stmt->execute([$idUtente]);
            $saldo = $stmt->fetchColumn();
            return $saldo === false ? 0.0 : (float)$saldo;
        } catch (Throwable $e) {
            return 0.0;
        }
    }
}

if (!function_exists('ricaricaSaldoUtente')) {
    function ricaricaSaldoUtente(PDO $pdo, int $idUtente, float $importo): void {
        if ($idUtente <= 0 || $importo <= 0) {
            throw new RuntimeException('Importo non valido.');
        }
        preparaPortafoglioUtente($pdo);
        try {
            $stmt = $pdo->prepare('UPDATE Utenti SET saldo_utente = saldo_utente + ? WHERE id_utente = ?');
            $stmt->execute([$importo, $idUtente]);
        } catch (Throwable $e) {
            throw new RuntimeException('Il portafoglio virtuale non è disponibile. Controlla la colonna Utenti.saldo_utente.');
        }
    }
}

if (!function_exists('addebitaSaldoUtente')) {
    function addebitaSaldoUtente(PDO $pdo, int $idUtente, float $importo): void {
        if ($idUtente <= 0 || $importo < 0) {
            throw new RuntimeException('Importo non valido.');
        }
        preparaPortafoglioUtente($pdo);
        try {
            $stmt = $pdo->prepare('SELECT saldo_utente FROM Utenti WHERE id_utente = ? FOR UPDATE');
            $stmt->execute([$idUtente]);
            $saldo = $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException('Il portafoglio virtuale non è disponibile. Controlla la colonna Utenti.saldo_utente.');
        }

        if ($saldo === false) {
            throw new RuntimeException('Utente non trovato.');
        }
        if ((float)$saldo + 0.0001 < $importo) {
            throw new RuntimeException('Saldo insufficiente nel portafoglio virtuale.');
        }
        $stmt = $pdo->prepare('UPDATE Utenti SET saldo_utente = saldo_utente - ? WHERE id_utente = ?');
        $stmt->execute([$importo, $idUtente]);
    }
}

if (!function_exists('ordineAutorizzato')) {
    function ordineAutorizzato($pdoOrOrdine, ?array $ordine = null): bool {
        // Compatibile sia con ordineAutorizzato($ordine) sia con ordineAutorizzato($pdo, $ordine)
        if ($ordine === null && is_array($pdoOrOrdine)) {
            $ordine = $pdoOrOrdine;
        }
        if (!$ordine) return false;
        if (isAdmin() || isCassiere()) return true;
        $idUtenteOrdine = isset($ordine['id_utente']) ? (int)$ordine['id_utente'] : 0;
        $idUtenteSessione = (int)($_SESSION['utente_id'] ?? 0);
        if ($idUtenteOrdine > 0 && $idUtenteSessione > 0 && $idUtenteOrdine === $idUtenteSessione) return true;
        $codice = (string)($ordine['codice_recupero'] ?? '');
        if ($codice !== '' && isset($_SESSION['ordini_recuperati']) && is_array($_SESSION['ordini_recuperati'])) {
            return in_array($codice, $_SESSION['ordini_recuperati'], true);
        }
        return false;
    }
}

/* CSRF */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/* Escape output */
function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
