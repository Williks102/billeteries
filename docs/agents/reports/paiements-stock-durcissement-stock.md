# paiements-stock : durcissement du plafond de réservations (N-A, N-B, N-C, N-D, I-A, I-B, M2, M3)

Branche `claude/durcissement-stock-n-a-b-c` (depuis origin/main 0ff47ad). Sources : securite-revalidation-finale-paiement-manuel.md, relecteur-final-paiement-manuel.md.

## Constats vérifiés dans le code avant correction
- N-B/I-A : plafond global 50 billets/événement, CheckoutService.php:196-205 (avant) ; message trompeur LIMIT_ERROR (l.27) : VRAI.
- N-A/I-B : `User::where('email')->first()` dans la transaction invité (GuestCheckoutController.php:147, appelé l.78 dans DB::transaction) avant le verrou events ; SUM non verrouillant : VRAI (raisonnement InnoDB, non exécutable ici).
- N-C/M2 : buildEventGroups sans tri (l.77) : VRAI.
- N-D : assertWithinOrderLimits comptait `billing_email OR user_id` sans filtre guest_token : VRAI ; dashboard/stats non filtrés (AcheteurController l.32,53 ; Acheteur/OrderController l.66 ; profile.blade.php l.128) : VRAI. Les « payées » ne changent pas (une commande payée est visible de toute façon).

## Corrections
- N-A : `eventPendingTicketsQuery` (lockForUpdate sur le SUM : lecture verrouillante = dernières données committées) ; capacité lue en `FOR UPDATE` (ticket_types, id croissant) APRÈS le comptage ; lecture du compte invité déplacée AVANT la transaction (GuestCheckoutController), seul l'INSERT reste dedans. Les lectures non verrouillantes restantes dans la transaction (relation `$order->event`) viennent après les verrous et ne servent à aucun comptage.
- N-B : plafond événement = clamp(ceil(0.3 x capacité), 10, 200) (`PENDING_RATIO`, `PENDING_MIN`, `MAX_PENDING_TICKETS_PER_EVENT`). Quota par acteur en BILLETS : 10 par email normalisé (minuscules, trim, alias `+tag` retiré ; points Gmail NON normalisés) ou compte connecté ; 20 par IP (`MAX_PENDING_TICKETS_PER_IP`, plus large que 10 pour le CGNAT, fenêtre invité, compteur non décrémenté à l'annulation). Messages distincts (LIMIT_ERROR « limite personnelle », EVENT_LIMIT_ERROR). Log warning structuré (event_id, pending, cap) ; alerte email aux admins au plus 1 fois/heure/événement (Cache::add atomique, envoyée en `terminating`, sans PII).
- N-D : le connecté n'est compté que sur `user_id = X AND guest_token IS NULL` ; dashboard, stats et profil acheteur filtrés par `visibleInBuyerHistory`.
- N-C : `ksort($groups)` dans buildEventGroups.

## Valeurs choisies et nombre d'acteurs pour geler un événement
Plafond dur 200 : au-delà de ~670 places, bloquer 30 % du stock n'est plus proportionné ; 200 billets exigent déjà >= 20 emails et >= 10 IP. Pour geler : événement de capacité C, plafond P = clamp(ceil(0,3 C), 10, 200) ; il faut >= ceil(P/10) emails distincts ET >= ceil(P/20) IP distinctes (pour un seul acteur : jamais). Exemples : C=100 -> P=30 -> 3 emails et 2 IP ; C=20 -> P=10 -> 1 email suffit (petit événement : le plancher 10 reste gelable par 1 acteur, c'est le compromis du plancher ; baisser PENDING_MIN à 6 si besoin) ; C>=667 -> P=200 -> 20 emails et 10 IP. La création d'emails jetables est gratuite : ces nombres sont un coût de friction, pas une barrière (voir risques).

Ordre des verrous : events (id croissant) -> orders/order_items (comptage) -> ticket_types (id croissant). releaseStock/confirmation/expiration : commande puis types ; aucun ne prend events après orders : pas de cycle.

## Tests
- tests/Feature/PendingCapsTest.php (13 tests, ajouté à test:payments) ; test existant de plafond adapté (message événement, assertions inchangées).
- tests/MySql/EventPendingCapConcurrencyTest.php + reserve_worker.php : `composer run test:mysql`, commande exacte dans docs/testing-mysql.md. Skippé sans MySQL (vérifié : 2 skipped), `php -l` OK, JAMAIS exécuté sur MySQL.

## Non prouvable ici
SQLite ignore FOR UPDATE et REPEATABLE READ : seul le SQL généré est vérifié (grammaire MySQL en mode pretend). Le comportement réel (instantané, deadlock, durée des verrous : le comptage verrouille toutes les commandes pending de l'événement pendant la transaction) reste à prouver sur MySQL.

## Constat hors périmètre
La vue `acheteur.orders.index` est introuvable (View not found) : la liste des commandes acheteur retombe sur la branche d'erreur, qui appelle `collect()->paginate()` inexistant (500). Préexistant, non touché (le test utilise une vue stub temporaire).

## À revalider
securite : N-A (SQL FOR UPDATE, lectures avant verrous), N-B (quotas, contournement par emails jetables / IPv6, alerte), N-D. relecteur : plafond relatif, absence de régression des tests existants, alerte `terminating`. qa-tests : exécuter test:mysql sur une vraie base.

## Risques restants
Quotas acteur/IP vérifiés hors transaction (course bornée par throttle 10/min et par le plafond événement, qui est sous verrou) ; emails jetables et IPv6 contournent les quotas (vérification d'email / captcha = N-B points 2-3, non faits) ; points Gmail non normalisés ; alerte = email seulement (pas de notification en base).
