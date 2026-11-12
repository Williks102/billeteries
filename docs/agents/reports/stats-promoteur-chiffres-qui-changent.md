# Statistiques promoteur (L4) : chiffres qui changent

À valider par le propriétaire avant fusion. Valeurs « avant » déduites du code de `origin/main` (`git show origin/main:...`) appliqué au jeu canonique (`tests/Concerns/StatsFixture.php`), non rejouées sur une base ; valeurs « après » vérifiées par `PromoteurScreensTest`. Promoteur P, « aujourd'hui » = 15/11/2026, période par défaut = novembre 2026 (mois civil, Africa/Abidjan, affichée). Commission 6 %.

Jeu de P : O1 paid 20 000 (2 billets dont 1 scanné) · O2 pending 10 000 · O3 refunded 10 000 · O4 paid 15 000 (2 lignes) · O5 paid gratuite 0 · O6 failed · O8 paid octobre 10 000 · O9 paid nov. 2025 5 000. O7 (20 000) appartient à P2 et n'apparaît jamais chez P.

| Écran | Indicateur | Avant | Après | Pourquoi |
|---|---|---|---|---|
| Dashboard | Revenus générés | 14 100 (net des commissions `paid` seules, toutes dates) | « Revenu net » **32 900** (période) ; « Net à recevoir (total, toutes dates) » **32 900** (solde) ; « Versé » **14 100** | décision : revenu = net des commandes payées ; un solde n'est pas un flux |
| Dashboard | Ventes | absent | « Ventes (billets, hors frais) » **35 000** (période) | GMV = somme des lignes, commandes payées |
| Dashboard | Billets vendus | 10 (`status != available` : sold, used, reserved, cancelled) | **5** sur novembre (7 toutes dates) ; « Réservés » **1** à part | vendu = sold + used d'une commande payée |
| Dashboard | « Revenus ce mois » | 0 (commission `paid` créée « ce mois ») ; `whereMonth` sans année | supprimé (cartes de période) | période unique affichée |
| Dashboard | Croissance « +x % » | `revenue_growth` : mois précédent sans année, flèche « hausse » même négatif ; `monthly_growth`, `monthly_tickets`, `monthly_orders` jamais fournis (toujours 0) | « — » sur le jeu au 15 novembre (1-15 oct. sans vente) ; +250,0 % si une vente de 10 000 tombe du 1er au 15 octobre (jours écoulés comparés aux mêmes jours du mois précédent) ; « — » si non calculable ; flèche vers le bas si négatif | valeurs inventées retirées |
| Dashboard | Événements | « ce mois » = `whereMonth` sans année | total 1, publiés 1, créés sur la période 1 | |
| Dashboard | Taux de remplissage / disponibles | absents (`tickets_sold`, `max_capacity` inexistants dans la liste) | **4,1 %** (7 / 170) ; disponibles **163** | |
| Ventes | Revenus totaux | 50 000 par défaut « toutes dates » (`total_amount` brut) | **35 000** (novembre) ; liste des commandes ajoutée, filtrée par la même période | période par défaut = mois civil (avant : `all`) ; période appliquée aux cartes ET aux listes |
| Ventes | Billets vendus | 10 (`!= available`, par date de création du billet) | **5** | définition unique |
| Ventes | Panier moyen | 10 000 (50 000 / 5 commandes payées, toutes dates) | **11 667** (35 000 / 3) | période par défaut et GMV sans doublon |
| Ventes | Graphique | une requête par jour | une série SQL (2 requêtes) | N+1 supprimé |
| Rapports | Ventes | **50 000** (JOIN order_items puis SUM(orders.total_amount) : O4 comptée 2 fois) | **35 000** | requête doublon supprimée |
| Rapports | Panier moyen | 16 667 (50 000 gonflé / 3) | **11 667** | |
| Rapports | Billets vendus | 4 (`sold` seul : le billet scanné disparaît) | **5** | sold + used |
| Rapports | Commissions | 3 300 (tous statuts) | **2 100** (1 200 + 900) ; commission suspendue/échouée exclue | décision D1 |
| Rapports | Financier | `DATE_FORMAT` (MySQL seulement), total_amount gonflé | table mois / commandes / ventes / commission / net, SQL portable | |
| Rapports | Par événement | `SUM(order_items.quantity)` des lignes de commandes payées | ventes 35 000, commandes 3, billets 5, remplissage 2,9 % (5 / 170) | |
| Rapports | Période | `week`, `month`, `year` | presets communs (`this_month`, `last_month`, `this_year`, `7d`, `30d`, `today`, `all`) + dates | `week`/`month`/`year` deviennent invalides (redirection avec message) |
| Scanner (stats) | Taux d'utilisation | 16,7 % (1 / 6 : `used` hors dénominateur), par événement | **14,3 %** toutes dates (1 / 7) ; **20,0 %** en novembre (1 / 5) | scannés / (sold + used) ; la vue était vide (fichier de 0 octet) : page construite |
| Événement (liste) | Vendus / ventes | `getTicketsSoldCount` + `totalRevenue` (total_amount brut) : 2 requêtes par carte | **7 / 170**, 4,1 %, ventes **50 000** (cumul), 5 requêtes pour la page | N+1 supprimé |
| Événement (fiche) | Billets vendus par type | `quantity_sold` : A 5 (réservation O2 incluse) | A **4** / 100 vendus | réservations = disponibles |
| Événement (fiche) | Revenus / graphique | 50 000 brut ; courbe « Il y a 7 j… » en données simulées | ventes 50 000 (GMV), net à recevoir 32 900, versé 14 100, réservés 1, disponibles 163 ; courbe supprimée | valeurs inventées retirées |
| `Event` (modèle) | `getCommissionEarned` | 900 | **3 000** | délègue à CommissionStats |
| `Event` | `getProgressPercentage` | 4,12 | **4,1** | délègue à TicketStats |
| `Event` | `availableTicketsCount` | cassée (`totalTicketsSold` inexistante) | **163** | |
| `Event` | `totalRevenue` / `getOrdersCount` | 50 000 (`total_amount`) / 5 | 50 000 (GMV) / 5 | mêmes valeurs sur ce jeu |

## Changements de comportement
- Période invalide (`this_week`, `month`, dates incohérentes) : redirection vers l'écran avec message lisible (422 en JSON), jamais 500. Le menu latéral promoteur envoyait `period=week` / `period=month` : remplacés par `7d` / `this_month` (test `MenuLinksUsePeriodsTest`).
- Le filtre de dates personnalisées de « Ventes » garde ses noms (`start_date`, `end_date`).
- Suppression de code mort sans route : `SalesController::reports` et `::export` et leurs aides, `PromoteurController::getDashboardStats` et `::getNotificationsCount`, vues dupliquées `promoteur/reports.blade.php` et `promoteur/sales/reports.blade.php`, `public/js/promoteur-dashboard.js` (animait des cartes qui n'existent plus). Les URL et la route `promoteur.reports.export` (L7) sont inchangées.
- `DatePeriod::previous()` ajouté (partagé avec l'admin, additif) : la croissance compare les jours écoulés à une période équivalente (mois en cours : mêmes jours du mois précédent, borné ; mois dernier : mois civil précédent ; autres : même durée juste avant ; « toute la période » : null).
- Fiche événement promoteur : colonne « Actions » retirée (lien vers l'écran admin, 403 pour le promoteur) ; erreur 500 si l'événement n'a pas de catégorie corrigée.
- Tests existants adaptés : `LegacyCharacterizationTest` (4 tests retournés vers la cible ; la colonne `quantity_sold` reste 8 : compteur de stock, plus affiché côté promoteur), `PromoteurPagesTest` (libellé « Ventes (billets, hors frais) »), `CharteEtape2Test` (`promoteur/sales/index` retiré de la liste des formulaires : le formulaire est le composant `x-period-filter`, déjà libellé).

## Laissé de côté
`ProfileController` / profil promoteur (`User::totalRevenue`, brut) hors périmètre ; exports (L7) ; scanner : seule `stats` est migrée (`index` garde ses compteurs) ; acheteur / public (L5).
