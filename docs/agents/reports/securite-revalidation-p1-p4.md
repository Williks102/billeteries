# Re-validation sécurité P1–P4 (commits 4a5ef2a, 24c6e15)

Lecture seule, aucun test exécuté. Le code est lu sur HEAD.

## Verdicts
- P1 CSRF : VALIDÉ. `bootstrap/app.php:29-31` exclut uniquement `payments/paiementpro/notification`. return et cancel sont des GET, et `VerifyCsrfToken` a été supprimée.
- P2 signature : VALIDÉ, avec une réserve.
  - Fail closed si secret ou merchant_id est vide (`PaiementProService.php:254-257`).
  - `hashcode` vide ou absent est refusé (`:259-262`). `merchantId` est comparé à la config avec `hash_equals` (`:264`).
  - Le hash est comparé avec `hash_equals` (`:275`). `generateHashCode` lève une exception sans secret (`:134`). Le constructeur ne lève plus d'exception. `test_mode` vaut false par défaut (`config/payments.php:15`).
  - Aucun log de `$request->all()`, de hashcode ou de PII (`PaiementProController.php:99,114`, `PaiementProService.php:196,223`). `safeProviderData` filtre les données stockées.
  - Réserve (Moyenne) : le hash couvre merchantId.referenceNumber.amount.secret et PAS `responsecode`. Quiconque capture une notification signée « échec » peut remplacer `responsecode` par 0 et la rejouer. Si la commande est failed ou pending, elle passe à paid. À confirmer dans la doc PaiementPro. Pas de correctif possible côté code si c'est leur formule. Voir recommandations.
  - Le montant notifié est signé et comparé à `total_amount` (`:222`). Le prix n'est donc pas modifiable via le webhook.
- P3 idempotence : VALIDÉ. Transaction + `lockForUpdate` (`:204-207`). Une commande `paid` n'est jamais écrasée : no-op en `:217`, et failed refusé hors pending/processing en `:338`. Un `success` sur `failed` passe par `reclaimStock` (tout ou rien). Les emails ne partent qu'au vrai passage à paid (`PaiementProController.php:126`).
- P4 stock : VALIDÉ.
  - `reserveTickets` fait un verrou de ligne et revérifie sous verrou (`TicketType.php:~140-160`). Le double incrément a disparu : seul `reserveTickets` incrémente.
  - Prix et total sont recalculés depuis `TicketType::price` (`CheckoutService.php:37-48`). Les prix venant de la session ne sont plus utilisés, et la cohérence `event_id` est contrôlée.
  - `releaseStock` est idempotent et verrouillé (`Order.php:246-268`). La libération est appelée en cas d'échec d'init (`CheckoutService.php:171`). La commande planifiée ne touche pas les commandes payées.
  - Fenêtre de course restante, mineure : `reclaimStock` utilise `findOrFail` sans `lockForUpdate` explicite, mais `reserveTickets` re-verrouille. Verrouillage commande puis types cohérent, donc pas de deadlock visible. Non testé sur MySQL, et SQLite ignore `lockForUpdate`.
  - Mineure : un paiement tardif sur une commande `failed` dont le stock est épuisé renvoie 200 et un log critical. Remboursement manuel, aucune alerte automatique.
- Route cancel : NON VALIDÉ (voir C1 ci-dessous).
  - Le contrôle statut + propriétaire/guest_token avec `hash_equals` est correct (`PaiementProController.php:224-237`). Le `guest_token` est de 32 caractères aléatoires (`GuestCheckoutController.php:173`).
  - Mais le token est obtenu par la route `return`.

## Constat bloquant
C1 (Élevé, préexistant mais non corrigé, lié à la validation de cancel) : fuite du `guest_token` via `GET /payments/paiementpro/return`.
- Preuve : `PaiementProController.php:151-157` lit `order_id` dans `returnContext` (paramètre non signé, id entier séquentiel). Les lignes `:176-177` redirigent ensuite vers `checkout.guest.confirmation` avec `$order->guest_token` dans l'URL.
- Scénario : un attaquant énumère `?returnContext=order_id%3D1..N` et suit les redirections. Il obtient le token de toutes les commandes invités.
- Impact : il peut annuler une commande en cours (cancel), voir la confirmation et les billets, et surtout appeler `createAccountAfterPurchase` (`GuestCheckoutController.php:314-333`). Cette méthode définit le mot de passe du compte invité et connecte l'attaquant, donc prise de contrôle du compte.
- Correctif (exécutant : paiements-stock) : ne jamais rediriger vers une URL portant le token à partir d'un identifiant GET. Pour un invité sans session, rediriger vers `home` avec le message de statut, ou lier le retour à la session (`session('last_order_id')` posé au checkout, comparé à l'order de la requête). Utiliser aussi `order_number` + token signé côté retour. Test : return avec `order_id` d'une commande invité sans session ne doit pas révéler le token.

## Décisions à valider
- Frais 500 FCFA au checkout connecté : OK côté sécurité. Le montant est calculé côté serveur (`CheckoutService.php:19,68`), c'est cohérent avec ce que la vue affichait (`CheckoutController.php:58`), et le montant PaiementPro = `total_amount`. Décision métier au chef.
- `guest_token` dans `$fillable` de Order : acceptable. Il est généré serveur (`Str::random(32)`) et jamais pris dans une entrée utilisateur ; vérifier qu'aucun `Order::create($request->all())` n'existe (non trouvé dans les fichiers du diff).
- cancel => statut `failed` : OK. `failed` permet un paiement tardif (reclaim) et n'écrase jamais `paid`. `return` traite encore `cancelled` comme erreur (code inoffensif).

## Résiduel hors périmètre
- `channels()` appelle une méthode inexistante (erreur 500 contrôlée).
- `checkStatus` et `initiate` ne sont pas routés dans les fichiers lus.
- Index unique sur `payments.provider_transaction_id` toujours absent (faible).
- Recommandation : alerter (log critical → monitoring) sur le rejeu avec `responsecode` modifié. Vérifier avec PaiementPro si `responsecode` est couvert par un hash.
