<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Chi siamo';
$pageDescription = 'Scopri perché è nato il progetto Museo Storico Severi: un sito didattico che unisce storia, tecnologia, biglietteria digitale e competenze web.';

include __DIR__ . '/header.php';
?>

<div class="bg-avorio-dark border-b border-oro border-opacity-20 py-3">
  <div class="max-w-7xl mx-auto px-4 text-sm text-gray-500 font-body">
    <a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a>
    <span class="mx-2 text-oro">›</span>
    <span class="text-antracite">Chi siamo</span>
  </div>
</div>

<section class="bg-antracite py-14 sm:py-16">
  <div class="max-w-5xl mx-auto px-4 text-center fade-up">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">Il progetto</p>
    <h1 class="font-display text-avorio text-3xl sm:text-4xl font-bold">Chi siamo</h1>
    <div class="w-16 h-px bg-oro mx-auto mt-4"></div>
    <p class="text-gray-300 text-sm sm:text-base leading-relaxed max-w-3xl mx-auto mt-6">
      Il Museo Storico Severi nasce come progetto didattico per trasformare un sito web in un vero laboratorio di competenze: storia, comunicazione digitale, basi di dati, accessibilità, sicurezza e servizi online.
    </p>
  </div>
</section>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
  <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-12">
    <article class="bg-white rounded-2xl shadow border border-avorio-dark p-7">
      <h2 class="font-display text-2xl font-bold text-antracite mb-3">Perché un museo</h2>
      <p class="text-gray-500 text-sm leading-relaxed">
        Abbiamo scelto il tema del museo perché permette di raccontare contenuti culturali in modo ordinato, visivo e coinvolgente. Le esposizioni diventano pagine da progettare, descrivere, organizzare e rendere prenotabili.
      </p>
    </article>

    <article class="bg-white rounded-2xl shadow border border-avorio-dark p-7">
      <h2 class="font-display text-2xl font-bold text-antracite mb-3">Tecnologia reale</h2>
      <p class="text-gray-500 text-sm leading-relaxed">
        Il sito non è solo una vetrina: usa PHP, MySQL, login, ruoli, ordini, biglietti, cassa, verifica email e validazione dei ticket. Ogni funzione aiuta a capire come lavora un servizio digitale completo.
      </p>
    </article>

    <article class="bg-white rounded-2xl shadow border border-avorio-dark p-7">
      <h2 class="font-display text-2xl font-bold text-antracite mb-3">Lavoro di squadra</h2>
      <p class="text-gray-500 text-sm leading-relaxed">
        Il progetto valorizza ruoli diversi: chi cura i contenuti, chi progetta l’interfaccia, chi controlla i dati, chi testa il sistema e chi ragiona sull’esperienza dell’utente.
      </p>
    </article>
  </section>

  <figure class="mb-16 rounded-2xl overflow-hidden shadow-xl border border-avorio-dark bg-white">
    <picture>
      <source srcset="<?= SITE_URL ?>/img/foto/galleria-storica.webp 640w, <?= SITE_URL ?>/img/foto/galleria-storica@2x.webp 960w" sizes="(max-width: 1024px) 100vw, 960px">
      <img src="<?= SITE_URL ?>/img/foto/galleria-storica.webp" width="960" height="576" alt="Galleria del Museo Storico Severi" class="w-full h-auto object-cover" loading="lazy" decoding="async">
    </picture>
  </figure>

  <section class="bg-white rounded-2xl shadow-xl border border-avorio-dark overflow-hidden mb-12">
    <div class="grid grid-cols-1 lg:grid-cols-2">
      <div class="bg-antracite p-8 sm:p-10 flex flex-col justify-center">
        <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">La nostra idea</p>
        <h2 class="font-display text-avorio text-3xl font-bold mb-5">Imparare costruendo qualcosa che funziona</h2>
        <p class="text-gray-300 text-sm leading-relaxed">
          L’obiettivo è far vedere che l’informatica non è fatta solo di codice isolato, ma di problemi concreti da risolvere: prenotare, pagare, generare un biglietto, recuperare un ordine, distinguere i permessi degli utenti e rendere il sito chiaro anche da telefono.
        </p>
      </div>
      <div class="p-8 sm:p-10">
        <h3 class="font-display text-2xl font-bold text-antracite mb-4">Cosa abbiamo voluto allenare</h3>
        <div class="space-y-4 text-sm text-gray-500 leading-relaxed">
          <p><strong class="text-antracite">Progettazione:</strong> organizzare pagine, menu, percorsi utente e aree riservate.</p>
          <p><strong class="text-antracite">Database:</strong> collegare utenti, ordini, biglietti, fasce orarie, tariffe e servizi opzionali.</p>
          <p><strong class="text-antracite">Responsabilità digitale:</strong> gestire sicurezza, ruoli, controlli, accessibilità e prestazioni.</p>
          <p><strong class="text-antracite">Comunicazione:</strong> scrivere testi comprensibili, curare l’immagine del museo e rendere le informazioni facili da trovare.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="text-center bg-avorio rounded-2xl border border-oro/30 p-8 sm:p-10">
    <p class="text-oro text-xs uppercase tracking-widest font-body mb-2">In sintesi</p>
    <h2 class="font-display text-3xl font-bold text-antracite mb-4">Un progetto scolastico, ma con logica professionale</h2>
    <p class="text-gray-500 text-sm leading-relaxed max-w-3xl mx-auto mb-6">
      Il Museo Storico Severi è stato creato per mettere insieme creatività e metodo: un sito bello da vedere, utile da usare e abbastanza completo da far ragionare su ciò che serve davvero quando si costruisce un’applicazione web.
    </p>
    <a href="<?= SITE_URL ?>/esposizioni.php" class="btn-oro px-7 py-3 rounded font-body text-sm uppercase tracking-wide inline-block">Scopri le esposizioni</a>
  </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
