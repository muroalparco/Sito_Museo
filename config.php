<?php
/* qua vanno messe le cose per metterlo in un posto diverso da xampp*/

define('DB_HOST', 'localhost');
define('DB_NAME', 'biglietteria_museo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Museo Storico Severi');

// URL del sito calcolata automaticamente.
// Funziona sia in locale, per esempio http://localhost/museo,
// sia su Altervista, per esempio https://nomeaccount.altervista.org/museo.
if (!defined('SITE_URL')) {
    $protocollo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $cartella = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    define('SITE_URL', $protocollo . '://' . $host . ($cartella && $cartella !== '.' ? $cartella : ''));
}

define('SMTP_ACTIVE', true);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'noreply.museostoricoseveri@gmail.com');
define('SMTP_PASSWORD', 'bwdswhuwzrhhiduy');

define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

define('MAIL_FROM', 'noreply.museostoricoseveri@gmail.com');
define('MAIL_FROM_NAME', 'Museo Storico Severi');

// Sessione sicura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}
