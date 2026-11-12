# Audit et correction des barres de navigation (4 espaces)

Méthode : instance jetable (SQLite, jeu minimal admin / promoteur + 1 événement / acheteur + 1 commande), Chromium via Playwright, Bootstrap 5.3.0 et Font Awesome servis en local par interception, `deviceScaleFactor` 1, mesures `getBoundingClientRect` à 390, 768, 1024 et 1280 px sur 3 à 4 pages par espace (dashboard, liste, formulaire). Script de mesure hors dépôt. Captures : `docs/audit-visuel/navbars-admin-{390,1280}-{avant,apres}.jpg` (page `/admin/users`, la barre avec icône d'accueil + titre).

Gouttière de la charte : 16 px < 768 px, 24 px dès 768 px. Hauteur de barre cible : 60 px.

## Décalages mesurés, avant / après

| Layout | Largeur | Décalage mesuré avant | Après | Correctif |
|---|---|---|---|---|
| admin | 390 / 768 | Bouton de menu et icône d'accueil EMPILÉS : `.topbar-left` n'était pas flex ; hamburger top -3,7 px (déborde de la barre), icône d'accueil centre y 51,9 px (barre = 60 px) et bas à 62,7 px, soit sous le bord de la barre | un seul alignement : tous les centres à 29,5 px, barre 60 px | `.topbar-left` flex, jetons de la charte |
| admin | 390 → 1280 | Icône d'accueil (16 x 22 px) et titre actif collés : 8 px d'écart, aucun séparateur, même couleur, 18,7 px de haut chacun ; les `<li>` du titre étaient hors `<ol>` | icône 44 x 44 px (couleur de marque), séparateur « / », titre en gras couleur `--ink` avec ellipsis, aucun chevauchement mesuré | `<ol class="breadcrumb">`, `.topbar-home`, `aria-current="page"` sur les 33 vues qui alimentent le fil |
| admin | 390 → 1280 | Gouttière de la barre 20 px contre 16 / 24 px pour le contenu (écart 4 px) | 16 / 24 px, identique au contenu | `padding: 0 var(--gutter)` |
| admin | 390 → 1280 | Chevron de réduction du menu positionné par rapport au menu (top 50 %) : au milieu de la liste à y ≈ 400, sur la ligne « Paramètres » ; menu réduit : bouton masqué, impossible de redéployer | dans l'en-tête du menu (y 30 px), visible au survol du menu réduit | `.sidebar-header` positionné, `.sidebar-toggle` en flux |
| admin | 1280 | Bouton « Voir le site » : texte à x 60 px contre 52 px pour les autres entrées (padding 16 px propre) ; sous-menus décalés (x 52 à 56 px) | texte à x 52 px partout, sous-menus alignés sur le libellé | entrée au style commun, `--sidebar-pad-x` |
| admin | 390 / 768 | Cloche 15 x 26 px (`div`, non focusable), avatar 32 px (`div`), hamburger 33 x 45 px | cloche et avatar en `button` 44 x 44 px, avatar 36 px, badge 18 px | `.topbar-icon-btn`, `.topbar-avatar-btn`, `--avatar-size`, `--badge-size` |
| admin | 390 / 768 | Tiroir : défilement de la page non bloqué (`overflow` visible), Échap sans effet, fond z 999 = barre 999 | `overflow: hidden` pendant l'ouverture, Échap ferme, fond 1040 > barre 1030, menu 1045 ; `aria-expanded` et libellé « Ouvrir / Fermer le menu » | `setDrawer()` dans `layout-admin.js`, jetons `--z-*` |
| admin | 1024 | Menu 60 px mais textes visibles avant l'exécution du JS (classe `mini` ajoutée plus tard) | masqués par CSS dans la plage 769-1024 px | règle partagée charte |
| promoteur | 390 | Gouttière de la barre 10 px (15 px à 768, 20 px au-delà) contre 16 / 24 | 16 / 24 | gouttière commune |
| promoteur | 390 / 768 | Hamburger 33 x 45 px, cloche 30 x 40 px (masquée sous 480 px), avatar 32 px, entrées `div onclick` non focusables | 44 x 44 px, cloche visible, avatar 36 px, `button` nommés | mêmes composants que l'admin |
| promoteur | 1280 | Bouton de réduction du menu sans effet (aucune règle `.mini` en CSS) | réduit réellement : menu 60 px, contenu et barre décalés de 60 px | règles `.mini ~ .promoteur-main` |
| promoteur | 390 → 1280 | Fil d'Ariane : icône + chevron + texte sans séparation ni mise en avant | même fil que l'admin (icône 44 px, « / », titre en gras) | `<ol class="breadcrumb">` |
| acheteur | 1280 | Quand le menu est réduit, la barre restait à `left: 280px` (sélecteur `+` jamais atteint) alors que le contenu passait à 60 px : décalage 220 px | barre et contenu à 60 px | `.mini ~ .acheteur-main .acheteur-topbar` |
| acheteur | 390 / 768 | Hamburger 30 x 40 px, lien « Dashboard » 74 x 17 px, avatar 40 px, avatar masqué sur mobile ; fond z 999 = barre 999 (la barre passait au-dessus du fond) | 44 x 44 px, lien 44 px de haut, avatar 36 px visible, fond 1040 > barre 1030 | charte, `.topbar-left` |
| acheteur | 390 → 1280 | Gouttière de la barre 20 px (contenu 16 / 24) ; couleur active bleue (breadcrumb, survol) contre orange ailleurs | 16 / 24 ; actif = fond + liseré orange, texte blanc | états de menu partagés |
| public | 1024 | Barre sur 2 lignes (111 px), défilement horizontal de 79 px : `.search-container { min-width: 350px }` | une ligne, 60 px, aucun défilement horizontal | recherche `flex: 1 1 200px; max-width: 350px` |
| public | 1280 | Centres verticaux 34,5 px (logo, liens) contre 28,8 px (panier, connexion) : 5,7 px de décalage ; hauteur 69 px | tous à 30 px, barre 60 px | `.nav-item` flex centré, bouton « Créer un compte » sans retour à la ligne |
| public | 390 | Logo centré en absolu (128 à 262 px) touchant le panier (x 262) ; hauteur 62 px ; cibles 34 x 40, 32 x 40, 30 x 40 px | logo centré dans l'espace libre, sans chevauchement (0 mesuré) ; 60 px ; cibles 44 x 44 px | `.navbar-brand` en flux, `.btn-link` 44 px |
| public | 1024 / 1280 | Liens du menu 42 px de haut | 44 px | `.nav-link` `min-height: var(--touch-min)` |

Mesures inchangées et correctes : aucun défilement horizontal aux autres largeurs, contenu jamais recouvert par la barre (premier élément à 76 px et plus pour une barre de 60 px), menu admin/promoteur 280 px = marge du contenu 280 px (60 / 60 en mode réduit).

## Corrections

- `public/css/charte.css` : nouvelle section « Barres de navigation » : jetons `--topbar-height`, `--sidebar-width`, `--sidebar-mini-width`, `--sidebar-pad-x`, `--avatar-size`, `--badge-size`, `--z-topbar/overlay/drawer`, `--nav-hover-bg`, `--nav-active-bg` ; barre, fil d'Ariane, boutons, avatar, badge, états actif / survol / focus du menu, tiroir mobile, mode réduit. Mouvement via `--motion` (coupé par `prefers-reduced-motion`, déjà dans la charte).
- Layouts `admin`, `promoteur`, `acheteur` : ne gardent que leurs règles propres (la quasi-totalité du CSS de barre / menu dupliqué est supprimée) ; `partials/header` (public) : barre unifiée.
- `layout-admin.js` (Échap, blocage du défilement, `aria-expanded`), `layouts-promoteur.js` (`aria-expanded`), script du layout acheteur (Échap, `aria-expanded`).
- 33 vues : `aria-current="page"` sur l'élément actif du fil d'Ariane (attribut seul, libellés inchangés).
- `tests/Feature/NavbarLayoutTest.php` (14 tests, ajouté à `test:routes`) : jetons, pas de valeur recopiée, gouttière, `prefers-reduced-motion`, bouton de menu nommé et relié, séparation icône / titre (cible 44 px, séparateur, ellipsis, gras), `aria-current`, rendu 200 des 4 layouts.

## Non vérifié / à signaler

- Pas de test sur appareil tactile réel ni Safari iOS ; Chromium seul. Bootstrap et Font Awesome ont été servis en local (versions 5.3.0 et 6.4.0, identiques aux CDN des layouts).
- Mode réduit au survol (`.mini:hover`) mesuré à la lecture du CSS, pas au clavier.
- Le fil d'Ariane du promoteur affiche « Dashboard » sur toutes les pages (aucune vue promoteur n'alimente `@section('breadcrumb')`) ; la page dashboard admin n'a pas de titre dans la barre. Libellés non modifiés ; à décider côté produit.
- `layouts/acheteurii.blade.php` n'est référencé nulle part (mort) : non touché.
- Boutons « cloche » et menu utilisateur du promoteur : `onclick` qui ne font que `console.log` (existant) ; le menu de compte promoteur n'a donc pas de contenu. Hors périmètre (logique), à signaler à l'architecte.
- Débogueur (barre de debug Laravel) masqué pour les captures (`DEBUGBAR_ENABLED=false` en local).
