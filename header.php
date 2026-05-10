<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= clean($pageTitle ?? SITE_NAME) ?> — <?= SITE_NAME ?></title>

  <!-- Google Fonts: Playfair Display + Lato -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            oro:     '#C9A84C',
            'oro-dark': '#A8822A',
            acciaio: '#6B8CAE',
            avorio:  '#F5F0E8',
            'avorio-dark': '#EAE3D2',
            antracite: '#2C2C2C',
            'antracite-light': '#4A4A4A',
          },
          fontFamily: {
            display: ['"Playfair Display"', 'Georgia', 'serif'],
            body:    ['Lato', 'sans-serif'],
          },
        }
      }
    }
  </script>

<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="min-h-screen flex flex-col">

<!-- ══════════════════ HEADER / NAVBAR ══════════════════ -->
<header class="bg-antracite shadow-lg sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-20">

      <!-- Logo -->
      <a href="<?= SITE_URL ?>/index.php" class="flex items-center gap-3 group">
        <img 
          src="<?= SITE_URL ?>/img/logo.png" 
          alt="Logo Museo Storico Severi"
          class="h-16 w-auto object-contain drop-shadow-[0_0_10px_rgba(201,168,76,0.30)]"
        >
        <div class="leading-tight hidden sm:block">
          <div class="font-display text-oro text-lg font-semibold tracking-wide group-hover:text-oro-dark transition-colors">Museo Storico</div>
          <div class="font-display text-avorio text-sm tracking-widest uppercase">Severi</div>
        </div>
      </a>

      <!-- Nav links (desktop) -->
      <nav class="hidden md:flex items-center gap-1">
        <a href="<?= SITE_URL ?>/index.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'text-oro border-b border-oro' : '' ?>">
          Home
        </a>

        <!-- Esposizioni dropdown -->
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

        <a href="<?= SITE_URL ?>/novita.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors">
          Novità
        </a>
        <a href="<?= SITE_URL ?>/info.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors">
          Info & Tariffe
        </a>
      </nav>

      <!-- Auth buttons -->
      <div class="flex items-center gap-3">
        <?php if (isLogged()): ?>
          <div class="nav-dropdown relative">
            <button class="flex items-center gap-2 text-avorio hover:text-oro transition-colors">
              <div class="w-8 h-8 rounded-full bg-oro flex items-center justify-center text-antracite font-bold text-sm">
                <?= strtoupper(substr($_SESSION['utente_nome'], 0, 1)) ?>
              </div>
              <span class="hidden sm:block text-sm font-body"><?= clean($_SESSION['utente_nome']) ?></span>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="nav-menu absolute top-full right-0 w-48 bg-antracite-light rounded shadow-xl py-1 border-t-2 border-oro">
              <a href="<?= SITE_URL ?>/account.php" class="block px-4 py-2 text-avorio text-sm hover:bg-antracite hover:text-oro transition-colors">Il mio account</a>
              <a href="<?= SITE_URL ?>/ordini.php" class="block px-4 py-2 text-avorio text-sm hover:bg-antracite hover:text-oro transition-colors">I miei ordini</a>
              <hr class="border-antracite my-1">
              <a href="<?= SITE_URL ?>/logout.php" class="block px-4 py-2 text-red-400 text-sm hover:bg-antracite transition-colors">Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/login.php"
             class="btn-outline px-4 py-2 rounded text-sm font-body">Accedi</a>
          <a href="<?= SITE_URL ?>/registrazione.php"
             class="btn-oro px-4 py-2 rounded text-sm font-body">Registrati</a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</header>
<!-- ══════════════════ fine HEADER ══════════════════ -->
