<?php
/* connesione database */
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


function dbColumnExists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->fetch()) {
            return true;
        }
    } catch (Throwable $e) {
        // Fallback sotto.
    }

    try {
        $pdo->query("SELECT `$column` FROM `$table` LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function walletColumn(PDO $pdo): ?string {
    static $column = null;
    static $checked = false;

    if ($checked) {
        return $column;
    }

    $checked = true;
    // Da ora il progetto usa una sola colonna ufficiale per il portafoglio.
    $column = dbColumnExists($pdo, 'Utenti', 'saldo_utente') ? 'saldo_utente' : null;
    return $column;
}

function walletIsActive(PDO $pdo): bool {
    return walletColumn($pdo) === 'saldo_utente';
}

function walletBalance(PDO $pdo, int $idUtente): float {
    $column = walletColumn($pdo);
    if (!$column) {
        return 0.0;
    }

    $stmt = $pdo->prepare("SELECT `$column` FROM Utenti WHERE id_utente = ? LIMIT 1");
    $stmt->execute([$idUtente]);
    $row = $stmt->fetch() ?: [];
    return (float)($row[$column] ?? 0);
}

function walletAdd(PDO $pdo, int $idUtente, float $amount): void {
    if ($amount <= 0) {
        throw new RuntimeException('Importo non valido.');
    }

    $column = walletColumn($pdo);
    if (!$column) {
        throw new RuntimeException('Il portafoglio virtuale non è ancora attivo nel database. Aggiungi la colonna saldo_utente alla tabella Utenti.');
    }

    $stmt = $pdo->prepare("UPDATE Utenti SET `$column` = `$column` + ? WHERE id_utente = ?");
    $stmt->execute([$amount, $idUtente]);
}

function walletDebit(PDO $pdo, int $idUtente, float $amount): void {
    if ($amount <= 0) {
        throw new RuntimeException('Importo non valido.');
    }

    $column = walletColumn($pdo);
    if (!$column) {
        throw new RuntimeException('Il portafoglio virtuale non è ancora attivo nel database. Aggiungi la colonna saldo_utente alla tabella Utenti.');
    }

    $stmt = $pdo->prepare("SELECT `$column` FROM Utenti WHERE id_utente = ? FOR UPDATE");
    $stmt->execute([$idUtente]);
    $row = $stmt->fetch() ?: [];
    $saldo = (float)($row[$column] ?? 0);

    if ($saldo + 0.00001 < $amount) {
        throw new RuntimeException('Saldo portafoglio insufficiente. Ricarica il portafoglio o scegli un altro metodo di pagamento.');
    }

    $stmt = $pdo->prepare("UPDATE Utenti SET `$column` = `$column` - ? WHERE id_utente = ?");
    $stmt->execute([$amount, $idUtente]);
}
