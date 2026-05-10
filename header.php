<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= clean($pageTitle ?? SITE_NAME) ?> — <?= SITE_NAME ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            oro: '#C9A84C',
            'oro-dark': '#A8822A',
            acciaio: '#6B8CAE',
            avorio: '#F5F0E8',
            'avorio-dark': '#EAE3D2',
            antracite: '#2C2C2C',
            'antracite-light': '#4A4A4A',
          },
          fontFamily: {
            display: ['"Playfair Display"', 'Georgia', 'serif'],
            body: ['Lato', 'sans-serif'],
          },
        }
      }
    }
  </script>

  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="min-h-screen flex flex-col">

<header class="bg-antracite shadow-lg sticky top-0 z-50 border-b border-oro/20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between min-h-20 py-3">

      <a href="<?= SITE_URL ?>/index.php" class="flex items-center gap-3 group min-w-0">
        <img
          src="<?= SITE_URL ?>/img/logo.png"
          alt="Logo Museo Storico Severi"
          class="w-14 h-14 sm:w-16 sm:h-16 object-contain flex-shrink-0 drop-shadow-[0_0_10px_rgba(201,168,76,0.30)]"
        >
        <div class="leading-tight min-w-0">
          <div class="font-display text-oro text-base sm:text-lg font-semibold tracking-wide group-hover:text-oro-dark transition-colors truncate">Museo Storico</div>
          <div class="font-display text-avorio text-xs sm:text-sm tracking-widest uppercase truncate">Severi</div>
        </div>
      </a>

      <nav class="hidden lg:flex items-center gap-1">
        <a href="<?= SITE_URL ?>/index.php" class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'index.php') ? 'text-oro border-b border-oro' : '' ?>">Home</a>
        <div class="nav-dropdown relative">
          <button class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors flex items-center gap-1">
            Esposizioni
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>
          <div class="nav-menu absolute top-full left-0 w-44 bg-antracite-light rounded shadow-xl py-1 border-t-2 border-oro">
            <a href="<?= SITE_URL ?>/esposizioni.php" class="block px-4 py-2 text-avorio text-sm hover:bg-antracite hover:text-oro transition-colors">Tutte le mostre</a>
            <a href="<?= SITE_URL ?>/esposizioni.php?stato=Pubblicata" class="block px-4 py-2 text-avorio text-sm hover:bg-antracite hover:text-oro transition-colors">In corso</a>
          </div>
        </div>
        <a href="<?= SITE_URL ?>/novita.php" class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'novita.php') ? 'text-oro border-b border-oro' : '' ?>">Novità</a>
        <a href="<?= SITE_URL ?>/info.php" class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= ($currentPage === 'info.php') ? 'text-oro border-b border-oro' : '' ?>">Info & Tariffe</a>
      </nav>

      <div class="hidden lg:flex items-center gap-3">
        <?php if (isLogged()): ?>
          <div class="nav-dropdown relative">
            <button class="flex items-center gap-2 text-avorio hover:text-oro transition-colors">
              <div class="w-8 h-8 rounded-full bg-oro flex items-center justify-center text-antracite font-bold text-sm"><?= strtoupper(substr($_SESSION['utente_nome'], 0, 1)) ?></div>
              <span class="text-sm font-body max-w-28 truncate"><?= clean($_SESSION['utente_nome']) ?></span>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-menu absolute top-full right-0 w-48 bg-antracite-light rounded shadow-xl py-1 border-t-2 border-oro">
              <a href="<?= SITE_URL ?>/account.php" class="block px-4 py-2 text-avorio text-sm hover:bg-antracite hover:text-oro transition-colors">Il mio account</a>
              <a href="<?= SITE_URL ?>/ordini.php" class="block px-4 py-2 text-avorio text-sm hover:bg-antracite hover:text-oro transition-colors">I miei ordini</a>
              <?php if (isOperatore()): ?>
                <hr class="border-antracite my-1">
                <a href="<?= SITE_URL ?>/admin/" class="block px-4 py-2 text-oro text-sm hover:bg-antracite transition-colors">Pannello Admin</a>
              <?php endif; ?>
              <hr class="border-antracite my-1">
              <a href="<?= SITE_URL ?>/logout.php" class="block px-4 py-2 text-red-400 text-sm hover:bg-antracite transition-colors">Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/login.php" class="btn-outline px-4 py-2 rounded text-sm font-body">Accedi</a>
          <a href="<?= SITE_URL ?>/registrazione.php" class="btn-oro px-4 py-2 rounded text-sm font-body">Registrati</a>
        <?php endif; ?>
      </div>

      <button type="button" id="mobileMenuButton" class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-lg border border-oro/40 text-oro hover:bg-oro hover:text-antracite transition" aria-label="Apri menu" aria-expanded="false">
        <svg id="menuIconOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg id="menuIconClose" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div id="mobileMenu" class="lg:hidden hidden border-t border-oro/20 pb-4">
      <nav class="pt-4 space-y-2">
        <a href="<?= SITE_URL ?>/index.php" class="block px-4 py-3 rounded-lg text-avorio hover:bg-antracite-light hover:text-oro transition <?= ($currentPage === 'index.php') ? 'bg-antracite-light text-oro' : '' ?>">Home</a>
        <a href="<?= SITE_URL ?>/esposizioni.php" class="block px-4 py-3 rounded-lg text-avorio hover:bg-antracite-light hover:text-oro transition <?= ($currentPage === 'esposizioni.php') ? 'bg-antracite-light text-oro' : '' ?>">Esposizioni</a>
        <a href="<?= SITE_URL ?>/novita.php" class="block px-4 py-3 rounded-lg text-avorio hover:bg-antracite-light hover:text-oro transition <?= ($currentPage === 'novita.php') ? 'bg-antracite-light text-oro' : '' ?>">Novità</a>
        <a href="<?= SITE_URL ?>/info.php" class="block px-4 py-3 rounded-lg text-avorio hover:bg-antracite-light hover:text-oro transition <?= ($currentPage === 'info.php') ? 'bg-antracite-light text-oro' : '' ?>">Info & Tariffe</a>

        <div class="pt-3 mt-3 border-t border-oro/20 space-y-2">
          <?php if (isLogged()): ?>
            <div class="px-4 py-2 text-sm text-gray-300">Ciao, <span class="text-oro font-semibold"><?= clean($_SESSION['utente_nome']) ?></span></div>
            <a href="<?= SITE_URL ?>/account.php" class="block px-4 py-3 rounded-lg text-avorio hover:bg-antracite-light hover:text-oro transition">Il mio account</a>
            <a href="<?= SITE_URL ?>/ordini.php" class="block px-4 py-3 rounded-lg text-avorio hover:bg-antracite-light hover:text-oro transition">I miei ordini</a>
            <?php if (isOperatore()): ?>
              <a href="<?= SITE_URL ?>/admin/" class="block px-4 py-3 rounded-lg text-oro hover:bg-antracite-light transition">Pannello Admin</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/logout.php" class="block px-4 py-3 rounded-lg text-red-400 hover:bg-antracite-light transition">Logout</a>
          <?php else: ?>
            <a href="<?= SITE_URL ?>/login.php" class="block text-center btn-outline px-4 py-3 rounded-lg text-sm font-body">Accedi</a>
            <a href="<?= SITE_URL ?>/registrazione.php" class="block text-center btn-oro px-4 py-3 rounded-lg text-sm font-body">Registrati</a>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </div>
</header>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('mobileMenuButton');
    const menu = document.getElementById('mobileMenu');
    const openIcon = document.getElementById('menuIconOpen');
    const closeIcon = document.getElementById('menuIconClose');
    if (!button || !menu) return;

    button.addEventListener('click', function () {
      const isHidden = menu.classList.toggle('hidden');
      button.setAttribute('aria-expanded', String(!isHidden));
      openIcon.classList.toggle('hidden', !isHidden);
      closeIcon.classList.toggle('hidden', isHidden);
    });
  });
</script>
