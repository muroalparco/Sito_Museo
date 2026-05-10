<?php
// ============================================================
//  Configurazione database — Museo Storico Severi
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'biglietteria_museo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Museo Storico Severi');
define('SITE_URL',  'http://localhost/museo');

// Sessione sicura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}
