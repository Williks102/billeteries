/*
 * Formulaire de création d'événement : anti double envoi (adapté de checkout-form.js).
 * Après un envoi dont la validation HTML a réussi (« submit » n'est émis que dans ce cas), les boutons
 * [data-submit-once] sont désactivés (après l'envoi, pour que le bouton cliqué reste dans les données) et le
 * bouton cliqué affiche « Enregistrement… ». Si le navigateur signale un champ invalide, ou au retour arrière
 * (pageshow, cache), l'état est réinitialisé.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-event-form]');
  if (!form) return;
  var buttons = Array.prototype.slice.call(form.querySelectorAll('[data-submit-once]'));
  var submitting = false;

  buttons.forEach(function (b) { b.dataset.originalHtml = b.innerHTML; });

  function reset() {
    submitting = false;
    buttons.forEach(function (b) {
      b.disabled = false;
      b.removeAttribute('aria-busy');
      b.innerHTML = b.dataset.originalHtml;
    });
  }

  form.addEventListener('submit', function (event) {
    if (submitting) { event.preventDefault(); return; }
    submitting = true;
    var clicked = event.submitter;
    setTimeout(function () {
      buttons.forEach(function (b) { b.disabled = true; });
      if (clicked && clicked.hasAttribute('data-submit-once')) {
        clicked.setAttribute('aria-busy', 'true');
        clicked.textContent = 'Enregistrement…';
      }
    }, 0);
  });

  form.addEventListener('invalid', reset, true);
  window.addEventListener('pageshow', reset);
})();
