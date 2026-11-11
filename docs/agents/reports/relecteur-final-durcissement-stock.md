# Relecture finale (3e) : claude/durcissement-stock-n-a-b-c

- Relu : `git diff 47d6d6c..92d4e7f` (dernier cycle FIX-FINAL-CAP) et branche entière vs origin/main 0ff47ad.
- Pendant la relecture, HEAD est passé à a076043 (commit `securite` : docs/agents/BOARD.md + rapport uniquement, aucun code) : le verdict vaut pour le code de 92d4e7f = a076043.
- Relecteur indépendant : je n'ai pas participé à l'implémentation.

## Verdict : APPROUVÉ

Aucun Bloquant, aucun Important. Mineurs et suggestions ci-dessous, sans impact sur la fusion.

## Exécution
`composer run check` (arbre principal, HEAD 92d4e7f) : `OK (8 tests, 9 assertions)` puis `OK (150 tests, 2022 assertions)`, durée totale `real 0m7.260s`.
Après exécution : `git status` vide, `storage/app` identique avant/après (comparaison de `find storage/app -type f`) : aucun PNG parasite [VÉRIFIÉ].

## I-b (QR avant réponse) : RÉSOLU [VÉRIFIÉ]
- app/Services/QRCodeService.php relu en entier : aucun appel réseau (bacon Encoder + GD), plus de Google Charts/QR Server/file_get_contents.
- Décodage indépendant (zxing-cpp 3.1.1 installé dans le scratchpad, hors projet) : tailles 100/150/200/300 -> PNG 74/148/185/296 px, RGB, 2 couleurs (26 et 255), coin blanc ; contenu décodé = `<APP_URL>/verify-ticket/<ticket_code>` ; niveau de correction M.
- Coût mesuré : 8,8 ms par QR (100 générations) contre un pire cas de 39 s/billet avant : le QR synchrone après commit n'est plus un risque de blocage de worker.
- Consommateurs : colonne `qr_code` (Admin/TicketController:332-345, 411-414, commandes Artisan), Acheteur/OrderController:161-175, Ticket::getQrCodeBase64/getQrCodeSvg/getReliableQrCode, vue acheteur/tickets : tous attendent `data:image/png;base64,` et le reçoivent. `generateAndSaveTicketQR` garde le même chemin disque et le même `Storage::url` qu'avant (comportement préexistant inchangé).
- `generateStyledQR` : aucun appelant dans app/, resources/, routes/, config/ (grep) ; la perte des options de couleur est sans effet.
- Entrées limites : chaîne vide -> QR valide ; 300 caractères UTF-8 -> OK ; 5000 caractères -> `null` + log (classe d'exception seule), pas d'exception remontée.
- Le test `test_the_real_service_generates_a_valid_local_png...` compare chaque module du PNG à la matrice bacon : c'est un vrai contrôle du contenu, pas un mock.

## I-a (total du panier) : RÉSOLU, règle cohérente avec le checkout [VÉRIFIÉ]
- CartController : contrôle du total dans addMultipleTickets (l.102-109, avant `session()->put`), addSingleTicket (l.147-151), update (l.263-271).
- Cohérence avec le checkout : `assertWithinOrderLimits` (CheckoutService.php:209-213) compare `countTickets($groups)`, total de TOUS les événements, au quota `max_pending_tickets_per_buyer` (10). Un panier 6 (A) + 6 (B) était donc DÉJÀ refusé au checkout (test `test_checkout_refuses_6_plus_6_with_the_exact_message`). Le panier applique maintenant la même règle plus tôt : pas de refus nouveau ni « à tort » par rapport au comportement existant.
- Qualification : c'est une règle métier (quota de billets en attente par acheteur, tous événements), pas un bug. Si le propriétaire veut autoriser 6 + 6, il faut changer la règle au checkout ET au panier (décision produit).

## Tests et mutations (worktree jetable, vendor copié par cp -a, supprimé ensuite)
Chaque mutation fait échouer au moins un test :
- ancien QRCodeService (47d6d6c) : 5 échecs (PNG valide, aucun réseau, source sans tiers, checkout réel, logs) ;
- plafond total désactivé partout : 2 échecs ; seulement dans `update` : 1 ; seulement dans `addSingleTicket` : 1 ;
- garde MySQL `\z` -> `$` : 1 ; boucle URL supprimée : 1 ; `trim($host)` réintroduit : 1 ;
- backoff SMTP remplacé par `Cache::forget` : 1 ;
- throttle verify-ticket retiré : 1 ;
- ancien Ticket.php (logs avec ticket_code) : 1.
- Aucun test supprimé ; seuls `markTestSkipped` ajoutés : garde MySQL (motif écrit) dans tests/MySql, hors suite par défaut (phpunit.xml `defaultTestSuite="Unit,Feature"`).
- TestCase : `Http::preventStrayRequests()` conservé, le vrai service est désormais utilisé (plus fort qu'avant) ; `Storage::fake()` n'enlève rien, aucun test ne vérifiait le disque réel.

## Régressions (contrôles antérieurs)
B1, I1, ksort (CheckoutService.php:116), lockForUpdate (l.351, 365, 369), guest_token, plafond relatif, ViewReferencesTest/RoutingConventionsTest : tests toujours présents et verts dans le check. Aucune migration sur la branche, frais de service et pages non touchés (diff --name-only).

## Mineurs
- M1 app/Services/QRCodeService.php:23 : zone calme de 2 modules (la norme ISO 18004 en demande 4). Le décodage zxing réussit, mais certains lecteurs de scanner ou un fond de PDF non blanc peuvent échouer. Correctif : `QUIET_ZONE = 4` et adapter le test (`$total = $modules + 8`). Agent : paiements-stock.
- M2 composer.json : `bacon/bacon-qr-code` et `ext-gd` non déclarés (venus de simplesoftwareio/simple-qrcode 4.2.0, épinglé, qui exige `ext-gd`). Risque faible tant que la version reste épinglée. Le commentaire QRCodeService.php:15 (« GD déjà requis par DomPDF ») est inexact : dompdf v3.1.0 n'exige pas ext-gd, c'est simple-qrcode qui l'exige. Correctif : déclarer `"bacon/bacon-qr-code": "^2.0"` et `"ext-gd": "*"`. Agent : devops-hygiene.
- M3 GD absent : `renderPng` renvoie null et journalise, le checkout continue (generateQrCodes avale l'erreur) ; mais `Ticket::generateQrCode` (Ticket.php:166-169) stocke alors l'URL brute dans `qr_code`, que Admin/TicketController:345 décode comme du base64 [SUPPOSÉ, comportement préexistant, non introduit ici].
- M4 CheckoutService.php:37-40 et booking-form-desktop : le message « au total par commande » est imprécis quand le panier couvre 2 événements (2 commandes) ; la règle réelle est « par acheteur, tous événements ». Correctif : « Maximum N billets au total par réservation (tous événements confondus) ». Agent : paiements-stock, sur décision du propriétaire.
- M5 CheckoutService.php:472-474 : le commentaire parle encore d'« API HTTP externes, timeout 8 s » : il est périmé.
- M6 booking-form-mobile : renommer `showErrorNotification` en `safeErrorNotification` fait que la page n'écrase plus `window.showErrorNotification` de public/js/layout-app.js:104 (puits innerHTML, hors périmètre, déjà signalé par l'implémenteur) [SUPPOSÉ : effet sur d'autres scripts de la page non vérifié].
- M7 tests/Unit/MySqlTestGuardTest.php : `test_the_url_really_overrides_host_and_database_in_laravel_config` teste surtout Laravel (ConfigurationUrlParser), pas la garde. La seconde garde (sur la connexion effective, EventPendingCapConcurrencyTest::setUp) n'est couverte par aucun test exécuté, faute de MySQL.

## Suggestions
- S1 : QR en niveau de correction Q pour les billets imprimés (froissés) ; 33 modules restent lisibles à 200 px.
- S2 : `DB_SOCKET` (unix_socket) n'est pas contrôlé par la garde MySQL (une socket peut pointer vers un proxy de production).

## Non vérifié
- DOM JS (échappement S-9) : pas de navigateur ici. Seuls la lecture du code et les gardes sur le source (CheckoutFinalFixesTest:323) ont été vérifiés.
- Tests MySQL (`composer run test:mysql`) : pas de serveur MySQL, donc non exécutés.
- Lecture du QR par un vrai scanner, sur un téléphone ou sur un PDF imprimé : seul le décodeur zxing-cpp a été utilisé.

## Écart déclaré/réel
Les affirmations du rapport « Dernier cycle » que j'ai contrôlées sont confirmées : 150 tests, environ 6-7 s, aucun réseau, PNG décodable, total du panier. Seule réserve : la justification « GD requis par DomPDF » est inexacte (voir M2).

## Recommandation
Fusionner. Traiter M1 et M2 avant la mise en production. Les réserves de production de `securite` (test:mysql, vérification d'email) restent à lever indépendamment.
