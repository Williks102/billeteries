# Création d'événement simplifiée, partie 2 : admin et duplication

Branche `claude/creation-evenement-admin` (depuis main, partie 1 incluse). Décisions du propriétaire : l'admin crée pour un promoteur, bouton « Dupliquer », mêmes règles qu'en partie 1.

## Parcours admin, avant / après
| | Avant (vérifié dans le code de main) | Après |
|---|---|---|
| Création | `store` appelait `$event->logAction()` (méthode inexistante) : événement créé puis fausse erreur « Erreur lors de la création », risque de doublon | `AdminStoreEventRequest` + `CreateEventAction` partagés avec le promoteur : 1 envoi, événement + billets, aucune fausse erreur |
| Statut | select `draft/pending/published/rejected` (pending et rejected hors enum de la base) | pas de champ : brouillon ; deux boutons « Enregistrer en brouillon » / « Publier » réellement pris en compte |
| Billets | non saisissables (les écrans « manage-tickets / hybride » n'existent plus) | saisis dans le même formulaire (partial `events/_form`, bloc Standard pré-rempli) |
| Promoteur | `exists:users,id` (n'importe quel rôle) | select de `topFields` ; `promoter_id` requis et validé `exists users … role = promoteur` (acheteur, admin ou id inconnu : refus) |
| `management_mode` | forcé à la création, hors `$fillable` | valeur par défaut de la base ; aucun champ ; `$fillable` inchangé |
| Après création | liste admin | fiche `admin.events.show` avec la checklist (composant `admin/publication-checklist`) |
| Publication rapide | `quickPublish` : `status = published` sans contrôle + `logAction` (500) | `PublishEventAction` : refus 422 (AJAX) ou message d'erreur, avec la liste des raisons ; aucun 500 |
| `updateStatus` -> published, action groupée « publier » | publiaient sans contrôle | passent par `PublishEventAction` (groupée : seuls les événements complets sont publiés et comptés) |
| Édition | `logAction` après l'enregistrement (fausse erreur), pas de recalage | `AdminUpdateEventRequest` (règles de `UpdateEventRequest` + promoteur de rôle promoteur + statut draft/published/cancelled), recalage `RealignSalePeriodsAction`, « published » via la checklist (sinon avertissement), image remplacée après succès seulement |

Sécurité : routes admin sous `auth` + `admin` ; promoteur et acheteur reçoivent 403, visiteur redirigé vers la connexion (testé sur create, store, quick-publish, quick-unpublish, duplicate, update). L'événement appartient au promoteur choisi, qui le voit dans sa liste (testé, P2 ne le voit pas).

## Duplication (`DuplicateEventAction`, route `promoteur.events.duplicate` et `admin.events.duplicate`, POST)
Copié : titre « … (copie) » ; catégorie ; lieu ; adresse ; description ; conditions ; heures de début et de fin ; image (le FICHIER est copié sur le disque `public` sous un nouveau nom généré : supprimer l'original ne casse pas la copie ; fichier absent = copie sans image) ; billets (nom, prix, quantité = capacité d'origine, max par commande, description, `is_active` identique) avec `quantity_sold = 0`.
Recalé : date = prochain samedi (comme le formulaire de création, `EventFormDefaults`) ; période de vente de chaque billet = de maintenant au début de l'événement copié.
Jamais copié : commandes, lignes de commande, billets émis, commissions, paiements, statut (toujours `draft`), lien vers l'original. `promoter_id` = propriétaire de l'original (un admin qui duplique garde le promoteur d'origine). Tout dans une transaction ; l'image copiée est supprimée si elle échoue. L'original n'est pas modifié.
Accès : propriétaire seulement (autre promoteur 403, acheteur 403, visiteur vers la connexion) ; l'admin peut dupliquer n'importe quel événement. Redirection vers l'édition (promoteur : `promoteur.events.edit`, admin : `admin.events.edit`) avec « Brouillon créé : choisissez la nouvelle date. »
Boutons « Dupliquer » (formulaire POST + @csrf) : liste et fiche promoteur ; fiche et menu de la liste admin. L'ancienne méthode `Promoteur\EventController::duplicate` (dates, billets, `replicate()`) est remplacée par l'Action. La route est déclarée avant la ressource `events` (convention) ; un test vérifie qu'un POST `events/{id}/duplicate` atteint l'action et qu'un GET donne 405.

Routes : 199 avant, 201 après (+ `promoteur.events.duplicate`, `admin.events.duplicate`).

## Tests
`tests/Feature/AdminEventCreationTest.php` (17 cas) et `tests/Feature/EventDuplicationTest.php` (17 cas), ajoutés à `test:routes`. Mutations vérifiées rouges : copie de `quantity_sold` (1 test), duplication sans contrôle de propriétaire (1), `promoter_id` d'un non-promoteur accepté (1), publication admin sans checklist (4), copie publiée au lieu de brouillon (3). Adaptés légitimement : `ActionBarsTest` (la vue admin de création délègue à `events/_form`) et `CharteEtape2Test` (vue contrôlée : `_promoter-field`). Le schéma de test ajoute `management_mode` avec son défaut (`tests/Concerns/BuildsEventsTable.php`).

## Vérification visuelle
Instance jetable (sqlite, Chrome via Playwright, Bootstrap et Font Awesome servis en local), 390 et 1280 px : création admin, fiche admin avec checklist, liste promoteur et redirection vers l'édition de la copie : pas de défilement horizontal, aucun contrôle de création < 44 px, aucune boîte de dialogue. Le bouton « Dupliquer » de la liste promoteur est forcé à 44 px ; les boutons existants de cette carte (Détails, Modifier, Billets, Publier) restent à 31 px (préexistant). Captures : `docs/audit-visuel/evenement-admin-brouillon-checklist-390.jpg`, `evenement-duplication-liste-390.jpg`. Non vérifié : appareil mobile réel, MySQL.

## Ce qui reste
- Édition admin : `admin/events/edit.blade.php` (617 lignes) garde son propre formulaire ; seul le contrôleur est aligné (requête, recalage, checklist). Pas de partial partagé, pas de champ « conditions », « supprimer l'image » (`remove_image`) non traité (comme avant).
- L'admin ne peut pas ajouter ni modifier les billets d'un événement après création (les écrans admin ont été supprimés ; les routes de billets sont promoteur seulement) : il passe par la création ou par le promoteur.
- `updateStatus` et le menu de la liste admin proposent encore `pending` / `rejected`, absents de l'enum de la base (erreur SQL en MySQL strict) ; `bulkAction` et `restorePromoterControl` appellent encore `enableAdminMode` / `restorePromoterMode`, inexistants dans `Event`.
- Le calculateur de gains reste référencé par `DefaultCommissionRateTest` (inchangé).
