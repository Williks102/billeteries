# Audit sécurité — CSP lot D (branche claude/csp-lot-d)

Verdict : VALIDÉ. Aucun Critique/Haut/Moyen.

1. Blocs <style> : 46 blocs dans resources/views (hors emails), 46 portent `@cspNonce`, 0 sans nonce (grep). Garde-fou : tests/Feature/Security/CspLocalScriptsTest.php:56-66. emails/layout.blade.php garde un <style> sans nonce (hors requête HTTP, non concerné).
2. Rendu hors requête : app/Support/Csp.php:58-63 renvoie '' sans nonce (try/catch). DomPDF (acheteur/tickets-pdf.blade.php:8, via TicketPdfService.php:87) ignore l'attribut ; vide ou présent, rendu inchangé.
3. JS : aucun createElement('style'), insertRule, adoptedStyleSheets ni `<style` dans public/js ; innerHTML sans <style>. `style.cssText` (layouts-promoteur.js:228) et inline styles passent par CSSOM / style-src-attr : couverts.
4. Nonce unique : SecurityHeaders.php:14-15 génère un nonce, utilisé pour scriptDirectives, styleDirectives (:26) et la directive Blade (via attribut de requête, AppServiceProvider.php:107) : identique en-tête et HTML.
5. Résiduel : `style-src-attr 'unsafe-inline'` (Csp.php:37) — Faible/Info : un attribut style injecté permet au pire défacement/exfiltration CSS limitée (img-src/font-src restreints), pas d'exécution JS. Suivi : convertir les style="" en classes (chantier à part).
6. Autres directives : diff limité à style-src ; script-src, img-src, connect-src, frame-*, form-action, object-src inchangés. Rapport-only reprend la même politique (Csp.php:75).
Info : les feuilles externes restent limitées à fonts.googleapis.com, cdnjs, cdn.jsdelivr.net (aucun @import tiers trouvé).
