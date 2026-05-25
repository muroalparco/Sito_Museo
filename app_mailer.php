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


function museoEmailSafe(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function museoEmailUrl(string $path = ''): string
{
    $base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
    if ($path === '') {
        return $base;
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return $base . '/' . ltrim($path, '/');
}

function museoEmailTemplate(string $title, string $subtitle, string $contentHtml, array $options = []): string
{
    $siteName = 'Museo Storico Severi';
    $safeSite = museoEmailSafe($siteName);
    $safeTitle = museoEmailSafe($title);
    $safeSubtitle = museoEmailSafe($subtitle);
    $badge = museoEmailSafe((string)($options['badge'] ?? 'Comunicazione del museo'));
    $ctaText = trim((string)($options['cta_text'] ?? ''));
    $ctaUrl = trim((string)($options['cta_url'] ?? ''));
    $cta = '';

    if ($ctaText !== '' && $ctaUrl !== '') {
        $cta =
            '<table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:26px auto 0;">' . "\r\n" .
            '<tr><td align="center" style="border-radius:12px;background:#8EC5E8;">' . "\r\n" .
            '<a href="' . museoEmailSafe($ctaUrl) . '" style="display:inline-block;color:#102744;text-decoration:none;font-weight:700;border-radius:12px;padding:13px 22px;font-size:14px;line-height:1.2;min-width:170px;text-align:center;">' . museoEmailSafe($ctaText) . '</a>' . "\r\n" .
            '</td></tr></table>' . "\r\n";
    }

    return "<!doctype html>\r\n" .
        "<html lang=\"it\">\r\n" .
        "<head>\r\n" .
        "<meta charset=\"UTF-8\">\r\n" .
        "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\r\n" .
        "<title>{$safeTitle}</title>\r\n" .
        "</head>\r\n" .
        "<body style=\"margin:0;padding:0;background:#f3f8fc;font-family:Arial,Helvetica,sans-serif;color:#12233B;\">\r\n" .
        "<table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"background:#f3f8fc;padding:28px 12px;\">\r\n" .
        "<tr><td align=\"center\">\r\n" .
        "<table role=\"presentation\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" style=\"max-width:680px;background:#ffffff;border:1px solid #d8e8f7;border-radius:24px;overflow:hidden;box-shadow:0 18px 45px rgba(16,39,68,.12);\">\r\n" .
        "<tr><td style=\"background:#102744;padding:30px 28px;color:#ffffff;\">\r\n" .
        "<div style=\"font-size:12px;color:#8EC5E8;font-weight:700;margin-bottom:10px;text-transform:uppercase;\">{$badge}</div>\r\n" .
        "<div style=\"font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;font-weight:700;margin-bottom:8px;color:#ffffff;\">{$safeSite}</div>\r\n" .
        "<div style=\"font-size:15px;color:#d8edf9;line-height:1.45;\">{$safeSubtitle}</div>\r\n" .
        "</td></tr>\r\n" .
        "<tr><td style=\"padding:30px 28px;\">\r\n" .
        "<h1 style=\"font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.18;margin:0 0 18px;color:#102744;\">{$safeTitle}</h1>\r\n" .
        $contentHtml . "\r\n" .
        $cta .
        "<div style=\"margin-top:28px;border-top:1px solid #e5f0f7;padding-top:18px;color:#6b7280;font-size:12px;line-height:1.5;\">\r\n" .
        "Email generata automaticamente dal Museo Storico Severi. Conserva questo messaggio come promemoria della tua operazione.\r\n" .
        "</div>\r\n" .
        "</td></tr></table>\r\n" .
        "</td></tr></table>\r\n" .
        "</body></html>\r\n";
}

function museoEmailInfoBox(array $items, string $tone = 'info'): string
{
    $bg = $tone === 'success' ? '#e8f7ee' : ($tone === 'warning' ? '#fff8e1' : '#f7fbff');
    $border = $tone === 'success' ? '#9bd5ae' : ($tone === 'warning' ? '#e1c16e' : '#d8e8f7');
    $html = '<div style="background:' . $bg . ';border:1px solid ' . $border . ';border-radius:16px;padding:16px 18px;margin:18px 0;">';
    foreach ($items as $label => $value) {
        $html .= '<div style="margin:5px 0;font-size:14px;"><strong style="color:#102744;">' . museoEmailSafe((string)$label) . ':</strong> ' . museoEmailSafe((string)$value) . '</div>';
    }
    return $html . '</div>';
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

function museoIsOnlineServer(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host);

    return $host !== '' && $host !== 'localhost' && $host !== '127.0.0.1';
}

function museoAttachmentPathIsSafe($path): bool
{
    if (!is_string($path)) {
        return false;
    }

    $path = trim($path);

    if ($path === '' || strlen($path) > 1024 || strpos($path, "\0") !== false || strpos($path, "
") !== false || strpos($path, "
") !== false) {
        return false;
    }

    return is_file($path) && is_readable($path);
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

        if (museoAttachmentPathIsSafe($path)) {
            $validAttachments[] = ['path' => trim($path), 'name' => $name];
        } else {
            museoMailLog('Allegato ignorato perché non leggibile o non è un percorso valido.');
        }
    }

    if (empty($validAttachments)) {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';

        $encodedBody = chunk_split(base64_encode($htmlBody));

        $ok = @mail(
            $to,
            museoHeaderEncode($subject),
            $encodedBody,
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
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";

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

    // Su Altervista l'invio di allegati tramite mail() può restare appeso a lungo.
    // Per evitare caricamenti infiniti dopo pagamento/cassa, online inviamo subito la mail HTML senza allegati.
    // In locale, invece, PHPMailer continua a poter allegare il PDF.
    if (museoIsOnlineServer() && !empty($attachments)) {
        museoMailLog('Server online: allegati rimossi dall\'invio per evitare timeout/caricamento infinito.');
        $attachments = [];
    }

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

                if (museoAttachmentPathIsSafe($path)) {
                    $mail->addAttachment(trim($path), $name);
                } else {
                    museoMailLog('Allegato PHPMailer ignorato perché non leggibile o non è un percorso valido.');
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
    $content = '<p style="font-size:16px;line-height:1.65;margin:0 0 14px;">Ciao <strong>' . museoEmailSafe($nome) . '</strong>, grazie per la registrazione.</p>' .
        '<p style="font-size:15px;line-height:1.65;margin:0 0 18px;">Inserisci questo codice nella pagina di verifica per attivare il tuo account.</p>' .
        '<div style="text-align:center;background:#f7fbff;border:1px solid #8EC5E8;border-radius:18px;padding:22px;margin:22px 0;">' .
        '<div style="font-size:34px;letter-spacing:9px;font-weight:900;color:#102744;font-family:Arial,Helvetica,sans-serif;">' . museoEmailSafe($codice) . '</div>' .
        '<div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.12em;margin-top:8px;">Codice verifica</div>' .
        '</div>' .
        '<p style="font-size:14px;color:#6b7280;line-height:1.55;">Se non hai richiesto tu la registrazione, puoi ignorare questa email.</p>';
    $body = museoEmailTemplate('Verifica il tuo account', 'Completa la registrazione al museo digitale', $content, [
        'badge' => 'Area riservata',
        'cta_text' => 'Vai alla verifica',
        'cta_url' => museoEmailUrl('/verifica_email.php?email=' . rawurlencode($email)),
    ]);
    return museoSendMail($email, $subject, $body, "Codice di verifica: {$codice}");
}

function inviaEmailCodiceRecuperoPassword(string $email, string $nome, string $codice): bool
{
    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi';
    $subject = 'Codice recupero password - ' . $siteName;
    $content = '<p style="font-size:16px;line-height:1.65;margin:0 0 14px;">Ciao <strong>' . museoEmailSafe($nome) . '</strong>,</p>' .
        '<p style="font-size:15px;line-height:1.65;margin:0 0 18px;">Usa questo codice per completare il recupero password insieme alla risposta di sicurezza.</p>' .
        '<div style="text-align:center;background:#fff8e1;border:1px solid #e1c16e;border-radius:18px;padding:22px;margin:22px 0;">' .
        '<div style="font-size:34px;letter-spacing:9px;font-weight:900;color:#102744;font-family:Arial,Helvetica,sans-serif;">' . museoEmailSafe($codice) . '</div>' .
        '<div style="font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.12em;margin-top:8px;">Codice recupero</div>' .
        '</div>' .
        '<p style="font-size:14px;color:#6b7280;line-height:1.55;">Il codice scade dopo pochi minuti. Se non hai richiesto tu il recupero, ignora questa email.</p>';
    $body = museoEmailTemplate('Recupero password', 'Proteggi il tuo account', $content, [
        'badge' => 'Sicurezza account',
        'cta_text' => 'Apri recupero password',
        'cta_url' => museoEmailUrl('/recupero_password.php'),
    ]);
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
    $nome = (string)($ordine['nome_cliente'] ?? $ordine['nome'] ?? 'Visitatore');
    $totale = '€ ' . number_format((float)($ordine['importo_totale'] ?? $ordine['totale'] ?? 0), 2, ',', '.');
    $stato = (string)($ordine['stato_pagamento'] ?? 'Pagato');
    $metodo = ucfirst((string)($ordine['metodo_pagamento'] ?? 'Non indicato'));
    $pagato = strcasecmp($stato, 'Pagato') === 0;

    $righeBiglietti = '';
    foreach ($codici as $codice) {
        $codice = (string)$codice;
        $qr = '';
        if ($pagato) {
            $qrUrl = museoEmailUrl('/ticket_qr.php?small=1&codice=' . rawurlencode($codice));
            $ticketUrl = museoEmailUrl('/ticket.php?codice=' . rawurlencode($codice));
            $qr = '<a href="' . museoEmailSafe($ticketUrl) . '" style="display:inline-block;margin-top:10px;"><img src="' . museoEmailSafe($qrUrl) . '" width="112" height="112" alt="QR code biglietto" style="display:block;border:1px solid #d8e8f7;border-radius:14px;padding:8px;background:#fff;"></a>';
        }
        $righeBiglietti .= '<div style="border:1px solid #d8e8f7;border-radius:16px;padding:14px;margin:10px 0;background:#f7fbff;">' .
            '<div style="font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#6b7280;font-weight:800;">Codice biglietto</div>' .
            '<div style="font-family:Courier New,monospace;font-weight:900;color:#102744;font-size:15px;margin-top:4px;word-break:break-all;">' . museoEmailSafe($codice) . '</div>' . $qr . '</div>';
    }
    if ($righeBiglietti === '') {
        $righeBiglietti = '<p style="font-size:14px;color:#6b7280;">Nessun codice biglietto disponibile.</p>';
    }

    $content = '<p style="font-size:16px;line-height:1.65;margin:0 0 14px;">Ciao <strong>' . museoEmailSafe($nome) . '</strong>, il tuo ordine è stato registrato correttamente.</p>' .
        museoEmailInfoBox([
            'Ordine' => $codiceOrdine,
            'Metodo pagamento' => $metodo,
            'Stato pagamento' => $stato,
            'Totale' => $totale,
        ], $pagato ? 'success' : 'warning') .
        '<h2 style="font-family:Georgia,serif;color:#102744;font-size:22px;margin:24px 0 12px;">Biglietti e QR code</h2>' .
        $righeBiglietti .
        '<div style="background:#fff8e1;border-left:4px solid #8EC5E8;padding:12px 14px;border-radius:10px;margin-top:18px;font-size:14px;color:#4b5563;">' . "\r\n" .
        'Porta eventuali documenti richiesti e mostra il QR code all&#39;ingresso.' . "\r\n" .
        '</div>';

    $subject = 'Conferma ordine ' . $codiceOrdine . ' - ' . $siteName;
    $body = museoEmailTemplate('Conferma ordine', 'Biglietti e riepilogo della tua visita', $content, [
        'badge' => 'Prenotazione confermata',
        'cta_text' => 'Apri i biglietti',
        'cta_url' => museoEmailUrl('/biglietti.php?codice=' . rawurlencode($codiceOrdine)),
    ]);

    $attachments = [];
    $tmpPdfDaEliminare = null;
    if ($pdfPath !== '') {
        if (museoAttachmentPathIsSafe($pdfPath)) {
            $attachments[] = ['path' => trim($pdfPath), 'name' => 'ordine_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codiceOrdine) . '.pdf'];
        } elseif (strncmp($pdfPath, '%PDF-', 5) === 0) {
            $tmpBase = tempnam(sys_get_temp_dir(), 'mss_pdf_mail_');
            if ($tmpBase !== false) {
                $tmpPdfDaEliminare = $tmpBase . '.pdf';
                @rename($tmpBase, $tmpPdfDaEliminare);
                @file_put_contents($tmpPdfDaEliminare, $pdfPath);
                if (museoAttachmentPathIsSafe($tmpPdfDaEliminare)) {
                    $attachments[] = ['path' => $tmpPdfDaEliminare, 'name' => 'ordine_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $codiceOrdine) . '.pdf'];
                }
            }
        } else {
            museoMailLog('PDF ordine non allegato: valore ricevuto non è un percorso valido.');
        }
    }

    try {
        return museoSendMail($email, $subject, $body, museoPlainFromHtml($body), $attachments);
    } finally {
        if ($tmpPdfDaEliminare) {
            @unlink($tmpPdfDaEliminare);
        }
    }
}

function inviaEmailEsitoRimborso(array $ordine, string $esito, string $nota = ''): bool
{
    $email = (string)($ordine['email_cliente'] ?? $ordine['email_utente'] ?? $ordine['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        museoMailLog('Esito rimborso non inviato: email mancante o non valida per ordine ' . (string)($ordine['codice_recupero'] ?? $ordine['id_ordine'] ?? ''));
        return false;
    }

    $siteName = defined('SITE_NAME') ? SITE_NAME : 'Museo Storico Severi';
    $codiceOrdine = (string)($ordine['codice_recupero'] ?? ('ORD-' . ($ordine['id_ordine'] ?? '')));
    $nome = trim((string)($ordine['nome_cliente'] ?? ''));
    if ($nome === '') {
        $nome = trim((string)(($ordine['nome'] ?? '') . ' ' . ($ordine['cognome'] ?? '')));
    }
    if ($nome === '') {
        $nome = 'Visitatore';
    }

    $esito = ucfirst(mb_strtolower(trim($esito), 'UTF-8'));
    $accettato = strcasecmp($esito, 'Accettato') === 0;
    $titoloEsito = $accettato ? 'Rimborso accettato' : 'Rimborso rifiutato';
    $subject = $titoloEsito . ' - ordine ' . $codiceOrdine . ' - ' . $siteName;
    $totale = '€ ' . number_format((float)($ordine['importo_totale'] ?? 0), 2, ',', '.');

    $messaggio = $accettato
        ? "La tua richiesta di rimborso è stata accettata. L’importo dell'ordine è stato riaccreditato sul portafoglio virtuale e i biglietti non sono più utilizzabili."
        : "La tua richiesta di rimborso è stata rifiutata. L’ordine resta consultabile nella tua area personale.";
    $notaHtml = trim($nota) !== '' ? '<p style="font-size:14px;line-height:1.6;color:#4b5563;background:#f7fbff;border:1px solid #d8e8f7;border-radius:12px;padding:12px 14px;"><strong>Nota:</strong><br>' . nl2br(museoEmailSafe($nota)) . '</p>' : '';
    $content = '<p style="font-size:16px;line-height:1.65;margin:0 0 14px;">Ciao <strong>' . museoEmailSafe($nome) . '</strong>,</p>' .
        '<p style="font-size:15px;line-height:1.65;margin:0 0 14px;">' . museoEmailSafe($messaggio) . '</p>' .
        museoEmailInfoBox([
            'Ordine' => $codiceOrdine,
            'Esito' => $titoloEsito,
            'Importo' => $totale,
        ], $accettato ? 'success' : 'warning') . $notaHtml;

    $body = museoEmailTemplate($titoloEsito, 'Aggiornamento sulla tua richiesta', $content, [
        'badge' => 'Gestione rimborsi',
        'cta_text' => 'Apri area personale',
        'cta_url' => museoEmailUrl('/account.php'),
    ]);

    return museoSendMail($email, $subject, $body, museoPlainFromHtml($body));
}
