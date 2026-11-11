# Audit mobile de toutes les pages (lecture seule)

Branche : claude/audit-ux-stats (worktree). Date : 2026-11-11. Agent : ui-design.
Méthode : analyse statique (grep/lecture) de `resources/views`, `public/js`, `public/css`. Aucune exécution : pas de `vendor/`, pas de MySQL, donc **aucune capture Playwright** ; tous les scores ci-dessous sont déduits du code (« supposé » = non vérifié visuellement). Les chiffres sont vérifiés (grep).

## 1. Constat global

- Les 5 layouts utilisent Bootstrap 5.3.0 + Font Awesome 6.0/6.4 via CDN bloquants (jsdelivr, cdnjs) : aucun build Vite utilisé dans les vues (`public/build` existe mais aucune vue n'utilise `@vite`). Sur 3G, deux feuilles CDN bloquantes avant premier rendu ; FA complet (~100 Ko+ CSS + webfonts) pour quelques icônes.
- Le socle responsive existe (viewport correct partout sauf 1 exception, sidebars en offcanvas maison sous 768 px, tables dans `table-responsive`). Les vrais problèmes sont : doublons mobile/desktop rendus tous deux dans le DOM, CSS/JS inline massifs par page, zones tactiles < 44 px, formulaires sans `inputmode`/`autocomplete`, parcours panier en modale, JS couplé au DOM par IDs dupliqués.
- Pas de page « inutilisable » démontrée par le code seul ; les bloquants ci-dessous sont des défauts de fiabilité/conversion mobile sur le parcours d'achat ou des vues cassées.

### Chiffres (86 vues hors `emails/`, 7 emails exclus du périmètre)

| Mesure | Valeur |
|---|---|
| `<table` | 21 (dont 4 dans `diagnostic/qr`), tous enveloppés dans `.table-responsive` sauf `admin/events/show.blade.php:46`, `diagnostic/qr` et 2 sur 4 de `admin/orders/show` |
| `style="…width…"` | 53 |
| `px` en dur (vues) | 1494 |
| `@media` dans les vues | 52 (dont 18 dans les 5 layouts) |
| lignes `col-md-*` sans `col-12`/`col-sm-*` | 402 — **pas un défaut en soi** : Bootstrap empile sous 768 px ; seul cas gênant = images `col-md-2` qui deviennent pleine largeur (checkout, voir §4) |
| `btn-sm` | 95 (zone tactile ~31 px) ; `form-control-sm`/`form-select-sm` : 2 |
| `onclick=` inline | 109 |
| `position: fixed` | 16 occurrences |
| `font-size` ≤ 13 px / ≤ 0.8rem | 92 |
| `100vh` | 18 (problème barre d'URL mobile : préférer `dvh`) |
| Lignes de `<script>` inline dans les vues | 3952 |
| `<img>` / `loading="lazy"` | 33 / 1 (seulement `components/events-carousel`) ; aucun `width/height`, aucun `srcset`, aucun webp |
| Modales Bootstrap (`modal fade`) | 10 |
| `inputmode`/`autocomplete` dans toutes les vues | 14 au total (0 dans checkout/cart) |
| CDN supplémentaires | chart.js (7 vues non épinglé), jQuery 3.6 (events/show), sortablejs@latest (admin/pages/index), qrcode 1.5.3 (admin/tickets/show), jsQR 1.4.0 (scanner), date-fns |

## 2. Inventaire des layouts

| Layout | Lignes | Viewport | Nav | Particularités mobile | Étendu par |
|---|---|---|---|---|---|
| `layouts/app.blade.php` | 689 | OK (`:5`) | `partials/header` (navbar-expand-lg, burger `d-lg-none`, version mobile et desktop séparées `header.blade.php:5,68`) ; footer | 332 lignes de CSS inline ; requêtes Eloquent dans le layout (`:453,498,546,598`) ; `.search-container{min-width:350px}` header:345 ; toast `min-width:300px` app:211 | 26 vues (public, auth, checkout, panier, pages, tickets) |
| `layouts/acheteur.blade.php` | 1186 | **`user-scalable=no`** (`:6`, a11y : interdit le zoom) | sidebar fixe 280 px (`:25`), offcanvas ≤ 768 px + overlay + swipe (`:344-360, 1053`) | 735 lignes de CSS inline, 120 `px`, 8 media queries ; bouton burger ~36 px (`:249-259`) ; bouton réduire masqué `d-none d-md-block` (`:766`) | 5 vues acheteur |
| `layouts/acheteurii.blade.php` | 298 | OK | navbar + sidebar | inclut `partials.acheteur-sidebar` **inexistant** (`:217`) ; **aucune vue ne l'étend** : code mort | 0 |
| `layouts/promoteur.blade.php` | 738 | OK | sidebar fixe 280 px, mini 60 px, offcanvas ≤ 768 px (`:390-413`), JS `layouts-promoteur.js` 275 l. | 533 lignes de CSS inline ; badge/typo 10 px (`:276`) ; `onclick` inline | 13 vues |
| `layouts/admin.blade.php` | 798 | OK | sidebar fixe 280 px ; **mini 60 px entre 769 et 1024 px** (`:407`), offcanvas ≤ 768 px (`:422`) ; `data-action` + `layout-admin.js` | 543 lignes de CSS inline ; typo 10 px (`:349`) | 20 vues |

Duplication : acheteur/promoteur/admin répètent les mêmes ~500 lignes de CSS de shell (sidebar, topbar, mobile-toggle, overlay, cards) avec trois variantes de JS (inline vs `layout-admin.js` vs `layouts-promoteur.js`). C'est le principal levier d'unification.

Partials/composants : `partials/header.blade.php` (490 l.), `components/{events-carousel, promoter-earnings-calculator, commission-widget, auto-sidebar}.blade.php`, `components/LayoutComposer.php` (fichier PHP au mauvais endroit dans `views`, vrai composer dans `app/View/Composers`), `checkout/partials/manual-payment`, `events/partials/*`. `auto-sidebar` inclut 3 partials `partials.{admin,promoteur,acheteur}-sidebar` **inexistants** (aussi `app/Components/layout-switcher.blade.php:36-40`, `AppServiceProvider.php:75`) : code mort. Aucun composant Blade réellement utilisé (`<x-…>` : 0 occurrence).

## 3. Inventaire des vues par espace

- **Public (layouts.app)** : `welcome` (477), `home` (23), `events/{all, show, test}` + `events/partials/*` (4), `pages/{about, contact, faq, how-it-works, pricing, privacy, terms}`, `tickets/{verify, show, index, authenticated-verify}` + doublon `ticket/{show, verify}` (md5 identiques à `tickets/*`).
- **Panier / checkout** : `cart/show` (1452 l.), `checkout/{show, guest, guest-confirmation, confirmation, verify-phone}` + `partials/manual-payment`.
- **Auth** : `auth/{login, register, verify}`, `auth/passwords/{email, reset, confirm}` (login/register/passwords sans layout, pages autonomes).
- **Acheteur** : `dashboard, orders, order-detail, tickets, profile, tickets-pdf` (PDF, hors responsive).
- **Promoteur** : `dashboard, profile, reports, events/{index, create, edit, show, tickets/{index, create}}, sales/{index, reports}, scanner/{index, stats (0 octet)}`.
- **Admin** : `dashboard, analytics (1073 l.), commissions, profile, events/{index, create, edit, show}, orders/{index, show}, pages/{index, create, edit, show}, reports/users, tickets/{index, show}, users/{index, create, edit, show}`.
- **Emails** (`emails/*`, 7 vues sur `emails.layout`) : exclus, non audités.
- **Divers** : `diagnostic/qr` (route de diagnostic à ne pas exposer en prod), `events/test`.

Vues référencées par un contrôleur mais **inexistantes** (500 probable ; `grep view('…')` vs fichiers) : `promoteur.reports.index` (le fichier est `promoteur/reports.blade.php`), `promoteur.scanner.recent|search`, `checkout.direct`, `tickets.public-verify`, `acheteur.orders.index`, `acheteur.profile.show`, `acheteur.tickets.index`, `promoteur.profile.show`, `admin.reports.*`, etc. (liste complète produite par la comparaison ; à confier à l'architecte, voir R1). `promoteur/scanner/stats.blade.php` est vide.
Vues présentes mais non appelées par un contrôleur/route (candidates à suppression, à confirmer) : `acheteur.profile`, `acheteur.orders`, `acheteur.tickets` (probablement appelées par d'autres noms, à vérifier), `ticket/*`, `events/test`, `diagnostic/qr`, `home`, `layouts/acheteurii`, `components/auto-sidebar`.
Fichiers `*.old`/`*.backup` : `git ls-files` ne remonte plus aucun fichier de ce type dans `resources/views` (supprimés par P7) : rien à faire.

## 4. Scores par page / groupe (Bloquant / Gênant / OK)

| Groupe | Score | Preuves |
|---|---|---|
| Fiche événement + réservation (`events/show`) | **Gênant** (fort risque) | Formulaire mobile ET desktop rendus dans le DOM (`events/show.blade.php:152-169`), deux systèmes JS en concurrence : script inline mobile (`booking-form-mobile:596-848`, `updateQuantity`, bottom-sheet) et classe `BookingSystem` jQuery 3.6 CDN (`show:176-582`) qui écoute `.qty-btn` globalement (`:203`) ; IDs `_desktop` vs mobile ; boutons +/- 36 px (`booking-form-mobile:481`) ; bottom-sheet `max-height:90vh` (barre d'URL mobile). Minuteur réservation en double (`timerContainer` / `timerContainerDesktop`). |
| Panier (`cart/show`, 1452 l.) | **Gênant / Bloquant selon test** | Sur mobile (≤ 768 px) le contenu panier est masqué (`cart/show:633-640` `.cart-card{display:none}`) ; seul un bouton flottant ouvre une modale bas d'écran (`:665, 941`) : pas de vraie page panier mobile, contenu dupliqué desktop (`:772-850`) / mobile (`:945+`), `Event::find()` dans la boucle de la vue (`:~980`, N+1), 97 `px`, bouton flottant `bottom:20px` fixe sans safe-area. À valider visuellement : si JS échoue, le panier est inaccessible sur mobile. |
| Checkout connecté/invité | **Gênant** | Images `col-md-2` → pleine largeur sous 768 px (`checkout/show:50`, `guest:52`) ; aucun `inputmode`/`autocomplete` sur email/tel/prénom/nom (`guest:95-125`, `show:98-110`) ; pas de CTA collant (le bouton de paiement est dans la colonne de résumé `col-lg-4`, en bas de page sur mobile) ; `guest.blade.php:257-263` force `position:fixed !important` sur tout `.navbar` (conflit avec `sticky-top` du header). Minuteur : pas de rappel dans le checkout (supposé, à vérifier dans `checkout-show.js`). |
| Confirmation (`checkout/*confirmation`) | OK (supposé) | Courtes (56/111 l.), peu de CSS. |
| Accueil / listing (`welcome`, `events/all`) | OK / Gênant mineur | Bootstrap grid ; 1 image lazy sur 3 ; pas de dimensions d'images (CLS) ; carrousel JS 258 l. |
| Pages statiques | OK | Texte, `col-md-*` empilés. `pricing` 553 l. avec 36 px en dur (supposé OK). |
| Vérification publique billet (`tickets/verify`) | OK | Mobile simple (`@extends layouts.app`) ; n'affiche **pas** de PII (statut, titre, date, lieu, type : `verify.blade.php:58-73`). Point : la consigne « validité + nom d'événement seulement » est dépassée (date/lieu/type affichés, non-PII) ; `Ticket::with('orderItem.order.user')` chargé inutilement côté contrôleur. `tickets/authenticated-verify:96-100` affiche nom/email du porteur : réservé aux connectés, à confirmer côté route. Vue référencée `tickets.public-verify` manquante. |
| Auth (login/register/reset) | OK / Gênant | Pages autonomes, mais `register` 453 l. avec 29 px fixes ; `type="tel"`/`email` présents, `autocomplete` partiel. |
| Espace acheteur | **Gênant** | Layout 1186 l. dont 735 de CSS inline, `user-scalable=no` (`acheteur:6`), burger 36 px ; `acheteur/dashboard:167` table à 7 colonnes (scroll horizontal dans `.table-responsive`) ; `acheteur/orders` est déjà en cartes (bon modèle) ; `acheteur/tickets` 581 l. avec 58 `px` ; requêtes dans la vue `acheteur/profile:128-134` (`$user->orders()->count()`). |
| Promoteur : dashboard, listes | Gênant | Header « titre + boutons » en `d-flex justify-content-between` sans `flex-wrap` (115 occurrences dans toutes les vues non wrap) ; graphiques chart.js (7 vues) hauteur fixe (`admin/analytics:58 height:400px`). |
| Promoteur : création événement / billets | Gênant (ne pas toucher maintenant) | `promoteur/events/tickets/create` 931 l. ; stepper 40 px (`:21`) ; 12 champs date/number sans `inputmode`. Chantier concurrent (voir §6). |
| Promoteur : scanner (`scanner/index`) | **Gênant** | Entête `d-flex justify-content-between` sans wrap (`:15-27`) ; texte d'aide long avant l'outil (cartes « Comment utiliser » + « Aide rapide » avant la caméra : `:30-50`, `:120+`) ; bouton « Test permissions » ; `jsQR` depuis CDN (`:153`) : hors-ligne = scanner mort ; `<video width="100%" height="300" style=…>` injecté (`:270`) ; `maxlength=12` + `text-transform` sans `autocapitalize`/`autocomplete="off"` ; pas de retour haptique/sonore ; vue stats vide (`scanner/stats` 0 octet, `showStats()` appelle une route qui rend une vue vide). Peu de CSS donc relativement sain. |
| Promoteur : ventes/rapports | Gênant | `sales/reports` et `reports` identiques en taille (407 l. chacun, doublon) ; chart.js ; tables 7-8 colonnes (`promoteur/events/show:429`, `tickets/index:100`). |
| Admin : tables (events 10 col., commissions 10, orders 9, tickets 9, users 8, pages 8) | **Gênant** | Scroll horizontal dans la carte (`admin/events/index:164`, `commissions:132`, `orders/index:167`, `tickets/index:382`, `users/index:121`) ; `admin/events/show:46` table sans `table-responsive` ; `admin/orders/show:67,91` (2 tables sur 4 non enveloppées, supposé). Sidebar mini 60 px entre 769-1024 px : tablette OK. |
| Admin : analytics (1073 l.) | Gênant | 39 `px`, graphiques hauteurs fixes (`:58, :66, :180`), 3 scripts inline. |
| Admin : formulaires (users/events/pages edit/create) | Gênant | 741 l. (`users/edit`), `col-md-*` OK, mais 24 `px` ; éditeur de page (`pages/create|edit`) non évalué visuellement. |
| `diagnostic/qr`, `events/test` | hors périmètre | Ne pas exposer en production. |

Aucun groupe n'est classé Bloquant avec preuve statique certaine ; le panier mobile (modale only) est le candidat n°1 à vérifier par Playwright dès que l'app tourne.

## 5. Doublons et dette (consolidation proposée)

1. `layouts/acheteurii` + `components/auto-sidebar` + `app/Components/layout-switcher.blade.php` : code mort (partials inexistants) → supprimer (après `grep`, aucun `@extends('layouts.acheteurii')` trouvé).
2. `ticket/*` = `tickets/*` (md5 identiques) → supprimer `ticket/`.
3. `events/partials/*-mobile` vs `*-desktop` : deux implémentations du même formulaire et du même détail. Proposition : un seul composant `<x-event.booking-panel>` (liste de types, quantités, résumé, minuteur) rendu une fois, avec CSS responsive : sticky colonne droite ≥ 992 px, **barre CTA collante + bottom-sheet** < 992 px (même DOM). Un seul module JS `public/js/booking.js` (sans jQuery, sans `console.log`) ; supprimer le doublon d'IDs `_desktop`. Idem `event-details-*` (deux 150 l. quasi identiques).
4. `cart/show` : même contenu rendu deux fois (desktop liste + modale mobile). Proposition : une seule liste de cartes d'articles responsive (image 64 px + titre + sélecteur de quantité 44 px + prix), résumé en pied collant sur mobile ; supprimer la modale. Scripts de `cart/show:1101-1452` + `resources/js/cart-unified.js` (473 l.) à unifier.
5. `layouts/{acheteur, promoteur, admin}` : factoriser le shell (sidebar, topbar, overlay, toggle) en un seul `layouts/dashboard.blade.php` + `@include('layouts.partials.nav-{role}')` + `public/css/dashboard-shell.css` + `public/js/dashboard-shell.js`. Éliminer ~1500 lignes de CSS inline et 3 JS divergents.
6. `promoteur/reports.blade.php` ≈ `promoteur/sales/reports.blade.php` (407 l. chacun) : fusionner (en coordination avec le chantier statistiques).
7. `partials/header` : une seule navbar avec `offcanvas` Bootstrap au lieu de deux blocs mobile/desktop (`header.blade.php:5, 68`).
8. Composants Blade à créer : `x-table-responsive` (tableau → cartes < 576 px via `data-label`), `x-page-header` (titre + actions, wrap), `x-form.input` (label, erreur `aria-describedby`, `inputmode`, `autocomplete`), `x-stat-card`, `x-price` (via `CurrencyHelper`), `x-event-card`, `x-sticky-cta`.

## 6. Parcours critiques à tester en priorité (Playwright 390×844, 768, 1280)

1. Accueil → liste → fiche événement → choix quantités → réserver (bottom-sheet, minuteur).
2. Panier : lecture, modification quantité, suppression, passage au checkout (mobile : modale).
3. Checkout connecté puis invité : saisie email/tel, clavier, message d'erreur, soumission, page de confirmation + instructions paiement manuel.
4. Acheteur : dashboard → commandes → détail → billets/QR (affichage QR plein écran ?).
5. Promoteur : création d'événement (ne pas modifier, juste constater), scanner (caméra HTTPS requise, saisie manuelle), ventes.
6. Admin : événements, commandes (changement de statut), utilisateurs (tables).
7. Vérification publique d'un billet via QR sur mobile.
Pré-requis : app démarrable (`composer install`, DB MySQL/SQLite de test, seed) ; non disponible ici. Prévoir des vérifs automatiques : scroll horizontal (`document.documentElement.scrollWidth <= innerWidth`), taille des cibles (`getBoundingClientRect` ≥ 44), police inputs ≥ 16 px.

## 7. Plan d'implémentation par lots

Effort : S ≤ 0,5 j, M ≈ 1-2 j, L ≈ 3+ j. Ordre : A → (B, C, D parallèles) → E...

| Lot | Contenu | Fichiers | Effort | Parallèle ? |
|---|---|---|---|---|
| **A. Socle partagé** (séquentiel, en premier) | (1) Héberger Bootstrap/FA : `npm run build` via Vite (déjà `package.json`, `resources/js|css`) ou fichiers locaux versionnés, FA limité aux icônes utilisées (subset) ; `preconnect`, scripts `defer` ; (2) corriger `user-scalable=no` ; (3) tokens (`--tap: 44px`, `font-size:16px` inputs, `dvh`) dans `public/css/theme.css` ; (4) composants `x-page-header`, `x-form.input`, `x-table-responsive`, `x-sticky-cta`, `x-price` | `resources/views/components/*` (nouveaux), `layouts/app.blade.php`, `public/css/theme.css`, `public/css/mobile.css` (nouveau) | M | Non : touche les 5 layouts, à faire avant tout autre lot |
| **B. Parcours achat : fiche événement + panier** | Panneau de réservation unique, CTA collant, bottom-sheet unique, un seul JS sans jQuery ; panier en page unique de cartes + résumé collant ; supprimer les doublons `_desktop` | `events/show`, `events/partials/*` (→ `components/event/*`), `cart/show`, `public/js/booking.js`, `resources/js/cart-unified.js` | L | Oui avec C, D, E, G (fichiers disjoints) |
| **C. Checkout + confirmation** | Images 64 px fixes (`col-3`/`col-9`), `inputmode`/`autocomplete`/`autocapitalize`, erreurs `aria-live`, CTA collant avec total FCFA, rappel minuteur, composant commun connecté/invité (fusionner les deux vues en un `checkout/_form`) | `checkout/*`, `public/js/checkout-*.js` | M | Oui (après A) ; coordonner avec D3 (CheckoutController) |
| **D. Navigation publique + auth** | `partials/header` : navbar unique + offcanvas ; footer accordéon mobile ; déplacer requêtes du layout vers View Composer (à signaler à l'architecte) ; pages auth cibles tactiles | `partials/header`, `layouts/app` (déjà touché en A : séquentiel après A), `auth/*` | M | Après A ; disjoint de B/C |
| **E. Shell dashboards** | Fusion CSS/JS du shell acheteur/promoteur/admin en `layouts/dashboard.blade.php` + `dashboard-shell.{css,js}` ; offcanvas Bootstrap natif, burger 44 px, supprimer `acheteurii`/`auto-sidebar` | `layouts/{acheteur,promoteur,admin}.blade.php`, `public/js/layout-admin.js`, `layouts-promoteur.js` | L | Séquentiel après A ; bloque F, G, H (ces vues dépendent du layout) |
| **F. Tables admin/promoteur → cartes** | `x-table-responsive` appliqué aux 11 tables (colonnes secondaires `d-none d-md-table-cell`, cartes < 576 px) | `admin/{events,orders,users,tickets,pages}/index`, `commissions`, `admin/orders/show`, `admin/events/show`, `promoteur/events/{show,tickets/index}`, `acheteur/dashboard`, `tickets/index` | M | Oui après A ; **exclure** `promoteur/events/**` et vues stats tant que les autres chantiers tournent |
| **G. Espace acheteur** | Commandes/billets : cartes, QR plein écran, pages 390 px | `acheteur/*` | M | Oui après E |
| **H. Scanner promoteur** | Écran plein écran mobile : caméra en premier, saisie manuelle repliable, résultat en gros (vert/rouge, vibration), jsQR hébergé en local, bouton torche ; supprimer textes d'aide | `promoteur/scanner/index`, éventuellement `promoteur/scanner/stats` | M | Oui après A ; fichier disjoint des autres chantiers |
| **I. Images/perf** | `loading="lazy"`, `width/height`, `srcset`/webp via accessor modèle, chart.js épinglé et chargé seulement sur les pages stats | `welcome`, `events/all`, `components/events-carousel`, vues avec `<img>` | S-M | Oui (attention aux conflits sur les vues listées en C/B) |
| **J. Nettoyage** | Supprimer `ticket/`, `layouts/acheteurii`, `components/auto-sidebar`, `app/Components/layout-switcher.blade.php`, `events/test`, `diagnostic/qr` (après `grep` routes) ; corriger vues manquantes avec l'architecte | cf. §3 | S | Oui en dernier |
| **K. Validation** | Script Playwright (390/768/1280) : scroll horizontal, cibles ≥ 44 px, inputs ≥ 16 px, captures de tous les parcours §6 | `tests/e2e/*` (nouveau) | M | Après chaque lot |

Critères d'acceptation communs (mesurables) : aucun scroll horizontal à 390 px ; cibles interactives ≥ 44×44 px ; `font-size` des inputs ≥ 16 px (pas de zoom iOS) ; viewport sans `user-scalable=no` ; contraste ≥ 4,5:1 ; CSS+JS bloquants ≤ 1 requête hors domaine ; LCP page événement < 2,5 s en 3G rapide simulée ; aucune requête Eloquent dans les vues modifiées ; formats FCFA via `CurrencyHelper`.
Vérification : Playwright 390×844 puis 768×1024 puis 1280×800, captures avant/après dans `docs/agents/reports/captures/`, plus Lighthouse mobile si disponible.

## 8. Risques et fichiers à ne pas modifier en parallèle

- **Chantier création d'événement** : ne pas toucher `resources/views/promoteur/events/**` (create/edit/index/show/tickets/*) ni `public/js/promoteur-events-*.js` avant sa fin ; lot F/G doivent exclure ces fichiers.
- **Chantier statistiques** : ne pas toucher `promoteur/{dashboard,reports}.blade.php`, `promoteur/sales/*`, `admin/{dashboard,analytics,commissions,reports/*}.blade.php`, `public/js/promoteur-dashboard.js`, `components/commission-widget` ; les lots A/E modifient `layouts/*` (partagés) : coordonner les dates.
- **Logique dans les vues (à signaler à l'architecte)** : requêtes dans `layouts/app.blade.php:453,498,546,598` (catégories/pages du footer) ; `Event::find` dans `cart/show` (boucle) ; `acheteur/profile.blade.php:128-134`, `admin/users/edit.blade.php:276,285`, `admin/events/index.blade.php:285` (`->orders()->count()` N+1) ; `admin/reports/users.blade.php:112` (tri en vue) ; 15 `$request`/calculs inline à inventorier. → View Composer ou `withCount`.
- **JS couplé au DOM** : `BookingSystem` (IDs `#reserveBtn`, `#qty_*_desktop`), `cart-unified.js`, `public/js/admin-*.js` (URLs en dur, R2), `layouts-promoteur.js` (classes `mobile-visible`) : changer les classes du shell casse la navigation ; migrer par attributs `data-*`.
- **CDN** : tout CDN bloqué (réseau d'entreprise, coupure, tracking) casse Bootstrap/FA/jQuery/jsQR/chart.js : héberger localement (lot A). `sortablejs@latest` non épinglé = risque de rupture.
- **Vues manquantes** : plusieurs contrôleurs pointent vers des vues inexistantes (voir §3) : à traiter par l'architecte (R1) avant tout test Playwright, sinon 500.
- **Conflit de routes de test** : `diagnostic/qr` et `events/test` ne doivent pas être servies en production.
- **Limite de l'audit** : aucune exécution ; tous les « Gênant » sont à confirmer par captures. Playwright Chromium non lancé (app non démarrable ici : pas de vendor, pas de base).

## 9. Questions à trancher par l'utilisateur

1. Héberger Bootstrap/FA en local (build Vite) ou garder les CDN ? Recommandé : local, FA en sous-ensemble.
2. Panier : une vraie page panier mobile (recommandé) ou conserver la modale actuelle ?
3. Checkout : fusionner connecté et invité en un seul formulaire ?
4. Autoriser le zoom (retirer `user-scalable=no`) : recommandé oui.
5. Fusionner les 3 layouts de tableaux de bord en un seul shell (lot E, L) ou corriger séparément ?
6. Scanner : garder jsQR (JS pur) ou passer à `BarcodeDetector`/lib plus performante ? Mode hors-ligne nécessaire ?
7. Page publique de vérification : réduire à « valide/invalide + nom de l'événement » (date/lieu/type retirés) ?
8. Supprimer `diagnostic/qr`, `events/test`, `ticket/`, `acheteurii` ?
