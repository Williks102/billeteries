# Audit sécurité — branche claude/panier-checkout (parcours d'achat simplifié)

Périmètre : `git diff origin/main...HEAD`, lecture seule. Rien n'a été exécuté (tests non lancés : « vérifié » = lu dans le code).

## Verdict : PR OUI SOUS RÉSERVES
Aucun Critique. 1 Moyen à corriger (ou accepter explicitement), le reste est faible.

## Critique / Élevé
Aucun.

## Moyen

### M1 — Billets d'événements non publiés / passés achetables via `checkout.direct` (et panier)
- Preuve : `app/Http/Controllers/CheckoutController.php:103-130` (`direct`) ne contrôle que `exists:events,id` + `canPurchaseQuantity` ; `app/Models/TicketType.php:82-88` (`isAvailable`) vérifie `is_active`, stock, fenêtre de vente, mais jamais `events.status = 'published'` ni la date de l'événement ; `CheckoutService::buildEventGroups` (`:78-117`) et `createOrderItemWithTickets` (`:~425`) non plus. La fiche publique, elle, refuse les non publiés (`EventController.php:20`).
- Scénario : un invité (désormais sans compte) POST `/checkout/direct` avec un `ticket_type_id` d'un événement brouillon/annulé/terminé (IDs séquentiels, devinables) : la réservation passe et consomme du stock. Le défaut préexistait via `cart.add`, mais la route est maintenant ouverte aux invités et sans étape intermédiaire.
- Correctif (agent paiements-stock) : dans `TicketType::isAvailable()` (donc aussi `reserveTickets`, sous verrou) ajouter `$this->event?->status === 'published'` et `event_date >= today` ; ajouter les tests ci-dessous.

## Faible

- F1 — `ticket_type_id` inexistant → `findOrFail` donne un 404 au lieu d'une erreur de validation (`CheckoutController.php:~100`). Oracle d'existence d'ID négligeable (les ID sont déjà publics) ; valider `tickets.*` avec clés `exists:ticket_types,id` ou `firstWhere` + message générique.
- F2 — `direct_booking` n'a plus de minuteur (`booking_timer` supprimé, `CheckoutController.php:117-119`) : la sélection persiste toute la session et masque le panier tant que `cart.show`/`cart.add` ne la purge pas (`CartController.php:17,229`). Pas d'impact prix/stock : tout est recalculé à `process` (`GuestCheckoutController.php:66-70` → `buildEventGroups`, prix lus en base ; quantité < 1 refusée `:84`). Seule la page `checkout.guest.show` (`:26-33`) n'a aucun contrôle de disponibilité et affiche les prix périmés de la session (affichage seul). Correctif : stocker un horodatage dans `direct_booking` et l'expirer, ou revérifier la dispo dans `GuestCheckoutController::show`.
- F3 — Le throttle de `/direct` partage le préfixe `checkout` avec `process` (`routes/web.php:106-108`) : un rafraîchissement/abus de `/direct` consomme le budget de `process` (10/min) pour la même IP/utilisateur (auto-DoS léger, NAT). Utiliser un limiteur nommé distinct.
- F4 — `Storage::exists($item['event_image'])` (`resources/views/components/checkout-lines.blade.php:6`) : lecture seule, mais la valeur vient de la session, elle-même alimentée par `$event->image` (base), jamais d'un champ client. Pas de traversée possible. Une E/S disque par ligne ; préférer `$event->image` sans `exists` si le coût compte.

## Points demandés — résultat
1. `direct` ouverte aux invités : vérifié. Prix, nom, event_id de session ne sont PAS de confiance : `buildEventGroups` relit `TicketType` (prix, event) et rejette l'incohérence event_id (`:90-92`). Quantités : `integer|min:0|max:20` (`direct`), plafonds `max_per_order`/quota acheteur (`:94-98`), plafonds en attente par e-mail et par IP (`assertWithinOrderLimits` appelé `GuestCheckoutController.php:68`), stock sous `lockForUpdate` (`TicketType.php:145-165`). CSRF : route dans le groupe `web` (aucune exclusion), le JS envoie `X-CSRF-TOKEN`. Throttle appliqué (`routes/web.php:106`). Pas d'IDOR : aucune ressource d'autrui lue. `direct` ne réserve rien : pas d'accaparement possible à ce stade. Sauf M1/F1-F3 : OK. Fixation de session : `direct_booking` ne contient aucun secret ; la régénération de session au login est inchangée.
2. Preuve `guest_token` (C1) : non affaiblie. `rememberGuestOrder` (`CheckoutService.php:610-624`) inchangé, `hash_equals` conservé ; la purge de session après `process` ne touche pas `guest_order_tokens`.
3. XSS : aucun `{!! !!}` dans les vues de checkout/composants modifiés ; `successMessage` est un texte statique sans donnée utilisateur ; `cart.js`, `checkout-form.js`, `deadline-countdown.js` n'utilisent ni `innerHTML` ni `insertAdjacentHTML` (le `onerror` inline de `checkout-lines.blade.php:7` est statique). `data-deadline` = ISO 8601 serveur échappé.
4. Toast supprimé : plus aucune référence à `flash-messages` dans `resources/`, `public/js/`, `tests/`. Pas d'usage cassé détecté ; vérifier visuellement que le layout affiche bien `session('success'/'error')` (supposé, non lu en détail).
5. `DurationHelper` : `intdiv(…,60)` sans division par zéro, `max(0,…)`, valeurs issues de la config ; `formatPrice` : affichage seul, pas de fuite. Note : `(int) $amount === 0` affiche « Gratuit » aussi pour un montant invalide/non numérique (affichage uniquement).
6. Secrets / PII : aucun secret ; e-mails de test en `@example.test`, téléphone `0102030405` fictif, jetons `tok-ui-*` factices ; les 2 captures `docs/audit-visuel/etape3-*.png` ne montrent que des données fictives (événement « Grand Concert Abidjan Live 2027 »).
7. `Storage::exists` : voir F4 (lecture seule, chemin non contrôlé par le client).

## Couverture de tests
`GuestCheckoutSecurityTest`, `CheckoutAbuseLimitsTest`, `PendingCapsTest`, `StockAndOrderFlowTest` restent valides mais ne passent que par le panier de session (`cart`) ; aucun n'utilise `direct_booking`. `CartCheckoutUiTest` couvre redirection, total recalculé, purge, validation de base, présence du middleware throttle. Tests manquants :
- `direct` sur événement brouillon / annulé / passé / `is_active=false` / hors fenêtre de vente → refusé (échouera tant que M1 n'est pas corrigé).
- `ticket_type_id` appartenant à un autre événement que `event_id` → erreur ; quantité négative, 21, non entière, tableau imbriqué.
- Session `direct_booking` falsifiée (prix 1, quantité 9999) → commande au prix base, refus > plafond (via `guest.process`).
- Plafonds e-mail/IP/quota billets atteints en passant par `direct` (réutiliser `PendingCapsTest` avec `withSession(['direct_booking'=>…])`).
- Dépassement réel du throttle : 11e POST `/checkout/direct` en 1 min → 429 (le test actuel ne vérifie que la présence du middleware).
- Preuve `guest_token` après un flux `direct` : confirmation invalide sans la session propriétaire.
- Concurrence : deux `direct` + `process` sur le dernier billet → un seul réussi.
