# Relecture : statistiques promoteur (L4), branche `claude/stats-promoteur`

Relecteur indépendant (n'a pas participé à l'implémentation). Lecture seule ; mutations faites dans une copie jetable sous le scratchpad, jamais dans le dépôt (`git status` du dépôt vide avant et après).
Base : `git diff origin/main...HEAD` (27 fichiers, 4 commits).

## Verdict : REFUSÉ

Un seul bloquant, mais c'est une régression avérée et visible, que le rapport de l'implémenteur contredit : deux liens du menu latéral promoteur mènent maintenant à un message d'erreur. Le correctif est petit. Les chiffres, l'isolation, la portée et les tests sont bons par ailleurs.

## Exécution
- `composer run check` : `OK (121 tests, 1077 assertions)` · `OK (250 tests, 7881 assertions)` · `OK (206 tests, 2172 assertions)` · `OK (54 tests, 2314 assertions)`, en 23 s. VERT.
- `php artisan route:list` : `Showing [199] routes`. Les routes ne sont pas modifiées (`routes/` absent du diff). `sales`, `reports`, `reports/events`, `reports/financial`, `reports/export`, `scanner/stats` et `dashboard` sont présentes.

## Bloquant
**B1 : les liens « Cette semaine » et « Ce mois » du menu Ventes affichent une erreur.**
- Où : `resources/views/layouts/promoteur.blade.php:626-627` (`route('promoteur.sales.index', ['period' => 'week'])` et `['period' => 'month']`).
- Preuve (copie jetable, sonde HTTP) : `/promoteur/sales?period=week` → 302 vers `/promoteur/sales` avec l'erreur « Période inconnue : week. ». Même chose pour `month`. `period=today` → 200.
- Sur main, `SalesController::getStartDate` acceptait `week` et `month`. Le rapport STATS-L4 affirme « aucun lien interne n'utilisait `period=` » : c'est faux.
- Correctif : remplacer par `7d` et `this_month` (libellés « 7 derniers jours » et « Ce mois-ci »), ou retirer ces entrées. Ajouter un test qui suit chaque lien `promoteur.*` du layout et exige 200 sans erreur de session. Agent : ui-design (le layout est hors du périmètre déclaré) ; test : qa-tests.

## Importants
**I1 : la « croissance vs période précédente » compare des périodes inégales.**
- Où : `app/Support/Stats/DatePeriod.php:116-131` et `PromoterStats::growth`.
- Le mois courant inclut les jours à venir et il est comparé aux N jours complets qui précèdent. Ce ne sont pas les jours du mois précédent : novembre → 2 au 31 octobre ; mars → 29 janvier au 28 février ; octobre (`last_month`) → 31 août au 30 septembre ; `this_year` → l'année précédente entière.
- Conséquence : sur l'écran par défaut, le 3 du mois, la carte affiche presque toujours une forte baisse (flèche vers le bas) qui n'a pas de sens. Le fixture (le 15, octobre sans vente le 1er) masque le problème.
- Correctif proposé (backend, définition à valider par le propriétaire) : comparer la partie écoulée, de `start` à `min(end, maintenant)`, à la même durée qui précède. Ou bien, pour les presets mensuels, comparer au mois civil précédent à date égale. Tester un « maintenant » au 3 du mois et un mois de 31 jours.

## Mineurs
- M1. Mutation `paid_at` → `created_at` (`SqlDate::SALE_REF`) : verte sur les tests des écrans promoteur (dans le jeu, les commandes sont créées et payées le même jour). Seul `SalesStatsTest::test_sale_date_is_paid_at_with_created_at_fallback` la détecte. C'est suffisant pour du code partagé, mais il manque un cas à l'écran (commande créée le 31/10, payée le 01/11).
- M2. Mutation `formatPercent(null)` → « 0,0 % » : verte. Le « — » des listes (scanner par événement, rapports par événement, liste d'événements sans capacité) n'est pas testé. La version carte est testée : la mutation `null` → 0 de `stat-card` est rouge.
- M3. Le « Taux de remplissage » de Rapports › Par événement est calculé sur la période (2,9 %), alors qu'il est instantané partout ailleurs (4,1 %). Une note l'explique dans la vue, mais c'est le même libellé pour deux définitions.
- M4. Sous la carte « Versé », le texte d'aide dit « Versé sur la période ». En réalité, le filtre porte sur la date de VENTE de la commande, pas sur la date de versement (hérité de l'admin).
- M5. Les listes de commandes affichent `total_amount` (encaissé, frais historiques de 500 F compris), alors que la carte « Ventes » affiche la somme des lignes. Pour une commande historique, la somme de la liste ne correspond pas à la carte.
- M6. Tableau avant/après : la ligne « Ventes | Panier moyen | 50 000 / 3 » est inexacte. Sur main, la période par défaut est `all` et il y a 5 commandes payées : 50 000 / 5 = 10 000. Toutes les autres valeurs « avant » contrôlées sont exactes (rapports 50 000 / 16 667 / 4 / 3 300, dashboard 10 billets et 14 100, scanner 1/6 = 16,7 %, `getProgressPercentage` 4,12, `getCommissionEarned` 900).
- M7. Hors mission : `.gitignore` reçoit `.claude/worktrees/`. C'est inoffensif, mais à mentionner dans la PR. Le trait `ResolvesStatsPeriod`, sous `Admin\Concerns`, est réutilisé par le promoteur (rangement à revoir).
- M8. Sur Ventes et Rapports, le texte d'aide de la croissance ne donne pas la période comparée (le dashboard la donne).

## Justesse (recalcul manuel depuis `tests/Concerns/StatsFixture.php`)

| Indicateur (P) | Novembre 2026 | Toutes dates | Test | HTML rendu |
|---|---|---|---|---|
| Ventes (GMV) | 35 000 (O1 + O4 + O5) | 50 000 (+ O8 + O9) | = | = |
| Net à recevoir (solde) | 32 900 (O1 18 800 + O8 9 400 + O9 4 700) | 32 900 | = (testé sur 5 périodes) | = |
| Revenu net | 32 900 (18 800 + 14 100) | 47 000 | = | = |
| Versé | 14 100 (O4) | 14 100 | = (0 en octobre) | = |
| Billets vendus | 5 | 7 | = | = |
| Réservés | 1 (O2, instantané) | 1 | = | = |
| Remplissage | 7 / 170 = 4,1 % (instantané) | 4,1 % | = | = |
| Utilisation scanner | 1 / 5 = 20,0 % | 1 / 7 = 14,3 % | = | = |
| Croissance | (35 000 − 10 000) / 10 000 = +250,0 % (2 au 31 octobre) | null, sans flèche | = | = |
| Panier moyen | 11 667 | — | = | = |

Le « 14,3 % / 20,0 % » est exact selon la définition scannés / (sold + used d'une commande payée), avec la période appliquée à la date de vente. Remarque : le revenu net et le net à recevoir valent tous deux 32 900 en novembre, par coïncidence du jeu. Une inversion de ces deux cartes ne serait donc détectée que sous d'autres périodes (`last_month` : 9 400 contre 32 900, testé).

## Sécurité (isolation)
- **Critique / Élevé / Moyen : aucun constat.**
- Vérifié :
  - Chaque écran migré restreint ses données par `Scope::promoter(Auth::id())` ou par `Auth::user()->events()`.
  - Aucun paramètre `event_id`, aucun tri, aucun identifiant de commande n'est lu depuis la requête. Seules la période et la page le sont.
  - Fiche d'un autre promoteur : `edit` → 403 ; `show` → redirection vers la liste (l'exception 403 est absorbée par le `catch`, comportement préexistant) ; identifiant inexistant → 404.
  - Acheteur et admin → 403, invité → redirection (testé sur les 9 écrans).
  - Le middleware existe à deux niveaux : sans `promoteur` dans `web.php`, 9 tests passent au rouge.
  - `Event::` méthodes : `Scope::event($this->id)`, donc périmètre de l'événement lui-même, ce qui est voulu pour l'admin (`admin/events/edit`).
- Mutations rouges : liste des commandes sans scope (6 échecs), cartes en `Scope::admin` (7), cartes du scanner (1), liste d'événements `Event::query()` (2), fiche sans `abort_if` (1), `edit` sans `abort_if` (1), `eventsReport` sans filtre (2), `eventCounts` sans filtre (2), événements du scanner sans filtre (1), événements récents du dashboard (2), middleware (9).
- Faible :
  - F1. `PromoterStats::usageByEvent` sans scope reste vert. L'effet n'est pas observable, car seule la liste des événements du promoteur est lue. C'est de la défense en profondeur.
  - F2. `soldByTicketType($eventId)` et `eventsFigures($ids)` ne vérifient pas le propriétaire et dépendent de l'appelant (aujourd'hui correct).
  - F3. La liste Ventes affiche `billing_email` quand la commande n'a pas d'utilisateur. La fiche événement affiche déjà l'e-mail (préexistant). Minimisation des données à trancher.

## Périodes
- `DatePeriod` : Africa/Abidjan, fin incluse (23:59:59.999999), immuable.
- `previous()` : renvoie null pour `all`, la croissance n'est pas calculée si la période précédente vaut 0, et il n'y a jamais de flèche vers le haut pour une valeur négative (testé). Voir I1 pour le choix de la durée.
- Période invalide (`this_week`, `last_year`, `month`, `bogus`, dates inversées, `start_date` seul, `period[]`, `2026-02-30`) : redirection avec erreur, ou 422 en JSON. Jamais de 500.
- Grep des anciens paramètres dans les vues et le JS promoteur : seul le layout reste (B1).

## Portée et historique
- Ne sont pas modifiés : OrderPaymentService, CheckoutService, `Order::createCommission`, la logique de scan, la création d'événement et les routes.
- La suppression de `SalesController::reports/export` et de leurs aides privées est justifiée : aucune route n'y mène (route:list), et aucune référence ne subsiste (grep app/resources/routes/tests/public).
- `public/js/promoteur-dashboard.js` n'est plus référencé.
- Les adaptations de tests sont légitimes :
  - `CharteEtape2Test` : la vue ne contient plus de champ et le composant `x-period-filter` a ses libellés `for`.
  - `PromoteurPagesTest` : changement de libellé seulement.
  - Legacy : les assertions sont retournées vers la cible, conformément à son en-tête.
- Historique : le commit f0917ca ajoute un gitlink `.claude/worktrees/agent-…`, retiré en f50e9e3. `git diff origin/main --name-only` et `git ls-tree HEAD` n'en contiennent aucune trace. Seul `.gitignore` subsiste.

## Qualité
- Vues sans requête (garde-fou par analyse du source).
- Budget de requêtes constant réellement testé : les mutations N+1 (retrait du `with`, boucle par événement) sont rouges.
- SQL portable (SUBSTR/COALESCE/SUM CASE, aucune fonction MySQL).
- Cibles de 44 px, `table-cards` avec `data-label`, libellés présents.
- Aucune mention de « frais de service » ; aucun secret.
- Rendu visuel 390/1280 non vérifié (pas de Playwright).

## Non vérifié
- MySQL/TiDB : non exécuté.
- Rendu mobile réel : non vérifié.
- Mutation des données de P2 sous d'autres statuts : non testée.
