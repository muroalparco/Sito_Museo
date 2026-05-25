<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$cssPath = __DIR__ . '/assets/css/style.css';
$cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
$tailwindPath = __DIR__ . '/assets/css/tailwind-local.css';
$tailwindVersion = file_exists($tailwindPath) ? filemtime($tailwindPath) : $cssVersion;
$fixesPath = __DIR__ . '/assets/css/fixes.css';
$fixesVersion = file_exists($fixesPath) ? filemtime($fixesPath) : $cssVersion;
$homeCriticalPath = __DIR__ . '/assets/css/home-critical.css';
$assistenteCssPath = __DIR__ . '/assets/css/assistente_ai.css';
$assistenteCssVersion = file_exists($assistenteCssPath) ? filemtime($assistenteCssPath) : $cssVersion;
$accessibilitaCssPath = __DIR__ . '/assets/css/accessibilita.css';
$accessibilitaCssVersion = file_exists($accessibilitaCssPath) ? filemtime($accessibilitaCssPath) : $cssVersion;
$currentPage = basename($_SERVER['PHP_SELF']);
$isAdminPage = ($currentPage === 'admin.php' && isAdmin());

$metaDescriptions = [
    'index.php' => 'Visita il Museo Storico Severi: scopri esposizioni, novità, servizi, tariffe e prenota online i biglietti per il tuo percorso museale.',
    'esposizioni.php' => 'Scopri le esposizioni del Museo Storico Severi, consulta mostre disponibili, dettagli, date e percorsi di visita.',
    'novita.php' => 'Leggi le novità del Museo Storico Severi: aggiornamenti, iniziative, eventi e comunicazioni per visitatori, studenti e docenti.',
    'chi_siamo.php' => 'Scopri il progetto Museo Storico Severi, nato come laboratorio didattico per unire storia, tecnologia, accessibilità e competenze digitali.',
    'info.php' => 'Consulta informazioni, tariffe, riduzioni, servizi opzionali e indicazioni utili per organizzare la visita al Museo Storico Severi.',
    'mappa.php' => 'Consulta la mappa del Museo Storico Severi, le sale espositive, i percorsi consigliati e i servizi utili durante la visita.',
    'features.php' => 'Scopri le features del Museo Storico Severi: prenotazioni, biglietti, QR code, pagamenti, rimborsi, dashboard, admin, export CSV e assistente virtuale.',
    'prenota.php' => 'Prenota online i biglietti per il Museo Storico Severi scegliendo data, fascia oraria, categoria e servizi aggiuntivi.',
    'prenota_docente.php' => 'Prenota una visita scolastica al Museo Storico Severi indicando docente, classe, studenti, data e fascia oraria.',
    'recupera_ordine.php' => 'Recupera il tuo ordine del Museo Storico Severi inserendo il codice ricevuto e consulta o ristampa i biglietti acquistati.',
    'biglietti.php' => 'Visualizza e stampa i biglietti del Museo Storico Severi associati al tuo ordine o alla tua prenotazione.',
    'pagamento.php' => 'Completa il pagamento simulato della prenotazione e conferma l’ordine dei biglietti per il Museo Storico Severi.',
    'registrazione.php' => 'Crea un account per gestire prenotazioni, ordini e biglietti del Museo Storico Severi in modo semplice e sicuro.',
    'login.php' => 'Accedi al tuo account del Museo Storico Severi per consultare profilo, ordini, prenotazioni e biglietti.',
    'account.php' => 'Gestisci il tuo profilo utente del Museo Storico Severi, controlla i dati personali, gli ordini e le impostazioni account.',
    'ordini.php' => 'Consulta lo storico dei tuoi ordini e delle prenotazioni effettuate per il Museo Storico Severi.',
    'admin.php' => 'Area amministratore del Museo Storico Severi per gestire utenti, esposizioni, fasce orarie, tariffe, servizi e ordini.',
    'cassa.php' => 'Area cassa del Museo Storico Severi per creare ordini, emettere biglietti e gestire acquisti in presenza.',
    'valida_biglietti.php' => 'Area operatore per controllare e validare i biglietti del Museo Storico Severi tramite codice ticket.',
    'recupero_password.php' => 'Recupera la password del tuo account del Museo Storico Severi e ripristina l’accesso in modo sicuro.',
    'verifica_email.php' => 'Verifica l’indirizzo email associato al tuo account del Museo Storico Severi e completa la registrazione.',
    '404.php' => 'Pagina non trovata sul sito del Museo Storico Severi: torna alla home o consulta le sezioni principali del museo.'
];
$metaDescription = $pageDescription ?? $metaDescriptions[$currentPage] ?? 'Museo Storico Severi: sito per informazioni, esposizioni, tariffe, prenotazioni online, ordini e biglietti del percorso museale.';

$seoTitles = [
    'index.php' => 'Museo Storico Severi | Biglietti e mostre',
    'chi_siamo.php' => 'Chi siamo | Museo Storico Severi',
    'esposizioni.php' => 'Esposizioni | Museo Storico Severi',
    'novita.php' => 'Novità | Museo Storico Severi',
    'info.php' => 'Info e tariffe | Museo Storico Severi',
    'mappa.php' => 'Mappa e percorso guidato | Museo Storico Severi',
    'features.php' => 'Features | Museo Storico Severi',
    'prenota.php' => 'Prenota biglietti | Museo Storico Severi',
    'prenota_docente.php' => 'Prenotazione docenti | Museo Storico Severi',
    'recupera_ordine.php' => 'Recupera ordine | Museo Storico Severi',
    'login.php' => 'Accesso utenti | Museo Storico Severi',
    'registrazione.php' => 'Registrazione | Museo Storico Severi',
    'account.php' => 'Area personale | Museo Storico Severi',
    'admin.php' => 'Area amministratore | Museo Storico Severi',
    'cassa.php' => 'Area cassa | Museo Storico Severi',
    'valida_biglietti.php' => 'Validazione biglietti | Museo Storico Severi',
    '404.php' => 'Pagina non trovata | Museo Storico Severi',
];
$seoTitle = $pageTitleSeo ?? $seoTitles[$currentPage] ?? clean(($pageTitle ?? SITE_NAME) . ' | ' . SITE_NAME);

$publicIndexPages = [
    'index.php',
    'chi_siamo.php',
    'esposizioni.php',
    'novita.php',
    'info.php',
    'mappa.php',
    'features.php',
    'prenota.php',
    'prenota_docente.php',
    'recupera_ordine.php',
    'admin.php',
];
$robotsContent = in_array($currentPage, $publicIndexPages, true) ? 'index, follow' : 'noindex, nofollow';

$canonicalPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$canonicalPath = preg_replace('#/+#', '/', $canonicalPath);
if ($canonicalPath === '/' || $canonicalPath === '/index.php') {
    $canonicalUrl = rtrim(SITE_URL, '/') . '/';
} else {
    $canonicalUrl = rtrim(SITE_URL, '/') . '/' . ltrim($canonicalPath, '/');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= clean($seoTitle) ?></title>
  <meta name="description" content="<?= clean($metaDescription) ?>">
  <meta name="robots" content="<?= clean($robotsContent) ?>">
  <link rel="canonical" href="<?= clean($canonicalUrl) ?>">

  <!-- Font di sistema: nessuna richiesta esterna -->

  <?php if ($currentPage === 'index.php'): ?>

    <!-- Home ottimizzata e valida W3C: CSS minimo inline, senza Tailwind completo e senza richieste esterne critiche. -->
    <style>
<?php
      if (file_exists($homeCriticalPath)) {
          echo file_get_contents($homeCriticalPath);
          echo "\n";
      }
?>
    </style>

  <?php else: ?>
    <!-- Tailwind locale generato: evita il CDN JavaScript bloccante -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/tailwind-local.css?v=<?= $tailwindVersion ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= $cssVersion ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/fixes.css?v=<?= $fixesVersion ?>">
  <?php endif; ?>

  <link rel="preload" href="<?= SITE_URL ?>/assets/css/assistente_ai.css?v=<?= $assistenteCssVersion ?>" as="style" data-deferred-stylesheet>
  <link rel="preload" href="<?= SITE_URL ?>/assets/css/accessibilita.css?v=<?= $accessibilitaCssVersion ?>" as="style" data-deferred-stylesheet>
  <noscript>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/assistente_ai.css?v=<?= $assistenteCssVersion ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/accessibilita.css?v=<?= $accessibilitaCssVersion ?>">
  </noscript>
</head>

<body class="min-h-screen flex flex-col">

<!-- Inizio header / navbar -->
<header class="bg-antracite shadow-lg sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-20 <?= $isAdminPage ? 'admin-header-row' : '' ?>">

      <?php if ($isAdminPage): ?>
        <button
          type="button"
          id="adminMobileMenuButton"
          class="admin-header-admin-button md:hidden"
          aria-controls="adminMobileMenuPanel"
          aria-expanded="false"
          aria-label="Apri menu amministrazione"
        >
          <span aria-hidden="true"></span>
          <span>Admin</span>
        </button>
      <?php endif; ?>

      <!-- Logo -->
      <a href="<?= SITE_URL ?>/index.php" class="flex items-center gap-3 group shrink-0 <?= $isAdminPage ? 'admin-header-logo' : '' ?>">
        <img 
          src="<?= SITE_URL ?>/img/logo-navbar.webp"
          width="96"
          height="72"
          alt="Logo Museo Storico Severi"
          class="header-logo-img object-contain drop-shadow-[0_0_10px_rgba(142,197,232,0.30)]"
          decoding="async"
          fetchpriority="high"
        >

        <div class="leading-tight hidden sm:block">
          <div class="font-display text-oro text-lg font-semibold tracking-wide group-hover:text-oro-dark transition-colors">
            Museo Storico
          </div>
          <div class="font-display text-avorio text-sm tracking-widest uppercase">
            Severi
          </div>
        </div>
      </a>

      <!-- Navigazione desktop -->
      <nav class="hidden md:flex items-center gap-1">
        <a href="<?= SITE_URL ?>/index.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'index.php') ? 'text-oro border-b border-oro' : '' ?>">
          Home
        </a>

        <a href="<?= SITE_URL ?>/chi_siamo.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'chi_siamo.php') ? 'text-oro border-b border-oro' : '' ?>">
          Chi siamo
        </a>

        <a href="<?= SITE_URL ?>/esposizioni.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'esposizioni.php') ? 'text-oro border-b border-oro' : '' ?>">
          Esposizioni
        </a>

        <a href="<?= SITE_URL ?>/novita.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'novita.php') ? 'text-oro border-b border-oro' : '' ?>">
          Novità
        </a>

        <a href="<?= SITE_URL ?>/info.php"
           class="px-3 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'info.php') ? 'text-oro border-b border-oro' : '' ?>">
          Info & Tariffe
        </a>

        <a href="<?= SITE_URL ?>/mappa.php"
           class="px-3 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'mappa.php') ? 'text-oro border-b border-oro' : '' ?>">
          Mappa
        </a>

        <a href="<?= SITE_URL ?>/features.php"
           class="px-3 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'features.php') ? 'text-oro border-b border-oro' : '' ?>">
          Features
        </a>
      </nav>

      <!-- Area utente desktop -->
      <div class="hidden md:flex items-center gap-3">
        <?php if (isLogged()): ?>

          <a href="<?= SITE_URL ?>/account.php" class="hidden lg:inline-flex items-center gap-2 rounded-full border border-oro/40 bg-antracite-light/70 px-3 py-2 text-xs font-body text-avorio hover:border-oro transition-colors" title="Saldo portafoglio">
            <span class="uppercase tracking-widest text-oro">Saldo</span>
            <strong>€ <?= number_format(saldoUtenteCorrente(), 2, ',', '.') ?></strong>
          </a>

          <div class="nav-dropdown">
            <button type="button" class="flex items-center gap-2 text-avorio hover:text-oro transition-colors">
              <div class="w-8 h-8 rounded-full bg-oro flex items-center justify-center text-antracite font-bold text-sm">
                <?= strtoupper(substr($_SESSION['utente_nome'] ?? 'U', 0, 1)) ?>
              </div>

              <span class="hidden sm:block text-sm font-body">
                <?= clean($_SESSION['utente_nome'] ?? 'Utente') ?>
              </span>

              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <div class="nav-dropdown-menu nav-dropdown-menu-right">
              <a href="<?= SITE_URL ?>/account.php">Il mio account</a>
              <a href="<?= SITE_URL ?>/ordini.php">I miei ordini</a>

              <?php if (isAdmin()): ?>
                <a href="<?= SITE_URL ?>/admin.php" class="admin-link">Vista amministratore</a>
              <?php endif; ?>

              <?php if (isOperatore()): ?>
                <a href="<?= SITE_URL ?>/valida_biglietti.php" class="admin-link">Valida biglietti</a>
              <?php endif; ?>

              <?php if (isCassiere()): ?>
                <a href="<?= SITE_URL ?>/cassa.php" class="admin-link">Cassa</a>
              <?php endif; ?>

              <a href="<?= SITE_URL ?>/logout.php" class="logout-link">Logout</a>
            </div>
          </div>

        <?php else: ?>

          <div class="header-buttons">
            <a href="<?= SITE_URL ?>/login.php" class="header-btn header-btn-outline">
              Accedi
            </a>

            <a href="<?= SITE_URL ?>/registrazione.php" class="header-btn header-btn-gold">
              Registrati
            </a>
          </div>

        <?php endif; ?>
      </div>

      <!-- Bottone menu mobile -->
      <button 
        type="button"
        id="mobileMenuButton"
        class="md:hidden inline-flex items-center justify-center w-11 h-11 rounded-lg border border-oro/50 text-oro hover:bg-oro hover:text-antracite transition-colors"
        aria-controls="mobileMenu"
        aria-expanded="false"
        aria-label="Apri menu di navigazione"
      >
        <svg id="mobileMenuIconOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg id="mobileMenuIconClose" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

    </div>
  </div>

  <!-- Menu mobile -->
  <div id="mobileMenu" class="mobile-menu md:hidden hidden border-t border-oro/20 bg-antracite">
    <div class="px-4 pt-3 pb-5 space-y-2">
      <a href="<?= SITE_URL ?>/index.php" class="mobile-menu-link <?= ($currentPage === 'index.php') ? 'mobile-menu-link-active' : '' ?>">Home</a>
      <a href="<?= SITE_URL ?>/chi_siamo.php" class="mobile-menu-link <?= ($currentPage === 'chi_siamo.php') ? 'mobile-menu-link-active' : '' ?>">Chi siamo</a>
      <a href="<?= SITE_URL ?>/esposizioni.php" class="mobile-menu-link <?= ($currentPage === 'esposizioni.php') ? 'mobile-menu-link-active' : '' ?>">Esposizioni</a>
      <a href="<?= SITE_URL ?>/novita.php" class="mobile-menu-link <?= ($currentPage === 'novita.php') ? 'mobile-menu-link-active' : '' ?>">Novità</a>
      <a href="<?= SITE_URL ?>/info.php" class="mobile-menu-link <?= ($currentPage === 'info.php') ? 'mobile-menu-link-active' : '' ?>">Info & Tariffe</a>
      <a href="<?= SITE_URL ?>/mappa.php" class="mobile-menu-link <?= ($currentPage === 'mappa.php') ? 'mobile-menu-link-active' : '' ?>">Mappa del museo</a>
      <a href="<?= SITE_URL ?>/features.php" class="mobile-menu-link <?= ($currentPage === 'features.php') ? 'mobile-menu-link-active' : '' ?>">Features</a>

      <div class="h-px bg-oro/20 my-3"></div>

      <?php if (isLogged()): ?>
        <div class="flex items-center gap-3 px-3 py-3 rounded-xl bg-antracite-light/70 mb-3">
          <div class="w-9 h-9 rounded-full bg-oro flex items-center justify-center text-antracite font-bold text-sm">
            <?= strtoupper(substr($_SESSION['utente_nome'] ?? 'U', 0, 1)) ?>
          </div>
          <div>
            <div class="text-avorio font-body font-bold text-sm"><?= clean($_SESSION['utente_nome'] ?? 'Utente') ?></div>
            <div class="text-oro text-xs uppercase tracking-widest"><?= clean($_SESSION['utente_ruolo'] ?? 'utente') ?></div>
            <div class="text-avorio text-xs mt-1">Saldo: <strong>€ <?= number_format(saldoUtenteCorrente(), 2, ',', '.') ?></strong></div>
          </div>
        </div>

        <a href="<?= SITE_URL ?>/account.php" class="mobile-menu-link">Il mio account</a>
        <a href="<?= SITE_URL ?>/ordini.php" class="mobile-menu-link">I miei ordini</a>

        <?php if (isAdmin()): ?>
          <a href="<?= SITE_URL ?>/admin.php" class="mobile-menu-link mobile-menu-link-gold">Vista amministratore</a>
        <?php endif; ?>

        <?php if (isOperatore()): ?>
          <a href="<?= SITE_URL ?>/valida_biglietti.php" class="mobile-menu-link mobile-menu-link-gold">Valida biglietti</a>
        <?php endif; ?>

        <?php if (isCassiere()): ?>
          <a href="<?= SITE_URL ?>/cassa.php" class="mobile-menu-link mobile-menu-link-gold">Cassa</a>
        <?php endif; ?>

        <a href="<?= SITE_URL ?>/logout.php" class="mobile-menu-link mobile-menu-link-danger">Logout</a>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <a href="<?= SITE_URL ?>/login.php" class="header-btn header-btn-outline w-full">Accedi</a>
          <a href="<?= SITE_URL ?>/registrazione.php" class="header-btn header-btn-gold w-full">Registrati</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($isAdminPage): ?>
    <?php
      $headerAdminMenuItems = [
        ['href' => '#admin-dashboard', 'label' => 'Dashboard'],
        ['href' => '#admin-esposizioni', 'label' => 'Esposizioni'],
        ['href' => '#admin-categorie', 'label' => 'Categorie riduzioni'],
        ['href' => '#admin-tariffe', 'label' => 'Tariffe'],
        ['href' => '#admin-servizi', 'label' => 'Servizi'],
        ['href' => '#admin-utenti', 'label' => 'Utenti'],
        ['href' => '#admin-rimborsi', 'label' => 'Rimborsi'],
      ];
    ?>
    <div id="adminMobileBackdrop" class="admin-mobile-backdrop md:hidden" hidden></div>

    <nav id="adminMobileMenuPanel" class="admin-mobile-panel md:hidden" aria-label="Menu amministrazione mobile" hidden>
      <div class="admin-mobile-panel-header">
        <div>
          <p>Menu amministrazione</p>
          <strong>Navigazione rapida</strong>
        </div>
        <button type="button" id="adminMobileMenuClose" aria-label="Chiudi menu amministrazione">×</button>
      </div>
      <div class="admin-mobile-links">
        <?php foreach ($headerAdminMenuItems as $item): ?>
          <a href="<?= clean($item['href']) ?>"><?= clean($item['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </nav>
  <?php endif; ?>
</header>
<!-- Fine header -->

<script nonce="<?= cspNonce() ?>">
  document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('mobileMenuButton');
    const menu = document.getElementById('mobileMenu');
    const iconOpen = document.getElementById('mobileMenuIconOpen');
    const iconClose = document.getElementById('mobileMenuIconClose');

    if (!button || !menu) return;

    button.addEventListener('click', function () {
      const isOpen = !menu.classList.contains('hidden');
      menu.classList.toggle('hidden', isOpen);
      button.setAttribute('aria-expanded', String(!isOpen));

      if (iconOpen && iconClose) {
        iconOpen.classList.toggle('hidden', !isOpen);
        iconClose.classList.toggle('hidden', isOpen);
      }
    });
  });
</script>

<script nonce="<?= cspNonce() ?>">
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.floating-alert').forEach(function (alertBox) {
      if (alertBox.parentNode !== document.body) {
        document.body.appendChild(alertBox);
      }
    });
  });
</script>
