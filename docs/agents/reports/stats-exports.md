# Statistiques unifiées, lot L7 : exports

Branche `claude/exports-stats`. Une seule brique : `App\Support\CsvExport` (BOM UTF-8, séparateur `;` comme l'existant, `fputcsv`, `CsvSafe::cell` sur toute cellule texte, nombres laissés numériques, largeur de ligne = largeur de l'en-tête sinon exception, écriture en flux). Les contrôleurs lisent par paquets de 500 (`chunkByIdDesc`) ; le filtre de chaque liste est extrait dans une méthode `filteredQuery` partagée par l'écran ET l'export. Période : `statsPeriod()` (DatePeriod::from) ; période invalide : redirection avec erreur `period`, ou 422 en JSON.

## Exports atteignables (route:list)

| Export | Route | Avant | Après |
|---|---|---|---|
| Commandes admin | `admin.orders.export` | CSV en flux, tout chargé, période `date_from/date_to` propre, e-mail + téléphone + codes billets, montant `10 000` (séparateur), cellules non neutralisées, N+1 (`tickets`), 500 si événement/utilisateur absent | Mêmes filtres et période que l'écran (`period/start_date/end_date`, commandes créées dans la période), CsvSafe, montants entiers, billets = somme des lignes (comme l'écran), colonnes Téléphone et Codes Billets retirées (non affichées par l'écran, codes = N+1), e-mail du compte complet (l'écran admin l'affiche en entier), invité sans compte : e-mail de facturation masqué |
| Utilisateurs admin | `admin.users.export` | Ignorait recherche et période, tout en mémoire, cellules brutes | Mêmes filtres/période que l'écran, colonnes explicites (jamais mot de passe ni jeton), e-mail et téléphone complets (l'écran `users/index` les affiche en entier), CsvSafe |
| Événements admin | `admin.events.export` | Stub JSON « en développement » | Vrai export : mêmes filtres/période que l'écran, vendus / capacité / commandes payées / ventes / net = `EventStats::forEvents` (5 requêtes par paquet) |
| Commissions admin | `admin.reports.export/commissions` | CSV concaténé à la main (`addslashes`, virgules), colonne `amount` inexistante (500 probable), sans période | `fputcsv`, CsvSafe, période = date de vente de la commande (comme les cartes), colonne « Compté dans les revenus » (commande payée ET commission pending/paid) : leur somme = revenu plateforme des cartes (3 300 en novembre 2026) |
| Rapports admin (redirections) | `admin.reports.export/{type}` | `$request->all()` retransmis | `$request->query()` ; type inconnu : retour avec erreur |
| Billets admin | `admin.tickets.export` | déjà corrigé (hors lot) | inchangé |
| Promoteur | `promoteur.reports.export` | Message « en développement » | `type=sales` (défaut) : commandes payées de la période de CE promoteur, avec commission et net ; `?event=` limité à un de SES événements (404 sinon) ; `type=events` et `type=financial` = écrans Rapports ; client = nom sinon e-mail masqué ; aucun e-mail dans le fichier |

## Chiffres qui changent
- Commandes admin : le total du fichier suit désormais la période de l'écran (mois civil par défaut) au lieu de « tout ». Montant : `20000` au lieu de `20 000`.
- Commissions admin : borné à la période (la LISTE de l'écran montre en plus toutes les commissions en attente quelle que soit leur date, pour le paiement en lot : différence voulue, l'export doit se rapprocher des cartes).
- Utilisateurs admin : limité aux inscrits de la période (défaut : mois courant) ; `period=all` pour tout.

## Supprimé comme mort (aucune route, aucun appelant)
`app/Services/ExcelExportService.php` (contenait aussi RevenuesExport, OrdersExport, PromotersExport et une classe anonyme) et `app/Exports/CommissionsExport.php`. Il n'existe donc plus d'export Excel (maatwebsite) atteignable. Le paquet `maatwebsite/excel` reste dans composer.json (inutilisé : à retirer sur décision du propriétaire). Les exports Excel supprimés lisaient `$commission->promoteur` inexistant ou exposaient e-mail et téléphone sans filtre.

## Tests (`tests/Feature/Stats/ExportsTest.php`, dans `test:stats`, 24 tests)
Content-Type + BOM, largeur de chaque ligne = en-tête (`str_getcsv`), totaux recalculés à la main (commandes payées 55 000 / 70 000 toutes dates / 10 000 mois dernier, commissions 3 300 / 2 100 / 600, promoteur P 35 000 / net 32 900, événements 50 000 + 20 000), bornes incluses, formules `=HYPERLINK`, `@SUM`, `+1`, `-2` préfixées d'une apostrophe dans chaque export, aucune requête par ligne (+30 commandes : même nombre de requêtes), période invalide (redirection + erreur, 422 en JSON), isolation P / P2, `?event=` d'un autre promoteur : 404, acheteur et promoteur (routes admin) : 403, visiteur : redirection login.
Mutations vérifiées rouges : retrait de CsvSafe, `pending` inclus (retrait de `paid()`), retrait du scope promoteur, retrait du filtre de période.

## Reste / non vérifié
- `admin.categories.export` pointe vers `CategoryController::export`, méthode inexistante (500, dette D2 connue) ; hors périmètre, et `admin.reports.export/categories` y redirige.
- MySQL non testé (SQLite seulement) ; aucun fichier ouvert dans un vrai Excel (l'apostrophe est la neutralisation standard, effet non constaté visuellement).
- Les exports ne sont pas planifiés en tâche de fond : un très gros volume reste limité par le temps de requête (lecture par paquets, pas de limite de lignes).
- Garde-fou nommage : le nom de fichier ne contient aucune donnée personnelle.
