# Relecture 2 : cycle de corrections du durcissement stock (branche claude/durcissement-stock-n-a-b-c)

Relecteur indépendant (n'a pas participé à l'implémentation). Lecture seule. Base origin/main 0ff47ad ; HEAD relu 47d6d6c
(cycle `3ded17a..47d6d6c`). Le commit baa54a1 apparu pendant la relecture (rapport `securite`) ne touche que docs/.

## Verdict : APPROUVÉ SOUS RÉSERVES

Aucun Bloquant. B1, I1 et I2 de la relecture précédente sont résolus dans leur périmètre (preuves par mutation ci-dessous).
Deux Importants restent : I1 n'est fermé que type par type (panier multi-types), I2 retire le QR de la transaction mais le
laisse synchrone sur le chemin de réponse.

## Exécution
`composer run check` (HEAD 47d6d6c, /home/user/billeteries) : `OK (8 tests, 9 assertions)` (0,30 s) puis
`OK (130 tests, 785 assertions)` (3,60 s) ; durée murale totale 5,1 s (contre 2 min 27 s à la relecture précédente).
`composer run test:mysql` : `Tests: 2, Skipped: 2` (pas de MySQL : skip propre) ; `php -l` OK sur tests/MySql/*.
`vendor/bin/phpunit` sans argument : 140 tests, aucun test MySql listé (`--list-tests`), 1 erreur préexistante
ExampleTest (MissingAppKeyException, pas de .env ; hors check).

## B1 : RÉSOLU [VÉRIFIÉ]
- app/Services/CheckoutService.php:382-418 : `Cache::add` pris dans le callback `terminating`, après le rollback.
- Mutation (worktree jetable) : remettre `Cache::add` avant `terminating` => `test_admin_alert_is_sent_once_with_the_real_database_cache_store`
  ROUGE (« actual size 3 matches expected size 1 »). Le test utilise un store `database` réel sur la même connexion.
- Parallèle : `DatabaseStore::add` = `insertOrIgnore` (vendor/.../Cache/DatabaseStore.php:213), atomique ; entrées expirées
  supprimées par `get()` (l.137-145) : pas de créneau bloqué après 1 h.
- Exception/redirection : le refus est attrapé par les contrôleurs (CheckoutRefusedException) ; `terminate` est appelé dans
  tous les cas (réponse rendue par le Handler) ; le drapeau `$done` empêche le rejeu.
- Échec d'envoi (expérience, Mail::raw qui lève) : 3 refus => `MAIL_ATTEMPTS=3`, 0 ligne de cache. Pas d'email en rafale
  (les envois échouent), mais une tentative SMTP + un Log::error par requête refusée tant que le SMTP est en panne. Mineur m1.

## I1 : RÉSOLU pour un type de billet, PAS pour le total du panier
- CheckoutService.php:41-46 `maxTicketsPerOrder` = min(max_per_order, quota) ; quota 0/invalide => max(1, …) = 1 ;
  max_per_order null/0 => quota ; très grand => quota. Types existants à 11-20 => affichés et plafonnés à 10.
- Contrôle serveur : buildEventGroups (l.90-94) par ligne ; assertWithinOrderLimits (l.204-207) sur le total.
  CartController.php:61-67 (multi) et 132-136 (simple) ; vues booking-form-desktop.blade.php:42,58 et booking-form-mobile.blade.php:101,612.
  Vues compilées (`view:cache`) et `php -l` OK ; seul le nombre affiché change ; le JS (events/show.blade.php:245,
  mobile l.636) lit toujours data-max / ticketMaxes : pas de régression de sélecteur [lecture, pas de navigateur].
- Fichiers hors liste initiale (CartController, booking-form-*) : justifiés (ce sont l'endroit où l'acheteur choisit la
  quantité ; sans eux, l'UI proposerait encore 20).
- Mutations : retrait du contrôle buildEventGroups => `test_per_order_cap_uses_the_type_max…` ROUGE ; retrait des deux
  contrôles serveur => 2 ROUGES ; retrait du contrôle CartController => `test_cart_and_booking_form…` ROUGE.
  NON détectées : retrait de `if ($newTickets > $maxTickets)` (l.204) seul, et retour des vues à `max_per_order ?? 10`.
- IMPORTANT I-a [VÉRIFIÉ par expérience] : panier de 2 types à 6 billets : `cart.add` => 200, panier = 12 ; checkout invité
  => « Maximum 10 billets par commande. Réduisez la quantité demandée. ». L'UI affiche « Max 10/commande » PAR type et le
  panier accepte un total que le checkout refusera toujours (même chose sur plusieurs événements, où « par commande » est
  inexact : le quota porte sur tout le passage en caisse). Correctif : dans CartController (multi et simple), refuser si
  somme des quantités du panier + demandée > `CheckoutService::maxTicketsPerOrder()` avec un message « au total » ; afficher
  ce total dans les formulaires ; ajouter le test 6+6 (qui couvrira aussi la mutation l.204). Exécutant : paiements-stock
  (JS du total : ui-design).

## I2 : RÉSOLU pour la transaction ; latence toujours sur le chemin de réponse
- createOrderItemWithTickets n'appelle plus de QR (CheckoutService.php:444) ; `generateQrCodes` (l.472-489) est appelé
  après le commit (CheckoutController.php:177, GuestCheckoutController.php:88) ; échecs attrapés, journalisés via
  `logFailure` (classe + fichier:ligne, sans message brut).
- Mutation : QR remis dans la transaction et appels après commit retirés => `test_qr_codes_are_generated_after_commit…`
  ROUGE (niveaux de transaction ≠ [0,0,0]).
- Absence de QR : vues protégées (`@if($ticket->qr_code)` acheteur/order-detail.blade.php:200, admin/tickets/index:408,515,
  `@if($ticket->qr_code_url)` ticket(s)/show:42) ; PDF via `Ticket::getReliableQrCode` (Ticket.php:182) qui régénère ou
  produit un placeholder. Emails d'instructions : n'embarquent aucun QR (emails/*.blade.php) : pas de mauvais contenu.
  Comportement identique à l'avant-branche en cas d'échec : pas de régression.
- IMPORTANT I-b [VÉRIFIÉ par lecture ; durées réelles en prod SUPPOSÉES] : la génération reste synchrone AVANT
  `recordGuestOrders`, `rememberGuestOrder`, `sendManualInstructions` et la purge du panier (GuestCheckoutController.php:88-98,
  CheckoutController.php:177-188). Pire cas par billet (réseau qui ne répond pas) : 3 configurations Google ×
  (Http 8 s + file_get_contents 5 s) = 39 s avant le repli local SimpleSoftwareIO (QRCodeService.php:91-118, 31-37) ;
  × 10 billets (quota) = 390 s. Au-delà du timeout passerelle (souvent 60 s), l'acheteur voit une erreur alors que ses
  commandes existent, sans preuve de session invité ni email, et son nouvel essai tombe sur le quota. Correctif (non
  implémenté) : sortir le QR du chemin de réponse : `dispatch(fn () => app(CheckoutService::class)->generateQrCodes(...ids))->afterResponse()`
  ou un job en file (table jobs existante), au minimum déplacer l'appel après la purge du panier ; mieux : générer le QR à
  la confirmation du paiement (les billets sont `reserved` jusque-là). Exécutant : paiements-stock.
- Tests : le faux QRCodeService (tests/Fakes/FakeQRCodeService.php) remplace un service qui n'avait AUCUN test
  (aucune référence dans tests/ à 3ded17a) : rien de perdu. Mineurs m2-m3.

## S-1 partiel
- normalizeIp (CheckoutService.php:268-279), sondé : `::ffff:203.0.113.7` et `::FFFF:cb00:7107` => 203.0.113.7 ; compressions
  `2001:db8::1`, `2001:0db8:0000:0000:ffff::1` => même /64 ; adresse invalide => inchangée ; `$request->ip()` déjà validée.
  Mineur m5 : NAT64 `64:ff9b::a.b.c.d` et `::a.b.c.d` regroupent tous leurs clients IPv4 dans un seul /64.
- normalizeEmail (l.126-145) : utilisé UNIQUEMENT dans buyerPendingRows (l.163, 182) pour les quotas : l'email stocké et
  envoyé n'est pas modifié [VÉRIFIÉ par grep].
- Balayage Gmail (l.169-177) : `whereRaw` à constantes, pas d'entrée utilisateur (pas d'injection) ; restreint par
  `payment_status` (index orders_status_expires_idx), mais `like '%@gmail.com'` sans index et toutes les lignes Gmail en
  attente chargées en PHP à chaque checkout invité : borné par la fenêtre de réservation (24-48 h), acceptable aujourd'hui,
  à surveiller (Mineur m5 ; suggestion : colonne email normalisé indexée).

## S-4
- MySqlTestGuard.php:15-29 + tests/Unit/MySqlTestGuardTest.php (dans check). `defaultTestSuite="Unit,Feature"` vérifié.
- Mineur m4 : `/^[a-z0-9_]*_test$/` accepte `billeterie_test\n` (`$` PCRE avant saut de ligne final : utiliser `\z` ou /D) ;
  le refus APP_ENV=production est inopérant si APP_ENV n'est défini que dans .env (phpunit.xml pose APP_ENV=testing, non
  écrasé) [lecture] : la protection repose sur le nom et l'hôte.

## Portée et conventions
Fichiers hors brief : CartController + booking-form-* (justifiés, I1), tests/Fakes, tests/TestCase.php (justifiés, I2).
Aucune migration, frais de service/commission non touchés (grep du diff), aucun secret, messages en français, pas de
logique métier ajoutée dans les routes ; les vues appellent un helper statique de calcul (acceptable, pas de requête).

## Régressions de la relecture précédente : aucune
ksort CheckoutService.php:110 ; lockForUpdate l.345, 359, 363 ; guest_token NULL pour le connecté l.160 ; plafond relatif
l.324-331 ; ViewReferencesTest + KNOWN_MISSING présents et dans `test:routes`.

## Mineurs
- m1 CheckoutService.php:405-409 : échec SMTP => `Cache::forget` => une tentative par requête refusée ; préférer un court
  délai (`Cache::put($key, 1, 300)`).
- m2 CheckoutCycleFixesTest::test_a_qr_failure_never_breaks_the_order passe même sans le try/catch du service (mutation :
  OK) car Ticket::generateQrCode (Ticket.php:156-176) attrape déjà `\Exception` ; il n'affirme rien sur les logs alors que
  Ticket.php:174 journalise `$e->getMessage()` (préexistant). L'appel QR du checkout CONNECTÉ (CheckoutController.php:177)
  n'est couvert par aucun test (mutation : suite verte).
- m3 `Http::preventStrayRequests()` ne bloque pas les replis `file_get_contents` de QRCodeService : « aucun réseau » ne tient
  qu'au faux service ; QRCodeService sans test propre (préexistant).
- m4, m5 : voir S-4, S-1. m6 (préexistant) : max_per_order=0 => plafond = quota au checkout mais `canPurchaseQuantity`
  (TicketType.php:107-112) refuse au panier.

## Suggestions
1. QR générés à la confirmation du paiement plutôt qu'au checkout. 2. Colonne `billing_email_normalized` indexée.
3. Afficher le plafond « 10 billets au total » dans le formulaire de réservation.

## Non vérifié
test:mysql réel (pas de MySQL) ; latence réelle des API QR en production ; rendu visuel et JS en navigateur ;
l'affirmation du rapport implémenteur sur la régénération via Admin\TicketController ; « test qui échoue sur appel HTTP
réel » : vrai pour la façade Http uniquement (m3).

Recommandation : fusion possible après décision de l'utilisateur ; corriger I-a et I-b (paiements-stock) puis relecture
ciblée de préférence avant production.
