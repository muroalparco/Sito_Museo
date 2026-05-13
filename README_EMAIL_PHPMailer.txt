CONFIGURAZIONE EMAIL - MUSEO STORICO SEVERI

Ho modificato il sistema email in modo che funzioni in due modi:

1) Se nel progetto esiste PHPMailer, il sito prova a inviare tramite SMTP Gmail/altro provider.
2) Se PHPMailer NON esiste, il sito prova automaticamente a usare la funzione mail() nativa del server.
   Su Altervista questo è spesso il metodo più semplice.

STRUTTURA OPZIONALE PHPMailer

Se vuoi usare SMTP Gmail, devi avere questa struttura:

museo/PHPMailer/src/PHPMailer.php
museo/PHPMailer/src/SMTP.php
museo/PHPMailer/src/Exception.php

CONFIG.PHP

Nel file config.php devono esserci questi dati:

define('SMTP_ACTIVE', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'la_tua_email@gmail.com');
define('SMTP_PASSWORD', 'password_app_google_senza_spazi');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('MAIL_FROM', 'la_tua_email@gmail.com');
define('MAIL_FROM_NAME', 'Museo Storico Severi');

Se PHPMailer manca, questi dati SMTP non vengono usati e il sito prova con mail() nativa.

TEST

Apri:

test_mail.php?email=tuoindirizzo@email.it

Se arriva la mail, elimina test_mail.php dal server.
Se non arriva, controlla:
- posta indesiderata/spam;
- cartella mail_debug;
- log errori di Altervista.

NOTA IMPORTANTE

Il SITE_URL ora viene calcolato automaticamente, quindi il link nella mail non dovrebbe più puntare a localhost quando il sito è online.

MITTENTE CON MAIL() NATIVA

Se PHPMailer manca e il sito usa mail() nativa, il codice evita di usare Gmail come From tecnico perché spesso viene bloccato dagli antispam.
La tua email configurata in MAIL_FROM resta comunque come Reply-To.
