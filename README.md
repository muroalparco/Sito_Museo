# 🏛️ Museo Storico Severi — Sito Web PHP

## Struttura del progetto

```
museo/
├── index.php
├── config.php
├── db.php
├── auth.php
├── header.php
├── footer.php
├── login.php
├── registrazione.php
├── account.php
├── logout.php
├── esposizioni.php
├── info.php
├── novita.php
└── img/
    ├── logo/
    ├── esposizioni/
    └── home/
```

Tutti i file PHP sono nella cartella principale. Le sole sottocartelle previste sono dentro `img/`.

## Setup rapido

1. Copia la cartella del progetto in `htdocs` di XAMPP.
2. Crea il database `biglietteria_museo` in phpMyAdmin.
3. Importa `biglietteria_museo.sql`.
4. Configura `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'biglietteria_museo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/museo');
```

5. Apri `http://localhost/museo`.

## Nota

I link a `ordini.php`, `recupero_password.php`, `termini.php`, `privacy.php`, `esposizione.php` sono predisposti, ma le relative pagine non sono incluse in questa versione base.


## Logo
Il logo ufficiale è in `img/logo.png` ed è richiamato da header, footer, login, registrazione e homepage.
