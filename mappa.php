<?php
$pageTitle = 'Mappa e tour';
$pageTitleSeo = 'Mappa e tour virtuale | Museo Storico Severi';
$pageDescription = 'Esplora la mappa e il tour virtuale del Museo Storico Severi: sale espositive, percorsi consigliati, servizi e indicazioni per organizzare la visita.';
include __DIR__ . '/header.php';

$sale = [
    ['num'=>'01','titolo'=>'Preistoria','tag'=>'Origini dell’uomo','tempo'=>'15 min','descrizione'=>'Un percorso dedicato alle prime comunità umane, agli strumenti, alle abitazioni e alle trasformazioni della vita quotidiana.','tour'=>'Osserva strumenti, tracce di vita quotidiana e prime forme di organizzazione sociale. È la sala ideale per iniziare il viaggio nella storia.','icona'=>'🪨','area'=>'Ingresso nord','focus'=>'Strumenti, fuoco, vita quotidiana'],
    ['num'=>'02','titolo'=>'Antico Egitto','tag'=>'Civiltà antiche','tempo'=>'20 min','descrizione'=>'Faraoni, simboli, scrittura e vita religiosa in una delle civiltà più affascinanti della storia.','tour'=>'Qui il visitatore incontra geroglifici, rituali, potere del faraone e simboli della vita oltre la morte.','icona'=>'𓂀','area'=>'Ala ovest','focus'=>'Geroglifici, faraoni, riti'],
    ['num'=>'03','titolo'=>'Impero Romano','tag'=>'Mondo classico','tempo'=>'20 min','descrizione'=>'Espansione, città, strade, diritto e cultura materiale dell’età romana.','tour'=>'La sala mostra come Roma abbia costruito città, infrastrutture e un modello culturale destinato a lasciare un segno profondo.','icona'=>'🏛️','area'=>'Galleria centrale','focus'=>'Città, strade, diritto'],
    ['num'=>'04','titolo'=>'Medioevo Europeo','tag'=>'Castelli e società','tempo'=>'18 min','descrizione'=>'Feudi, città, commerci, castelli e trasformazioni sociali dell’Europa medievale.','tour'=>'Il visitatore attraversa il mondo dei castelli, dei borghi, dei commerci e delle nuove forme di vita urbana.','icona'=>'⚔️','area'=>'Ala est','focus'=>'Castelli, borghi, società'],
    ['num'=>'05','titolo'=>'Rinascimento Italiano','tag'=>'Arte e scienza','tempo'=>'25 min','descrizione'=>'Arte, innovazione, prospettiva, scoperte e centralità dell’uomo nel pensiero rinascimentale.','tour'=>'Questa sala mette al centro creatività, scienza, arte e nuova fiducia nelle capacità dell’uomo.','icona'=>'🎨','area'=>'Sala grande','focus'=>'Arte, prospettiva, invenzioni'],
    ['num'=>'06','titolo'=>'Arte Contemporanea','tag'=>'Linguaggi moderni','tempo'=>'18 min','descrizione'=>'Installazioni, materiali, nuove forme espressive e dialogo tra arte, società e tecnologia.','tour'=>'La visita si conclude con linguaggi artistici contemporanei, installazioni e collegamenti tra arte, società e tecnologia.','icona'=>'🖼️','area'=>'Uscita sud','focus'=>'Installazioni, materiali, tecnologia'],
];

$percorsi = [
    ['nome'=>'Percorso breve','durata'=>'45 minuti','sale'=>'Preistoria, Egitto, Roma','target'=>'Ideale per una visita rapida e ordinata.','icona'=>'⏱️'],
    ['nome'=>'Percorso studenti','durata'=>'90 minuti','sale'=>'Preistoria, Medioevo, Rinascimento','target'=>'Pensato per classi e gruppi scolastici.','icona'=>'👩‍🏫'],
    ['nome'=>'Percorso famiglia','durata'=>'60 minuti','sale'=>'Egitto, Roma, Arte contemporanea','target'=>'Adatto a bambini e accompagnatori.','icona'=>'👨‍👩‍👧'],
    ['nome'=>'Percorso completo','durata'=>'2 ore','sale'=>'Tutte le sale','target'=>'Per chi vuole vivere l’intero museo.','icona'=>'🏛️'],
];
?>

<style nonce="<?= cspNonce() ?>">
  .mappa-page { background: #f3f8fc; color: #102744; }
  .mappa-wrap { width: min(1180px, calc(100% - 32px)); margin: 0 auto; }
  .mappa-breadcrumb { background: #eaf5fd; border-bottom: 1px solid rgba(142,197,232,.55); padding: .8rem 0; font-size: .9rem; }
  .mappa-breadcrumb a { color: #366f9e; text-decoration: none; }
  .mappa-breadcrumb span { margin: 0 .45rem; color: #8aa7bc; }
  .mappa-hero, .tour-box {
    position: relative; overflow: hidden;
    background: radial-gradient(circle at top right, rgba(142,197,232,.24), transparent 34%), linear-gradient(135deg, #102744 0%, #1b4167 58%, #102744 100%);
    color: #fffdf5;
  }
  .mappa-hero::after, .tour-box::after {
    content: ""; position: absolute; inset: 0; pointer-events: none;
    background-image: linear-gradient(135deg, rgba(255,255,255,.06) 8.33%, transparent 8.33%, transparent 50%, rgba(255,255,255,.06) 50%, rgba(255,255,255,.06) 58.33%, transparent 58.33%, transparent 100%);
    background-size: 18px 18px; opacity: .2;
  }
  .mappa-hero > *, .tour-box > * { position: relative; z-index: 1; }
  .mappa-hero { padding: 4rem 0 4.5rem; }
  .mappa-eyebrow { margin: 0 0 .8rem; color: #8ec5e8; text-transform: uppercase; letter-spacing: .22em; font-size: .78rem; font-weight: 900; }
  .mappa-title { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2.4rem, 5vw, 4.4rem); line-height: .98; font-weight: 900; }
  .mappa-lead { max-width: 760px; margin: 1.25rem 0 0; color: rgba(255,253,245,.84); font-size: 1.08rem; line-height: 1.7; }
  .mappa-actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 1.8rem; }
  .mappa-btn { display: inline-flex; justify-content: center; align-items: center; min-height: 46px; border-radius: .85rem; padding: .75rem 1.1rem; font-weight: 900; text-decoration: none; text-transform: uppercase; letter-spacing: .06em; font-size: .82rem; }
  .mappa-btn-primary { background: #8ec5e8; color: #102744; }
  .mappa-btn-secondary { border: 1px solid rgba(142,197,232,.75); color: #bfe3f7; }
  .mappa-main { padding: 3rem 0 4rem; }
  .tour-box { border-radius: 1.65rem; padding: clamp(1.1rem, 2.4vw, 2rem); box-shadow: 0 24px 55px rgba(16,39,68,.18); }
  .tour-head { display: grid; grid-template-columns: minmax(0,1fr) 260px; gap: 1rem; align-items: end; margin-bottom: 1.4rem; }
  .tour-head h2 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2rem, 3vw, 3rem); line-height: 1; }
  .tour-head p { margin: .55rem 0 0; color: rgba(255,253,245,.72); line-height: 1.55; }
  .tour-progress-text { display: flex; justify-content: space-between; gap: 1rem; color: rgba(255,253,245,.72); font-size: .78rem; font-weight: 800; margin-bottom: .45rem; }
  .tour-meter { height: .55rem; border-radius: 999px; background: rgba(255,255,255,.18); overflow: hidden; }
  .tour-meter span { display: block; height: 100%; width: 16.66%; background: linear-gradient(90deg,#8ec5e8,#d7a84f); border-radius: inherit; transition: width .22s ease; }
  .tour-layout { display: grid; grid-template-columns: minmax(360px, 42%) minmax(0, 1fr); gap: 1.25rem; align-items: stretch; }
  .tour-stage, .tour-nav { border: 1px solid rgba(255,255,255,.16); border-radius: 1.35rem; background: rgba(255,255,255,.075); box-shadow: inset 0 1px 0 rgba(255,255,255,.08); }
  .tour-stage { padding: clamp(1.1rem, 2vw, 1.55rem); display: flex; flex-direction: column; min-height: 470px; }
  .tour-meta { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
  .tour-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: .38rem .75rem; background: rgba(255,255,255,.14); color: #fffdf5; font-size: .76rem; font-weight: 900; letter-spacing: .04em; }
  .tour-visual { min-height: 150px; border-radius: 1.2rem; border: 1px solid rgba(142,197,232,.24); background: radial-gradient(circle at center, rgba(142,197,232,.28), transparent 58%), rgba(255,255,255,.07); display: grid; place-items: center; margin-bottom: 1.2rem; }
  .tour-visual span { font-size: 4.8rem; filter: drop-shadow(0 18px 22px rgba(0,0,0,.28)); }
  .tour-tag { margin: 0 0 .65rem; color: #8ec5e8; text-transform: uppercase; letter-spacing: .22em; font-size: .74rem; font-weight: 900; }
  .tour-stage h3 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(2rem, 3vw, 3.2rem); line-height: 1; }
  .tour-description { margin: 1rem 0 0; color: rgba(255,253,245,.84); line-height: 1.65; font-size: 1rem; }
  .tour-facts { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: .75rem; margin-top: auto; padding-top: 1.4rem; }
  .tour-fact { border: 1px solid rgba(255,255,255,.16); border-radius: 1rem; background: rgba(255,255,255,.08); padding: .85rem; min-width: 0; }
  .tour-fact small { display: block; color: rgba(255,253,245,.62); text-transform: uppercase; letter-spacing: .12em; font-size: .66rem; }
  .tour-fact strong { display: block; margin-top: .3rem; color: #fffdf5; font-size: .9rem; line-height: 1.28; }
  .tour-nav { padding: 1rem; display: flex; flex-direction: column; }
  .tour-list { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .75rem; }
  .tour-room { display: grid; grid-template-columns: auto 1fr auto; gap: .72rem; align-items: center; min-width: 0; border: 1px solid rgba(142,197,232,.30); border-radius: 1rem; padding: .85rem; background: rgba(255,255,255,.08); color: #fffdf5; text-align: left; cursor: pointer; transition: transform .16s ease, background .16s ease, border-color .16s ease; }
  .tour-room:hover, .tour-room:focus-visible, .tour-room.is-active { transform: translateY(-1px); background: rgba(142,197,232,.18); border-color: rgba(142,197,232,.82); outline: none; }
  .tour-num { width: 2.55rem; height: 2.55rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .9rem; background: #8ec5e8; color: #102744; font-weight: 950; }
  .tour-room-title { display:block; font-weight: 950; line-height: 1.15; }
  .tour-room-sub { display:block; color: rgba(255,253,245,.68); font-size: .76rem; margin-top: .22rem; }
  .tour-room-icon { font-size: 1.42rem; }
  .tour-buttons { margin-top: auto; padding-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
  .tour-control { min-height: 46px; border-radius: .9rem; border: 1px solid rgba(142,197,232,.7); background: transparent; color: #8ec5e8; font-weight: 900; cursor: pointer; }
  .tour-control-next { background: #8ec5e8; color: #102744; }
  .section-card { margin-top: 2rem; border: 1px solid rgba(142,197,232,.28); border-radius: 1.45rem; background: #fff; box-shadow: 0 12px 32px rgba(16,39,68,.07); padding: clamp(1.1rem, 2vw, 1.75rem); }
  .section-head { display: flex; justify-content: space-between; gap: 1rem; align-items: end; margin-bottom: 1.4rem; }
  .section-head h2 { margin: .2rem 0 0; font-family: Georgia, 'Times New Roman', serif; font-size: clamp(1.8rem, 3vw, 2.5rem); }
  .section-head p { margin: .45rem 0 0; color: #5a6b7c; max-width: 720px; line-height: 1.55; }
  .duration-badge { border: 1px solid rgba(142,197,232,.4); background: #f3f8fc; border-radius: 1rem; padding: .75rem 1rem; min-width: 150px; }
  .duration-badge small { display:block; text-transform:uppercase; color:#667; font-size:.7rem; letter-spacing:.12em; }
  .duration-badge strong { font-family: Georgia, 'Times New Roman', serif; font-size: 1.45rem; }
  .map-line { height: 3px; background: linear-gradient(90deg,#8ec5e8,#2f75a8,#102744); border-radius: 999px; margin-bottom: 1.3rem; }
  .rooms-grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 1rem; }
  .room-card { border: 1px solid rgba(142,197,232,.35); border-radius: 1.15rem; background: linear-gradient(180deg,#fff,#f8fcff); padding: 1.1rem; min-height: 210px; box-shadow: 0 12px 28px rgba(16,39,68,.08); }
  .room-top { display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; }
  .room-icon { font-size: 2rem; }
  .room-card .tag { margin: 1rem 0 .25rem; color:#2f75a8; text-transform:uppercase; letter-spacing:.16em; font-size:.72rem; font-weight:900; }
  .room-card h3 { margin:0; font-family: Georgia, 'Times New Roman', serif; font-size:1.55rem; }
  .room-card p { color:#5a6b7c; line-height:1.52; font-size:.92rem; }
  .room-data { display:grid; grid-template-columns:1fr 1fr; gap:.65rem; margin-top:1rem; }
  .room-data div { background:#f3f8fc; border-radius:.85rem; padding:.65rem; }
  .room-data small { display:block; color:#667; }
  .room-data strong { display:block; margin-top:.2rem; }
  .paths-grid { display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap:1rem; }
  .path-card { border: 1px solid rgba(142,197,232,.28); border-radius: 1.1rem; background:#fff; padding:1.1rem; box-shadow: 0 10px 26px rgba(16,39,68,.06); }
  .path-card .path-icon { font-size:2rem; margin-bottom:.6rem; }
  .path-card h3 { margin:0; font-family: Georgia, 'Times New Roman', serif; font-size:1.25rem; }
  .path-card .time { color:#2f75a8; font-weight:900; margin:.45rem 0; }
  .path-card p { color:#5a6b7c; font-size:.9rem; line-height:1.5; }
  .path-sales { margin-top: .9rem; background:#f3f8fc; border-radius:.9rem; padding:.75rem; font-weight:900; font-size:.86rem; }
  .service-strip { display:grid; grid-template-columns: 2fr 1fr; gap:1rem; margin-top:2rem; }
  .service-main { border-radius:1.45rem; background:#202020; color:#fffdf5; padding:1.5rem; }
  .service-main h2, .help-card h2 { margin:0; font-family: Georgia, 'Times New Roman', serif; font-size:2rem; }
  .service-grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:.85rem; margin-top:1rem; }
  .service-item { border:1px solid rgba(255,255,255,.12); border-radius:1rem; background:rgba(255,255,255,.08); padding:1rem; }
  .help-card { border: 1px solid rgba(142,197,232,.28); border-radius:1.45rem; background:#fff; padding:1.5rem; }
  @media (max-width: 1024px) {
    .tour-head { grid-template-columns: 1fr; }
    .tour-layout { grid-template-columns: 1fr; }
    .tour-stage { min-height: auto; }
    .paths-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .service-strip { grid-template-columns: 1fr; }
  }
  @media (max-width: 760px) {
    .mappa-wrap { width: min(100% - 24px, 1180px); }
    .mappa-hero { padding: 3rem 0; }
    .tour-list { grid-template-columns: 1fr; }
    .tour-room { grid-template-columns: auto 1fr; }
    .tour-room-icon { display:none; }
    .tour-facts { grid-template-columns: 1fr; }
    .rooms-grid, .paths-grid, .service-grid { grid-template-columns: 1fr; }
    .section-head { display:block; }
    .duration-badge { margin-top: 1rem; }
  }
</style>

<div class="mappa-page">
  <div class="mappa-breadcrumb">
    <div class="mappa-wrap">
      <a href="<?= SITE_URL ?>/index.php">Home</a><span>›</span><strong>Mappa e tour</strong>
    </div>
  </div>

  <section class="mappa-hero">
    <div class="mappa-wrap">
      <p class="mappa-eyebrow">Museo Storico Severi</p>
      <h1 class="mappa-title">Mappa e tour virtuale</h1>
      <p class="mappa-lead">Esplora le sale del museo, scegli un percorso consigliato e preparati alla visita con una guida virtuale leggera e interattiva.</p>
      <div class="mappa-actions">
        <a class="mappa-btn mappa-btn-primary" href="#tour">Avvia il tour</a>
        <a class="mappa-btn mappa-btn-secondary" href="<?= SITE_URL ?>/prenota.php">Prenota ora</a>
      </div>
    </div>
  </section>

  <main class="mappa-main">
    <div class="mappa-wrap">
      <section id="tour" class="tour-box">
        <div class="tour-head">
          <div>
            <p class="mappa-eyebrow">Tour virtuale</p>
            <h2>Percorso interattivo</h2>
            <p>Qui il tour si vede davvero: scegli una sala, leggi la descrizione e procedi come in una visita guidata virtuale.</p>
          </div>
          <div>
            <div class="tour-progress-text"><span>Avanzamento</span><span id="tour-progress-label">Sala 1 di 6</span></div>
            <div class="tour-meter" aria-hidden="true"><span id="tour-meter-fill"></span></div>
          </div>
        </div>

        <div class="tour-layout">
          <article class="tour-stage" aria-live="polite">
            <div class="tour-meta">
              <span class="tour-pill">Sala attiva</span>
              <span id="tour-number" class="tour-pill">01</span>
            </div>
            <div class="tour-visual"><span id="tour-icon" aria-hidden="true">🪨</span></div>
            <p id="tour-tag" class="tour-tag">Origini dell’uomo</p>
            <h3 id="tour-title">Preistoria</h3>
            <p id="tour-description" class="tour-description">Osserva strumenti, tracce di vita quotidiana e prime forme di organizzazione sociale. È la sala ideale per iniziare il viaggio nella storia.</p>
            <div class="tour-facts">
              <div class="tour-fact"><small>Area</small><strong id="tour-area">Ingresso nord</strong></div>
              <div class="tour-fact"><small>Tempo</small><strong id="tour-time">15 min</strong></div>
              <div class="tour-fact"><small>Focus</small><strong id="tour-focus">Strumenti, fuoco, vita quotidiana</strong></div>
            </div>
          </article>

          <aside class="tour-nav" aria-label="Scegli sala del tour virtuale">
            <div class="tour-list" id="tour-room-list">
              <?php foreach ($sale as $idx => $sala): ?>
                <button type="button" class="tour-room <?= $idx === 0 ? 'is-active' : '' ?>" data-tour-index="<?= $idx ?>" aria-pressed="<?= $idx === 0 ? 'true' : 'false' ?>">
                  <span class="tour-num"><?= clean($sala['num']) ?></span>
                  <span>
                    <span class="tour-room-title"><?= clean($sala['titolo']) ?></span>
                    <span class="tour-room-sub"><?= clean($sala['tempo']) ?> · <?= clean($sala['area']) ?></span>
                  </span>
                  <span class="tour-room-icon" aria-hidden="true"><?= clean($sala['icona']) ?></span>
                </button>
              <?php endforeach; ?>
            </div>
            <div class="tour-buttons">
              <button type="button" id="tour-prev" class="tour-control">← Precedente</button>
              <button type="button" id="tour-next" class="tour-control tour-control-next">Successiva →</button>
            </div>
          </aside>
        </div>
      </section>

      <section class="section-card">
        <div class="section-head">
          <div>
            <p class="mappa-eyebrow" style="color:#2f75a8">Planimetria semplificata</p>
            <h2>Le sale principali</h2>
            <p>La mappa è organizzata come percorso progressivo: dall’ingresso nord fino all’uscita sud, attraversando sei aree tematiche.</p>
          </div>
          <div class="duration-badge"><small>Durata completa</small><strong>circa 2 ore</strong></div>
        </div>
        <div class="map-line" aria-hidden="true"></div>
        <div class="rooms-grid">
          <?php foreach ($sale as $sala): ?>
            <article class="room-card">
              <div class="room-top"><span class="tour-num"><?= clean($sala['num']) ?></span><span class="room-icon" aria-hidden="true"><?= clean($sala['icona']) ?></span></div>
              <p class="tag"><?= clean($sala['tag']) ?></p>
              <h3><?= clean($sala['titolo']) ?></h3>
              <p><?= clean($sala['descrizione']) ?></p>
              <div class="room-data">
                <div><small>Area</small><strong><?= clean($sala['area']) ?></strong></div>
                <div><small>Tempo</small><strong><?= clean($sala['tempo']) ?></strong></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="section-card">
        <div class="section-head">
          <div>
            <p class="mappa-eyebrow" style="color:#2f75a8">Suggerimenti di visita</p>
            <h2>Percorsi consigliati</h2>
            <p>Scegli un percorso in base al tempo disponibile e al tipo di visita.</p>
          </div>
        </div>
        <div class="paths-grid">
          <?php foreach ($percorsi as $percorso): ?>
            <article class="path-card">
              <div class="path-icon" aria-hidden="true"><?= clean($percorso['icona']) ?></div>
              <h3><?= clean($percorso['nome']) ?></h3>
              <p class="time"><?= clean($percorso['durata']) ?></p>
              <p><?= clean($percorso['target']) ?></p>
              <div class="path-sales"><?= clean($percorso['sale']) ?></div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="service-strip">
        <div class="service-main">
          <p class="mappa-eyebrow">Servizi in museo</p>
          <h2>Punti utili durante la visita</h2>
          <div class="service-grid">
            <div class="service-item"><strong>🎧 Audioguide</strong><br><small>Disponibili come servizio opzionale.</small></div>
            <div class="service-item"><strong>👩‍🏫 Gruppi classe</strong><br><small>Percorso didattico con dati scuola.</small></div>
            <div class="service-item"><strong>📱 QR code</strong><br><small>Controllo rapido all’ingresso.</small></div>
          </div>
        </div>
        <aside class="help-card">
          <p class="mappa-eyebrow" style="color:#2f75a8">Hai bisogno di aiuto?</p>
          <h2>Usa la guida virtuale</h2>
          <p>L’assistente in basso a destra può spiegarti come prenotare, recuperare un ordine o scegliere il percorso più adatto.</p>
          <a class="mappa-btn mappa-btn-primary" href="<?= SITE_URL ?>/prenota_docente.php">Prenotazione classe</a>
        </aside>
      </section>
    </div>
  </main>
</div>

<script nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  var rooms = <?= json_encode($sale, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var index = 0;
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-tour-index]'));
  var title = document.getElementById('tour-title');
  var number = document.getElementById('tour-number');
  var icon = document.getElementById('tour-icon');
  var tag = document.getElementById('tour-tag');
  var description = document.getElementById('tour-description');
  var area = document.getElementById('tour-area');
  var time = document.getElementById('tour-time');
  var focus = document.getElementById('tour-focus');
  var label = document.getElementById('tour-progress-label');
  var meter = document.getElementById('tour-meter-fill');
  var prev = document.getElementById('tour-prev');
  var next = document.getElementById('tour-next');

  function render(nextIndex) {
    index = (nextIndex + rooms.length) % rooms.length;
    var room = rooms[index];
    title.textContent = room.titolo;
    number.textContent = room.num;
    icon.textContent = room.icona;
    tag.textContent = room.tag;
    description.textContent = room.tour;
    area.textContent = room.area;
    time.textContent = room.tempo;
    focus.textContent = room.focus;
    label.textContent = 'Sala ' + (index + 1) + ' di ' + rooms.length;
    meter.style.width = (((index + 1) / rooms.length) * 100) + '%';
    buttons.forEach(function (button, i) {
      var active = i === index;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      render(parseInt(button.dataset.tourIndex || '0', 10));
    });
  });
  prev.addEventListener('click', function () { render(index - 1); });
  next.addEventListener('click', function () { render(index + 1); });
  render(0);
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
