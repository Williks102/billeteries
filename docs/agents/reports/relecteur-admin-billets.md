# Relecture : admin-billets (branche claude/admin-billets, commit 5ddfcc8)

Relecteur indépendant (n'a pas participé à l'implémentation). Lecture seule ; mutations et sondes faites dans une copie jetable (`cp -a`) sous le scratchpad, jamais dans le dépôt.

## Verdict : REFUSÉ

Un Bloquant (B1) : l'export CSV, livrable explicite de la mission, produit un fichier corrompu (colonne Prix non quotée contenant une virgule) et n'est couvert par aucun test fonctionnel. Le reste (liste, fiche, cartes, transitions unitaires) est correct et bien testé. B1 se corrige en quelques lignes ; I1 et I2 doivent être traités ou arbitrés avant la PR.

## Exécution

`composer run check` (copie à l'identique de la branche) : `OK (113 tests, 1050 assertions)` · `OK (250 tests, 7881 assertions)` · `OK (151 tests, 1448 assertions)` · `OK (54 tests, 2314 assertions)`.
`php artisan route:list --name=tickets` : 10 routes admin billets inchangées (`routes/` absent du diff).

## Bloquants

- **B1 [VÉRIFIÉ] Export CSV corrompu et non testé** : `app/Http/Controllers/Admin/TicketController.php:342` `number_format($ticket->ticketType->price ?? 0, 2)` produit `10,000.00` sans guillemets. Chaque ligne a donc 10 champs pour 9 en-têtes, et toutes les colonnes après Prix sont décalées. Sortie réelle obtenue par sonde : `TKT-O1-0,"@SUM(1+1)","A","=HYPERLINK(""http://x"",""y"")",acheteur@example.test,sold,10,000.00,2026-11-03 09:00:00,N/A`. Le même extrait montre que les formules ne sont pas neutralisées (voir Sécurité S1). Aucun test n'appelle l'export en tant qu'admin : seul le 403 est testé. Correctif (backend) : écrire avec `fputcsv` (sur `php://temp` ou en réponse streamée), qui quote chaque champ. Préfixer d'une apostrophe toute cellule commençant par `= + - @` (ainsi que tabulation et retour chariot). Formater le prix sans séparateur de milliers. Test (qa-tests) : en-tête et ligne relus avec `str_getcsv` (9 champs), plus une cellule `=...` échappée.

## Importants

- **I1 [VÉRIFIÉ] Réactivation d'un billet d'une commande remboursée ou échouée** : `app/Models/Ticket.php:71` `'cancelled' => ['sold']` est sans condition. Sonde : la réactivation en lot de TKT-O3-0 (commande refunded) et de TKT-O6-0 (commande failed) les fait passer tous deux à `sold`, alors que `quantity_sold` du type A reste à 5. `refund()` et `fail()` avaient pourtant libéré ces places (`OrderPaymentService.php:235`, `releaseStock`). On obtient des billets « vendus » hors stock, donc une survente possible et des données incohérentes. Pas de QR, puisque `isIssued` exige une commande payée. Le test `test_used_ticket_is_frozen_and_cancelled_can_be_reactivated` (AdminTicketsTest:166) fige ce comportement sur O3, remboursée. La règle existait déjà sur main, mais elle est maintenant centralisée et testée comme voulue. Correctif (backend, décision propriétaire) : `cancelled -> sold` seulement si `linkedOrder()?->payment_status === 'paid'` ; sinon, ne rien permettre. Adapter le test pour qu'il utilise un billet annulé d'une commande payée et vérifie le refus sur O3 et O6.
- **I2 [VÉRIFIÉ] bulkAction : passage par les règles non protégé par les tests** : la mutation qui remplace `applyTransition` par `$ticket->update(['status' => $to])` (TicketController.php:270) laisse les 140 tests verts. Le code actuel est correct : par sonde, mark_used refuse le billet réservé, cancel refuse le billet utilisé, une action inconnue ou des ids invalides donnent des erreurs de validation. Mais une régression ne serait pas détectée. Correctif (qa-tests) : test en lot avec mark_used sur une commande non payée et cancel sur un billet utilisé, qui doivent donner 0 traité et des statuts inchangés.
- **I3 [VÉRIFIÉ] E-mail complet dans l'export** : TicketController.php:340 exporte `user->email` en clair. Le brief dit « e-mail complet réservé à la fiche ». Correctif (backend) : masquer avec `PrivacyHelper::maskEmail`, ou faire valider l'export complet par le propriétaire et l'écrire dans decisions.md.

## Mineurs

- Export avec un filtre invalide (`status=nope`) : la `ValidationException` est avalée par `catch (\Exception)` (TicketController.php:349), d'où une redirection avec « Erreur lors de l'export » au lieu d'une erreur de validation. L'export accumule aussi tout le CSV en mémoire, sans borne (`period=all`).
- Les tests 403 ne couvrent que les routes GET. Il existe deux couches (middleware de groupe `routes/web.php:147` et constructeur `TicketController.php:27`) : retirer l'une ou l'autre laisse les tests verts, retirer les deux les fait rougir (8 échecs). PATCH, PUT et POST sont protégés en réalité (sonde : acheteur PATCH cancel = 403, promoteur POST bulk-action = 403), mais aucun test ne le vérifie.
- Annuler un billet réservé d'une commande pending ne libère pas le stock, et `confirmPayment` le repasse à `sold` (`markAsSold` sur tous les billets, OrderPaymentService.php:76). Comportement hérité.
- Le verrou `lockForUpdate` ne s'applique pas sous SQLite : sa suppression laisse les tests verts. La commande liée n'est pas verrouillée pendant `used` (une course avec un remboursement reste possible sur MySQL). [SUPPOSÉ] sans conséquence pratique, admin seul.
- La sélection et les actions en lot ont disparu de la liste (seul « Régénérer le QR » de la fiche utilise bulk-action). La recherche ne porte plus que sur le code (avant : nom/e-mail de l'acheteur, événement) et le filtre promoteur a disparu. C'est conforme au brief, mais c'est une réduction fonctionnelle.
- `index.blade.php` : petit `@php` (`linkedOrder()`, date de vente) dans la boucle. Il ne déclenche aucune requête (budget constant testé, mutation N+1 rouge) mais reste de la logique dans la vue.

## Points vérifiés conformes

- Cartes recalculées à la main sur StatsFixture, novembre 2026 : commandes payées O1 (sold et used), O4 (2 sold), O5 (1, pivot et item comptés une fois), O7 (1) : vendus 6. Scannés 1 (O1-1). Taux 1/6 = 16,67, affiché 16,7 %. Réservés 1 (O2). 9 billets listés. Conforme aux tests.
- Billets anciens (pivot seul, O1) et récents (order_item_id, O4) : commande et acheteur affichés en liste et en fiche, testés. La mutation qui retire le pivot donne 4 tests rouges.
- Filtres statut, événement, code et période. Un statut invalide donne une erreur de validation avec redirection.
- Transitions (update, markUsed, cancel, reactivate, bulk) passent toutes par `applyTransition`, puis `canTransitionTo` sur le billet relu. Un billet utilisé est figé. Le double scan est refusé. « Utilisé » exige une commande payée (mutation rouge, tout comme celle où markUsed contourne les règles).
- Aucun QR ni data-URI dans la liste ni dans l'export. Régénération du QR limitée aux billets émis (testé). E-mail masqué en liste (mutation rouge). Budget de requêtes réellement mesuré (mutation N+1 rouge).
- Portée : routes, services de paiement, stock et commission inchangés. `OrderPaymentService` et `CheckoutService` hors diff. KNOWN_MISSING en baisse (-1). Aucune mention « frais de service » dans les vues billets. Suppression de `public/js/admin-tickets-index.js` justifiée (plus référencé).
- `LegacyCharacterizationTest` : le retournement vers la cible est légitime (il affirmait l'exception de main ; il affirme maintenant 6 / 1 / 16,7, identiques à TicketStats).
- Commandes Artisan hors périmètre, confirmées : `DiagnoseTicketsCommand.php:34,246` (`tickets:diagnose`) et `ProjectCleanupCommand.php:201` (code généré) utilisent encore `orderTickets`. Une exécution lèverait `RelationNotFoundException`. Risque faible : maintenance manuelle uniquement, aucune exposition web, défaut déjà présent sur main.

## Sécurité

- **Critique** : aucun.
- **Élevé** : aucun.
- **Moyen S1 [VÉRIFIÉ] Injection de formules CSV** (TicketController.php:336-339) : le titre d'événement et le nom du type (saisis par un promoteur) ainsi que le nom de l'acheteur (saisi par l'acheteur) sont exportés tels quels. `=HYPERLINK(...)` et `@SUM(...)` sortent intacts. Ces valeurs s'exécutent à l'ouverture du fichier dans un tableur sur le poste de l'admin. Le défaut existait déjà, mais il se trouve dans le code réécrit. Correctif : celui de B1.
- **Moyen S2 [VÉRIFIÉ] E-mails complets dans l'export** : voir I3.
- **Faible** : `tickets` de bulkAction sans `max` ni `integer` (une requête `exists` et une transaction par id, admin seul) ; export sans borne (mémoire) ; messages d'exception journalisés (download, bulk, export), sans PII constatée. Accès : middleware admin sur les 10 routes (groupe et constructeur), IDOR impossible hors admin, pas d'affectation de masse (`update` avec tableau explicite), journal des transitions sans PII (ids seulement).

## Écart déclaré / réel

- Le BOARD affirme « e-mail masqué en liste » : c'est vrai, mais l'export n'est pas masqué (I3).
- Export CSV présenté comme réparé : il ne lève plus d'exception, mais son contenu est faux (B1) et non testé.
- « Mêmes règles pour bulkAction » : vrai dans le code, non garanti par les tests (I2).
- Non vérifié : MySQL/TiDB (verrous réels), rendu visuel (les captures jointes n'ont pas été rejouées), Pint (absent de `composer run check`).

## Recommandation

Corriger puis relire. B1, puis S1 avec le même correctif : backend. Test de l'export et I2 : qa-tests. I1 et I3 : décision propriétaire, puis backend.
