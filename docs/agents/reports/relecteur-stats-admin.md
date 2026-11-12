# Relecture : statistiques unifiées L2+L3 (branche `claude/stats-admin`, commit b556c2a)

Relecteur indépendant (n'a pas participé à l'implémentation). Base : `git diff origin/main...HEAD` (36 fichiers). Lecture seule ; mutations faites dans une copie jetable du dépôt (scratchpad, supprimée ensuite).

## Verdict : APPROUVÉ SOUS RÉSERVES

Aucun bloquant : chiffres justes sur le jeu canonique, `composer run check` vert, accès admin intact, portée respectée. Deux points importants (I1, I2) relèvent de la présentation et d'une décision du propriétaire, pas d'un bug de calcul.

## Exécution
- `composer run check` : `OK (113 tests, 1051 assertions)` · `OK (250 tests, 7881 assertions)` · `OK (120 tests, 1195 assertions)` · `OK (54 tests, 2314 assertions)`.
- Suite complète `phpunit` (copie jetable) : 539 tests, 1 erreur `ExampleTest` (MissingAppKeyException : pas d'APP_KEY dans l'environnement). L'erreur est identique sur `origin/main` et ne vient donc pas de cette branche.
- `php artisan route:list --path=admin` : 111 routes, aucune route modifiée (`routes/` absent du diff).

## 1. Justesse des chiffres [VÉRIFIÉ]
Recalcul à la main depuis `tests/Concerns/StatsFixture.php`. Novembre 2026 (défaut) : ventes 55 000 (O1+O4+O5+O7), encaissé 55 000, commission 3 300 (1 200+900+1 200), net à verser 37 600 (O1+O7), net versé 14 100 (O4), billets vendus 6 (2+2+1+1), utilisation 16,7 % (1/6), paiement 57,1 % (4 payées / 7 créées), en attente 1 (O2), remboursées 1 (O3), panier moyen 13 750. Octobre 2026 : 10 000 / 1 commande / commission 600 / à verser 9 400 / versé 0. Année 2026 : 65 000 / 5 commandes. Toute la période : 70 000 / 6 commandes. Fiche E (toutes dates) : 50 000, 7 vendus / 170 = 4,1 %, 163 disponibles, à verser 32 900, versé 14 100. Top promoteurs : P 32 900, P2 18 800.
Toutes ces valeurs correspondent aux assertions d'AdminScreensTest, qui lit le HTML réellement rendu (`data-stat` puis `.kpi-card__value`) et non les variables de vue.
Colonne « avant » du rapport, contrôlée avec `git show origin/main:…AdminController.php` : 9 / 70 000 / 60 000 (whereMonth sans année), revenus 4 500 / 50 500 / 7, commissions 900 / 3 300 / 600 / 4 800, rapports 9 / 70 000 / 5 400, financier 4 500 / 900 / 2 400, commandes 9 / 6 / 1 / 8, événements 70 000 et 8/170, fiche 65 800 / 162, top promoteurs 14 100. Tout est exact.
Seul écart : la ligne « Suspendues » annonce le libellé « Commandes remboursées », alors que la carte s'intitule « Commissions suspendues » (`resources/views/admin/commissions.blade.php:39`).

## 2. Périodes [VÉRIFIÉ]
- `DatePeriod` : fuseau Africa/Abidjan (identique à `config/app.php:68`), fin `23:59:59.999999` comparée avec `<=` partout (`Order::scopeInPeriod`, `PlatformStats::between`, liste des commissions `AdminController.php:115-120`, `EventStats::byCategory`). La date de vente est `COALESCE(paid_at, created_at)`.
- Période invalide (`ResolvesStatsPeriod.php:19-25`) : redirection avec erreur, ou réponse 422 en JSON, testée sur les 11 écrans. Mutation « catch retiré » : rouge.
- Anciens paramètres d'URL :
  - `this_week` et `last_year` : redirection avec « Période inconnue : … », sans erreur 500.
  - `date_from` et `date_to` sur l'écran commissions : ignorés sans message, et la période par défaut s'applique. Seuls d'anciens favoris sont concernés : aucun lien interne n'envoie ces paramètres (vérifié par grep dans `resources/views` et `public/js`).
  - Il reste une référence morte à `date_from` dans `commissions.blade.php:271`.
- Les SQL sont portables : SUBSTR, COALESCE, SUM(CASE), alias dans GROUP BY et ORDER BY, aucune fonction propre à MySQL. Rien n'a été exécuté sous MySQL ni TiDB [SUPPOSÉ OK].

## 3. Contrôle d'accès [VÉRIFIÉ]
Le middleware `admin` est posé deux fois : sur le groupe de routes (`routes/web.php:147`) et dans chaque constructeur de contrôleur. Ni l'un ni l'autre n'est modifié par la branche. Un test vérifie le refus (403) pour l'acheteur et pour le promoteur, et la redirection d'un invité, sur 11 écrans.
Mutations :
- retrait d'une seule des deux couches : le test reste vert, ce qui est normal puisque l'autre couche protège encore ;
- retrait du middleware du groupe ET de `OrderController` : le test devient rouge.

Les fiches `users.show` et `events.show` ne figurent pas dans ce test de refus, mais elles sont protégées par les mêmes constructeurs.

## 4. Portée [VÉRIFIÉ]
Aucun diff sur les fichiers suivants : `routes/`, `app/Models`, `OrderPaymentService`, `CheckoutService`, `Admin/TicketController`, `analytics*`, les exports (`ExcelExportService`, `exportCommissions`), et les écrans promoteur, acheteur et public.
Refonte de `admin/events/show` : justifiée. Sur main, la vue utilise `$totalRevenue`, `$totalTickets`, `$orders`, `start_date` et `location`, que le contrôleur ne fournit pas. Le rendu, qui a lieu hors du `try`, plantait donc. La nouvelle fiche est testée (4 cartes).
L'alias `x-admin.stat-card` reste compatible avec categories et settings (valeurs texte affichées telles quelles).

## 5. Tests et mutations
Mutations rendues rouges :
- `pending` inclus dans les ventes ;
- période retirée des cartes de commissions (`pending_payout`), de orders/index, de users/index et de events/index ;
- `paid_at` remplacé par `created_at` ;
- statut payé retiré dans `EventStats` ;
- `null` affiché comme 0 dans `stat-card` ;
- catch de la période retiré ;
- middleware retiré des deux couches.

Mutations restées vertes :
- Équivalentes sur ce jeu (pas des trous de test) : `pending` ou `held` ajouté dans `TicketStats::soldQuery` ou dans `CommissionStats::counted`. Un second filtre (statut du billet, ou commande `paid`) couvre chacun de ces cas.
- Trous de test (Mineurs, voir plus bas) :
  - filtre de période de la LISTE des commissions retiré ;
  - `paidOutByPromoter` sur le mauvais statut ;
  - `<=` remplacé par `<` dans `PlatformStats::between`, car aucun inscrit n'est placé à la dernière seconde de la période.

Modification de `LegacyCharacterizationTest` : légitime. L'assertion qui figeait les valeurs fausses est retournée vers la cible (70 000 / 6, puis 55 000 / 4) au lieu d'être supprimée.
`AdminViewsHaveNoQueriesTest` est un garde-fou par recherche de texte dans le source : il ne détecte pas un chargement paresseux de relation. Une lecture manuelle des 13 vues n'a trouvé aucune requête.
Budget de requêtes : le test compare le nombre de requêtes avant et après ajout de 15 commandes et 15 événements. Ce test détecte bien un N+1 sur le dashboard, orders/index, events/index, reports.sales et reports.events, mais pas sur commissions : aucune commission n'est ajoutée.

## Importants
- **I1. « Net promoteurs à verser » est un solde affiché par période, et la liste des commissions est filtrée par défaut sur le mois.** Fichiers : `AdminController.php:115-133`, `commissions.blade.php:37`, `dashboard.blade.php`, `reports/financial.blade.php`.
  - Par défaut, l'admin voit 37 600 alors que le montant réellement dû est de 51 700 (O8 9 400 et O9 4 700 sont masqués).
  - Les commissions `pending` des mois précédents disparaissent aussi de la liste utilisée pour le « Paiement en lot ». C'est une régression du parcours de versement par rapport à main, où l'historique complet s'affichait par défaut.
  - Correctif proposé : sur l'écran commissions, afficher en plus une carte « Net à verser (total, toutes dates) » en instantané. Pour la liste, soit `period=all` par défaut, soit un raccourci « Toutes les commissions en attente ».
  - Exécutant : backend (stats). Décision du propriétaire.
- **I2. La période ne filtre que les cartes, pas les listes, sans que l'écran le dise.** Fichiers : `orders/index.blade.php:27-35` et `76-88`, `events/index`, `users/index`.
  - Sous « Période affichée : Ce mois-ci », les listes de commandes, d'événements et d'utilisateurs montrent tout l'historique.
  - orders/index garde en plus un second filtre de dates (`date_from` / `date_to`) qui ne s'applique qu'à la liste.
  - Chaque formulaire efface les paramètres de l'autre : le filtre de période ne conserve pas `date_from` / `date_to`, et le formulaire de liste ne conserve pas `period`.
  - Correctif proposé : soit appliquer `DatePeriod` à la liste (et retirer `date_from` / `date_to`), soit écrire « La période s'applique aux chiffres ci-dessus » et conserver les paramètres croisés.
  - Exécutant : ui-design et backend.

## Mineurs
- Le rapport annonce le libellé « Commandes remboursées », la carte affiche « Commissions suspendues ».
- `commissions.blade.php:271` teste encore `date_from` et `date_to`.
- Sur l'écran commissions, la liste est datée par `commissions.created_at` et les cartes par la date de vente de la commande. Ces dates sont identiques sur le jeu de test, mais peuvent diverger en réel.
- Le libellé « Ventes (billets, hors frais) » et la carte « Encaissé » restent alors que les frais sont supprimés : Encaissé égale toujours Ventes sur les nouvelles commandes.
- Tests absents :
  - liste des commissions filtrée par période ;
  - `paidOutByPromoter` (« Net versé (total) » par ligne) ;
  - borne de la dernière seconde au niveau des écrans ;
  - test de refus (403) sur les fiches `users.show` et `events.show` ;
  - budget de requêtes de l'écran commissions avec un volume de commissions croissant.
- Sans JavaScript, des dates personnalisées restées remplies l'emportent sur un preset choisi dans la liste (`DatePeriod::from`).

## Suggestions
1. Ajouter au fixture d'AdminScreensTest un utilisateur et une commande à `2026-11-30 23:59:59`, pour verrouiller la borne de fin à l'écran.
2. Dans `AdminViewsHaveNoQueriesTest`, ajouter un contrôle à l'exécution : `Model::preventLazyLoading()` pendant le rendu.

## Non vérifié
- Exécution sous MySQL et TiDB.
- Rendu visuel réel à 390 px et 1280 px (captures fournies, non rejouées).
- Valeur « avant » de la fiche utilisateur promoteur (`User::totalRevenue()` sur main) : non recalculée.
