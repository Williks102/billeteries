# qa-tests : couverture du paiement manuel (branche claude/retirer-paiementpro)

## Ajouté (tous dans `composer run check`, sauf tests/Known)
- tests/Feature/ManualPaymentJourneyTest : parcours connecté (panier falsifié 1 FCFA -> pending, 48 h, billets reserved, stock, 1 commission, email instructions -> admin confirme -> paid, billets sold, 1 Payment, 1 commission, 1 PaymentConfirmation) ; parcours invité (guest_token en session, page de confirmation, autre session : id de commande en guise de token = 404, page authentifiée 403/login, create-account 403, aucune route d'annulation publique) ; invité avec email d'un compte existant (pas de token, pas de preuve de session, compte inchangé, pas de connexion) ; deux confirmPayment successifs avec instances périmées (1 paid, 1 Payment, 1 commission, 1 email).
- tests/Feature/OrderExpiryTest : orders:release-expired (failed, stock libéré, billets cancelled ; paid jamais touchée même expires_at passé ; commande en cours intacte) ; confirmation admin HTTP d'une commande expirée dont le stock a été repris (422, message « Stock indisponible », rien de paid, aucun Payment, pas de survente) ; stock libre : reclaim + paid.
- tests/Feature/ConfirmPaymentAuthorizationTest : non connecté -> login ; acheteur et promoteur 403 ; admin OK ; 419 sans jeton CSRF, 419 avec mauvais jeton, OK avec bon jeton CSRF actif. CSRF : ValidateCsrfToken est désactivé quand l'app est en console avec env=testing ; le test bascule `$this->app['env'] = 'production'` avant la requête (le vrai middleware tourne).
- tests/Feature/AdminOrderStatusPathsTest : updateStatus / update / bulkAction mark_paid -> via service (1 commission, 1 Payment, 1 email, idempotent, 2 lancements) ; paid sur refunded refusé (422) ; DETTE CONNUE documentée (non fausse) : failed/refunded via updateStatus, update et bulk mark_failed annulent les billets mais ne libèrent pas le stock.
- tests/Feature/CheckoutValidationTest : méthode vide/inconnue/ancienne, cgu absentes, email invalide, panier vide, quantité 0 et -3, billet d'un autre événement, quantité > stock (rollback total), billet inconnu ; aucun order/ticket/commission/stock/mail créé dans chaque cas. Prix falsifié : couvert dans ManualPaymentJourneyTest (et déjà dans ManualCheckoutTest).
- tests/Concerns/BuildsOrderSchema : helpers tamperedCart(), makeUser().
- composer.json (scripts) : test:payments étendu ; nouveau script `test:known-bugs` (hors check).

## Tests rouges (tests/Known/KnownBugsTest.php, EXCLU de check et de phpunit.xml)
1. Double comptage du stock : admin passe une commande pending à failed (stock non libéré) puis à paid -> reclaimStock() réserve à nouveau, quantity_sold = 4 pour 2 billets. Responsable : paiements-stock (Admin/OrderController::updateStatus/update/bulkAction mark_failed doivent appeler Order::releaseStock ; ou confirmPayment doit distinguer « failed libéré » de « failed non libéré »).
2. CGU invité : GuestCheckoutController::process, `'terms_accepted' => 'required'` accepte "0"/"false" ; à remplacer par `required|accepted`. Responsable : paiements-stock. Gravité faible.

## Observations
- Un invité qui saisit l'email d'un compte existant crée une commande rattachée à ce compte (spam de réservations 48 h possible) : pas de fuite de données, mais à évaluer par securite.
- La page de confirmation invité reste accessible à tout porteur du token (par conception, token 32 car.) ; seules create-account (et le retour) exigent la preuve de session.

## Exécution
`composer run check` : OK (6 tests) puis OK (62 tests, 426 assertions) ~30 s. `vendor/bin/phpunit` complet : 70 tests, 1 erreur préexistante indépendante : ExampleTest (GET / sans base migrée). Les 2 rouges de tests/Known ne sont pas dans phpunit.xml.

## Ce que SQLite ne prouve pas (MySQL)
lockForUpdate (ignoré), vraie concurrence (deux requêtes parallèles, deadlocks), contraintes uniques/clés étrangères des migrations (schéma de test simplifié), collation/casse des emails. La double confirmation est simulée en séquence avec instances périmées. Pint non lancé.
