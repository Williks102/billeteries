# Statistiques admin (L2 + L3) : chiffres qui changent

À valider par le propriétaire avant fusion. Les valeurs « avant » sont déduites du code précédent appliqué au jeu canonique (non rejouées sur une base). Jeu canonique (`tests/Concerns/StatsFixture.php`), « aujourd'hui » = 15/11/2026, période par défaut = novembre 2026 (mois civil, Africa/Abidjan, affichée à l'écran). Frais de service supprimés : encaissé = ventes.

Jeu : O1 paid 20 000 · O2 pending 10 000 · O3 refunded 10 000 · O4 paid 15 000 · O5 paid gratuite 0 · O6 failed 10 000 · O7 paid (autre promoteur) 20 000 · O8 paid octobre 10 000 · O9 paid novembre 2025 5 000. Commission 6 %.

| Écran | Indicateur | Avant | Après | Pourquoi |
|---|---|---|---|---|
| Dashboard | « Commandes » | 9 | 4 payées (+ 1 en attente, 1 remboursée affichées à part) | `Order::count()` comptait tous les statuts |
| Dashboard | Revenus totaux | 70 000 (toutes dates, sans libellé de période) | supprimé ; « Ventes (billets, hors frais) » = **55 000** pour la période affichée (70 000 avec « Toute la période ») | une seule définition, une période affichée |
| Dashboard | « Ce mois » | 60 000 | **55 000** | `whereMonth` sans année cumulait novembre 2025 (O9) |
| Dashboard | Commission plateforme | absent | **3 300** (O1 1 200 + O4 900 + O7 1 200) | commissions des commandes payées seulement |
| Dashboard | Net promoteurs à verser / versé | absent | **37 600** / **14 100** | net_amount, statut commission pending / paid |
| Dashboard | Taux d'utilisation / taux de paiement | absent | **16,7 %** (1 scanné / 6 vendus) / **57,1 %** (4 payées / 7 créées) | `sold`+`used` ; payées / créées (pas de « conversion », aucun comptage de visites) |
| Dashboard | Top promoteurs | net des commissions `paid` créées « ce mois » : P 14 100 | net des ventes de la période : P 32 900, P2 18 800 | même définition que les revenus |
| Dashboard | Graphe 6 mois | 18 requêtes, boutons 3M/6M/1A factices | 1 série SQL, boutons retirés | simulé retiré |
| Revenus | Ventes | 55 000 | 55 000 (inchangé) | libellé « Ventes (billets, hors frais) », date = paiement (repli création) |
| Revenus | Commission plateforme | 4 500 | **3 300** | exclut la commission `held` (O3, 600) et `cancelled` (O6, 600) |
| Revenus | « Part des promoteurs » | 50 500 (ventes − commissions) | **51 700** (net_amount réel) | somme du net, plus une soustraction |
| Revenus | « Commandes créées » | 7 | remplacé par « Commandes payées » **4** | libellé honnête |
| Commissions | Payées | 900 (commission) | « Net promoteurs versé » **14 100** | on affiche le net versé au promoteur |
| Commissions | En attente | 3 300 (toutes dates, toutes commandes) | « Net promoteurs à verser » **37 600** (période) | idem |
| Commissions | « Ce mois » | 4 800 (novembre de toutes les années, tous statuts) | « Commission plateforme » **3 300** | période + statuts comptés |
| Commissions | Suspendues | 600 | 600, hors revenus | libellé « Commandes remboursées » |
| Commissions | Liste | tout l'historique par défaut | commissions créées sur la période (défaut : ce mois-ci ; « Toute la période » disponible) | un seul défaut ; filtres date_from/date_to remplacés par le filtre de période |
| Rapports (accueil) | Commandes / Ventes / Commissions | 9 / 70 000 / 5 400 | 4 payées / 55 000 / 3 300 (période affichée) | filtre de période ajouté |
| Rapport financier | Commissions | 4 500 ; payées 900 ; en attente 2 400 | commission 3 300 ; net à verser 37 600 ; net versé 14 100 ; suspendues 600 | cf. ci-dessus |
| Rapport des ventes | Commandes / ventes | 4 / 55 000 | 4 / 55 000 (inchangé) ; + encaissé, panier moyen 13 750 ; liste paginée | date de vente = paid_at (repli création) |
| Rapport événements | Ventes | somme des commandes payées de chaque événement, toutes dates | ventes de la période ; « Moyenne par événement » remplacée par « Commandes payées » ; catégories : ventes de la période | période cohérente |
| Rapport utilisateurs | Actifs, évolution | calculés en PHP sur une collection ; 7 derniers jours fixes | SQL ; évolution sur la période choisie | |
| Commandes | Total / payées / en attente | 9 / 6 / 1 | 7 créées / **4** payées / 1 (période, par date de création) | période affichée ; « ce mois » corrigé (8 → 7) |
| Commandes | Revenus | 70 000 « Revenus (FCFA) » | « Ventes (billets, hors frais) » **55 000** | |
| Événements (liste) | Revenus totaux | 70 000 | « Ventes de la période » **55 000** | |
| Événements (liste) | Vendus / remplissage E | 8 / 170 (`quantity_sold`, inclut la réservation O2) | **7 / 170 = 4,1 %** | billets `sold`+`used` de commandes payées |
| Événement (fiche) | Revenu promoteur | 65 800 (net de TOUTES les commissions) | **47 000** (à verser 32 900 + versé 14 100) | statuts non comptés exclus ; la fiche plantait (variables de vue inexistantes), refaite |
| Événement (fiche) | Disponibles | 162 | **163** | la réservation non payée reste « disponible » (décision) |
| Utilisateur (fiche) | Total payé (acheteur 1), ventes/net promoteur P | 50 000 ; 50 000 / 32 900 | 50 000 ; 50 000 / 32 900 + versé 14 100 | identiques sur ce jeu ; sources désormais les services |

Retirés (valeurs simulées, décision propriétaire) : « conversion », « +x % » en dur, boutons de période factices du graphe.

Changements de comportement à connaître : `this_week` et `last_year` ne sont plus des périodes valides (presets : today, 7d, 30d, this_month, last_month, this_year, all + dates). Une période invalide redirige vers l'écran avec un message lisible. Les exports (L7), analytics (L6), billets admin (écran cassé) et les écrans promoteur/acheteur/public (L4-L5) ne sont pas touchés.
