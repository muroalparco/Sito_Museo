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
<script id="auto-hide-alerts-global">
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.floating-alert, .alert-success, .alert-error').forEach(function (box) {
    if (box.dataset.keepOpen === 'true') return;
    window.setTimeout(function () {
      box.style.transition = 'opacity .35s ease, transform .35s ease';
      box.style.opacity = '0';
      box.style.transform = box.classList.contains('floating-alert') ? 'translate(-50%, -8px)' : 'translateY(-8px)';
      window.setTimeout(function () { box.remove(); }, 450);
    }, 4200);
  });
});
</script>
</body>
</html>
