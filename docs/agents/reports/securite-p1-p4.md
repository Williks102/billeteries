# Audit sécurité ciblé P1–P4 (paiement / stock)

Branche : claude/bloquants-p1-p7. Lecture seule ; statut « vérifié » = lu dans le code. Exécutant : paiements-stock. Tests : qa-tests.

## P1 — Webhook soumis au CSRF — CONFIRMÉ, Critique
Preuves :
- `app/Http/Middleware/VerifyCsrfToken.php:14-16` : `$except` vide.
- `bootstrap/app.php:28-30` : ajoute cette classe custom en plus du groupe web ; or le groupe web du framework contient déjà `ValidateCsrfToken` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/Middleware.php:490`) et l'exception se déclare via `validateCsrfTokens(except: [...])` (ibid. :598-602, qui agit sur `ValidateCsrfToken::except`). La classe custom ne neutralise donc rien : même si on remplit son `$except`, le middleware natif continue de rejeter.
- Route : `routes/web.php:75` (groupe web, donc session + CSRF). Aucun `->withoutMiddleware`.
Scénario : PaiementPro POST sans cookie/jeton -> 419 -> aucune commande n'est jamais marquée payée (billets restent `reserved`, stock bloqué). Impact : paiements encaissés sans billets.
Correctif (fichiers : `bootstrap/app.php`, `app/Http/Middleware/VerifyCsrfToken.php`) :
- Dans `withMiddleware` : `$middleware->validateCsrfTokens(except: ['payments/paiementpro/notification']);`
- Supprimer l'ajout redondant `web(append: [VerifyCsrfToken::class])` (double vérification inutile, et le `$except` de la classe custom est trompeur) et supprimer/vider la classe, ou y mettre la même entrée si conservée.
- Ne PAS exclure return/cancel.
Tests : POST sans jeton sur la route -> pas 419 (400 si paramètres invalides, 200 si valide) ; POST sans jeton sur une autre route POST (ex. `/cart/add`) -> 419.

## P2 — Hash facultatif, mode test, boot — CONFIRMÉ, Critique
Preuves :
- `PaiementProService.php:213-216` : secret vide -> `return true`. Un POST forgé suffit à marquer n'importe quelle commande payée (le `order_number` est exposé sur les pages/URLs de confirmation).
- Même en présence d'un secret, `:218-223` utilise `$data['merchantId']`, `$data['amount']` fournis par l'appelant (clé `merchantId` non comparée à la config) ; `$data['merchantId']` absent -> notice/erreur ; `amount` du hash n'est pas comparé au montant de la commande (voir P3).
- `config/payments.php:16` : `test_mode` vaut `true` par défaut ; `.env.example:44` aussi. `PaiementProService::generateHashCode:115-118` envoie un hash sans secret (dégradé silencieux).
- `PaiementProService.php:31-33` : exception au constructeur si `merchant_id` vide ; le contrôleur (`PaiementProController.php:18`) injecte le service, donc toute résolution du contrôleur lève (route:list échoue car la résolution est faite à l'énumération des routes ; supposé pour le mécanisme exact, l'échec est signalé par devops-hygiene dans le BOARD ligne 7).
- `PaiementProController.php:362-380` `testNotification` : non routée (grep routes) mais envoie `hashcode=test_hash` et lit `services.paiementpro.*` inexistant ; code mort dangereux -> supprimer.
Scénario : en prod sans `PAIEMENTPRO_SECRET_KEY` (oubli probable, absent de `.env.example` valeur vide), `curl -X POST .../notification -d merchantId=x -d referenceNumber=CMD-... -d amount=1 -d responsecode=0 -d transactiondt=x` -> commande payée, billets vendus, gratuits.
Correctif (fichiers : `PaiementProService.php`, `config/payments.php`, `PaiementProController.php`) :
- `verifyNotificationHashCode` : si secret vide -> `Log::critical` + `return false` (fail closed), quel que soit l'environnement ; utiliser `$this->merchantId` (config) et exiger `hash_equals($data['merchantId'], $this->merchantId)` ; `hash_equals($expected, (string)($data['hashcode'] ?? ''))` ; exiger la présence de `hashcode`. Garder la formule actuelle (merchantId.referenceNumber.amount.secret) ; vérifier la doc PaiementPro avant tout changement de formule.
- `generateHashCode` : ne plus tomber en hash sans secret ; lever une exception de configuration en non-test.
- `config/payments.php` : `test_mode` défaut `false` ; (en `production`) refuser de démarrer le paiement si secret vide.
- Constructeur : ne pas lever ; valider paresseusement dans `initTransaction`/`handleNotification` (retour d'erreur propre), ou enregistrer le service en singleton lazy ; ainsi `route:list`/`route:cache` fonctionnent sans variables. Alternative : injecter `app(PaiementProService::class)` dans les méthodes plutôt que dans le constructeur du contrôleur.
- Supprimer `testNotification`.
- Retirer `Log::info(... $request->all())` / log des params (`PaiementProController.php:99,154,240` ; Service `:48,176`) : contiennent hashcode, emails (PII). Ne logger que `referenceNumber`, `responsecode`.
Tests : secret vide -> notification rejetée (400, commande inchangée) ; hash faux -> rejeté ; merchantId différent -> rejeté ; hash valide -> accepté ; `php artisan route:list` OK avec env vide (`PAIEMENTPRO_MERCHANT_ID` non défini).

## P3 — Pas d'idempotence / de contrôle de montant — CONFIRMÉ, Critique
Preuves (`PaiementProService.php:171-285`) :
- Aucun contrôle `amount` notifié == `(int)$order->total_amount` (`:181-191`) ; note : à l'init, `amount = (int)total_amount` (`:94`).
- Aucun contrôle du statut courant : une notification `success` rejouée remet `paid`/`payment_date` et ré-exécute `markAsSold` ; une notification `failed` tardive/forgée écrase `paid` par `failed` (`:268`) ; `cancel` passe `cancelled` (non présent dans l'enum `orders.payment_status`, migration `2025_09_11_102806…:11` : erreur SQL en MySQL strict).
- Pas de transaction ni `lockForUpdate` : deux notifications concurrentes passent toutes deux.
- `:234-237` met à jour `payment_date`, colonne inexistante et non fillable (`Order.php:13-24` a `paid_at`) : date de paiement jamais enregistrée (silencieux).
- Résultat de `handleNotification` retourne `['success'=>true]` seulement (`:196`) alors que le contrôleur teste `$result['order']`/`payment_successful` (`PaiementProController.php:128`) : les emails de confirmation ne partent jamais ; et en cas de rejeu les emails partiraient en double une fois corrigé.
- Paiement `failed` : n'annule pas les billets ni ne libère le stock (voir P4).
- `Payment` n'est pas retrouvé par `provider_transaction_id` (`:240-242`) ; pas d'index unique.
Scénarios : rejeu d'une notification valide (capturée) pour regénérer les effets ; fraude : payer 1 FCFA une commande de 50 000 si la plateforme accepte (le hash porte sur `amount` fourni -> le montant payé n'est pas comparé) ; notification `failed` forgée après paiement (si secret connu/absent) -> annulation de commande payée.
Correctif (fichiers : `PaiementProService.php`, `PaiementProController.php`, `Order.php` pour `paid_at`, migration si ajout de `cancelled`/`expired` à l'enum, index unique sur `payments.provider_transaction_id`) :
```php
return DB::transaction(function () use ($data) {
    $order = Order::where('order_number', $data['referenceNumber'])->lockForUpdate()->first();
    if (!$order) return ['success'=>false,'error'=>'Commande non trouvée'];
    if ($order->payment_status === 'paid') return ['success'=>true,'duplicate'=>true]; // idempotent, 200
    if ((int)$data['amount'] !== (int)$order->total_amount) { Log::critical(...); return ['success'=>false,'error'=>'Montant invalide']; }
    // success: paid + paid_at=now() + payment completed + tickets sold ; return ['success'=>true,'order'=>$order,'payment_successful'=>true]
    // failed: uniquement si statut in (pending, processing) ; jamais sur 'paid'
});
```
- Envoyer les emails après commit et seulement si `duplicate` faux (idéalement via job, cf. D6).
- Remplacer `payment_date` par `paid_at`.
- `cancel` (route GET publique, `PaiementProController.php:238-250`) : aujourd'hui toute personne peut annuler n'importe quelle commande par `?order_id=` (IDOR, y compris commandes payées -> écrasées en `cancelled`). Exiger statut `pending`, propriétaire (auth) ou `guest_token`, passer par le service de libération (P4). Cette modification est dans le périmètre paiements-stock.
- `return` (`:152`) ne doit rien modifier ; à confirmer : il ne modifie rien (vérifié, simple redirection), mais le message « Paiement réussi » est basé sur le paramètre GET `responsecode` non signé : afficher plutôt `$order->payment_status`.
Tests : rejeu 2x -> 1 seul passage à `paid`, 200 les deux fois, 1 seul email ; montant différent -> rejet, commande `pending` ; `failed` sur commande `paid` -> reste `paid` ; `paid_at` renseigné ; `cancel` sur commande payée / d'autrui -> refus.

## P4 — Stock : survente et réservations jamais libérées — CONFIRMÉ, Critique
Preuves :
- `TicketType.php:106-111` (`canPurchaseQuantity`) et `:140-146` (`reserveTickets`) : lecture non verrouillée puis `increment` ; aucun `lockForUpdate` ni `UPDATE ... WHERE quantity_available - quantity_sold >= ?`.
- Double incrément : `reserveTickets` fait déjà `increment('quantity_sold')` (`:143`), puis `CheckoutController.php:374` et `GuestCheckoutController.php:304` font un second `increment('quantity_sold', qty)` : le stock est décompté 2x par achat (sous-vente et incohérence des stats). Vérifié par lecture.
- `CheckoutController.php:157-205` : transaction avec commit AVANT `processPaiementPro` ; en cas d'échec d'init (`:254`) commande + billets + stock restent réservés. `GuestCheckoutController` : `beginTransaction :77`, mêmes motifs.
- Aucune libération : `releaseTickets` (`TicketType.php:149-152`) n'est appelée nulle part dans les chemins checkout (grep) ; pas d'`expires_at` sur commandes/billets (grep « expire » vide), pas de commande planifiée ; paiement `failed`/`cancelled` ne libère rien (`PaiementProService.php:266-285`).
- Prix/total : total issu de `session('cart')` (`CheckoutController.php:160`, items `total_price`), non recalculé depuis `TicketType::price` au moment de la commande (`:341-346` utilise `$item['unit_price']`) -> prix périmé/manipulé via panier non revalidé (à confirmer côté CartController, hors lecture exhaustive ; marqué supposé). Invité : frais `+ 500` ajouté dans le total (`GuestCheckoutController.php:221`) mais pas dans le checkout normal : incohérence de montant.
Scénarios : (1) 50 requêtes simultanées sur un type à 1 place restante -> toutes passent `canPurchaseQuantity` -> survente ; (2) un bot crée des commandes `pending` non payées et vide tout le stock indéfiniment (déni de vente) ; (3) incohérence des compteurs.
Correctif (fichiers : `TicketType.php`, `CheckoutController.php`, `GuestCheckoutController.php`, `PaiementProService.php`, nouvelle commande `app/Console/Commands/ReleaseExpiredOrders.php` + `routes/console.php`, migration `expires_at` sur `orders`) :
- `reserveTickets` atomique : `static::whereKey($id)->whereRaw('quantity_available - quantity_sold >= ?', [$q])->increment('quantity_sold', $q) === 1` (ou `lockForUpdate` dans la transaction) en conservant les contrôles dates/actif/`max_per_order` APRÈS verrou (`TicketType::lockForUpdate()->findOrFail`). Supprimer l'incrément en double (`:374`, `:304`).
- Verrouiller dans l'ordre d'id croissant des types pour éviter les deadlocks ; tout le flux (commande, items, billets, stock) dans une seule `DB::transaction` ; rollback complet si init PaiementPro échoue (ou appeler `releaseOrder`).
- Créer `Order::releaseStock()` (ou service) : transaction + lock, uniquement si statut `pending`/`processing`, `releaseTickets(qty)` par item, billets `reserved` -> `cancelled`, commande -> `failed`/`expired` (ajouter à l'enum), idempotent. L'appeler depuis webhook `failed`, `cancel`, et la commande planifiée.
- `orders.expires_at` (ex. `now()+15 min`, config) ; commande `orders:release-expired` planifiée chaque minute (`withoutOverlapping`) qui libère les commandes `pending` expirées sans `Payment completed`. Prudence : un paiement tardif sur commande expirée -> webhook réactive uniquement si stock disponible, sinon alerte/remboursement (log critical).
- Recalculer prix et total côté serveur depuis `TicketType` (ne pas faire confiance à la session) ; harmoniser les frais.
Tests : (qa-tests) concurrence simulée : stock 1, deux réservations successives -> la seconde échoue, `quantity_sold` jamais > `quantity_available` ; un achat de N billets incrémente de N (pas 2N) ; commande `pending` expirée -> libérée, billets annulés, `quantity_sold` restauré, commande payée non touchée ; webhook `failed` libère ; init PaiementPro en échec -> stock restauré ; la commande de libération est idempotente.

## Ordre recommandé et périmètre
1. P1 puis P2 (indissociables : exposer le webhook sans fail-closed est pire que l'état actuel). 2. P3. 3. P4. Tout est dans le périmètre de paiements-stock (le fichier `routes/console.php` et la migration sont des ajouts liés). Hors périmètre à signaler : `cancel` public (IDOR) traité en P3.
Non vérifié : aucun test exécuté (lecture seule), comportement MySQL réel de l'enum, CartController (revalidation des prix).
