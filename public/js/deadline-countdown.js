/*
 * Décompte discret vers l'échéance de paiement (page de confirmation d'une commande en attente).
 * Amélioration progressive : l'élément [data-deadline] est masqué (hidden) dans le HTML ; le texte statique
 * « À régler avant le jj/mm/aaaa à hh:mm » reste lisible sans JavaScript. Aucune logique métier : l'échéance
 * vient du serveur (Order::paymentDeadline()), le script ne fait que l'afficher.
 */
(function () {
  'use strict';

  function format(ms) {
    var minutes = Math.floor(ms / 60000);
    var days = Math.floor(minutes / 1440);
    var hours = Math.floor((minutes % 1440) / 60);
    var mins = minutes % 60;
    var parts = [];
    if (days) parts.push(days + ' j');
    if (days || hours) parts.push(hours + ' h');
    parts.push(String(mins).padStart(days || hours ? 2 : 1, '0') + (days || hours ? '' : ' min'));
    return parts.join(' ');
  }

  document.querySelectorAll('[data-deadline]').forEach(function (el) {
    var end = Date.parse(el.getAttribute('data-deadline'));
    if (isNaN(end)) return;

    function tick() {
      var left = end - Date.now();
      el.textContent = left > 0 ? 'Temps restant : ' + format(left) : 'Délai dépassé : la réservation peut être libérée.';
      el.hidden = false;
      if (left <= 0) clearInterval(timer);
    }

    var timer = setInterval(tick, 30000);
    tick();
  });
})();
