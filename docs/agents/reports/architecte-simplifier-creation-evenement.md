# Architecte : simplifier la création d'événement (audit + plan, lecture seule)

Base : branche claude/audit-ux-stats (état de claude/retirer-paiementpro, a49b95b). Analyse statique, rien d'exécuté (pas de vendor/). « Vérifié » = lu dans le code ; « supposé » = déduit.

## 1. État actuel

### 1.1 Parcours promoteur (vérifié)
Routes : `routes/promoteur.php:21-30` (publish/unpublish, resource `events`, resource imbriquée `events.tickets`).

| Étape | Écran / action | Fichier |
|---|---|---|
| 1 | GET events/create : 10 champs | `Promoteur/EventController.php:55-59`, `promoteur/events/create.blade.php` (343 l.) + `public/js/promoteur-events-create.js` (77 l.) |
| 2 | POST store -> redirige vers `events.tickets.index` (page liste, vide) | `EventController.php:64-107` (redirect l.96) |
| 3 | Clic « ajouter » -> GET tickets/create | `promoteur/events/tickets/create.blade.php` (931 l.) |
| 4 | POST tickets.store -> redirige vers `events.show` | `TicketTypeController.php:29-123` |
| 5 | Sur show : bouton « Publier » (POST publish, ou fetch JS l.465) | `show.blade.php:201-210`, `EventController.php:262-271` |

Total : 4 écrans (create, tickets.index, tickets.create, show), 3 POST, 4 chargements de page, 2 redirections intermédiaires sans valeur (l'écran 2 est une liste vide).

Champs :
- Événement : 10 champs (titre, catégorie, image, description, lieu, date, adresse, heure début, heure fin, conditions). 7 obligatoires côté serveur (`EventController.php:67-77` : title, description, category_id, venue, address, event_date, event_time), 3 facultatifs (end_time, image, terms_conditions).
- Par type de billet : 7 champs (nom, prix, description, quantité, max/commande, début vente, fin vente) ; 6 obligatoires (`TicketTypeController.php:44-52`). Pré-remplis par le JS : quantité=100, max=5, début=aujourd'hui, fin=date événement (`tickets/create.blade.php:335,342,350,356`). Reste à saisir : nom + prix.
- Événement minimal avec 2 types : 7 + 2x6 = 19 champs obligatoires, dont 7 + 2x2 = 11 réellement tapés (le reste est pré-rempli), plus 4 clics de navigation + 2 « enregistrer » + 1 « publier ».
- Temps estimé (supposé, desktop, saisie normale) : 5-8 min (description rédigée + adresse + 2 billets + 4 chargements). Sur mobile (formulaires Bootstrap larges, 931 l. de page billets) : 8-12 min.

Validations : 2 jeux de règles côté serveur (store+update dupliqués dans le contrôleur, sans FormRequest) ; 2 jeux JS (`promoteur-events-create.js` : `alert()` bloquants sur date/heure) ; HTML `required`. Règle `event_date after:today` à la création (`EventController.php:72`) mais `required|date` seulement à l'update (l.150) : incohérent.

Volumétrie vues/JS : create 343 + tickets/create 931 + show 530 + edit 364 + tickets/index 388 = 2556 lignes pour le parcours création/édition. JS inline dans tickets/create : ~220 l. (l.285-505).

### 1.2 Dette découverte dans le parcours (vérifié)
- `tickets/create.blade.php` contient DEUX templates complets : l.1-282 (`@extends('layouts.promoteur')`, script l.285-505) puis l.507-932 (`@extends('layouts.app')`, Tailwind, script dupliqué l.697+). Seule une version devrait exister : -425 lignes, comportement de rendu à vérifier (deux `@extends` dans un fichier).
- Un calculateur de gains `components/promoter-earnings-calculator.blade.php` (167 l.) + `js/components-promoter-earnings-calculator.js` n'est inclus que dans son propre commentaire (l.176) : non utilisé dans le parcours (supposé : grep ne trouve aucun `<x-` / `@include` externe).
- `TicketTypeController::store` : logs `Log::info('Données reçues:', $request->all())` (D8, l.37-40) ; et pré-génération des lignes `tickets` (jusqu'à 1000 par type, `generateTicketsForType`, l.131-169) : écriture lourde synchrone, alors que le stock est géré par `quantity_available/quantity_sold`. Impact sur le flux de paiement/stock (agent paiements-stock) : à vérifier avant de la retirer (hors périmètre ici, voir question Q4).
- `Promoteur\EventController::duplicate` (l.285-310) existe mais AUCUNE route (`grep duplicate routes/promoteur.php` : rien ; seules `admin.pages.duplicate`). Elle copie billets ET `sale_start_date/sale_end_date` anciens (donc ventes potentiellement déjà closes) et met `event_date = now()+7j` sans recaler `event_time` (dateTime, garde l'ancien jour) : bug de cohérence.
- `publish()` (l.262-271) ne vérifie que « au moins un billet actif » ; pas de contrôle date future, ni image, ni période de vente.

### 1.3 Parcours admin (vérifié)
Routes : `routes/admin.php:86-99` (resource `events` + `manage-tickets`, `store-tickets`, `quick-publish`...).
- `Admin\EventController::create/store` (l.97-153) : formulaire 11 champs (`admin/events/create.blade.php` 297 l. : titre, catégorie, promoteur, statut, description, lieu, adresse, date, heure, heure fin, image). Boutons « brouillon » / « créer et publier » (l.202-208) envoient `action=draft|publish` que `store` IGNORE : seul `status` (select) compte, et `status` est `required` donc double commande.
- `storeWithMode` (l.162-243), la seule méthode qui gère `management_mode` + billets en ligne : AUCUNE route (`grep storeWithMode routes` vide). Code mort.
- `manageTickets/storeTickets` (l.596-650) : la vue `admin.events.manage-tickets` n'existe pas (`ls resources/views/admin/events` : create, edit, index, show) ; idem `admin.events.manage-hybrid` (cible du redirect l.233, 300, 641). Appel 500 garanti.
- BUGS bloquants de `store` admin (vérifiés par grep) : `$event->logAction()` (l.140) n'existe pas dans `Event` (ni trait) ; `adminHasPermission` (l.599, 617) non plus ; `Event::$fillable` (`Event.php:14-27`) ne contient ni `management_mode`, `max_capacity`, `admin_permissions`, `management_reason`, `management_changed_*` (ignorés silencieusement). Conséquence : `Event::create` réussit, puis `logAction` lève `BadMethodCallException`, capturé -> message « Erreur lors de la création » alors que l'événement EST créé (risque de doublons par re-soumission). Et `status` accepte `pending,rejected` (l.121) alors que l'enum DB n'a que `draft,published,cancelled,finished` (migration `create_events_table.php:22`, aucune migration ne l'étend : grep) -> erreur SQL en MySQL strict.
- `max_capacity` est validé mais n'a ni colonne (migration events) ni champ de formulaire.
- Admin ne peut donc, aujourd'hui, ni créer proprement un événement, ni saisir les billets : le parcours admin « fonctionnel » = créer l'événement (avec fausse erreur), puis passer par l'UI promoteur impossible (middleware promoteur). Il s'agit d'un parcours à reconstruire, pas à simplifier.
- Mode de gestion : colonnes `management_mode` (enum promoter|admin|collaborative, défaut promoter), `admin_permissions` json, etc. (`2025_08_22_113331_...add_management_mode_to_events.php`). Défaut DB = promoter ; à la création, aucun champ utilisateur nécessaire : le mode est un état de gouvernance, pas une donnée de création. Le déplacer vers l'écran d'événement existant (`changeManagementMode`, l.532, `updateStatus`).

## 2. Règles métier incontournables

Pour vendre (vérifié, DB + code d'achat) :
- Événement : `promoter_id`, `category_id`, `title`, `description` (NOT NULL text), `venue`, `address` (NOT NULL), `event_date`, `event_time` (NOT NULL). Nullables : `end_time`, `image`, `terms_conditions`. `status` défaut `draft`.
- Billet : `event_id`, `name`, `price` (int FCFA, 0 autorisé), `quantity_available`, `sale_start_date`, `sale_end_date` (NOT NULL), `max_per_order` défaut 10, `is_active` défaut true, `quantity_sold` défaut 0. `description` nullable.
- Pas de slug sur `events` (le `slug` n'existe que sur catégories, `EventController.php:107`) ; pas de SEO à créer. Image facultative en base ; le catalogue public affiche vraisemblablement un visuel par défaut (supposé, vues non auditées : à confirmer par ui-design).
- Commission : calculée à la commande (`CheckoutService.php:176,257-259`, `Order::calculateCommission`), PAS à la création : aucun champ commission dans le formulaire événement/billet. Création d'événement sans effet de bord commission. Risque nul si on ne touche pas ces appels.
- Effets de bord : image stockée sur disque `public/events` ; `status` publié rend l'événement visible (`scopePublished`) ; `management_mode` défaut promoter.

Verdict « obligatoire pour publier » : titre, catégorie, date, heure de début, lieu, au moins 1 billet actif (nom, prix >= 0, quantité >= 1, période de vente cohérente avec l'événement). Description et adresse sont NOT NULL en base : on peut les rendre « suggérées » en les défaut-ant (adresse = lieu ; description = vide acceptée -> colonne text NOT NULL accepte '') sans migration. Image : recommandée, non bloquante.

## 3. Redondances / défauts / déductions / report

| Champ | Verdict |
|---|---|
| Titre, catégorie, date, heure, lieu | requis, saisie |
| Adresse | par défaut = lieu ; champ « précision d'adresse » dans Options avancées |
| Description | facultative à la création (''), exigée en checklist de publication (recommandé) |
| Heure de fin | avancé (facultatif) |
| Conditions | avancé |
| Image | avancé / recommandé ; ajout possible après publication |
| Date de l'événement | défaut = samedi prochain, 20:00 (proposition) |
| Billet : quantité | défaut 100 (existant) |
| Billet : max/commande | défaut 10 (DB) / 5 (JS) -> aligner à 10, avancé |
| Billet : début vente | défaut = maintenant, avancé |
| Billet : fin vente | défaut = début de l'événement, avancé (déduction ; actuellement date seule + 23:59:59 : coupe après le début d'un événement matinal, à corriger) |
| Billet : description | avancé |
| Statut initial (admin) | remplacé par 2 boutons brouillon / publier |
| Mode de gestion, permissions | hors création, écran événement admin |
| Promoteur (admin) | requis, 1 select (inévitable) |
| Devise | FCFA implicite (prix int FCFA, `create_ticket_types_table.php:20`), aucun champ |
| Calculateur de gains | retirer du formulaire (inutilisé) ; éventuellement texte « commission estimée » repris de la config plus tard |

Reportable après publication : image, conditions, heure de fin, description longue, billets supplémentaires, dates de vente affinées.

## 4. Proposition

Chiffrage comparatif (événement minimal + 2 types) :

| | Actuel | A : formulaire unique | B : assistant 3 étapes |
|---|---|---|---|
| Écrans | 4 | 1 | 1 écran, 3 vues d'étape (+ aperçu) |
| Soumissions serveur | 3 | 1 (ou 2 : brouillon puis publier) | 3-4 (auto-sauvegarde par étape) + 1 publier |
| Champs visibles à l'arrivée | 10 (+ 7 par billet après) | 5 + 2x2 billets = 9 | 5 / 4 / 0 = 9 répartis |
| Champs requis saisis | 11 | 5 (titre, catégorie, date, heure, lieu) + 2 noms + 2 prix = 9 | idem 9 |
| Clics de navigation | 4 | 0-1 (replier/ouvrir une section) | 2 (Suivant) |
| Temps estimé | 5-8 min | 1,5-3 min | 2-3,5 min |
| Coût de dév | - | moyen (1 Form Request, 1 Action, 1 vue + ~120 l. JS) | élevé (état brouillon, 3 vues, endpoints d'auto-sauvegarde, reprise) |
| Fragilité | - | faible | moyenne (brouillons partiels : événements sans billets, nettoyage) |

Défauts intelligents communs : date = prochain samedi, heure 20:00, fin de vente = début de l'événement (datetime, pas date + 23:59:59), début de vente = maintenant, max/commande 10, quantité 100, devise FCFA affichée en suffixe, billet 1 pré-ajouté « Standard » (l'utilisateur ne tape que le prix), bouton « + Ajouter un billet » avec modèles (Standard/VIP/Gratuit : les `preset-card` existent déjà `tickets/create.blade.php:180`), adresse = lieu si vide.

Checklist de publication : composant (méthode `Event::publicationChecklist(): array` ou `PublishEventAction`) retournant les manques : billet actif absent, date passée, période de vente close/hors événement, description vide, image absente (avertissement non bloquant). Affichée en haut de show et dans le formulaire (bouton Publier désactivé si bloquant ; sinon liste « à compléter »). Remplace la condition unique de `publish()` (l.262).

Duplication : brancher `duplicate` via route fixe AVANT la resource (convention) `Route::post('events/{event}/duplicate', ...)->name('events.duplicate')`, et corriger la méthode (décalage de date + heure, billets avec période de vente recalée, quantity_sold=0, image copiée ou partagée, statut draft). Intégrer dans une `DuplicateEventAction`. Bouton « Dupliquer » sur index + show. Gain majeur pour promoteurs récurrents : 0 saisie sauf date.

Mobile-first : une colonne, sections `<details>`/accordéon natifs, boutons collants en bas (Enregistrer brouillon / Publier), inputs `type=date/time` natifs, pas de `alert()`. Le chantier mobile touche `resources/views/**` : voir section 7 ; ne pas dupliquer, la vue unique sera livrée mobile-first par ui-design en coordination.

### Recommandation : variante A
Justification : plus court (1 écran, 0 aller-retour), pas d'état partiel en base (un événement n'existe que complet : il a au moins 1 billet), pas d'auto-sauvegarde/endpoint à sécuriser, mise en oeuvre ~2x plus rapide que B, réutilisable telle quelle pour l'admin (même Action, même partial), testable en une requête Feature. L'assistant B n'apporte une vraie valeur que si les promoteurs abandonnent en cours de saisie ; à réévaluer après mesure. Brouillon explicite (« Enregistrer en brouillon » sans exiger de billet valide ? voir Q2) couvre le besoin de reprise.

## 5. Plan d'implémentation (7 lots, atomiques, réversibles)

Cible technique : `app/Http/Requests/StoreEventRequest.php` (+ `UpdateEventRequest`), `app/Actions/Events/{CreateEventAction,PublishEventAction,DuplicateEventAction}.php`, un partial de formulaire partagé `resources/views/events/_form.blade.php` (promoteur + admin). Contrôleurs : validation via FormRequest, 1 appel d'Action, redirection. Autorisation : `EventPolicy` (promoteur propriétaire ; admin selon management_mode) plutôt que les `abort_if` répétés (D5).

| Lot | Contenu | Fichiers | Agent | Tests (Feature) | Risques |
|---|---|---|---|---|---|
| L0 | Corriger le socle admin : `Event::$fillable` (+ management_mode, admin_permissions, management_reason, management_changed_*), ajouter `logAction`/`auditLogs`/`adminHasPermission` (ou les retirer), aligner enum `status` (retirer pending/rejected de la validation OU migration), supprimer ou router `storeWithMode`, vue manquante manage-tickets/manage-hybrid (ou retrait des routes) | `app/Models/Event.php`, `Admin/EventController.php`, `routes/admin.php`, nouvelle migration éventuelle | backend générique (architecte valide) | admin crée un événement sans fausse erreur ; `management_mode` persisté ; `RoutingConventionsTest` vert | `RoutingConventionsTest` signale les actions absentes ; fillable élargi = mass-assignment : ne jamais passer `$request->all()` |
| L1 | `StoreEventRequest/UpdateEventRequest` (règles uniques, défauts `prepareForValidation` : adresse=lieu, description ''), `CreateEventAction` (événement + billets en transaction, sans pré-génération `tickets`, sans log `$request->all()`) ; contrôleur promoteur `store` branché | `app/Http/Requests`, `app/Actions/Events`, `Promoteur/EventController.php` | backend générique | création complète en UNE requête (événement + 2 billets) ; brouillon vs publié ; 422 date passée / fin de vente avant début / prix négatif / catégorie inexistante ; 403 autre promoteur ; non-promoteur redirigé | pré-génération `tickets` retirée : vérifier avec paiements-stock que checkout ne dépend pas de lignes `tickets` `available` pré-créées (Q4) ; `after:today` -> règle commune |
| L2 | Checklist + `PublishEventAction` (publication refusée avec raisons) ; routes `publish`/`unpublish` inchangées | `Event.php` (méthode), `Promoteur/EventController::publish`, action | backend générique | publier sans billet / date passée / période close -> refus avec liste ; publier OK ; admin `quickPublish` utilise la même action | commission/statut inchangés ; `quickPublish` admin à aligner |
| L3 | Formulaire unique promoteur (sections repliables, billets dynamiques, 2 boutons, Options avancées), suppression du double template et du calculateur inutilisé | `promoteur/events/create.blade.php`, `_form.blade.php`, `tickets/create.blade.php` (suppression duplication), JS | ui-design (après lot L1) | Feature : GET create contient les champs ; POST complet ; `old()` conservé en erreur | conflit mobile (section 7) ; redirection store -> `events.show` (plus `tickets.index`) |
| L4 | Admin : même Action/partial, select promoteur, boutons brouillon/publier réellement pris en compte, ajout billets en ligne ; mode de gestion hors formulaire (défaut promoter) | `Admin/EventController::{create,store}`, `admin/events/create.blade.php` | backend + ui-design | admin crée pour un promoteur ; `management_mode=promoter` ; promoteur propriétaire voit l'événement ; admin sans droit 403 | `public/js/admin-events-*.js` référence des URLs en dur : ne pas changer les routes ; édition admin (617 l.) hors périmètre |
| L5 | Duplication : route `promoteur.events.duplicate` (avant la resource), `DuplicateEventAction` corrigée (dates recalées, billets recalés), boutons index/show | `routes/promoteur.php`, action, `Promoteur/EventController::duplicate`, vues index/show | backend + ui-design | duplication autre promoteur 403 ; copie en draft, `quantity_sold=0`, dates futures cohérentes ; `RoutingConventionsTest` | route statique avant resource (shadowing) |
| L6 | Édition alignée : `edit` réutilise `_form` + gestion des billets sur la même page (ou liens), `tickets.store/update` sur FormRequest | `edit.blade.php`, `TicketTypeController` | ui-design + backend | update d'un billet : quantité >= vendus ; interdits de supprimer vendu | quantity_sold / stock : ne pas toucher aux colonnes de stock, seulement validation |

Ordre : L0 -> L1 -> L2 -> (L3 || L4) -> L5 -> L6. Chaque lot : `composer run check` vert avant commit ; branche dédiée ; aucun commit sur main.

Réversibilité : L1-L2 ajoutent des classes et rebranchent les contrôleurs (revert simple) ; L3-L4 remplacent des vues (garder l'ancienne vue sous un nom de route de repli le temps de la bascule : non nécessaire si revert git) ; aucune migration obligatoire (seulement L0, optionnelle).

Tests à écrire (qa-tests, nouveaux) : `EventCreationTest` (promoteur : une requête événement+2 billets ; validation ; brouillon ; publication refusée/acceptée ; 403 croisé), `AdminEventCreationTest` (création pour un promoteur, management_mode persisté, status invalide), `EventDuplicationTest`. Aucun test existant ne couvre events (`ls tests/Feature | grep -i event` : vide).

Risques de régression principaux :
1. Admin vs promoteur : l'Action doit prendre `promoter_id` explicitement ; ne jamais le lire depuis la requête pour un promoteur.
2. `management_mode` : l'agrandissement de `$fillable` ouvre le mass-assignment ; les modes non-promoteur doivent rester modifiables seulement par `changeManagementMode`.
3. Suppression de la pré-génération des `tickets` (impact paiement/stock : voir Q4).
4. `sale_end_date` : passage de `date + 23:59:59` à datetime = changement de règle visible (ventes closes plus tôt si l'événement a lieu le jour même à 20:00 ; aujourd'hui on peut vendre jusqu'à 23:59).
5. Redirection après store modifiée : liens/tests existants vers `tickets.index` (aucun test, vues à vérifier par ui-design).
6. `RoutingConventionsTest` (noms utilisés dans les vues) : toute vue retirée/ajoutée doit rester cohérente.

## 6. Questions à trancher par l'utilisateur
- Q1 : variante A (recommandée) ou B ?
- Q2 : un brouillon doit-il pouvoir être enregistré SANS billet (plus souple, mais événements incomplets) ou exiger au moins 1 billet dès la création (plus simple et sans état partiel) ?
- Q3 : description et adresse restent-elles obligatoires à la publication ? Image obligatoire (rendu catalogue) ou facultative ?
- Q4 : peut-on supprimer la pré-génération des lignes `tickets` à la création (le stock suit `quantity_*`) ? Décision à valider avec paiements-stock avant L1.
- Q5 : fin de vente par défaut = début de l'événement (proposé), ou fin de journée comme aujourd'hui ?
- Q6 : l'admin doit-il pouvoir créer un événement pour un promoteur (parcours à reconstruire en L4), et le mode de gestion doit-il rester une fonctionnalité (aujourd'hui sans UI de création, code mort et vues manquantes) ou être retiré (simplification) ?
- Q7 : activer la duplication (L5) maintenant ?

## 7. Fichiers à ne pas modifier en parallèle
- Chantier mobile (ui-design) : `resources/views/promoteur/events/{create,edit,show,index}.blade.php`, `promoteur/events/tickets/*`, `admin/events/*`, `layouts/promoteur.blade.php`, `public/css/**`, `public/js/promoteur-events-create.js`, `public/js/admin-events-*.js`. Les lots L3/L4/L6 doivent être séquencés APRÈS ou fusionnés avec le chantier mobile sur ces vues : un seul agent par fichier de vue à la fois.
- Chantier statistiques : `Promoteur\SalesController`, `ReportsController`, `Promoteur\ProfileController` (dashboard), `Event::{totalRevenue,getTicketsSoldCount,getProgressPercentage,getCommissionEarned}` (`Event.php:124-209`) ; L0/L2 touchent `Event.php` : n'ajouter que des méthodes/fillable, ne pas modifier ces méthodes.
- Hors périmètre architecte pour ce chantier : `CheckoutService`, `OrderPaymentService`, `Order::calculateCommission` (paiements-stock).
- Fichiers que ce plan modifie (propriété architecte/backend) : `app/Http/Controllers/{Promoteur,Admin}/EventController.php`, `Promoteur/TicketTypeController.php`, `routes/{promoteur,admin}.php`, `app/Models/Event.php`, `app/Http/Requests/*`, `app/Actions/*`.
