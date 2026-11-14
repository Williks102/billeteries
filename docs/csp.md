# CSP stricte : plan en 4 lots

## Objectif
Supprimer `'unsafe-inline'` des scripts (puis des styles) de la politique de sécurité du contenu (CSP), pour que du JavaScript injecté dans une page (XSS) ne puisse plus s'exécuter. La politique appliquée aujourd'hui (`app/Http/Middleware/SecurityHeaders.php`) reste permissive : elle autorise les scripts et styles inline et deux CDN. On avance sans rien casser : on mesure d'abord, on corrige ensuite, on active en dernier.

## Les 4 lots
- **A (fait)** : nonce par requête, politique stricte en mode « rapport seulement », collecte des rapports, écran admin.
- **B** : sortir le JavaScript inline vers des fichiers externes : surtout les ~91 attributs `onclick`/`onsubmit`/etc. (30 vues) et les blocs `<script>` (24).
- **C** : héberger jQuery et Chart.js localement (plus de CDN pour les scripts), puis activer `script-src` strict (nonce) en remplaçant la politique appliquée.
- **D** : les styles en dernier (~47 vues avec `<style>`, attributs `style=`), puis retirer `'unsafe-inline'` de `style-src`.

## Ce que le lot A fait
- Un **nonce** aléatoire (18 octets cryptographiques, base64) est généré par requête, au début du middleware `SecurityHeaders`, conservé dans l'attribut de requête `csp_nonce` (ni session, ni cache). Aide `csp_nonce()` et directive Blade `@cspNonce` (produit `nonce="…"`). Hors requête HTTP (console, vue isolée) : chaîne vide, aucune erreur.
- Les 24 `<script>` inline des vues portent `@cspNonce` (contenu inchangé). Garde-fou : `CspReportOnlyTest::test_every_inline_script_in_views_has_a_nonce` échoue si un script inline sans nonce réapparaît (e-mails exclus).
- L'en-tête **`Content-Security-Policy-Report-Only`** envoie la politique cible : `script-src 'self' 'nonce-…'` + CDN (jusqu'au lot C), `script-src-attr 'none'`, `style-src` avec `'unsafe-inline'` (jusqu'au lot D), mêmes `img-src` (origine du disque média comprise), `font-src`, `connect-src`, `report-uri /csp-report`. Elle ne bloque RIEN et coexiste avec la politique appliquée, qui n'a pas changé d'un caractère (testé).
- `POST /csp-report` (route `csp.report`) collecte les rapports. Sans authentification, sans CSRF (exception unique, commentée dans `bootstrap/app.php`), sans session ni cookie, 30 requêtes/min par IP puis HTTP 429, plafond global de 120 écritures/min, corps limité à 16 Ko (au-delà : ignoré), formats `application/csp-report` et `application/reports+json`, **204 dans tous les cas** (même table absente ou JSON invalide ; le 429 du débit est le seul autre code).
- Table `csp_violations` : une ligne par (directive, ressource bloquée, page) avec compteur, première et dernière date ; plafond de 500 lignes (au-delà on n'incrémente que l'existant).
- Écran admin **Paramètres > Rapports CSP** (`/admin/csp`, lecture seule).

## Ce que le lot A ne fait pas
- Il ne bloque rien et ne change aucun comportement visible. Il ne corrige aucun `onclick`, aucun `<style>`, ne retire aucun CDN.
- Les scripts avec `src` (Bootstrap, jQuery, Chart.js depuis un CDN) n'ont pas de nonce : ils sont admis par l'hôte listé.
- Le navigateur n'envoie un rapport que s'il prend en charge `report-uri` (tous les principaux) ; les extensions de navigateur génèrent du bruit (hôtes `chrome-extension`, scripts injectés) : à ignorer.

## Lire l'écran « Rapports CSP »
Chaque ligne = un point à corriger. Colonnes : directive violée, ressource bloquée, page, nombre de signalements, dernier signalement. Tri : du plus fréquent au moins fréquent.
- `script-src-attr` + `inline` : un attribut `onclick`/`onsubmit`… (lot B). La page est un modèle de route, par exemple `/events/{event}` : on sait quelle vue corriger, pas quel visiteur.
- `script-src-elem` + `inline` : un `<script>` sans nonce (ne devrait pas arriver : le garde-fou l'empêche ; sinon script injecté par une extension, ou page d'erreur).
- `script-src-elem` + hôte : script externe non listé (à héberger ou à autoriser en connaissance de cause).
- `style-src-*` : n'apparaît pas tant que `'unsafe-inline'` est dans `style-src` (lot D).
- Une page `/(inconnue)` = adresse ne correspondant à aucune route.

## Critères pour passer au lot suivant
- **Vers B** : aucun. Le lot B se pilote avec l'écran : chaque correction doit faire baisser les lignes `script-src-attr`.
- **Vers C** : plus aucune ligne `script-src-attr` ni `script-src-elem` + `inline` pendant au moins 7 jours d'usage normal (parcours public, acheteur, promoteur avec scanner, admin).
- **Vers l'activation stricte** (remplacer `script-src` appliqué par la version avec nonce, sans `'unsafe-inline'` ni CDN) : jQuery et Chart.js hébergés localement, 0 ligne de script pendant 7 jours, y compris en production sur une vraie base. Passer d'abord la politique stricte en `Content-Security-Policy` sur un environnement de test, parcourir les pages, puis seulement la production. Les styles (lot D) suivent la même démarche.

## Désactiver le mode rapport
`CSP_REPORT_ONLY=false` (variable d'environnement ; déclarée dans `.env.example` et `render.yaml`, défaut `true`) retire l'en-tête `Content-Security-Policy-Report-Only`. La politique appliquée n'est pas touchée. Avec `config:cache`, redéployer ou relancer `php artisan config:cache` après le changement.

## Ce qu'on ne collecte JAMAIS
Adresse IP, user-agent, en-têtes, referrer, cookies, échantillon de script (`script-sample`), fichier source complet, query string, fragment, valeurs de paramètres de l'adresse (un code de billet ou un jeton dans l'URL n'est jamais conservé : seul le modèle de route l'est, ex. `/verify-ticket/{ticketCode}`), ressource bloquée au-delà de son hôte. Seuls sont stockés : directive, hôte (ou inline/eval/data/blob), modèle de route, compteur, dates.

## Limites connues
- La clé d'agrégation inclut la page : une même cause sur 30 pages donne 30 lignes (plafond 500 ; au-delà, nouvelles clés ignorées). Si la table est pleine, la vider (`DELETE FROM csp_violations`) après avoir corrigé les premières causes.
- Le plafond de lignes est vérifié sans verrou : une rafale simultanée peut le dépasser de quelques lignes.
- Un rapport est écrit de façon synchrone (une requête SQL) ; le débit limité borne la charge.
