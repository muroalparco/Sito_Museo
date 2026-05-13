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

  <!--  fonts presi da gogle: Playfair Display + Lato -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />

  <!-- Tailwind css cdn -->
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

<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=20">
</head>
<body class="min-h-screen flex flex-col">

<!-- inizio header / navbar  -->
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

      <!-- nav links pc -->
      <nav class="hidden md:flex items-center gap-1">
        <a href="<?= SITE_URL ?>/index.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'text-oro border-b border-oro' : '' ?>">
          Home
        </a>

        <a href="<?= SITE_URL ?>/esposizioni.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= (basename($_SERVER['PHP_SELF']) === 'esposizioni.php') ? 'text-oro border-b border-oro' : '' ?>">
          Esposizioni
        </a>

        <a href="<?= SITE_URL ?>/novita.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= (basename($_SERVER['PHP_SELF']) === 'novita.php') ? 'text-oro border-b border-oro' : '' ?>">
          Novità
        </a>
        <a href="<?= SITE_URL ?>/info.php"
           class="px-4 py-2 text-avorio hover:text-oro font-body text-sm tracking-wide transition-colors <?= (basename($_SERVER['PHP_SELF']) === 'info.php') ? 'text-oro border-b border-oro' : '' ?>">
          Info & Tariffe
        </a>
      </nav>

      <!-- Auth pulsanti  -->
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
              <?php if (isAdmin()): ?>
              <a href="<?= SITE_URL ?>/admin.php" class="block px-4 py-2 text-oro text-sm hover:bg-antracite transition-colors">Vista amministratore</a>
              <?php endif; ?>
              <?php if (isOperatore()): ?>
              <a href="<?= SITE_URL ?>/valida_biglietti.php" class="block px-4 py-2 text-oro text-sm hover:bg-antracite transition-colors">Valida biglietti</a>
              <?php endif; ?>
              <?php if (isCassiere()): ?>
              <a href="<?= SITE_URL ?>/cassa.php" class="block px-4 py-2 text-oro text-sm hover:bg-antracite transition-colors">Cassa</a>
              <?php endif; ?>
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
<!--  fine header  -->
