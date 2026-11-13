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
| Événement | `event.created`, `.duplicated`, `.updated` (titre, date, heure, lieu, catégorie ; un changement d'adresse est signalé « adresse modifiée » sans la valeur), `.published`, `.unpublished`, `.status_changed`, `.deleted` | `CreateEventAction`, `DuplicateEventAction`, `EventActivityObserver` |
| Plateforme | `settings.updated` (taux de commission par défaut, valeur effective avant/après, y compris le retour à la valeur du serveur ; rien n'est écrit si le taux est inchangé), `auth.admin_login` | `Admin\SettingsController`, `Auth\LoginController` |
| Connexions | `auth.login_failed` (raison : compte inconnu / mot de passe incorrect), `auth.lockout` (trop de tentatives) ; `auth.admin_login` figure dans la même famille du filtre | `Listeners\LogAuthFailure` (événements `Failed` et `Lockout`) |
| Utilisateur | `user.created`, `user.deleted` (identifiant et rôle seulement), `user.role_changed`, `user.status_changed` (vérification d'e-mail) | `UserActivityObserver` |

## Plusieurs lignes pour une même action
Un changement de statut de paiement fait par l'admin produit **deux lignes** : `payment.confirmed` + `order.status_changed` ; de même annulation = `order.cancelled` + `order.status_changed`, remboursement = `order.refunded` + `order.status_changed`. La première décrit l'événement métier, la seconde la transition de statut ; ce n'est pas un doublon à dédupliquer.

## Volontairement non journalisé
- Scan et validation des billets (`ticket.scanned`) : flux à haut volume avec plusieurs points de passage (`ScannerController`, `TicketVerificationController`, admin), pas de point unique.
- Mouvements de `quantity_sold` (ventes et réservations) : déjà tracés par les commandes.
- Paiement des commissions, `payment.rejected` (les paiements sont manuels : un refus est une annulation, `order.cancelled`).

## Conservation et RGPD
- Durée de conservation : **illimitée pour l'instant** (décision à prendre avant la production ; une purge devra passer par une commande dédiée, le modèle ne permet pas la suppression applicative).
- **Aucune donnée personnelle** : jamais d'e-mail, téléphone, nom, mot de passe, jeton, `guest_token`, clé API, ni référence de paiement saisie librement. Seulement des identifiants (id, numéro de commande) et des valeurs métier (statut, montant, prix, quantité).
- L'IP n'est jamais stockée en clair : seulement `ip_fingerprint` = HMAC-SHA256 de l'IP avec la clé de l'application (`CheckoutService::ipHash`, comme le plafond des commandes gratuites). Changer `APP_KEY` rend les anciennes empreintes incomparables.
- Défense en profondeur : `ActivityLogger` retire des propriétés toute clé contenant (insensible à la casse) `email`, `phone`, `password`, `token`, `secret`, `api_key`, `name`, `address`, `mail`, `mobile`, `iban`, `card`, `jwt`, `cvv`, ou comportant le mot `tel`, `ip` ou `pin` (par mot : `user_ip`, `clientIp`, pas `description`). Garde-fous : `tests/Feature/Activity/`.
- L'écran affiche le nom de l'acteur en le relisant dans `users` (il n'est pas copié dans le journal).
- **Ajout seul = règle applicative.** Le modèle refuse update/delete, mais `ActivityLog::query()->delete()`, `truncate` ou du SQL direct le contournent. **À faire en production** : retirer les droits `UPDATE` et `DELETE` sur `activity_logs` à l'utilisateur SQL de l'application (ne garder que `INSERT` et `SELECT`), et confier toute purge à un compte distinct.

## Échecs de connexion (`auth.login_failed`, `auth.lockout`)
Écrits par `App\Listeners\LogAuthFailure`. Acteur absent ; propriétés = raison et empreinte HMAC de l'identifiant saisi (jamais l'e-mail ni le mot de passe) ; empreinte HMAC de l'IP en colonne. **Anti-inondation** : au plus une ligne par minute et par identifiant, une par minute et par IP (`Cache::add`), et 30 lignes par minute au total ; au-delà, l'événement est ignoré. Le journaliseur ne lève jamais (la connexion n'est ni ralentie ni cassée). Filtre « Connexions » de l'écran. Les lignes d'un même identifiant se reconnaissent par `identifier_hash` ; les compteurs de limitation vivent dans le cache (donc perdus au vidage du cache, sans conséquence sur les données).
