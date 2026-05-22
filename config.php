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
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);

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
}
