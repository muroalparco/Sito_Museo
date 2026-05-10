# 🏛️ Museo Storico Severi — Sito Web PHP

## Struttura del progetto

```
museo/
├── index.php                  ← Homepage
├── config.php                 ← Configurazione DB e costanti
├── db.php                     ← Connessione PDO singleton
├── auth.php                   ← Funzioni login/registrazione/sessione
├── header.php                 ← Header + navbar
├── footer.php                 ← Footer
├── login.php                  ← Form di accesso
├── registrazione.php          ← Form di registrazione
├── account.php                ← Profilo utente + ordini
├── logout.php                 ← Handler logout
├── esposizioni.php            ← Lista mostre
└── img/                       ← Cartella immagini
```

## Setup

### 1. Requisiti
- PHP 8.1+
- MySQL 8+ (o MariaDB 10.5+)
- Web server con mod_rewrite (Apache/Nginx) o PHP built-in server

### 2. Database
```bash
mysql -u root -p < biglietteria_museo.sql
```

### 3. Configurazione
Modifica `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'biglietteria_museo');
define('DB_USER', 'root');
define('DB_PASS', 'tuapassword');
define('SITE_URL', 'http://localhost/museo');
```

### 4. Avvio rapido (PHP built-in server)
```bash
cd /percorso/a/museo
php -S localhost:8000
```
Poi visita: http://localhost:8000

## Palette colori
| Nome | HEX |
|------|-----|
| Oro antico | `#C9A84C` |
| Acciaio | `#6B8CAE` |
| Avorio | `#F5F0E8` |
| Antracite | `#2C2C2C` |

## Font
- **Playfair Display** — Titoli e display (via Google Fonts)
- **Lato** — Corpo testo e UI (via Google Fonts)

## Framework CSS
**Tailwind CSS** via CDN (configurato inline con palette custom).

## Sicurezza implementata
- Password hashate con `password_hash()` (bcrypt cost 12)
- Protezione CSRF con token di sessione
- Sessioni sicure (httponly, strict mode, session_regenerate_id)
- PDO con prepared statements (prevenzione SQL injection)
- Output sanitizzato con `htmlspecialchars()`
- ON DELETE SET NULL su ordini (preserva storico acquisti)

## Credenziali di test (dopo INSERT del SQL)
| Email | Password hash | Ruolo |
|-------|--------------|-------|
| luca.rossi@email.com | hash1 (da aggiornare) | visitatore |
| anna.neri@email.com | hash4 (da aggiornare) | amministratore |

> ⚠️ Gli hash nel file SQL sono placeholder. Per usare il login,
> aggiorna le password con `password_hash('tua_password', PASSWORD_BCRYPT)`.
