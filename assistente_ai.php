<?php
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}
if (!function_exists('isLogged')) {
    require_once __DIR__ . '/auth.php';
}

$assistentePagina = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$assistenteLogged = isLogged() ? '1' : '0';
$assistenteRuolo = function_exists('ruoloCorrente') ? ruoloCorrente() : '';
?>
<div
  id="msai-assistant"
  class="msai"
  data-site-url="<?= clean(SITE_URL) ?>"
  data-current-page="<?= clean($assistentePagina) ?>"
  data-logged="<?= $assistenteLogged ?>"
  data-role="<?= clean($assistenteRuolo) ?>"
>
  <button
    type="button"
    id="msai-open"
    class="msai-open"
    aria-label="Apri assistente virtuale del Museo Storico Severi"
    aria-controls="msai-panel"
    aria-expanded="false"
  >
    <span class="msai-open-icon" aria-hidden="true">💬</span>
    <span class="msai-open-text">Serve aiuto?</span>
  </button>

  <section
    id="msai-panel"
    class="msai-panel"
    aria-label="Assistente virtuale del Museo Storico Severi"
    aria-live="polite"
    hidden
  >
    <div class="msai-header">
      <div class="msai-avatar" aria-hidden="true">🏛️</div>
      <div>
        <h2 class="msai-title">Assistente Severi</h2>
        <p class="msai-subtitle">Guida virtuale del museo</p>
      </div>
      <button type="button" id="msai-close" class="msai-close" aria-label="Chiudi assistente">×</button>
    </div>

    <div id="msai-messages" class="msai-messages" tabindex="0"></div>

    <div id="msai-suggestions" class="msai-suggestions" role="group" aria-label="Domande rapide"></div>

    <form id="msai-form" class="msai-form" autocomplete="off">
      <label class="sr-only" for="msai-input">Scrivi una domanda all'assistente</label>
      <input
        id="msai-input"
        class="msai-input"
        type="text"
        maxlength="180"
        placeholder="Scrivi una domanda..."
        aria-label="Scrivi una domanda all'assistente"
      >
      <button type="submit" class="msai-send" aria-label="Invia domanda">Invia</button>
    </form>

    <div class="msai-actions">
      <button type="button" id="msai-reset" class="msai-reset">🔄 Nuova domanda</button>
    </div>
  </section>
</div>
