# Relecture : création d'événement partie 2 (admin + duplication)

Branche `claude/creation-evenement-admin`, `git diff origin/main...HEAD` : 25 fichiers, 2 commits (230a046, d1b5747). Relecteur indépendant (n'a pas participé à l'implémentation). Volet sécurité inclus.

## Verdict : APPROUVÉ SOUS RÉSERVES
Aucun bloquant introduit par la branche ; check vert ; 15/16 mutations rouges (la 16e est sans objet, voir m1). Une réserve importante, préexistante, dans des méthodes touchées par la branche (I1) : l'utilisateur décide si elle est corrigée ici ou dans une mission séparée.

## Exécution
- `composer run check` : `OK (232 tests, 1706 assertions)` + `OK (250 tests, 7881 assertions)` + `OK (268 tests, 3058 assertions)` + `OK (54 tests, 2314 assertions)`, sortie 0.
- `php artisan route:list` : 201 routes ; `POST admin/events/{event}/duplicate` et `POST promoteur/events/{event}/duplicate` présentes [VÉRIFIÉ].
- Dépôt non modifié (hors ce rapport et la ligne du BOARD) ; mutations faites dans une copie jetable sous le scratchpad.

## Bloquants
Aucun.

## Importants
- **I1 (préexistant, méthodes touchées)** : `app/Http/Controllers/Admin/EventController.php:298` valide encore `status in:draft,pending,published,rejected` ; `:336` accepte `reject` et `take_control` ; `:356` écrit `status = 'rejected'` (hors de l'enum `draft,published,cancelled,finished`, `database/migrations/2025_07_12_141615_create_events_table.php:25`) ; `:370` appelle `enableAdminMode` et `:679` `restorePromoterMode`, méthodes absentes de `Event`. Elles lèvent des exceptions attrapées (`catch (\Exception)`) : l'admin reçoit un JSON 500 « Erreur lors de l'action groupée », pas une page d'erreur. Accessible depuis l'interface : l'option « Rejeter les événements » du formulaire groupé (`resources/views/admin/events/index.blade.php:112`) [VÉRIFIÉ à la lecture, non exécuté en MySQL]. `take_control` et `restore-promoter-control` ne sont accessibles que par une requête forgée par un admin (aucune vue ne les appelle). Correction minimale (backend) : `updateStatus` -> `in:draft,published,cancelled` ; `bulkAction` -> `in:publish,delete`, retirer les `case reject/take_control` et l'option « Rejeter » de la vue ; supprimer la route `restore-promoter-control` et sa méthode (aucun appelant ; 201 -> 200 routes), ou bien la faire répondre 404. Ajouter un test 422 sur `status=rejected`.

## Mineurs
- m1 : `EventDuplicationTest::test_the_duplicate_route_is_not_shadowed_by_the_events_resource` reste vert quand la route est déclarée APRÈS la ressource (mutation M11) : la ressource n'a aucune route `POST events/{event}/…`, donc aucun masquage n'est possible. Le test ne protège rien mais n'est pas faux ; la convention (déclarer avant) est respectée (`routes/promoteur.php:23`, `routes/admin.php:73`).
- m2 : `bulkAction` « publier » compte seulement les événements publiés mais ne dit pas lesquels ont été refusés ni pourquoi (`Admin/EventController.php:347-350`). À améliorer côté UX.
- m3 : `update` admin avec `status=published` sur un événement `cancelled` incomplet le laisse en `draft` avec un avertissement (`:203`) ; c'est cohérent avec « jamais publié sans checklist », mais l'annulation est levée sans message explicite.
- m4 : double clic sur « Dupliquer » : deux brouillons (pas de verrou ni de jeton d'idempotence). Acceptable (brouillons supprimables, rien de vendu) ; on peut désactiver le bouton à la soumission.
- m5 : bouton « Dupliquer » de la fiche admin (`admin/events/show.blade.php:15`) : taille `.btn` standard, pas de min-height 44 px explicite (la liste promoteur la force, `promoteur/events/index.blade.php:222`). Non mesuré.

## Sécurité (Critique / Élevé / Moyen / Faible)
- Critique, Élevé, Moyen : **aucun**.
- Faible : I1 (JSON 500 accessible par un admin, aucune fuite ni écriture partielle hors du lot déjà traité).
- Vérifié :
  - `promoter_id` admin : `Rule::exists('users','id')->where('role','promoteur')` + `integer` (`AdminStoreEventRequest.php:22`, `AdminUpdateEventRequest.php:21`). Test sur acheteur, admin, 999, vide, « abc » ; un tableau est refusé par `integer`.
  - Routes admin : middleware `admin` (`AdminMiddleware.php:19`) + `authorize()` des requêtes. Un promoteur ou un acheteur reçoit 403, un visiteur est redirigé (test sur create, store, quick-publish, quick-unpublish, duplicate, update).
  - Duplication promoteur : `abort_if($event->promoter_id !== Auth::id(), 403)` (`Promoteur/EventController.php:300`). Le promoteur de la copie est `$source->promoter_id` (`DuplicateEventAction.php:41`), jamais lu de la requête.
  - Mass-assignment : `eventData()` est une liste explicite (`EventFormRequest.php:17-20,85`) ; `management_mode` hors `$fillable` ; `status` forcé à `draft` (`CreateEventAction.php:50`). `image` en chaîne : refusée par la règle `image`. Test : injection de `management_mode`, `status` et `id`.
  - Image copiée : chemin source lu en base seulement ; nom `events/<40 aléatoires>.<ext liste blanche>` (`DuplicateEventAction.php:84-86`). Une traversée de répertoire est bloquée par Flysystem (exception attrapée par le contrôleur). Fichier source absent : copie sans image, pas de 500 (test + mutation M10 rouge).
  - CSRF : les 4 boutons sont des `form POST` avec `@csrf` ; GET donne 405 (testé).
  - XSS : titres affichés en `{{ }}` ; aucun `{!! !!}` dans les vues touchées.
  - Journaux : classe d'exception et identifiants seulement ; pas de `$request->all()` (testé). Pas de secret.

## Règles métier [VÉRIFIÉ code + tests]
La copie est un brouillon, avec `quantity_sold = 0`. Elle ne reçoit aucune commande, ligne de commande, billet émis, commission ni paiement (test sur l'égalité des comptes de tables). Date : samedi suivant, heure d'origine ; vente : de maintenant au début de l'événement ; billets inactifs conservés inactifs. L'original n'est pas modifié (test sur l'égalité des lignes). Tout se fait dans une transaction, avec suppression de l'image copiée en cas d'échec.
Seul `PublishEventAction.php:22` écrit `status = 'published'` dans `app/` (grep) : `quickPublish`, `updateStatus`, l'action groupée, `update` et `store` passent tous par la checklist. `draft` et `cancelled` sont inchangés.

## Tests et mutations (copie jetable)
Rouges : M1 copie de `quantity_sold` ; M2 contrôle du propriétaire retiré ; M3 `promoter_id` non promoteur accepté (création) ; M16 idem en édition ; M4 `quickPublish` sans checklist ; M5 copie publiée ; M6 promoteur laissé passer par le middleware admin ; M7 `updateStatus` sans checklist ; M12 publication groupée sans checklist ; M14 `update` qui publie sans checklist ; M8 copie des lignes `tickets` ; M9 copie d'une commande ; M10 échec si l'image source est absente ; M13 billet inactif rendu actif ; M15 recalage des fins de vente retiré.
Vert : M11 ordre des routes (sans objet, m1).
Adaptations légitimes : `ActionBarsTest` vise désormais `events/_form`, qui contient les deux boutons ; `CharteEtape2Test` vise `_promoter-field` (le `create` admin ne contient plus de champ) ; `EventCreationTest` : `@dataProvider` remplacé par l'attribut, comportement identique.

## Portée
Conforme. `CheckoutService`, `OrderPaymentService`, `TicketType`, `Order` et les migrations ne sont pas modifiés. Ajouts annexes justifiés : `tests/Concerns/BuildsEventsTable.php`, 2 captures `docs/audit-visuel`, `composer.json` (`test:routes`). L'ancienne méthode `Promoteur\EventController::duplicate` est remplacée.

## Non vérifié
MySQL/TiDB (comportement de `rejected` en mode strict), rendu mobile réel du bouton admin « Dupliquer », affichage des messages flash `error` sur la fiche admin après un refus de `quickPublish` hors AJAX.
