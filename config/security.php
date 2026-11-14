<?php

return [
    /*
    | CSP en mode « rapport seulement » (lot A, voir docs/csp.md) : envoie l'en-tête
    | Content-Security-Policy-Report-Only avec la politique stricte cible. Ne bloque rien.
    | CSP_REPORT_ONLY=false le désactive.
    */
    'csp_report_only' => filter_var(env('CSP_REPORT_ONLY', true), FILTER_VALIDATE_BOOLEAN),

    // Nombre maximal de lignes de la table csp_violations (au-delà : on n'incrémente que les lignes existantes).
    'csp_report_max_rows' => (int) env('CSP_REPORT_MAX_ROWS', 500),
];
