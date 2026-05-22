# Museo Storico Severi

> Sito web didattico completo per la gestione di un museo storico: esposizioni, prenotazioni, biglietti, pagamenti simulati, portafoglio virtuale, rimborsi, cassa, validazione QR, area utente e pannello amministrativo.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%2FMariaDB-Database-4479A1?logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-Frontend-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-Responsive-1572B6?logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?logo=javascript&logoColor=black)
![Project](https://img.shields.io/badge/Project-Didattico-success)

---

## Indice

1. [Presentazione del progetto](#presentazione-del-progetto)
2. [Obiettivi didattici](#obiettivi-didattici)
3. [Tecnologie utilizzate](#tecnologie-utilizzate)
4. [Funzionalità principali](#funzionalità-principali)
5. [Ruoli utente](#ruoli-utente)
6. [Area pubblica](#area-pubblica)
7. [Registrazione, login e sicurezza account](#registrazione-login-e-sicurezza-account)
8. [Area personale utente](#area-personale-utente)
9. [Prenotazione biglietti](#prenotazione-biglietti)
10. [Prenotazione docente e classi](#prenotazione-docente-e-classi)
11. [Pagamenti simulati](#pagamenti-simulati)
12. [Portafoglio virtuale](#portafoglio-virtuale)
13. [Ordini e biglietti](#ordini-e-biglietti)
14. [PDF e stampa biglietti](#pdf-e-stampa-biglietti)
15. [Recupero ordine](#recupero-ordine)
16. [Validazione biglietti](#validazione-biglietti)
17. [Area cassa](#area-cassa)
18. [Rimborsi](#rimborsi)
19. [Area amministratore](#area-amministratore)
20. [Sistema email](#sistema-email)
21. [Database](#database)
22. [Struttura delle cartelle](#struttura-delle-cartelle)
23. [File principali](#file-principali)
24. [Installazione locale con XAMPP](#installazione-locale-con-xampp)
25. [Installazione su Altervista](#installazione-su-altervista)
26. [Account demo](#account-demo)
27. [Accessibilità, responsive design e performance](#accessibilità-responsive-design-e-performance)
28. [Sicurezza applicativa](#sicurezza-applicativa)
29. [Flussi operativi consigliati per il test](#flussi-operativi-consigliati-per-il-test)
30. [Manutenzione e personalizzazione](#manutenzione-e-personalizzazione)
31. [Possibili sviluppi futuri](#possibili-sviluppi-futuri)
32. [Licenza e note](#licenza-e-note)

---

## Presentazione del progetto

**Museo Storico Severi** è un progetto web sviluppato in PHP e MySQL/MariaDB che simula il funzionamento completo di un piccolo gestionale per un museo.

Il sito non si limita a mostrare pagine informative, ma permette di gestire un vero flusso operativo:

- pubblicazione delle esposizioni;
- scelta dei biglietti;
- selezione di data, fascia oraria, categoria e servizi opzionali;
- prenotazione anche senza account;
- registrazione e login utente;
- pagamento simulato con carta, PayPal, contanti o saldo;
- generazione dell’ordine;
- generazione dei biglietti;
- recupero ordine tramite codice;
- stampa o download del PDF;
- validazione dei ticket all’ingresso;
- gestione della cassa;
- gestione dei rimborsi;
- amministrazione di utenti, tariffe, esposizioni, servizi e fasce orarie.

Il progetto è pensato per essere utilizzato in ambito scolastico come esempio completo di applicazione web dinamica con database relazionale, ruoli, sessioni, form, controlli, operazioni CRUD, invio email e interfaccia responsive.

---

## Obiettivi didattici

Il progetto è stato costruito per mostrare in modo concreto molte competenze dell’informatica applicata:

- progettazione di un sito dinamico;
- separazione tra area pubblica e area riservata;
- uso di PHP per la logica server-side;
- uso di MySQL/MariaDB per la persistenza dei dati;
- modellazione di un database con chiavi primarie, chiavi esterne e vincoli;
- gestione di ruoli e permessi;
- creazione, lettura, aggiornamento ed eliminazione di dati;
- gestione sicura delle password;
- validazione dei dati lato server;
- protezione da accessi non autorizzati;
- simulazione di pagamenti;
- generazione di ticket e codici univoci;
- lettura QR code da fotocamera;
- generazione di PDF;
- invio email applicative;
- progettazione responsive per desktop, tablet e smartphone;
- ottimizzazione delle prestazioni e dell’accessibilità.

Il sito può essere usato come progetto di laboratorio, come base per una relazione tecnica o come esempio da caricare su GitHub.

---

## Tecnologie utilizzate

### Backend

- PHP procedurale;
- PDO per la connessione al database;
- sessioni PHP;
- funzioni personalizzate per autenticazione, autorizzazione, email, ordini, pagamento e sicurezza;
- prepared statement per le query principali;
- generazione manuale del PDF dei biglietti;
- PHPMailer per l’invio email, con fallback tramite `mail()` e debug locale.

### Database

- MySQL/MariaDB;
- tabelle InnoDB;
- chiavi primarie;
- chiavi esterne;
- indici;
- vincoli `CHECK`;
- campi `ENUM` per stati e ruoli;
- script SQL unico completo per installazione o aggiornamento.

### Frontend

- HTML5;
- CSS personalizzato;
- Tailwind CSS tramite file locale o classi utility;
- JavaScript vanilla;
- interfaccia responsive;
- layout ottimizzato per desktop, tablet, iPad, iPhone e smartphone stretti.

### Librerie e risorse

- PHPMailer;
- font web;
- immagini ottimizzate in formato WebP;
- libreria JavaScript per lettura QR compatibile anche con browser che non supportano nativamente `BarcodeDetector`.

---

## Funzionalità principali

Il sito include:

- homepage istituzionale;
- pagina “Chi siamo”;
- pagina esposizioni;
- pagina novità;
- pagina informazioni e tariffe;
- registrazione utente;
- verifica email tramite codice;
- login con controllo password;
- visualizzazione password con icone;
- recupero password tramite domanda di sicurezza e codice email;
- profilo personale;
- modifica dati account;
- storico ordini;
- eliminazione account;
- prenotazione biglietti standard;
- prenotazione docente/classe;
- pagamenti simulati;
- pagamento con saldo;
- ricarica portafoglio virtuale;
- generazione ordine;
- generazione biglietti;
- generazione PDF;
- stampa biglietti;
- recupero ordine senza login;
- validazione biglietto tramite codice o QR;
- validazione tramite fotocamera;
- cassa con ricerca ordine o ticket;
- pagamento in contanti in cassa;
- lettura QR anche nella vista cassa;
- richiesta rimborso utente;
- approvazione o rifiuto rimborso da admin;
- email automatica su accettazione o rifiuto rimborso;
- blocco dei biglietti rimborsati;
- pannello amministratore;
- gestione esposizioni;
- gestione fasce orarie;
- gestione categorie riduzione;
- gestione tariffe;
- gestione servizi opzionali;
- gestione utenti;
- gestione ruoli;
- area tester con permessi estesi;
- responsive design avanzato;
- ottimizzazioni Lighthouse;
- main landmark per accessibilità;
- controllo layout shift;
- logo ottimizzato per LCP.

---

## Ruoli utente

Il sistema distingue diversi ruoli.

### Visitatore non registrato

Può:

- navigare nel sito;
- consultare esposizioni e informazioni;
- acquistare biglietti;
- recuperare un ordine con codice;
- stampare o scaricare biglietti se possiede il codice ordine.

### Visitatore registrato

Può:

- accedere all’area personale;
- aggiornare i propri dati;
- vedere lo storico ordini;
- usare il portafoglio virtuale;
- ricaricare il saldo;
- pagare con saldo;
- richiedere rimborsi;
- recuperare più facilmente i propri ordini.

### Operatore

Può:

- accedere alla pagina di validazione biglietti;
- cercare un biglietto tramite codice;
- leggere il QR code con la fotocamera;
- verificare lo stato del ticket;
- validare il biglietto se è valido e pagato;
- bloccare la validazione se il biglietto è già usato, non pagato, annullato o rimborsato.

### Cassiere

Può:

- accedere all’area cassa;
- cercare ordini;
- cercare biglietti;
- leggere QR code come nella pagina di validazione;
- vedere tutti i biglietti collegati a un ordine;
- saldare ordini non pagati;
- confermare pagamenti in contanti;
- impedire operazioni su ordini rimborsati.

### Amministratore

Può:

- gestire esposizioni;
- gestire fasce orarie;
- gestire categorie;
- gestire tariffe;
- gestire servizi opzionali;
- gestire utenti e ruoli;
- gestire rimborsi;
- approvare o rifiutare richieste di rimborso;
- attivare o modificare dati amministrativi;
- monitorare il funzionamento del sistema.

### Tester

È un ruolo speciale utile durante lo sviluppo. Può accedere alle funzioni principali di:

- amministratore;
- operatore;
- cassiere.

Serve per testare l’intero progetto senza dover cambiare continuamente account.

---

## Area pubblica

### Homepage

La homepage presenta il Museo Storico Severi con una grafica istituzionale e moderna.

Contiene:

- hero iniziale;
- titolo del museo;
- breve descrizione;
- pulsanti di accesso alle funzioni principali;
- dati dinamici sulle esposizioni;
- card informative;
- collegamenti alla prenotazione;
- collegamenti alle esposizioni;
- adattamento mobile senza logo grande nella sezione hero.

Nella versione mobile il logo grande non viene mostrato per evitare ingombro visivo. Al suo posto la pagina mantiene una struttura più compatta e leggibile, con elementi informativi organizzati in modo ordinato.

### Chi siamo

La pagina presenta il progetto come esperienza didattica e tecnologica.

Può contenere sezioni dedicate a:

- identità del museo;
- finalità del progetto;
- valore storico e culturale;
- competenze digitali coinvolte;
- collaborazione e lavoro di squadra;
- uso consapevole della tecnologia.

### Esposizioni

La pagina mostra le esposizioni pubblicate.

Per ogni esposizione possono essere visualizzati:

- titolo;
- descrizione;
- periodo;
- stato;
- link alla prenotazione;
- eventuali fasce disponibili.

Le esposizioni vengono filtrate in base allo stato, così l’utente vede solo quelle pubbliche e coerenti con il percorso di visita.

### Novità

La sezione novità permette di presentare comunicazioni, eventi e aggiornamenti collegati al museo.

### Info e tariffe

La pagina mostra:

- informazioni utili;
- categorie di riduzione;
- prezzi;
- servizi opzionali;
- indicazioni sulla prenotazione;
- modalità di pagamento;
- indicazioni per gruppi e scuole.

---

## Registrazione, login e sicurezza account

### Registrazione

La pagina di registrazione permette di creare un account inserendo:

- nome;
- cognome;
- email;
- password;
- eventuale domanda di sicurezza;
- risposta di sicurezza.

Il sistema prevede:

- controllo dei campi obbligatori;
- controllo email;
- hashing della password;
- invio del codice di verifica email;
- creazione dell’utente come visitatore;
- account non verificato fino all’inserimento del codice.

La card di registrazione è centrata nello schermo come la card di login, mantenendo una resa ordinata sia su desktop sia su mobile.

### Login

La pagina di login consente l’accesso tramite email e password.

Sono presenti:

- campo email;
- campo password;
- pulsante di accesso;
- link per recupero password;
- link per registrazione;
- icone per mostrare o nascondere la password.

La password può essere visualizzata o nascosta con:

- `👁️` quando la password è nascosta;
- `🙈` quando la password è visibile.

La card di login resta centrata nello schermo, come nella versione originale del progetto.

### Verifica email

Dopo la registrazione, l’utente riceve un codice via email.

La verifica email consente di:

- confermare che l’indirizzo email appartenga davvero all’utente;
- impedire l’uso completo di account non verificati;
- migliorare l’affidabilità del sistema.

### Recupero password

Il recupero password utilizza un flusso più sicuro rispetto al semplice cambio password.

Il sistema può richiedere:

- email;
- domanda di sicurezza;
- codice inviato via email;
- nuova password.

La nuova password viene salvata sempre in forma hashata.

---

## Area personale utente

L’area personale permette all’utente registrato di gestire il proprio profilo.

Funzioni principali:

- visualizzazione dati personali;
- modifica nome e cognome;
- modifica email;
- cambio password;
- visualizzazione ultimi ordini;
- accesso allo storico completo ordini;
- visualizzazione saldo portafoglio;
- ricarica portafoglio;
- eliminazione account.

La vista è stata resa più stabile: quando si cambia sezione all’interno del profilo, il contenitore mantiene una dimensione coerente e non provoca salti visivi fastidiosi.

Da desktop il saldo del portafoglio è visibile come da mobile, così l’utente può controllare subito il credito disponibile.

---

## Prenotazione biglietti

La prenotazione standard consente di acquistare biglietti anche senza login.

Il flusso prevede:

1. scelta del tipo di biglietto;
2. scelta della data;
3. scelta della fascia oraria, se richiesta;
4. scelta della categoria di riduzione;
5. scelta della quantità;
6. selezione di eventuali servizi opzionali;
7. inserimento dei dati cliente;
8. scelta del metodo di pagamento;
9. conferma ordine;
10. generazione dei biglietti.

### Biglietto base

Il biglietto base consente l’ingresso al museo senza collegamento diretto a una specifica fascia di una mostra.

### Biglietto esposizione

Il biglietto esposizione è collegato a una specifica esposizione e a una fascia oraria.

In questo caso vengono controllati:

- esposizione;
- data;
- fascia;
- capienza;
- disponibilità residua.

### Categorie di riduzione

Le categorie permettono di applicare sconti.

Esempi:

- intero;
- studente;
- senior;
- bambino;
- docente accompagnatore.

Ogni categoria può richiedere un documento.

### Servizi opzionali

Il visitatore può aggiungere servizi come:

- audioguida;
- visita guidata;
- catalogo mostra;
- laboratorio didattico.

Il prezzo del servizio viene salvato come snapshot, così resta corretto anche se in futuro il prezzo del servizio viene modificato.

---

## Prenotazione docente e classi

La pagina dedicata ai docenti consente la prenotazione per classi o gruppi scolastici.

Campi gestiti:

- docente referente;
- email referente;
- scuola;
- codice meccanografico;
- indirizzo scuola;
- città scuola;
- telefono scuola;
- classe/sezione;
- numero studenti;
- numero docenti accompagnatori;
- note;
- servizi opzionali;
- metodo di pagamento.

I docenti accompagnatori possono essere trattati come biglietti gratuiti tramite la categoria dedicata.

La prenotazione docente permette una gestione più ampia rispetto alla prenotazione ordinaria, perché le classi possono superare i limiti previsti per un acquisto standard.

---

## Pagamenti simulati

Il progetto non effettua pagamenti reali, ma simula diversi metodi.

Metodi disponibili:

- carta di credito;
- PayPal;
- contanti;
- saldo del portafoglio virtuale.

### Pagamento con carta

Il pagamento con carta mostra un form dedicato simile a un checkout reale.

Può richiedere:

- numero carta;
- intestatario;
- scadenza;
- CVV.

I dati non vengono realmente processati: servono solo per simulare il flusso didattico.

### Pagamento con PayPal

Il pagamento PayPal è simulato e permette di completare l’ordine come se l’utente avesse pagato con account PayPal.

### Pagamento in contanti

Se l’utente sceglie il contante:

- l’ordine viene creato;
- i biglietti risultano non pagati;
- il cassiere potrà successivamente saldare l’ordine;
- solo dopo il pagamento i biglietti diventano utilizzabili.

### Pagamento con saldo

Il pagamento con saldo usa il credito disponibile nel portafoglio virtuale dell’utente.

Se il saldo è sufficiente:

- viene scalato l’importo;
- l’ordine viene segnato come pagato;
- i biglietti vengono generati come validi.

Se il saldo non è sufficiente:

- il pagamento viene bloccato;
- l’utente deve ricaricare il portafoglio o scegliere un altro metodo.

---

## Portafoglio virtuale

Il portafoglio virtuale è collegato al campo `saldo_utente` della tabella `Utenti`.

Permette all’utente registrato di:

- visualizzare il saldo;
- scegliere un importo da ricaricare;
- scegliere il metodo di ricarica;
- simulare il pagamento della ricarica;
- usare il saldo per pagare ordini futuri.

### Ricarica saldo

La vecchia logica con semplice campo numerico è stata sostituita da un flusso più completo.

Ora la ricarica prevede:

1. visualizzazione del saldo attuale;
2. scelta dell’importo;
3. scelta tra carta di credito o PayPal;
4. conferma tramite form di pagamento dedicato;
5. aggiornamento del saldo utente.

La sezione è stata organizzata in modo più ordinato:

- prima il saldo;
- sotto la scelta dell’importo;
- sotto i metodi di pagamento;
- infine il pulsante di conferma.

### Pagina pagamento ricarica

È presente un form di pagamento dedicato al saldo del portafoglio, separato dal pagamento degli ordini.

Questo rende più chiara la differenza tra:

- pagamento di un ordine;
- ricarica del credito personale.

---

## Ordini e biglietti

### Ordini

Ogni acquisto genera un record nella tabella `Ordini`.

Un ordine può contenere:

- utente collegato;
- codice di recupero;
- dati cliente;
- importo totale;
- stato pagamento;
- metodo pagamento;
- dati scuola se è una prenotazione docente;
- dati rimborso;
- data acquisto.

### Biglietti

Ogni ordine genera uno o più biglietti nella tabella `Biglietti`.

Ogni biglietto contiene:

- codice univoco;
- ordine collegato;
- tipo;
- data validità;
- fascia oraria;
- categoria;
- prezzo lordo;
- sconto applicato;
- stato;
- data utilizzo.

### Stati del biglietto

Gli stati principali sono:

- `Valido`;
- `Utilizzato`;
- `Non pagato`;
- `Annullato`.

Quando un ordine viene rimborsato, il biglietto non deve più essere utilizzabile. Rimane comunque nello storico ordini come documento della transazione avvenuta.

### Codice univoco

Il codice univoco del biglietto viene usato per:

- visualizzazione;
- recupero;
- validazione manuale;
- generazione QR code;
- scansione da fotocamera.

---

## PDF e stampa biglietti

Il progetto genera un documento riepilogativo dell’ordine e dei biglietti.

Funzioni principali:

- riepilogo ordine;
- dati cliente;
- metodo pagamento;
- stato pagamento;
- elenco ticket;
- codici univoci;
- stato ticket;
- indicazione dei biglietti rimborsati;
- layout di stampa ordinato;
- footer del documento.

Il PDF può essere:

- scaricato;
- stampato;
- usato come documento da presentare all’ingresso.

Se un ordine è rimborsato, il PDF lo indica chiaramente, evitando che il ticket possa essere confuso con un biglietto ancora utilizzabile.

---

## Recupero ordine

La pagina di recupero ordine consente a un visitatore di recuperare i biglietti senza login.

Il visitatore inserisce:

- codice ordine;
- eventuale email collegata, se prevista dal flusso.

Il sistema cerca l’ordine e rimanda alla pagina biglietti.

Questa funzione è utile perché il progetto permette l’acquisto anche senza registrazione.

---

## Validazione biglietti

La pagina di validazione è dedicata a operatori, amministratori e tester.

Permette di:

- inserire manualmente il codice del biglietto;
- leggere un QR code con la fotocamera;
- recuperare i dati del ticket;
- verificare ordine, cliente, tipo, fascia e stato;
- validare il biglietto se tutto è corretto.

### Lettura QR

La lettura QR è stata resa compatibile anche con browser che non supportano nativamente `BarcodeDetector`.

Il sistema può usare una libreria JavaScript compatibile con Safari/iPhone e altri browser mobili.

In questo modo la scansione non viene più bloccata con il messaggio “browser non supportato” quando la fotocamera può invece essere usata.

### Blocco validazione

Il biglietto non può essere validato se:

- è già stato utilizzato;
- l’ordine non è pagato;
- l’ordine è rimborsato;
- il biglietto è annullato;
- il codice non esiste.

Quando viene validato, lo stato passa a `Utilizzato` e viene salvata la data di utilizzo.

---

## Area cassa

La vista cassiere gestisce il pagamento degli ordini non pagati e il controllo dei ticket.

Funzioni:

- ricerca per codice ordine;
- ricerca per codice biglietto;
- scansione QR tramite fotocamera;
- visualizzazione ordine;
- visualizzazione dei biglietti dell’ordine;
- pagamento in cassa;
- conferma del saldo;
- blocco di ordini rimborsati.

### QR code in cassa

La cassa può leggere i QR code come la pagina di validazione biglietti.

La logica è stata resa simile alla pagina `valida_biglietti.php`, così il cassiere può lavorare più velocemente anche senza digitare manualmente il codice.

### Pagamento ordine non pagato

Se un ordine è stato creato con metodo contanti:

- inizialmente risulta non pagato;
- i biglietti non sono utilizzabili;
- il cassiere può confermare l’incasso;
- l’ordine diventa pagato;
- i biglietti diventano validi.

### Ordini rimborsati

Se un ordine è rimborsato:

- non può essere pagato;
- non può essere modificato operativamente;
- non può generare biglietti utilizzabili;
- resta visibile come storico.

---

## Rimborsi

Il sistema di rimborsi è una delle funzionalità più complete del progetto.

### Richiesta rimborso utente

L’utente può chiedere il rimborso di un ordine se le condizioni lo permettono.

La richiesta viene registrata correttamente e compare nell’area amministratore.

Sono stati corretti i messaggi di feedback, così quando la richiesta viene inviata correttamente non compare più un alert rosso errato.

### Condizioni di rimborso

Un ordine non dovrebbe essere rimborsabile se:

- è già stata richiesta una procedura di rimborso;
- è già stato rimborsato;
- uno o più biglietti risultano utilizzati;
- lo stato dell’ordine non consente l’operazione.

### Gestione amministratore

L’amministratore può:

- vedere le richieste di rimborso;
- leggere il motivo inserito dall’utente;
- accettare il rimborso;
- rifiutare il rimborso;
- aggiornare lo stato dell’ordine.

### Email rimborso

Quando un rimborso viene accettato o rifiutato, l’utente riceve una comunicazione email.

L’email consente di informare il visitatore dell’esito della richiesta senza dover controllare manualmente il sito.

### Ordine rimborsato

Quando un ordine è rimborsato:

- rimane nello storico;
- viene mostrato come rimborsato;
- i biglietti non possono più essere validati;
- il cassiere non può più operarci;
- il pagamento non può essere ripetuto;
- il PDF lo indica come rimborsato.

---

## Area amministratore

L’area amministratore è il pannello gestionale del sito.

### Sezioni principali

L’amministratore può gestire:

- esposizioni;
- fasce orarie;
- categorie riduzione;
- tariffe;
- servizi opzionali;
- utenti;
- rimborsi.

### Esposizioni

È possibile:

- creare esposizioni;
- modificare titolo;
- modificare descrizione;
- modificare periodo;
- cambiare stato;
- gestire esposizioni pubblicate, bozze, concluse o annullate.

### Fasce orarie

Per ogni esposizione possono essere create fasce orarie.

Ogni fascia ha:

- esposizione collegata;
- data;
- ora ingresso;
- capienza massima.

La capienza viene usata per evitare prenotazioni oltre il numero disponibile.

### Categorie riduzione

Le categorie permettono la gestione degli sconti.

Ogni categoria contiene:

- nome;
- percentuale di sconto;
- documento richiesto.

### Tariffe

Le tariffe collegano:

- tipo biglietto;
- categoria;
- prezzo.

In questo modo il prezzo finale può cambiare in base a biglietto e categoria.

### Servizi opzionali

Il pannello permette di gestire servizi aggiuntivi.

Ogni servizio ha:

- nome;
- descrizione;
- prezzo.

### Utenti

La gestione utenti permette di:

- cercare utenti;
- modificare ruolo;
- aggiornare dati;
- gestire account;
- attribuire permessi operativi.

### Rimborsi

La sezione rimborsi mostra le richieste pendenti e consente all’amministratore di accettare o rifiutare.

### Responsive admin

L’area admin è stata adattata per:

- smartphone stretti;
- iPhone;
- iPad;
- tablet;
- schermi intermedi.

Quando lo schermo non consente una visualizzazione comoda, le card vengono ridotte:

- padding più contenuto;
- input più stretti;
- griglie più flessibili;
- bottoni più compatti;
- maggiore controllo degli overflow;
- minore rischio di accavallamenti.

---

## Sistema email

Il progetto usa un sistema centralizzato per l’invio email.

Le email possono essere inviate con:

- PHPMailer;
- SMTP;
- funzione `mail()`;
- debug locale se l’invio fallisce.

### Email gestite

Il sito può inviare:

- codice di verifica account;
- codice recupero password;
- conferma ordine;
- comunicazione rimborso accettato;
- comunicazione rimborso rifiutato;
- eventuali messaggi diagnostici di test.

### Debug email

Se l’invio fallisce, il progetto può salvare una copia della mail nella cartella di debug.

Questo è utile in ambiente locale o durante i test su hosting.

### Differenza locale/produzione

Il progetto è pensato per funzionare sia in locale sia su Altervista.

Può usare configurazioni diverse in base all’ambiente.

---

## Database

Il database è relazionale e contiene le tabelle principali del progetto.

### Tabelle principali

- `Utenti`
- `Esposizioni`
- `Categorie_Riduzione`
- `Tariffe`
- `Servizi_Opzionali`
- `Fasce_Orarie`
- `Ordini`
- `Biglietti`
- `Biglietti_Servizi`

### Utenti

Contiene gli account del sistema.

Campi principali:

- `id_utente`;
- `nome`;
- `cognome`;
- `email`;
- `password_hash`;
- `domanda_sicurezza`;
- `risposta_sicurezza_hash`;
- `ruolo`;
- `email_verificata`;
- `codice_verifica_email`;
- `codice_verifica_scadenza`;
- `data_registrazione`;
- `password_reset_code`;
- `password_reset_scadenza`;
- `saldo_utente`.

### Esposizioni

Contiene le mostre del museo.

Campi principali:

- `id_esposizione`;
- `titolo`;
- `descrizione`;
- `emoji`;
- `data_inizio`;
- `data_fine`;
- `stato`.

### Categorie_Riduzione

Contiene le categorie di sconto.

Campi principali:

- `id_categoria`;
- `nome`;
- `percentuale_sconto`;
- `documento_richiesto`.

### Tariffe

Contiene i prezzi dei biglietti.

Campi principali:

- `id_tariffa`;
- `tipo_biglietto`;
- `id_categoria`;
- `prezzo`.

### Servizi_Opzionali

Contiene i servizi aggiuntivi acquistabili.

Campi principali:

- `id_servizio`;
- `nome`;
- `descrizione`;
- `prezzo`.

### Fasce_Orarie

Contiene date e orari delle esposizioni.

Campi principali:

- `id_fascia`;
- `id_esposizione`;
- `data`;
- `ora_ingresso`;
- `capienza_massima`.

### Ordini

Contiene gli acquisti e le prenotazioni.

Campi principali:

- `id_ordine`;
- `id_utente`;
- `codice_recupero`;
- `nome_cliente`;
- `email_cliente`;
- `data_acquisto`;
- `importo_totale`;
- `stato_pagamento`;
- `metodo_pagamento`;
- `prenotazione_docente`;
- `nome_scuola`;
- `codice_meccanografico`;
- `indirizzo_scuola`;
- `citta_scuola`;
- `telefono_scuola`;
- `classe_sezione`;
- `quantita_studenti`;
- `numero_docenti`;
- `note_scuola`;
- `richiesta_rimborso`;
- `motivo_rimborso`;
- `stato_rimborso`;
- `data_richiesta_rimborso`;
- `data_esito_rimborso`.

### Biglietti

Contiene i singoli ticket.

Campi principali:

- `id_biglietto`;
- `codice_univoco`;
- `id_ordine`;
- `tipo`;
- `data_validita`;
- `id_fascia`;
- `id_categoria`;
- `prezzo_lordo`;
- `sconto_applicato`;
- `stato`;
- `data_utilizzo`.

### Biglietti_Servizi

Tabella ponte molti-a-molti tra biglietti e servizi opzionali.

Campi principali:

- `id_biglietto`;
- `id_servizio`;
- `prezzo_snapshot`.

### Relazioni principali

- un utente può avere molti ordini;
- un ordine può contenere molti biglietti;
- un biglietto appartiene a un ordine;
- un biglietto può appartenere a una categoria;
- un biglietto esposizione appartiene a una fascia oraria;
- una fascia oraria appartiene a una esposizione;
- una tariffa è collegata a una categoria;
- un biglietto può avere più servizi opzionali;
- un servizio opzionale può comparire su più biglietti.

---

## Struttura delle cartelle

Una struttura tipica del progetto è la seguente:

```text
museo/
├── index.php
├── chi_siamo.php
├── esposizioni.php
├── novita.php
├── info_tariffe.php
├── prenota.php
├── prenota_docente.php
├── pagamento.php
├── pagamento_wallet.php
├── biglietti.php
├── ordine_pdf.php
├── scarica_pdf.php
├── recupera_ordine.php
├── registrazione.php
├── verifica_email.php
├── login.php
├── logout.php
├── account.php
├── ordini.php
├── recupero_password.php
├── admin.php
├── cassa.php
├── valida_biglietti.php
├── config.php
├── db.php
├── auth.php
├── app_mailer.php
├── header.php
├── footer.php
├── database_unico_completo_museo.sql
├── assets/
│   └── css/
│       ├── style.css
│       ├── fixes.css
│       ├── home-critical.css
│       └── tailwind-local.css
├── img/
│   ├── logo-lcp.webp
│   └── ...
├── PHPMailer/
│   └── ...
└── mail_debug/
    └── ...
```

Alcuni file possono variare in base alla versione effettiva del progetto, ma questa è la struttura logica complessiva.

---

## File principali

### `index.php`

Homepage del sito.

Gestisce:

- presentazione museo;
- caricamento dati dinamici;
- esposizioni pubblicate;
- collegamenti rapidi;
- layout mobile senza logo grande;
- ottimizzazione LCP del logo;
- area gestionale dinamica in base al ruolo.

### `header.php`

Contiene:

- apertura HTML;
- meta tag;
- caricamento CSS;
- eventuale preload delle risorse critiche;
- menu pubblico;
- menu utente;
- collegamenti alle aree riservate;
- gestione responsive della navbar;
- apertura del landmark principale se previsto dal layout.

### `footer.php`

Contiene:

- footer istituzionale;
- link rapidi;
- contatti;
- orari;
- copyright;
- chiusura del landmark principale;
- script comuni.

### `config.php`

Contiene le impostazioni generali del progetto:

- nome sito;
- URL base;
- parametri ambiente;
- impostazioni email;
- eventuali costanti applicative.

### `db.php`

Gestisce la connessione al database tramite PDO.

### `auth.php`

Gestisce:

- sessioni;
- login;
- logout;
- controllo utente;
- controllo ruoli;
- protezione pagine riservate;
- funzioni di supporto per autenticazione e autorizzazione.

### `registrazione.php`

Gestisce:

- form registrazione;
- creazione utente;
- hash password;
- codice verifica email;
- visualizzazione password con emoji;
- layout card centrata.

### `login.php`

Gestisce:

- form login;
- verifica credenziali;
- controllo account verificato;
- redirect utente;
- visualizzazione password con emoji;
- layout card centrata.

### `account.php`

Gestisce l’area personale.

Include:

- profilo;
- sicurezza;
- ordini recenti;
- portafoglio;
- ricarica saldo;
- richieste rimborso;
- layout stabile tra sezioni.

### `ordini.php`

Mostra lo storico ordini dell’utente.

Consente:

- consultazione ordine;
- pagamento di ordini non pagati;
- download biglietti;
- richiesta rimborso se ammessa.

### `prenota.php`

Gestisce la prenotazione ordinaria.

### `prenota_docente.php`

Gestisce la prenotazione per scuole e classi.

### `pagamento.php`

Gestisce il pagamento simulato degli ordini.

### `pagamento_wallet.php`

Gestisce la ricarica del portafoglio virtuale.

### `biglietti.php`

Mostra i biglietti di un ordine.

Gestisce:

- codice ordine;
- riepilogo;
- stati biglietto;
- blocco di biglietti rimborsati;
- link stampa e PDF.

### `ordine_pdf.php`

Genera il PDF riepilogativo.

### `scarica_pdf.php`

Permette il download del PDF.

### `recupera_ordine.php`

Consente di recuperare un ordine tramite codice.

### `valida_biglietti.php`

Area operatore per validazione ticket.

Include:

- ricerca manuale;
- lettura QR;
- verifica stato;
- validazione;
- blocco per ticket non utilizzabili.

### `cassa.php`

Area cassiere.

Include:

- ricerca ordine;
- ricerca biglietto;
- lettura QR;
- gestione pagamento contanti;
- controllo ordini rimborsati.

### `admin.php`

Pannello amministrativo.

Include:

- CRUD esposizioni;
- fasce orarie;
- categorie;
- tariffe;
- servizi;
- utenti;
- ruoli;
- rimborsi;
- responsive tablet/mobile.

### `app_mailer.php`

Sistema email centralizzato.

Include:

- invio verifica;
- invio recupero password;
- invio conferma ordine;
- invio esito rimborso;
- fallback e debug.

### `assets/css/fixes.css`

Contiene correzioni mirate:

- responsive admin;
- centratura login/registrazione;
- fix layout tablet;
- fix overflow;
- fix card;
- fix CLS;
- regole per elementi che non devono sforare.

### `assets/css/home-critical.css`

CSS critico per homepage.

Serve per:

- ridurre caricamenti iniziali;
- migliorare LCP;
- organizzare hero;
- nascondere il logo grande da mobile;
- mantenere una home ordinata anche su schermi piccoli.

---

## Installazione locale con XAMPP

### 1. Copiare il progetto

Copia la cartella `museo` dentro:

```text
C:\xampp\htdocs\
```

Il percorso finale sarà:

```text
C:\xampp\htdocs\museo
```

### 2. Avviare Apache e MySQL

Apri il pannello XAMPP e avvia:

- Apache;
- MySQL.

### 3. Creare il database

Apri phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Crea un database, ad esempio:

```text
biglietteria_museo
```

### 4. Importare il file SQL

Importa il file:

```text
database_unico_completo_museo.sql
```

### 5. Configurare la connessione

Apri `config.php` o `db.php` e verifica i parametri:

```php
$host = 'localhost';
$dbname = 'biglietteria_museo';
$user = 'root';
$password = '';
```

### 6. Aprire il sito

Vai su:

```text
http://localhost/museo/
```

---

## Installazione su Altervista

### 1. Caricare i file

Carica la cartella `museo` nello spazio web Altervista tramite file manager o FTP.

### 2. Configurare il database

Su Altervista il database ha spesso nome simile a:

```text
my_nomeutente
```

Aggiorna le credenziali in `db.php` o `config.php`.

### 3. Importare SQL

Apri phpMyAdmin da Altervista e importa:

```text
database_unico_completo_museo.sql
```

### 4. Controllare `SITE_URL`

Nel file di configurazione verifica che l’URL del sito sia corretto.

Esempio:

```text
https://museostoricoseveri.altervista.org/museo
```

### 5. Controllare email

Su hosting reale è importante verificare:

- mittente;
- dominio;
- funzione `mail()`;
- eventuali impostazioni SMTP;
- cartella `mail_debug`.

---

## Account demo

Se il file SQL contiene dati demo, possono essere presenti account di prova.

Esempio tipico:

| Ruolo | Email |
|---|---|
| Amministratore | `admin@museo.local` |
| Operatore | `operatore@museo.local` |
| Cassiere | `cassiere@museo.local` |
| Visitatore | `visitatore@museo.local` |

La password demo può essere indicata direttamente nel file SQL o nella documentazione interna del progetto.

Per sicurezza, dopo l’installazione reale è consigliato:

- cambiare tutte le password demo;
- eliminare gli account non necessari;
- usare email reali;
- verificare i ruoli.

---

## Accessibilità, responsive design e performance

Il progetto è stato rifinito per migliorare usabilità e risultati Lighthouse.

### Accessibilità

Sono stati considerati:

- presenza del landmark `main`;
- struttura più chiara della pagina;
- pulsanti leggibili;
- contrasto dei testi;
- stati visivi;
- navigazione più comprensibile;
- contenuti più ordinati per screen reader.

### Responsive design

Il sito è stato adattato per:

- desktop;
- notebook;
- tablet;
- iPad;
- iPhone;
- smartphone stretti.

Particolare attenzione è stata data a:

- navbar;
- area admin;
- card;
- input;
- tabelle;
- bottoni;
- hero home;
- login e registrazione.

### iPad e tablet

Nella zona admin, quando lo schermo non consente una visualizzazione completa:

- le card vengono ridotte;
- il padding diminuisce;
- le griglie diventano più flessibili;
- input e bottoni non sforano;
- le sezioni non si accavallano.

### Mobile

Da mobile:

- il logo grande della homepage viene nascosto;
- la home mantiene una struttura più compatta;
- l’area admin usa card più piccole;
- i menu diventano più gestibili;
- le card del profilo restano leggibili;
- i form rimangono centrati.

### Performance

Sono state effettuate ottimizzazioni su:

- immagine logo LCP;
- dimensione del logo WebP;
- preload delle risorse importanti;
- riduzione layout shift;
- immagini con dimensioni esplicite;
- CSS critico per home;
- riduzione elementi inutili;
- gestione più pulita del DOM.

---

## Sicurezza applicativa

Il progetto include diverse misure di sicurezza adatte a un contesto didattico.

### Password

Le password vengono salvate con hash e non in chiaro.

### Sessioni

Le aree riservate usano sessioni PHP.

### Ruoli

Le pagine operative controllano il ruolo dell’utente.

### Query

Le query principali usano prepared statement tramite PDO.

### Email

I codici inviati via email hanno scadenza e vengono controllati lato server.

### Validazione dati

I form controllano i dati principali prima di procedere.

### Biglietti

Un biglietto non viene validato se non è in uno stato corretto.

### Rimborsi

Un biglietto rimborsato non può più essere usato.

---

## Flussi operativi consigliati per il test

### Test visitatore non registrato

1. Aprire la homepage.
2. Visitare le esposizioni.
3. Prenotare un biglietto.
4. Scegliere pagamento carta o PayPal.
5. Recuperare l’ordine.
6. Visualizzare i biglietti.
7. Scaricare il PDF.

### Test utente registrato

1. Registrarsi.
2. Verificare l’email.
3. Effettuare il login.
4. Entrare nel profilo.
5. Ricaricare il portafoglio.
6. Prenotare un biglietto.
7. Pagare con saldo.
8. Controllare lo storico ordini.

### Test contanti/cassa

1. Effettuare una prenotazione con pagamento in contanti.
2. Accedere come cassiere.
3. Cercare l’ordine.
4. Saldare l’ordine.
5. Verificare che i biglietti diventino validi.

### Test validazione

1. Accedere come operatore.
2. Inserire codice ticket oppure scansionare QR.
3. Validare il biglietto.
4. Riprovare a validarlo.
5. Verificare il messaggio di biglietto già utilizzato.

### Test rimborso

1. Accedere come utente.
2. Richiedere rimborso di un ordine rimborsabile.
3. Accedere come amministratore.
4. Accettare o rifiutare il rimborso.
5. Verificare invio email.
6. Verificare che il biglietto rimborsato non sia più utilizzabile.

### Test responsive

1. Aprire il sito da desktop.
2. Aprire il sito da iPad.
3. Aprire il sito da iPhone.
4. Controllare login e registrazione.
5. Controllare area admin.
6. Controllare profilo e portafoglio.
7. Controllare QR in validazione e cassa.

---

## Manutenzione e personalizzazione

### Cambiare colori

Le regole principali si trovano nei file CSS in:

```text
assets/css/
```

In particolare:

- `style.css`;
- `fixes.css`;
- `home-critical.css`.

### Cambiare logo

Sostituire il file:

```text
img/logo-lcp.webp
```

È consigliato mantenere:

- formato WebP;
- peso ridotto;
- dimensioni definite;
- versione ottimizzata per LCP.

### Cambiare tariffe

Le tariffe possono essere modificate da admin oppure direttamente nel database.

### Cambiare esposizioni

Le esposizioni si gestiscono da admin.

### Cambiare account demo

Gli account demo si modificano nel database o da pannello admin.

### Pulizia produzione

Prima di pubblicare definitivamente:

- eliminare file di test;
- controllare `mail_debug`;
- cambiare password demo;
- verificare permessi ruoli;
- controllare configurazione email;
- fare backup del database.

---

## Possibili sviluppi futuri

Il progetto può essere esteso con:

- dashboard statistiche;
- esportazione CSV ordini;
- report vendite;
- gestione immagini esposizioni da pannello admin;
- QR code generati graficamente nel PDF;
- pagamenti reali tramite gateway esterno;
- area newsletter;
- log operazioni amministrative;
- storico rimborsi più dettagliato;
- gestione multi-museo;
- calendario interattivo;
- prenotazioni guidate per eventi;
- notifiche automatiche prima della visita;
- pannello analytics;
- API JSON per app mobile.

---

## Licenza e note

Questo progetto è pensato per uso didattico e formativo.

Può essere personalizzato, studiato, modificato e caricato su GitHub come esempio di applicazione web PHP/MySQL completa.

Prima di usarlo in produzione reale è consigliato effettuare ulteriori controlli su:

- sicurezza;
- protezione dati personali;
- invio email;
- privacy policy;
- cookie policy;
- gestione pagamenti reali;
- backup;
- log applicativi;
- hardening del server.

---

## Sintesi finale

**Museo Storico Severi** è un progetto completo che integra frontend, backend e database in un’unica applicazione didattica.

La piattaforma gestisce l’intero ciclo di visita:

```text
Esposizione → Prenotazione → Pagamento → Ordine → Biglietto → QR → Validazione → Eventuale rimborso
```

È quindi un esempio utile per studiare:

- progettazione web;
- database relazionali;
- logica applicativa;
- ruoli e permessi;
- UX responsive;
- sicurezza base;
- gestione di flussi reali.

