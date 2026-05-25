(function () {
  'use strict';

  var key = 'museo-severi-accessibilita-v1';
  var state = { fontLarge: false, reducedMotion: false };

  function activateDeferredStylesheets() {
    document.querySelectorAll('link[data-deferred-stylesheet]').forEach(function (link) {
      if (link.getAttribute('rel') !== 'stylesheet') {
        link.setAttribute('rel', 'stylesheet');
        link.removeAttribute('as');
      }
      link.removeAttribute('data-deferred-stylesheet');
    });
  }

  function load() {
    try {
      var saved = JSON.parse(localStorage.getItem(key) || '{}');
      state.fontLarge = !!saved.fontLarge;
      state.reducedMotion = !!saved.reducedMotion;
    } catch (e) {}
  }

  function save() {
    try { localStorage.setItem(key, JSON.stringify(state)); } catch (e) {}
  }

  function apply() {
    document.documentElement.classList.toggle('a11y-font-large', state.fontLarge);
    document.documentElement.classList.remove('a11y-high-contrast');
    document.documentElement.classList.toggle('a11y-reduced-motion', state.reducedMotion);
    document.documentElement.style.setProperty('--a11y-scale', state.fontLarge ? '1.08' : '1');
    document.querySelectorAll('[data-a11y-action]').forEach(function (button) {
      var action = button.getAttribute('data-a11y-action');
      var active = action === 'font' ? state.fontLarge : action === 'motion' ? state.reducedMotion : false;
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  function init() {
    activateDeferredStylesheets();
    load();
    apply();

    var toggle = document.getElementById('a11y-toggle');
    var panel = document.getElementById('a11y-panel');
    if (!toggle || !panel) return;

    toggle.addEventListener('click', function () {
      panel.hidden = !panel.hidden;
      toggle.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
    });

    document.querySelectorAll('[data-a11y-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-a11y-action');
        if (action === 'font') state.fontLarge = !state.fontLarge;
        if (action === 'motion') state.reducedMotion = !state.reducedMotion;
        if (action === 'reset') state = { fontLarge: false, reducedMotion: false };
        save();
        apply();
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !panel.hidden) {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
