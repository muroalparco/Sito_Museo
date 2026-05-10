<?php
// ============================================================
//  Connessione PDO — Museo Storico Severi
// ============================================================
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In produzione loggare l'errore, non mostrarlo
            error_log('DB Connection Error: ' . $e->getMessage());
            die(json_encode(['error' => 'Errore di connessione al database.']));
        }
    }
    return $pdo;
}
