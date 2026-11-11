# Vues acheteur : QR après paiement, commande en attente, bandeau démo

## Livré
- `partials/demo-banner` (inclus dans layouts app, acheteur, promoteur, admin) : bandeau fixe en bas, `role="status"`, ne capte aucun clic, `@if(!empty($demoMode))`.
- `acheteur/ticket-detail` (créée, route 500 corrigée) : détails événement, QR en `<img data:>` seulement si `isIssued()`, sinon message d'attente + lien vers la commande. `TicketController::show` ne passe que `order`, `isIssued`, `qrDataUri` ; autorisation inchangée (autre acheteur : 403).
- Composants `x-order-status-badge` et `x-payment-instructions` (coordonnées en champs lecture seule, sélection en un appui, sans JS) ; `checkout/partials/manual-payment` les utilise (plus de logique dans la vue).
- `order-detail` : bloc « En attente de paiement » + date limite, QR affiché directement via `qrDataUri()`, mention « MODE DÉMO : paiement simulé ». Modal et `public/js/acheteur-order-detail.js` (innerHTML) supprimés. `orders` : badge et date limite. `tickets` : `qrDataUri()` (plus de `try` dans la vue), données passées au JS par `data-*`.
- Confirmations connecté/invité : message démo, bouton « Voir mes billets », boutons pleine largeur sur mobile.

## À signaler (hors périmètre)
- `tickets.download` (route `Ticket` PDF) appelle la vue `tickets.pdf` qui n'existe pas : 500. La page billet propose donc le PDF de la commande.
- `Acheteur\OrderController::download` ne vérifie ni propriétaire ni statut payé (IDOR possible) : à corriger côté contrôleur.
- Les layouts promoteur/admin laissent un tampon de sortie ouvert (défaut antérieur, observé sur HEAD).
- Autorisation de `Acheteur\TicketController::show` via `tickets.order_item_id` : un billet sans cette valeur renvoie 403 (échec fermé).

## Preuves
Voir le message de retour (check, mutations, captures dans le dossier scratchpad de la session).
