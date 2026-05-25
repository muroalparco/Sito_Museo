<?php
http_response_code(404);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Pagina non trovata';
$pageDescription = 'Pagina non trovata sul sito del Museo Storico Severi: torna alla home, consulta le esposizioni o recupera un ordine.';

include __DIR__ . '/header.php';
?>

<style>
  .mss404-hero {
    position: relative;
    overflow: hidden;
    background: radial-gradient(circle at 80% 20%, rgba(142,197,232,.18), transparent 32%), linear-gradient(135deg, #102744 0%, #1a4c73 52%, #0c1e32 100%);
    color: #fffdf5;
  }
  .mss404-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: linear-gradient(135deg, rgba(255,255,255,.055) 8.33%, transparent 8.33%, transparent 50%, rgba(255,255,255,.055) 50%, rgba(255,255,255,.055) 58.33%, transparent 58.33%, transparent 100%);
    background-size: 18px 18px;
    opacity: .55;
  }
  .mss404-wrap { position: relative; z-index: 1; }
  .mss404-badge {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.10);
    border-radius: 999px;
    padding: .55rem .85rem;
    color: #d8effc;
    font-size: .74rem;
    font-weight: 900;
    letter-spacing: .14em;
    text-transform: uppercase;
  }
  .mss404-number {
    font-family: Georgia, serif;
    font-size: clamp(5rem, 14vw, 11rem);
    line-height: .82;
    letter-spacing: -.08em;
    color: rgba(255,255,255,.11);
    position: absolute;
    right: 5vw;
    top: 1.4rem;
    pointer-events: none;
  }
  .mss404-card {
    background: #ffffff;
    border: 1px solid #d7e9f5;
    border-radius: 1.5rem;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #102744;
    text-decoration: none;
    box-shadow: 0 10px 28px rgba(16,39,68,.075);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
  }
  .mss404-card:hover,
  .mss404-card:focus-visible {
    transform: translateY(-4px);
    border-color: #8ec5e8;
    box-shadow: 0 20px 42px rgba(16,39,68,.14);
    outline: none;
  }
  .mss404-icon {
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 1.1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e8f5fc;
    font-size: 1.45rem;
    flex: 0 0 auto;
  }
  .mss404-card strong { display:block; font-weight: 900; font-size: 1.05rem; }
  .mss404-card small { display:block; color:#52677a; margin-top:.2rem; line-height:1.35; }

  .mss404-content {
    padding-top: 3.25rem !important;
  }
  @media (max-width: 640px) {
    .mss404-content { padding-top: 2.5rem !important; }
  }

  .mss404-help {
    background: linear-gradient(135deg, #ffffff, #f7fbff);
    border: 1px solid #d7e9f5;
    border-radius: 1.75rem;
    box-shadow: 0 18px 42px rgba(16,39,68,.10);
  }
  .mss404-trail {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .6rem;
    margin-top: 1.4rem;
  }
  .mss404-trail span {
    border-radius: 999px;
    padding: .45rem .75rem;
    background: rgba(255,255,255,.10);
    border: 1px solid rgba(255,255,255,.18);
    color: #e9f7ff;
    font-size: .8rem;
    font-weight: 800;
  }
  @media (prefers-reduced-motion: no-preference) {
    .mss404-card { animation: mss404In .42s ease both; }
    .mss404-card:nth-child(2) { animation-delay: .06s; }
    .mss404-card:nth-child(3) { animation-delay: .12s; }
    @keyframes mss404In { from { opacity:0; transform: translateY(12px); } to { opacity:1; transform: translateY(0); } }
  }
  @media (max-width: 640px) {
    .mss404-number { right: 1.2rem; top: 1rem; }
    .mss404-card { align-items: flex-start; padding: 1rem; border-radius: 1.25rem; }
  }
</style>

<main class="bg-avorio min-h-screen">
  <section class="mss404-hero py-16 md:py-24">
    <div class="mss404-number" aria-hidden="true">404</div>
    <div class="mss404-wrap max-w-6xl mx-auto px-5 text-center">
      <span class="mss404-badge"><span aria-hidden="true">🧭</span> Errore 404</span>
      <h1 class="font-display text-4xl md:text-6xl font-bold leading-tight mt-5">Ti sei perso nel museo?</h1>
      <p class="max-w-2xl mx-auto text-avorio/85 text-lg leading-relaxed mt-5">La pagina non è disponibile, ma il percorso continua: puoi tornare alla home, esplorare le mostre o recuperare un ordine.</p>
      <div class="mss404-trail" aria-label="Percorso suggerito">
        <span>Home</span>
        <span>Esposizioni</span>
        <span>Biglietti</span>
        <span>Assistenza</span>
      </div>
    </div>
  </section>

  <section class="mss404-content max-w-6xl mx-auto px-5 relative z-10 pb-16">
    <div class="grid md:grid-cols-3 gap-5">
      <a href="<?= SITE_URL ?>/index.php" class="mss404-card">
        <span class="mss404-icon" aria-hidden="true">🏛️</span>
        <span><strong>Home</strong><small>Rientra dalla pagina principale del museo.</small></span>
      </a>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="mss404-card">
        <span class="mss404-icon" aria-hidden="true">🎟️</span>
        <span><strong>Esposizioni</strong><small>Scopri mostre, date e disponibilità.</small></span>
      </a>
      <a href="<?= SITE_URL ?>/recupera_ordine.php" class="mss404-card">
        <span class="mss404-icon" aria-hidden="true">🔎</span>
        <span><strong>Recupera ordine</strong><small>Ritrova biglietti e ricevuta con il codice ordine.</small></span>
      </a>
    </div>

    <div class="mss404-help mt-8 p-6 md:p-8 text-center">
      <p class="text-oro text-xs uppercase tracking-widest font-body font-bold mb-2">Percorso alternativo</p>
      <h2 class="font-display text-3xl font-bold text-antracite">Serve una mano?</h2>
      <p class="text-gray-500 mt-2 max-w-2xl mx-auto">Apri l’assistente virtuale in basso a destra oppure scegli una delle sezioni più utili per riprendere la navigazione.</p>
      <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
        <a href="<?= SITE_URL ?>/mappa.php" class="btn-outline rounded px-5 py-3 font-bold text-sm uppercase tracking-wide">Mappa e percorso</a>
        <a href="<?= SITE_URL ?>/features.php" class="btn-oro rounded px-5 py-3 font-bold text-sm uppercase tracking-wide">Features del sito</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
