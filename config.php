<?php

/* =========================
   RICONOSCIMENTO AMBIENTE
   ========================= */

$hostAttuale = $_SERVER['HTTP_HOST'] ?? 'localhost';
$hostNome = strtolower((string)(parse_url('http://' . $hostAttuale, PHP_URL_HOST) ?: $hostAttuale));

$isLocalhost =
    $hostNome === 'localhost' ||
    $hostNome === '127.0.0.1' ||
    $hostNome === '::1' ||
    substr($hostNome, -10) === '.localhost';


$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
    ((string)($_SERVER['SERVER_PORT'] ?? '') === '443');

if (!isset($GLOBALS['CSP_NONCE'])) {
    try {
        $GLOBALS['CSP_NONCE'] = base64_encode(random_bytes(16));
    } catch (Throwable $e) {
        $GLOBALS['CSP_NONCE'] = base64_encode(uniqid('', true));
    }
}

if (!function_exists('cspNonce')) {
    function cspNonce(): string {
        return $GLOBALS['CSP_NONCE'] ?? '';
    }
}

if (!function_exists('cspEventHashes')) {
    function cspEventHashes(): string {
        $handlers = [
            "showTab('dashboard')",
            "showTab('profilo')",
            "showTab('portafoglio')",
            "showTab('sicurezza')",
            "showTab('ordini')",
            "window.print()",
            "togglePasswordVisibility('password', this)",
            "togglePasswordVisibility('confirm', this)",
            "checkStrength(this.value)",
            "return confirm('Eliminare questa fascia oraria?');",
            "return confirm('Eliminare questa categoria?');",
            "return confirm('Eliminare definitivamente questo account?');",
            "return confirm('Accettare questo rimborso e riaccreditare il portafoglio utente?');",
            "return confirm('Rifiutare questo rimborso?');",
            "return confirm('Confermi di voler segnare questo ordine come pagato?');",
            "return confirm('Confermi la validazione di tutti i biglietti validi di questo ordine?');",
            "return confirm('Validare questo biglietto?');",
            "return confirm('Confermi la validazione di questo biglietto?');",
        ];
        $hashes = [];
        foreach ($handlers as $handler) {
            $hashes[] = "'sha256-" . base64_encode(hash('sha256', $handler, true)) . "'";
        }
        return implode(' ', array_unique($hashes));
    }
}

if (!$isLocalhost && !headers_sent()) {
    $nonce = cspNonce();
    header('Strict-Transport-Security: max-age=15552000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://unpkg.com 'nonce-{$nonce}'; script-src-attr 'unsafe-hashes' " . cspEventHashes() . "; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests");
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=()');
}

/* =========================
   ERRORI PHP
   =========================
   Per ora li mostriamo anche online,
   così se c'è un problema lo vediamo.
   Quando il sito è stabile, metti display_errors a 0 online.
*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


/* =========================
   DATABASE
   ========================= */

if ($isLocalhost) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'biglietteria_museo');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'my_museostoricoseveri');
    define('DB_USER', 'museostoricoseveri');
    define('DB_PASS', '');
}

define('DB_CHARSET', 'utf8mb4');


/* =========================
   SITO
   ========================= */

define('SITE_NAME', 'Museo Storico Severi');

if (!defined('SITE_URL')) {
    if ($isLocalhost) {
        $protocollo = 'http';
    } else {
        $protocollo = 'https';
    }

    $cartella = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    define(
        'SITE_URL',
        $protocollo . '://' . $hostAttuale . ($cartella && $cartella !== '.' ? $cartella : '')
    );
}


/* =========================
   SMTP / PHPMailer
   ========================= */

define('SMTP_ACTIVE', true);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'noreply.museostoricoseveri@gmail.com');

/*
   Inserisci qui la nuova password per app Google,
   senza spazi.
*/
define('SMTP_PASSWORD', 'bwdswhuwzrhhiduy');

define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

define('MAIL_FROM', 'noreply.museostoricoseveri@gmail.com');
define('MAIL_FROM_NAME', 'Museo Storico Severi');


/* =========================
   SESSIONE
   ========================= */

if (session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');

    $cookieParams = session_get_cookie_params();
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookieParams['path'] ?: '/',
            'domain' => $cookieParams['domain'] ?: '',
            'secure' => !$isLocalhost,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    if (!$isLocalhost) {
        ini_set('session.cookie_secure', 1);
    } else {
        $sessionPathLocale = __DIR__ . '/tmp/sessions';
        if (!is_dir($sessionPathLocale)) {
            @mkdir($sessionPathLocale, 0775, true);
        }
        if (is_dir($sessionPathLocale) && is_writable($sessionPathLocale)) {
            ini_set('session.save_path', $sessionPathLocale);
        }
    }

    session_start();

    if (!$isLocalhost && !headers_sent()) {
        header('Cache-Control: private, no-cache, max-age=0, must-revalidate');
        header('Pragma:');
        header('Expires:');
    }
}
