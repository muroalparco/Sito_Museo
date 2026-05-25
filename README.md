# Museo Storico Severi

<p align="center">
  <strong>Sito web didattico per la gestione completa di un museo digitale</strong><br>
  Prenotazioni, biglietteria, pagamenti simulati, QR code, portafoglio virtuale, rimborsi, dashboard, cassa, validazione, area admin, assistente virtuale, accessibilità, SEO, export CSV e percorso guidato.
</p>

<p align="center">
  <a href="https://museostoricoseveri.altervista.org/">Sito online</a> ·
  <a href="https://museostoricoseveri.altervista.org/features.php">Features</a> ·
  <a href="https://museostoricoseveri.altervista.org/esposizioni.php">Esposizioni</a> ·
  <a href="https://museostoricoseveri.altervista.org/mappa.php">Mappa e percorso</a> ·
  <a href="https://museostoricoseveri.altervista.org/prenota.php">Prenota</a>
</p>

---

## Indice

- [Descrizione del progetto](#descrizione-del-progetto)
- [Obiettivi didattici](#obiettivi-didattici)
- [Demo online](#demo-online)
- [Tecnologie utilizzate](#tecnologie-utilizzate)
- [Funzionalità principali](#funzionalità-principali)
- [Ruoli utente](#ruoli-utente)
- [Flussi principali del sito](#flussi-principali-del-sito)
- [Struttura delle pagine](#struttura-delle-pagine)
- [Database](#database)
- [Installazione in locale](#installazione-in-locale)
- [Installazione su Altervista](#installazione-su-altervista)
- [Configurazione email](#configurazione-email)
- [QR code e validazione biglietti](#qr-code-e-validazione-biglietti)
- [Pagamenti simulati e portafoglio virtuale](#pagamenti-simulati-e-portafoglio-virtuale)
- [Rimborsi](#rimborsi)
- [Dashboard utente](#dashboard-utente)
- [Dashboard admin](#dashboard-admin)
- [Assistente virtuale](#assistente-virtuale)
- [Mappa e percorso guidato](#mappa-e-percorso-guidato)
- [Features del sito](#features-del-sito)
- [Accessibilità](#accessibilità)
- [SEO e indicizzazione](#seo-e-indicizzazione)
- [Performance e ottimizzazione](#performance-e-ottimizzazione)
- [Sicurezza](#sicurezza)
- [Export CSV](#export-csv)
- [PDF, ricevute e stampa](#pdf-ricevute-e-stampa)
- [Pagina 404 personalizzata](#pagina-404-personalizzata)
- [Struttura del progetto](#struttura-del-progetto)
- [File principali](#file-principali)
- [Test consigliati](#test-consigliati)
- [Possibili sviluppi futuri](#possibili-sviluppi-futuri)
- [Licenza e note](#licenza-e-note)

---

## Descrizione del progetto

**Museo Storico Severi** è un progetto web didattico sviluppato in **PHP e MySQL** per simulare il funzionamento di un vero gestionale museale.

Il sito permette al visitatore di consultare esposizioni, prenotare biglietti, scegliere servizi opzionali, simulare un pagamento, ricevere biglietti con QR code, scaricare ricevute e gestire ordini dalla propria area personale.

Il progetto comprende anche funzionalità di back office: area amministratore, cassa, validazione biglietti, gestione rimborsi, export CSV, statistiche, controllo qualità dati, assistente virtuale e percorso guidato del museo.

L’obiettivo non è solo creare una pagina vetrina, ma costruire un sistema completo, realistico e presentabile, con attenzione a:

- esperienza utente;
- gestione dati;
- sicurezza;
- accessibilità;
- responsive design;
- SEO;
- performance;
- modularità del codice;
- simulazione di processi reali.

---

## Obiettivi didattici

Il progetto nasce come applicazione completa per esercitare competenze di informatica applicata al web.

Permette di lavorare su:

- progettazione di un database relazionale;
- gestione di utenti e ruoli;
- autenticazione e sessioni;
- sicurezza dei form;
- operazioni CRUD;
- query SQL con join e aggregazioni;
- generazione di PDF e ricevute;
- invio email;
- validazione tramite QR code;
- responsive design;
- accessibilità;
- SEO tecnica;
- organizzazione di un progetto PHP reale;
- documentazione tecnica tramite README;
- esportazione dati in formato CSV;
- simulazione di pagamenti, rimborsi e processi amministrativi.

---

## Demo online

Il progetto è pensato per funzionare online su Altervista:

- **Home:** [https://museostoricoseveri.altervista.org/](https://museostoricoseveri.altervista.org/)
- **Esposizioni:** [https://museostoricoseveri.altervista.org/esposizioni.php](https://museostoricoseveri.altervista.org/esposizioni.php)
- **Prenotazione standard:** [https://museostoricoseveri.altervista.org/prenota.php](https://museostoricoseveri.altervista.org/prenota.php)
- **Prenotazione docente/classe:** [https://museostoricoseveri.altervista.org/prenota_docente.php](https://museostoricoseveri.altervista.org/prenota_docente.php)
- **Mappa e percorso guidato:** [https://museostoricoseveri.altervista.org/mappa.php](https://museostoricoseveri.altervista.org/mappa.php)
- **Features:** [https://museostoricoseveri.altervista.org/features.php](https://museostoricoseveri.altervista.org/features.php)
- **Recupero ordine:** [https://museostoricoseveri.altervista.org/recupera_ordine.php](https://museostoricoseveri.altervista.org/recupera_ordine.php)
- **Area riservata:** [https://museostoricoseveri.altervista.org/login.php](https://museostoricoseveri.altervista.org/login.php)

---

## Tecnologie utilizzate

### Backend

- **PHP**
- **PDO** per connessione sicura al database
- **MySQL / MariaDB**
- Sessioni PHP
- CSRF token nei form sensibili
- Password hash con `password_hash()` e `password_verify()`
- PHPMailer / mail fallback per invio email

### Frontend

- HTML5
- CSS3
- JavaScript vanilla
- Layout responsive
- Tailwind locale / classi utility già integrate
- CSS personalizzato
- Icone tramite emoji e SVG inline
- Nessuna API esterna necessaria per l’assistente virtuale

### Funzioni aggiuntive

- QR code per biglietti
- Lettura QR da fotocamera per operatori e cassieri
- PDF biglietti
- PDF ricevuta ordine
- Export CSV
- Assistente virtuale senza API
- Accessibilità lato utente
- Sitemap e canonical
- Header di sicurezza
- Pagine responsive per desktop, tablet e mobile

---

## Funzionalità principali

### Area pubblica

- Home moderna e responsive
- Presentazione del museo
- Statistiche dinamiche
- Esposizioni attive e concluse
- Filtri per esposizioni
- Pagina novità
- Pagina informazioni e tariffe
- Pagina mappa e percorso guidato
- Pagina features del sito
- Assistente virtuale sempre disponibile
- Pagina 404 personalizzata

### Prenotazione visitatore

- Scelta esposizione
- Scelta data e fascia oraria
- Controllo capienza
- Selezione categoria biglietto
- Selezione quantità
- Servizi opzionali
- Riepilogo prenotazione
- Scelta metodo pagamento
- Prenotazione anche senza account
- Recupero ordine tramite codice

### Prenotazione docente/classe

- Dati docente referente
- Dati scuola
- Classe
- Numero studenti
- Numero accompagnatori
- Scelta esposizione
- Scelta data e fascia oraria
- Servizi opzionali
- Riepilogo visita didattica
- Flusso grafico dedicato alla visita scolastica

### Pagamenti simulati

- Carta di credito simulata
- PayPal simulato
- Pagamento in contanti presso la cassa
- Pagamento con portafoglio virtuale
- Ricarica portafoglio tramite flusso dedicato
- Stato pagamento visibile negli ordini

### Biglietti

- Codice univoco biglietto
- Stato biglietto
- QR code
- Biglietto digitale migliorato
- Stampa biglietti
- Download PDF
- Stato visivo: valido, utilizzato, rimborsato, scaduto
- Blocco biglietti rimborsati

### Ricevute

- Ricevuta PDF ordine
- Dati ordine
- Metodo pagamento
- Importo totale
- Stato pagamento
- Riepilogo biglietti e servizi
- Layout più professionale

### Account utente

- Dashboard personale moderna
- Saldo portafoglio in evidenza
- Stato prossima visita
- Biglietti validi
- Ordini totali
- Ordini pagati
- Rimborsi in attesa
- Ultimi ordini
- Profilo personale
- Modifica dati utente
- Sicurezza e cambio password
- Portafoglio virtuale
- Ricarica saldo
- I miei ordini
- Logout
- Eliminazione account
- Empty state curati quando non ci sono dati

### Rimborsi

- Richiesta rimborso da parte dell’utente
- Controllo sui biglietti utilizzati
- Rimborsabilità solo se i biglietti non sono stati usati
- Gestione richiesta in admin
- Accettazione o rifiuto
- Email automatica all’utente
- Biglietto rimborsato non più utilizzabile
- Ordine conservato nello storico come rimborsato

### Cassa

- Ricerca ordine
- Gestione pagamenti in presenza
- Conferma pagamento in contanti
- Lettura QR code
- Validazione o controllo ticket
- Blocco dei biglietti rimborsati
- Interfaccia dedicata al ruolo cassiere

### Validazione biglietti

- Ricerca tramite codice biglietto
- Lettura QR da fotocamera
- Controllo stato biglietto
- Validazione biglietto
- Blocco ticket già usati
- Blocco ticket rimborsati
- Supporto operatori

### Admin

- Dashboard amministratore
- Menu sticky
- Gestione esposizioni
- Gestione fasce orarie
- Gestione categorie riduzione
- Gestione tariffe
- Gestione servizi opzionali
- Gestione utenti
- Gestione ordini
- Gestione rimborsi
- Statistiche avanzate
- Ultime attività
- Controllo qualità dati e contenuti
- Manutenzione contenuti
- Export CSV
- Filtri migliorati
- Card con bordi tondi e layout coerente

### Features

- Pagina dedicata a tutte le funzioni del sito
- Descrizione visiva delle aree principali
- Funzionalità organizzate per ambito
- Link utili
- Layout responsive
- Testi personalizzati e non generici

### Assistente virtuale

- Chat in basso a destra
- Nessuna API esterna
- Nessun costo
- Domande rapide contestuali
- Risposte diverse in base alla pagina
- Modalità guidata passo passo
- Problemi frequenti
- Link rapidi
- Riconoscimento di parole chiave e sinonimi
- Supporto a visitatori, admin, cassieri e operatori
- Memoria locale nel browser
- Pulsante di reset conversazione
- Accessibile da tastiera

---

## Ruoli utente

Il progetto prevede più ruoli, ognuno con permessi differenti.

### Visitatore

Può:

- registrarsi;
- accedere;
- prenotare biglietti;
- visualizzare ordini;
- scaricare biglietti;
- scaricare ricevute;
- richiedere rimborsi;
- usare il portafoglio virtuale;
- modificare i propri dati;
- cambiare password.

### Operatore

Può:

- accedere all’area di validazione;
- cercare biglietti;
- leggere QR code;
- validare biglietti;
- controllare lo stato dei ticket.

### Cassiere

Può:

- accedere alla cassa;
- cercare ordini;
- confermare pagamenti in contanti;
- leggere QR code;
- controllare biglietti e ordini;
- gestire situazioni di pagamento in presenza.

### Amministratore

Può:

- gestire utenti;
- gestire esposizioni;
- gestire fasce orarie;
- gestire categorie;
- gestire tariffe;
- gestire servizi;
- gestire rimborsi;
- visualizzare statistiche;
- esportare CSV;
- controllare qualità dati e contenuti.

### Tester

È un ruolo dimostrativo avanzato che può accedere a più aree per provare il progetto in tutte le sue parti.

---

## Flussi principali del sito

### Flusso prenotazione visitatore

1. L’utente consulta le esposizioni.
2. Sceglie una mostra.
3. Seleziona data e fascia oraria.
4. Sceglie categoria e quantità dei biglietti.
5. Aggiunge eventuali servizi opzionali.
6. Visualizza il riepilogo.
7. Sceglie il metodo di pagamento.
8. Completa il pagamento simulato o seleziona pagamento in cassa.
9. Riceve ordine e biglietti.
10. Mostra il QR code all’ingresso.

### Flusso prenotazione docente

1. Il docente accede alla prenotazione per classi.
2. Inserisce dati referente.
3. Inserisce dati scuola e classe.
4. Indica studenti e accompagnatori.
5. Sceglie mostra, data e orario.
6. Aggiunge eventuali servizi.
7. Conferma la prenotazione.
8. Ottiene riepilogo, ordine e biglietti.

### Flusso rimborso

1. L’utente entra nell’area personale.
2. Apre i propri ordini.
3. Richiede il rimborso se possibile.
4. L’admin valuta la richiesta.
5. L’utente riceve email di esito.
6. I biglietti rimborsati restano nello storico ma non sono più utilizzabili.

### Flusso cassa

1. Il cassiere cerca ordine o biglietto.
2. Controlla stato pagamento.
3. Conferma pagamento in presenza.
4. Può leggere QR code.
5. Verifica che il biglietto non sia rimborsato o già utilizzato.

### Flusso validazione

1. Operatore o cassiere legge il QR code.
2. Il sistema recupera il biglietto.
3. Controlla stato, pagamento e rimborso.
4. Se valido, il ticket può essere marcato come utilizzato.

---

## Struttura delle pagine

### Home

La home presenta:

- identità del museo;
- call to action;
- statistiche dinamiche;
- esposizioni in evidenza;
- collegamenti alle aree principali;
- assistente virtuale;
- responsive design ottimizzato.

### Esposizioni

Permette di:

- consultare le mostre;
- filtrare per stato;
- vedere date, durata, stato e dettagli;
- accedere alla prenotazione.

Da mobile i filtri sono compatti e restano sulla stessa riga quando possibile.

### Prenota

Pagina per la prenotazione standard con:

- hero dedicata;
- form organizzato in card;
- fasce orarie chiare;
- categorie e quantità;
- servizi opzionali;
- metodi pagamento;
- riepilogo finale.

### Prenota docente

Pagina per la prenotazione didattica con:

- dati referente;
- dati scuola;
- partecipanti;
- visita;
- pagamento;
- riepilogo.

### Account

Area personale con dashboard moderna e card azione al posto della vecchia sidebar.

### Admin

Area di gestione con:

- menu sticky;
- dashboard;
- qualità dati;
- statistiche;
- gestione contenuti;
- export CSV.

### Mappa e percorso guidato

Pagina che racconta le sale del museo come tappe di visita, con domande guida e percorsi consigliati.

### Features

Pagina vetrina che documenta tutte le funzioni implementate.

---

## Database

Il database gestisce le entità principali del museo.

### Tabelle principali

- `Utenti`
- `Esposizioni`
- `Fasce_Orarie`
- `Categorie_Riduzione`
- `Tariffe`
- `Servizi_Opzionali`
- `Ordini`
- `Biglietti`
- `Biglietti_Servizi`

### Utenti

Contiene informazioni su:

- nome;
- cognome;
- email;
- password hash;
- ruolo;
- verifica email;
- saldo portafoglio;
- data registrazione;
- recupero password.

### Esposizioni

Contiene:

- titolo;
- descrizione;
- data inizio;
- data fine;
- stato;
- informazioni utili alla prenotazione.

### Fasce orarie

Contiene:

- esposizione associata;
- data;
- ora ingresso;
- capienza massima.

### Ordini

Contiene:

- utente associato;
- codice ordine;
- importo totale;
- metodo pagamento;
- stato pagamento;
- stato rimborso;
- richiesta rimborso;
- data acquisto.

### Biglietti

Contiene:

- codice univoco;
- ordine associato;
- tipo;
- data validità;
- fascia;
- categoria;
- prezzo;
- sconto;
- stato.

### Biglietti servizi

Tabella ponte tra biglietti e servizi opzionali.

---

## Installazione in locale

### Requisiti

- PHP 8.x consigliato
- MySQL / MariaDB
- Apache
- XAMPP, MAMP, Laragon o ambiente equivalente

### Passaggi

1. Copiare il progetto nella cartella web locale.

   Esempio con XAMPP:

   ```text
   C:/xampp/htdocs/museo
   ```

2. Creare un database MySQL.

   Esempio:

   ```sql
   CREATE DATABASE museo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Importare il file SQL completo.

4. Configurare `config.php` con i dati locali.

5. Avviare Apache e MySQL.

6. Aprire:

   ```text
   http://localhost/museo/
   ```

---

## Installazione su Altervista

Il progetto è stato adattato per funzionare direttamente nella **root** del sito, senza cartella `/museo`.

La struttura online deve essere:

```text
/index.php
/header.php
/footer.php
/config.php
/db.php
/assets/
/img/
/PHPMailer/
...
```

Non deve essere:

```text
/museo/index.php
```

### Passaggi

1. Caricare tutti i file nella root di Altervista.
2. Importare il database dal pannello MySQL.
3. Verificare `config.php`.
4. Controllare che la home sia raggiungibile da:

   ```text
   https://museostoricoseveri.altervista.org/
   ```

5. Verificare che `robots.txt` e `sitemap.xml` siano in root.

---

## Configurazione email

Il progetto include invio email per:

- verifica account;
- recupero password;
- conferma ordine;
- biglietti e riepilogo;
- esito rimborso;
- comunicazioni collegate a pagamenti e ordini.

Le email sono state migliorate con:

- template HTML più elegante;
- intestazione coerente;
- colori del museo;
- box riepilogativi;
- codifica più stabile per evitare parole spezzate;
- fallback testuale.

Il file principale è:

```text
app_mailer.php
```

---

## QR code e validazione biglietti

Ogni biglietto ha un codice univoco e un QR code.

Il QR viene usato per:

- visualizzazione del biglietto;
- PDF;
- controllo in cassa;
- validazione operatore;
- verifica dello stato.

La lettura QR funziona sia nell’area:

```text
valida_biglietti.php
```

sia in:

```text
cassa.php
```

È stata adottata una soluzione compatibile anche con browser che non supportano nativamente `BarcodeDetector`, utile soprattutto su iPhone e Safari.

---

## Pagamenti simulati e portafoglio virtuale

Il sistema non effettua pagamenti reali. Simula diversi scenari:

- carta di credito;
- PayPal;
- contanti in cassa;
- saldo portafoglio virtuale.

Il portafoglio virtuale consente di:

- vedere saldo disponibile;
- ricaricare il saldo;
- pagare biglietti e servizi;
- usare un flusso dedicato di pagamento simulato per la ricarica.

---

## Rimborsi

Il sistema rimborsi prevede:

- richiesta rimborso da area personale;
- controllo biglietti non utilizzati;
- visualizzazione richiesta in admin;
- accettazione o rifiuto;
- email automatica all’utente;
- biglietti rimborsati non più utilizzabili;
- storico ordine mantenuto.

Un biglietto rimborsato non può essere:

- validato;
- pagato nuovamente;
- usato in cassa;
- considerato valido.

---

## Dashboard utente

La dashboard utente è stata ridisegnata con:

- hero personale;
- saldo portafoglio;
- card riepilogative;
- stato prossima visita;
- ultimi ordini;
- notifiche interne;
- card azione;
- sezioni profilo, portafoglio, sicurezza e ordini più moderne;
- empty state curati.

Le vecchie sezioni laterali sono state sostituite da una dashboard più visiva.

---

## Dashboard admin

L’area amministratore include:

- menu sticky;
- dashboard;
- statistiche avanzate;
- grafici con tonalità blu;
- qualità dati e contenuti;
- manutenzione contenuti;
- export CSV;
- gestione esposizioni;
- gestione tariffe;
- gestione servizi;
- gestione utenti;
- gestione rimborsi.

### Qualità dati e contenuti

La sezione segnala automaticamente possibili problemi, come:

- ordini in attesa;
- rimborsi da valutare;
- esposizioni senza fasce;
- contenuti incompleti;
- elementi che potrebbero bloccare prenotazioni o vendite.

---

## Assistente virtuale

L’assistente virtuale è una chat integrata nel sito.

### Caratteristiche

- Non usa API esterne.
- Non richiede chiavi.
- Non ha costi.
- Risponde tramite regole, parole chiave e contesto pagina.
- Mostra suggerimenti dinamici.
- Propone link diretti.
- Guida l’utente passo passo.
- Supporta problemi frequenti.
- È accessibile da tastiera.
- Salva temporaneamente la conversazione nel browser.

### Argomenti supportati

- prenotazioni;
- pagamenti;
- portafoglio;
- biglietti;
- QR code;
- rimborsi;
- cassa;
- validazione;
- admin;
- mappa;
- percorso guidato;
- features;
- export CSV;
- account;
- problemi frequenti.

---

## Mappa e percorso guidato

La pagina `mappa.php` non è un semplice elenco di sale, ma un percorso guidato.

Include:

- sale del museo;
- tappa attiva;
- descrizione narrativa;
- focus didattico;
- domanda guida;
- percorso consigliato;
- collegamento alla prenotazione;
- collegamento alla visita per classi.

Le sale sono presentate come tappe:

1. Preistoria
2. Antico Egitto
3. Impero Romano
4. Medioevo Europeo
5. Rinascimento Italiano
6. Arte Contemporanea

---

## Features del sito

La pagina `features.php` documenta le funzioni principali del progetto in modo visivo.

Include:

- prenotazione completa;
- visite per classi;
- pagamenti simulati;
- dashboard personale;
- biglietti e QR code;
- admin;
- cassa e operatori;
- email automatiche;
- assistente virtuale;
- mappa e percorso;
- accessibilità e SEO;
- export CSV;
- manutenzione dati;
- pagina 404;
- stampa;
- toast;
- empty state;
- sicurezza;
- documentazione.

---

## Accessibilità

Sono stati curati:

- testi leggibili;
- contrasti principali;
- navigazione da tastiera;
- aria-label nei controlli;
- label per i form admin;
- assistente accessibile;
- riduzione animazioni;
- testo aumentabile;
- layout mobile e tablet;
- controlli WAVE/Lighthouse.

La modalità alto contrasto è stata rimossa perché interferiva con la leggibilità generale.

---

## SEO e indicizzazione

Il sito include:

- title ottimizzati;
- meta description;
- canonical;
- sitemap XML;
- robots.txt;
- URL principali indicizzabili;
- pagina features;
- contenuti descrittivi;
- struttura semantica migliorata.

File principali:

```text
robots.txt
sitemap.xml
header.php
```

Per indicizzare il sito è consigliato usare Google Search Console e inviare:

```text
https://museostoricoseveri.altervista.org/sitemap.xml
```

---

## Performance e ottimizzazione

Sono state eseguite ottimizzazioni su:

- immagini WebP;
- logo alleggerito;
- CSS non necessario ridotto;
- caricamento CSS assistente/accessibilità non bloccante;
- layout shift ridotti;
- preload rimosso dove non più utile;
- mobile layout corretto;
- riduzione DOM dove possibile;
- cache statica;
- rimozione elementi pesanti dalla home.

---

## Sicurezza

Il progetto include varie misure di sicurezza:

- password hashate;
- query PDO preparate;
- CSRF token;
- sessioni protette;
- ruoli e permessi;
- controllo accesso pagine riservate;
- cookie con `HttpOnly`, `Secure`, `SameSite` dove supportato;
- CSP;
- Referrer Policy;
- X-Content-Type-Options;
- HSTS compatibile con hosting;
- frame-ancestors tramite CSP;
- blocco accessi non autorizzati.

Su Altervista alcune impostazioni, come il redirect HTTPS completo, possono dipendere anche dal pannello hosting.

---

## Export CSV

L’admin può esportare dati in formato CSV.

Export disponibili:

- ordini;
- biglietti;
- rimborsi;
- utenti;
- esposizioni;
- tariffe;
- servizi.

File principale:

```text
export_csv.php
```

L’export è riservato agli utenti autorizzati.

---

## PDF, ricevute e stampa

Il progetto prevede:

- stampa biglietti;
- PDF ordine;
- PDF biglietti;
- ricevuta PDF;
- layout di stampa migliorato;
- riepilogo ordine;
- QR code nei biglietti;
- stato biglietto visibile.

File principali:

```text
ordine_pdf.php
scarica_pdf.php
ricevuta_pdf.php
biglietti.php
ordine_dettaglio.php
```

---

## Pagina 404 personalizzata

La pagina 404 è stata personalizzata con:

- hero coerente con il sito;
- messaggio “Ti sei perso nel museo?”;
- link rapidi;
- card per tornare alle sezioni utili;
- collegamento all’assistente;
- design responsive.

File:

```text
404.php
```

---

## Struttura del progetto

La struttura principale è pensata per la root del sito Altervista.

```text
/
├── index.php
├── header.php
├── footer.php
├── config.php
├── db.php
├── auth.php
├── app_mailer.php
├── login.php
├── registrazione.php
├── account.php
├── admin.php
├── cassa.php
├── valida_biglietti.php
├── esposizioni.php
├── prenota.php
├── prenota_docente.php
├── pagamento.php
├── biglietti.php
├── ordine_dettaglio.php
├── ordine_pdf.php
├── ricevuta_pdf.php
├── recupera_ordine.php
├── recupero_password.php
├── verifica_email.php
├── mappa.php
├── features.php
├── export_csv.php
├── 404.php
├── robots.txt
├── sitemap.xml
├── assets/
│   ├── css/
│   └── js/
├── img/
├── PHPMailer/
└── mail_debug/
```

---

## File principali

### `config.php`

Contiene configurazione generale, costanti del sito e impostazioni di sicurezza.

### `db.php`

Gestisce la connessione al database tramite PDO.

### `auth.php`

Gestisce autenticazione, ruoli, sessioni e funzioni di controllo accesso.

### `header.php`

Contiene head HTML, meta tag, canonical, CSS, navbar e logiche comuni.

### `footer.php`

Contiene footer, link, inclusione assistente virtuale, JS comuni e widget accessibilità.

### `index.php`

Home del sito.

### `esposizioni.php`

Elenco esposizioni con filtri e card responsive.

### `prenota.php`

Prenotazione standard.

### `prenota_docente.php`

Prenotazione per classi e gruppi scolastici.

### `pagamento.php`

Pagamento simulato per ordini e ricariche portafoglio.

### `account.php`

Dashboard personale utente.

### `admin.php`

Pannello amministratore.

### `cassa.php`

Area cassiere.

### `valida_biglietti.php`

Area operatore per validare ticket.

### `assistente_ai.php`

Markup dell’assistente virtuale.

### `assets/js/assistente_ai.js`

Logica dell’assistente virtuale.

### `assets/css/fixes.css`

Regole CSS di rifinitura, responsive, animazioni, stampa e componenti aggiuntivi.

---

## Test consigliati

Prima della consegna o pubblicazione è consigliato testare:

### Funzionalità

- registrazione;
- verifica email;
- login;
- recupero password;
- prenotazione standard;
- prenotazione docente;
- pagamento carta;
- pagamento PayPal;
- pagamento contanti;
- pagamento con saldo;
- ricarica portafoglio;
- generazione biglietti;
- PDF biglietti;
- ricevuta PDF;
- recupero ordine;
- rimborso;
- cassa;
- validazione QR;
- admin;
- export CSV.

### Accessibilità

- WAVE;
- Lighthouse Accessibility;
- navigazione da tastiera;
- mobile Safari;
- Chrome Android;
- iPhone stretto;
- iPad/tablet.

### Performance

- Lighthouse Performance;
- immagini;
- layout shift;
- cache;
- DOM size;
- CSS bloccante.

### SEO

- title;
- description;
- canonical;
- sitemap;
- robots;
- Google Search Console.

### Sicurezza

- sessioni;
- redirect;
- cookie;
- CSP;
- header sicurezza;
- accessi non autorizzati.

---

## Possibili sviluppi futuri

Il progetto è già molto completo, ma potrebbe essere esteso con:

- integrazione reale Apple Wallet tramite `.pkpass`;
- notifiche email programmate prima della visita;
- recensioni visitatori;
- preferiti utente;
- log attività con tabella dedicata;
- gestione allegati/materiali didattici per docenti;
- attestato PDF per classi;
- quiz storico interattivo;
- sistema badge utente persistente;
- API REST interne;
- pannello impostazioni generali del museo;
- calendario visite più avanzato;
- backup database da admin;
- statistiche storiche per anno e mese;
- integrazione con mappa SVG interattiva più avanzata.

---

## Licenza e note

Questo progetto è stato realizzato a scopo didattico.

Non gestisce pagamenti reali e non deve essere usato come sistema di biglietteria reale senza ulteriori controlli tecnici, legali e di sicurezza.

Il codice può essere usato come base di studio per:

- PHP;
- MySQL;
- progettazione web;
- gestione ruoli;
- interfacce responsive;
- accessibilità;
- SEO;
- documentazione tecnica;
- simulazione di processi digitali.

---

<p align="center">
  <strong>Museo Storico Severi</strong><br>
  Un viaggio nella storia, progettato come esperienza digitale completa.
</p>
