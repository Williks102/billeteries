<?php

/*
 * Aide globale (espace de noms global) pour la politique de sécurité du contenu (CSP).
 *
 * csp_nonce() renvoie le nonce de la requête HTTP en cours, posé par le middleware SecurityHeaders AVANT le rendu
 * des vues. Hors requête HTTP (console, vue rendue seule dans un test, page d'erreur produite avant le middleware)
 * il n'y a pas de nonce : la fonction renvoie une chaîne vide et ne plante jamais.
 */

if (! function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return \App\Support\Csp::nonce();
    }
}
