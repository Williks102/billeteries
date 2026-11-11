# paiements-stock : retrait de PaiementPro, paiement manuel confirmé par l'admin

Branche claude/retirer-paiementpro (depuis claude/bloquants-p1-p7, 1277543). Motif : le `responsecode` PaiementPro n'est pas signé (notification d'échec rejouable avec responsecode=0).

## Supprimé
PaiementProService, Payments/PaiementProController, vues payments/paiementpro, routes payments/paiementpro/{notification,return,cancel}, exemption CSRF (retour au CSRF par défaut), bloc providers de config/payments.php (reservation_minutes et currency gardés), PAIEMENTPRO_* (.env.example, phpunit.xml), CSP form-action (retour à 'self'), singleton AppServiceProvider, canaux CARD/MOMO/... (vues et JS des deux checkouts), validations `in:paiementpro,bank_transfer` et `channel`, CheckoutService::initPaiementPro, Order::latestPaiementProPayment, PaiementProWebhookSecurityTest. La migration payments historique est inchangée.
`git grep -il paiementpro` sur app routes config resources public tests database bootstrap composer.json phpunit.xml .env.example ne renvoie que cette migration.

## Ajouté
- config/payments.php : `manual_methods` (bank_transfer, mobile_money, cash ; libellé, consigne, coordonnées depuis env MANUAL_* avec défauts génériques « communiqué par l'organisateur »), `manual_reservation_minutes` (env MANUAL_RESERVATION_MINUTES, 2880).
- App\Services\OrderPaymentService::confirmPayment(Order, ?string $reference, ?User $by) : transaction + lockForUpdate ; idempotent ; pending/processing -> paid (paid_at, expires_at null, payment_reference, billets markAsSold) ; failed -> Order::reclaimStock() sous verrou sinon App\Exceptions\OrderPaymentException « Stock indisponible… remboursement/relance manuel » (+ log critical) ; refunded et autres statuts refusés ; trace = ligne Payment completed (provider_response : confirmed_by, confirmed_at, manual) ; email sendPaymentConfirmation APRÈS le commit, échec journalisé sans annuler.
- Comportements repris de l'ancien processSuccessfulPayment : reclaimStock, markAsSold, logs sans PII, pas d'écrasement d'un `paid`.
- CheckoutService : `paymentMethodRule()`, `manualMethodKeys()`, `manualPaymentInfo()`, `sendManualInstructions()` ; createOrder fixe expires_at = now + manual_reservation_minutes pour une méthode manuelle. Plus de statut `pending_transfer` (absent de l'enum) : commandes `pending`.
- EmailService::sendManualPaymentInstructions (+ alias sendBankTransferInstructions ; l'ancienne méthode n'existait pas : les contrôleurs avalaient l'erreur, aucun email de virement n'est jamais parti) ; Mail/ManualPaymentInstructions + vue emails/manual-payment-instructions.
- Parcours : connecté -> checkout.confirmation (vue checkout/confirmation créée, elle n'existait pas) ; invité -> checkout.guest.confirmation/{token} (vue adaptée pending/paid) ; partial checkout/partials/manual-payment (numéro, montant frais inclus, instructions, date limite). Preuve de session guest_order_tokens (C1) conservée ; test de non-régression.
- Admin : POST admin/orders/{order}/confirm-payment (admin.orders.confirm-payment, avant la ressource), référence max 100, JSON ou redirection ; bouton + modale dans admin/orders/show (pending/processing/failed). updateStatus, update et bulkAction mark_paid délèguent à OrderPaymentService pour passer à paid.
- Order::markAsPaid : simple délégué vers OrderPaymentService (aucun appelant dans le dépôt). Une seule commission : créée par CheckoutService::createOrder ; confirmPayment n'appelle Order::createCommission() que si elle manque (garde `!commission`).

## Découverte hors mission, corrigée localement
User::$fillable ne contient pas is_guest, first_name, last_name : User::create/update les ignoraient. Conséquence : un invité n'avait jamais de guest_token (`$user->is_guest` faux), donc pas de page de confirmation invité ni de preuve C1. GuestCheckoutController::handleUser passe désormais par forceFill (saveUser). À traiter proprement par le propriétaire de User.php (hors périmètre).

## Tests (composer run check)
tests/Feature : RoutingConventionsTest (liste de dette réduite : payments.paiementpro.status retiré), StockAndOrderFlowTest (ex StockAndWebhookFlowTest : stock, libération, commande planifiée, create-account sans preuve de session, plus de route payments/), OrderPaymentServiceTest (idempotence, pending->paid, failed->reclaim ok/ko, refunded refusé, une commission, échec email), ManualCheckoutTest (pending + 48 h, méthode invalide refusée des deux côtés, prix serveur, preuve de session C1, pages de checkout sans canaux, email), AdminConfirmPaymentTest (route admin, max 100, non-admin 403, JSON 422 sans stock, updateStatus idempotent, page de confirmation acheteur). Schéma SQLite minimal partagé : tests/Concerns/BuildsOrderSchema.

## Non vérifié / limites
- Aucun test MySQL réel : lockForUpdate ignoré par SQLite (concurrence non testée). Migrations non rejouées.
- Pint non appliqué (le code existant n'est pas conforme au preset ; mes fichiers non plus sur concat_space).
- Plusieurs événements dans un panier : une commande par événement ; la confirmation affiche la première et liste les autres numéros (flash), chaque commande reçoit son email et se confirme séparément.
- Les commandes `failed` existantes (ancien flux) restent confirmables si le stock le permet.

## À faire par les autres
- qa-tests : scénario complet connecté + invité ; confirmation admin d'une commande expirée (stock repris -> message) ; test MySQL de concurrence double confirmation.
- securite : re-valider (1) confirm-payment réservé admin (middleware admin du contrôleur), CSRF actif sans exception ; (2) absence de PII/secrets dans les logs de OrderPaymentService ; (3) page confirmation invité par token (inchangée) + preuve de session ; (4) forceFill de handleUser ; (5) coordonnées de paiement uniquement via env, jamais en dur ; (6) les chemins update/bulkAction admin qui passent à failed/refunded sans libérer le stock (existant, non traité).
