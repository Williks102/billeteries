/*
 * Checkout (connecté, invité) : anti double envoi.
 * Après un envoi dont la validation HTML a réussi (l'événement « submit » n'est émis que dans ce cas),
 * le bouton [data-submit-once] est désactivé et son libellé devient data-loading-label (« Traitement… »).
 * Si la validation HTML échoue, aucun « submit » n'est émis : le bouton reste actif. Retour arrière du
 * navigateur (cache) : l'état est réinitialisé.
 */
(function () {
  'use strict';

  function reset(button) {
    var label = button.querySelector('[data-submit-label]');
    button.disabled = false;
    button.removeAttribute('aria-busy');
    if (label && button.dataset.originalLabel) label.textContent = button.dataset.originalLabel;
    var icon = button.querySelector('i');
    if (icon && button.dataset.originalIcon) icon.className = button.dataset.originalIcon;
  }

  document.querySelectorAll('form').forEach(function (form) {
    var button = form.querySelector('[data-submit-once]');
    if (!button) return;
    var label = button.querySelector('[data-submit-label]');
    var icon = button.querySelector('i');
    button.dataset.originalLabel = label ? label.textContent : '';
    button.dataset.originalIcon = icon ? icon.className : '';

    form.addEventListener('submit', function (event) {
      if (button.disabled) { event.preventDefault(); return; }
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      if (label) label.textContent = button.dataset.loadingLabel || 'Traitement…';
      if (icon) icon.className = 'fas fa-spinner fa-spin';
    });

    // Champ invalide signalé par le navigateur : le bouton doit rester utilisable.
    form.addEventListener('invalid', function () { reset(button); }, true);
  });

  window.addEventListener('pageshow', function (event) {
    if (event.persisted) document.querySelectorAll('[data-submit-once]').forEach(reset);
  });
})();
