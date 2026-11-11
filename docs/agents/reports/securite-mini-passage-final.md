# Sécurité - mini-passage final (e2a60f4..6dfb6df)
1. M-1 VALIDÉ : verify/verifyApi chargent seulement ticketType.event.category (TicketVerificationController.php:23,72) ; getTicketVerificationInfo (~l.438) sans holder/order ; JSON = code/status/info ; vue verify sans titulaire. Routes auth sous middleware `promoteur` (web.php:140) ; contrôle promoter_id !== user->id (l.172, l.250, ScannerController:85) strict, OK (ints). scan() public : route commentée (web.php:78).
2. M-2 RÉSERVE : plus de ticket_code dans les logs modifiés. Reste F-4 (Faible) : getMessage() loggé dans authenticatedVerify (~l.212), authenticatedScan (~l.348/361) ; un QueryException embarque le code en binding. Correctif : logger get_class($e). Agent : paiements-stock.
3. F-1 VALIDÉ : ext-gd + bacon ^2.0 déclarés ; diff composer.lock = content-hash + platform ext-gd uniquement, aucune URL/version/dist/shasum modifiés.
4. F-2/F-3/R-1/R-2 VALIDÉ : QRCodeService MIN 100/MAX 600, QUIET_ZONE 4 ; toasts en textContent, aucun appelant HTML ; message « dans votre panier » sans fuite.
PR : oui (F-4 corrigeable après, non bloquant).
