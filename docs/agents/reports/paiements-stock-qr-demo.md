# QR après paiement (data-URI) et mode démo

## Décisions du propriétaire
1. Le QR d'un billet n'est visible qu'après confirmation du paiement.
2. Un mode démo explicite confirme les paiements automatiquement (désactivé par défaut, bandeau visible).

## Changements
- `Ticket::isIssued()` (statut sold/used ET commande payée) et `Ticket::qrDataUri(int $size = 300)` : null si non émis ; sinon renvoie `tickets.qr_code` (data-URI) ou le génère localement, le persiste (colonne `text`, ~2-3 Ko, pas de migration) ; idempotent, ne lève jamais. Une ancienne valeur non data-URI (URL) est régénérée.
- `QRCodeService` : plus aucune écriture de fichier (`generateAndSaveTicketQR`, `getTicketQRFromCache`, `cleanupOldQRCodes` supprimés) ; `getOrGenerateTicketQR` passe par `qrDataUri()` (donc gardé) ; `generateTicketQRBase64` conservé (PDF/admin).
- `OrderPaymentService::confirmPayment` (seul point qui passe à `paid` : admin confirm, changeStatus, reclaim failed->paid, `markAsPaid`, démo) appelle `issueQrCodes()` APRÈS commit ; un échec est journalisé sans PII et n'annule rien.
- `CheckoutService::generateQrCodes` supprimé ; plus aucun QR à la réservation.
- Commande `php artisan tickets:generate-qr` : backfill idempotent des billets émis sans QR.
- Mode démo : `payments.demo_auto_confirm` (env `DEMO_AUTO_CONFIRM_PAYMENTS`, défaut false). `CheckoutService::autoConfirmDemo()` après commit dans les deux checkouts : `confirmPayment($order, 'DEMO-AUTO', null)` (mêmes verrous/idempotence/commission unique). Validation et plafonds s'exécutent AVANT : non contournés. Journal d'avertissement une fois par requête. Flash « MODE DÉMO : paiement simulé ». `View::share('demoMode')` dans `AppServiceProvider` (ViewServiceProvider n'est pas enregistré). `render.yaml` : clé `sync: false` sans valeur + avertissement ; `.env.example` documenté (false).
- Modèles : `Order::isAwaitingPayment()`, `Order::paymentDeadline()`.
- Variables de vue : confirmation connecté/invité et `acheteur.orders.show` reçoivent `awaitingPayment`, `paymentInfo` (méthode, instructions, coordonnées MANUAL_*), `paymentDeadline`, `demoSimulatedPayment`.

## Test en erreur du WIP (CheckoutFinalFixesTest::test_logs_never_contain...)
Cause : le TEST, pas le code. Il utilisait un billet `reserved` ; `markAsUsed()` renvoyait false avant d'écrire. Le billet est maintenant émis (sold + commande payée, donc l'exception du service remplacé est réellement exercée), `markAsUsed` va jusqu'à l'UPDATE de `used_at`, colonne absente du schéma SQLite de test. Correction : colonne `used_at` ajoutée dans le setUp de ce test. Les assertions (aucun ticket_code, aucun `verify-ticket` dans les logs) sont inchangées et couvrent davantage de chemins.

## Preuves
- `composer run check` : 8 OK / 195 tests, 7535 assertions OK / 51 tests OK, ~10 s.
- `php artisan route:list` OK ; `git grep "Storage::put" -- app/Services/QRCodeService.php` : aucun résultat.
- Mutation (a) QR remis à la réservation : 4 échecs (CheckoutCycleFixesTest x3, QrAndDemoModeTest::..._stay_pending).
- Mutation (b) défaut du flag à true : échec de `test_demo_mode_is_disabled_by_default_and_orders_stay_pending`.

## Re-validation et tests
- qa-tests : scénarios dans `tests/Feature/QrAndDemoModeTest.php` ; à ajouter : test MySQL réel de confirmation concurrente démo (non exécuté, pas de MySQL).
- securite (priorité mode démo) : défaut false ; jamais true dans render.yaml ; le flag ne contourne ni validation ni plafonds ; absence de PII dans les journaux ; `demoMode` global ; QR non accessible avant paiement par `qr_code_url`, `getQrCodeSvg`, `acheteur.orders.qr-codes`, `getOrGenerateTicketQR`.
- Risques : les vues qui lisent directement `$ticket->qr_code` (order-detail, admin/tickets) afficheront le QR dès qu'il est persisté ; pas de bandeau tant que ui-design n'a pas édité les layouts ; mode démo = billets gratuits si activé par erreur en production.
