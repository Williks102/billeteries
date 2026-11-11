# relecteur : durcissement du plafond de réservations (branche claude/durcissement-stock-n-a-b-c)

Relu : origin/main 0ff47ad...3ded17a (8344453, 996e9e1, 3ded17a). Le commit a920a01 (rapport securite, docs seules) est arrivé pendant la relecture : aucun code touché. Je n'ai pas participé à l'implémentation.

## Verdict : REFUSÉ (1 Bloquant, correctif court)

## composer run check (HEAD 3ded17a)
`OK (8 tests, 9 assertions)` puis `OK (115 tests, 731 assertions)` ; durée réelle 2 min 27 s (user 25 s).

## Bloquant
**B1 [VÉRIFIÉ] L'alerte admin « au plus 1/h/événement » n'est pas limitée avec le cache par défaut.**
`CheckoutService.php:324` fait `Cache::add(...)` DANS la transaction (`createOrder` -> `assertEventPendingTicketsWithinCap` -> `alertAdminsEventCapReached`, appelés sous `DB::transaction` dans les deux checkouts). `.env.example:27` `CACHE_STORE=database` ; `config/cache.php:43` `connection` null = même connexion PDO. Le `CheckoutRefusedException` annule la transaction, donc aussi l'INSERT du marqueur ; le callback `terminating` est déjà enregistré et part. Preuve (test jetable, worktree supprimé) : cache `database` + table `cache`, 3 refus consécutifs donnent 3 emails (attendu 1). Le test `test_admin_is_alerted_once_per_hour_per_event_without_pii` passe parce que phpunit.xml force `CACHE_STORE=array`.
Conséquence : un email synchrone aux admins par checkout refusé (flood de boîte, bloqué seulement par le throttle 10/min/IP).
Correctif : faire le `Cache::add` dans le callback `terminating` (après le rollback), ou lever l'alerte depuis le contrôleur après le `catch`. Ajouter un test avec un store `database`. Agent : paiements-stock.

## Importants
**I1 [VÉRIFIÉ] Une commande unique de 11 à 20 billets est refusée à tout le monde, avec un message faux.** Les promoteurs peuvent régler `max_per_order` jusqu'à 20 (`Promoteur/TicketTypeController.php:56,222`, `Admin/EventController.php:190,628`). Le quota acteur est de 10 (`CheckoutService.php:165-168`). Un acheteur sans aucune commande qui demande 12 billets reçoit LIMIT_ERROR « vous avez trop de réservations en attente » (`CheckoutService.php:29`), ce qui est faux. Avec les valeurs par défaut, une commande de 10 billets ou moins sur un événement vide passe toujours, puisque le plancher de l'événement (10) est au moins égal au quota.
Correctif : prévoir un message distinct quand `$newTickets > $maxTickets` à lui seul (« au plus N billets non payés à la fois »), et soit aligner `max_per_order` sur le quota, soit relever le quota (décision du propriétaire). Agent : paiements-stock + propriétaire.

**I2 [VÉRIFIÉ, préexistant, hors périmètre] Cause de la lenteur des tests et du hook pre-push : des appels réseau réels, un par billet, dans la transaction.** `CheckoutService.php:377-379` : `method_exists($ticket, 'generateQRCode')` est insensible à la casse et trouve `Ticket::generateQrCode` (`Ticket.php:~155`). Cette méthode appelle `QRCodeService` : Google Charts `Http::timeout(8)` (`QRCodeService.php:96`), puis `file_get_contents`, puis d'autres API (`:325,362,389`). Ni bcrypt ni sleep n'y sont pour quelque chose.
Mesures :
- check en ligne : 2 min 27 s (CPU 25 s) ; le même check sans réseau (`unshare -rn`) : **5,4 s**, avec le même `OK (115 tests, 731 assertions)` ;
- PendingCapsTest : 90 s, environ 0,6 s par billet réservé ;
- un seul test : 10 s en ligne, 0,27 s hors ligne.
En production, ces appels ont lieu sous les verrous events, orders pending de l'événement et ticket_types. Cette branche élargit le verrou (FOR UPDATE sur toutes les commandes pending de l'événement) : chaque checkout d'un événement attend les appels HTTP des autres.
Correctifs : (a) tests : lier un QRCodeService factice dans `tests/TestCase.php` (qa-tests) ; (b) prod : générer le QR hors transaction (après commit ou à l'affichage) (paiements-stock / architecte).

## Mineurs
- `phpunit.xml:14` : le commentaire « HORS des suites par défaut » est faux. `phpunit --list-tests` liste les 2 tests MySql et un `php artisan test` complet les lance. Ils sont skippés sans configuration, mais lancent `migrate:fresh` si `TEST_MYSQL_DATABASE` est présent dans l'environnement ou le `.env`. Correctif : `defaultTestSuite="Unit,Feature"`.
- `tests/MySql/EventPendingCapConcurrencyTest.php:29` : le garde-fou « le nom contient test » est faible (latest, contest…). Exiger un suffixe `_test`.
- `CheckoutService.php:131` (N-D) : le quota du connecté ignore ses propres commandes passées en invité. Le résultat est deux réservoirs : 10 billets en connecté (compte de plus de 24 h, sans plafond IP) plus 10 par email en invité. C'est un compromis : à arbitrer par securite.
- Quotas acteur et IP lus hors transaction (`CheckoutController.php:163`, `GuestCheckoutController.php:75`, compteur IP incrémenté après commit) : des requêtes parallèles d'un même acteur peuvent dépasser 10 ou 20. La course est bornée par le throttle (`routes/web.php:97,107`) et par le plafond de l'événement, qui est sous verrou. Le rapport le dit (Risques restants). La docblock `CheckoutService.php:148-153` est périmée (« Message neutre ») et ne le mentionne pas.
- IPv6 : clé = sha1 de l'adresse complète (`CheckoutService.php:215`), contournable en changeant d'adresse dans un /64 (admis par le rapport) : à voir avec securite.
- Capacité (`CheckoutService.php:305`) : elle compte les types inactifs ou hors période de vente, et la capacité totale plutôt que le restant. Sur un événement presque complet, le plafond peut couvrir tout le reste. Impact borné par le plafond dur et les quotas.
- `test_pending_count_is_a_locking_read_on_mysql_grammar` teste le helper, pas son emploi à `CheckoutService.php:303` : une requête inline non verrouillante passerait [SUPPOSÉ, à la lecture].
- Écart du rapport : la section « hors périmètre » dit `acheteur.orders.index` « préexistant, non touché ». Faux : la régression vient de 140b124 et 3ded17a la corrige. La branche d'erreur `collect()->paginate()` (`Acheteur/OrderController.php`) reste un 500 préexistant.
- [SUPPOSÉ, MySQL] Le SUM FOR UPDATE pourrait passer par `orders_status_expires_idx` et verrouiller les commandes pending (et les gaps) de TOUS les événements. À vérifier avec EXPLAIN pendant `test:mysql`.

## Suggestions
1. ViewReferencesTest : la regex ignore `View::make` et les vues de Mailables (`view:` / `markdown:`).
2. Normaliser l'IPv6 en /64 pour les clés IP.

## Points vérifiés (OK)
- **Ordre des verrous** : events, puis orders/order_items, puis ticket_types (id croissant). Confirmation et expiration (`OrderPaymentService.php:39,192`, `Order.php:235`) verrouillent la commande puis les types, jamais events : pas de cycle trouvé. Toutes les lectures servant au plafond sont verrouillantes, donc à jour, y compris pour le 2e événement du panier. Lecture du compte invité déplacée avant la transaction.
- **Cas limites** : capacité 0 ou aucun type de billet donne un plafond de 10 (puis refus de stock par reserveTickets) ; si max < min, le min(max(...)) retourne le max.
- **Messages** : constantes distinctes et génériques, affichées par `layouts/app.blade.php:371-374` pour les deux checkouts (`@extends('layouts.app')`) ; soumission classique, pas d'AJAX. Rendu non exécuté.
- **Mutations (worktree jetable, supprimé)** : chaque correctif retiré fait échouer au moins un test.

  | Correctif retiré | Test(s) en échec |
  |---|---|
  | `ksort` | test_carts_a_b… |
  | `lockForUpdate` du SUM | test_pending_count… |
  | `whereNull(guest_token)` | test_third_party… |
  | plafond fixe à 50 | 4 tests |
  | lecture user remise dans la transaction | test_guest_checkout_reads… |
  | une vue renommée | ViewReferencesTest |

- **CheckoutAbuseLimitsTest** : non affaibli (LIMIT_ERROR -> EVENT_LIMIT_ERROR, valeur exacte, mêmes comptes).
- **3ded17a** : les 4 renommages inversent exactement ceux de 140b124 (`git show 140b124`). KNOWN_MISSING (35) = vues manquantes à HEAD = vues manquantes à 72d6fd6 (36) moins `checkout.confirmation` (vue créée depuis). Aucune régression cachée. 140b124 en avait ajouté exactement 4.
- **Test MySQL (lecture seule)** : skip propre sans MySQL (2 skipped, vérifié). La barrière fige un instantané avant la réservation : sans FOR UPDATE, 4 sur 4 passeraient et l'assertion `== 30` échouerait, donc pas de réussite à tort sur le test 1. Le test de deadlock est probabiliste.
- **Portée** : tous les fichiers touchés sont dans le brief (+ `.env.example`, docs). Pas de secret ni de cache commité.

## Non vérifié
- Test MySQL non exécuté (pas de MySQL) : comportement réel des verrous, deadlock, index choisi.
- Rendu HTML des 4 pages restaurées (seule leur existence est testée).
- Envoi SMTP réel de l'alerte.
