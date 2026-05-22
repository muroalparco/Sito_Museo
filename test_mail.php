<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_mailer.php';

$email = trim($_GET['email'] ?? '');
$tipo = trim($_GET['tipo'] ?? 'recupero');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo '<h2>Test email Museo Storico Severi</h2>';
    echo '<p>Usa questo indirizzo:</p>';
    echo '<pre>' . htmlspecialchars((defined('SITE_URL') ? SITE_URL : '') . '/test_mail.php?email=latuaemail@gmail.com', ENT_QUOTES, 'UTF-8') . '</pre>';
    exit;
}

$codice = (string)random_int(100000, 999999);

if ($tipo === 'verifica') {
    $ok = inviaEmailVerificaAccount($email, 'Test', $codice);
} else {
    $ok = inviaEmailCodiceRecuperoPassword($email, 'Test', $codice);
}

$logFile = __DIR__ . '/mail_debug/mail_error_log.txt';
$log = is_file($logFile) ? file_get_contents($logFile) : 'Nessun file mail_error_log.txt trovato.';
$logTail = implode("\n", array_slice(explode("\n", $log), -30));

?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test email</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; line-height: 1.5; }
        .ok { color: #0f7a31; }
        .ko { color: #a40000; }
        pre { background: #f5f5f5; padding: 16px; overflow: auto; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Test email Museo Storico Severi</h1>

    <?php if ($ok): ?>
        <h2 class="ok">Invio riuscito</h2>
        <p>Controlla la casella email e anche lo spam.</p>
    <?php else: ?>
        <h2 class="ko">Invio non riuscito</h2>
        <p>Il contenuto della mail potrebbe essere stato salvato in <code>mail_debug</code>. Guarda il log sotto.</p>
    <?php endif; ?>

    <p><strong>Email:</strong> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Codice test:</strong> <?= htmlspecialchars($codice, ENT_QUOTES, 'UTF-8') ?></p>

    <h2>Ultime righe del log</h2>
    <pre><?= htmlspecialchars($logTail, ENT_QUOTES, 'UTF-8') ?></pre>

    <p style="color:#a40000"><strong>Importante:</strong> elimina questo file dal server quando hai finito il test.</p>
</body>
</html>
