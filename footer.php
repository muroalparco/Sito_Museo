<!-- footer -->
<footer class="bg-antracite text-avorio mt-auto">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

      <!-- colonna 1 brand -->
      <div>
        <div class="flex items-center gap-3 mb-4">
          <img src="<?= SITE_URL ?>/img/logo-256.webp" width="64" height="48" alt="Logo Museo Storico Severi" class="footer-logo-img object-contain" loading="lazy" decoding="async">
          <div>
            <div class="font-display text-oro font-semibold">Museo Storico Severi</div>
            <div class="text-xs text-gray-400 tracking-widest uppercase">Dal 2020</div>
          </div>
        </div>
        <p class="text-sm text-gray-400 leading-relaxed">
          Un viaggio attraverso la storia dell'umanità, dalle civiltà antiche al mondo contemporaneo.
        </p>
      </div>

      <!-- colonna 2 link -->
      <div class="footer-link-rapidi print:hidden">
        <h3 class="font-display text-oro text-sm uppercase tracking-widest mb-4">Link rapidi</h3>
        <ul class="space-y-2 text-sm text-gray-400">
          <li><a href="<?= SITE_URL ?>/index.php" class="hover:text-oro transition-colors">Home</a></li>
          <li><a href="<?= SITE_URL ?>/chi_siamo.php" class="hover:text-oro transition-colors">Chi siamo</a></li>
          <li><a href="<?= SITE_URL ?>/esposizioni.php" class="hover:text-oro transition-colors">Esposizioni</a></li>
          <li><a href="<?= SITE_URL ?>/info.php" class="hover:text-oro transition-colors">Informazioni & Tariffe</a></li>
          <li><a href="<?= SITE_URL ?>/mappa.php" class="hover:text-oro transition-colors">Mappa del museo</a></li>
          <li><a href="<?= SITE_URL ?>/progetto_digitale.php" class="hover:text-oro transition-colors">Il progetto digitale</a></li>
          <li><a href="<?= isLogged() ? SITE_URL . '/account.php' : SITE_URL . '/login.php' ?>" class="hover:text-oro transition-colors">Area riservata</a></li>
        </ul>
      </div>

      <!-- colonna 3 contatti -->
      <div>
        <h3 class="font-display text-oro text-sm uppercase tracking-widest mb-4">Contatti</h3>
        <ul class="space-y-2 text-sm text-gray-400">
          <li class="flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 text-oro flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Via Luigi Pettinati 46, 35128 Padova
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-oro flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            info@museostoricoseveri.it
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-4 h-4 text-oro flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Mar–Dom: 9:00 – 18:00
          </li>
        </ul>
      </div>
    </div>

    <hr class="border-gray-700 my-8 oro-line">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-gray-500">
      <span>&copy; <?= date('Y') ?> Museo Storico Severi. Tutti i diritti riservati.</span>
      <span class="font-display italic text-gray-600">«La storia è il testimone dei tempi» — Cicerone</span>
    </div>
  </div>
</footer>
<?php
$assistenteJsPath = __DIR__ . '/assets/js/assistente_ai.js';
$assistenteJsVersion = file_exists($assistenteJsPath) ? filemtime($assistenteJsPath) : time();
$accessibilitaJsPath = __DIR__ . '/assets/js/accessibilita.js';
$accessibilitaJsVersion = file_exists($accessibilitaJsPath) ? filemtime($accessibilitaJsPath) : time();
include __DIR__ . '/assistente_ai.php';
?>
<div class="a11y-widget print:hidden" role="group" aria-label="Strumenti di accessibilità">
  <div id="a11y-panel" class="a11y-panel" hidden>
    <h2>Accessibilità</h2>
    <div class="a11y-actions">
      <button type="button" data-a11y-action="font" aria-pressed="false">Aumenta testo</button>
      <button type="button" data-a11y-action="motion" aria-pressed="false">Riduci animazioni</button>
      <button type="button" data-a11y-action="reset">Ripristina</button>
    </div>
  </div>
  <button type="button" id="a11y-toggle" class="a11y-toggle" aria-label="Apri strumenti di accessibilità" aria-controls="a11y-panel" aria-expanded="false">Aa</button>
</div>
<script src="<?= SITE_URL ?>/assets/js/assistente_ai.js?v=<?= $assistenteJsVersion ?>" defer></script>
<script src="<?= SITE_URL ?>/assets/js/accessibilita.js?v=<?= $accessibilitaJsVersion ?>" defer></script>
<script id="auto-hide-alerts-global" nonce="<?= cspNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {
  var alerts = Array.prototype.slice.call(document.querySelectorAll('.floating-alert, .alert-success, .alert-error'));
  if (!alerts.length) return;
  var stack = document.createElement('div');
  stack.className = 'mss-toast-stack';
  stack.setAttribute('aria-live', 'polite');
  document.body.appendChild(stack);

  alerts.forEach(function (box) {
    if (box.dataset.keepOpen === 'true') return;
    var toast = document.createElement('div');
    var isError = box.classList.contains('alert-error');
    var isSuccess = box.classList.contains('alert-success');
    toast.className = 'mss-toast' + (isError ? ' is-error' : '') + (isSuccess ? ' is-success' : '');
    toast.setAttribute('role', isError ? 'alert' : 'status');

    var message = document.createElement('div');
    message.innerHTML = box.innerHTML;
    var close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Chiudi notifica');
    close.textContent = '×';
    close.addEventListener('click', function () { toast.remove(); });

    toast.appendChild(message);
    toast.appendChild(close);
    stack.appendChild(toast);
    box.remove();

    window.setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
      toast.style.transition = 'opacity .25s ease, transform .25s ease';
      window.setTimeout(function () { toast.remove(); }, 280);
    }, 5200);
  });
});
</script>
</body>
</html>
