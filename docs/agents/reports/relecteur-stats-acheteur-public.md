# Relecture indépendante : statistiques acheteur et public (L5)

Branche `claude/stats-acheteur-public` (commits cb79d23, d212cc8), diff `origin/main...HEAD` : 27 fichiers. Relecteur indépendant : je n'ai pas participé à l'implémentation. Lecture seule ; les mutations ont été faites dans une copie jetable (`scratchpad/mutL5`), jamais dans le dépôt.

## Verdict : APPROUVÉ SOUS RÉSERVES

Aucun Bloquant. Les chiffres sont exacts, l'isolation entre acheteurs tient (mutations rouges), la fiche publique ne lit plus aucune commande, les routes ne changent pas (199) et le check est vert. Trois Importants restent à trancher ou à corriger : un trou de test sur la confidentialité de l'accueil, la contradiction D6 avec `decisions.md`, et la section de l'accueil qui n'a pas été demandée.

## Exécution
- `composer run check` : `OK (121 tests, 1077 assertions)`, `OK (250 tests, 7881 assertions)`, `OK (243 tests, 2642 assertions)`, `OK (54 tests, 2314 assertions)`.
- `php artisan route:list` : `Showing [199] routes`. Aucun fichier `routes/` modifié.
- MySQL et TiDB : non exécutés. `SUBSTR(date,1,10)` et `COUNT(DISTINCT CASE … END)` sont portables à la lecture [SUPPOSÉ].

## Justesse (recalculée à la main depuis StatsFixture, « maintenant » = 15/11/2026 Africa/Abidjan) [VÉRIFIÉ]
- **Acheteur 1, jeu canonique :**
  - 8 commandes : 5 payées (O1, O4, O5, O8, O9), 1 en attente (O2), 1 remboursée (O3), 1 échouée (O6).
  - 7 billets : 2 + 2 + 1 + 1 + 1 (sold + used, le billet `used` de O1 compris).
  - Dépensé : 50 000 = 20 000 + 15 000 + 0 + 10 000 + 5 000. O3 est exclue, la gratuite compte pour 0.
  - Événements : 1 à venir (l'événement 1, daté du 01/01/2027), 0 passé.
- **Jeu étendu du test :** 12 commandes, 8 payées, 10 billets, 73 000 F, 2 à venir, 1 passé. Ces valeurs concordent avec `BuyerPublicScreensTest` et avec le HTML rendu (cartes `data-stat`).
- **« Aujourd'hui » est à venir :**
  - `BuyerStats::today()` convertit l'heure vers Africa/Abidjan, puis compare `SUBSTR(event_date,1,10) >= 'AAAA-MM-JJ'` : c'est une comparaison de date à date, pas de date+heure.
  - C'est cohérent avec `TicketType::eventAcceptsSales()` (`>= today`) et avec la liste de l'accueil.
  - Un événement sans date n'est compté ni à venir ni passé.
- **Accueil :**
  - Billets vendus 8 : 7 tickets sold + used des commandes payées, plus O7.
  - Événements à venir 2 : les événements 1 et 2 publiés ; le brouillon est exclu dans le test étendu.
  - Organisateurs 2 : c'est le nombre de comptes `role = promoteur`, quelle que soit leur activité, y compris ceux qui n'ont publié aucun événement (`PublicStats::roles()`, l. 63-69). Le libellé d'aide « Comptes organisateur inscrits » le dit honnêtement.
- **Places restantes publiques :** 95 / 48 / 19 (capacité − quantity_sold) sont exactes. `max(0, …)` empêche une valeur négative (testé avec 130 vendus sur 100). Le badge reste correct (Complet, Bientôt complet, Disponible).
- **Tableau avant/après :** les valeurs « avant » recalculées depuis `git show origin/main` sont exactes :
  - dashboard « à venir » 5 : on comptait des commandes ;
  - « assistés » 0 : le builder `$paidOrders` était muté ;
  - billets 7 ;
  - dépensé (liste) 50 000 ;
  - accueil 7 (`status = sold` uniquement).

  Seule erreur : la mention « D6 confirmée » (voir I2).

## Bloquants
Aucun.

## Importants

**I1. Confidentialité de l'accueil : un nom d'utilisateur affiché passe tous les tests.**
- Mutation M7 : ajout de `{{ \App\Models\User::find(1)?->name }}` dans `welcome.blade.php`. Résultat : `OK (191 tests)`, la mutation reste verte.
- Cause 1 : `test_home_and_event_pages_expose_no_per_user_data` (BuyerPublicScreensTest.php:263-276) ne cherche que des e-mails, des numéros de commande et des codes de billets, jamais les noms. Avec un e-mail à la place du nom, la mutation devient rouge.
- Cause 2 : le garde-fou lexical `BuyerPublicViewsHaveNoQueriesTest::FORBIDDEN` ne contient ni `::find`, ni `::first`, ni `::all`, ni `->name`.
- Correctif, à confier à **qa-tests** :
  - ajouter les noms de la fixture (« Acheteur », « Acheteur 2 ») aux chaînes interdites ;
  - ajouter `::find(`, `::first(`, `::all(` et `\App\Models\` à la liste FORBIDDEN.

**I2. D6 contredit une décision écrite, et le rapport affirme l'inverse.**
- `docs/agents/decisions.md:43` dit : « Remplissage public : billets vendus seulement ; les réservations non payées restent « disponibles » ».
- La branche affiche au public capacité − quantity_sold, réservations déduites. C'est le même comportement que sur `main` : aucune régression, et c'est cohérent avec `remainingTickets()` et l'anti-survente.
- Le tableau de `stats-acheteur-public-chiffres-qui-changent.md` écrit pourtant « D6 confirmée », alors que la section « Points à trancher » écrit « À confirmer ».
- Correctif :
  - le **propriétaire** tranche ;
  - le **développeur** retire « confirmée » du tableau ;
  - le **chef d'orchestre** met `decisions.md` à jour selon la décision.

**I3. Section de 3 cartes ajoutée à l'accueil sans demande** (`welcome.blade.php:65-74`).
- Ce n'est pas de la logique métier dans la vue : c'est propre et testé.
- Mais c'est un ajout d'interface non demandé, qui expose publiquement des volumes de vente (voir Sécurité M-1).
- Décision du **propriétaire** requise avant fusion (avis ci-dessous).

## Mineurs
- `Acheteur/OrderController.php:38` : `withCount('tickets')` s'ajoute au chargement anticipé `tickets.ticketType` déjà présent. C'est une sous-requête redondante, sans effet sur le nombre de requêtes.
- `Acheteur/OrderController.php:75` (préexistant) : le `catch` appelle `collect()->paginate(10)`, méthode inexistante, donc erreur 500 dans la branche d'erreur. `stats => null` y est bien géré par le partial.
- `components/event-card.blade.php` et `Event::remainingPlaces()` : sur les listes (catégories, recherche), les `ticketTypes` ne sont pas filtrés. Les types inactifs ou hors période de vente comptent donc dans les places restantes. C'est préexistant, mais c'est désormais centralisé : à documenter.
- `EventController::show` (préexistant) : la fiche d'un événement publié mais passé affiche encore le formulaire de réservation. Le serveur refuse ensuite (`eventAcceptsSales`).
- `test_guest_is_redirected_to_login_on_buyer_screens` : `assertRedirect()` ne vérifie pas que la cible est la page de connexion.
- Mutation `paid_at` → `created_at` : sans objet pour L5, car l'acheteur et le public n'ont aucune période. Elle est rouge via SalesStatsTest et PromoteurScreensTest.
- La ligne L5 du BOARD ne respecte pas le format à 6 colonnes : le statut est placé dans la colonne « Fichiers ».
- Cibles tactiles < 44 px : les boutons `btn-sm` des tableaux du dashboard sont préexistants et non touchés. Aucune nouvelle cible. Aucune mention « frais de service » (garde-fou testé).

## Portée [VÉRIFIÉ]
- Non modifiés : OrderPaymentService, CheckoutService, `TicketType::reserveTickets`, `Order::createCommission`, routes.
- `Event.php` et `TicketType.php` : uniquement des ajouts (`remainingPlaces`, `ticketTypesCount`, `publicRemaining`). `remainingTickets()` et `isAvailable()` sont inchangés.
- `HomeController::show` était sans route (199 avant, 199 après). L'ordre de la liste de l'accueil est inchangé : la 2e affectation écrasait déjà la 1re sur `main`.
- `PendingCapsTest` (`total_orders` → `orders_total`) : changement légitime. La clé venait de `Acheteur\OrderController::index` (vue `acheteur.orders`). Grep : plus aucun lecteur de l'ancienne clé côté acheteur. `User.php:152` a sa propre clé `total_orders`, sans rapport.
- Fichiers hors code : 2 captures JPG dans `docs/audit-visuel`, sans secret. Les noms visibles sont des données de démo [SUPPOSÉ].

## Tests et mutations (copie jetable, Stats + PendingCapsTest)

| Mutation | Résultat |
|---|---|
| M1 liste des commandes : `Order::query()` au lieu de `$user->orders()` | rouge (isolation) |
| M2a profil : `overview(1)` | rouge |
| M2b `Scope::admin()` dans `overview` | rouge (8) |
| M3 « payées » inclut `pending` | rouge (3) |
| M4 `publicRemaining` ignore quantity_sold | rouge (2) |
| M5 `paid_at` → `created_at` | rouge hors L5 (sans objet ici) |
| M6 organisateurs = tous les utilisateurs | rouge |
| M7 nom d'utilisateur sur l'accueil | **vert** (I1) ; avec un e-mail : rouge |
| M8 événements sans filtre `paid` | rouge |
| M9 « aujourd'hui » exclu (`>`) | rouge |
| M10 « % vendus » sur la fiche | rouge |
| M11 requête dans la vue de la fiche | rouge (garde-fou) |
| M12 la fiche charge `orders` | rouge |
| M13 brouillons comptés sur l'accueil | rouge |
| M0 contrôle neutre | vert |

Le budget de requêtes est réellement testé : il est mesuré avant et après 12 commandes ajoutées, sur les 3 écrans.

## Sécurité / confidentialité

**Critique, Élevé : aucun.**

Isolation [VÉRIFIÉ] :
- la liste des commandes part de `$user->orders()`, et la recherche est regroupée dans une closure, donc pas de fuite par `orWhere` ;
- les filtres de statut et de date sont liés (bindings) ;
- la pagination reste dans ce périmètre ;
- `show` et `download` gardent le contrôle `user_id` (403) ;
- le visiteur non connecté est redirigé ;
- un admin qui ouvre le dashboard acheteur ne voit que ses propres commandes ;
- aucun nouvel endpoint ;
- la fiche publique ne lit aucune commande (testé par le journal SQL) et n'expose ni revenu ni pourcentage.

**Moyen, M-1 : « Billets vendus » global sur l'accueil = volume de ventes d'un promoteur tant qu'il y a peu d'organisateurs.**
- La capture `docs/audit-visuel/stats-accueil-1280.jpg` montre « Organisateurs 1 / Billets vendus 5 ». Dans cette situation, l'agrégat est exactement le chiffre de ventes de ce promoteur.
- En observant le compteur dans le temps, on peut aussi suivre les ventes au fil de l'eau.
- Le brief interdit le volume de ventes par promoteur au public.
- Correctif : retirer la carte, ou ne l'afficher qu'au-delà d'un seuil (par exemple au moins 5 organisateurs actifs et au moins 1 000 billets). C'est une décision du propriétaire.

**Faible :**
- `data-available` donne au public les places restantes exactes par type (préexistant). L'évolution dans le temps révèle le rythme des ventes et réservations par événement. C'est accepté par conception (D6).
- Le compteur « Organisateurs » révèle la taille de la plateforme.

## Avis sur la section de 3 cartes de l'accueil : à valider par le propriétaire
En l'état, je recommande de retirer « Billets vendus » et « Organisateurs », ou de les conditionner à un seuil, et de ne garder éventuellement que « Événements à venir ».
- Ce n'était pas demandé.
- Sur une plateforme jeune, des chiffres faibles (1 organisateur, 5 billets) desservent la crédibilité commerciale.
- Le chiffre exposé recoupe celui d'un promoteur (M-1).

## Non vérifié
- MySQL et TiDB.
- Le rendu mobile 390 px de l'accueil : seule la capture 1280 a été vue.
- Le rendu réel des captures du développeur au-delà de la capture de l'accueil.

## Recommandation
Corriger I1 (qa-tests) et la mention D6 du rapport (développeur), obtenir la décision du propriétaire sur D6 et sur la section d'accueil, puis fusionner. Une nouvelle relecture complète n'est pas nécessaire si seuls les tests et la documentation changent.
