# Création / édition des types de billets (promoteur) : version mobile-first

## Avant / après
- `resources/views/promoteur/events/tickets/create.blade.php` : 931 lignes, DEUX templates complets (`@extends('layouts.promoteur')` l.1 et `@extends('layouts.app')` l.505, ce dernier collé sur la dernière ligne du premier) : Blade ajoute les deux layouts au rendu, d'où deux documents. Après : 1 template de ~55 lignes.
- Nouveaux : `_form.blade.php` (formulaire, aide, CSS local, JS), `_fields.blade.php` (un bloc de billet), `edit.blade.php` (corrige l'erreur 500 de la route edit), `public/js/promoteur-tickets-form.js` (JS sorti de la vue).
- `ViewReferencesTest::KNOWN_MISSING` : `promoteur.events.tickets.edit` retiré (liste en baisse).
- Contrôleur, routes, layout, validation : inchangés.

## Choix
- Colonne unique sous 576 px (`col-12 col-sm-6`), champs 16 px, hauteur >= 44 px, `type=number` + `inputmode=numeric` + `pattern`, `autocomplete=off`, dates `type=date`.
- Erreurs : `@error` sous chaque champ (`role=alert`, `aria-invalid`, `aria-describedby`) + résumé focalisé.
- Barre d'actions collante (sticky) sous 576 px, boutons empilés. Aides (récapitulatif, modèles) repliables (`<details>`, sans JS).
- JS : blocs ajoutés par clonage de `<template>` (aucun innerHTML), modèles prédéfinis (nom, description, prix, quantité, max par commande), dates de vente préremplies côté serveur (début = aujourd'hui, fin = date de l'événement) et cohérence début/fin via `setCustomValidity` (plus d'`alert`).
- Aperçu de gain : NON ajouté (pas prévu sur cette page). L'estimation existante est la recette BRUTE potentielle (libellé précisé) ; pas de commission.

## Validation visuelle (Playwright Chromium, HTML rendu par Laravel + Bootstrap 5.3.6 local, CDN bloqué)
390x844 / 768x1024 / 1280x800, pages create, edit et create après erreurs : scrollWidth = clientWidth partout (aucun scroll horizontal), 0 champ/bouton < 44 px, police des champs 16 px, barre collante `sticky` à 390 px (en bas du viewport) et `static` au-delà, aucune erreur JS. Interaction : clic sur le modèle VIP remplit nom/quantité 50/max 3/recette « 500 000 FCFA », ajout d'un 2e bloc (`ticket_types[1][price]`). Non vu : icônes Font Awesome (CDN bloqué), vrai téléphone, app complète (pas de MySQL).

## Tests (`tests/Feature/PromoteurTicketFormMobileTest.php`, 6 tests)
200 create/edit propriétaire, 403 autre promoteur, un seul `<html`, champs/`inputmode`, erreurs reliées par `aria-describedby`, garde-fou source (pas de `width: Npx`, `col-12`, pas de `col-md-`). Mutations (worktree jetable) : `@extends` dupliqué rouge (`2 !== 1`), retrait de `inputmode` rouge.

## Constats hors périmètre
- Les messages de validation sans traduction s'affichent en clés brutes (`validation.required`, `validation.min.numeric`) : fichiers de langue FR absents (architecte).
- Le nouveau test n'est pas dans `composer run check` (composer.json hors périmètre) : à ajouter à `test:routes`.
- Le contrôleur `store` journalise `$request->all()` (D8).
