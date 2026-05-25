<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Features';
$pageTitleSeo = 'Features | Museo Storico Severi';
$pageDescription = 'Tutte le features del Museo Storico Severi: prenotazioni, pagamenti simulati, QR code, portafoglio, rimborsi, dashboard, admin, cassa, export CSV, mappa, assistente virtuale, accessibilità, SEO e sicurezza.';

$featureGroups = [
    ['icon' => '🎟️', 'label' => 'Visitatore', 'title' => 'Prenotazione visitatore', 'text' => 'Percorso guidato con mostra, data, fascia oraria, categorie, quantità, servizi opzionali e riepilogo.', 'items' => ['Mostre', 'Fasce orarie', 'Categorie', 'Servizi'], 'tone' => 'blue'],
    ['icon' => '🏫', 'label' => 'Scuole', 'title' => 'Prenotazione classe', 'text' => 'Flusso dedicato ai docenti con dati referente, scuola, classe, studenti, accompagnatori e pagamento.', 'items' => ['Referente', 'Scuola', 'Classe', 'Gruppo'], 'tone' => 'teal'],
    ['icon' => '💳', 'label' => 'Pagamenti', 'title' => 'Pagamenti simulati', 'text' => 'Carta, PayPal, contanti in cassa e saldo del portafoglio per simulare scenari reali.', 'items' => ['Carta', 'PayPal', 'Contanti', 'Saldo'], 'tone' => 'gold'],
    ['icon' => '💰', 'label' => 'Wallet', 'title' => 'Portafoglio virtuale', 'text' => 'Saldo utente visibile in dashboard, ricarica simulata e pagamento dei biglietti con credito interno.', 'items' => ['Saldo', 'Ricarica', 'Pagamento', 'Dashboard'], 'tone' => 'violet'],
    ['icon' => '👤', 'label' => 'Account', 'title' => 'Area personale moderna', 'text' => 'Dashboard utente con prossima visita, statistiche, azioni rapide, profilo, sicurezza, ordini e rimborsi.', 'items' => ['Profilo', 'Ordini', 'Sicurezza', 'Rimborsi'], 'tone' => 'navy'],
    ['icon' => '📱', 'label' => 'Ticket', 'title' => 'Biglietti digitali', 'text' => 'Card biglietto, codice univoco, stato visivo, QR code, PDF e accesso rapido al dettaglio ordine.', 'items' => ['QR', 'PDF', 'Stato', 'Codice'], 'tone' => 'rose'],
    ['icon' => '🧾', 'label' => 'Documenti', 'title' => 'Ricevute e riepiloghi', 'text' => 'Ricevuta PDF simulata con dati ordine, pagamento, importo, biglietti e servizi collegati.', 'items' => ['Ricevuta', 'Ordine', 'Importo', 'Servizi'], 'tone' => 'orange'],
    ['icon' => '↩️', 'label' => 'Rimborsi', 'title' => 'Gestione rimborsi', 'text' => 'Richiesta rimborso dall’account, valutazione admin, email di esito e blocco dei biglietti rimborsati.', 'items' => ['Richiesta', 'Esito', 'Email', 'Blocco'], 'tone' => 'green'],
    ['icon' => '🏛️', 'label' => 'Admin', 'title' => 'Dashboard amministratore', 'text' => 'Statistiche, grafici, gestione esposizioni, tariffe, categorie, servizi, utenti, rimborsi ed esportazioni.', 'items' => ['Statistiche', 'Grafici', 'CRUD', 'Export'], 'tone' => 'blue'],
    ['icon' => '✅', 'label' => 'Back office', 'title' => 'Cassa e validazione', 'text' => 'Cassiere e operatore possono cercare ordini, registrare pagamenti, leggere QR e validare ticket.', 'items' => ['Cassa', 'QR scanner', 'Validazione', 'Controlli'], 'tone' => 'teal'],
    ['icon' => '📊', 'label' => 'Dati', 'title' => 'Export CSV', 'text' => 'Esportazione per ordini, biglietti, rimborsi, utenti, esposizioni, tariffe e servizi.', 'items' => ['Ordini', 'Biglietti', 'Utenti', 'Rimborsi'], 'tone' => 'gold'],
    ['icon' => '🔎', 'label' => 'Qualità', 'title' => 'Controlli dati admin', 'text' => 'Pannello con avvisi su rimborsi, ordini in attesa, esposizioni, fasce orarie e contenuti da completare.', 'items' => ['Avvisi', 'Checklist', 'Manutenzione', 'Dati'], 'tone' => 'violet'],
    ['icon' => '🧭', 'label' => 'Museo', 'title' => 'Mappa e percorso guidato', 'text' => 'Sale raccontate con tappe, focus, domande guida, tempi consigliati e percorsi tematici.', 'items' => ['Sale', 'Percorsi', 'Focus', 'Domande'], 'tone' => 'cyan'],
    ['icon' => '💬', 'label' => 'Supporto', 'title' => 'Assistente virtuale', 'text' => 'Chat senza API esterne con risposte contestuali, guide passo passo, problemi frequenti e link rapidi.', 'items' => ['FAQ', 'Contesto', 'Guide', 'Link'], 'tone' => 'navy'],
    ['icon' => '✉️', 'label' => 'Email', 'title' => 'Comunicazioni HTML', 'text' => 'Template email per verifica account, recupero password, conferma ordine, ricarica ed esito rimborso.', 'items' => ['Verifica', 'Password', 'Ordini', 'Rimborsi'], 'tone' => 'orange'],
    ['icon' => '♿', 'label' => 'Accessibilità', 'title' => 'Strumenti accessibili', 'text' => 'Testo aumentabile, riduzione animazioni, etichette nei form, landmark, contrasto e navigazione più chiara.', 'items' => ['Testo', 'Motion', 'Label', 'WAVE'], 'tone' => 'green'],
    ['icon' => '🚀', 'label' => 'UX', 'title' => 'Micro-interazioni', 'text' => 'Card animate, stati vuoti curati, toast moderni, pulsanti più leggibili e feedback immediato.', 'items' => ['Toast', 'Hover', 'Empty state', 'Feedback'], 'tone' => 'rose'],
    ['icon' => '🖨️', 'label' => 'Stampa', 'title' => 'Stampa ottimizzata', 'text' => 'Regole print per rendere più pulita la stampa da browser di pagine, ordini e contenuti utili.', 'items' => ['Print CSS', 'Ordini', 'Ticket', 'Pulizia'], 'tone' => 'slate'],
    ['icon' => '🔐', 'label' => 'Sicurezza', 'title' => 'SEO e sicurezza', 'text' => 'Canonical, sitemap, robots, header di sicurezza, CSP, cookie sicuri e struttura pronta per Google.', 'items' => ['CSP', 'Sitemap', 'Canonical', 'Cookie'], 'tone' => 'blue'],
    ['icon' => '📚', 'label' => 'Presentazione', 'title' => 'Documentazione completa', 'text' => 'README, SQL unico, schema ER, schema logico, pagina Features e struttura pensata per GitHub.', 'items' => ['README', 'SQL', 'ER', 'GitHub'], 'tone' => 'teal'],
];

$flow = [
    ['01', 'Scelta mostra', 'L’utente esplora esposizioni e filtri.'],
    ['02', 'Prenotazione', 'Seleziona data, orario e biglietti.'],
    ['03', 'Pagamento', 'Simula il pagamento o passa dalla cassa.'],
    ['04', 'Ingresso', 'Mostra QR code, ricevuta e ticket digitale.'],
];

include __DIR__ . '/header.php';
?>

<style>
  .features-page{background:#f3f8fc;color:#102744}.features-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#102744 0%,#174468 55%,#0c1e32 100%);color:#fffdf5}.features-hero:before{content:"";position:absolute;inset:0;background-image:linear-gradient(135deg,rgba(255,255,255,.055) 8.33%,transparent 8.33%,transparent 50%,rgba(255,255,255,.055) 50%,rgba(255,255,255,.055) 58.33%,transparent 58.33%,transparent 100%);background-size:18px 18px;opacity:.55}.features-hero:after{content:"";position:absolute;width:420px;height:420px;right:-120px;top:-180px;border-radius:999px;background:radial-gradient(circle,rgba(142,197,232,.30),transparent 65%)}.features-wrap{max-width:1180px;margin:0 auto;padding-left:24px;padding-right:24px;position:relative;z-index:1}.features-hero-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr);gap:28px;align-items:center;padding:54px 0 42px}.features-kicker{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.22em;color:#9bd1f0;margin:0 0 12px}.features-title{font-family:Georgia,serif;font-size:clamp(38px,5vw,64px);line-height:1.02;margin:0;color:#fffdf5}.features-lead{max-width:660px;color:#e7f4fc;font-size:18px;line-height:1.7;margin:18px 0 0}.features-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}.features-btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;border-radius:12px;padding:0 18px;text-decoration:none;text-transform:uppercase;letter-spacing:.04em;font-weight:900;font-size:13px}.features-btn-primary{background:#8ec5e8;color:#102744}.features-btn-ghost{border:1px solid rgba(255,255,255,.42);color:#fffdf5;background:rgba(255,255,255,.06)}.features-badges{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}.features-badges span{display:inline-flex;align-items:center;min-height:30px;border-radius:999px;padding:0 12px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);color:#fffdf5;font-size:12px;font-weight:900}.features-spotlight{display:grid;grid-template-columns:1fr;gap:12px}.features-mini{border-radius:22px;padding:18px;background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.18);box-shadow:0 18px 42px rgba(0,0,0,.12)}.features-mini span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.16em;color:#a8d8f2;font-weight:900;margin-bottom:8px}.features-mini strong{display:block;font-family:Georgia,serif;font-size:25px;line-height:1.12;color:#fff}.features-mini p{margin:7px 0 0;color:#d9edf7;font-size:14px}.features-flow{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:0;position:relative;z-index:2}.features-flow-card{border-radius:20px;background:#fff;border:1px solid #d7e9f5;padding:16px;box-shadow:0 12px 28px rgba(16,39,68,.08)}.features-flow-num{width:38px;height:38px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;background:#e2f4ff;color:#15537a;font-family:Georgia,serif;font-weight:900;margin-bottom:10px}.features-flow-card h2{font-family:Georgia,serif;font-size:20px;margin:0 0 6px}.features-flow-card p{font-size:14px;line-height:1.45;color:#42556b;margin:0}.features-intro{text-align:center;max-width:760px;margin:48px auto 28px}.features-intro h2{font-family:Georgia,serif;font-size:clamp(30px,3.2vw,44px);line-height:1.1;margin:0;color:#102744}.features-intro p{color:#42556b;line-height:1.65;margin:12px 0 0}.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.feature-card{--accent:#2d73aa;--soft:#eef8ff;position:relative;overflow:hidden;border-radius:24px;padding:20px;background:linear-gradient(180deg,#fff 0%,var(--soft) 100%);border:1px solid #dcebf4;box-shadow:0 10px 28px rgba(16,39,68,.07);transition:transform .18s ease,box-shadow .18s ease}.feature-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:6px;background:var(--accent)}.feature-card:hover{transform:translateY(-3px);box-shadow:0 18px 40px rgba(16,39,68,.12)}.feature-top{display:flex;align-items:center;justify-content:space-between;gap:14px}.feature-icon{width:46px;height:46px;border-radius:16px;background:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:23px;box-shadow:0 10px 22px rgba(16,39,68,.08)}.feature-label{font-size:11px;text-transform:uppercase;letter-spacing:.14em;font-weight:900;color:#346280}.feature-card h3{font-family:Georgia,serif;font-size:25px;line-height:1.12;margin:18px 0 9px;color:#102744}.feature-card p{font-size:14px;line-height:1.55;color:#33465d;margin:0}.feature-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}.feature-tags span{border-radius:999px;background:rgba(255,255,255,.78);border:1px solid rgba(16,39,68,.08);padding:6px 10px;font-size:12px;font-weight:800;color:#102744}.tone-blue{--accent:#2d7db6;--soft:#eef8ff}.tone-teal{--accent:#168d86;--soft:#effdfa}.tone-gold{--accent:#c78b1f;--soft:#fff7e8}.tone-violet{--accent:#735cc6;--soft:#f5f1ff}.tone-rose{--accent:#d34f72;--soft:#fff1f5}.tone-navy{--accent:#102744;--soft:#eef5fb}.tone-green{--accent:#2e9d65;--soft:#f0fff6}.tone-orange{--accent:#d86f25;--soft:#fff4ea}.tone-cyan{--accent:#1788a6;--soft:#eefcff}.tone-slate{--accent:#506070;--soft:#f6f8fa}.features-final{margin-top:42px;border-radius:28px;background:linear-gradient(135deg,#102744,#1e5986);color:#fffdf5;padding:28px;display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;box-shadow:0 18px 42px rgba(16,39,68,.16)}.features-final h2{font-family:Georgia,serif;font-size:32px;line-height:1.15;margin:0}.features-final p{margin:10px 0 0;color:#e6f4fb;line-height:1.6}.features-final-actions{display:flex;gap:12px;flex-wrap:wrap}.features-main{padding:42px 0 56px;background:linear-gradient(180deg,#f4f9fd 0%,#ffffff 34%);border-top:1px solid rgba(142,197,232,.18)}@media(max-width:960px){.features-hero-grid{grid-template-columns:1fr}.features-spotlight{grid-template-columns:repeat(3,1fr)}.features-flow,.features-grid{grid-template-columns:repeat(2,1fr)}.features-final{grid-template-columns:1fr}}@media(max-width:640px){
  .features-wrap{padding-left:24px;padding-right:24px;width:100%;max-width:100%}
  .features-hero{background:linear-gradient(180deg,#102744 0%,#173e61 100%)}
  .features-hero:after{width:220px;height:220px;right:-90px;top:-90px}
  .features-hero-grid{padding:34px 0 32px;gap:22px}
  .features-kicker{font-size:10px;letter-spacing:.18em;margin-bottom:10px}
  .features-title{font-size:34px;line-height:1.08}
  .features-lead{font-size:15px;line-height:1.6;margin-top:14px;max-width:100%}
  .features-actions{display:grid;grid-template-columns:1fr;gap:10px;margin-top:20px}
  .features-btn{width:100%;min-height:42px;font-size:12px}
  .features-spotlight{grid-template-columns:1fr;gap:12px;width:100%;margin:0 auto}
  .features-mini{border-radius:18px;padding:16px;background:rgba(255,255,255,.10);width:100%;max-width:100%;box-shadow:none}
  .features-mini span{font-size:10px;margin-bottom:5px}
  .features-mini strong{font-size:22px;line-height:1.15}
  .features-mini p{font-size:13px;line-height:1.4}
  .features-main{padding-top:30px}
  .features-flow,.features-grid{grid-template-columns:1fr;gap:14px}
  .features-flow-card{padding:16px;border-radius:18px}
  .features-intro{margin:34px auto 22px;text-align:left}
  .features-intro h2{font-size:30px}
  .feature-card{border-radius:20px;padding:18px}
  .features-final{padding:22px;border-radius:22px}
  .features-final h2{font-size:26px}
  .features-final-actions{flex-direction:column}
}


  @media(max-width:640px){
    .features-hero .features-wrap{
      width:calc(100% - 56px);
      max-width:440px;
      padding-left:0!important;
      padding-right:0!important;
      margin-left:auto!important;
      margin-right:auto!important;
    }
    .features-hero-grid{
      padding-top:34px;
      padding-bottom:34px;
    }
    .features-lead{
      max-width:100%;
    }
    .features-actions,
    .features-spotlight{
      width:100%;
      max-width:100%;
    }
    .features-mini{
      width:100%;
      margin-left:0;
      margin-right:0;
    }
  }

  @media(max-width:380px){
    .features-hero .features-wrap{
      width:calc(100% - 44px);
    }
  }
</style>

<div class="features-page">
  <section class="features-hero">
    <div class="features-wrap features-hero-grid">
      <div>
        <p class="features-kicker">Museo Storico Severi</p>
        <h1 class="features-title">Features del sito</h1>
        <p class="features-lead">Tutte le funzioni integrate nel sito: prenotazioni, pagamenti, portafoglio, rimborsi, QR code, cassa, admin, export CSV, email, mappa, accessibilità e assistente virtuale.</p>
        <div class="features-actions">
          <a href="<?= SITE_URL ?>/prenota.php" class="features-btn features-btn-primary">Prova la prenotazione</a>
          <a href="<?= SITE_URL ?>/mappa.php" class="features-btn features-btn-ghost">Mappa e percorso</a>
        </div>
      </div>

      <div class="features-spotlight" aria-label="Aree principali">
        <article class="features-mini">
          <span>Esperienza utente</span>
          <strong>Prenota, paga, scarica</strong>
          <p>Un flusso completo dall’esposizione al biglietto.</p>
        </article>
        <article class="features-mini">
          <span>Back office</span>
          <strong>Admin, cassa, operatori</strong>
          <p>Ruoli diversi per gestire il museo in modo realistico.</p>
        </article>
        <article class="features-mini">
          <span>Funzioni extra</span>
          <strong>QR, CSV, email, assistente</strong>
          <p>Strumenti moderni, leggeri e integrati nel sito.</p>
        </article>
      </div>
    </div>
  </section>

  <main class="features-main">
    <div class="features-wrap">
      <section class="features-flow" aria-label="Flusso principale del sito">
        <?php foreach ($flow as $step): ?>
          <article class="features-flow-card">
            <span class="features-flow-num"><?= clean($step[0]) ?></span>
            <h2><?= clean($step[1]) ?></h2>
            <p><?= clean($step[2]) ?></p>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="features-intro">
        <p class="features-kicker" style="color:#2d73aa;margin-bottom:8px;">Dentro il progetto</p>
        <h2>Tutte le features, divise per area</h2>
        <p>Una panoramica completa di ciò che il sito gestisce: esperienza utente, didattica, biglietteria, amministrazione, qualità e presentazione del progetto.</p>
      </section>

      <section class="features-grid" aria-label="Elenco delle features">
        <?php foreach ($featureGroups as $group): ?>
          <article class="feature-card tone-<?= clean($group['tone']) ?>">
            <div class="feature-top">
              <span class="feature-icon" aria-hidden="true"><?= clean($group['icon']) ?></span>
              <span class="feature-label"><?= clean($group['label']) ?></span>
            </div>
            <h3><?= clean($group['title']) ?></h3>
            <p><?= clean($group['text']) ?></p>
            <div class="feature-tags">
              <?php foreach ($group['items'] as $item): ?>
                <span><?= clean($item) ?></span>
              <?php endforeach; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>

      <section class="features-final">
        <div>
          <p class="features-kicker" style="margin-bottom:8px;">In sintesi</p>
          <h2>Un progetto didattico con logiche da gestionale vero.</h2>
          <p>Il sito copre tutto il percorso: scoperta della mostra, prenotazione, pagamento simulato, biglietti, ricevuta, QR code, area personale e gestione amministrativa.</p>
        </div>
        <div class="features-final-actions">
          <a href="<?= SITE_URL ?>/esposizioni.php" class="features-btn features-btn-primary">Vedi le mostre</a>
          <a href="<?= SITE_URL ?>/admin.php" class="features-btn features-btn-ghost">Area admin</a>
        </div>
      </section>
    </div>
  </main>
</div>

<?php include __DIR__ . '/footer.php'; ?>
