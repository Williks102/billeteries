# Journal d'activité

Qui a fait quoi, et quand. Écran réservé aux admins : **Paramètres > Journal d'activité** (`/admin/activity`). Lecture seule, 25 entrées par page, filtres par type d'action, acteur et période (mois civil par défaut, fuseau Africa/Abidjan). Pas d'export. Les promoteurs ne le voient pas.

## Fonctionnement
- Table `activity_logs` (modèle `App\Models\ActivityLog`) : **ajout seul**. Le modèle lève une exception sur update, delete et forceDelete ; aucune route d'édition ni de suppression.
- Écriture : `App\Services\ActivityLogger::record(action, sujet, description, propriétés, événement)`. Il lit l'acteur via `Auth`, **ne lève jamais** (un échec donne un `Log::warning` sans donnée personnelle) et écrit **après le commit** (`DB::afterCommit`) : une action annulée n'est pas journalisée.
- Acteur absent = « système » (commande Artisan, confirmation automatique démo ou gratuite).
- Le journal survit à la suppression du sujet, de l'événement et du compte acteur (pas de clé étrangère sauf `user_id` en `nullOnDelete`).
- Les points d'écriture sont dans la couche service, les actions et des observers de modèle (`app/Observers`), pas dans chaque contrôleur.

## Actions journalisées (clés stables, constantes de `ActivityLog`)
| Famille | Clés | Où |
|---|---|---|
| Paiement | `payment.confirmed` | `OrderPaymentService::confirmPayment` |
| Commande | `order.status_changed`, `order.cancelled`, `order.refunded`, `order.expired` | `OrderPaymentService` ; `Order::releaseStock` (annulation et expiration, via `orders:release-expired`) |
| Billet | `ticket_type.created`, `.updated` (avant/après : prix, capacité, activation, dates de vente, max par commande), `.deleted` | `TicketTypeActivityObserver` |
| Événement | `event.created`, `.duplicated`, `.updated` (titre, date, heure, lieu, adresse, catégorie), `.published`, `.unpublished`, `.status_changed`, `.deleted` | `CreateEventAction`, `DuplicateEventAction`, `EventActivityObserver` |
| Plateforme | `settings.updated` (taux de commission avant/après), `auth.admin_login` | `Admin\SettingsController`, `Auth\LoginController` |
| Utilisateur | `user.role_changed`, `user.status_changed` (vérification d'e-mail) | `UserActivityObserver` |

## Volontairement non journalisé
- Scan et validation des billets (`ticket.scanned`) : flux à haut volume avec plusieurs points de passage (`ScannerController`, `TicketVerificationController`, admin), pas de point unique.
- Mouvements de `quantity_sold` (ventes et réservations) : déjà tracés par les commandes.
- Paiement des commissions, `payment.rejected` (les paiements sont manuels : un refus est une annulation, `order.cancelled`).

## Conservation et RGPD
- Durée de conservation : **illimitée pour l'instant** (décision à prendre avant la production ; une purge devra passer par une commande dédiée, le modèle ne permet pas la suppression applicative).
- **Aucune donnée personnelle** : jamais d'e-mail, téléphone, nom, mot de passe, jeton, `guest_token`, clé API, ni référence de paiement saisie librement. Seulement des identifiants (id, numéro de commande) et des valeurs métier (statut, montant, prix, quantité).
- L'IP n'est jamais stockée en clair : seulement `ip_fingerprint` = HMAC-SHA256 de l'IP avec la clé de l'application (`CheckoutService::ipHash`, comme le plafond des commandes gratuites). Changer `APP_KEY` rend les anciennes empreintes incomparables.
- Défense en profondeur : `ActivityLogger` retire des propriétés toute clé contenant `email`, `phone`, `password`, `token`, `secret`, `api_key`, `name`, `address`. Garde-fous : `tests/Feature/Activity/`.
- L'écran affiche le nom de l'acteur en le relisant dans `users` (il n'est pas copié dans le journal).
