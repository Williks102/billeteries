# Statistiques acheteur et public (L5) : chiffres qui changent

À valider par le propriétaire avant fusion. Valeurs « avant » déduites du code de `origin/main` (`git show origin/main:...`) appliqué au jeu canonique (`tests/Concerns/StatsFixture.php`), non rejouées ; valeurs « après » vérifiées par `BuyerPublicScreensTest`. « Aujourd'hui » = 15/11/2026. Acheteur 1 (jeu canonique seul) : O1 paid 20 000 (2 billets dont 1 scanné), O2 pending, O3 refunded 10 000, O4 paid 15 000, O5 paid gratuite 0, O6 failed, O8 paid 10 000 (octobre), O9 paid 5 000 (2025) ; tous sur l'événement 1 (01/01/2027, donc à venir).

| Écran | Indicateur | Avant | Après | Pourquoi |
|---|---|---|---|---|
| Dashboard acheteur | Commandes | 8 (sans ventilation) | **8**, dont 5 payées, 1 en attente, 1 remboursée, 1 échouée (cartes séparées) | statuts distingués |
| Dashboard acheteur | Billets | 7 (SUM des quantités des lignes payées) | **7** (sold + used de commandes payées) | même valeur ici ; diffère si un billet est annulé dans une commande payée |
| Dashboard acheteur | Événements à venir | 5 (compte des COMMANDES) | **1** (événements distincts) | l'ancienne valeur comptait des commandes ; aujourd'hui compris dans « à venir » |
| Dashboard acheteur | « Événements assistés » | **0 toujours** | renommé « Événements passés » : **0** ici (aucun événement passé dans le jeu) ; 1 avec un événement d'hier | `$paidOrders` était un builder mutable : `>= today` puis `< today` sur la même requête ; renommé car l'assistance n'est pas mesurée |
| Dashboard acheteur | Dépensé | absent | **50 000 FCFA** (somme des lignes des commandes payées) | nouveau ; O3 remboursée (10 000) exclue et comptée dans « Remboursées » |
| Liste des commandes | Commandes / payées / billets | 8 / 5 / 7 | 8 / 5 / 7 | inchangé sur ce jeu |
| Liste des commandes | « FCFA dépensés » | 50 000 (SUM `total_amount`) | **50 000** (SUM des lignes) | même valeur sans frais ; les anciennes commandes avec 500 F de frais baissent de 500 F |
| Profil | 4 chiffres | mêmes valeurs, requêtes dans la vue | mêmes valeurs via BuyerStats | requêtes retirées de la vue |
| Accueil | Billets vendus | non affiché (calculé : 7, `status = sold`, hors `used`) | **8** affiché (sold + used de commandes payées) | le billet scanné de O1 compte ; réservé / remboursé / échoué non |
| Accueil | Événements à venir | non affiché | **2** (publiés, date >= aujourd'hui) | nouvelle section « chiffres » ; brouillons exclus |
| Accueil | Organisateurs | non affiché | **2** (comptes `promoteur`) | agrégat global, aucune donnée par utilisateur |
| Accueil | Utilisateurs / événements publiés / catégories / « aujourd'hui » | calculés (6 requêtes) mais jamais affichés | supprimés | valeur morte |
| Fiche événement publique | Places restantes (A / B / C) | 95 / 48 / 19 (capacité − `quantity_sold`) | **95 / 48 / 19** | D6 confirmée : réservations non payées déduites (anti-survente) ; calcul centralisé (PublicStats) |
| Fiche événement publique | Ventes, revenu, % de vente | calculés côté contrôleur (revenu = toutes les commandes payées chargées en mémoire), non affichés | supprimés ; aucune commande lue | le public ne voit aucun chiffre de vente |

## Points à trancher / à noter
- `decisions.md` (compléments) dit « les réservations non payées restent disponibles » pour le remplissage ; la consigne D6 de la mission impose pour le public « places restantes = capacité − quantity_sold ». Appliqué : le public voit donc potentiellement moins de places que « Places disponibles » du promoteur (qui, lui, laisse les réservations disponibles). À confirmer.
- Événement sans date : ni à venir ni passé. « À venir » = date >= aujourd'hui (journée entière), « passé » = date < aujourd'hui.
- `PageController::about` (compteurs « en chiffres ») n'a AUCUNE route : page morte, laissée de côté.
- Le fallback de `welcome.blade.php` (3 faux événements « Magic System Live ») n'est pas une statistique : laissé tel quel.
- Routes : 199 avant, 199 après. Méthode morte supprimée : `HomeController::show` (aucune route).
