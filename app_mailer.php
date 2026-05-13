<?php
require_once __DIR__ . '/config.php';

function mailerLogDebug(string $oggetto, string $html, string $destinatario, string $nota = ''): void {
    $dir = __DIR__ . '/mail_debug';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $file = $dir . '/mail_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.html';
    $header = '<!-- A: ' . htmlspecialchars($destinatario, ENT_QUOTES, 'UTF-8') .
        ' | Oggetto: ' . htmlspecialchars($oggetto, ENT_QUOTES, 'UTF-8') .
        ($nota !== '' ? ' | Nota: ' . htmlspecialchars($nota, ENT_QUOTES, 'UTF-8') : '') .
        " -->\n";

    @file_put_contents($file, $header . $html);
}

function mailerMimeSubject(string $subject): string {
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

function mailerTextFromHtml(string $html): string {
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $html = preg_replace('/<\/p>/i', "\n\n", $html);
    $html = preg_replace('/<\/li>/i', "\n", $html);
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function inviaEmailConMailNativa(string $destinatario, string $nomeDestinatario, string $oggetto, string $html, array $allegati = []): bool {
    $configuredFrom = defined('MAIL_FROM') ? MAIL_FROM : 'noreply@localhost';
    $fromEmail = $configuredFrom;
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Museo Storico Severi';

    // Quando si usa mail() nativa, un mittente Gmail/Outlook/Yahoo può essere rifiutato
    // dai controlli antispam. In quel caso usiamo un mittente tecnico sul dominio del sito
    // e lasciamo la mail configurata come Reply-To.
    if (preg_match('/@(gmail\.com|googlemail\.com|outlook\.com|hotmail\.com|live\.com|yahoo\.)/i', $fromEmail)) {
        $host = preg_replace('/[^a-zA-Z0-9._-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $fromEmail = 'noreply@' . ($host ?: 'localhost');
    }

    $from = mailerMimeSubject($fromName) . ' <' . $fromEmail . '>';
    $to = ($nomeDestinatario !== '' ? mailerMimeSubject($nomeDestinatario) . ' ' : '') . '<' . $destinatario . '>';
    $subject = mailerMimeSubject($oggetto);

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $configuredFrom;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    if (empty($allegati)) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        return @mail($to, $subject, $html, implode("\r\n", $headers));
    }

    $boundaryMixed = 'bnd_mixed_' . bin2hex(random_bytes(12));
    $boundaryAlt = 'bnd_alt_' . bin2hex(random_bytes(12));

    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundaryMixed . '"';

    $body = '';
    $body .= '--' . $boundaryMixed . "\r\n";
    $body .= 'Content-Type: multipart/alternative; boundary="' . $boundaryAlt . '"' . "\r\n\r\n";

    $body .= '--' . $boundaryAlt . "\r\n";
    $body .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
    $body .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
    $body .= mailerTextFromHtml($html) . "\r\n\r\n";

    $body .= '--' . $boundaryAlt . "\r\n";
    $body .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $body .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
    $body .= $html . "\r\n\r\n";
    $body .= '--' . $boundaryAlt . "--\r\n";

    foreach ($allegati as $allegato) {
        if (empty($allegato['content']) || empty($allegato['filename'])) {
            continue;
        }

        $filename = str_replace(["\r", "\n", '"'], '', (string)$allegato['filename']);
        $mime = $allegato['mime'] ?? 'application/octet-stream';
        $content = chunk_split(base64_encode($allegato['content']));

        $body .= '--' . $boundaryMixed . "\r\n";
        $body .= 'Content-Type: ' . $mime . '; name="' . $filename . '"' . "\r\n";
        $body .= 'Content-Transfer-Encoding: base64' . "\r\n";
        $body .= 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n\r\n";
        $body .= $content . "\r\n";
    }

    $body .= '--' . $boundaryMixed . "--";

    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function inviaEmailHtml(string $destinatario, string $nomeDestinatario, string $oggetto, string $html, array $allegati = []): bool {
    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        mailerLogDebug('ERRORE destinatario non valido - ' . $oggetto, $html, $destinatario, 'destinatario non valido');
        return false;
    }

    $smtpAttivo = defined('SMTP_ACTIVE') ? (bool)SMTP_ACTIVE : false;

    if ($smtpAttivo) {
        $exceptionFile = __DIR__ . '/PHPMailer/src/Exception.php';
        $phpMailerFile = __DIR__ . '/PHPMailer/src/PHPMailer.php';
        $smtpFile = __DIR__ . '/PHPMailer/src/SMTP.php';

        if (file_exists($exceptionFile) && file_exists($phpMailerFile) && file_exists($smtpFile)) {
            require_once $exceptionFile;
            require_once $phpMailerFile;
            require_once $smtpFile;

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $secure = strtolower((string)SMTP_SECURE);

                $mail->CharSet = 'UTF-8';
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->Port = (int)SMTP_PORT;

                if ($secure === 'ssl' || $secure === 'smtps') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($destinatario, $nomeDestinatario);
                $mail->isHTML(true);
                $mail->Subject = $oggetto;
                $mail->Body = $html;
                $mail->AltBody = mailerTextFromHtml($html);

                foreach ($allegati as $allegato) {
                    if (!empty($allegato['content']) && !empty($allegato['filename'])) {
                        $mail->addStringAttachment(
                            $allegato['content'],
                            $allegato['filename'],
                            'base64',
                            $allegato['mime'] ?? 'application/octet-stream'
                        );
                    }
                }

                return $mail->send();
            } catch (Throwable $e) {
                error_log('Errore PHPMailer: ' . $e->getMessage());
                mailerLogDebug('ERRORE SMTP - ' . $oggetto, $html, $destinatario, $e->getMessage());
                return false;
            }
        }

        // PHPMailer non è stato caricato nel progetto: su Altervista proviamo comunque con mail() nativa.
        error_log('PHPMailer non trovato: invio tramite mail() nativa.');
    }

    $ok = inviaEmailConMailNativa($destinatario, $nomeDestinatario, $oggetto, $html, $allegati);

    if (!$ok) {
        mailerLogDebug('ERRORE mail() - ' . $oggetto, $html, $destinatario, 'mail() ha restituito false');
    }

    return $ok;
}

function inviaEmailVerificaAccount(string $email, string $nome, string $codice): bool {
    $link = SITE_URL . '/verifica_email.php?email=' . urlencode($email);
    $html = '
        <div style="font-family:Arial,sans-serif;line-height:1.6;color:#2C2C2C">
            <h2 style="color:#C9A84C">Museo Storico Severi</h2>
            <p>Ciao ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>grazie per la registrazione. Per attivare il tuo account inserisci questo codice nella pagina di verifica:</p>
            <div style="font-size:32px;font-weight:bold;letter-spacing:8px;background:#F5F0E8;border:1px solid #C9A84C;padding:16px;text-align:center;max-width:260px">' . htmlspecialchars($codice, ENT_QUOTES, 'UTF-8') . '</div>
            <p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Vai alla verifica email</a></p>
            <p>Se non hai richiesto tu questa registrazione, ignora questa email.</p>
        </div>';

    return inviaEmailHtml($email, $nome, 'Verifica email - Museo Storico Severi', $html);
}

function inviaEmailConfermaOrdine(array $ordine, array $codiciBiglietti, string $pdfContent): bool {
    $email = $ordine['email_cliente'] ?? '';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $nome = $ordine['nome_cliente'] ?? 'Visitatore';
    $stato = $ordine['stato_pagamento'] ?? 'Pagato';
    $metodo = $ordine['metodo_pagamento'] ?? 'carta';
    $codice = $ordine['codice_recupero'] ?? '';
    $totale = number_format((float)($ordine['importo_totale'] ?? 0), 2, ',', '.');

    $listaBiglietti = '';
    foreach ($codiciBiglietti as $codiceBiglietto) {
        $listaBiglietti .= '<li>' . htmlspecialchars($codiceBiglietto, ENT_QUOTES, 'UTF-8') . '</li>';
    }

    $html = '
        <div style="font-family:Arial,sans-serif;line-height:1.6;color:#2C2C2C">
            <h2 style="color:#C9A84C">Conferma ordine - Museo Storico Severi</h2>
            <p>Ciao ' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . ',</p>
            <p>il tuo ordine è stato registrato correttamente.</p>
            <p><strong>Codice ordine:</strong> ' . htmlspecialchars($codice, ENT_QUOTES, 'UTF-8') . '<br>
            <strong>Stato pagamento:</strong> ' . htmlspecialchars($stato, ENT_QUOTES, 'UTF-8') . '<br>
            <strong>Metodo pagamento:</strong> ' . htmlspecialchars($metodo, ENT_QUOTES, 'UTF-8') . '<br>
            <strong>Totale:</strong> € ' . $totale . '</p>
            <p><strong>Biglietti:</strong></p>
            <ul>' . $listaBiglietti . '</ul>
            <p>In allegato trovi il PDF riepilogativo dell\'ordine.</p>
        </div>';

    return inviaEmailHtml(
        $email,
        $nome,
        'Conferma ordine ' . $codice . ' - Museo Storico Severi',
        $html,
        [[
            'content' => $pdfContent,
            'filename' => 'ordine_' . preg_replace('/[^A-Z0-9_-]/i', '_', $codice) . '.pdf',
            'mime' => 'application/pdf'
        ]]
    );
}
