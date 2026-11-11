# ui-design : textes publics alignés sur le paiement manuel

Pages modifiées : pages/terms (art. 7 et 8, 6 étapes d'achat, livraison), pages/how-it-works (étapes 3-4, avantage "Billets instantanés"), pages/pricing (bloc "Moyens de paiement acceptés" : virement / Mobile Money / espèces, plus de carte ni coupon), emails/welcome (ligne "Payer en sécurité par Orange Money, MTN").
Faits repris du code : 48 h (24 h invité non connecté), billets après confirmation manuelle, libération auto à l'expiration, frais de service 500 FCFA par commande (CheckoutService::SERVICE_FEE), lien de confirmation pour l'invité. Coupon retiré : aucune fonctionnalité coupon dans app/ ni routes.

## Clauses laissées intactes, à faire décider par le propriétaire
1. terms art. 8 : "En cas de non livraison des articles plus de deux (2) heures après la commande, le Client pourra procéder à la résolution de la vente et demander le remboursement". Contredit le flux (billets émis seulement après paiement manuel confirmé, réservation 24/48 h). Proposition (non appliquée) : "Le délai de livraison court à compter de la confirmation du paiement. En l'absence de livraison [À COMPLÉTER PAR L'ORGANISATEUR : délai] après confirmation, le Client peut demander la résolution de la vente et le remboursement."
2. terms art. 8 : "délai de sept (7) jours à compter de la date d'achat ... pour signaler la non réception". Proposition : faire courir le délai à compter de la confirmation du paiement.
3. terms art. 7 : "CLICBILLET se réserve la propriété des articles jusqu'au règlement complet ... encaissement du prix TTC" : compatible, inchangé.
4. terms art. 6 : "frais de service (taxes, frais de paiement...)" : "frais de paiement" n'a plus de sens ; formulation à valider.
5. pricing l.347 et FAQ l.501 : "500 FCFA/ticket" et commission 8 % côté organisateur ; le code applique 500 FCFA par commande (pas par billet). À trancher (texte inchangé).
6. privacy l.85 "Informations de paiement" et l.154 "Prestataires de paiement : services certifiés pour les transactions" : plus de prestataire de paiement en ligne ; à reformuler par le propriétaire.
7. terms art. 9 : "disponible dans le compte" : l'invité n'a pas de compte (partiellement corrigé en art. 8).
8. contact.blade l.161 (annulation) : inchangé, dépend de la politique.

## Placeholder ajouté
terms art. 8 : [À COMPLÉTER PAR L'ORGANISATEUR : délai de confirmation du paiement].

## Non vérifié
Pas de rendu Playwright (mobile 390 px / desktop) ni capture : non effectué. `composer run check` rouge, mais hors de mon périmètre : GuestCheckoutSecurityTest::test_account_creation_after_purchase_route_no_longer_exists (routes/web.php, modifié en parallèle par paiements-stock) ; aucun test ne dépend des textes modifiés (non vérifié exhaustivement : à confirmer par qa-tests).
