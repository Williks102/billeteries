# Relecture : claude/billets-qr-demo (QR après paiement, mode démo, vues acheteur, IDOR download)

Base : origin/main 1a15681 ; HEAD 53e1abb ; 44 fichiers (+1217 / -404). Relecteur indépendant (aucune participation).
Mutations faites dans une copie jetable (git archive + `cp -al vendor`), supprimée ensuite ; branche et arbre de travail intacts.

## Verdict : REFUSÉ

## composer run check (HEAD, vendor présent)
```
OK (8 tests, 9 assertions)
OK (213 tests, 7626 assertions)
OK (51 tests, 2151 assertions)
real 0m8.915s
```
`php artisan route:list` : 215 routes, OK.

## Bloquant

**B1 [VÉRIFIÉ] La page « voir mon billet » renvoie 403 au propriétaire pour tout billet créé par le checkout.**
- `app/Services/CheckoutService.php:443-447` crée les billets SANS `order_item_id` (le lien est seulement dans le pivot `order_tickets`, l. 451-457).
- `app/Http/Controllers/Acheteur/TicketController.php:61` autorise via `$ticket->order()` = `hasOneThrough` sur `tickets.order_item_id` (`app/Models/Ticket.php:48`) : toujours null, donc `abort(403)`.
- Preuve : test jetable (checkout connecté réel, mode démo) : `order_item_id=NULL isIssued=true status=403`.
- Le test `tests/Feature/BuyerTicketViewsTest.php:44` masque le défaut : il renseigne `order_item_id` à la main, ce que la production ne fait jamais (le rapport ui-design le signale en « échec fermé, hors périmètre »).
- Effet : l'objectif de la mission (« page voir mon billet absente ») n'est pas atteint : on passe d'une erreur 500 à une 403 ; les liens « Voir le billet » de `order-detail.blade.php:208` et `tickets.blade.php:177` aboutissent tous à 403.
- Correctif (paiements-stock) : autoriser via le pivot (`$ticket->orders()->where('user_id', Auth::id())->first()`, comme le fait déjà `isIssued()` en repli) et/ou renseigner `tickets.order_item_id` dans `createOrderItemWithTickets` (avec rattrapage des billets existants). Ajouter un test qui passe par un vrai checkout puis GET `acheteur.tickets.show` (200 + QR).

## Important

**I1 [VÉRIFIÉ] Test de journaux affaibli : `CheckoutFinalFixesTest::test_logs_never_contain_the_ticket_code_nor_the_raw_exception_message` n'exerce plus le chemin d'erreur de `Ticket::qrDataUri()`.**
- Le commentaire de `tests/Feature/CheckoutFinalFixesTest.php:200` dit « force la régénération avec le service qui échoue ci-dessous », mais `getQrCodeBase64()` (l. 202) régénère et persiste le QR avec le VRAI service AVANT le remplacement par le service qui échoue. Ensuite, `generateQrCode()`, `getQrCodeBase64()` et `qr_code_url` renvoient la valeur en cache, et le service qui échoue n'est jamais appelé par `qrDataUri()`.
- Mutation : `app/Models/Ticket.php:119` remplacé par `Log::error("Erreur QR " . $e->getMessage())` (le message contient le code du billet) : le test reste VERT. Sur main, le chemin équivalent (`getQrCodeBase64` qui appelait `getOrGenerateTicketQR`, lequel levait une exception) était exercé.
- Correctif (qa-tests ou paiements-stock) : déplacer `update(['qr_code' => null])` APRÈS le `app()->instance(...)` du service qui échoue, puis appeler `qrDataUri()`.

## Mineurs
- M1 [VÉRIFIÉ] Mode démo, échec de confirmation : le message flash reste « MODE DÉMO : paiement simulé. Votre commande est confirmée automatiquement. » alors que la commande est `pending`. Constaté par un test jetable. Cause : `CheckoutService::successMessage()` ne dépend que du flag (`CheckoutService.php:607`). Le reste est correct : instructions envoyées, commande en attente, aucune exception.
- M2 [VÉRIFIÉ] `acheteur/ticket-detail.blade.php:58-67` : un billet annulé (commande expirée ou remboursée) ou un billet émis dont la génération du QR a échoué affiche « En attente de confirmation du paiement » et un lien vers les « instructions de paiement ». Message faux, et le badge « Valide » contredit l'alerte dans le second cas.
- M3 [VÉRIFIÉ] La garde `$isIssued` de la vue et du contrôleur (`TicketController.php:76`) est redondante : la mutation vue + contrôleur reste verte ; seule la garde du modèle (`Ticket::qrDataUri`) est testée (la mutation du modèle donne 4 tests rouges). C'est une défense en profondeur acceptable.
- M4 [VÉRIFIÉ] Le QR persisté encode `url('/verify-ticket/…')` de la PREMIÈRE génération (hôte de la requête, ou APP_URL en CLI). Un changement de domaine ne régénère pas les QR. La commande de rattrapage ne traite que les valeurs non data-URI.
- M5 [VÉRIFIÉ] N+1 : `isIssued()` exécute 1 à 2 requêtes par billet (repli sur le pivot car `order_item_id` est null), et il est appelé deux fois par billet dans `issueQrCodes`, `order-detail` et `tickets`. Mesure : 11,4 ms par QR à 300 px, data-URI de 2 146 octets (colonne `text` largement suffisante) ; commande de 10 billets ≈ 0,15 s. Acceptable.
- M6 Deux commandes Artisan quasi identiques : `tickets:generate-qr` (nouvelle, idempotente, testée) et `tickets:generate-qr-codes` (dont `--force` ne régénère plus rien, car `qrDataUri` renvoie la valeur en cache).
- M7 [VÉRIFIÉ] `Acheteur\OrderController::download` : la branche « admin » est morte, car la route est sous le middleware `acheteur` (`AcheteurMiddleware` : 403 pour un admin). C'est cohérent avec `show()`. Correctif IDOR : 2 mutations rouges (contrôle du propriétaire retiré, puis contrôle « payée » retiré).
- M8 Admin `TicketController::reactivate`/bulk `reactivate` passent un billet à `sold` sans QR immédiat. Sans conséquence : `qrDataUri()` le génère à la demande si la commande est payée.
- M9 Bandeau fixe en bas : `body { padding-bottom: 44px }` ne s'applique pas aux barres latérales fixes, dont les ~44 px du bas sont masqués (le bandeau a `pointer-events: none`, donc les clics passent). Acceptable en démo.

## Points vérifiés conformes
- Aucune voie de confirmation sans QR : toutes passent par `OrderPaymentService::confirmPayment` (admin confirm-payment `Admin/OrderController.php:274` ; update/updateStatus/bulk via `changeStatus` l. 204, 242, 318, 410, aucune transaction englobante ; `Order::markAsPaid` l. 222 ; reprise failed→paid l. 45 ; mode démo). `issueQrCodes` est appelé après le commit (`OrderPaymentService.php:114`). Seul `markAsSold` est utilisé, uniquement dans la transaction de confirmation.
- Aucun QR à la réservation ; plus de `Storage::put` ni de `generateAndSaveTicketQR`, `getTicketQRFromCache` ou `cleanupOldQRCodes`, et aucune référence restante. L'ancien format (URL dans `qr_code`) est régénéré (`Ticket.php:99`, commande de rattrapage `orWhere not like`).
- `qrDataUri` : null si le billet n'est pas émis, idempotent, attrape `\Throwable`, une persistance ratée n'empêche pas l'affichage.
- Flag lu via `config('payments.demo_auto_confirm')` (compatible `config:cache`), défaut false ; render.yaml : `sync: false` sans valeur (testé). `View::share` au boot : s'applique aussi aux vues de mail et en CLI, sans effet car aucun mail n'inclut le bandeau.
- Mode démo : confirmation après commit, validation et plafonds (testé) ; commission unique, 1 Payment, stock décompté une fois (testé) ; rejeu idempotent (retour anticipé sur `paid`).
- Mutations demandées : QR à la réservation → 5 tests rouges ; défaut du flag à true → 1 test rouge ; condition `isIssued` retirée de ticket-detail → voir M3.
- `acheteur-order-detail.js` supprimé, plus aucune référence dans le code. `KNOWN_MISSING` : `acheteur.ticket-detail` retiré (vue créée). `tickets.pdf` est toujours absente (500 sur `tickets.download`, préexistant) ; plus aucune vue ne la lie, et la page billet propose le PDF de la commande : contournement acceptable.
- Tampon de sortie laissé ouvert par les layouts promoteur/admin : confirmé IDENTIQUE sur origin/main (delta ob = 1 sur les deux).
- Aucun secret ajouté ; pas de commit sur main ; périmètre cohérent (TicketController racine, GenerateQrCodes et ProjectCleanup : ajustements liés au retrait du stockage par fichiers).

## Non vérifié
- MySQL/TiDB réels (tests sur SQLite). Rendu mobile réel (pas de navigateur). Scan du QR persisté par l'application scanner : `ScannerController` attend un `ticket_code`, alors que le QR encode une URL ; c'est préexistant et hors périmètre.

## Recommandation
Corriger B1 (paiements-stock) et I1 (qa-tests), puis relire.
