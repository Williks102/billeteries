# Architecte — Unifier les indicateurs (audit + plan, lecture seule)

Branche `claude/audit-ux-stats` (état de claude/retirer-paiementpro). Analyse statique, vérifié = lu dans le code avec fichier:ligne ; les chiffres des scénarios sont calculés à la main à partir de ces lectures (non exécutés : pas de vendor/).
Abréviations : AC = app/Http/Controllers/Admin/AdminController.php ; AN = .../Admin/AnalyticsController.php ; PC = .../Promoteur/PromoteurController.php ; SC = .../Promoteur/SalesController.php ; RC = .../Promoteur/ReportsController.php.

## 1. Inventaire (≈ 70 blocs de calcul répartis sur ~35 fichiers ; 360 occurrences sum/count/selectRaw dans app/)

| Indicateur | Où | Définition exacte | Période par défaut |
|---|---|---|---|
| Revenus plateforme (« total_revenue ») | AC:40, 664, 749 ; Admin/OrderController:84 ; Admin/EventController:80-82 ; Admin/UserController:129 | `Order paid` somme `total_amount` (FRAIS 500 INCLUS) | tout le temps |
| Revenus mois | AC:41-44 ; Admin/OrderController:86-89 | idem + `whereMonth(created_at, now()->month)` SANS année | mois de n'importe quelle année |
| Évolution 6/12 mois | AC:50-66, 256-275, 340-350 | paid total_amount, whereYear+whereMonth (1 requête × mois) | 6 / 12 mois civils |
| Revenus période (revenues, rapport ventes/financier) | AC:236-250, 776-790 | paid total_amount `between(getDateRange)` ; période `this_month` | mois civil |
| Revenus net « plateforme » | AC:253, 268, 791 | `total_amount paid − Commission::sum(commission_amount)` de TOUS statuts (pending, held, cancelled) | idem |
| Commissions (total/paid/pending/held) | AC:153-157, 249, 751, 786-792 ; AN:86 ; Admin/EventController:262-265 | `Commission::sum(commission_amount)` avec ou sans filtre status ; sans lien avec le statut de la commande (commission créée à la commande, CheckoutService:176/257) | tout / mois |
| Commission « gagnée » event | Event::getCommissionEarned (Event.php:192-212) | status=paid ; en cas d'exception : 5 % codé en dur (alors que défaut = 10 % + 500) | tout |
| Taux de conversion | AC:560-566 (visiteurs = 1000 codé en dur, commandes de TOUS statuts) ; AN:24 (`3.2` fixe) ; admin/analytics.blade.php:319, 546 (« +15 % » en dur) | non réel | 30 jours glissants |
| Panier moyen | AC:301-303, 429-433, 481 ; SC:182 ; RC:96-99 | AVG(total_amount paid) frais inclus ; SC/RC : revenu/commandes | variable |
| Commandes | AC:38, 247, 296 ; Admin/OrderController:79-83 ; PC:233 ; SC:140-147 ; RC:110-119 ; Event::getOrdersCount | tantôt toutes (AC:38,247), tantôt `paid` seulement | variable |
| Billets vendus | Ticket `sold` : HomeController:55, AN:19, Admin/TicketController:78, RC:106 ; `sold+used` : Event::getTicketsSoldCount (Event.php:136) ; `!= available` : PC:35-43, SC:132, 235, 316 ; `quantity_sold` : HomeController:142, EventController:56, Admin/EventController:256, 292, admin/events/index.blade:271 ; SUM(order_items.quantity) paid : User::totalTicketsSold (User.php:119), Acheteur (AcheteurController:35-39, OrderController:68-74) | 5 définitions différentes (cf. §2) | variable |
| Places disponibles | TicketType::remainingTickets (TicketType.php:62) = `quantity_available − quantity_sold` ; Admin/EventController:261 ; AN:40 (`sum(quantity_available)` = CAPACITÉ, étiquetée « available ») ; Event::availableTicketsCount (Event.php:149) appelle `totalTicketsSold()` qui n'existe que sur User => erreur fatale si appelée | | |
| Taux de remplissage | Event::getProgressPercentage (Event.php:177) sold+used / capacité ; TicketType:67 quantity_sold/capacité ; EventController:65-68 ; admin/events/index.blade:272 ; promoteur/sales/index.blade:251-252 | numérateur différent selon l'écran | |
| Taux d'utilisation / présence | Admin/TicketController:83 ; ScannerController:205-213 ; admin/tickets/show.blade:371-375 ; AN:41 (`attendance_rate` = un COMPTE, pas un taux) | used / sold avec `sold` EXCLUANT `used` (donc taux faussé) | |
| Revenu promoteur | PC:48-57 (Commission `paid` net_amount) ; Admin/EventController:262-265 (net_amount tous statuts) ; User::pendingRevenue (User.php:104 : pending net) ; User::totalRevenue (User.php:94) et Event::totalRevenue (Event.php:124) = paid total_amount BRUT ; SC:114-127 = paid total_amount BRUT ; RC:84-99 = SUM(orders.total_amount) JOINTE sur order_items (doublons) ; Admin/OrderController:142 `total_amount − commission_amount` | 6 définitions | |
| Revenu par événement | SC:211-257, 296-335 (collections) ; RC:145-176 (`SUM(order_items.total_price)`, HORS frais) ; AN:38, 54-58 (total_amount + `price` actuel du type) ; Admin/AdminController::eventsReport:875-893 (collections, tous les events de la période) | frais inclus ou non selon l'écran | |
| Valeur billets (Admin tickets) | Admin/TicketController:86-88 | somme du prix ACTUEL du type pour billets `sold` (ignore le prix payé, ignore `used`) | tout |
| Stats acheteur | AcheteurController:30-50 ; Acheteur/OrderController:64-78 ; resources/views/acheteur/profile.blade:128-155 (requêtes DANS la vue) | commandes (toutes) / payées / billets / dépensé (total_amount, frais inclus) | tout |
| Scans | ScannerController:165-230 ; PC:178-184 (`today_tickets` = tickets créés aujourd'hui sans filtre statut) | `used` ; semaine = 7 j glissants, mois = 1 mois glissant | |
| Heatmap/cohortes/prévisions/abandon | AN:157-270 ; AC:... | HOUR()/DATE() MySQL ; `abandonment_rate => 28.8`, `abandoned_value => 850000`, prévisions `* 1.08/1.15` : SIMULÉS (AN:11, 162-168, 254-262) | 30 j |
| Exports | ExcelExportService (RevenuesExport:190-199 paid total_amount ; CommissionsExport:114-130 tous statuts ; PromotersExport:335-350 net_amount tous statuts) ; AC:493-540, 620-650 ; SC:441-620 (order_items.total_price, DB direct) | SQL propre à chaque export ; `end_date` brut (2026-11-30 => minuit => dernier jour exclu, supposé : valeur non normalisée) | |
| Vues avec logique | acheteur/profile.blade:141-155 ; admin/commissions.blade:182-184 (N+1 : 1 requête par ligne), 219 (`$commission->amount` : attribut non défini, supposé 0 %) ; admin/events/index.blade:270-285 (N+1 : orders() par ligne) ; promoteur/{reports,sales/reports}.blade:183, 284-311 (taux calculés dans la vue, 2 copies identiques) ; promoteur/sales/index.blade:251 ; promoteur/dashboard.blade:304-317 (`monthly_growth`, `monthly_tickets`, `monthly_orders` : clés JAMAIS fournies par PC => toujours 0) ; admin/tickets/show.blade:371-375 | | |
| Composants/ViewComposer | LayoutComposer et AppServiceProvider:47 ne calculent que le layout ; AUCUN composant de carte stat : chaque vue a son HTML | | |

Constat structurel : il n'existe aucune source unique ; Controllers + modèles + vues + exports recalculent chacun leur variante. Fuseau : config/app.php:68 = Africa/Abidjan (= UTC), pas d'écart de fuseau à craindre aujourd'hui (le risque est dans `whereDate` et `between` avec bornes non alignées, cf. §2).

## 2. Écarts de définition (preuves)

Faits du modèle de données (vérifiés) :
- `total_amount` = sous-total + 500 FCFA PAR COMMANDE (CheckoutService:164, SERVICE_FEE:21 ; une commande par événement et par panier).
- Commande créée `pending`, billets `reserved`, `quantity_sold` incrémenté dès la réservation (CheckoutService:154-176, 225-250), commission `pending` créée DÈS LA COMMANDE (CheckoutService:176, 257-272). Pas de statut tickets `refunded` : remboursement => billets `cancelled`, commission `held` (OrderPaymentService:203-207) ; failed => commission `cancelled` (:179).
- Commission : `commission_amount = 10 % × total_amount (frais inclus) + 500` ; `net_amount = total_amount − commission_amount` (Order.php:156-185, CurrencyHelper:80-89). Donc le promoteur reçoit 0,9·S − 50 pour un sous-total S ; la plateforme 0,1·S + 550. Le « frais de service 500 » du client et le « platform_fee 500 » de la commission sont deux notions différentes qui coïncident : à clarifier (décision D2).
- Un billet scanné passe de `sold` à `used` : un filtre `status='sold'` le fait DISPARAÎTRE des ventes.

### Jeu de données canonique (réutilisable pour le lot 0)
Événement E (promoteur P), types A (10 000 F, capacité 100) et B (5 000 F, capacité 50). Commission 10 % + 500. Toutes créées ce mois-ci.
- O1 `paid` : 2×A = 20 000 + 500 = **20 500**. Billets : 1 `sold`, 1 `used`. Commission 2 550 (net 17 950), pending.
- O2 `pending` : 1×A = **10 500**. Billet `reserved`. Commission 1 550 (net 8 950), pending.
- O3 `refunded` : 1×A = **10 500**. Billet `cancelled`, stock libéré. Commission 1 550, `held`.
- O4 `paid` : 1×A + 1×B = 15 000 + 500 = **15 500**, 2 billets `sold`. Commission 2 050 (net 13 450), pending.
Aucune commission n'a été marquée `paid` par l'admin. Acheteur X possède O1, O2, O3. `quantity_sold` : A = 4, B = 1 (=5).

### Ce que chaque vue affiche pour ce même jeu

| Indicateur | Vue (fichier:ligne) | Affiche | Pourquoi |
|---|---|---|---|
| « Revenu » | Admin dashboard (AC:40) | 36 000 | paid, total_amount frais inclus |
| idem | Promoteur ventes (SC:122-126) | 36 000 | idem |
| idem | Promoteur rapports (RC:84-99) | **51 500** | JOIN order_items puis SUM(orders.total_amount) : O4 (2 lignes) compté 2 × 15 500 ; faux dès qu'une commande a 2 types |
| idem | Promoteur dashboard (PC:48-52) | **0** | Commission `paid` net : rien tant que l'admin n'a pas payé |
| idem | Admin event « revenue_promoteur » (Admin/EventController:262-265) | **49 300** | net_amount de TOUTES les commissions (y compris O2 pending, O3 held) |
| idem | Rapport promoteur par événement (RC:161) | 35 000 | SUM(order_items.total_price) : sans frais |
| Revenu net admin | AC:253 et :791 | 36 000 − 7 700 = **28 300** | soustrait les commissions de TOUS statuts (dont O2 non payée, O3 remboursée) ; le net juste serait 36 000 − (2 550 + 2 050) = 31 400 |
| Commissions « total » vs « pending » | AC:153-155 | 7 700 / 6 150 | O2 (non payée) et O3 (remboursée, held) comptent |
| Billets vendus | Promoteur dashboard (PC:35-37) / SC:132 | **6** | `!= available` : sold 3 + used 1 + reserved 1 + cancelled 1 |
| idem | Promoteur rapports (RC:106) | **3** | seulement `sold` : le billet scanné disparaît |
| idem | Promoteur event show (Event.php:136) | **4** | sold + used |
| idem | Page publique / admin event (quantity_sold) | **5** | inclut le billet réservé O2 non payé |
| idem | Accueil public (HomeController:55) | **3** | sold |
| Taux de remplissage event | Event.php:177 | 4/150 = 2,67 % | |
| idem | Page publique (EventController:65-68) | 5/150 = 3,3 % | |
| Taux d'utilisation | Scanner + admin tickets | 1/3 = 33,3 % | `sold` exclut `used` ; juste = 1/4 = 25 % |
| Valeur billets (Admin tickets:86) | | 25 000 | prix courant des 3 `sold` (35 000 payés hors frais) |
| Commandes | Admin dashboard (AC:38) / Admin analytics (AC:296) | **4** / 2 | AC:38 compte tout ; AC:296 paid |
| Conversion | AC:560 | 4/1000 = **0,4 %** (4 = commandes de tous statuts, 1000 = inventé) ; AN:24 affiche **3,2 %** | simulé |
| Acheteur X (Acheteur/OrderController:64-78) | | 3 commandes, 1 payée, 2 billets, 20 500 « dépensé » | frais inclus ; O3 remboursée ignorée (acceptable mais non dit) |
| Acheteur dashboard `past_events` (AcheteurController:33, 45-49) | | **0 toujours** | `$paidOrders` est un builder mutable réutilisé : `whereHas(>= today)` est ajouté, puis `whereHas(< today)` s'y ajoute => conditions contradictoires |
| « Ce mois » | AC:41-44, Admin/OrderController:86-89, Admin/{Event:79,User:58,Ticket:82} | cumule novembre de TOUTES les années | `whereMonth` sans `whereYear` |
| Croissance promoteur (PC:200-216) | | `lastMonth = now()->subMonth()->month` sans année ; en janvier compare à décembre de toutes années ; vue affiche « +x % » avec flèche haut même négatif (dashboard.blade:163) | |

Top 5 (preuves) : (1) revenus 36 000 / 51 500 / 0 / 35 000 / 49 300 selon l'écran ; (2) billets vendus 3 / 4 / 5 / 6 selon l'écran ; (3) RC doublonne les commandes multi-types (JOIN) ; (4) `past_events` toujours 0 + `whereMonth` sans année + clés de vue jamais fournies ; (5) indicateurs simulés présentés comme réels (conversion 3,2 %/0,4 %, abandon 28,8 %, prévisions +8/15 %, « +15 % ce mois » en dur).

Autres écarts :
- Périodes : mois civil (AC:569-575, SC, RC) vs 30 jours glissants (analytics AC:542-553, AN:117-210) vs « all » par défaut (SC:22) vs mois par défaut (RC:24) : le même libellé « revenus » change de sens selon la page. `whereDate` vs `between` : getRevenueEvolution (AC:597-617) `between` début/fin non normalisé à startOfDay ; AC:640 `calculateConversionRate([$startDate,$startDate])` (intervalle de 0 s).
- Dates de ventes : billets filtrés par `tickets.created_at` (SC:135, RC:107) = date de création/réservation, pas de paiement ; commandes par `orders.created_at`, pas `paid_at` (colonne ajoutée par paiements-stock) : une commande payée le 1er du mois suivant tombe dans le mauvais mois.
- MySQL seulement : `HOUR()` AN:197,201 ; `DATE_FORMAT` RC:190 ; `DATE()` AN:43 ; `whereMonth/whereYear` sont portables via Laravel (OK) mais lents (non sargables). Les tests tournent en SQLite : ces chemins ne sont pas testables.
- N+1 / agrégats PHP : getRevenueEvolution 1 requête par jour (jusqu'à 366) ; dashboard 18 requêtes mensuelles ; revenues 24 ; heatmap 48 ; SC:211-257 et 296-335 (3 requêtes par événement + collections) ; AC:861-893 charge tous les événements + commandes en mémoire ; Scanner stats charge `ticketTypes.tickets` de tous les événements (ScannerController:198-205) ; vues commissions/events index (1 requête par ligne).
- Code mort/incohérent : SC::reports/export doublon (BOARD R3) ; deux vues promoteur identiques (promoteur/reports.blade et promoteur/sales/reports.blade) ; Event::availableTicketsCount cassé ; `User::getSalesStats` (User.php:136) : la requête est consommée par `sum` puis réutilisée pour `count`/`join` (builder mutable, résultat à vérifier).
- Commission « 5 % en repli » (Event.php:207) contredit le défaut 10 % + 500 (Order.php:161).

## 3. Glossaire canonique proposé (montants en FCFA entiers, fuseau Africa/Abidjan)

Statuts : « vente » = commande `payment_status = paid`. `pending/processing` = en attente (jamais comptées comme ventes). `failed` = ignorée. `refunded` = exclue des ventes, comptée à part.

| Indicateur | Formule | Période / date de référence |
|---|---|---|
| Commandes payées | COUNT(orders paid) | `paid_at` (repli `created_at`) |
| Chiffre d'affaires brut (GMV) | SUM(order_items.total_price) des commandes paid = billets seuls, SANS frais | idem |
| Frais de service perçus | SUM(total_amount − SUM(items)) des commandes paid = 500 × nb commandes | idem |
| Encaissé client | SUM(total_amount) paid = GMV + frais | idem |
| Billets vendus | COUNT(tickets) status IN (`sold`,`used`) liés à une commande paid ; `reserved`/`cancelled`/`available` exclus | date de paiement de la commande |
| Billets scannés | COUNT(tickets used) ; date = `used_at` | |
| Taux d'utilisation | scannés / vendus (vendus = sold+used) | |
| Places réservées (stock) | `quantity_sold` (inclut réservations non payées) : réservé aux écrans de vente/stock | instantané |
| Places disponibles | capacité − quantity_sold (ce que le client peut encore acheter) | instantané |
| Taux de remplissage | billets vendus / capacité (affichage public : places réservées / capacité ; à trancher D6) | instantané |
| Commission plateforme | SUM(commissions.commission_amount) des commandes paid, statut commission pending/paid/held-> voir D1 ; exclut `cancelled` | |
| Revenu promoteur (net) | SUM(commissions.net_amount) des commandes paid ; « à recevoir » = statut pending, « versé » = paid | |
| Revenu plateforme | commission_amount (qui contient déjà les 500 F de frais selon la formule actuelle) | |
| Panier moyen | Encaissé client / commandes payées (ou GMV : D3) | |
| Taux de conversion | NON DISPONIBLE tant qu'il n'y a pas de comptage de visites ; sinon proxy « commandes payées / commandes créées » nommé « taux de paiement » (AC:585-594 existe déjà) | |
| Remboursements | COUNT et SUM(total_amount) des commandes refunded | `updated_at` du refund |
| Période | objet `DatePeriod` normalisé : `[start 00:00:00, end 23:59:59.999]` Africa/Abidjan, presets : today, 7d, 30d, this_month, last_month, this_year, all ; PAS de mélange mois civil / glissant sous le même libellé | défaut unique par écran, affiché à l'écran |

Décisions métier à faire trancher par l'utilisateur :
- D1. Une commission d'une commande `refunded` (statut `held`) ou `pending` (non payée) compte-t-elle ? Proposition : seules celles de commandes paid ; held = à part.
- D2. Les 500 FCFA de frais de service sont-ils un revenu de la plateforme séparé, ou déjà dans la commission (c'est le cas aujourd'hui : `commission = 10 % × (S+500) + 500`, ce qui fait payer 10 % de commission sur les frais ET compte 500 de platform_fee) ? Le calcul actuel donne au promoteur 0,9·S − 50.
- D3. « Revenu » affiché au promoteur : GMV hors frais (35 000), ou net après commission (31 400) ? Quelle est LA définition affichée sur son dashboard (aujourd'hui 0 tant que l'admin ne marque pas `paid`) ? Proposition : « Ventes » = GMV, « Revenu net » = net commission, « Versé » = commissions paid.
- D4. Un billet remboursé compte-t-il comme vendu ? Proposition : non (comme aujourd'hui `cancelled`), compté dans « remboursements ».
- D5. Billets `used` : vendus ? Proposition : oui (sold + used).
- D6. Remplissage public : réservations non payées comptent-elles (anti-survente) ou seulement les ventes confirmées ?
- D7. Frais 500 : par commande (code actuel) ou par billet (CGU, cf. BOARD UI-CGU-PM) ? Impacte tous les montants.
- D8. Indicateurs simulés (abandon, prévisions, heatmap rapide, conversion) : supprimer, ou garder avec badge « estimation » ?
- D9. Période par défaut du promoteur (SC « all », RC « mois ») et de l'admin (dashboard « tout », analytics « 30 j »).

## 4. Architecture cible (simple)

```
app/Support/Stats/DatePeriod.php        value object (start, end, label, presets, from(Request))
app/Services/Stats/Scope.php            value object : admin() | promoter($id) | event($id) | buyer($id)
app/Services/Stats/SalesStats.php       paidOrders count, gross, fees, collected, avgBasket, byDay/byMonth/byHour(Scope, DatePeriod)
app/Services/Stats/TicketStats.php      sold, used, reserved, capacity, available, fillRate, usageRate (Scope)
app/Services/Stats/CommissionStats.php  platform commission, promoter net by status, platform revenue (Scope, DatePeriod)
app/Services/Stats/OrderStats.php       counts by payment_status, refunds, success rate
app/Services/Stats/DashboardStats.php   façade qui compose les 4 pour chaque écran (une méthode par écran)
resources/views/components/stat-card.blade.php, stat-grid.blade.php  (props : label, value, unit, delta, hint, estimate)
```
Règles : (1) une seule base de requêtes `Order::query()->paid()` + scopes `forScope()` / `inPeriod()` ajoutés sur les modèles ; (2) agrégation SQL uniquement (`selectRaw` avec expressions portables : regroupement jour/mois via `whereBetween` + groupBy sur `DATE(created_at)` n'est pas portable partout, donc utiliser un petit helper `SqlDate::day($col)` qui renvoie `date(col)` (SQLite) / `DATE(col)` (MySQL) / `strftime` vs `DATE_FORMAT` selon `DB::getDriverName()`, ou à défaut grouper en PHP sur des lignes déjà réduites `(created_at, total)` bornées à la période) ; jamais `HOUR()` brut ; (3) les montants sont des `int` (FCFA), formatage dans `<x-stat-card>` / helper `CurrencyHelper` ; (4) `Cache::remember` 60 s optionnel, clé = scope+période ; (5) contrôleurs : `app(DashboardStats::class)->adminDashboard($period)` et passent les DTO/tableaux à la vue ; (6) vues : plus de requêtes ni de `sum/count/round` ; (7) exports Excel/PDF/CSV lisent les mêmes services (même scope/période) ; les exports de lignes détail utilisent `Order::paid()->inPeriod()` ; (8) `DatePeriod` expose `start/end` déjà normalisés, un seul parseur de `?period=&start_date=&end_date=` ; (9) valeurs non calculables renvoient `null` et la carte affiche « — » (jamais un chiffre inventé) ; (10) pas de DDD : classes de requête plates, injectables, testables sur SQLite.

## 5. Plan par lots (atomiques, réversibles)

| Lot | Contenu | Fichiers | Agent | Tests | Risques |
|---|---|---|---|---|---|
| L0 | Tests de caractérisation chiffrés sur le jeu canonique (§2) : figent les valeurs ACTUELLES des écrans (y compris les « faux », marqués `@group legacy`) et les valeurs CIBLES (`markTestIncomplete`/skipped) ; factory `StatsFixture` | tests/Feature/Stats/*, tests/Concerns/StatsFixture.php | qa-tests | php artisan test ; SQLite | HOUR()/DATE_FORMAT non testables en SQLite (documenter) |
| L1 | Glossaire validé + DatePeriod, Scope, SalesStats, TicketStats, CommissionStats, OrderStats + scopes modèles (`Order::scopePaid` existe Order.php:70 ; ajouter `inPeriod`, `forScope`) ; AUCUN écran modifié | app/Support/Stats/*, app/Services/Stats/*, Order/Ticket/Commission (scopes seulement) | backend générique (architecte relit) | tests unitaires du service sur le jeu canonique (valeurs cibles du §3) | choix D1-D7 non tranchés : bloquant pour les chiffres cibles |
| L2 (parallélisable avec L1 après glossaire) | Composants `<x-stat-card>`, `<x-stat-grid>` + page de démo | resources/views/components/stat-*.blade.php | ui-design | rendu snapshot | conflit avec chantier mobile (voir §6) |
| L3 | Admin : dashboard, commissions, revenues, reports (sales/financial/events/users), Admin/{Event,Order,Ticket,User} index/show stats | AC (dashboard/commissions/revenues/reports), Admin/{Event,Order,Ticket,User}Controller, vues admin/dashboard, commissions, revenues, reports/*, events/index, orders/index, tickets/index+show | backend + ui-design (vues) | L0 passe à « valeur cible » ; N+1 vérifié | chiffres admin changent (cf. liste) |
| L4 | Promoteur : dashboard + getDashboardStats, ventes, rapports (supprimer la copie dupliquée), scanner stats, events show/index | PC, SC (+ suppression reports/export morts : R3), RC, ScannerController::stats, Promoteur/EventController::show, vues promoteur/{dashboard,reports,sales/*,events/index|show|edit,scanner/stats}, Event.php (délégation) | backend + ui-design | idem | conflit fort avec simplification création événement : fichiers promoteur/events/* |
| L5 | Acheteur et public : dashboard, orders index/profile (retirer requêtes de la vue), page événement publique, accueil | AcheteurController, Acheteur/OrderController, HomeController, EventController (public), vues acheteur/*, events/show, home | backend + ui-design | idem | `past_events` passe de 0 à la vraie valeur |
| L6 | Analytics admin : supprimer/étiqueter les valeurs simulées (D8), heatmap/cohortes en SQL portable, remplacer le `HOUR()` | AN, AC analytics*, admin/analytics.blade | backend + ui-design | tests SQLite des requêtes | décision D8 ; modification possible d'URL JS (admin-*.js) : ne pas changer les routes |
| L7 | Exports : ExcelExportService/Exports, AC export*, SC export* lisent les services ; borne de fin normalisée | app/Services/ExcelExportService.php, app/Exports/*, AC:493-540,620-650,905-950 | backend | test export = chiffres des écrans | formats de colonnes publics |

Ordre : L0 → (L1 ‖ L2) → L3 → L4 → L5 → L6 → L7 ; L3/L4/L5 sont parallélisables entre eux une fois L1+L2 faits si les fichiers ne se recouvrent pas (L3 = admin, L4 = promoteur, L5 = acheteur/public ; seul Event.php et les scopes de L1 sont partagés : à figer en L1). L6 et L7 parallélisables après L3. Chaque lot : un commit, un `composer run check` vert, la liste de ses chiffres changés soumise à l'utilisateur AVANT merge.

Chiffres qui CHANGERONT (à valider) :
- L3 : revenu admin = choix D3 (36 000 → GMV 35 000 / encaissé 36 000 ; libellés explicites) ; revenu net 28 300 → 31 400 ; commissions totales 7 700 → 4 600 (O2, O3 exclues) ; « ce mois » ne cumule plus les autres années ; commandes dashboard 4 → 2 payées (+ ligne « en attente 1 / remboursées 1 ») ; usage_rate 33,3 → 25 % ; « valeur billets » 25 000 → 35 000.
- L4 : dashboard promoteur revenu 0 → net 31 400 − (à voir D3) ; rapports 51 500 → 35 000 (correction du doublon JOIN) ; billets 6 / 3 / 4 → 4 partout ; croissance corrigée (année) ; blocs mensuels `monthly_*` de la vue deviennent réels.
- L5 : `past_events` 0 → vraie valeur ; public « vendus » 5 → selon D6 ; accueil 3 → 4.
- L6 : conversion 3,2 %/0,4 % → « — » ou « taux de paiement » 50 % (2 payées/4) ; abandon/prévisions supprimés ou badge « estimation ».
- L7 : exports alignés sur les écrans.

## 6. Conflits avec chantiers parallèles

- Mobile (resources/views/**) : fichiers communs = admin/dashboard, admin/commissions, admin/events/index, admin/tickets/*, promoteur/dashboard, promoteur/reports, promoteur/sales/*, acheteur/{dashboard,profile,orders}, events/show, home, layouts/*. Risque max sur les vues stats.
- Simplification création événement (promoteur/events/**) : communs = promoteur/events/{index,show,edit}.blade (blocs stats lignes 218-335), Promoteur/EventController, TicketTypeController (création de billets pré-générés `available`), Event.php, TicketType.php.
- Ordre recommandé : (a) L0, L1 (aucune vue) et L7/L6 backend n'entrent pas en conflit : à faire en premier, en parallèle des deux chantiers ; (b) L2 (nouveaux fichiers de composants) sans conflit ; (c) laisser mobile et création événement fusionner d'abord, puis faire L3-L5 par-dessus (rebase) : les vues sont le point de collision, donc migrer les vues en dernier et par petites PR ; (d) pour promoteur/events/*, ne toucher que les blocs stats (remplacer par `<x-stat-grid>`) après fusion de la simplification ; (e) ne pas toucher aux routes (public/js/admin-*.js).
- Interdits respectés : aucun fichier paiement/stock modifié par cet audit ; L1 lit `OrderPaymentService`/`CheckoutService` mais ne les change pas (SERVICE_FEE à exposer via constante existante CheckoutService::SERVICE_FEE).
