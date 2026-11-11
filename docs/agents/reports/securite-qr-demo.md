# Sécurité : branche claude/billets-qr-demo (revue ciblée)

Verdict : 1 VALIDÉ, 2 VALIDÉ, 3 VALIDÉ (réserves faibles), 4 VALIDÉ, 5 VALIDÉ, 6 VALIDÉ, 7 VALIDÉ. Aucun constat Critique/Élevé.

- Mode démo : défaut false (config/payments.php:84), .env.example:105 = false, render.yaml:107 `sync: false` sans valeur, absent de Dockerfile/entrypoint. Activable par variable d'environnement seulement (aucun grep route/formulaire/réglage). Appelé après commit, validation et plafonds (CheckoutController::process, GuestCheckoutController::process) via OrderPaymentService::confirmPayment (verrous, commission, transitions). Référence DEMO-AUTO : seul l'admin peut saisir une référence (Admin\OrderController:268), cosmétique. Bandeau dans les 4 layouts.
- Moyen (M-1) : activation par erreur en production = billets gratuits pour tout visiteur. Garde-fou proposé (AppServiceProvider::boot) : si APP_ENV=production et demo_auto_confirm true sans DEMO_MODE_ACKNOWLEDGED=true, forcer false et Log::critical. Render étant APP_ENV=production, l'acquittement doit être mis explicitement pour la démo. Agent : paiements.
- QR avant paiement : Ticket::qrDataUri gardé par isIssued (Ticket.php), utilisé par qrCodes (via getOrGenerateTicketQR), PDF, vues, getQrCodeSvg. Vérification publique : reserved => is_valid false.
- Faible (F-1) : Admin\TicketController::download (l. 328) génère/sert le QR sans contrôle de paiement (admin seulement). Corriger via qrDataUri().
- Faible (F-2) : tickets.qr_code est $fillable mais aucune écriture depuis une requête (admin update ne passe que status/notes) ; retirer 'qr_code' de $fillable par défense en profondeur. getQrCodeSvg est rendu en {!! !!} : sûr tant que qr_code n'est pas contrôlable.
- Faible (F-3) : routes acheteur.orders.download sous middleware acheteur : le « OU admin » est inatteignable (403 middleware) ; inoffensif. TicketController général : rôles limités à admin/promoteur/acheteur, OK ; $ticket->order null => 500 possible (pas de fuite).
- Faible (F-4) : confirmation invité = jeton seul (inchangé), affiche désormais les instructions de paiement à qui détient le jeton (secret) ; acceptable.
- Backfill tickets:generate-qr : sold/used + isIssued, idempotent.
- Historique : aucun secret/PII (emails de tests @test.test).
Peut-on ouvrir la PR : oui (M-1 recommandé avant d'activer la démo en production).
