# 🏛️ Museo Storico Severi — Sito Web PHP/MySQL

Progetto didattico realizzato in **PHP**, **MySQL/MariaDB**, **HTML**, **CSS** e **JavaScript** per la gestione di una biglietteria museale.

Il sito permette agli utenti di consultare le esposizioni, acquistare biglietti per una mostra o per il solo ingresso al museo, simulare un pagamento, recuperare un ordine tramite codice e, se amministratori, gestire esposizioni, tariffe e servizi opzionali.

---

## 📌 Funzionalità principali

### Area pubblica

- Visualizzazione homepage del museo.
- Pagina `esposizioni.php` con elenco delle esposizioni.
- Card dedicata per acquistare **solo l’ingresso al museo**, senza scegliere una mostra.
- Pulsante **Prenota** sulle esposizioni pubblicate.
- Pulsante **Vuoi recuperare il tuo ordine?** per recuperare i biglietti tramite codice.
- Pagina `info.php` con due card affiancate:
  - prezzi **base**, cioè ingresso solo museo;
  - prezzi **esposizione**, cioè biglietti collegati a una mostra.
- Pagina `novita.php` per contenuti informativi o aggiornamenti.

### Prenotazione biglietti

- La prenotazione può essere effettuata anche da utenti **non registrati**.
- Da `esposizioni.php` il pulsante **Prenota** porta a `prenota.php`.
- In `prenota.php` l’utente può:
  - scegliere la fascia oraria dell’esposizione;
  - indicare i dati del cliente;
  - scegliere la categoria tariffaria;
  - indicare la quantità di biglietti;
  - selezionare eventuali servizi opzionali.
- Il pulsante di conferma porta a `pagamento.php`.
- `pagamento.php` simula un pagamento fittizio e genera un codice ordine del tipo:

```text
ORD-XXXXXXXX
```

- Il codice ordine permette di recuperare successivamente i biglietti.

### Prenotazione per docenti e classi

È stata aggiunta la pagina:

```text
prenota_docente.php
```

Il link è presente dentro la pagina di prenotazione normale con il testo:

```text
Sei un docente e vuoi portare la tua classe?
```

La prenotazione docente è simile a quella ordinaria, ma consente di acquistare biglietti per una classe senza limite massimo di biglietti.

In più vengono richiesti dati specifici della scuola:

- nome scuola;
- codice meccanografico;
- indirizzo scuola;
- città;
- telefono scuola;
- classe/sezione;
- numero studenti;
- numero docenti accompagnatori;
- eventuali note.

I **docenti accompagnatori** pagano il biglietto **0 euro**.

Nel database è presente la categoria:

```text
Docente accompagnatore
```

con tariffa pari a `0.00` sia per il biglietto `base` sia per il biglietto `esposizione`.

### Pagamento simulato

La pagina:

```text
pagamento.php
```

non esegue un pagamento reale, ma simula la conferma dell’acquisto.

Dopo la conferma vengono creati:

- un record nella tabella `Ordini`;
- uno o più record nella tabella `Biglietti`;
- eventuali collegamenti ai servizi opzionali nella tabella `Biglietti_Servizi`;
- un codice recupero ordine.

### Recupero ordine

Il recupero dei biglietti avviene tramite:

```text
recupera_ordine.php
```

L’utente inserisce il codice ordine ricevuto dopo il pagamento simulato.

Se il codice è valido, viene indirizzato a:

```text
biglietti.php
```

In questa pagina può visualizzare:

- dati dell’ordine;
- codice ordine;
- dati cliente;
- dati scuola, se si tratta di una prenotazione docente;
- biglietti acquistati;
- servizi opzionali associati;
- importo totale.

---

## 🔐 Area amministratore

L’area amministratore è gestita dalla pagina:

```text
admin.php
```

L’accesso è consentito solo agli utenti loggati con ruolo:

```text
amministratore
```

Il controllo viene effettuato nel file:

```text
auth.php
```

tramite le funzioni:

```php
isAdmin()
requireAdmin()
```

All’inizio di `admin.php` è presente il controllo:

```php
requireAdmin();
```

Quindi un utente non autorizzato non può accedere alla pagina amministratore scrivendo direttamente l’indirizzo nel browser.

### Pulsante amministratore in homepage

Se l’utente loggato è amministratore, nella homepage `index.php` compare il pulsante:

```text
Vista amministratore
```

Il pulsante reindirizza a:

```text
admin.php
```

### Menu interno dell’admin

Nella parte iniziale di `admin.php` è presente un menu con collegamenti interni:

- **Esposizioni**
- **Servizi**
- **Tariffe**

Cliccando su una voce, la pagina scorre automaticamente alla sezione corrispondente.

### Gestione esposizioni

Dalla vista amministratore è possibile:

- creare nuove esposizioni;
- modificare esposizioni esistenti;
- modificare titolo;
- modificare descrizione;
- modificare data di inizio;
- modificare data di fine;
- modificare stato.

Gli stati disponibili sono:

```text
Bozza
Pubblicata
Conclusa
Annullata
```

Le esposizioni in stato `Bozza` sono visibili solo agli utenti amministratori.

Gli utenti normali e gli utenti non loggati non vedono le esposizioni in bozza.

### Gestione tariffe

Dalla sezione tariffe è possibile gestire i prezzi disponibili.

I biglietti possono essere di tipo:

```text
base
esposizione
```

Dove:

- `base` indica il solo ingresso al museo;
- `esposizione` indica un biglietto collegato a una mostra.

Ogni tariffa è collegata a una categoria di riduzione, per esempio:

- Intero;
- Studente;
- Senior;
- Bambino;
- Docente accompagnatore.

### Gestione servizi opzionali

Dalla sezione servizi è possibile creare e modificare servizi opzionali, per esempio:

- Audioguida;
- Visita guidata;
- Catalogo mostra.

Per ogni servizio si possono modificare:

- nome;
- descrizione;
- prezzo.

---

## 🗂️ Struttura dei file principali

```text
museo/
├── index.php
├── esposizioni.php
├── prenota.php
├── prenota_docente.php
├── pagamento.php
├── recupera_ordine.php
├── biglietti.php
├── admin.php
├── ordini.php
├── ordine_dettaglio.php
├── account.php
├── login.php
├── logout.php
├── registrazione.php
├── recupero_password.php
├── info.php
├── novita.php
├── config.php
├── db.php
├── auth.php
├── header.php
├── footer.php
├── biglietteria_museo.sql
├── aggiornamento_database.sql
├── aggiornamento_database_docenti.sql
├── database_completo_biglietteria_museo.sql
├── assets/
│   └── css/
│       └── style.css
└── img/
    ├── logo.png
    └── logoconscritte.png
```

---

## 🧱 Database

Il database si chiama:

```text
biglietteria_museo
```

### File SQL consigliato

Per installare il progetto da zero, usare il file completo:

```text
database_completo_biglietteria_museo.sql
```

Questo file contiene tutto:

- creazione del database;
- eliminazione delle tabelle precedenti;
- ricreazione completa delle tabelle;
- campi per pagamento simulato;
- codice recupero ordine;
- campi per prenotazione docente;
- categoria docente accompagnatore;
- tariffe docente a 0 euro;
- dati demo iniziali.

⚠️ Attenzione: importando questo file, le tabelle esistenti con lo stesso nome vengono eliminate e ricreate.

### Tabelle principali

Il database contiene le seguenti tabelle:

| Tabella | Descrizione |
|---|---|
| `Utenti` | Utenti registrati e ruoli |
| `Esposizioni` | Mostre ed esposizioni del museo |
| `Fasce_Orarie` | Date e orari prenotabili per le esposizioni |
| `Categorie_Riduzione` | Categorie tariffarie e sconti |
| `Tariffe` | Prezzi base ed esposizione |
| `Servizi_Opzionali` | Servizi acquistabili insieme al biglietto |
| `Ordini` | Ordini effettuati dagli utenti o dai visitatori |
| `Biglietti` | Biglietti generati dopo il pagamento simulato |
| `Biglietti_Servizi` | Collegamento tra biglietti e servizi opzionali |

### Aggiornamento da versioni precedenti

Se hai già un database precedente e non vuoi ricrearlo da zero, puoi eseguire gli aggiornamenti in questo ordine:

```text
aggiornamento_database.sql
aggiornamento_database_docenti.sql
```

Tuttavia, per evitare errori durante una consegna o una verifica, è consigliato ripartire dal file completo:

```text
database_completo_biglietteria_museo.sql
```

---

## ⚙️ Installazione con XAMPP

### 1. Copiare il progetto

Copia la cartella del progetto dentro:

```text
C:\xampp\htdocs\
```

Il percorso finale dovrebbe essere simile a:

```text
C:\xampp\htdocs\museo\
```

### 2. Avviare XAMPP

Avvia:

- Apache;
- MySQL.

### 3. Importare il database

Apri phpMyAdmin dal browser:

```text
http://localhost/phpmyadmin
```

Poi importa il file:

```text
database_completo_biglietteria_museo.sql
```

### 4. Controllare `config.php`

Il file `config.php` deve contenere dati coerenti con XAMPP:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'biglietteria_museo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/museo');
```

Se il progetto è in una cartella diversa, modifica `SITE_URL`.

Esempio:

```php
define('SITE_URL', 'http://localhost/nome-cartella');
```

### 5. Aprire il sito

Nel browser apri:

```text
http://localhost/museo
```

---

## 👤 Account demo

Tutti gli utenti demo hanno password:

```text
password
```

### Amministratore

```text
Email: anna.neri@email.com
Password: password
Ruolo: amministratore
```

### Visitatore

```text
Email: luca.rossi@email.com
Password: password
Ruolo: visitatore
```

### Operatore

```text
Email: marco.verdi@email.com
Password: password
Ruolo: operatore
```

---

## 🧪 Ordini demo

È possibile testare il recupero ordine con questi codici:

```text
ORD-DEMO0001
ORD-DEMO0002
```

Oppure è possibile effettuare una nuova prenotazione e usare il codice generato da `pagamento.php`.

---

## 🔄 Flusso utente consigliato per il test

### Prenotazione esposizione

1. Aprire `esposizioni.php`.
2. Scegliere un’esposizione pubblicata.
3. Premere **Prenota**.
4. Compilare `prenota.php`.
5. Confermare.
6. Simulare il pagamento in `pagamento.php`.
7. Copiare il codice ordine generato.
8. Recuperare l’ordine da `recupera_ordine.php`.

### Prenotazione solo museo

1. Aprire `esposizioni.php`.
2. Usare la card grande dedicata al solo ingresso museo.
3. Procedere con la prenotazione.
4. Simulare il pagamento.
5. Recuperare i biglietti tramite codice ordine.

### Prenotazione docente/classe

1. Aprire una pagina di prenotazione.
2. Cliccare su **Sei un docente e vuoi portare la tua classe?**.
3. Compilare i dati della scuola.
4. Inserire numero studenti.
5. Inserire numero docenti accompagnatori.
6. Confermare.
7. Simulare il pagamento.
8. Recuperare l’ordine tramite codice.

---

## 🛠️ Query utili

### Rendere amministratore un utente

```sql
UPDATE Utenti
SET ruolo = 'amministratore'
WHERE email = 'tua_email@email.com';
```

### Inserire una nuova fascia oraria per un’esposizione

```sql
INSERT INTO Fasce_Orarie
(id_esposizione, data, ora_ingresso, capienza_massima)
VALUES
(1, '2026-06-20', '10:00:00', 50),
(1, '2026-06-20', '15:00:00', 50);
```

### Controllare le esposizioni pubblicate

```sql
SELECT *
FROM Esposizioni
WHERE stato = 'Pubblicata';
```

### Controllare le tariffe disponibili

```sql
SELECT t.id_tariffa, t.tipo_biglietto, c.nome AS categoria, t.prezzo
FROM Tariffe t
JOIN Categorie_Riduzione c ON c.id_categoria = t.id_categoria
ORDER BY t.tipo_biglietto, c.nome;
```

### Controllare gli ordini docente

```sql
SELECT *
FROM Ordini
WHERE prenotazione_docente = 1;
```

---

## 🚨 Problemi comuni

### Non vedo il pulsante “Vista amministratore”

Controlla di aver effettuato il login con un utente che ha ruolo:

```text
amministratore
```

L’account demo amministratore è:

```text
anna.neri@email.com
password
```

### Non riesco ad accedere ad `admin.php`

La pagina è protetta da:

```php
requireAdmin();
```

Quindi devi essere loggato come amministratore.

### Non vedo una esposizione nella pagina pubblica

Controlla lo stato dell’esposizione.

Se è in stato:

```text
Bozza
```

viene visualizzata solo dagli amministratori.

Per renderla visibile agli utenti, impostala su:

```text
Pubblicata
```

### In prenotazione non compaiono orari disponibili

Controlla che esistano record nella tabella:

```text
Fasce_Orarie
```

per l’esposizione selezionata.

### Il sito non si collega al database

Controlla:

- che MySQL sia avviato;
- che il database `biglietteria_museo` esista;
- che `config.php` abbia i dati corretti;
- che `DB_USER` e `DB_PASS` siano corretti.

### I link portano a pagine sbagliate

Controlla il valore di:

```php
define('SITE_URL', 'http://localhost/museo');
```

nel file `config.php`.

---

## 🔒 Note di sicurezza

Questo progetto è pensato per uso didattico.

Il pagamento è simulato e non è collegato a circuiti reali.

Per un uso reale bisognerebbe aggiungere:

- validazione più robusta dei dati;
- protezione CSRF sui form;
- gestione reale dei pagamenti;
- invio email automatico dei biglietti;
- generazione PDF dei biglietti;
- QR code sui biglietti;
- pannello amministratore più completo;
- gestione completa delle fasce orarie da area admin;
- log delle operazioni amministrative.

---

## ✅ Stato finale del progetto

Il progetto attualmente include:

- biglietteria pubblica;
- prenotazione esposizioni;
- prenotazione solo museo;
- prenotazione docente/classe;
- pagamento simulato;
- generazione codice ordine;
- recupero biglietti;
- area amministratore;
- gestione esposizioni;
- gestione tariffe;
- gestione servizi opzionali;
- distinzione tra tariffe base ed esposizione;
- esposizioni in bozza visibili solo agli amministratori;
- database completo aggiornato.
