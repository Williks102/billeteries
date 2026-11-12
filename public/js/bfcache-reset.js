/* Retour arrière : le navigateur peut restaurer la page telle qu'elle était, boutons « Ajout en cours… » ou
   « Ajouté au panier ! » désactivés compris, sans réexécuter le script. On recharge alors la page : tous les boutons
   et le compteur du panier repartent d'un état propre. */
(function () {
  'use strict';
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
})();
