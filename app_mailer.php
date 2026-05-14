<?php
require_once __DIR__ . '/config.php';

/**
 * Mailer centralizzato - Museo Storico Severi
 *
 * Sostituisci il vecchio app_mailer.php con questo file.
 * Cosa fa:
 * - usa PHPMailer + SMTP se SMTP_ACTIVE è true e PHPMailer è presente;
 * - evita caricamenti infiniti con timeout;
 * - se SMTP fallisce prova la funzione mail() del server;
 * - se anche mail() fallisce salva la mail in mail_debug e scrive il motivo in mail_error_log.txt.
 */

function museoMailerBool($value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function museoMailDebugDir(): string
{
    $dir = __DIR__ . '/mail_debug';

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function museoMailLog(string $message): void
{
    $dir = museoMailDebugDir();

    @file_put_contents(
        $dir . '/mail_error_log.txt',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function museoMailSaveDebugCopy(string $to, string $subject, string $htmlBody): void
{
    $dir = museoMailDebugDir();
    $safeTo = preg_replace('/[^a-zA-Z0-9_@.-]/', '_', $to);
    $safeSubject = preg_replace('/[^a-zA-Z0-9_-]/', '_', substr($subject, 0, 60));

    @file_put_contents(
        $dir . '/mail_' . date('Ymd_His') . '_' . $safeSubject . '_' . $safeTo . '.html',
        "<!doctype html><html lang='it'><head><meta charset='UTF-8'><title>" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</title></head><body>" .
        "<h1>" . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . "</h1>\n" . $htmlBody .
        "</body></html>"
    );
}

function museoHeaderEncode(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function museoPlainFromHtml(string $htmlBody): string
{
    $htmlBody = str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $htmlBody);
    $plain = strip_tags($htmlBody);
    $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $plain = preg_replace("/[ \t]+/", ' ', $plain);
    $plain = preg_replace("/\n{3,}/", "\n\n", $plain);

    return trim($plain);
}

function museoFindPhpMailer(): ?string
{
    $possibleBases = [
        __DIR__ . '/PHPMailer/src',
        __DIR__ . '/phpmailer/src',
        __DIR__ . '/PHPMailer-master/src',
    ];

    foreach ($possibleBases as $base) {
        if (
            is_file($base . '/PHPMailer.php') &&
            is_file($base . '/SMTP.php') &&
            is_file($base . '/Exception.php')
        ) {
            return $base;
        }
    }

    if (is_file(__DIR__ . '/vendor/autoload.php')) {
        return 'composer';
    }

    return null;
}

function museoGetSmtpFromEmail(): string
{
    if (defined('MAIL_FROM') && filter_var(MAIL_FROM, FILTER_VALIDATE_EMAIL)) {
        return MAIL_FROM;
    }

    if (defined('SMTP_USERNAME') && filter_var(SMTP_USERNAME, FILTER_VALIDATE_EMAIL)) {
        return SMTP_USERNAME;
    }

    return 'noreply@localhost';
}

function museoGetNativeFromEmail(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $host = preg_replace('/:\d+$/', '', $host);

    if ($host !== '' && $host !== 'localhost' && $host !== '127.0.0.1') {
        return 'noreply@' . $host;
    }

    return museoGetSmtpFromEmail();
}

function museoSendMailNative(
    string $to,
    string $subject,
    string $htmlBody,
    string $plainBody = '',
    array $attachments = []
): bool {
    $fromEmail = museoGetNativeFromEmail();
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi');
    $plainBody = $plainBody !== '' ? $plainBody : museoPlainFromHtml($htmlBody);

    $headers = [];
    $headers[] = 'From: ' . museoHeaderEncode($fromName) . ' <' . $fromEmail . '>';
    $headers[] = 'Reply-To: ' . $fromEmail;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $validAttachments = [];
    foreach ($attachments as $attachment) {
        $path = is_array($attachment) ? ($attachment['path'] ?? '') : (string)$attachment;
        $name = is_array($attachment) ? ($attachment['name'] ?? basename($path)) : basename($path);

        if ($path !== '' && is_file($path) && is_readable($path)) {
            $validAttachments[] = ['path' => $path, 'name' => $name];
        } else {
            museoMailLog('Allegato ignorato perché non leggibile: ' . $path);
        }
    }

    if (empty($validAttachments)) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        $ok = @mail(
            $to,
            museoHeaderEncode($subject),
            $htmlBody,
            implode("\r\n", $headers),
            '-f' . $fromEmail
        );

        museoMailLog($ok ? 'mail() OK verso ' . $to : 'mail() FALLITA verso ' . $to);
        return $ok;
    }

    $boundary = '=_Museo_' . bin2hex(random_bytes(12));
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $message = '';
    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";

    foreach ($validAttachments as $attachment) {
        $data = chunk_split(base64_encode((string)file_get_contents($attachment['path'])));
        $name = str_replace(['"', "\r", "\n"], '', $attachment['name']);

        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: application/octet-stream; name=\"{$name}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$name}\"\r\n\r\n";
        $message .= $data . "\r\n";
    }

    $message .= '--' . $boundary . "--\r\n";

    $ok = @mail(
        $to,
        museoHeaderEncode($subject),
        $message,
        implode("\r\n", $headers),
        '-f' . $fromEmail
    );

    museoMailLog($ok ? 'mail() OK con allegati verso ' . $to : 'mail() FALLITA con allegati verso ' . $to);
    return $ok;
}


function museoPreferisciInvioRapidoSenzaAllegati(array $attachments): bool
{
    if (!empty($attachments)) {
        return false;
    }

    if (defined('MAIL_FAST_NATIVE_FIRST')) {
        return museoMailerBool(MAIL_FAST_NATIVE_FIRST);
    }

    // Per codici di verifica e recupero password è meglio non bloccare la pagina con SMTP lento.
    return true;
}

function museoSmtpTimeout(): int
{
    if (defined('SMTP_TIMEOUT')) {
        return max(2, min(15, (int)SMTP_TIMEOUT));
    }

    // Timeout breve: evita attese lunghe su registrazione e recupero password.
    return 4;
}

function museoSendMail(
    string $to,
    string $subject,
    string $htmlBody,
    string $plainBody = '',
    array $attachments = []
): bool {
    $to = trim($to);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        museoMailLog('Email destinatario non valida: ' . $to);
        museoMailSaveDebugCopy($to, $subject, $htmlBody);
        return false;
    }

    $fromEmail = museoGetSmtpFromEmail();
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : (defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi');
    $plainBody = $plainBody !== '' ? $plainBody : museoPlainFromHtml($htmlBody);

    $smtpActive = defined('SMTP_ACTIVE') && museoMailerBool(SMTP_ACTIVE);
    $phpMailerBase = museoFindPhpMailer();

    museoMailLog('Tentativo invio a ' . $to . ' | SMTP_ACTIVE=' . ($smtpActive ? 'true' : 'false') . ' | PHPMailer=' . ($phpMailerBase ?: 'non trovato'));

    if (museoPreferisciInvioRapidoSenzaAllegati($attachments)) {
        museoMailLog('Invio rapido attivo: provo prima mail() per evitare attese SMTP.');
        $nativeFirstOk = museoSendMailNative($to, $subject, $htmlBody, $plainBody, $attachments);
        if ($nativeFirstOk) {
            return true;
        }

        museoMailLog('Invio rapido con mail() non riuscito: provo SMTP con timeout breve.');
    }

    if ($smtpActive && $phpMailerBase !== null) {
        if ($phpMailerBase === 'composer') {
            require_once __DIR__ . '/vendor/autoload.php';
        } else {
            require_once $phpMailerBase . '/Exception.php';
            require_once $phpMailerBase . '/PHPMailer.php';
            require_once $phpMailerBase . '/SMTP.php';
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->isSMTP();
            $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : $fromEmail;
            $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
            $mail->Port = defined('SMTP_PORT') ? (int)SMTP_PORT : 587;

            // Evita caricamenti infiniti.
            $mail->Timeout = museoSmtpTimeout();
            $mail->SMTPKeepAlive = false;
            $mail->SMTPDebug = 0;

            if (defined('SMTP_SECURE') && strtolower((string)SMTP_SECURE) === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Aiuta su hosting dove i certificati locali possono dare problemi.
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom($fromEmail, $fromName);
            $mail->addReplyTo($fromEmail, $fromName);
            $mail->addAddress($to);

            foreach ($attachments as $attachment) {
                $path = is_array($attachment) ? ($attachment['path'] ?? '') : (string)$attachment;
                $name = is_array($attachment) ? ($attachment['name'] ?? basename($path)) : basename($path);

                if ($path !== '' && is_file($path) && is_readable($path)) {
                    $mail->addAttachment($path, $name);
                } else {
                    museoMailLog('Allegato PHPMailer ignorato perché non leggibile: ' . $path);
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();
            museoMailLog('PHPMailer/SMTP OK verso ' . $to);
            return true;
        } catch (Throwable $e) {
            $errorInfo = isset($mail) ? $mail->ErrorInfo : '';
            museoMailLog('PHPMailer/SMTP FALLITO verso ' . $to . ' | Exception: ' . $e->getMessage() . ' | ErrorInfo: ' . $errorInfo);
        }
    } elseif ($smtpActive && $phpMailerBase === null) {
        museoMailLog('SMTP_ACTIVE è true, ma PHPMailer non è stato trovato. Cartella attesa: PHPMailer/src oppure vendor/autoload.php');
    }

    // Fallback su mail() del server, solo se non era già stato provato all'inizio.
    $nativeOk = false;
    if (!museoPreferisciInvioRapidoSenzaAllegati($attachments)) {
        $nativeOk = museoSendMailNative($to, $subject, $htmlBody, $plainBody, $attachments);
    }
    if ($nativeOk) {
        return true;
    }

    museoMailLog('Invio definitivo FALLITO verso ' . $to . '. Salvo copia HTML in mail_debug.');
    museoMailSaveDebugCopy($to, $subject, $htmlBody);

    return false;
}

function inviaEmailVerificaAccount(string $email, string $nome, string $codice): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi';
    $subject = 'Codice di verifica - ' . $siteName;

    $codiceHtml = htmlspecialchars($codice, ENT_QUOTES, 'UTF-8');
    $nomeHtml = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $siteHtml = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');

    $body = "
        <div style='font-family:Arial,sans-serif;line-height:1.6;color:#2C2C2C;max-width:620px;margin:auto;border:1px solid #e5e0d0;border-radius:14px;overflow:hidden;'>
            <div style='background:#2C2C2C;color:#F5F0E8;padding:24px;text-align:center;'>
                <h1 style='margin:0;color:#C9A84C;'>{$siteHtml}</h1>
                <p style='margin:8px 0 0;'>Verifica il tuo account</p>
            </div>
            <div style='padding:26px;background:#ffffff;'>
                <p>Ciao <strong>{$nomeHtml}</strong>,</p>
                <p>grazie per la registrazione. Inserisci questo codice nella pagina di verifica:</p>
                <div style='font-size:32px;letter-spacing:8px;font-weight:bold;text-align:center;background:#F5F0E8;border:1px solid #C9A84C;border-radius:12px;padding:18px;margin:24px 0;color:#2C2C2C;'>{$codiceHtml}</div>
                <p>Se non hai richiesto tu la registrazione, ignora questa email.</p>
            </div>
        </div>
    ";

    return museoSendMail($email, $subject, $body, "Codice di verifica: {$codice}");
}

function inviaEmailCodiceRecuperoPassword(string $email, string $nome, string $codice): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi';
    $subject = 'Codice recupero password - ' . $siteName;

    $codiceHtml = htmlspecialchars($codice, ENT_QUOTES, 'UTF-8');
    $nomeHtml = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $siteHtml = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');

    $body = "
        <div style='font-family:Arial,sans-serif;line-height:1.6;color:#2C2C2C;max-width:620px;margin:auto;border:1px solid #e5e0d0;border-radius:14px;overflow:hidden;'>
            <div style='background:#2C2C2C;color:#F5F0E8;padding:24px;text-align:center;'>
                <h1 style='margin:0;color:#C9A84C;'>{$siteHtml}</h1>
                <p style='margin:8px 0 0;'>Recupero password</p>
            </div>
            <div style='padding:26px;background:#ffffff;'>
                <p>Ciao <strong>{$nomeHtml}</strong>,</p>
                <p>per completare il recupero password inserisci questo codice insieme alla risposta di sicurezza:</p>
                <div style='font-size:32px;letter-spacing:8px;font-weight:bold;text-align:center;background:#F5F0E8;border:1px solid #C9A84C;border-radius:12px;padding:18px;margin:24px 0;color:#2C2C2C;'>{$codiceHtml}</div>
                <p>Il codice scade dopo pochi minuti. Se non hai richiesto tu il recupero, ignora questa email.</p>
            </div>
        </div>
    ";

    return museoSendMail($email, $subject, $body, "Codice recupero password: {$codice}");
}

// Alias per compatibilità con eventuali vecchie chiamate.
function inviaEmailRecuperoPassword(string $email, string $nome, string $codice): bool
{
    return inviaEmailCodiceRecuperoPassword($email, $nome, $codice);
}

function inviaEmailConfermaOrdine(array $ordine, array $codici = [], string $pdfPath = ''): bool
{
    $email = (string)($ordine['email_cliente'] ?? $ordine['email'] ?? $ordine['utente_email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        museoMailLog('Conferma ordine non inviata: email mancante o non valida.');
        return false;
    }

    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi';
    $codiceOrdine = (string)($ordine['codice_recupero'] ?? $ordine['codice_ordine'] ?? ('ORD-' . ($ordine['id_ordine'] ?? '')));
    $nome = htmlspecialchars((string)($ordine['nome_cliente'] ?? $ordine['nome'] ?? 'Visitatore'), ENT_QUOTES, 'UTF-8');
    $totale = number_format((float)($ordine['importo_totale'] ?? $ordine['totale'] ?? 0), 2, ',', '.');
    $stato = htmlspecialchars((string)($ordine['stato_pagamento'] ?? 'Pagato'), ENT_QUOTES, 'UTF-8');
    $metodo = htmlspecialchars((string)($ordine['metodo_pagamento'] ?? 'Non indicato'), ENT_QUOTES, 'UTF-8');
    $codiceHtml = htmlspecialchars($codiceOrdine, ENT_QUOTES, 'UTF-8');
    $siteHtml = htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8');

    $righeBiglietti = '';
    foreach ($codici as $codice) {
        $righeBiglietti .= '<li style="margin:4px 0;"><strong>' . htmlspecialchars((string)$codice, ENT_QUOTES, 'UTF-8') . '</strong></li>';
    }
    if ($righeBiglietti === '') {
        $righeBiglietti = '<li>Nessun codice biglietto disponibile.</li>';
    }

    $subject = 'Conferma ordine ' . $codiceOrdine . ' - ' . $siteName;
    $body = "
        <div style='font-family:Arial,sans-serif;line-height:1.6;color:#2C2C2C;max-width:680px;margin:auto;border:1px solid #e5e0d0;border-radius:14px;overflow:hidden;'>
            <div style='background:#2C2C2C;color:#F5F0E8;padding:24px;text-align:center;'>
                <h1 style='margin:0;color:#C9A84C;'>{$siteHtml}</h1>
                <p style='margin:8px 0 0;'>Conferma ordine</p>
            </div>
            <div style='padding:26px;background:#ffffff;'>
                <p>Ciao <strong>{$nome}</strong>,</p>
                <p>ti confermiamo l'ordine <strong>{$codiceHtml}</strong>.</p>
                <p><strong>Metodo pagamento:</strong> {$metodo}<br>
                   <strong>Stato pagamento:</strong> {$stato}<br>
                   <strong>Totale:</strong> € {$totale}</p>
                <p><strong>Codici biglietto:</strong></p>
                <ul>{$righeBiglietti}</ul>
                <p>In allegato trovi il PDF riepilogativo dell'ordine, se disponibile.</p>
            </div>
        </div>
    ";

    $attachments = [];
    if ($pdfPath !== '' && is_file($pdfPath) && is_readable($pdfPath)) {
        $attachments[] = [
            'path' => $pdfPath,
            'name' => 'ordine_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codiceOrdine) . '.pdf',
        ];
    }

    return museoSendMail($email, $subject, $body, museoPlainFromHtml($body), $attachments);
}
