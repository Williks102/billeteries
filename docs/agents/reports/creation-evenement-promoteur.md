# Création d'événement simplifiée, partie 1 : parcours promoteur

Branche `claude/creation-evenement-promoteur`. Variante A (décisions du propriétaire). Admin et duplication : partie 2.

## Avant / après
| | Avant | Après |
|---|---|---|
| Écrans | 4 (create, liste billets vide, ajout billets, show) | 1 (create) puis show |
| Envois au serveur | 3 (événement, billets, publier) | 1 (événement + billets, brouillon ou publier) |
| Champs à taper (événement + 1 billet) | 7 + nom + prix + dates | titre, catégorie, lieu, prix (date 20:00 / prochain samedi, quantité 100, nom Standard pré-remplis) |
| Interactions mesurées (Chrome sans interface, instance jetable) | n/a | 5 pour un brouillon, 7 pour publier avec image (< 10) |
| Validation | 2 jeux dupliqués + `alert()` | `StoreEventRequest` / `UpdateEventRequest` (règles uniques, messages FR) |
| Publication | 1 seul contrôle (billet actif) | checklist (`Event::publicationChecklist`) + `PublishEventAction` |

Code : `app/Http/Requests/{EventFormRequest,StoreEventRequest,UpdateEventRequest}`, `app/Actions/Events/{CreateEventAction,PublishEventAction,EventCreationResult}`, `app/Support/Events/{SalePeriod,EventFormDefaults}`, `resources/views/events/_form.blade.php` (partial partagé), `components/promoteur/publication-checklist`. Aucune route créée ou supprimée (199 avant, 199 après).

## Changements de règles
- Fin de vente par défaut = début de l'événement (date + heure), au lieu de date + 23:59:59. Une date de fin saisie le jour de l'événement est ramenée à l'heure de début ; une date après l'événement est refusée. S'applique aussi à l'ajout et à l'édition d'un billet (`TicketTypeController`).
- Début de vente par défaut = maintenant. Max par commande par défaut 10 (au lieu de 5 dans le formulaire), borné par la quantité.
- Date : >= aujourd'hui à la création ; à l'édition, un événement passé reste modifiable si sa date n'est pas changée (avant : `after:today` seulement à la création).
- Brouillon libre sans billet. Un bloc « Standard » sans prix est ignoré (pas une erreur). Description et adresse facultatives (adresse = lieu, description '').
- Publication : bloquants = billet actif, date+heure futures, au moins un billet à période valide (début <= fin <= début de l'événement, fin non passée), image. Avertissements : description vide, un seul billet. « Publier » demandé avec manques = enregistré en brouillon + message listant les manques. Ancienne règle (un billet actif) durcie.
- Image : règles de l'édition (jpeg/png/jpg/gif, 2 Mo) appliquées aussi à la création (webp n'était déjà pas listé à l'édition).
- Prix : entier (avant : numérique). Pré-génération des lignes `tickets` retirée de `TicketTypeController::store` (le checkout les crée à la réservation) ; logs de debug retirés (plus aucun `$request->all()`).
- Correctifs au passage : l'édition ne vide plus `terms_conditions` (champ absent du formulaire d'édition) ; `after:sale_start_date` (cassé en tableau) remplacé par un contrôle explicite ; script mort `publishEvent()` (avec `alert()`) retiré de show.

## Tests
`tests/Feature/EventCreationTest.php` (45 cas, ajouté à `test:routes`). Mutations vérifiées rouges : image non exigée, `promoter_id` lu depuis la requête, publication malgré checklist, fin de vente non bornée. Adaptés légitimement : `ActionBarsTest` et `CharteEtape2Test` (les vues contrôlées sont passées de `promoteur/events/create` à `events/_form`).

## Vérification visuelle
Instance jetable, 390 et 1280 px : pas de défilement horizontal, aucun contrôle < 44 px, aucune boîte de dialogue. Captures : `docs/audit-visuel/evenement-creation-390.jpg`, `evenement-brouillon-checklist-390.jpg`. Non vérifié : appareil mobile réel, MySQL.

## Reste pour la partie 2
- Admin : réutiliser `CreateEventAction` (passer `promoter_id` explicitement) et `events/_form` (paramètre `topFields` pour le choix du promoteur) ; `quickPublish` admin à aligner sur `PublishEventAction`.
- Duplication (route + `DuplicateEventAction`), édition alignée sur le partial.
- Calculateur de gains `components/promoter-earnings-calculator` : inutilisé dans les vues mais référencé par `DefaultCommissionRateTest` (zone commission), non supprimé.
- Changer la date d'un événement ne recale pas les fins de vente des billets existants : la checklist le signale à la publication.
