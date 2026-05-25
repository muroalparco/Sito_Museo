ISTRUZIONI

1. Sostituisci nel sito il file:
   app_mailer.php

2. Carica temporaneamente anche:
   test_mail.php

3. Controlla che esista questa cartella:
   PHPMailer/src/PHPMailer.php
   PHPMailer/src/SMTP.php
   PHPMailer/src/Exception.php

4. In config.php deve esserci:
   define('SMTP_ACTIVE', true);
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_USERNAME', 'la_tua_mail_gmail');
   define('SMTP_PASSWORD', 'password_app_google_senza_spazi');
   define('SMTP_PORT', 587);
   define('SMTP_SECURE', 'tls');

5. Prova:
   https://museostoricoseveri.altervista.org/test_mail.php?email=LA_TUA_EMAIL

6. Se non arriva la mail, guarda:
   mail_debug/mail_error_log.txt

7. Quando hai finito, elimina test_mail.php dal server.
