# Audit sécurité - branche claude/pdf-billets

Verdict : OK avec réserves. Aucun constat critique ni élevé. Tests TicketPdfTest + OrderDownloadAuthorizationTest : 22 OK.

## Moyen
- M1 Fichier PDF temporaire jamais supprimé, nom prévisible (app/Mail/PaymentConfirmation.php:~75, app/Services/EmailService.php:225,254).
  Le Mailable écrit `storage/app/temp/Billets-<order_number>.pdf` (B majuscule) ; `cleanupTempPDF` supprime `billets-<n>.pdf` et `cleanupOldTempPDFs` globbe `billets-*.pdf` (minuscule) : sous Linux le fichier n'est JAMAIS supprimé. Un PDF contenant les QR valides reste sur disque (dossier créé en 0755, fichier 0644), nom devinable. Non exposé par le web (storage/app privé) mais fuite en cas de LFI, backup, accès disque. Antérieur à la branche (casse déjà divergente), non corrigé.
  Correctif (agent backend) : ne pas écrire sur disque, utiliser `Attachment::fromData(fn() => $pdf->output(), $fileName)->withMime('application/pdf')` ; sinon aligner la casse et supprimer en `finally`, dossier 0700, nom aléatoire.

## Faible
- F1 Le PDF d'une commande liste aussi les billets annulés/non émis (QR remplacé par « QR indisponible ») mais affiche quand même leur `ticket_code` (components/ticket-sheet.blade.php:83). Le code seul n'est pas le QR mais est le secret de vérification (/verify). Correctif : masquer le code si `qr` est null.
- F2 `PaymentConfirmation` ne contrôle pas elle-même le paiement (comportement antérieur). Tous les appelants sont gardés (OrderPaymentService:136 après confirmation ; EmailService:181 sous `payment_status==='paid'` ; Admin/OrderController:295 et :364 contrôlent `paid`). Défense en profondeur présente : `sheets()` ne produit un QR que si `isPaid() && isIssued()`. Aucun chemin n'envoie un QR avant paiement. Correctif optionnel : garde `isPaid()` dans `attachments()`.
- F3 Destinataire : EmailService:47 envoie à `$order->user->email` (pas `billing_email`) : acheteur de la commande uniquement, OK.
- F4 Logs Mailable : `order_id`, nom de fichier, `is_guest` seulement (pas d'e-mail, pas de QR, pas de contenu) : OK. Le message d'exception est loggé (`getMessage`), pouvant contenir un chemin : log interne uniquement.
- F5 Robustesse : pas de plafond de taille ; 100 billets = 100 QR PNG 400px en data-URI en mémoire (DomPDF), risque de mémoire/temps sur le téléchargement et en envoi synchrone d'e-mail. Les relations sont eager-loadées (pas de N+1 dans la vue) mais `isIssued()` + `qrDataUri()` rappellent `linkedOrder()` par billet (relations chargées, pas de requête). Correctif : tester 100 billets, borner la taille ou mettre l'envoi en file.

## Vérifications conformes
1. IDOR `tickets.download` (TicketController:50-69, route sous middleware `auth`, routes/web.php:136) : propriétaire déterminé par `linkedOrder()` (orderItem->order, sinon pivot order_tickets) ; comparaison stricte `!==` entre `user_id` (int) et `$user->id` ; commande introuvable (null) => 403 ; commande invitée : `user_id` = compte invité, donc inaccessible aux autres connectés (seul l'admin passe) ; promoteur et autres acheteurs : 403 ; invité non connecté : redirection login. Binding par id séquentiel : l'énumération ne donne rien, car 403 dans tous les cas non autorisés (existence révélée seulement par 404 vs 403, faible, déjà vrai pour tickets.show).
2. Règle paiement : `isPaid()` + `isIssued()` (statuts sold/used seulement + commande `paid`) vérifiés au contrôleur ET par billet dans `sheets()`. Commande remboursée/annulée après paiement : si `payment_status` n'est plus `paid`, plus de PDF ni de QR (comportement voulu à confirmer côté métier) ; billet `used` : PDF téléchargeable (par conception). Billet annulé : redirection, pas de PDF.
3. Redirection non autorisée : seul le propriétaire/admin reçoit le message « disponible après paiement » (contrôle d'accès passe avant), pas de fuite.
4. PII : le PDF n'affiche que le nom du titulaire, n° de commande, date ; ni e-mail ni téléphone.
5. Sorties : toutes en `{{ }}` (aucun `{!! !!}`) ; QR data-URI local ; DomPDF : pas de config/dompdf.php publié, défauts du paquet `enable_remote=false`, `enable_php=false`, chroot = base_path ; `setOptions` n'active rien de risqué. Noms de fichier via `Str::slug` (pas d'injection d'en-tête).
6. Cache : la réponse download de Laravel envoie `Cache-Control: private` par défaut ; `no-store` optionnel (faible). Le `ticket_code` est déjà montré sur le site à l'acheteur.
7. Échec de génération : en téléchargement, exception non interceptée => page 500 générique (APP_DEBUG=false requis en production) ; e-mail : exception interceptée, PDF omis.
8. Secrets : aucun dans le diff, les vues, les tests.
