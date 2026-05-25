(function () {
  'use strict';

  var root = document.getElementById('msai-assistant');
  if (!root) return;

  var siteUrl = (root.dataset.siteUrl || '').replace(/\/$/, '');
  var currentPage = root.dataset.currentPage || 'index.php';
  var isLogged = root.dataset.logged === '1';
  var role = root.dataset.role || '';

  var openButton = document.getElementById('msai-open');
  var panel = document.getElementById('msai-panel');
  var closeButton = document.getElementById('msai-close');
  var messagesBox = document.getElementById('msai-messages');
  var suggestionsBox = document.getElementById('msai-suggestions');
  var form = document.getElementById('msai-form');
  var input = document.getElementById('msai-input');
  var resetButton = document.getElementById('msai-reset');
  if (!openButton || !panel || !closeButton || !messagesBox || !suggestionsBox || !form || !input || !resetButton) return;

  var storageKey = 'museo-severi-assistente-chat-v3';
  var MAX_SUGGESTIONS = 4;

  function url(path) {
    return siteUrl + '/' + String(path || '').replace(/^\//, '');
  }

  function escapeHtml(text) {
    return String(text).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
  }

  function normalize(text) {
    return String(text || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function cleanLabel(question) {
    return String(question || '').replace(/^[^a-zA-Z0-9À-ÿ]+/, '').trim();
  }

  function renderLinks(links) {
    if (!links || !links.length) return '';
    return '<div class="msai-link-list">' + links.map(function (link) {
      return '<a class="msai-link" href="' + url(link[1]) + '">' + escapeHtml(link[0]) + '</a>';
    }).join('') + '</div>';
  }

  function renderCards(cards) {
    if (!cards || !cards.length) return '';
    return '<div class="msai-card-grid">' + cards.map(function (card) {
      var safeTitle = escapeHtml(card.title || '');
      var safeText = escapeHtml(card.text || '');
      return '<article class="msai-mini-card">' +
        '<strong>' + safeTitle + '</strong>' +
        '<span>' + safeText + '</span>' +
        (card.link ? '<a href="' + url(card.link) + '">Portami lì</a>' : '') +
        '</article>';
    }).join('') + '</div>';
  }

  function addMessage(type, html, links, save, cards) {
    var item = document.createElement('div');
    item.className = 'msai-message ' + (type === 'user' ? 'msai-user' : 'msai-bot');
    item.innerHTML = type === 'user' ? escapeHtml(html) : html + renderCards(cards) + renderLinks(links);
    messagesBox.appendChild(item);
    window.requestAnimationFrame(function () { messagesBox.scrollTop = 1000000; });
    if (save !== false) saveMessages();
  }

  function getMessagesForStorage() {
    return Array.prototype.slice.call(messagesBox.querySelectorAll('.msai-message')).slice(-12).map(function (node) {
      return { type: node.classList.contains('msai-user') ? 'user' : 'bot', html: node.innerHTML };
    });
  }

  function saveMessages() {
    try { localStorage.setItem(storageKey, JSON.stringify(getMessagesForStorage())); } catch (error) {}
  }

  function restoreMessages() {
    try {
      var saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
      if (!Array.isArray(saved) || !saved.length) return false;
      saved.forEach(function (msg) {
        var item = document.createElement('div');
        item.className = 'msai-message ' + (msg.type === 'user' ? 'msai-user' : 'msai-bot');
        item.innerHTML = msg.html;
        messagesBox.appendChild(item);
      });
      messagesBox.scrollTop = 1000000;
      return true;
    } catch (error) {
      return false;
    }
  }

  var pageHints = {
    'index.php': {
      text: 'Sei nella home: puoi scoprire mostre in evidenza, informazioni principali, prenotazioni e assistenza rapida.',
      links: [['Esposizioni', 'esposizioni.php'], ['Prenota', 'prenota.php'], ['Info e tariffe', 'info.php']],
      suggestions: ['🎟️ Prenota una visita', '🏛️ Vedi mostre', '🔎 Recupera ordine', '🧭 Guidami passo passo']
    },
    'esposizioni.php': {
      text: 'Sei nella pagina esposizioni: puoi filtrare tra tutte, in corso e concluse, poi prenotare una mostra disponibile.',
      links: [['Prenota', 'prenota.php'], ['Recupera ordine', 'recupera_ordine.php']],
      suggestions: ['🏛️ Come filtro le mostre?', '🎟️ Come prenoto?', '📅 Cosa significa in corso?', '🧭 Guidami']
    },
    'prenota.php': {
      text: 'Sei nella prenotazione: scegli mostra o ingresso base, data, fascia oraria, categoria e servizi opzionali.',
      links: [['Info e tariffe', 'info.php'], ['Prenotazione docente', 'prenota_docente.php']],
      suggestions: ['🧭 Guidami nella prenotazione', '💳 Come pago?', '👩‍🏫 Prenotazione docente', '🛠️ Ho un problema']
    },
    'prenota_docente.php': {
      text: 'Sei nella prenotazione docente: puoi organizzare una visita per classe, studenti e accompagnatori.',
      links: [['Info e tariffe', 'info.php'], ['Prenotazione standard', 'prenota.php']],
      suggestions: ['👩‍🏫 Come prenoto per la classe?', '🎟️ Prenotazione standard', '💳 Pagamento', '🛠️ Ho un problema']
    },
    'pagamento.php': {
      text: 'Sei nella pagina di pagamento simulato: completa l’ordine o la ricarica del portafoglio scegliendo il metodo disponibile.',
      links: [['Area personale', 'account.php'], ['Recupera ordine', 'recupera_ordine.php']],
      suggestions: ['💳 Come completo il pagamento?', '💰 Posso usare il saldo?', '📄 Dopo il pagamento?', '🛠️ Ho un problema']
    },
    'account.php': {
      text: 'Sei nella dashboard personale: trovi saldo, notifiche, prossima visita, timeline, ordini, rimborsi e impostazioni account.',
      links: [['Prenota una visita', 'prenota.php'], ['Recupera ordine', 'recupera_ordine.php']],
      suggestions: ['📦 Dove sono gli ordini?', '💰 Ricarico il saldo', '↩️ Chiedo rimborso', '📄 Scarico biglietti']
    },
    'biglietti.php': {
      text: 'Sei nella pagina biglietti: qui trovi ticket digitali, QR code, stato, PDF e ricevuta.',
      links: [['Area personale', 'account.php'], ['Recupera ordine', 'recupera_ordine.php']],
      suggestions: ['📱 Come uso il QR?', '📄 Scarico il PDF', '🧾 Ricevuta', '↩️ Rimborso']
    },
    'ordine_dettaglio.php': {
      text: 'Sei nel dettaglio ordine: puoi controllare pagamento, timeline, biglietti, rimborso e ricevuta PDF.',
      links: [['Area personale', 'account.php']],
      suggestions: ['📌 Spiegami la timeline', '🧾 Ricevuta PDF', '↩️ Rimborso', '📄 Biglietti']
    },
    'recupera_ordine.php': {
      text: 'Sei nel recupero ordine: puoi ritrovare una prenotazione anche senza account usando i dati richiesti.',
      links: [['Accedi', 'login.php'], ['Prenota', 'prenota.php']],
      suggestions: ['🔎 Come recupero?', '📧 Ho perso la mail', '📄 Biglietti', '🛠️ Ho un problema']
    },
    'login.php': {
      text: 'Sei nel login: inserisci email e password. Puoi usare il recupero password se non ricordi le credenziali.',
      links: [['Registrati', 'registrazione.php'], ['Recupera password', 'recupero_password.php']],
      suggestions: ['🔐 Non accedo', '📧 Email non verificata', '🔑 Password dimenticata', '🧭 Guidami']
    },
    'registrazione.php': {
      text: 'Sei nella registrazione: crea l’account, verifica l’email e poi gestisci ordini e biglietti dall’area personale.',
      links: [['Accedi', 'login.php']],
      suggestions: ['📝 Come mi registro?', '📧 Verifica email', '🔐 Login', '🛠️ Ho un problema']
    },
    'admin.php': {
      text: 'Sei nell’area amministratore: puoi gestire dashboard, utenti, esposizioni, fasce, tariffe, servizi, ordini e rimborsi.',
      links: [['Area cassa', 'cassa.php'], ['Validazione', 'valida_biglietti.php']],
      suggestions: ['📊 Dashboard admin', '⬇️ Export CSV', '↩️ Gestione rimborsi', '🧭 Cosa posso fare qui?']
    },
    'cassa.php': {
      text: 'Sei nell’area cassa: puoi cercare ordini, confermare pagamenti, emettere biglietti e leggere QR code.',
      links: [['Validazione', 'valida_biglietti.php'], ['Area admin', 'admin.php']],
      suggestions: ['🔎 Cercare ordine', '📱 Leggere QR', '💵 Pagamento contanti', '🛠️ Ho un problema']
    },
    'valida_biglietti.php': {
      text: 'Sei nella validazione: controlla i ticket con codice o QR e verifica che non siano usati, rimborsati o non validi.',
      links: [['Area cassa', 'cassa.php']],
      suggestions: ['📱 Come leggo il QR?', '🎟️ Stati biglietto', '🚫 Biglietto rimborsato', '🛠️ Ho un problema']
    },
    'info.php': {
      text: 'Sei nella pagina informazioni e tariffe: puoi leggere prezzi, riduzioni, servizi opzionali e indicazioni di visita.',
      links: [['Prenota', 'prenota.php'], ['Esposizioni', 'esposizioni.php'], ['Mappa del museo', 'mappa.php']],
      suggestions: ['💶 Tariffe', '🎧 Servizi opzionali', '🗺️ Mappa', '🎟️ Prenota']
    },
    'mappa.php': {
      text: 'Sei nella mappa del museo: puoi orientarti tra le sale, scegliere un percorso consigliato e raggiungere prenotazioni o esposizioni.',
      links: [['Prenota', 'prenota.php'], ['Esposizioni', 'esposizioni.php'], ['Prenotazione classe', 'prenota_docente.php']],
      suggestions: ['🗺️ Che percorso scelgo?', '👩‍🏫 Percorso studenti', '🎟️ Prenota', '❓ Cosa posso fare qui?']
    },
    'features.php': {
      text: 'Sei nella pagina Features: qui trovi una panoramica chiara delle funzioni principali del sito.',
      links: [['Prenota', 'prenota.php'], ['Mappa e percorso', 'mappa.php'], ['Area riservata', isLogged ? 'account.php' : 'login.php']],
      suggestions: ['📌 Cosa include?', '💬 Assistente virtuale', '🛠️ Area admin', '🎟️ Prenotazioni']
    }
  };

  var defaultSuggestions = ['🧭 Guidami passo passo', '❓ Cosa posso fare qui?', '🛠️ Ho un problema', '🔎 Recupera ordine'];

  var roleSuggestions = {
    'amministratore': ['📊 Dashboard admin', '↩️ Gestione rimborsi', '🏛️ Gestione mostre', '👥 Gestione utenti'],
    'tester': ['📊 Dashboard admin', '📱 QR e cassa', '↩️ Rimborsi', '🧭 Cosa posso fare qui?'],
    'cassiere': ['🔎 Cercare ordine', '💵 Confermare pagamento', '📱 Leggere QR', '🧭 Cosa posso fare qui?'],
    'operatore': ['📱 Validare QR', '🎟️ Stato biglietto', '🚫 Biglietto non valido', '🧭 Cosa posso fare qui?']
  };

  var guides = {
    prenotare: {
      title: 'Ti guido nella prenotazione',
      text: '<ol><li>Apri <strong>Esposizioni</strong> e scegli una mostra in corso.</li><li>Premi <strong>Prenota</strong>.</li><li>Seleziona data, fascia oraria, categoria e servizi.</li><li>Conferma l’ordine.</li><li>Completa il pagamento o scegli il metodo previsto.</li></ol>',
      links: [['Vai alle esposizioni', 'esposizioni.php'], ['Apri prenotazione', 'prenota.php']]
    },
    docente: {
      title: 'Ti guido nella prenotazione docente',
      text: '<ol><li>Apri la pagina <strong>Prenotazione docente</strong>.</li><li>Inserisci scuola, classe, studenti e accompagnatori.</li><li>Scegli data e fascia oraria.</li><li>Conferma l’ordine e completa il pagamento previsto.</li></ol>',
      links: [['Prenotazione docente', 'prenota_docente.php'], ['Info e tariffe', 'info.php']]
    },
    recuperare: {
      title: 'Ti guido nel recupero ordine',
      text: '<ol><li>Apri <strong>Recupera ordine</strong>.</li><li>Inserisci il codice ordine e l’email usata.</li><li>Apri il dettaglio e scarica biglietti, PDF o ricevuta.</li></ol>',
      links: [['Recupera ordine', 'recupera_ordine.php']]
    },
    biglietti: {
      title: 'Ti guido sui biglietti',
      text: '<ol><li>Accedi all’area personale o recupera l’ordine.</li><li>Apri il dettaglio ordine.</li><li>Controlla stato e QR code.</li><li>Scarica PDF, stampa o ricevuta se disponibile.</li></ol>',
      links: [['Area personale', 'account.php'], ['Recupera ordine', 'recupera_ordine.php']]
    },
    rimborso: {
      title: 'Ti guido nel rimborso',
      text: '<ol><li>Vai in <strong>Area personale</strong>.</li><li>Apri <strong>I miei ordini</strong>.</li><li>Apri il dettaglio dell’ordine.</li><li>Richiedi il rimborso, se i biglietti non sono stati usati.</li><li>Attendi l’esito via email.</li></ol>',
      links: [['Area personale', 'account.php']]
    },
    portafoglio: {
      title: 'Ti guido nel portafoglio',
      text: '<ol><li>Apri l’area personale.</li><li>Vai su <strong>Portafoglio virtuale</strong>.</li><li>Scegli importo e metodo di ricarica.</li><li>Completa il pagamento simulato.</li></ol>',
      links: [['Area personale', 'account.php']]
    },
    accesso: {
      title: 'Ti guido nell’accesso',
      text: '<ol><li>Apri <strong>Login</strong>.</li><li>Inserisci email e password.</li><li>Se l’account non è verificato, completa la verifica email.</li><li>Se non ricordi la password, usa recupero password.</li></ol>',
      links: [['Accedi', 'login.php'], ['Recupera password', 'recupero_password.php']]
    }
  };

  var problemCards = [
    { title: 'Non trovo i biglietti', text: 'Recupera l’ordine o entra nell’area personale.', link: 'recupera_ordine.php' },
    { title: 'QR non funziona', text: 'Controlla permesso fotocamera o inserisci il codice manualmente.', link: 'valida_biglietti.php' },
    { title: 'Email non arrivata', text: 'Controlla spam e verifica l’indirizzo usato.', link: 'recupero_password.php' },
    { title: 'Pagamento in sospeso', text: 'Apri il dettaglio ordine o completa in cassa.', link: 'account.php' }
  ];

  var answers = [
    {
      id: 'guidami',
      terms: ['guidami', 'passo passo', 'cosa devo fare', 'aiutami', 'procedura', 'istruzioni'],
      text: '<p>Dimmi cosa vuoi fare oppure scegli una mini-guida. Ti porto direttamente alla pagina giusta.</p>',
      cards: [
        { title: 'Prenotare', text: 'Mostra, data, fascia e pagamento.', link: 'esposizioni.php' },
        { title: 'Recuperare ordine', text: 'Ritrova biglietti con codice ed email.', link: 'recupera_ordine.php' },
        { title: 'Rimborso', text: 'Richiesta dagli ordini se possibile.', link: 'account.php' },
        { title: 'Portafoglio', text: 'Saldo e ricarica simulata.', link: 'account.php' }
      ],
      links: [['Prenotazione', 'prenota.php'], ['Area personale', 'account.php']]
    },
    {
      id: 'qui',
      terms: ['cosa posso fare qui', 'questa pagina', 'dove sono', 'a cosa serve questa pagina', 'pagina corrente'],
      text: '',
      special: 'current-page'
    },
    {
      id: 'prenotazione',
      terms: ['prenotare', 'prenoto', 'prenotazione', 'biglietto', 'ticket', 'mostra', 'esposizione', 'fascia', 'orario', 'data', 'acquistare', 'comprare'],
      text: '<p>Per prenotare una visita scegli mostra, data, fascia oraria, categoria e servizi opzionali. Poi confermi l’ordine e completi il pagamento previsto.</p>',
      guide: 'prenotare',
      links: [['Prenota ora', 'prenota.php'], ['Vedi esposizioni', 'esposizioni.php'], ['Prenotazione docente', 'prenota_docente.php']]
    },
    {
      id: 'filtri-esposizioni',
      terms: ['filtra', 'filtri', 'tutte', 'in corso', 'concluse', 'mostre concluse', 'mostre attive', 'vedere mostre'],
      text: '<p>Nella pagina esposizioni puoi usare <strong>Tutte</strong>, <strong>In corso</strong> e <strong>Concluse</strong>. Le mostre in corso sono quelle prenotabili; le concluse restano come storico.</p>',
      links: [['Apri esposizioni', 'esposizioni.php']]
    },
    {
      id: 'pagamento',
      terms: ['pagare', 'pagamento', 'carta', 'paypal', 'contanti', 'saldo', 'metodo', 'bancomat', 'portafoglio', 'credito', 'pagato'],
      text: '<p>Il sito gestisce pagamenti simulati con carta, PayPal, contanti in cassa e portafoglio virtuale. Se scegli contanti, il cassiere conferma il pagamento prima dell’utilizzo dei biglietti.</p>',
      links: [['Info e tariffe', 'info.php'], ['Area personale', 'account.php']]
    },
    {
      id: 'biglietti-pdf',
      terms: ['biglietti', 'ticket', 'pdf', 'stampa', 'scaricare', 'download', 'codice', 'digitale', 'visualizzare', 'ricevuta', 'perso biglietto'],
      text: '<p>I biglietti sono disponibili dopo la conferma dell’ordine. Puoi vederli nell’area personale o recuperarli con codice ordine ed email. Ogni ticket mostra stato, QR code e PDF.</p>',
      guide: 'biglietti',
      links: [['Area personale', 'account.php'], ['Recupera ordine', 'recupera_ordine.php']]
    },
    {
      id: 'recupero',
      terms: ['recuperare', 'recupero', 'perso', 'non trovo', 'codice ordine', 'email ordine', 'ritrovare', 'ordine sparito', 'ordine perso'],
      text: '<p>Puoi recuperare un ordine anche senza account dalla pagina dedicata. Servono i dati richiesti, come codice ordine ed email usata nella prenotazione.</p>',
      guide: 'recuperare',
      links: [['Recupera ordine', 'recupera_ordine.php']]
    },
    {
      id: 'docente',
      terms: ['docente', 'classe', 'scuola', 'studenti', 'accompagnatori', 'visita scolastica', 'gita', 'professore', 'professoressa'],
      text: '<p>La prenotazione docente permette di indicare scuola, classe, studenti, accompagnatori, data e fascia oraria. È pensata per visite scolastiche e gruppi classe.</p>',
      guide: 'docente',
      links: [['Prenotazione docente', 'prenota_docente.php'], ['Info e tariffe', 'info.php']]
    },
    {
      id: 'portafoglio',
      terms: ['portafoglio', 'saldo', 'ricarica', 'ricaricare', 'credito', 'wallet', 'soldi', 'pagare con saldo'],
      text: '<p>Il portafoglio virtuale è nell’area personale. Puoi vedere il saldo, avviare una ricarica simulata e usare il credito per pagare se il saldo è sufficiente.</p>',
      guide: 'portafoglio',
      links: [['Area personale', 'account.php']]
    },
    {
      id: 'rimborso',
      terms: ['rimborso', 'rimborsare', 'rimborsato', 'soldi indietro', 'annullare', 'annullo', 'restituzione', 'richiesta rimborso'],
      text: '<p>Puoi richiedere il rimborso dall’area personale, se i biglietti non sono stati utilizzati. L’amministratore accetta o rifiuta la richiesta e ricevi l’esito via email.</p>',
      guide: 'rimborso',
      links: [['Area personale', 'account.php']]
    },
    {
      id: 'qr',
      terms: ['qr', 'qrcode', 'codice qr', 'scanner', 'fotocamera', 'camera', 'validazione', 'lettura', 'lettore'],
      text: '<p>Il QR code serve a controllare rapidamente il biglietto. Operatore e cassiere possono leggerlo con la fotocamera oppure inserire manualmente il codice.</p><p>Se la fotocamera non parte, controlla permessi browser e HTTPS.</p>',
      cards: [
        { title: 'Operatore', text: 'Valida ticket con QR o codice.', link: 'valida_biglietti.php' },
        { title: 'Cassiere', text: 'Cerca ordini e legge QR.', link: 'cassa.php' }
      ],
      links: [['Validazione biglietti', 'valida_biglietti.php'], ['Area cassa', 'cassa.php']]
    },
    {
      id: 'stato-biglietto',
      terms: ['valido', 'utilizzato', 'usato', 'scaduto', 'rimborsato', 'stato biglietto', 'non valido', 'non utilizzabile'],
      text: '<p>Gli stati più importanti sono:</p><ul><li><strong>Valido</strong>: utilizzabile.</li><li><strong>Utilizzato</strong>: già validato.</li><li><strong>Rimborsato</strong>: resta nello storico ma non si usa.</li><li><strong>Scaduto</strong>: data visita passata.</li></ul>',
      links: [['Area personale', 'account.php'], ['Recupera ordine', 'recupera_ordine.php']]
    },
    {
      id: 'accesso',
      terms: ['accesso', 'login', 'password', 'registrazione', 'email', 'verifica', 'account', 'non riesco ad accedere', 'credenziali', 'codice verifica'],
      text: '<p>Per accedere usa email e password. Dopo la registrazione può essere richiesta la verifica email. Se non ricordi la password, usa il recupero password.</p>',
      guide: 'accesso',
      links: [['Accedi', 'login.php'], ['Registrati', 'registrazione.php'], ['Recupera password', 'recupero_password.php']]
    },
    {
      id: 'mappa-percorsi',
      terms: ['mappa', 'sale', 'sala', 'percorso', 'percorsi', 'orientarmi', 'dove andare', 'quanto dura', 'visita breve', 'percorso studenti', 'percorso famiglia', 'percorso completo'],
      text: '<p>Nella mappa trovi le sale principali e i percorsi consigliati: breve, studenti, famiglia e completo. È utile per scegliere cosa visitare prima di prenotare.</p>',
      cards: [
        { title: 'Mappa', text: 'Sale e servizi del museo.', link: 'mappa.php' },
        { title: 'Percorso studenti', text: 'Ideale per gruppi classe.', link: 'mappa.php' },
        { title: 'Prenota', text: 'Scegli mostra e fascia.', link: 'prenota.php' }
      ],
      links: [['Apri mappa', 'mappa.php'], ['Prenota', 'prenota.php']]
    },
    {
      id: 'servizi-tariffe',
      terms: ['servizi', 'audioguida', 'visita guidata', 'catalogo', 'riduzione', 'riduzioni', 'tariffe', 'prezzi', 'costo', 'quanto costa'],
      text: '<p>Nella pagina informazioni trovi tariffe, riduzioni e servizi opzionali come audioguida, visita guidata e catalogo, se disponibili.</p>',
      links: [['Info e tariffe', 'info.php']]
    },
    {
      id: 'accessibilita',
      terms: ['accessibilita', 'testo grande', 'aumenta testo', 'animazioni', 'leggere meglio', 'vista', 'carattere', 'aa'],
      text: '<p>Usa il pulsante <strong>Aa</strong> in basso a sinistra per migliorare la leggibilità: puoi aumentare il testo e ridurre le animazioni.</p>',
      links: []
    },
    {
      id: 'ricevuta',
      terms: ['ricevuta', 'fattura', 'pdf ricevuta', 'documento pagamento', 'scarica ricevuta', 'prova pagamento'],
      text: '<p>Dal dettaglio ordine o dalla pagina biglietti puoi scaricare una ricevuta PDF con codice ordine, data, metodo di pagamento, importo e riepilogo.</p>',
      links: [['Area personale', 'account.php'], ['Recupera ordine', 'recupera_ordine.php']]
    },
    {
      id: 'dashboard-account',
      terms: ['dashboard', 'area personale', 'profilo', 'prossima visita', 'ultimi ordini', 'notifiche', 'riepilogo'],
      text: '<p>Nella dashboard personale trovi riepilogo ordini, biglietti validi, saldo, prossima visita, timeline dell’ultimo ordine e notifiche utili.</p>',
      links: [['Area personale', 'account.php']]
    },
    {
      id: 'admin',
      terms: ['admin', 'amministratore', 'gestire', 'utenti', 'tariffe', 'servizi', 'fasce', 'categorie', 'esposizioni', 'dashboard admin', 'grafici', 'statistiche'],
      text: '<p>L’area amministratore permette di gestire utenti, esposizioni, fasce orarie, tariffe, servizi, ordini, rimborsi e statistiche della dashboard.</p>',
      links: [['Area admin', 'admin.php']]
    },
    {
      id: 'cassa',
      terms: ['cassa', 'cassiere', 'pagato', 'contanti', 'pagamento in presenza', 'ordine in cassa', 'lettura cassa'],
      text: '<p>L’area cassa serve per cercare ordini, confermare pagamenti in presenza, emettere biglietti e leggere QR code quando previsto.</p>',
      links: [['Area cassa', 'cassa.php']]
    },
    {
      id: 'orari-contatti',
      terms: ['orari', 'apertura', 'chiusura', 'contatti', 'indirizzo', 'email', 'telefono', 'dove si trova', 'padova'],
      text: '<p>Nel footer e nella pagina informazioni trovi contatti, indirizzo e orari del museo. Per prenotazioni e biglietti usa invece le pagine dedicate.</p>',
      links: [['Info e tariffe', 'info.php'], ['Home', 'index.php']]
    },
    {
      id: 'problema',
      terms: ['problema', 'errore', 'non funziona', 'bloccato', 'non vedo', 'non arriva', 'mail', 'email non arriva', 'pagamento fallito', 'browser', 'rotto', 'aiuto'],
      text: '<p>Vediamo il problema più probabile. Scegli una situazione o scrivi una frase come “non trovo il biglietto”, “non arriva email”, “QR non funziona”.</p>',
      cards: problemCards,
      links: [['Recupera ordine', 'recupera_ordine.php'], ['Accedi', 'login.php'], ['Info e tariffe', 'info.php']]
    },
    {
      id: 'export-csv',
      terms: ['csv', 'export', 'esporta', 'esportazione', 'scarica dati', 'excel', 'foglio di calcolo'],
      text: '<p>In area amministratore trovi la sezione <strong>Esportazione CSV</strong>: permette di scaricare ordini, biglietti, rimborsi, utenti, esposizioni, tariffe e servizi in formato apribile con Excel.</p>',
      links: [['Vai all’export CSV', 'admin.php#admin-export']]
    },
    {
      id: 'features-sito',
      terms: ['features', 'funzionalita', 'cosa include', 'github', 'demo', 'mostrami le funzionalita'],
      text: '<p>Il sito include registrazione, login, verifica email, prenotazione, pagamento simulato, portafoglio, rimborsi, QR code, cassa, validazione, admin, PDF, dashboard, accessibilità e assistente virtuale.</p>',
      links: [['Features', 'features.php'], ['Home', 'index.php'], ['Area admin', 'admin.php']]
    }
  ];

  function getRoleIntro() {
    if (role === 'amministratore' || role === 'tester') {
      return '<p>Per il tuo ruolo posso aiutarti anche con dashboard admin, utenti, mostre, ordini, rimborsi, cassa e validazione.</p>';
    }
    if (role === 'cassiere') {
      return '<p>Per il tuo ruolo posso aiutarti con ricerca ordini, pagamenti in presenza e lettura QR.</p>';
    }
    if (role === 'operatore') {
      return '<p>Per il tuo ruolo posso aiutarti con validazione biglietti, QR code e controllo stati.</p>';
    }
    return '';
  }

  function currentPageAnswer() {
    var hint = pageHints[currentPage] || {
      text: 'In questa pagina puoi consultare le informazioni disponibili e usare i link principali del sito.',
      links: [['Home', 'index.php'], ['Esposizioni', 'esposizioni.php']]
    };
    return {
      text: '<p>' + hint.text + '</p>' + getRoleIntro(),
      links: hint.links,
      cards: [
        { title: 'Prenota', text: 'Avvia una nuova visita.', link: 'prenota.php' },
        { title: 'Ordini', text: 'Controlla o recupera biglietti.', link: isLogged ? 'account.php' : 'recupera_ordine.php' }
      ]
    };
  }

  function contextIntro() {
    var hint = pageHints[currentPage];
    var profile = isLogged
      ? '<p>Vedo che hai già effettuato l’accesso' + (role ? ' come <strong>' + escapeHtml(role) + '</strong>' : '') + '.</p>'
      : '<p>Puoi usare il sito anche senza account; con l’accesso gestisci meglio ordini, biglietti e portafoglio.</p>';
    if (hint) {
      return {
        text: '<p>Ciao! Sono l’assistente virtuale del Museo Storico Severi.</p>' + profile + '<p>' + hint.text + '</p>' + getRoleIntro() + '<p>Posso guidarti passo passo oppure portarti direttamente alla pagina giusta.</p>',
        links: hint.links
      };
    }
    return {
      text: '<p>Ciao! Sono l’assistente virtuale del Museo Storico Severi.</p>' + profile + getRoleIntro() + '<p>Posso aiutarti con prenotazioni, pagamenti, biglietti, QR, rimborsi, portafoglio, account e problemi frequenti.</p>',
      links: [['Home', 'index.php'], ['Esposizioni', 'esposizioni.php'], ['Area riservata', isLogged ? 'account.php' : 'login.php']]
    };
  }

  function getSuggestions() {
    var base = [];
    if (pageHints[currentPage] && pageHints[currentPage].suggestions) {
      base = pageHints[currentPage].suggestions.slice();
    } else if (roleSuggestions[role]) {
      base = roleSuggestions[role].slice();
    } else {
      base = defaultSuggestions.slice();
    }

    if (roleSuggestions[role] && currentPage !== 'admin.php' && currentPage !== 'cassa.php' && currentPage !== 'valida_biglietti.php') {
      base = base.concat(roleSuggestions[role]);
    }

    if (base.indexOf('❓ Cosa posso fare qui?') === -1 && base.indexOf('🧭 Cosa posso fare qui?') === -1) {
      base.push('❓ Cosa posso fare qui?');
    }

    var seen = {};
    return base.filter(function (item) {
      var key = normalize(cleanLabel(item));
      if (seen[key]) return false;
      seen[key] = true;
      return true;
    }).slice(0, MAX_SUGGESTIONS);
  }

  function showInitialMessage() {
    messagesBox.innerHTML = '';
    var intro = contextIntro();
    addMessage('bot', intro.text, intro.links, false);
    saveMessages();
  }

  function renderSuggestions() {
    suggestionsBox.innerHTML = '';
    getSuggestions().forEach(function (question) {
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'msai-chip';
      button.textContent = question;
      button.addEventListener('click', function () {
        ask(cleanLabel(question));
      });
      suggestionsBox.appendChild(button);
    });
  }

  function findGuide(question) {
    var clean = normalize(question);
    if (clean.indexOf('docente') !== -1 || clean.indexOf('classe') !== -1) return guides.docente;
    if (clean.indexOf('rimbor') !== -1 || clean.indexOf('annull') !== -1) return guides.rimborso;
    if (clean.indexOf('portafoglio') !== -1 || clean.indexOf('saldo') !== -1 || clean.indexOf('ricaric') !== -1) return guides.portafoglio;
    if (clean.indexOf('recuper') !== -1 || clean.indexOf('perso') !== -1) return guides.recuperare;
    if (clean.indexOf('bigliett') !== -1 || clean.indexOf('pdf') !== -1 || clean.indexOf('ricevuta') !== -1) return guides.biglietti;
    if (clean.indexOf('login') !== -1 || clean.indexOf('access') !== -1 || clean.indexOf('password') !== -1) return guides.accesso;
    return guides.prenotare;
  }

  function answerFromGuide(question) {
    var guide = findGuide(question);
    return {
      text: '<p><strong>' + escapeHtml(guide.title) + '</strong></p>' + guide.text,
      links: guide.links
    };
  }

  function findAnswer(question) {
    var cleanQuestion = normalize(question);

    if (cleanQuestion.indexOf('guidami') !== -1 || cleanQuestion.indexOf('passo passo') !== -1) {
      return answerFromGuide(question);
    }

    var best = null;
    var bestScore = 0;
    answers.forEach(function (answer) {
      var score = 0;
      answer.terms.forEach(function (term) {
        var cleanTerm = normalize(term);
        if (!cleanTerm) return;
        if (cleanQuestion.indexOf(cleanTerm) !== -1) score += cleanTerm.length > 8 ? 4 : 2;
        cleanTerm.split(' ').forEach(function (part) {
          if (part.length > 3 && cleanQuestion.indexOf(part) !== -1) score += 1;
        });
      });
      if (score > bestScore) { best = answer; bestScore = score; }
    });

    if (best && bestScore > 0) {
      if (best.special === 'current-page') return currentPageAnswer();
      if (best.guide && cleanQuestion.indexOf('spiegami') !== -1) {
        return answerFromGuide(best.id);
      }
      return best;
    }

    return {
      id: 'fallback',
      text: '<p>Non sono sicuro di aver capito. Posso aiutarti con <strong>prenotazioni</strong>, <strong>pagamenti</strong>, <strong>biglietti</strong>, <strong>rimborsi</strong>, <strong>QR code</strong>, <strong>portafoglio</strong>, <strong>account</strong> e <strong>problemi frequenti</strong>.</p><p>Puoi scrivere, per esempio: “non trovo il biglietto”, “voglio un rimborso”, “come uso il QR”, “come ricarico il saldo”.</p>',
      cards: problemCards.slice(0, 2),
      links: [['Prenota', 'prenota.php'], ['Recupera ordine', 'recupera_ordine.php'], ['Area riservata', isLogged ? 'account.php' : 'login.php']]
    };
  }

  function ask(question) {
    var trimmed = String(question || '').trim();
    if (!trimmed) return;
    addMessage('user', trimmed);
    var answer = findAnswer(trimmed);
    window.setTimeout(function () {
      addMessage('bot', answer.text, answer.links, true, answer.cards);
      renderSuggestions();
    }, 120);
  }

  function openPanel() {
    panel.hidden = false;
    openButton.setAttribute('aria-expanded', 'true');
    openButton.style.display = 'none';
    renderSuggestions();
    window.setTimeout(function () { input.focus(); }, 80);
  }

  function closePanel() {
    panel.hidden = true;
    openButton.setAttribute('aria-expanded', 'false');
    openButton.style.display = '';
    openButton.focus();
  }

  openButton.addEventListener('click', openPanel);
  closeButton.addEventListener('click', closePanel);
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !panel.hidden) closePanel(); });
  form.addEventListener('submit', function (event) { event.preventDefault(); ask(input.value); input.value = ''; });
  resetButton.addEventListener('click', function () { try { localStorage.removeItem(storageKey); } catch (error) {} showInitialMessage(); renderSuggestions(); input.focus(); });

  renderSuggestions();
  if (!restoreMessages()) showInitialMessage();
})();
