# Museo Storico Severi — README tecnico e funzionale completo

> Documentazione estesa del sito **Museo Storico Severi**, progetto didattico PHP/MySQL per la gestione di un museo, delle esposizioni, delle prenotazioni, degli ordini, dei biglietti, della cassa, della validazione ingressi e dell'area amministrativa.

---

## 1. Scopo del progetto

Il progetto **Museo Storico Severi** è un sito web completo, realizzato in **PHP**, **MySQL/MariaDB**, **HTML**, **CSS** e **JavaScript**, pensato come applicazione didattica ma strutturato con logica molto vicina a un piccolo gestionale reale.

Il sito permette di:

- presentare un museo storico con homepage, pagine informative, esposizioni e novità;
- mostrare al pubblico le esposizioni disponibili;
- consentire la prenotazione di biglietti anche senza registrazione;
- gestire biglietti per il solo ingresso al museo;
- gestire biglietti collegati a una specifica esposizione;
- gestire prenotazioni speciali per docenti e classi;
- simulare pagamenti con carta, PayPal o contanti in cassa;
- generare ordini e codici di recupero;
- generare biglietti con codice univoco;
- recuperare un ordine tramite codice;
- stampare o scaricare un PDF riepilogativo dell'ordine;
- inviare email di verifica account, recupero password e conferma ordine;
- offrire un'area utente con profilo, sicurezza e storico ordini;
- offrire un'area amministratore per esposizioni, fasce orarie, categorie, tariffe, servizi e utenti;
- offrire un'area cassiere per saldare ordini non pagati;
- offrire un'area operatore per validare i biglietti all'ingresso.

L'applicazione è quindi divisa in tre grandi parti:

1. **Area pubblica**, accessibile a tutti.
2. **Area utente**, accessibile agli utenti registrati e loggati.
3. **Aree operative riservate**, accessibili in base al ruolo: amministratore, operatore, cassiere, tester.

---

## 2. Tecnologie utilizzate

### 2.1 Backend

Il backend è realizzato in **PHP procedurale** con uso di funzioni dedicate. Non è presente un framework come Laravel o Symfony; la struttura è volutamente semplice, adatta a un progetto scolastico o didattico.

Caratteristiche backend principali:

- connessione a MySQL tramite `PDO`;
- prepared statement per le query più importanti;
- gestione delle sessioni PHP;
- funzioni centralizzate per login, logout, ruoli, CSRF e pulizia output;
- funzioni specifiche per pagamenti simulati, creazione ordini e biglietti;
- generazione PDF manuale senza librerie esterne per il PDF;
- sistema email centralizzato con PHPMailer, `mail()` nativa e fallback di debug.

### 2.2 Database

Il database è MySQL/MariaDB e usa tabelle InnoDB con vincoli, chiavi primarie, chiavi esterne, indici, `ENUM` e `CHECK`.

Le tabelle principali sono:

- `Utenti`
- `Esposizioni`
- `Categorie_Riduzione`
- `Tariffe`
- `Servizi_Opzionali`
- `Fasce_Orarie`
- `Ordini`
- `Biglietti`
- `Biglietti_Servizi`

### 2.3 Frontend

Il frontend usa:

- HTML5;
- CSS personalizzato in `assets/css/style.css`;
- Tailwind locale in `assets/css/tailwind-local.css` per le pagine interne;
- CSS critico separato per la homepage in `assets/css/home-critical.css`;
- JavaScript vanilla per menu mobile, tab, filtri, formattazione carta e scadenza, alert flottanti e piccoli comportamenti interattivi.

La palette visiva richiama un museo istituzionale:

- antracite/scuro per testate e sezioni importanti;
- avorio per sfondi chiari;
- oro antico per accenti, pulsanti e linee decorative;
- grigi per testi secondari;
- colori di stato per conferme, errori, avvisi, pagamento e validazione.

### 2.4 Email

Il progetto include **PHPMailer** nella cartella `PHPMailer/` e un sistema di fallback in `app_mailer.php`.

Il sistema email può:

- usare SMTP con PHPMailer;
- usare `mail()` nativa come alternativa;
- salvare copie HTML delle email nella cartella `mail_debug/` quando l'invio fallisce;
- scrivere log in `mail_debug/mail_error_log.txt`.

### 2.5 PDF

Il file `ordine_pdf.php` contiene una classe interna che genera un PDF minimale direttamente in PHP, senza dipendere da librerie esterne come Dompdf, TCPDF o FPDF.

Il PDF contiene:

- intestazione del Museo Storico Severi;
- riepilogo ordine;
- dati acquirente;
- percorso o esposizione;
- data visita;
- servizi opzionali;
- stato pagamento;
- totale;
- numero biglietti;
- eventuali dati scuola/classe;
- avviso in caso di ordine non pagato;
- elenco dei codici biglietto.

---

## 3. Struttura generale della cartella

La cartella principale del progetto è `museo/`.

```text
museo/
├── .htaccess
├── 404.php
├── account.php
├── admin.php
├── app_mailer.php
├── auth.php
├── biglietti.php
├── cassa.php
├── chi_siamo.php
├── config.php
├── db.php
├── db_completo_tabelle.sql
├── elimina_account.php
├── esposizioni.php
├── footer.php
├── header.php
├── index.php
├── info.php
├── login.php
├── logout.php
├── novita.php
├── ordine_dettaglio.php
├── ordine_pdf.php
├── ordini.php
├── pagamento.php
├── prenota.php
├── prenota_docente.php
├── recupera_ordine.php
├── recupero_password.php
├── registrazione.php
├── scarica_pdf.php
├── test_mail.php
├── valida_biglietti.php
├── verifica_email.php
├── README.md
├── README_EMAIL_PHPMailer.txt
├── README_MAIL_FIX.txt
├── assets/
│   └── css/
│       ├── home-critical.css
│       ├── style.css
│       └── tailwind-local.css
├── img/
│   ├── logo-128.webp
│   ├── logo-256.webp
│   ├── logo-512.webp
│   ├── logo-lcp.webp
│   ├── logo.png
│   ├── logoconscritte.png
│   └── logovechcio.png
├── mail_debug/
│   ├── .gitkeep
│   ├── mail_*.html
│   └── mail_error_log.txt
└── PHPMailer/
    ├── src/
    │   ├── PHPMailer.php
    │   ├── SMTP.php
    │   ├── Exception.php
    │   ├── OAuth.php
    │   ├── OAuthTokenProvider.php
    │   ├── DSNConfigurator.php
    │   └── POP3.php
    ├── language/
    │   └── file lingua PHPMailer
    ├── README.md
    ├── SECURITY.md
    ├── SMTPUTF8.md
    ├── LICENSE
    ├── VERSION
    ├── COMMITMENT
    ├── composer.json
    └── get_oauth_token.php
```

---

## 4. Ruoli previsti dal sito

Il sito usa il campo `ruolo` della tabella `Utenti`.

I ruoli ammessi sono:

| Ruolo | Significato | Permessi principali |
|---|---|---|
| `visitatore` | Utente normale registrato | Può accedere all'account, vedere i propri ordini, acquistare e recuperare biglietti. |
| `operatore` | Addetto al controllo ingressi | Può usare l'area `valida_biglietti.php` per cercare e validare i ticket. |
| `cassiere` | Addetto alla cassa | Può usare l'area `cassa.php` per cercare ordini e segnare come pagati gli ordini non saldati. |
| `amministratore` | Gestore del sito | Può accedere ad `admin.php` e gestire esposizioni, fasce, categorie, tariffe, servizi e utenti. |
| `tester` | Ruolo speciale di test | Viene trattato come amministratore, operatore e cassiere per testare tutte le aree. |

Le funzioni di controllo dei ruoli sono definite in `auth.php`:

- `isLogged()`
- `ruoloCorrente()`
- `isTester()`
- `isAdmin()`
- `isOperatore()`
- `isCassiere()`
- `requireLogin()`
- `requireAdmin()`
- `requireCassiere()`

Una cosa importante: nel codice attuale `isAdmin()` considera amministratori sia gli utenti con ruolo `amministratore` sia quelli con ruolo `tester`. Lo stesso accade per `isOperatore()` e `isCassiere()`, che includono il ruolo `tester`.

---

## 5. Flusso generale per il visitatore

### 5.1 Navigazione pubblica

Un visitatore può usare il sito senza account.

Può aprire:

- `index.php`, homepage;
- `chi_siamo.php`, pagina di presentazione del progetto;
- `esposizioni.php`, elenco delle esposizioni;
- `novita.php`, pagina novità;
- `info.php`, informazioni, tariffe e servizi;
- `prenota.php`, form di prenotazione;
- `prenota_docente.php`, form prenotazione classe;
- `recupera_ordine.php`, recupero ordine tramite codice;
- `biglietti.php`, visualizzazione biglietti tramite codice ordine;
- `scarica_pdf.php`, download PDF tramite codice ordine.

### 5.2 Prenotazione standard

Il visitatore può prenotare:

1. un biglietto **base**, cioè solo ingresso al museo;
2. un biglietto **esposizione**, cioè collegato a una mostra pubblicata e a una fascia oraria.

Il flusso è:

1. l'utente sceglie una mostra da `esposizioni.php`, oppure sceglie il biglietto solo museo;
2. viene portato a `prenota.php`;
3. compila nome, email, tariffa, quantità, data/fascia e servizi opzionali;
4. sceglie metodo di pagamento: contanti, carta o PayPal;
5. viene inviato a `pagamento.php`;
6. se il pagamento è carta/PayPal, vede una pagina di simulazione pagamento;
7. se il pagamento è contanti, l'ordine viene emesso come **Non pagato**;
8. al termine viene generato un codice ordine `ORD-...`;
9. l'utente può vedere i biglietti e scaricare il PDF.

### 5.3 Prenotazione per docenti e classi

Il sito ha una pagina dedicata per le classi: `prenota_docente.php`.

Questa pagina permette di inserire:

- docente referente;
- email referente;
- nome scuola;
- codice meccanografico;
- indirizzo scuola;
- città scuola;
- telefono scuola;
- classe/sezione;
- numero studenti;
- numero docenti accompagnatori;
- note per il museo;
- servizi opzionali;
- metodo di pagamento.

I docenti accompagnatori vengono trattati come biglietti gratuiti a `0,00 €`. Il codice cerca nel database la categoria `Docente accompagnatore`; se esiste, i biglietti docente vengono collegati a quella categoria.

Nella prenotazione docente non viene applicato il limite massimo di 20 biglietti presente nella prenotazione ordinaria.

### 5.4 Recupero ordine

Chiunque abbia un codice ordine può recuperare i biglietti da `recupera_ordine.php`.

Il codice viene trasformato in maiuscolo e passato a:

```text
biglietti.php?codice=ORD-XXXXXXXX
```

`biglietti.php` cerca il codice nella tabella `Ordini` e poi carica i biglietti associati.

---

## 6. Flusso generale per l'utente registrato

Un utente registrato può:

- accedere con email e password;
- vedere il proprio nome nel menu;
- accedere al profilo;
- modificare nome, cognome ed email;
- cambiare password;
- vedere gli ultimi ordini nel profilo;
- vedere tutti gli ordini in `ordini.php`;
- pagare un ordine non pagato dalla propria area ordini;
- recuperare i propri biglietti;
- eliminare il proprio account.

L'account non è necessario per comprare biglietti, ma se l'utente è loggato l'ordine viene collegato al suo `id_utente`.

---

## 7. Flusso generale per amministratore

L'amministratore accede a `admin.php`.

Può gestire:

- esposizioni;
- emoji delle esposizioni;
- stato esposizioni;
- fasce orarie;
- capienza delle fasce;
- categorie di riduzione;
- tariffe;
- servizi opzionali;
- utenti;
- ruoli utente;
- password utente;
- domanda e risposta di sicurezza utente;
- eliminazione account utente.

La pagina contiene una navigazione rapida interna con sezioni:

- Esposizioni;
- Categorie riduzioni;
- Tariffe;
- Servizi;
- Utenti.

Su mobile l'area amministratore dispone di un pulsante menu dedicato, visibile anche quando si scorre, con pannello mobile per saltare alle sezioni.

---

## 8. Flusso generale per operatore

L'operatore accede a `valida_biglietti.php`.

Può:

- inserire il codice ticket `TKT-...`;
- vedere i dati del biglietto;
- vedere stato, ordine, acquirente, email, tipo, categoria, percorso, data validità e prezzo;
- validare un biglietto se è nello stato `Valido`;
- vedere errori specifici se il biglietto è già usato, non pagato o annullato.

Quando un biglietto viene validato:

- `stato` passa da `Valido` a `Utilizzato`;
- `data_utilizzo` viene impostata a `NOW()`.

---

## 9. Flusso generale per cassiere

Il cassiere accede a `cassa.php`.

Può cercare:

- per ID ordine numerico;
- per codice ordine `ORD-...`;
- per codice biglietto `TKT-...`.

Può visualizzare l'ordine e i biglietti collegati.

Se l'ordine non è pagato e non è annullato, può segnare il pagamento come saldato. In questo caso:

- `Ordini.stato_pagamento` diventa `Pagato`;
- `Ordini.metodo_pagamento` viene impostato a `contanti`;
- tutti i biglietti dell'ordine con stato `Non pagato` diventano `Valido`;
- viene inviata una email di conferma con PDF, se il sistema email funziona.

---

## 10. Database

Il file SQL di riferimento è:

```text
db_completo_tabelle.sql
```

Questo file contiene solo la creazione delle tabelle, senza `INSERT INTO`.

### 10.1 Tabella `Utenti`

La tabella `Utenti` gestisce utenti registrati, ruoli, verifica email e recupero password.

Campi principali:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_utente` | INT AUTO_INCREMENT | Identificativo univoco utente. |
| `nome` | VARCHAR(50) | Nome dell'utente. |
| `cognome` | VARCHAR(50) | Cognome dell'utente. |
| `email` | VARCHAR(100) | Email univoca. |
| `password_hash` | VARCHAR(255) | Hash bcrypt della password. |
| `domanda_sicurezza` | VARCHAR(100) | Chiave della domanda di sicurezza. |
| `risposta_sicurezza_hash` | VARCHAR(255) | Hash bcrypt della risposta normalizzata. |
| `ruolo` | ENUM | Ruolo applicativo. |
| `email_verificata` | TINYINT | Indica se l'email è stata verificata. |
| `codice_verifica_email` | CHAR(6) | Codice email a 6 cifre. |
| `codice_verifica_scadenza` | DATETIME | Scadenza del codice di verifica email. |
| `data_registrazione` | DATETIME | Data di creazione account. |
| `password_reset_code` | CHAR(6) | Codice recupero password. |
| `password_reset_scadenza` | DATETIME | Scadenza codice recupero password. |

Vincoli e indici:

- chiave primaria su `id_utente`;
- vincolo univoco su `email`;
- indice su `ruolo`;
- indice su `email_verificata`.

### 10.2 Tabella `Esposizioni`

La tabella `Esposizioni` contiene le mostre del museo.

Campi principali:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_esposizione` | INT AUTO_INCREMENT | Identificativo esposizione. |
| `titolo` | VARCHAR(150) | Titolo della mostra. |
| `descrizione` | TEXT | Descrizione testuale. |
| `emoji` | VARCHAR(10) | Icona visuale associata alla mostra. |
| `data_inizio` | DATE | Data inizio. |
| `data_fine` | DATE | Data fine. |
| `stato` | ENUM | Stato della mostra: Bozza, Pubblicata, Conclusa, Annullata. |

Vincoli:

- `data_fine >= data_inizio`;
- indici su stato/date.

### 10.3 Tabella `Categorie_Riduzione`

Contiene le categorie tariffarie.

Campi:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_categoria` | INT AUTO_INCREMENT | Identificativo categoria. |
| `nome` | VARCHAR(80) | Nome categoria, univoco. |
| `percentuale_sconto` | DECIMAL(5,2) | Percentuale di sconto informativa/gestionale. |
| `documento_richiesto` | VARCHAR(150) | Documento richiesto per accedere alla riduzione. |

Vincoli:

- nome categoria univoco;
- sconto compreso tra 0 e 100.

### 10.4 Tabella `Tariffe`

Associa un prezzo a un tipo biglietto e a una categoria.

Campi:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_tariffa` | INT AUTO_INCREMENT | Identificativo tariffa. |
| `tipo_biglietto` | ENUM | `base` oppure `esposizione`. |
| `id_categoria` | INT | Categoria di riduzione. |
| `prezzo` | DECIMAL(8,2) | Prezzo finale. |

Vincoli:

- combinazione univoca `tipo_biglietto + id_categoria`;
- foreign key verso `Categorie_Riduzione`;
- prezzo non negativo.

### 10.5 Tabella `Servizi_Opzionali`

Contiene servizi aggiuntivi acquistabili con i biglietti.

Campi:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_servizio` | INT AUTO_INCREMENT | Identificativo servizio. |
| `nome` | VARCHAR(100) | Nome servizio. |
| `descrizione` | TEXT | Descrizione. |
| `prezzo` | DECIMAL(8,2) | Prezzo. |

Vincoli:

- nome servizio univoco;
- prezzo non negativo.

### 10.6 Tabella `Fasce_Orarie`

Contiene le fasce prenotabili per una esposizione.

Campi:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_fascia` | INT AUTO_INCREMENT | Identificativo fascia. |
| `id_esposizione` | INT | Esposizione collegata. |
| `data` | DATE | Giorno della visita. |
| `ora_ingresso` | TIME | Ora di ingresso. |
| `capienza_massima` | SMALLINT | Numero massimo di posti. |

Vincoli:

- una sola fascia per stessa esposizione, data e ora;
- foreign key verso `Esposizioni`;
- capienza maggiore di zero.

### 10.7 Tabella `Ordini`

Contiene gli ordini generati da prenotazioni e pagamenti.

Campi principali:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_ordine` | INT AUTO_INCREMENT | Identificativo ordine. |
| `id_utente` | INT NULL | Utente collegato, se loggato. |
| `codice_recupero` | VARCHAR(20) | Codice ordine pubblico per recupero. |
| `nome_cliente` | VARCHAR(120) | Nome del cliente/referente. |
| `email_cliente` | VARCHAR(120) | Email cliente/referente. |
| `data_acquisto` | DATETIME | Data ordine. |
| `importo_totale` | DECIMAL(10,2) | Totale dell'ordine. |
| `stato_pagamento` | ENUM | In attesa, Pagato, Annullato, Non pagato. |
| `metodo_pagamento` | ENUM | contanti, carta, paypal. |
| `prenotazione_docente` | TINYINT | Indica ordine classe/docente. |
| `nome_scuola` | VARCHAR(150) | Nome scuola. |
| `codice_meccanografico` | VARCHAR(20) | Codice meccanografico. |
| `indirizzo_scuola` | VARCHAR(200) | Indirizzo scuola. |
| `citta_scuola` | VARCHAR(100) | Città scuola. |
| `telefono_scuola` | VARCHAR(30) | Telefono scuola. |
| `classe_scuola` | VARCHAR(50) | Classe/sezione. |
| `quantita_studenti` | INT | Numero studenti. |
| `numero_docenti` | INT | Numero docenti accompagnatori. |
| `note_scuola` | TEXT | Note per il museo. |

Vincoli:

- codice recupero univoco;
- foreign key verso `Utenti` con `ON DELETE SET NULL`;
- totale non negativo;
- controlli su prenotazione docente, studenti e docenti.

### 10.8 Tabella `Biglietti`

Contiene ogni biglietto generato.

Campi principali:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_biglietto` | INT AUTO_INCREMENT | Identificativo biglietto. |
| `codice_univoco` | VARCHAR(36) | Codice ticket pubblico, tipo `TKT-...`. |
| `id_ordine` | INT | Ordine collegato. |
| `tipo` | ENUM | `base` oppure `esposizione`. |
| `data_validita` | DATE | Data visita. |
| `id_fascia` | INT NULL | Fascia, solo per biglietti esposizione. |
| `id_categoria` | INT NULL | Categoria tariffaria. |
| `prezzo_lordo` | DECIMAL(8,2) | Prezzo intero di riferimento. |
| `sconto_applicato` | DECIMAL(8,2) | Sconto applicato. |
| `stato` | ENUM | Valido, Utilizzato, Annullato, Non pagato. |
| `data_utilizzo` | DATETIME | Momento di validazione. |

Vincoli:

- codice univoco;
- foreign key verso `Ordini`;
- foreign key verso `Fasce_Orarie`;
- foreign key verso `Categorie_Riduzione`;
- se `tipo = esposizione`, `id_fascia` deve essere presente;
- se `tipo = base`, `id_fascia` deve essere `NULL`;
- prezzi e sconti non negativi.

### 10.9 Tabella `Biglietti_Servizi`

È una tabella ponte molti-a-molti tra biglietti e servizi opzionali.

Campi:

| Campo | Tipo | Funzione |
|---|---|---|
| `id_biglietto` | INT | Biglietto collegato. |
| `id_servizio` | INT | Servizio collegato. |
| `prezzo_snapshot` | DECIMAL(8,2) | Prezzo del servizio al momento dell'acquisto. |

La scelta di salvare `prezzo_snapshot` è corretta perché consente di conservare il prezzo storico anche se in futuro il prezzo del servizio viene modificato nell'area admin.

---

## 11. File di configurazione e infrastruttura

### 11.1 `.htaccess`

Il file `.htaccess` fa tre cose:

1. definisce la pagina di errore 404:

```apache
ErrorDocument 404 /museo/404.php
```

2. abilita cache lunga per file statici, quando il modulo Apache `mod_expires` è disponibile:

- immagini WebP, PNG, JPEG, SVG: 1 anno;
- CSS: 1 mese;
- JavaScript: 1 mese;
- font WOFF/WOFF2: 1 anno.

3. abilita header `Cache-Control` per file statici e compressione gzip/deflate per contenuti testuali.

Questo migliora le prestazioni e il punteggio Lighthouse nelle visite successive.

### 11.2 `config.php`

`config.php` è il file centrale di configurazione.

Contiene:

- riconoscimento ambiente locale/online;
- configurazione errori PHP;
- configurazione database;
- definizione `SITE_NAME`;
- calcolo dinamico di `SITE_URL`;
- configurazione SMTP/PHPMailer;
- avvio sessione.

#### Riconoscimento ambiente

Il codice legge `$_SERVER['HTTP_HOST']` e considera locale:

- `localhost`;
- `127.0.0.1`;
- host che contengono `localhost`.

In locale usa:

```php
DB_HOST = localhost
DB_NAME = biglietteria_museo
DB_USER = root
DB_PASS = ''
```

Online usa valori dedicati al server Altervista o hosting configurato.

#### Errori PHP

Nel file attuale `display_errors` è attivo anche online. È utile in fase di debug, ma per produzione andrebbe impostato a `0` online.

#### SMTP

Sono presenti costanti per SMTP:

- `SMTP_ACTIVE`
- `SMTP_HOST`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_PORT`
- `SMTP_SECURE`
- `MAIL_FROM`
- `MAIL_FROM_NAME`

Nota di sicurezza: il README non riporta password o segreti. In un progetto reale le credenziali andrebbero tolte dal codice e spostate in variabili d'ambiente o in un file non versionato.

#### Sessione

Il file avvia la sessione se non già attiva e configura:

- `session.cookie_httponly = 1`;
- `session.use_strict_mode = 1`;
- `session.cookie_secure = 1` quando non si è in localhost.

### 11.3 `db.php`

Il file `db.php` contiene una sola funzione:

#### `getDB(): PDO`

Crea e restituisce una connessione PDO al database.

Caratteristiche:

- usa un oggetto statico `$pdo` per riutilizzare la stessa connessione nella stessa richiesta;
- costruisce il DSN con host, nome database e charset;
- imposta `PDO::ATTR_ERRMODE` a `PDO::ERRMODE_EXCEPTION`;
- imposta `PDO::ATTR_DEFAULT_FETCH_MODE` a `PDO::FETCH_ASSOC`;
- disattiva `PDO::ATTR_EMULATE_PREPARES`;
- in caso di errore scrive nel log PHP e termina con un messaggio JSON generico.

---

## 12. Autenticazione e sicurezza applicativa: `auth.php`

`auth.php` centralizza login, registrazione, ruoli, CSRF e pulizia output.

### 12.1 `generaCodice6(): string`

Genera un codice numerico casuale a 6 cifre.

Viene usato per:

- verifica email;
- recupero password.

Il codice è generato con `random_int(0, 999999)` e completato con zeri iniziali tramite `str_pad()`.

### 12.2 `colonnaCodiceVerificaEmail(PDO $pdo): string`

Determina quale colonna usare per il codice verifica email.

Cerca in ordine:

1. `codice_verifica_email`;
2. `codice_verifica`.

Lo fa con più strategie:

- query su `INFORMATION_SCHEMA.COLUMNS`;
- `SHOW COLUMNS`;
- tentativo di `SELECT`.

Serve per compatibilità con versioni precedenti del database.

### 12.3 `loginUtente(string $email, string $password): array`

Gestisce il login.

Passaggi:

1. cerca l'utente per email;
2. verifica la password con `password_verify()`;
3. controlla che `email_verificata` sia uguale a 1, se la colonna esiste;
4. rigenera l'ID sessione con `session_regenerate_id(true)`;
5. salva in sessione:
   - `utente_id`;
   - `utente_nome`;
   - `utente_email`;
   - `utente_ruolo`;
6. restituisce successo e ruolo.

Se l'account non è verificato restituisce un array con `verification_required`.

### 12.4 `normalizzaRispostaSicurezza(string $risposta): string`

Normalizza una risposta di sicurezza:

- rimuove spazi eccessivi;
- applica `trim()`;
- converte in minuscolo con `mb_strtolower()`.

Questo rende meno fragile il confronto della risposta di sicurezza.

### 12.5 `registraUtente(...)`

Registra un nuovo utente.

Parametri:

- nome;
- cognome;
- email;
- password;
- domanda di sicurezza;
- risposta di sicurezza.

Passaggi:

1. controlla che l'email non sia già registrata;
2. genera hash password con bcrypt cost 12;
3. normalizza e hasha la risposta di sicurezza con bcrypt cost 12;
4. genera codice verifica a 6 cifre;
5. imposta scadenza codice a 24 ore;
6. inserisce l'utente con ruolo `visitatore` e `email_verificata = 0`;
7. invia email di verifica tramite `inviaEmailVerificaAccount()`;
8. restituisce messaggio e stato invio email.

### 12.6 `logoutUtente(): void`

Esegue logout completo:

- svuota `$_SESSION`;
- elimina il cookie di sessione;
- distrugge la sessione.

### 12.7 Funzioni ruolo

#### `isLogged(): bool`

Restituisce `true` se in sessione è presente `utente_id`.

#### `ruoloCorrente(): string`

Restituisce il ruolo salvato in sessione.

#### `isTester(): bool`

Restituisce `true` se il ruolo è `tester`.

#### `isAdmin(): bool`

Restituisce `true` per `amministratore` e `tester`.

#### `isOperatore(): bool`

Restituisce `true` per `operatore` e `tester`.

#### `isCassiere(): bool`

Restituisce `true` per `cassiere` e `tester`.

### 12.8 Funzioni di accesso obbligatorio

#### `requireLogin(string $redirect = 'login.php'): void`

Se l'utente non è loggato, lo manda al login.

#### `requireAdmin(string $redirect = 'index.php'): void`

Se l'utente non è loggato, lo manda al login. Se è loggato ma non è amministratore/tester, lo rimanda alla pagina indicata.

#### `requireCassiere(string $redirect = 'index.php'): void`

Se l'utente non è loggato, lo manda al login. Se è loggato ma non è cassiere/tester, lo rimanda alla pagina indicata.

### 12.9 CSRF

#### `csrfToken(): string`

Genera e conserva in sessione un token CSRF di 32 byte casuali convertiti in esadecimale.

#### `verifyCsrf(string $token): bool`

Confronta il token ricevuto con quello in sessione usando `hash_equals()`.

### 12.10 `clean(string $val): string`

Esegue:

- `trim()`;
- `htmlspecialchars()` con `ENT_QUOTES` e charset UTF-8.

È usata per stampare dati in pagina riducendo il rischio XSS.

---

## 13. Layout comune: `header.php` e `footer.php`

### 13.1 `header.php`

`header.php` è incluso dalla maggior parte delle pagine.

Funzioni e caratteristiche:

- carica `config.php` e `auth.php`;
- calcola la versione CSS con `filemtime()` per cache busting;
- carica CSS diverso per homepage e pagine interne;
- sulla homepage usa CSS critico inline da `home-critical.css`;
- sulle pagine interne carica `tailwind-local.css` e `style.css`;
- definisce meta description specifiche per molte pagine;
- usa logo responsive WebP con `srcset`;
- crea la navbar desktop;
- crea menu mobile;
- mostra pulsanti `Accedi` e `Registrati` se l'utente non è loggato;
- mostra menu utente se l'utente è loggato;
- mostra link speciali in base al ruolo:
  - `Vista amministratore`;
  - `Valida biglietti`;
  - `Cassa`;
- aggiunge menu admin mobile quando la pagina corrente è `admin.php` e l'utente è admin;
- contiene JavaScript per aprire/chiudere il menu mobile;
- sposta gli alert flottanti nel `body` per posizionarli correttamente.

### 13.2 Menu pubblico

Voci principali:

- Home;
- Chi siamo;
- Esposizioni;
- Novità;
- Info & Tariffe.

### 13.3 Menu utente loggato

Voci principali:

- Il mio account;
- I miei ordini;
- Vista amministratore, se autorizzato;
- Valida biglietti, se autorizzato;
- Cassa, se autorizzato;
- Logout.

### 13.4 `footer.php`

Il footer contiene:

- logo del museo;
- descrizione breve;
- link rapidi;
- contatti;
- indirizzo;
- email;
- orari;
- copyright dinamico con `date('Y')`;
- citazione di Cicerone.

La colonna dei link rapidi ha classe `print:hidden`, utile per non stampare link rapidi quando si stampa la pagina biglietti.

---

## 14. Area pubblica

### 14.1 `index.php` — Homepage

La homepage mostra:

- hero istituzionale;
- logo ottimizzato per LCP;
- claim del Museo Storico Severi;
- pulsanti principali;
- eventuale pulsante gestionale in base al ruolo;
- statistiche dinamiche;
- esposizioni pubblicate recenti;
- sezione “Come visitarci”.

#### Dati dinamici caricati

La home carica dal database:

- le ultime 4 esposizioni con stato `Pubblicata`;
- il numero di esposizioni attive;
- il numero di servizi opzionali.

#### Funzione `indexEsposizioniSupportaEmoji(PDO $pdo): bool`

Controlla se la tabella `Esposizioni` contiene la colonna `emoji`.

Serve per compatibilità: se la colonna non esiste, la homepage usa icone di fallback.

#### Pulsante gestionale dinamico

Se l'utente è loggato, la home prepara `$areaGestionale`:

- tester → `Pannello tester` verso `admin.php`;
- amministratore → `Vista amministratore` verso `admin.php`;
- operatore → `Valida biglietti` verso `valida_biglietti.php`;
- cassiere → `Cassa` verso `cassa.php`.

### 14.2 `chi_siamo.php`

Pagina statica di presentazione.

Sezioni principali:

- Chi siamo;
- Perché un museo;
- Tecnologia reale;
- Lavoro di squadra;
- Imparare costruendo qualcosa che funziona;
- Cosa abbiamo voluto allenare;
- Un progetto scolastico, ma con logica professionale.

La pagina posiziona il progetto come esperienza didattica che unisce storia, tecnologia, accessibilità e competenze digitali.

### 14.3 `esposizioni.php`

Mostra l'elenco delle esposizioni.

Caratteristiche:

- filtro per stato tramite query string `?stato=...`;
- stati ammessi: `Pubblicata`, `Conclusa`, `Annullata`, `Bozza`;
- le bozze sono visibili solo agli amministratori;
- gli amministratori possono vedere anche le bozze;
- gli utenti normali vedono tutto tranne `Bozza`;
- card grande per “Visita solo il museo”;
- card per ogni esposizione;
- badge di stato;
- pulsante `Prenota` solo per esposizioni pubblicate;
- link alla prenotazione base per il solo museo.

#### Funzione `esposizioniPaginaSupportaEmoji(PDO $pdo): bool`

Controlla la presenza della colonna `emoji` nella tabella `Esposizioni`.

Se disponibile, la pagina usa l'emoji salvata nel database.

### 14.4 `novita.php`

Mostra aggiornamenti e ultime esposizioni.

Caratteristiche:

- conta esposizioni disponibili;
- mostra una sezione hero “Novità dal museo”;
- mostra una card “In evidenza”;
- mostra avvisi rapidi per la visita;
- mostra l'ultima esposizione inserita in evidenza;
- mostra altre esposizioni in griglia;
- usa le stesse emoji salvate nelle esposizioni;
- mostra badge colore in base allo stato;
- se l'esposizione è pubblicata, offre link `Prenota ora`.

#### Funzione `colonnaEsposizioniEsiste(PDO $pdo, string $colonna): bool`

Controlla se una colonna esiste in `Esposizioni`.

#### Funzione `emojiEsposizioneNovita(array $esposizione): string`

Restituisce l'emoji dell'esposizione, oppure `🏛️` se non presente.

#### Funzione `badgeStatoClass(string $stato): string`

Restituisce le classi CSS per colorare lo stato:

- `Pubblicata` → verde;
- `Bozza` → giallo;
- `Conclusa` → grigio;
- `Annullata` → rosso;
- default → avorio/antracite.

### 14.5 `info.php`

Pagina informazioni e tariffe.

Mostra:

- orari;
- luogo;
- servizi;
- tariffe disponibili;
- card per biglietti base;
- card per biglietti esposizione;
- elenco dei servizi opzionali.

Dati caricati:

- tariffe da `Tariffe` unite a `Categorie_Riduzione`;
- servizi da `Servizi_Opzionali`.

Le tariffe sono divise per `tipo_biglietto`:

- `base` → solo museo;
- `esposizione` → esposizione specifica.

### 14.6 `404.php`

Pagina di errore 404.

Caratteristiche:

- imposta `http_response_code(404)`;
- mostra logo;
- messaggio “Pagina non trovata”;
- pulsante per tornare alla home;
- pulsante per tornare indietro con `javascript:history.back()`.

---

## 15. Registrazione, login, verifica email e recupero password

### 15.1 `registrazione.php`

Pagina per creare un account.

Campi richiesti:

- nome;
- cognome;
- email;
- password;
- conferma password;
- domanda di sicurezza;
- risposta di sicurezza.

Validazioni:

- nome obbligatorio;
- cognome obbligatorio;
- email valida;
- password lunga almeno 8 caratteri;
- password e conferma uguali;
- domanda di sicurezza valida;
- risposta di sicurezza obbligatoria;
- CSRF valido.

Funzioni presenti:

#### `fieldClass(string $field, array $errors): string`

Restituisce classi CSS diverse se un campo ha errore.

#### `togglePasswordVisibility(inputId, button)`

Funzione JavaScript che permette di mostrare/nascondere la password tramite pulsante con icona occhio.

#### `checkStrength(pw)`

Funzione JavaScript che calcola una stima della forza password e aggiorna barra/testo.

La registrazione non abilita immediatamente l'accesso: l'utente deve verificare l'email.

### 15.2 `login.php`

Pagina di accesso.

Campi:

- email;
- password.

Comportamenti:

- se l'utente è già loggato, viene mandato alla home;
- valida CSRF;
- chiama `loginUtente()`;
- se il login riesce, reindirizza alla home;
- se l'account non è verificato, reindirizza a `verifica_email.php`;
- mostra eventuali messaggi da query string, ad esempio registrazione, verifica, password aggiornata;
- contiene un pulsante per mostrare/nascondere la password;
- offre link alla registrazione;
- offre link all'esplorazione mostre senza registrazione.

### 15.3 `verifica_email.php`

Pagina di verifica dell'account.

Funziona con codice numerico a 6 cifre.

Flusso:

1. l'utente arriva dopo la registrazione o dopo un tentativo di login con account non verificato;
2. inserisce email e codice;
3. il codice viene confrontato con quello salvato in `Utenti`;
4. viene controllata la scadenza;
5. se corretto, `email_verificata` diventa `1`;
6. il codice viene annullato;
7. l'utente viene mandato al login.

Funzioni:

#### `caricaUtenteDaEmail(PDO $pdo, string $email): ?array`

Carica utente e codice verifica email usando la colonna corretta determinata da `colonnaCodiceVerificaEmail()`.

Azioni POST:

- `verifica`;
- `reinvia`.

L'azione `reinvia` genera un nuovo codice valido 24 ore e tenta l'invio email.

### 15.4 `recupero_password.php`

Pagina per recuperare password.

Il recupero usa una doppia verifica:

1. codice email a 6 cifre;
2. risposta alla domanda di sicurezza.

Step principali:

- `email`: inserimento email;
- `verifica`: inserimento risposta, codice e nuova password;
- `completato`: password aggiornata e redirect al login dopo pochi secondi.

Funzioni:

#### `generaCodiceRecuperoPassword(): string`

Wrapper di `generaCodice6()`.

#### `caricaUtenteRecupero(PDO $pdo, string $email): ?array`

Carica utente, domanda, hash risposta, codice reset e scadenza.

#### `generaInviaCodiceRecupero(PDO $pdo, array $utente): bool`

Genera codice reset, lo salva con scadenza a 30 minuti e invia email.

Azioni POST:

- `cerca_email`;
- `reinvia_codice_recupero`;
- `reset_password`.

Validazioni per reset:

- email valida;
- risposta presente;
- codice a 6 cifre;
- password lunga almeno 8 caratteri;
- conferma uguale;
- codice corretto;
- codice non scaduto;
- risposta di sicurezza corretta.

Quando la password è aggiornata:

- viene salvato un nuovo hash bcrypt;
- `password_reset_code` viene messo a `NULL`;
- `password_reset_scadenza` viene messo a `NULL`;
- viene fatto redirect al login dopo 3 secondi.

### 15.5 `logout.php`

Esegue logout chiamando `logoutUtente()` e reindirizza alla home.

### 15.6 `elimina_account.php`

Permette all'utente loggato di eliminare il proprio account.

Sicurezze richieste:

- utente loggato;
- CSRF valido;
- inserimento esatto della propria email;
- inserimento esatto della parola `CONFERMA`.

Se tutto è corretto:

- cancella l'utente da `Utenti`;
- esegue logout;
- reindirizza alla registrazione con parametro `account_deleted=1`.

---

## 16. Area account e ordini

### 16.1 `account.php`

Pagina riservata agli utenti loggati.

Usa `requireLogin()`.

Carica:

- dati completi dell'utente;
- ultimi 5 ordini con conteggio biglietti.

Sezioni a tab:

1. `Il mio profilo`;
2. `Sicurezza`;
3. `I miei ordini`.

La sidebar contiene anche:

- Logout;
- Elimina account come ultima sezione, evidenziata con sfondo rosso.

#### Aggiornamento profilo

Azione POST: `update_profile`.

Permette di modificare:

- nome;
- cognome;
- email.

Validazioni:

- nome obbligatorio;
- cognome obbligatorio;
- email valida;
- email non già usata da altro utente.

Aggiorna anche la sessione:

- `utente_nome`;
- `utente_email`.

#### Cambio password

Azione POST: `change_password`.

Richiede:

- password attuale;
- nuova password;
- conferma nuova password.

Validazioni:

- password attuale corretta;
- nuova password almeno 8 caratteri;
- conferma uguale.

Salva la nuova password con bcrypt cost 12.

#### Funzione JavaScript `showTab(name)`

Nasconde tutte le sezioni e mostra la tab selezionata.

### 16.2 `ordini.php`

Pagina riservata agli utenti loggati.

Mostra tutti gli ordini dell'utente.

Per ogni ordine mostra:

- ID ordine;
- numero biglietti;
- stato pagamento;
- esposizioni collegate o “Biglietto museo”;
- data acquisto;
- codice recupero;
- totale;
- link `Vedi biglietti`;
- link `Paga` se l'ordine è `Non pagato`.

### 16.3 `ordine_dettaglio.php`

File di redirect tecnico.

Riceve `id` ordine.

Se l'utente è amministratore, può recuperare l'ordine per ID. Se è utente normale, può recuperarlo solo se appartiene a lui.

Poi reindirizza a:

```text
biglietti.php?codice=CODICE_RECUPERO
```

Se l'ordine non è valido, torna a `ordini.php`.

---

## 17. Prenotazione standard: `prenota.php`

`prenota.php` gestisce il form di prenotazione normale.

### 17.1 Tipo prenotazione

Il tipo viene determinato così:

- se in query string c'è `id` maggiore di zero → tipo `esposizione`;
- altrimenti → tipo `base`.

Per `esposizione`, carica solo mostre con stato `Pubblicata`.

### 17.2 Dati caricati

La pagina carica:

- dati esposizione, se presente;
- fasce orarie con posti disponibili;
- tariffe del tipo corretto;
- servizi opzionali.

Le tariffe escludono la categoria `Docente accompagnatore`, perché viene gestita solo nella prenotazione classe.

### 17.3 Campi del form

Campi principali:

- nome e cognome;
- email;
- fascia oraria, se esposizione;
- data visita, se biglietto base;
- tariffa;
- numero posti;
- servizi opzionali;
- metodo di pagamento.

Il numero posti nella prenotazione standard ha limite massimo 20.

### 17.4 Metodi di pagamento

La pagina offre tre opzioni:

- `contanti`: ordine emesso, biglietti non pagati;
- `carta`: pagamento simulato con dati carta;
- `paypal`: pagamento simulato con email PayPal.

Il form invia a `pagamento.php`.

### 17.5 Collegamento alla prenotazione docente

La pagina mostra un box:

```text
Sei un docente e vuoi portare la tua classe?
```

con link a `prenota_docente.php`, mantenendo l'eventuale esposizione selezionata.

---

## 18. Prenotazione classe/docente: `prenota_docente.php`

Questa pagina è pensata per gruppi scolastici.

### 18.1 Caricamento dati

Come `prenota.php`, determina se la prenotazione è:

- `base`;
- `esposizione`.

Carica:

- esposizione pubblicata;
- fasce orarie;
- tariffe escluse quelle per docente accompagnatore;
- servizi opzionali.

### 18.2 Campi aggiuntivi

Oltre ai campi standard, richiede:

- docente referente;
- email referente;
- nome scuola;
- codice meccanografico;
- indirizzo scuola;
- città scuola;
- telefono scuola;
- classe/sezione;
- numero studenti;
- numero docenti accompagnatori;
- note per il museo.

### 18.3 Logica studenti/docenti

Gli studenti pagano la tariffa selezionata.

I docenti accompagnatori hanno biglietto gratuito a `0,00 €`.

I servizi opzionali selezionati vengono associati a tutti i partecipanti: studenti e docenti.

### 18.4 Nessun limite ordinario

La pagina comunica che il numero di studenti e docenti non ha limite massimo nella prenotazione classe.

### 18.5 Collegamento alla prenotazione standard

Mostra anche il link per tornare alla prenotazione standard.

---

## 19. Pagamento, creazione ordini e biglietti: `pagamento.php`

`pagamento.php` è uno dei file più importanti del progetto.

Gestisce:

- ricezione dei dati da `prenota.php` o `prenota_docente.php`;
- preparazione ordine;
- validazione dati;
- calcolo totale;
- simulazione carta;
- simulazione PayPal;
- generazione ordine non pagato per contanti;
- generazione ordine non pagato in caso di errore pagamento;
- saldo di un ordine già esistente;
- creazione biglietti;
- associazione servizi;
- invio email di conferma con PDF.

### 19.1 `generaCodiceOrdine(PDO $pdo): string`

Genera codice ordine del tipo:

```text
ORD-XXXXXXXX
```

Usa `bin2hex(random_bytes(4))`, lo rende maiuscolo e controlla che non esista già in `Ordini`.

### 19.2 `generaCodiceBiglietto(PDO $pdo): string`

Genera codice ticket del tipo:

```text
TKT-XXXXXXXXXX
```

Usa `bin2hex(random_bytes(5))`, lo rende maiuscolo e controlla che non esista già in `Biglietti`.

### 19.3 `colonnaEsiste(PDO $pdo, string $tabella, string $colonna): bool`

Controlla se una colonna esiste in una tabella tramite `SHOW COLUMNS`.

È usata per compatibilità con database leggermente diversi.

### 19.4 `idCategoriaDocente(PDO $pdo): ?int`

Cerca la categoria con nome `Docente accompagnatore`.

Se la trova, restituisce `id_categoria`; altrimenti `null`.

### 19.5 `normalizzaInputPagamento(array $input): array`

Assicura che il campo `servizi` sia sempre un array.

Se arriva un singolo valore, lo converte in array. Se manca, imposta array vuoto.

### 19.6 `preparaOrdine(PDO $pdo, array $dati): array`

Prepara tutti i dati necessari alla creazione dell'ordine.

Validazioni principali:

- tipo biglietto valido: `base` o `esposizione`;
- nome cliente presente;
- email valida;
- metodo pagamento valido;
- tariffa valida;
- per prenotazione docente: nome scuola, città e classe obbligatori;
- impedisce di scegliere manualmente la tariffa `Docente accompagnatore`;
- per esposizione: fascia esistente e mostra pubblicata;
- per prenotazione standard: disponibilità posti sufficiente;
- per biglietto base: data visita valida.

Calcoli principali:

- quantità studenti;
- numero docenti;
- quantità totale;
- prezzo lordo di riferimento;
- prezzo finale;
- sconto applicato;
- totale servizi per singolo biglietto;
- totale studenti;
- totale docenti;
- totale ordine.

Per le esposizioni controlla i posti disponibili contando i biglietti non annullati nella fascia.

Nella prenotazione docente, il controllo dei posti non blocca nello stesso modo la quantità classe.

### 19.7 `creaOrdineConBiglietti(PDO $pdo, array $datiOrdine, string $statoPagamento, string $statoBiglietto): array`

Crea materialmente ordine e biglietti.

Usa una transazione.

Passaggi:

1. genera codice recupero ordine;
2. salva `id_utente` se l'utente è loggato;
3. prepara campi ordine;
4. aggiunge campi extra della prenotazione docente se presenti nel database;
5. inserisce record in `Ordini`;
6. prepara statement per `Biglietti`;
7. prepara statement per `Biglietti_Servizi`;
8. crea un biglietto per ogni studente/visitatore pagante;
9. crea un biglietto per ogni docente accompagnatore;
10. associa eventuali servizi a ogni biglietto;
11. fa commit;
12. restituisce dati ordine e codici biglietto.

Se qualcosa fallisce, esegue rollback.

### 19.8 `creaPayloadPagamento(array $dati): string`

Rimuove il token CSRF e serializza i dati prenotazione in JSON, poi li codifica in base64.

Serve per conservare i dati della prenotazione nella schermata di pagamento simulato.

### 19.9 `leggiPayloadPagamento(string $payload): array`

Decodifica il payload base64 e JSON.

Se i dati non sono validi, lancia errore.

### 19.10 `cartaLuhnValida(string $numero): bool`

Non applica un vero controllo Luhn rigido.

Accetta numeri realistici da 13 a 19 cifre e rifiuta numeri palesemente finti composti dalla stessa cifra ripetuta, come tutti zero o tutti uno.

Questo è coerente con un pagamento simulato didattico.

### 19.11 `scadenzaCartaValida(string $scadenza): bool`

Valida formato `MM/AA` e controlla che la scadenza non sia passata.

### 19.12 `validaPagamentoSimulato(string $metodo, array $input): void`

Valida i dati del pagamento simulato.

Per carta richiede:

- titolare;
- numero carta realistico;
- scadenza valida;
- CVV da 3 o 4 cifre.

Per PayPal richiede:

- email PayPal valida.

Se i dati non vanno bene, lancia un messaggio che suggerisce la possibilità di generare l'ordine come non pagato.

### 19.13 `creaFormPagamentoDaDati(array $dati, array $datiOrdine): array`

Prepara i dati da mostrare nella schermata pagamento simulato:

- metodo;
- payload;
- totale;
- nome;
- email;
- percorso.

### 19.14 `caricaOrdineUtenteDaPagare(PDO $pdo, int $idOrdine): ?array`

Carica un ordine non pagato collegato all'utente loggato.

Viene usata quando un utente clicca `Paga` dalla sua area ordini.

Carica anche:

- quantità biglietti;
- tipo;
- data validità;
- codici biglietti;
- titolo percorso.

### 19.15 `marcaOrdinePagato(PDO $pdo, int $idOrdine, string $metodo): void`

Aggiorna un ordine esistente a pagato.

Passaggi:

1. apre transazione;
2. aggiorna `Ordini.stato_pagamento` a `Pagato`;
3. aggiorna `metodo_pagamento` se la colonna esiste;
4. aggiorna i biglietti `Non pagato` a `Valido`;
5. commit.

### 19.16 `codiciDaOrdine(array $ordine): array`

Legge la stringa `codici_biglietti`, separata da virgole, e restituisce un array di codici.

### 19.17 `inviaEmailOrdineDopoRisposta(array $ordine, array $codici): void`

Gestisce l'invio email dopo che la risposta al browser è stata preparata.

Caratteristiche:

- chiude la sessione se possibile;
- usa `fastcgi_finish_request()` se disponibile;
- in alternativa prova `ob_flush()` e `flush()`;
- crea PDF temporaneo;
- invia email di conferma ordine;
- cancella il file temporaneo;
- in caso di errore scrive nel log `mail_debug/mail_error_log.txt`.

### 19.18 Flussi gestiti dal file

#### GET con `ordine`

Esempio:

```text
pagamento.php?ordine=12
```

Serve per pagare un ordine esistente. Richiede login.

#### POST `paga_ordine`

Paga un ordine già esistente dell'utente loggato.

Nel codice attuale viene usato metodo carta.

#### POST `genera_non_pagato`

Crea ordine e biglietti con stato `Non pagato`.

È utile quando il pagamento simulato fallisce e si vuole comunque generare l'ordine da saldare in seguito.

#### POST `conferma_pagamento`

Conferma un pagamento simulato carta o PayPal.

Se la validazione va bene:

- ordine `Pagato`;
- biglietti `Valido`;
- email inviata.

#### POST iniziale da prenotazione

Se il metodo è `contanti`, crea subito ordine `Non pagato`. Se è carta o PayPal, mostra il form di pagamento simulato.

### 19.19 JavaScript pagamento

In fondo al file sono presenti due funzioni JavaScript:

#### `formattaNumeroCarta(input)`

Durante la digitazione, mantiene solo numeri, limita a 19 cifre e inserisce uno spazio visuale ogni 4 cifre.

#### `formattaScadenzaCarta(input)`

Durante la digitazione, mantiene solo numeri e inserisce automaticamente lo slash dopo mese:

```text
MM/AA
```

---

## 20. Recupero e visualizzazione biglietti

### 20.1 `recupera_ordine.php`

Pagina pubblica per inserire il codice ordine.

Validazioni:

- CSRF;
- codice non vuoto.

Poi reindirizza a `biglietti.php?codice=...`.

### 20.2 `biglietti.php`

Mostra ordine e biglietti.

Riceve:

```text
?codice=ORD-XXXXXXXX
```

Passaggi:

1. normalizza il codice in maiuscolo;
2. cerca l'ordine in `Ordini`;
3. se non lo trova, mostra errore;
4. carica i biglietti associati;
5. unisce categoria, fascia, esposizione e servizi;
6. mostra riepilogo ordine;
7. se è ordine classe, mostra dati scuola;
8. mostra totale;
9. mostra pulsanti `Stampa` e `Scarica PDF`;
10. mostra i biglietti in card.

Per ogni biglietto mostra:

- codice biglietto;
- stato;
- tipo;
- percorso;
- data e ora;
- categoria;
- servizi;
- prezzo.

### 20.3 Gestione stampa

Il file contiene CSS specifico per stampa:

- elementi `no-pdf` nascosti;
- card biglietto con `break-inside: avoid`;
- gruppi di biglietti in pagine;
- interruzioni pagina con `.pdf-page-break`.

I biglietti vengono raggruppati in blocchi da 4 tramite `array_chunk($biglietti, 4)`.

### 20.4 `scarica_pdf.php`

Genera e scarica il PDF dell'ordine.

Riceve:

```text
?codice=ORD-XXXXXXXX
```

Passaggi:

1. valida codice;
2. cerca ordine;
3. carica informazioni su tipo, data validità, percorso, servizi e quantità;
4. carica codici biglietto;
5. crea array ordine arricchito;
6. chiama `creaPdfOrdine()`;
7. invia header:
   - `Content-Type: application/pdf`;
   - `Content-Disposition: attachment`;
   - `Content-Length`;
   - cache private/no-store;
8. stampa il PDF e termina.

#### Funzione `rispondiErrorePdf(string $messaggio, int $codiceHttp = 400): void`

Invia errore testuale e codice HTTP quando il PDF non può essere generato.

---

## 21. PDF ordine: `ordine_pdf.php`

Questo file genera PDF manualmente.

### 21.1 Funzioni di utilità

#### `pdfNorm(string $text): string`

Normalizza il testo per PDF, sostituendo caratteri non compatibili con versioni semplificate.

#### `pdfEscape(string $text): string`

Esegue escaping di caratteri speciali per stringhe PDF.

#### `pdfColor(string $hex): string`

Converte un colore esadecimale in componenti RGB normalizzate per comandi PDF.

#### `pdfWrapText(string $text, int $maxChars): array`

Divide un testo in righe con lunghezza massima approssimativa.

### 21.2 Classe `PdfOrdineBuilder`

Classe interna per costruire il PDF.

Metodi principali:

#### `__construct()`

Inizializza il PDF e aggiunge la prima pagina.

#### `raw(string $cmd)`

Aggiunge un comando grezzo al contenuto PDF corrente.

#### `addPage()`

Chiude la pagina corrente, aggiunge footer, salva la pagina e apre una nuova pagina.

#### `rect(float $x, float $y, float $w, float $h, string $fill = '', string $stroke = '', float $lineWidth = 1)`

Disegna un rettangolo.

#### `line(float $x1, float $y1, float $x2, float $y2, string $color = '#C9A84C', float $w = 1)`

Disegna una linea.

#### `text(float $x, float $y, string $text, int $size = 10, string $font = 'F1', string $color = '#2C2C2C')`

Scrive testo in una posizione.

#### `header()`

Disegna intestazione della pagina PDF con titolo museo e fascia decorativa.

#### `footer()`

Disegna footer con testo automatico e data generazione.

#### `ensure(float $heightNeeded)`

Controlla se c'è spazio sufficiente nella pagina. Se non c'è, crea una nuova pagina.

#### `title(string $text)`

Scrive un titolo di sezione.

#### `keyValue(string $label, string $value, int $maxChars = 54)`

Scrive una riga etichetta/valore.

#### `warning(string $title, string $message)`

Disegna un box avviso, usato per gli ordini non pagati.

#### `ticketRow(int $numero, string $codice, string $stato)`

Scrive una riga con numero progressivo, codice biglietto e stato.

#### `output(): string`

Costruisce la struttura PDF completa:

- catalogo;
- pagine;
- font;
- stream contenuti;
- xref;
- trailer;
- EOF.

Restituisce il contenuto binario/testuale del PDF come stringa.

### 21.3 `creaPdfOrdine(array $ordine, array $codiciBiglietti): string`

Funzione pubblica usata da `scarica_pdf.php`, `pagamento.php` e `cassa.php`.

Crea un PDF con:

- codice ordine;
- acquirente;
- email;
- percorso;
- data visita;
- servizi opzionali;
- metodo pagamento;
- stato pagamento;
- totale;
- numero biglietti;
- dati classe se prenotazione docente;
- avviso per ordine non pagato;
- elenco codici biglietto.

---

## 22. Cassa: `cassa.php`

`cassa.php` è riservato a utenti con ruolo cassiere o tester.

Usa:

```php
requireCassiere();
```

### 22.1 Funzioni

#### `cassaCaricaOrdine(PDO $pdo, int $idOrdine): ?array`

Carica un ordine per ID.

#### `cassaTrovaOrdine(PDO $pdo, string $codice): ?array`

Cerca un ordine in tre modi:

1. se il codice è numerico, lo tratta come ID ordine;
2. cerca in `Ordini.codice_recupero`;
3. cerca in `Biglietti.codice_univoco` e risale all'ordine.

#### `cassaCaricaBiglietti(PDO $pdo, int $idOrdine): array`

Carica tutti i biglietti di un ordine, con categoria, data, ora, esposizione e servizi.

#### `cassaCodiciBiglietti(array $biglietti): array`

Estrae i codici univoci dai biglietti.

#### `cassaInviaMailOrdine(array $ordine, array $biglietti): bool`

Genera PDF dell'ordine e invia email di conferma.

### 22.2 Azione `segna_pagato`

Quando il cassiere conferma il pagamento:

- controlla CSRF;
- carica ordine;
- impedisce pagamento se ordine annullato;
- aggiorna ordine a `Pagato`;
- imposta metodo `contanti`;
- aggiorna i biglietti `Non pagato` a `Valido`;
- invia email con PDF;
- mostra messaggio di successo o avviso se email non inviata.

### 22.3 Interfaccia

La pagina mostra:

- area cassiere;
- campo ricerca codice ordine o biglietto;
- dati ordine;
- stato pagamento;
- totale;
- pulsante per segnare come pagato;
- dettaglio biglietti.

---

## 23. Validazione biglietti: `valida_biglietti.php`

`valida_biglietti.php` è l'area operatori.

Il file controlla manualmente:

- se l'utente è operatore;
- oppure se è amministratore;
- oppure se è tester.

Se non autorizzato, mostra “Accesso negato”.

### 23.1 Funzione `cercaBigliettoPerCodice(PDO $pdo, string $codice): ?array`

Cerca un biglietto per codice univoco.

Carica anche:

- codice ordine;
- nome cliente;
- email cliente;
- categoria;
- data fascia;
- ora ingresso;
- titolo esposizione.

### 23.2 Ricerca ticket

Il form chiede esplicitamente il **numero ticket**, non il codice ordine.

Formato atteso:

```text
TKT-XXXXXXXXXX
```

### 23.3 Validazione ticket

Azione POST: `valida`.

Regole:

- se `Utilizzato` → errore “già usato”;
- se `Annullato` → non validabile;
- se `Non pagato` → non validabile;
- se `Valido` → aggiorna a `Utilizzato` e salva `data_utilizzo = NOW()`.

### 23.4 Informazioni mostrate

Per ogni biglietto trovato mostra:

- codice ticket;
- stato;
- eventuale data utilizzo;
- codice ordine;
- acquirente;
- email;
- tipo;
- categoria;
- percorso;
- data validità;
- ora;
- prezzo;
- pulsante `Valida` solo se valido.

---

## 24. Area amministratore: `admin.php`

`admin.php` è una pagina lunga e centrale.

Usa:

```php
requireAdmin();
```

Quindi è accessibile solo ad amministratore o tester.

### 24.1 Costanti e array iniziali

Il file definisce:

- stati esposizione: `Bozza`, `Pubblicata`, `Conclusa`, `Annullata`;
- tipi biglietto: `base`, `esposizione`;
- ruoli disponibili: `visitatore`, `operatore`, `cassiere`, `amministratore`, `tester`;
- etichette ruolo;
- lista emoji esposizioni;
- domande di sicurezza.

Emoji disponibili:

- `🏛️` Museo storico;
- `🏺` Civiltà antiche;
- `⚔️` Battaglie e imperi;
- `🏰` Medioevo;
- `🎨` Arte;
- `🖼️` Galleria;
- `🗿` Archeologia;
- `📜` Documenti;
- `🪙` Reperti;
- `🌍` Culture.

### 24.2 Funzioni admin

#### `normalizzaOraFascia(string $ora): string`

Accetta orario `HH:MM` o `HH:MM:SS`.

Se arriva `HH:MM`, aggiunge `:00`.

Se il formato non è valido, genera errore.

#### `caricaEsposizione(PDO $pdo, int $idEsposizione): array`

Carica esposizione con ID, titolo, data inizio e data fine.

Se non esiste, lancia errore.

#### `validaDatiFascia(array $esposizione, string $data, string $ora, int $capienza): string`

Valida i dati di una fascia oraria.

Controlla:

- data in formato `YYYY-MM-DD`;
- data compresa tra inizio e fine esposizione;
- capienza maggiore di zero;
- orario valido.

Restituisce l'orario normalizzato.

#### `contaBigliettiFascia(PDO $pdo, int $idFascia): int`

Conta i biglietti della fascia con stato diverso da `Annullato`.

Serve per impedire di abbassare la capienza sotto i biglietti già prenotati o eliminare fasce già usate.

#### `esposizioniSupportaEmoji(PDO $pdo): bool`

Controlla se la tabella `Esposizioni` ha colonna `emoji`.

#### `normalizzaEmojiEsposizione(string $emoji, array $emojiEsposizioni): string`

Accetta solo emoji presenti nella lista. Se non valida, usa `🏛️`.

### 24.3 Gestione esposizioni

Azioni:

- `create_esposizione`;
- `update_esposizione`.

Campi:

- titolo;
- descrizione;
- emoji;
- data inizio;
- data fine;
- stato.

Validazioni:

- titolo obbligatorio;
- date obbligatorie;
- stato valido;
- data fine non precedente a data inizio.

L'admin può creare esposizioni in qualsiasi stato.

### 24.4 Gestione fasce orarie

Azioni:

- `create_fascia`;
- `update_fascia`;
- `delete_fascia`.

Campi:

- esposizione;
- data;
- ora ingresso;
- capienza massima.

Regole:

- la fascia deve appartenere al periodo dell'esposizione;
- la capienza deve essere maggiore di zero;
- non possono esistere due fasce uguali per stessa esposizione, data e ora;
- non si può ridurre la capienza sotto i biglietti già prenotati;
- non si può eliminare una fascia con biglietti già prenotati.

La pagina mostra per ogni fascia:

- prenotati;
- disponibili;
- campi modificabili;
- pulsante elimina, disabilitato se ci sono prenotazioni.

### 24.5 Gestione categorie riduzione

Azioni:

- `create_categoria`;
- `update_categoria`;
- `delete_categoria`.

Campi:

- nome categoria;
- percentuale sconto;
- documento richiesto.

Regole:

- nome obbligatorio;
- sconto tra 0 e 100;
- nome univoco;
- una categoria non può essere eliminata se collegata a tariffe o biglietti.

La pagina mostra per ogni categoria il numero di tariffe e biglietti collegati.

### 24.6 Gestione tariffe

Azioni:

- `create_tariffa`;
- `update_tariffa`.

Campi:

- tipo biglietto;
- categoria;
- prezzo.

Regole:

- tipo deve essere `base` o `esposizione`;
- categoria valida;
- prezzo non negativo;
- una combinazione tipo/categoria deve essere unica.

### 24.7 Gestione servizi opzionali

Azioni:

- `create_servizio`;
- `update_servizio`.

Campi:

- nome;
- descrizione;
- prezzo.

Regole:

- nome obbligatorio;
- prezzo non negativo.

### 24.8 Gestione utenti e ruoli

Azioni:

- `update_user_role`;
- `force_user_password`;
- `force_user_security`;
- `delete_user`.

#### Cambio ruolo

L'admin può cambiare ruolo agli altri utenti.

Non può cambiare il proprio ruolo da questa pagina.

Questo evita che un amministratore si tolga accidentalmente i permessi.

#### Forzatura password

L'admin può impostare una nuova password a un utente.

Validazione:

- almeno 8 caratteri.

La nuova password viene salvata con bcrypt cost 12.

#### Forzatura domanda di sicurezza

L'admin può cambiare domanda e risposta di sicurezza di un utente.

La risposta viene normalizzata e salvata con hash bcrypt.

#### Eliminazione utente

L'admin può eliminare account di altri utenti.

Non può eliminare il proprio account amministratore da questa pagina: per quello deve usare la pagina account personale.

### 24.9 Ricerca interna

La pagina contiene JavaScript per filtrare:

- utenti per email;
- esposizioni per nome.

#### `filtraUtenti()`

Nasconde/mostra le card utente in base alla ricerca nella mail.

#### `filtraEsposizioni()`

Nasconde/mostra le card esposizione in base al titolo.

### 24.10 Menu amministrazione mobile

La pagina usa tre elementi:

- `adminMobileMenuButton`;
- `adminMobileMenuPanel`;
- `adminMobileBackdrop`.

La funzione JS `setAdminMenu(open)` apre/chiude pannello, backdrop e aggiorna `aria-expanded`.

### 24.11 Alert automatici

Gli alert di successo con `data-auto-dismiss="true"` scompaiono automaticamente dopo 3,5 secondi.

---

## 25. Sistema email: `app_mailer.php`

Il file `app_mailer.php` è il mailer centralizzato del sito.

È progettato per essere robusto in ambienti scolastici/hosting condivisi dove SMTP può essere lento o non disponibile.

### 25.1 Obiettivi

- usare PHPMailer + SMTP quando possibile;
- evitare attese infinite con timeout breve;
- usare `mail()` nativa se SMTP non funziona;
- salvare copia debug della mail quando l'invio fallisce;
- scrivere log dettagliati.

### 25.2 Funzioni generiche

#### `museoMailerBool($value): bool`

Converte valori in booleano usando `filter_var()`.

#### `museoMailDebugDir(): string`

Restituisce la cartella `mail_debug` e la crea se non esiste.

#### `museoMailLog(string $message): void`

Scrive una riga nel file:

```text
mail_debug/mail_error_log.txt
```

#### `museoMailSaveDebugCopy(string $to, string $subject, string $htmlBody): void`

Salva una copia HTML della mail in `mail_debug`.

Il nome file include data, oggetto e destinatario puliti.

#### `museoHeaderEncode(string $text): string`

Codifica header email in UTF-8 base64.

#### `museoPlainFromHtml(string $htmlBody): string`

Converte HTML in testo semplice.

#### `museoFindPhpMailer(): ?string`

Cerca PHPMailer in:

- `PHPMailer/src`;
- `phpmailer/src`;
- `PHPMailer-master/src`;
- `vendor/autoload.php`.

Restituisce la base trovata oppure `composer`, oppure `null`.

#### `museoGetSmtpFromEmail(): string`

Determina l'indirizzo mittente SMTP.

Priorità:

1. `MAIL_FROM` se valido;
2. `SMTP_USERNAME` se valido;
3. `noreply@localhost`.

#### `museoGetNativeFromEmail(): string`

Determina il mittente per `mail()` nativa.

Se il sito è online, usa `noreply@host`, altrimenti il mittente SMTP.

### 25.3 Invio nativo

#### `museoSendMailNative(...)`

Invia email usando `mail()`.

Supporta:

- email HTML senza allegati;
- email multipart con allegati;
- header UTF-8;
- Reply-To;
- log esito.

Se un allegato non è leggibile, lo ignora e lo segnala nel log.

### 25.4 Scelta invio rapido

#### `museoPreferisciInvioRapidoSenzaAllegati(array $attachments): bool`

Per email senza allegati, preferisce `mail()` nativa prima dello SMTP, salvo configurazione diversa.

Questo evita rallentamenti durante registrazione o recupero password.

#### `museoSmtpTimeout(): int`

Restituisce timeout SMTP, minimo 2 e massimo 15 secondi. Default 4 secondi.

### 25.5 Invio principale

#### `museoSendMail(...)`

È la funzione centrale.

Passaggi:

1. valida destinatario;
2. determina mittente;
3. genera plain text se non fornito;
4. valuta SMTP e PHPMailer;
5. se invio rapido attivo, prova `mail()`;
6. se fallisce, prova PHPMailer SMTP;
7. se fallisce, prova `mail()`;
8. se tutto fallisce, salva copia debug.

### 25.6 Email applicative

#### `inviaEmailVerificaAccount(string $email, string $nome, string $codice)`

Invia codice per verificare account.

Contiene:

- saluto con nome;
- codice a 6 cifre;
- riferimento al Museo Storico Severi;
- link/sito calcolato con `SITE_URL`.

#### `inviaEmailCodiceRecuperoPassword(string $email, string $nome, string $codice)`

Invia codice di recupero password.

#### `inviaEmailRecuperoPassword(string $email, string $nome, string $codice)`

Funzione di compatibilità/alias per recupero password.

#### `inviaEmailConfermaOrdine(array $ordine, array $codici = [], string $pdfPath = '')`

Invia conferma ordine.

Può allegare PDF se viene passato un percorso valido.

Include informazioni come:

- codice ordine;
- totale;
- stato pagamento;
- codici biglietto;
- link per recuperare l'ordine.

---

## 26. Test email: `test_mail.php`

File temporaneo/diagnostico.

Uso:

```text
test_mail.php?email=latuaemail@example.com
```

Parametri:

- `email`, obbligatoria;
- `tipo`, facoltativa: `verifica` oppure default recupero.

Comportamento:

- se manca email valida, mostra istruzioni;
- genera codice casuale;
- invia email di verifica o recupero;
- mostra esito;
- mostra ultime righe del log email;
- avvisa di eliminare il file dal server al termine del test.

Per sicurezza, in produzione questo file andrebbe rimosso.

---

## 27. CSS e risorse statiche

### 27.1 `assets/css/home-critical.css`

CSS minimo e critico per la home.

Viene caricato inline da `header.php` solo su `index.php`.

Obiettivo:

- migliorare caricamento iniziale;
- ridurre risorse bloccanti;
- ottimizzare LCP/FCP della homepage.

### 27.2 `assets/css/style.css`

CSS principale del sito.

Contiene stili personalizzati per:

- palette colori;
- pulsanti;
- card;
- alert;
- menu;
- layout admin;
- layout account;
- responsive;
- print/PDF;
- componenti grafici ricorrenti.

### 27.3 `assets/css/tailwind-local.css`

Versione locale del CSS Tailwind usata dalle pagine interne.

Evita il caricamento da CDN JavaScript e rende il sito più stabile su hosting.

### 27.4 Cartella `img/`

Contiene versioni del logo:

- `logo-128.webp`;
- `logo-256.webp`;
- `logo-512.webp`;
- `logo-lcp.webp`;
- `logo.png`;
- `logoconscritte.png`;
- `logovechcio.png`.

Le versioni WebP sono usate per performance e responsive image.

---

## 28. Sicurezza già presente

Il progetto include diverse misure di sicurezza didatticamente corrette.

### 28.1 Password con hash

Le password non vengono salvate in chiaro.

Sono salvate con:

```php
password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])
```

La verifica avviene con `password_verify()`.

### 28.2 Risposte di sicurezza con hash

Anche la risposta di sicurezza viene salvata come hash bcrypt.

La risposta viene normalizzata prima dell'hash.

### 28.3 CSRF token

Molti form sensibili usano `csrfToken()` e `verifyCsrf()`.

Esempi:

- registrazione;
- login;
- verifica email;
- recupero password;
- account;
- admin;
- pagamento;
- recupera ordine;
- cassa;
- validazione biglietti;
- eliminazione account.

### 28.4 Prepared statement

La maggior parte delle query usa prepared statement PDO.

### 28.5 Pulizia output

La funzione `clean()` viene usata ampiamente per stampare dati in HTML.

### 28.6 Session hardening

La sessione usa:

- cookie HTTP only;
- strict mode;
- secure cookie online.

### 28.7 Separazione ruoli

L'accesso ad admin, cassa e validazione è controllato da funzioni ruolo.

### 28.8 Stati biglietto

La validazione non permette di usare biglietti:

- non pagati;
- annullati;
- già utilizzati.

---

## 29. Limiti e aspetti da migliorare

Il progetto è didattico. Alcuni aspetti andrebbero migliorati per produzione reale.

### 29.1 Pagamenti

Il pagamento è simulato.

Non usa gateway reali come:

- Stripe;
- Nexi;
- PayPal reale;
- Satispay;
- PagoPA.

### 29.2 Codici biglietto

I biglietti hanno codici testuali, ma non QR code.

Miglioria possibile:

- generazione QR code per ogni biglietto;
- scansione QR da smartphone operatore;
- validazione rapida da fotocamera.

### 29.3 Email e segreti

Le credenziali SMTP non dovrebbero restare nel codice.

Miglioria:

- usare variabili ambiente;
- usare file `.env` non versionato;
- ruotare password app se è stata caricata online o condivisa.

### 29.4 Logging amministrativo

Non è presente una tabella log per azioni admin.

Miglioria:

- log creazione/modifica esposizioni;
- log cambio ruoli;
- log validazioni biglietto;
- log pagamenti in cassa.

### 29.5 Eliminazione utenti

L'eliminazione account cancella il record utente. Grazie a `ON DELETE SET NULL` sugli ordini, gli ordini restano senza utente collegato.

Per produzione si potrebbe preferire:

- soft delete;
- anonimizzazione;
- mantenimento storico.

### 29.6 Privacy

Manca una pagina privacy/cookie policy.

### 29.7 Gestione annullamenti e rimborsi

Lo schema prevede stati `Annullato`, ma non c'è ancora un flusso completo per:

- annullare ordine;
- annullare singoli biglietti;
- rimborsare;
- registrare motivazione annullamento.

### 29.8 Accessibilità

Sono presenti diversi attributi `aria`, ma sarebbe utile una revisione completa WCAG:

- contrasto;
- focus visibile;
- navigazione tastiera;
- etichette form;
- messaggi errore collegati ai campi.

---

## 30. Installazione locale con XAMPP

### 30.1 Copiare la cartella

Copiare `museo/` in:

```text
C:\xampp\htdocs\
```

Percorso finale:

```text
C:\xampp\htdocs\museo\
```

### 30.2 Avviare servizi

Aprire XAMPP e avviare:

- Apache;
- MySQL.

### 30.3 Creare database

Aprire:

```text
http://localhost/phpmyadmin
```

Creare database:

```text
biglietteria_museo
```

Charset consigliato:

```text
utf8mb4_unicode_ci
```

### 30.4 Importare SQL

Importare:

```text
db_completo_tabelle.sql
```

Il file crea le tabelle ma non inserisce dati demo.

Dopo l'importazione bisogna creare almeno:

- categorie riduzione;
- tariffe;
- esposizioni;
- fasce orarie;
- servizi opzionali se desiderati;
- un utente amministratore.

### 30.5 Configurare `config.php`

In locale il file è già predisposto per:

```php
DB_HOST = localhost
DB_NAME = biglietteria_museo
DB_USER = root
DB_PASS = ''
```

### 30.6 Aprire il sito

```text
http://localhost/museo/index.php
```

---

## 31. Installazione online / Altervista

Il file `config.php` ha una configurazione online già distinta da localhost.

Prima della pubblicazione verificare:

- nome database;
- utente database;
- password database;
- cartella di pubblicazione;
- permessi cartella `mail_debug`;
- presenza di PHPMailer;
- configurazione SMTP;
- rimozione di `test_mail.php` dopo i test;
- disattivazione `display_errors` online quando il sito è stabile.

---

## 32. Creazione del primo amministratore

Poiché il file SQL non contiene dati, dopo aver registrato un utente è necessario promuoverlo ad amministratore via database.

Esempio:

```sql
UPDATE Utenti
SET ruolo = 'amministratore', email_verificata = 1
WHERE email = 'tua_email@example.com';
```

In alternativa si può creare direttamente un record con password hash generato da PHP, ma la via più semplice è registrarsi dal sito e poi aggiornare il ruolo.

---

## 33. Dati minimi per rendere il sito utilizzabile

Per poter prenotare un biglietto base servono almeno:

- una categoria riduzione;
- una tariffa `base` per quella categoria.

Per prenotare una esposizione servono almeno:

- una esposizione con stato `Pubblicata`;
- una fascia oraria collegata all'esposizione;
- una categoria riduzione;
- una tariffa `esposizione` per quella categoria.

Per prenotare una classe con docenti gratuiti servirebbe anche:

- categoria `Docente accompagnatore`;
- tariffa `base` a 0,00 per docente accompagnatore;
- tariffa `esposizione` a 0,00 per docente accompagnatore.

Anche se il codice può creare biglietti docente con prezzo 0, è meglio che la categoria esista per avere dati puliti.

---

## 34. Query SQL utili

### 34.1 Creare categoria Intero

```sql
INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto)
VALUES ('Intero', 0.00, NULL);
```

### 34.2 Creare categoria Studente

```sql
INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto)
VALUES ('Studente', 30.00, 'Tessera studente');
```

### 34.3 Creare categoria docente accompagnatore

```sql
INSERT INTO Categorie_Riduzione (nome, percentuale_sconto, documento_richiesto)
VALUES ('Docente accompagnatore', 100.00, 'Documento scolastico');
```

### 34.4 Creare tariffe base

```sql
INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'base', id_categoria, 8.00
FROM Categorie_Riduzione
WHERE nome = 'Intero';
```

### 34.5 Creare tariffe esposizione

```sql
INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'esposizione', id_categoria, 12.00
FROM Categorie_Riduzione
WHERE nome = 'Intero';
```

### 34.6 Creare tariffe gratuite per docenti accompagnatori

```sql
INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'base', id_categoria, 0.00
FROM Categorie_Riduzione
WHERE nome = 'Docente accompagnatore';

INSERT INTO Tariffe (tipo_biglietto, id_categoria, prezzo)
SELECT 'esposizione', id_categoria, 0.00
FROM Categorie_Riduzione
WHERE nome = 'Docente accompagnatore';
```

### 34.7 Rendere un utente amministratore

```sql
UPDATE Utenti
SET ruolo = 'amministratore', email_verificata = 1
WHERE email = 'tua_email@example.com';
```

### 34.8 Rendere un utente operatore

```sql
UPDATE Utenti
SET ruolo = 'operatore', email_verificata = 1
WHERE email = 'operatore@example.com';
```

### 34.9 Rendere un utente cassiere

```sql
UPDATE Utenti
SET ruolo = 'cassiere', email_verificata = 1
WHERE email = 'cassiere@example.com';
```

### 34.10 Inserire una esposizione pubblicata

```sql
INSERT INTO Esposizioni (titolo, descrizione, emoji, data_inizio, data_fine, stato)
VALUES (
  'Padova antica',
  'Percorso storico dedicato alla città antica e ai suoi reperti.',
  '🏛️',
  '2026-06-01',
  '2026-09-30',
  'Pubblicata'
);
```

### 34.11 Inserire fasce orarie

```sql
INSERT INTO Fasce_Orarie (id_esposizione, data, ora_ingresso, capienza_massima)
VALUES
(1, '2026-06-10', '09:30:00', 30),
(1, '2026-06-10', '11:30:00', 30),
(1, '2026-06-10', '15:00:00', 30);
```

---

## 35. Mappa delle pagine e permessi

| File | Accesso | Funzione |
|---|---|---|
| `index.php` | Pubblico | Homepage e vetrina esposizioni. |
| `chi_siamo.php` | Pubblico | Presentazione progetto. |
| `esposizioni.php` | Pubblico | Elenco esposizioni e ingresso museo. |
| `novita.php` | Pubblico | Novità e ultime esposizioni. |
| `info.php` | Pubblico | Orari, luogo, tariffe e servizi. |
| `prenota.php` | Pubblico | Prenotazione standard. |
| `prenota_docente.php` | Pubblico | Prenotazione classe/docente. |
| `pagamento.php` | Pubblico/utente | Pagamento simulato e creazione ordine. |
| `recupera_ordine.php` | Pubblico | Inserimento codice ordine. |
| `biglietti.php` | Pubblico con codice | Visualizza biglietti di un ordine. |
| `scarica_pdf.php` | Pubblico con codice | Scarica PDF ordine. |
| `registrazione.php` | Pubblico non loggato | Creazione account. |
| `verifica_email.php` | Pubblico non loggato | Verifica account. |
| `login.php` | Pubblico non loggato | Accesso. |
| `recupero_password.php` | Pubblico non loggato | Reset password. |
| `logout.php` | Utente loggato | Logout. |
| `account.php` | Utente loggato | Profilo, sicurezza, ultimi ordini. |
| `ordini.php` | Utente loggato | Storico ordini. |
| `ordine_dettaglio.php` | Utente loggato | Redirect sicuro verso biglietti. |
| `elimina_account.php` | Utente loggato | Eliminazione account personale. |
| `admin.php` | Admin/tester | Gestione completa sito. |
| `cassa.php` | Cassiere/tester | Saldo ordini non pagati. |
| `valida_biglietti.php` | Operatore/admin/tester | Validazione biglietti. |
| `test_mail.php` | Tecnico | Test invio email; da rimuovere online. |
| `404.php` | Pubblico | Pagina errore 404. |

---

## 36. Checklist test funzionale

### 36.1 Test area pubblica

- Aprire home.
- Verificare logo e menu.
- Aprire Chi siamo.
- Aprire Esposizioni.
- Verificare card solo museo.
- Verificare esposizioni pubblicate.
- Aprire Novità.
- Aprire Info & Tariffe.

### 36.2 Test registrazione

- Registrare nuovo utente.
- Verificare obbligatorietà campi.
- Verificare controllo email.
- Verificare password breve.
- Verificare conferma password.
- Verificare domanda sicurezza.
- Verificare arrivo email o presenza in `mail_debug`.
- Verificare codice email.
- Fare login.

### 36.3 Test recupero password

- Inserire email.
- Ricevere codice.
- Inserire risposta sbagliata.
- Inserire codice sbagliato.
- Inserire nuova password breve.
- Inserire password corretta.
- Verificare redirect al login.
- Accedere con nuova password.

### 36.4 Test prenotazione base

- Aprire Esposizioni.
- Scegliere “Visita solo il museo”.
- Compilare prenotazione.
- Scegliere carta.
- Inserire dati carta validi.
- Verificare ordine pagato.
- Visualizzare biglietti.
- Scaricare PDF.

### 36.5 Test prenotazione esposizione

- Creare esposizione pubblicata.
- Creare fascia oraria.
- Prenotare esposizione.
- Verificare posti disponibili.
- Completare pagamento.
- Controllare biglietti.

### 36.6 Test ordine non pagato

- Prenotare con contanti.
- Verificare ordine `Non pagato`.
- Verificare biglietti `Non pagato`.
- Tentare validazione: deve essere bloccata.
- Pagare da area ordini o cassa.
- Verificare biglietti `Valido`.

### 36.7 Test prenotazione classe

- Aprire `prenota_docente.php`.
- Inserire dati scuola.
- Inserire studenti e docenti.
- Selezionare servizi.
- Completare pagamento.
- Verificare dati scuola in biglietti.
- Verificare biglietti docente gratuiti.

### 36.8 Test admin

- Accedere come amministratore.
- Creare esposizione.
- Modificare esposizione.
- Cambiare emoji.
- Creare fascia.
- Modificare fascia.
- Provare a eliminarla senza prenotazioni.
- Creare categoria.
- Creare tariffa.
- Creare servizio.
- Cercare utente per email.
- Cambiare ruolo ad altro utente.
- Verificare impossibilità di cambiare il proprio ruolo.

### 36.9 Test operatore

- Accedere come operatore.
- Inserire codice ticket valido.
- Validare.
- Cercare lo stesso ticket.
- Verificare messaggio già usato.

### 36.10 Test cassiere

- Accedere come cassiere.
- Cercare ordine non pagato.
- Segnare pagato.
- Verificare biglietti validi.
- Verificare email/PDF se configurati.

---

## 37. Stato complessivo del progetto

Il progetto è già molto ricco e copre le funzionalità principali di una biglietteria museale didattica:

- sito pubblico;
- gestione esposizioni;
- gestione tariffe;
- gestione categorie;
- gestione servizi;
- prenotazione ordinaria;
- prenotazione classe;
- pagamento simulato;
- ordini non pagati;
- saldo successivo;
- PDF;
- email;
- account;
- verifica email;
- recupero password;
- ruoli;
- admin;
- cassa;
- validazione biglietti.

La struttura è adatta per essere presentata come progetto scolastico avanzato perché mostra il collegamento tra:

- database relazionale;
- interfaccia web;
- sessioni e ruoli;
- sicurezza base;
- processi reali di prenotazione;
- flussi amministrativi;
- produzione di documenti;
- invio email;
- responsive design.

---

## 38. Elenco sintetico funzioni PHP rilevate

### `auth.php`

- `generaCodice6()`
- `colonnaCodiceVerificaEmail(PDO $pdo)`
- `loginUtente(string $email, string $password)`
- `normalizzaRispostaSicurezza(string $risposta)`
- `registraUtente(...)`
- `logoutUtente()`
- `isLogged()`
- `ruoloCorrente()`
- `isTester()`
- `isAdmin()`
- `isOperatore()`
- `isCassiere()`
- `requireLogin(string $redirect = 'login.php')`
- `requireAdmin(string $redirect = 'index.php')`
- `requireCassiere(string $redirect = 'index.php')`
- `csrfToken()`
- `verifyCsrf(string $token)`
- `clean(string $val)`

### `db.php`

- `getDB()`

### `admin.php`

- `normalizzaOraFascia(string $ora)`
- `caricaEsposizione(PDO $pdo, int $idEsposizione)`
- `validaDatiFascia(array $esposizione, string $data, string $ora, int $capienza)`
- `contaBigliettiFascia(PDO $pdo, int $idFascia)`
- `esposizioniSupportaEmoji(PDO $pdo)`
- `normalizzaEmojiEsposizione(string $emoji, array $emojiEsposizioni)`

### `app_mailer.php`

- `museoMailerBool($value)`
- `museoMailDebugDir()`
- `museoMailLog(string $message)`
- `museoMailSaveDebugCopy(string $to, string $subject, string $htmlBody)`
- `museoHeaderEncode(string $text)`
- `museoPlainFromHtml(string $htmlBody)`
- `museoFindPhpMailer()`
- `museoGetSmtpFromEmail()`
- `museoGetNativeFromEmail()`
- `museoSendMailNative(...)`
- `museoPreferisciInvioRapidoSenzaAllegati(array $attachments)`
- `museoSmtpTimeout()`
- `museoSendMail(...)`
- `inviaEmailVerificaAccount(string $email, string $nome, string $codice)`
- `inviaEmailCodiceRecuperoPassword(string $email, string $nome, string $codice)`
- `inviaEmailRecuperoPassword(string $email, string $nome, string $codice)`
- `inviaEmailConfermaOrdine(array $ordine, array $codici = [], string $pdfPath = '')`

### `cassa.php`

- `cassaCaricaOrdine(PDO $pdo, int $idOrdine)`
- `cassaTrovaOrdine(PDO $pdo, string $codice)`
- `cassaCaricaBiglietti(PDO $pdo, int $idOrdine)`
- `cassaCodiciBiglietti(array $biglietti)`
- `cassaInviaMailOrdine(array $ordine, array $biglietti)`

### `pagamento.php`

- `generaCodiceOrdine(PDO $pdo)`
- `generaCodiceBiglietto(PDO $pdo)`
- `colonnaEsiste(PDO $pdo, string $tabella, string $colonna)`
- `idCategoriaDocente(PDO $pdo)`
- `normalizzaInputPagamento(array $input)`
- `preparaOrdine(PDO $pdo, array $dati)`
- `creaOrdineConBiglietti(PDO $pdo, array $datiOrdine, string $statoPagamento, string $statoBiglietto)`
- `creaPayloadPagamento(array $dati)`
- `leggiPayloadPagamento(string $payload)`
- `cartaLuhnValida(string $numero)`
- `scadenzaCartaValida(string $scadenza)`
- `validaPagamentoSimulato(string $metodo, array $input)`
- `creaFormPagamentoDaDati(array $dati, array $datiOrdine)`
- `caricaOrdineUtenteDaPagare(PDO $pdo, int $idOrdine)`
- `marcaOrdinePagato(PDO $pdo, int $idOrdine, string $metodo)`
- `codiciDaOrdine(array $ordine)`
- `inviaEmailOrdineDopoRisposta(array $ordine, array $codici)`

### `ordine_pdf.php`

- `pdfNorm(string $text)`
- `pdfEscape(string $text)`
- `pdfColor(string $hex)`
- `pdfWrapText(string $text, int $maxChars)`
- classe `PdfOrdineBuilder`
- `creaPdfOrdine(array $ordine, array $codiciBiglietti)`

### `recupero_password.php`

- `generaCodiceRecuperoPassword()`
- `caricaUtenteRecupero(PDO $pdo, string $email)`
- `generaInviaCodiceRecupero(PDO $pdo, array $utente)`

### `registrazione.php`

- `fieldClass(string $field, array $errors)`

### `scarica_pdf.php`

- `rispondiErrorePdf(string $messaggio, int $codiceHttp = 400)`

### `valida_biglietti.php`

- `cercaBigliettoPerCodice(PDO $pdo, string $codice)`

### `verifica_email.php`

- `caricaUtenteDaEmail(PDO $pdo, string $email)`

### `index.php`

- `indexEsposizioniSupportaEmoji(PDO $pdo)`

### `esposizioni.php`

- `esposizioniPaginaSupportaEmoji(PDO $pdo)`

### `novita.php`

- `colonnaEsposizioniEsiste(PDO $pdo, string $colonna)`
- `emojiEsposizioneNovita(array $esposizione)`
- `badgeStatoClass(string $stato)`

---

## 39. Nota finale

Questo README descrive la versione letta dallo ZIP `museo.zip` e considera come riferimento i file presenti nella cartella `museo/`, in particolare il database `db_completo_tabelle.sql`.

Il progetto è una buona base per una consegna didattica avanzata perché integra molte parti normalmente separate: frontend, backend, database, gestione utenti, ruoli, email, PDF, pagamenti simulati, gestione amministrativa e flussi operativi di cassa/validazione.
