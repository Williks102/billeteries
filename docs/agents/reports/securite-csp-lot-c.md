# Audit sécurité - CSP lot C (branche claude/csp-lot-c)

Verdict : VALIDÉ, aucun constat Critique/Élevé/Moyen.

1. Intégrité vendor : jquery 3.6.0, Sortable 1.15.6, jsQR 1.4.0 identiques octet à octet aux paquets npm ; bootstrap 5.3.0 bundle.min et chart.js 4.4.7 umd identiques sauf la ligne `sourceMappingURL` retirée (admis).
2. Scripts : 24 balises `<script>` inline dans resources/views, 24 avec `@cspNonce`. Aucun `on*=`, `javascript:`, `eval`, `new Function`, `setTimeout(chaîne)` dans public/js ni les vues (public/js/csp-actions.js l'atteste). Plus aucun `<script src>` externe.
3. Pages d'erreur : pas de resources/views/errors (vues Laravel par défaut, sans script). Le middleware est global (bootstrap/app.php:37), donc les réponses d'erreur reçoivent aussi l'en-tête.
4. Nonce : `Csp::generateNonce()` (random_bytes(18)) à chaque requête (SecurityHeaders.php:15-16), stocké dans les attributs de la requête, lu par `@cspNonce` ; en-tête et HTML partagent la même valeur. Tests Csp : 267 assertions OK.
5. Politique : seul changement hors script-src = retrait de cdn.jsdelivr.net de connect-src (Csp.php:17). style-src, font-src, img-src inchangés.

Observations Faibles (non bloquantes)
- F1 : style-src garde 'unsafe-inline' + CDN (prévu lot D). Bootstrap CSS reste chargé depuis cdn.jsdelivr.net sans SRI (layouts/*.blade.php:11-14, auth/*.blade.php) : risque supply-chain côté styles. Correctif : héberger le CSS ou ajouter integrity ; agent exécutant : frontend.
- F2 : la politique Report-Only duplique désormais l'appliquée ; sans valeur, à retirer au lot D. Ne jamais mettre en cache une page HTML (nonce lié à la requête).
