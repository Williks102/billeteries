# CSP stricte : plan en 4 lots

## Objectif
Supprimer `'unsafe-inline'` des scripts (puis des styles) de la politique de sécurité du contenu (CSP), pour que du JavaScript injecté dans une page (XSS) ne puisse plus s'exécuter. La politique appliquée aujourd'hui (`app/Http/Middleware/SecurityHeaders.php`) reste permissive : elle autorise les scripts et styles inline et deux CDN. On avance sans rien casser : on mesure d'abord, on corrige ensuite, on active en dernier.

## Les 4 lots
- **A (fait)** : nonce par requête, politique stricte en mode « rapport seulement », collecte des rapports, écran admin.
- **B (fait)** : plus aucun attribut d'événement inline (`onclick`, `onsubmit`, `onchange`, `onerror`...) dans les vues : 89 convertis en `data-*` + délégation d'événements (`public/js/csp-actions.js`). Les blocs `<script>` (24) restent inline avec leur nonce.
- **C** : héberger jQuery et Chart.js localement (plus de CDN pour les scripts), puis activer `script-src` strict (nonce) en remplaçant la politique appliquée.
- **D** : les styles en dernier (~47 vues avec `<style>`, attributs `style=`), puis retirer `'unsafe-inline'` de `style-src`.

## Lot B : écrire un gestionnaire d'événement (règle : plus JAMAIS d'attribut inline)
Interdit dans `resources/views` (hors e-mails) : `onclick="..."`, `onchange`, `onsubmit`, `onerror`, tout `on*=` et `href="javascript:..."`. Le test `CspNoInlineHandlersTest` échoue sinon. Tous les layouts (`app`, `acheteur`, `promoteur`, `admin`) chargent `public/js/csp-actions.js` (`defer`) : délégation d'événements au niveau du document, sans `eval`, sans `new Function`, sans appel de `window[nom]` : seuls les noms enregistrés sont appelables.

Attributs `data-*` pris en charge :
- `data-confirm="Message"` sur un bouton, un lien ou un `<form>` : `confirm(message)` au clic (ou à la soumission) ; refus = action annulée (écouteur en phase de capture, les autres écouteurs ne s'exécutent pas non plus).
- `data-on-click="nom"`, `data-on-change="nom"`, `data-on-submit="nom"`, `data-on-input="nom"` : appelle le gestionnaire enregistré `nom(element, event)`. Nom inconnu : ignoré, avec `console.warn`. Les arguments se lisent dans `element.dataset` (ex. `data-order-id="12"` -> `el.dataset.orderId`), jamais dans du JSON évalué. L'action par défaut n'est PAS annulée (liens et formulaires normaux intacts) : appeler `e.preventDefault()` dans le gestionnaire si besoin.
- `data-img-fallback="hide"` (masque l'image en erreur) ou `"placeholder"` (la remplace par un `<span>` de même classe). Écouteur `error` en phase de capture, plus un contrôle des images déjà en échec au chargement du DOM.

Actions intégrées (à utiliser avec `data-on-click`/`data-on-change`) : `history-back` (retour), `print` (impression), `reload` (recharger), `prevent` (annule l'action par défaut), `submit-form` (soumet le formulaire parent, ou celui de `data-form="id"` ; typique : `data-on-change="submit-form"` sur un `<select>`), `submit-form-prevent` (idem après `preventDefault`, pour un lien de déconnexion : `data-form="logout-form"`).

Enregistrer un gestionnaire de page (les scripts de page s'exécutent avant le fichier en `defer`, d'où `DOMContentLoaded`) :
```js
document.addEventListener('DOMContentLoaded', function () {
    CspActions.register('order-refund', function (el, event) { refundOrder(el.dataset.orderId); });
});
```
Le bloc `<script>` de page garde son `@cspNonce`. Les noms utilisés dans les vues doivent exister dans un registre (test : aucune action orpheline). Ne jamais construire du HTML contenant `onclick=` dans du JavaScript (les gabarits de chaînes comptent).

Jauge restante : script-src-attr -> 0 attribut inline dans le code (mesure statique) ; il restera à vérifier l'écran « Rapports CSP » en usage réel (les 9 pages concernées doivent cesser de signaler `script-src-attr`). Reste pour l'activation stricte : lot C (jQuery, Chart.js, Bootstrap, jsQR, SortableJS hébergés localement) puis lot D (styles). Le bip du scanner (URI `data:` lue par un `Audio`) est couvert par `media-src 'self' data:`, ajouté à la politique de rapport seulement ; la politique appliquée n'a pas changé.

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
