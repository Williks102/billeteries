# Audit sécurité : billets gratuits (branche claude/billets-gratuits, commit 1868530)

Lecture seule ; revue du diff `origin/main...HEAD` et du code appelé. Aucun code exécuté.

## Verdict : PR oui sous réserves
Aucun contournement du prix constaté, confirmation uniquement via OrderPaymentService, QR protégés. Réserves : plafonds anti-épuisement de stock contournables (M1, M2), à traiter ou à accepter explicitement avant production.

## Critique
Aucun.

## Élevé
Aucun.

## Moyen
**M1 : plafond e-mail contournable, épuisement de stock gratuit (agent : codeur checkout).**
- `app/Services/CheckoutService.php` `assertWithinFreeOrderLimits` (l.~444-477) : le plafond e-mail compte `orders.billing_email` saisi librement, non vérifié. Un compte connecté (ou un invité) change `billing_email` à chaque commande (a@, b@, c@ de domaines différents ; les alias +tag/Gmail sont bien normalisés, mais pas les adresses distinctes). Seul reste le plafond IP : 5 commandes/h/IP x jusqu'à `maxTicketsPerOrder` billets (10 par défaut) = environ 1 200 billets/jour/IP, sans coût ni CAPTCHA ni vérification d'e-mail. Un bot avec quelques IP (4G, proxys) vide un événement gratuit.
- Les commandes gratuites sont exclues du plafond événement `assertEventPendingTicketsWithinCap` (`createOrder`, branche `$free`) : aucun garde-fou par événement.
- Correctif : (a) ajouter un plafond par événement et par fenêtre (ex. max X billets gratuits/IP/événement, et max % du stock par e-mail) ; (b) pour les comptes connectés, plafonner aussi par `user_id` ; (c) CAPTCHA/Turnstile ou vérification d'e-mail (lien) avant émission des QR sur invité ; (d) `max_per_order` des types gratuits faible. Tests manquants : 6 e-mails distincts depuis une même IP, 1 compte connecté avec billing_email variable, plafond par événement.

**M2 : empreinte IP dépendante de TRUSTED_PROXIES=`*` (Render) (agent : DevOps/déploiement).**
- `render.yaml:47-48` + `bootstrap/app.php:33-36` : avec `*`, Laravel ne fait confiance qu'à l'adresse du pair direct, donc `X-Forwarded-For` forgé n'est pas pris en compte tant que le proxy Render ajoute l'IP réelle à droite. Mais toute chaîne supplémentaire (CDN/Cloudflare devant Render) ferait voir l'IP du CDN : tous les visiteurs partageraient 5 commandes/h (déni de service) ; à l'inverse, un hébergeur qui n'écrase pas XFF rendrait l'IP falsifiable (plafond IP nul). `ipHash` utilise `$request->ip()` (bon point d'entrée), mais rien ne le vérifie.
- Correctif : documenter et tester l'IP réelle en préproduction (X-Forwarded-For forgé doit être ignoré) ; liste de CIDR au lieu de `*` si possible. Test manquant : requête avec `X-Forwarded-For` forgé + TRUSTED_PROXIES défini.

## Faible
- **F1 : CGNAT.** Mobile CI : beaucoup d'abonnés partagent une IP ; 5 réservations gratuites/h/IP peut bloquer des acheteurs légitimes (message générique, pas de fuite). Surveiller ; seuil configurable (`FREE_ORDERS_PER_IP_PER_HOUR`).
- **F2 : prix décimal.** `TicketTypeController.php:52,218` accepte `numeric|min:0` (ex. 0.4) alors que le recalcul serveur fait `(int) $ticketType->price` (`CheckoutService::buildEventGroups` l.~108, `serverCartTotal`) : un prix < 1 devient gratuit. Pas exploitable par l'acheteur (le prix est fixé par le promoteur) mais incohérent. Correctif : `integer|min:0` ou `min:1` hors gratuit.
- **F3 : échec partiel multi-événements.** `finalizeFreeOrders` : si la 2e commande gratuite échoue, la 1re reste confirmée (billets/e-mail émis) alors que l'utilisateur voit une erreur et que le panier n'est pas vidé ; un rejeu crée un doublon, borné par les plafonds. Les commandes payantes du même panier restent `pending`. Correctif : vider/ajuster le panier des commandes déjà confirmées.
- **F4 : trace Payment à 0 FCFA.** `OrderPaymentService.php:84-95` crée un `Payment` complété (montant 0, `manual: true`) pour chaque commande gratuite ; vérifier que les rapports financiers n'en tiennent pas compte comme paiement.
- **F5 : compte existant d'un tiers.** Pré-existant : `GuestCheckoutController` rattache la commande à un compte existant portant l'e-mail saisi ; un tiers peut déclencher l'envoi de 5 e-mails/jour vers une adresse (e-mail non vérifié). Borné par le plafond e-mail.
- **F6 : verrou.** `Cache::lock($key, 30)` + `block(5)` : clés triées, libération en `finally`, expiration 30 s, acquisition hors transaction : pas d'interblocage ni de verrou orphelin ; le driver `database` (table `cache_locks` présente dans la migration 0001_01_01_000001) est correct. Risque résiduel : si `$fn` dépasse 30 s (charge TiDB), deux requêtes pourraient passer ; dépassement du plafond de 1-2 commandes au pire.

## Points vérifiés conformes
1. **Contournements prix** : `buildEventGroups` recalcule tout depuis `TicketType` (session ignorée, `event_id` incohérent refusé, quantité < 1 refusée, plafond par commande) ; « gratuit » = sous-total serveur d'un groupe (somme, donc 0 sur une ligne + prix ailleurs reste payant) ; `payment_method` client jamais utilisé pour décider (`createOrder` force `free` seulement si total serveur = 0) ; la valeur `free` n'est pas dans `manualMethodKeys()` donc refusée sur commande payante ; pas de coupon/remise dans le code. `total_amount` n'est pas lu de la requête. `checkout_ip_hash` dans `$fillable` : aucune affectation depuis la requête (attributs construits côté serveur).
2. **Refus sans réserver de stock** : `assertWithinFreeOrderLimits` s'exécute avant `DB::transaction`/`createOrder`. Les commandes `failed` comptent dans les plafonds (pas de recyclage par échec).
3. **Confirmation** : seul `OrderPaymentService::confirmPayment` (verrou, idempotence) ; échec -> `fail()` (stock rendu une fois, `releaseStock` ne fonctionne que sur `pending/processing`) -> `CheckoutRefusedException` ; stock décrémenté une seule fois (à la création). Aucune commission (`createCommission` sort si montant 0 ; test l.97).
4. **Confidentialité** : QR invité affichés seulement si `ownsGuestOrder` (`guest-confirmation.blade.php:18-20`, contrôleur l.200-204), sinon e-mail ; HMAC-SHA256 de l'IP normalisée (/64 en IPv6) avec `app.key` : clé secrète, irréversible en pratique tant que la clé reste secrète (espace IPv4 énumérable si la clé fuit : traiter comme donnée personnelle, purger périodiquement) ; journaux : plus de `payment_method`, avertissements avec `scope` seul, pas d'e-mail/IP/téléphone ; e-mail et vues sans fuite nouvelle.
5. **Régressions** : plafonds « en attente » conservés pour les groupes payants (`assertPendingLimitsForPaidGroups`) et compteurs invités seulement sur les commandes payantes ; commission payante inchangée ; `autoConfirmDemo` saute les gratuites ; `DemoModeGuardTest::tearDown` restaure `APP_ENV=testing`.
6. **Migration** : additive, gardée par `hasColumn`, `string(64)` nullable + index, compatible MySQL/TiDB ; `down()` vide (convention du projet).
7. **Secrets/PII** : aucun secret ni donnée réelle dans le diff ; tests avec adresses `@example.test`.

## Tests manquants (résumé)
Plafond e-mail avec adresses distinctes ; plafond par événement/compte ; XFF forgé avec/sans TRUSTED_PROXIES ; panier mixte gratuit + payant avec échec de confirmation ; prix décimal < 1 ; expiration du verrou ; purge des empreintes IP.
