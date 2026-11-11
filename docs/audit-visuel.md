# Audit visuel (11/11/2026) et proposition de charte

Étape 1 du chantier « charte visuelle » : constat seul, aucune vue, CSS ni contrôleur modifié. Méthode reprise de Fiberoperate (`docs/audit-interface.md`) : captures à 390, 768 et 1280 px (Chromium, facteur 1) sur une instance locale jetable (copie du dépôt, SQLite, données de démonstration : 1 admin, 1 promoteur, 1 acheteur, 10 catégories, 2 événements avec 2 types de billets, 1 commande en attente, 1 payée avec 2 billets et QR), connexion par le formulaire pour chaque rôle, mesures automatiques et relecture à l'œil.

## Périmètre et limites
- 23 pages x 3 largeurs = 69 mesures : accueil, liste et fiche d'événement, panier, checkout, confirmation, connexion, vérification publique d'un billet, espace acheteur (tableau de bord, commandes, détail, billets, billet), promoteur (tableau de bord, liste, création d'événement, création de billets, scanner), admin (tableau de bord, commandes, catégories, rapports). Aucune page n'a planté (HTTP 200 partout, pas de redirection inattendue).
- **CDN bloqués** dans le bac à sable : Bootstrap 5.3.0 et Font Awesome 6.4.0 ont été servis depuis des copies locales identiques (npm) pour que le rendu soit fidèle. Restent non chargés : jQuery (`events/show.blade.php:176`) et Chart.js (`admin/dashboard.blade.php:321`) : erreurs JS « $ is not defined » / « Chart is not defined » et graphique vide, **artefacts du bac à sable, pas des défauts constatés** (mais ils montrent la dépendance au réseau, voir D9).
- Police : les mesures utilisent la police de repli de la machine (Segoe UI absente) ; le rendu réel varie selon l'appareil.
- Non couvert : checkout invité (`/checkout/guest`), pages CMS/FAQ/contact/CGU, édition d'événement, listes admin utilisateurs/événements, états d'erreur de validation, états vides, minuteur de réservation en cours de décompte.
- Les comptes « zones tactiles < 44 px » incluent les liens du tiroir latéral (hors écran à 390 px) : chiffres un peu gonflés sur promoteur/admin ; l'ordre de grandeur reste valable.
- Captures clés : `docs/audit-visuel/*.jpg` (390 px).

## Constats chiffrés (390 / 768 / 1280 px)
Légende : H = défilement horizontal en px ; T = éléments tactiles < 44 px sur le total ; C = textes sous le contraste AA (4,5:1 ; 3:1 pour le grand texte) sur les textes mesurés ; L = champs sans libellé associé.

| Page | H (390/768/1280) | T (390) | C (390) | L |
|---|---|---|---|---|
| Accueil | 0/0/0 | 21/51 | 11/48 | 0 |
| Liste d'événements | 0/0/0 | 31/59 | 15/52 | 4 |
| Fiche événement | 0/0/0 | 28/62 | 20/72 | 0 |
| Panier | 0/0/0 | 22/60 | 17/50 | 0 |
| Checkout | 0/0/0 | 26/55 | 9/66 | 0 |
| Confirmation | 0/0/0 | 18/48 | 2/34 | 0 |
| Connexion | 0/0/0 | 3/6 | 4/9 | 0 |
| Vérification billet (publique) | 0/0/0 | 20/44 | 4/36 | 0 |
| Acheteur : tableau de bord | 2/0/0 | 5/29 | 3/53 | 0 |
| Acheteur : commandes | 2/0/0 | 15/37 | 8/60 | 1 |
| Acheteur : détail commande | 2/0/0 | 6/30 | 4/63 | 0 |
| Acheteur : billets | 0/0/0 | 5/30 | 7/36 | 2 |
| Acheteur : billet | 2/0/0 | 2/26 | 3/38 | 0 |
| Promoteur : tableau de bord | 2/0/0 | 20/33 | 13/64 | 0 |
| Promoteur : événements | 0/0/0 | 27/36 | 13/63 | 3 |
| Promoteur : création événement | **215**/0/0 | 23/38 | 10/61 | **10** |
| Promoteur : création billets | 0/0/0 | 20/39 | 4/65 | 0 |
| Promoteur : scanner | 2/0/0 | 18/30 | 4/53 | 0 |
| Admin : tableau de bord | 0/0/0 | 37/52 | 9/90 | 0 |
| Admin : commandes | 0/0/0 | 43/60 | 4/85 | **8** |
| Admin : catégories | 0/0/0 | 57/73 | 2/75 | 0 |
| Admin : rapports | 0/0/0 | 28/46 | 2/57 | 0 |

Points positifs : taille de police des champs >= 16 px partout à 390 et 768 px (0 écart) ; une page d'authentification propre ; badges portant du texte ; aucune page ne casse.

Autres mesures : texte de moins de 12 px présent sur toutes les pages (jusqu'à 15 éléments sur le panier, 7 sur le tableau de bord acheteur) ; 0 image sans `alt` ; aucune barre de navigation basse mobile sur aucun des 4 layouts ; 0 règle `prefers-reduced-motion` dans tout `resources/views` et `public/css` ; 965 couleurs hexadécimales en dur dans les vues et CSS (`#ff6b35` : 184 fois), 307 attributs `style=""`, 63 vues avec un bloc `<style>` ; durées de transition : 74 x 0,3 s, 3 x 0,4 s (limite 200 ms dépassée), 37 x 0,2 s.

## Les 10 défauts les plus gênants
1. **Contraste AA non tenu sur la couleur de marque.** `#FF6B35` sur blanc = 2,84:1 : boutons (« Passer commande », « Valider la commande », « Se connecter »), badges, chiffres et liens du parcours d'achat. Texte blanc sur dégradé orange du bandeau « Mon Panier » mesuré à 1:1 par l'outil (dégradé, à confirmer à l'oeil : lisible mais faible). Touche toutes les pages.
2. **Aucun minuteur de réservation visible** dans le panier ni le checkout (aucune occurrence de timer/countdown dans `resources/views/cart` et `checkout`), alors que le service pose `expires_at` (`CheckoutService.php:310`). Le texte du checkout annonce « stock réservé pendant 48 h » sans durée réelle ni compte à rebours : à rapprocher de la contradiction CGU (BOARD, REV2-PM I-C).
3. **Panier mobile cassé visuellement.** `/cart` à 390 px : ~500 px de vide sous l'en-tête, le contenu apparaît en « feuille » basse avec le bouton flottant « Mon Panier (2) 50,000 FCFA » et le pied de page sombre (capture `panier-390.jpg`). Le parcours principal commence par un écran qui semble vide.
4. **Cibles tactiles trop petites** : 40 % et plus des éléments interactifs < 44 px sur panier (boutons +/- de 30 x 30 px, suppression 15 x 24 px), accueil, liste, espace promoteur (23/38) ; admin catégories 57/73 (en partie le tiroir). Les pieds de page et fils d'Ariane sont à ~26 px de haut.
5. **Formulaire de création d'événement inutilisable au téléphone** : 215 px de défilement horizontal à 390 px (assistant en étapes et champs rognés, `pro-creation-evenement-390.jpg`) ; 10 champs sans libellé programmatique.
6. **Quatre layouts, quatre identités** (détail plus bas) : l'acheteur passe de la boutique orange (`app`) à un espace bleu/orange (`acheteur`) puis le promoteur et l'admin sont encore autres ; deux fois le même mot « Déconnexion » avec trois styles de bouton. Un cinquième layout, `acheteurii`, n'est étendu par aucune vue (mort).
7. **Aucune navigation basse mobile** : menu hamburger partout, la tâche principale (acheter, voir mes billets, scanner) est à deux touches ; sur le scanner promoteur le titre passe à la ligne et le bouton « Statistiques » flotte à côté.
8. **Formats de montants incohérents** : « 10,000 FCFA » (séparateur anglais) sur boutique et acheteur, « 10 000 FCFA » sur admin/rapports, « 0 F » avec symbole euro sur le tableau de bord promoteur ; 160 `number_format` dans les vues pour seulement 21 usages de `CurrencyHelper`. Dates en `01/12/2026` partout (cohérent) mais heures sans format commun.
9. **Dépendance réseau et poids** : Bootstrap, Font Awesome (versions 6.0.0 et 6.4.0 selon le layout), jQuery et Chart.js chargés depuis des CDN (pas de repli) ; layouts de 700 à 1190 lignes avec CSS embarqué non mis en cache ; un échec CDN rend les pages sans style (constaté en bac à sable) ou sans JS. Mauvais pour la faible bande passante.
10. **Accessibilité des formulaires et messages** : champs sans libellé (promoteur création 10, admin commandes 8, liste d'événements 4), libellés en placeholder sur la connexion (libellé flottant lisible mais pas au-dessus du champ), pas de `prefers-reduced-motion`, textes de 10 à 11 px (badges, étapes du panier « Informations »), couleur seule pour certains états (cartes colorées du tableau de bord acheteur : bleu, vert, cyan, jaune, sans lien avec le sens).

Hors visuel, signalé à l'architecte : le tableau de bord promoteur affiche « 0 événement créé » alors que 2 événements existent pour ce promoteur et l'infobulle « +2 ce mois » (chiffres incohérents ; supposé, non investigué) ; `EventSeeder` cherche le rôle `promoter` et d'autres e-mails que `UserSeeder`, donc ne crée aucun événement (`php artisan db:seed` donne 0 événement et 0 type de billet).

## Page publique de vérification de billet
Conforme à la règle : validité, nom de l'événement, date, lieu et type de billet ; aucun nom, e-mail ni numéro de commande. Défauts visuels : titre sans `h1` (0 `h1` sur la page), double icône de validation (pastille et emoji), bandeau bleu qui ne reprend pas la charte orange.

## Incohérences entre les 4 layouts
| | `app` (public, panier, checkout) | `acheteur` | `promoteur` | `admin` |
|---|---|---|---|---|
| Police | Segoe UI, Tahoma... | -apple-system, Segoe UI, Roboto | idem acheteur | idem acheteur |
| Couleur principale | orange `#FF6B35` | bleu `#2563eb` + orange | orange `#FF6B35`, noir `#1d2327` | orange, noir, bleu Bootstrap `#0d6efd` pour les liens |
| Boutons | orange, rayon 6 px, pilule (créer un compte) | bleu plein, rayon 4-8 px | noir (« Créer un événement »), orange, rayon 10 px | gris Bootstrap par défaut, rayon 0 |
| Badges d'état | `badge bg-success/secondary/orange`, orange clair | `bg-warning`, `bg-success`, cartes entières colorées | `badge bg-success`, `status-active` | `bg-light text-dark`, `bg-warning`, `bg-success` |
| Navigation mobile | hamburger + liste de liens développée en bandeau, pas de barre basse | hamburger | hamburger + tiroir | hamburger + tiroir + cloche |
| Titres | `h1` présent, grande taille | pas de `h1` sur 4 pages mesurées sur 5 | `h1` absent sur le tableau de bord | `h1` absent sur les commandes |
| Font Awesome | 6.4.0 | 6.0.0 | 6.0.0 | 6.0.0 |
| Espacements | marges 12 px, cartes arrondies 16 px, ombres douces | cartes à bord vif, 8 px | cartes à liseré orange | cartes plates à bord fin |
| Animations | 0,3 s (4x), 0,2 s | variables `--transition-smooth` (0,3 s) | 0,2 s | 0,2 s et 0,3 s |

Les états d'une commande existent déjà en composant (`resources/views/components/order-status-badge.blade.php`) mais les vues ne l'utilisent pas toutes (badges Bootstrap écrits à la main).

## Proposition de charte
À valider par le propriétaire avant toute implémentation ; la couleur de marque reste orange, ajustée pour l'accessibilité.

**Jetons** (variables CSS dans un seul fichier `public/css/theme.css`, déjà présent et sous-utilisé) :
| Jeton | Valeur proposée | Usage |
|---|---|---|
| `--brand` | `#C2410C` (orange foncé, 5,2:1 sur blanc) | boutons pleins, liens, texte de marque |
| `--brand-hover` | `#9A3412` | survol, appui |
| `--brand-soft` | `#FFEDD5` | fonds d'accent, badge d'attente léger |
| `--signal` | `#FF6B35` (la couleur actuelle) | repère décoratif uniquement (icônes, filets, jamais du texte) |
| `--canvas` | `#FAFAF9` | fond des pages |
| `--surface` | `#FFFFFF` | cartes |
| `--ink` | `#1C1917` | texte (16:1) ; `--ink-muted` `#57534E` (7,5:1) |
| `--line` | `#E7E5E4` | bordures |
| `--success` / `--success-soft` | `#15803D` / `#DCFCE7` | payé, valide |
| `--warning` / `--warning-soft` | `#B45309` / `#FEF3C7` | en attente (texte foncé, icône horloge) |
| `--danger` / `--danger-soft` | `#B91C1C` / `#FEE2E2` | échec, annulé, expiré |
| `--info` / `--info-soft` | `#1D4ED8` / `#DBEAFE` | information neutre |

**Police** : Inter (variable) embarquée en WOFF2 dans `public/fonts` (sous-ensemble latin, ~50 Ko), repli `system-ui` ; pas de Google Fonts. Corps 16 px, texte secondaire 14 px minimum (aucun texte sous 12 px), titres en 600.
**Rayon** : 8 px (champs, boutons), 12 px (cartes), pilule (badges). Un seul jeu de valeurs.
**Ombres** : deux niveaux seulement : `--shadow-sm` (carte au repos) et `--shadow-md` (feuille, menu), sans couleur de marque.
**Mouvement** : 150 ms maximum, `@media (prefers-reduced-motion: reduce)` qui coupe transitions et défilement animé.

**Composants communs** (composants Blade dans `resources/views/components`, un seul gabarit par besoin) :
- `x-button` : plein (`--brand`), secondaire (bordure), danger ; hauteur 44 px minimum, anneau de focus 3 px visible ; état « chargement » pour éviter le double envoi.
- `x-status-badge` : généralise `order-status-badge` ; texte + icône + couleur (jamais la couleur seule) pour : en attente, payée, échouée, annulée, expirée, valide, utilisée.
- `x-card` : fond `--surface`, bordure `--line`, rayon 12 px, en-tête optionnel.
- `x-field` : libellé visible au-dessus, champ 44 px, 16 px, aide et erreur liées par `aria-describedby`, `aria-invalid`, message d'erreur annoncé (`role="alert"`).
- `x-money` (ou `CurrencyHelper::formatFCFA` partout) : « 10 000 FCFA » avec espace insécable, un seul format ; `x-reservation-timer` : minuteur de réservation (voir étape 3).
- `x-bottom-nav` : barre basse mobile (acheteur : Événements, Panier, Mes billets, Compte ; promoteur : Événements, Scanner, Ventes, Compte) ; admin garde le tiroir.

## Plan en étapes (une PR par étape, ordre conseillé)
1. **Jetons et police** : `theme.css` unique chargé par les 4 layouts, Inter embarquée, `prefers-reduced-motion`, remplacement de `#FF6B35` utilisé comme texte ou fond de bouton par `--brand` ; fusion des versions Font Awesome ; suppression du layout `acheteurii` mort (après Grep). Gain immédiat sur le défaut 1.
2. **Composants de base** : `x-button`, `x-card`, `x-field`, `x-status-badge`, `x-money`, avec un test de rendu ; remplacer les montants `number_format` par le composant (défaut 8).
3. **Parcours d'achat mobile** (panier, checkout, confirmation) : réparer le panier (défaut 3), minuteur de réservation lisible (défaut 2, avec l'architecte pour la durée réelle et le texte « 48 h »), cibles 44 px, une seule barre d'étapes, page invité alignée. Vérification Playwright à 390 px.
4. **Coque commune et navigation** : `x-bottom-nav`, en-tête compact, un gabarit de base partagé par les 4 layouts (extraction du CSS embarqué en fichier mis en cache), `h1` partout.
5. **Espaces acheteur, promoteur, admin** : tableaux de bord sans couleur seule, formulaires en `x-field` (création d'événement sans défilement horizontal, défaut 5), tableaux admin responsives, scanner.
6. **Faible bande passante et robustesse** : Bootstrap/Font Awesome/jQuery/Chart.js servis localement ou remplacés (icônes en SVG de sprite, graphiques chargés à la demande), images optimisées (WebP, `loading="lazy"`), vérification de la page publique de billet (`h1`, un seul pictogramme).
7. **Contrôle final** : relancer ce même script de mesure (0 débordement, 0 contraste sous AA, cibles >= 44 px) et noter le résultat dans ce document.

Règles à tenir à chaque étape : mobile d'abord (360 px puis `md`), jetons uniquement (pas de couleur en dur), libellé visible au-dessus des champs, jamais l'information par la couleur seule, animations <= 200 ms coupées sous `prefers-reduced-motion`, pas de logique métier dans les vues (extraire en View Composer et le signaler à l'architecte).
