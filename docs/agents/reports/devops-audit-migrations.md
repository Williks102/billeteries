# Audit des migrations : premier démarrage sur MySQL 8 / TiDB 8.5 vierge

Branche `claude/deploiement-render`. Auteur : devops. Rédigé après correction du constat B1 du relecteur.

## Méthode et honnêteté

- **Lu** : les 37 fichiers de `database/migrations` (36 exécutables + 1 `.back`), dans l'ordre de tri par nom (celui du framework : il ne charge que les fichiers dont le nom se termine par `.php` ; `xxx.php.php` est donc exécuté, `xxx.php.back` ne l'est jamais). 4 migrations correctives ont été ajoutées : 40 exécutables au total.
- **Exécuté** : toute la chaîne sur SQLite (fichier jetable, puis test `tests/Feature/MigrationChainTest.php` en mémoire) : OK, 40 migrations, second passage « Nothing to migrate ».
- **NON exécuté** : MySQL, MariaDB, TiDB (aucun serveur ; apt et téléchargements bloqués). Tout ce qui est dit sur MySQL/TiDB est un **raisonnement** (lecture des migrations, du code de Laravel 12 `MySqlGrammar`, et de la documentation connue), pas une preuve. Les points les moins sûrs sont marqués [SUPPOSÉ].
- Ce que SQLite ne peut pas montrer : erreur 1826 (nom de clé étrangère dupliqué), 3780 (types de FK incompatibles), limite d'index 3072 octets, comportement de `ALTER … MODIFY ENUM`, `RENAME COLUMN` sur colonne à clé étrangère, restrictions `ALTER` propres à TiDB.
- Règles appliquées à chaque migration : table/colonne préexistante ? ordre des dépendances FK ? nom d'index ou de FK dupliqué (unique par table pour TiDB, par base pour MySQL) ? types FK (`foreignId` = BIGINT UNSIGNED = `id()` : compatibles) ? `dropColumn` d'une colonne à FK/index ? `dropForeign` d'un nom inexistant ? `renameColumn` + index ? défauts/strict mode ? taille d'index (utf8mb4 : 4 octets/car., varchar(255) = 1020 octets ; aucun composite proche de 3072) ?
- Nota Laravel 12 : `->change()` est natif (pas de doctrine/dbal) et génère `ALTER TABLE … MODIFY …` ; chaque commande du Blueprint est un `ALTER` séparé (pas de multi-schéma TiDB).

## Verdict chiffré

| | Échouent sur MySQL/TiDB vierge |
|---|---|
| AVANT l'audit | **1 certaine** : `2025_07_29_134010_fix_commissions_table_structure` (erreur 1826). Les 29 suivantes n'étaient jamais atteintes. Sur SQLite : 4 échecs (195142 + les 3 `MODIFY … ENUM`). |
| APRÈS l'audit | **0 certaine**. Risques résiduels non prouvables ici (voir fin) : `RENAME COLUMN` sur TiDB [SUPPOSÉ OK], 5 modifications d'ENUM sur TiDB [SUPPOSÉ OK sur tables vides]. |

Hors échec de migration, 3 défauts de schéma faisaient planter le code à l'exécution (corrigés par migrations additives) : `tickets.used_at` & co (scan), table `event_audit_logs` (statistiques admin), `commissions.paid_by` (paiement en lot).

## Chaîne dans l'ordre d'exécution

Notation : « État » = ce que contient le schéma après la migration (cumulatif seulement quand utile). « Échec ? » = risque sur MySQL 8 / TiDB 8.5 vierge.

1. `0001_01_01_000000_create_users_table` : crée `users` (id, name, email unique, email_verified_at, password, remember_token, timestamps), `password_reset_tokens` (email PK), `sessions` (id PK, user_id index, ip_address, user_agent, payload, last_activity index). Échec ? Non (email unique 1020 octets).
2. `0001_01_01_000001_create_cache_table` : `cache`, `cache_locks` (key varchar(255) PK). Échec ? Non.
3. `0001_01_01_000002_create_jobs_table` : `jobs`, `job_batches`, `failed_jobs` (uuid unique). Échec ? Non.
4. `2025_07_12_141606_add_role_to_users_table` : `users.role` ENUM(admin,promoteur,acheteur) défaut acheteur après email ; `users.phone`. Échec ? Non (`AFTER` supporté par MySQL et TiDB).
5. `2025_07_12_141611_create_event_categories_table` : `event_categories` (name, slug unique, description, icon). Non.
6. `2025_07_12_141615_create_events_table` : `events` avec FK `promoteur_id`→users (`events_promoteur_id_foreign`) et `category_id`→event_categories (créée avant : ordre OK). Non.
7. `2025_07_12_141618_create_ticket_types_table` : FK `event_id`→events. Non.
8. `2025_07_12_141621_create_tickets_table` : FK `ticket_type_id`, `ticket_code` unique, `qr_code` text, `status` ENUM(available,sold,used,cancelled), `seat_number`. Pas de `used_at` (voir I4). Non.
9. `2025_07_12_141624_create_orders_table` : FK `user_id`→users, `event_id`→events ; `payment_status` ENUM(pending,paid,failed,refunded) ; `order_number` unique ; `billing_email`. Non.
10. `2025_07_12_141627_create_order_items_table` : FK order_id, ticket_type_id (tables existantes). Non.
11. `2025_07_12_141631_create_order_tickets_table` : FK order_id, ticket_id, order_item_id (toutes créées avant) ; unique(ticket_id). Non.
12. `2025_07_12_141634_create_commissions_table` : FK `order_id`, **`promoteur_id`→users (nom `commissions_promoteur_id_foreign`)**, montants, `status` ENUM(pending,paid,held), `paid_at`. Non.
13. `2025_07_12_141803_create_commission_settings_table` : FK category_id (nullable), `promoteur_id` (nullable)→users. Non.
14. `2025_07_12_150857_create_telescope_entries_table` : 3 tables Telescope ; connexion = `config('telescope.storage.database.connection')` = `DB_CONNECTION` (défaut `mysql`), lisible même sans le paquet (config/telescope.php ne référence que des constantes `::class`). FK `entry_uuid`→`telescope_entries.uuid` (colonne unique, même type/collation). PK composite (char(36)+varchar(255)) = 1164 octets. Non.
15. `2025_07_25_003819_…tickets_table.php.back` : **jamais exécutée** (extension `.back`). Aurait ajouté holder_*, created_ip, sent_at, downloaded_at, download_count, order_item_id, index. Reprise par la migration 37 ci-dessous. Elle n'aurait d'ailleurs pas fonctionné telle quelle (`after('validation_data')` sur une colonne inexistante ; index `ticket_code` redondant).
16. `2025_07_25_093744_add_phone_role_to_users_table` : gardée par `hasColumn` : ne fait rien (role/phone existent). Non.
17. `2025_07_28_122001_fix_commissions_table_structure` : renomme `commissions.promoteur_id`→`promoter_id` (`ALTER … RENAME COLUMN` : Laravel 12 le choisit si version serveur ≥ 8.0.3 ; MySQL met à jour la FK qui **garde son nom** `commissions_promoteur_id_foreign`) ; puis `status` → ENUM(pending,paid,cancelled,held) par `MODIFY` (insertion de `cancelled` au milieu ; table vide). État : `commissions.promoter_id` + FK ancien nom. Échec ? Non sur MySQL. TiDB : `RENAME COLUMN` [SUPPOSÉ supporté : le parseur et le DDL TiDB le gèrent ; FK mise à jour par `updateFKInfoWhenModifyColumn`, source lue par le relecteur] ; ENUM au milieu : réorganisation acceptée [relecteur : source lue], table vide.
18. `2025_07_29_134010_fix_commissions_table_structure` : **FAUTIVE avant correction.** Recréait `promoteur_id` (absente après le renommage) + `foreign('promoteur_id')` au nom par défaut `commissions_promoteur_id_foreign` = nom déjà pris par la FK renommée → **ERREUR 1826 « Duplicate foreign key constraint name »** (MySQL : unique par base ; TiDB : par table, doc foreign-key.md). **Correction** : `promoteur_id`, sa FK et l'index `[promoteur_id,status]` ne sont créés que si NI `promoteur_id` NI `promoter_id` n'existe (calculé avant la closure). Reste inchangé : `notes` text après `paid_at`, index `commissions_status_index`, `commissions_created_at_index` (gardés par `hasIndex`). État : commissions = order_id, promoter_id, montants, status, paid_at, notes, timestamps + FK/index. Échec ? Non après correction. Pourquoi une garde plutôt que de réécrire : base existante où `promoteur_id` a déjà été recréée : comportement inchangé.
19. `2025_07_29_214626_create_settings_table` : `settings` (key unique) + 10 lignes. Utilise `DB::table` sans `use` : fonctionne grâce à l'alias global `DB` des facades (vérifié par l'exécution). Non.
20. `2025_07_31_171013_create_mail_logs_table` : `mail_logs` (JSON, ENUM, 3 index dont (to_email,created_at)). Non.
21. `2025_08_01_125848_create_pages_table` : `pages` (slug unique, JSON, longText sans défaut). Non.
22. `2025_08_02_195119_rename_promoteur_to_promoter_in_events_table` : gardée ; renomme `events.promoteur_id`→`promoter_id` (FK `events_promoteur_id_foreign` conservée). Non (même remarque RENAME que n°17).
23. `2025_08_02_195133_complete_commissions_promoter_rename` : renommage gardé (déjà fait : sans effet) ; `MODIFY status` ENUM identique à n°17 (no-op de fait, mais exécuté alors que `commissions_status_index` existe : liste identique, donc pas de changement de type) ; index `commissions_promoter_id_status_index` (promoter_id,status) créé si absent. Échec ? Non. TiDB : modification d'une colonne indexée vers un ENUM identique : métadonnées seulement [SUPPOSÉ].
24. `2025_08_02_195136_rename_promoteur_to_promoter_in_commission_settings_table` : gardée ; renomme `commission_settings.promoteur_id`. Non.
25. `2025_08_02_195139_update_user_roles_promoteur_to_promoter` : corps en commentaire, ne fait rien. Non.
26. `2025_08_02_195142_cleanup_duplicate_commissions_migrations` : bloc 1 gardé par `hasColumn('promoteur_id')` : ignoré après la correction n°18 (avant : `dropForeign`+`dropColumn` auraient réussi sur MySQL mais échoué sur SQLite). Bloc 2 : ajoutait **sans garde** `foreign('promoter_id')` (les `try/catch` sont sans effet : les commandes du Blueprint s'exécutent après la closure) : sur MySQL cela aurait créé une seconde FK redondante `commissions_promoter_id_foreign` et échouerait si rejoué. **Correction minimale** : n'ajoute la FK que si aucune FK ne couvre déjà `promoter_id` (`Schema::getForeignKeys`, dans un `try/catch Throwable` : en cas d'impossibilité d'inspecter on suppose qu'elle existe, une FK redondante en moins est sans conséquence). Échec ? Non.
27. `2025_08_02_211543_cleanup_obsolete_promoteur_id_column` : `up()` vide. Non.
28. `2025_08_22_113331_2025_08_22_000001_add_management_mode_to_events` : `events.management_mode` ENUM (après status), `admin_permissions` JSON, `management_reason`, `management_changed_by` FK nullable→users (nullOnDelete), `management_changed_at` ; index `events_management_mode_index`, `events_management_mode_status_index`. Colonnes `AFTER` définies dans l'ordre : chacune existe quand on la référence. Non.
29. `2025_08_22_113552_2025_08_22_000002_create_event_audit_logs_table` : **`up()` vide** : la table n'existe pas alors que `App\Models\EventAuditLog` est interrogé (`Admin\EventController::hybridStats`, l.747). Corrigé par la migration 38.
30. `2025_08_26_145751_add_guest_checkout_fields` : `users.is_guest` (après role), `guest_converted_at` ; `orders.guest_token` varchar(32) + index. Non.
31. `2025_09_01_012923_add_customer_code_to_users_table` : `users.customer_code` varchar(20) unique + index (deux index sur la même colonne : noms différents `users_customer_code_unique` / `_index` ; MySQL tolère). Non.
32. `2025_09_10_225735_create_payments_table.php.php` : exécutée (le suffixe `.php.php` se termine par `.php`). `payments` (FK order_id→orders, ENUM status, JSON, index). Non.
33. `2025_09_11_021012_add_reserved_status_to_tickets_table` : `ALTER TABLE tickets MODIFY COLUMN status ENUM(…,'reserved') DEFAULT 'available'` (ajout en fin de liste). **Garde ajoutée** : instruction brute conservée à l'identique pour `mysql`/`mariadb` ; ailleurs (SQLite) `->change()` Laravel. (La colonne devient nullable côté MySQL : comportement d'origine conservé.) Échec ? Non sur MySQL ; TiDB : ajout de membre en fin de liste accepté (doc/source).
34. `2025_09_11_100326_add_processing_status_to_tickets_table.php.php` : idem, ajoute `processing` en fin de liste. Même garde. Non.
35. `2025_09_11_102806_add_processing_status_to_orders_table` : `orders.payment_status` ENUM avec `processing` **inséré au milieu** (pending,processing,paid,…). MySQL : reconstruction de table, sans souci sur table vide. TiDB : réorganisation acceptée d'après le code lu par le relecteur (`CheckModifyTypeCompatible` → `canReorg`) [SUPPOSÉ]. Même garde de pilote. Non sur MySQL ; TiDB : risque faible, résiduel.
36. `2026_11_11_100000_add_expires_at_and_paid_at_to_orders_table` : gardée ; `orders.paid_at`, `expires_at`, index explicite `orders_status_expires_idx` (payment_status ENUM + timestamp). Non.
37. `2026_11_11_110000_add_admin_notes_to_orders_table` : gardée ; `orders.admin_notes`. Non.

### Précision propriétaire : `promoter_id` seul, aucun nom « promoteur » à l'arrivée

Chaîne par table (vérifiée par lecture + SQLite) : `events` : 141615 crée `promoteur_id`, 195119 renomme en `promoter_id`. `commissions` : 141634 crée `promoteur_id`, 122001 renomme (134010 ne la recrée plus), 195133 pose l'index `commissions_promoter_id_status_index`, 195142 et 211543 n'ont plus rien à faire. `commission_settings` : 141803 crée, 195136 renomme. Résultat SQLite : `promoter_id` présent, `promoteur_id` absent des 3 tables (test). Sur MySQL/TiDB, le renommage de colonne laisse la clé étrangère (`events_promoteur_id_foreign`, `commissions_promoteur_id_foreign`, `commission_settings_promoteur_id_foreign`) et son index implicite sous l'ancien nom : la migration 41 (`2026_11_11_120300_rename_promoteur_constraints_to_promoter`) les remplace par `<table>_promoter_id_foreign` (drop FK, drop index implicite, recréation avec les mêmes ON DELETE/ON UPDATE), uniquement si un nom contenant « promoteur » est détecté (donc sans effet sur SQLite, dont les FK n'ont pas de nom, et sur rejeu). Choix d'une migration postérieure plutôt que de modifier les anciennes : elle couvre aussi les bases existantes. Non prouvé sur MySQL/TiDB (aucun serveur) ; protégée : si l'inspection des clés échoue, elle ne touche à rien. Clés de journal `promoteur_id` restantes dans 5 contrôleurs : hors périmètre, sans effet.

### Migrations ajoutées par cet audit (additives, gardées, jamais destructives)

38. `2026_11_11_120000_add_missing_columns_to_tickets_table` : (I4) ajoute, chacune seulement si absente, avec les types du `.back` : `qr_code_path` varchar, `validation_data` text, `used_at` timestamp, `holder_name`/`holder_email`/`holder_phone` varchar, `created_ip` varchar(45), `sent_at`, `downloaded_at` timestamp, `download_count` int défaut 0 (toutes nullable sauf le compteur) ; `order_item_id` FK nullable→`order_items.id` (nullOnDelete ; types BIGINT UNSIGNED identiques, pas d'erreur 3780) ; index `tickets_used_at_index`, `tickets_status_used_at_index`, `tickets_holder_email_index` (gardés par `hasIndex`). Choix : migration postérieure plutôt que renommer le `.back` (renommer en `.php` l'exécuterait AVANT les ajouts de statuts ENUM, avec `after('validation_data')` sur une colonne absente : il échouerait). `down()` vide (jamais de suppression de données).
39. `2026_11_11_120100_create_event_audit_logs_table_if_missing` : crée `event_audit_logs` si absente (event_id FK cascade, user_id FK nullable nullOnDelete, action, description, old/new_values JSON, ip_address, user_agent, timestamps, 2 index) : correspond au `$fillable` du modèle.
40. `2026_11_11_120200_add_paid_by_to_commissions_table` : `commissions.paid_by` FK nullable→users, car `Admin\AdminController` (~l.210) fait un `UPDATE` direct avec `paid_by` (« Unknown column » sinon).

41. `2026_11_11_120300_rename_promoteur_constraints_to_promoter` : voir « Précision propriétaire » ci-dessus (renommage des FK/index `promoteur`).

## Colonnes utilisées par le code / créées par / manquantes ?

Méthode : (a) pour chaque modèle, `$fillable` + `$casts` comparés au schéma final simulé (script, puis test `test_chaque_modele_trouve_ses_colonnes_fillable_et_castees`) ; (b) recherche des colonnes citées dans `where/orderBy/pluck/sum/update([...])` du dossier `app` absentes de toute table. Les alias de `selectRaw` (`period_earned`, `revenue`, `count`, `total_revenue`, `month`, `date`, `sold_today`, `abandoned`, `events_count`, `orders_count`, `revenue_this_month`) ne sont pas des colonnes.

| Table.colonne (code) | Créée par | Manquante avant l'audit ? |
|---|---|---|
| users.role, phone | 141606 / 093744 | non |
| users.is_guest, guest_converted_at | 26_145751 | non |
| users.customer_code | 09_01_012923 | non |
| users.avatar (`Promoteur\ProfileController` l.140 `update(['avatar'=>…])`) | aucune | oui, mais `avatar` n'est pas dans `User::$fillable` : l'écriture est ignorée sans erreur SQL ; non ajoutée (code mort, hors périmètre) |
| events.promoter_id | 141615 (promoteur_id) + 195119 | non |
| events.management_* , admin_permissions | 08_22_113331 | non |
| orders.expires_at, paid_at | 2026_100000 | non |
| orders.admin_notes | 2026_110000 | non |
| orders.guest_token | 08_26_145751 | non |
| orders.billing_email/phone, payment_* , order_number | 141624 | non |
| order_items.* ; order_tickets.* | 141627 / 141631 | non |
| tickets.used_at (`Ticket::markAsUsed`, scanners, admin) | aucune (le `.back` jamais exécuté) | **oui → 38** |
| tickets.qr_code_path (`QRCodeService` l.121, `GenerateQrCodes`, `TicketController` l.78 `whereNull`) | aucune | **oui → 38** (le `whereNull` produisait une erreur SQL) |
| tickets.validation_data, holder_phone, order_item_id (`$fillable`, relation `orderItem()`/`order()`) | aucune | **oui → 38** |
| tickets.holder_name, holder_email (vues `ticket/show`, `tickets/show`) | aucune | oui (lecture null sans erreur) → 38 |
| tickets.used_by, cancelled_by, cancelled_at, admin_notes (`Admin\TicketController` update) | aucune | oui, mais absents de `Ticket::$fillable` : valeurs ignorées sans erreur SQL ; non ajoutées (hors périmètre, logique métier) |
| commissions.promoter_id, order_id, gross_amount, commission_rate, commission_amount, net_amount, platform_fee, payment_processor_fee, status, paid_at, notes | 141634 / 122001 / 134010 | non |
| commissions.paid_by (`Admin\AdminController` ~l.214, UPDATE direct) | aucune | **oui → 40** |
| commission_settings.category_id, promoter_id, rate, platform_fee_fixed, min_commission, is_active, valid_* | 141803 / 195136 | non |
| payments.* | 09_10_225735 | non |
| event_audit_logs.* (`EventAuditLog`) | aucune (migration vide) | **table entière manquante → 39** |
| ticket_types.*, settings.*, pages.*, mail_logs.* (`EmailLogService`) | créations d'origine | non |
| sessions/cache/jobs | 0001_01_01_* | non |

Limite : la recherche (b) est une heuristique par expression régulière ; les colonnes citées dans du SQL brut (`DB::raw`, `selectRaw` longs) n'ont pas été vérifiées une par une. Les flux principaux (commande, paiement manuel, expiration) sont couverts par les autres tests, qui utilisent leur propre schéma (`tests/Concerns/BuildsOrderSchema.php`), d'où l'intérêt du test de chaîne.

## Ce que le test de chaîne SQLite prouve et ne prouve pas

Prouve : les 40 migrations s'exécutent dans l'ordre sans table/colonne/index manquant ; le schéma final contient les colonnes de la liste ci-dessus et celles de chaque `$fillable`/`$casts` ; `commissions` n'a plus `promoteur_id` ; les 8 migrations gardées se rejouent sans exception ; un billet payé peut être scanné (`markAsUsed` écrit `used_at`) ; les statuts `reserved`/`processing` sont acceptés ; tout `ALTER … MODIFY` brut est gardé par le pilote (le test échoue sinon) ; seul le `.back` connu n'est pas exécuté et ses colonnes sont reprises. Mutation : en remettant la version d'origine de 2025_07_29_134010, 6 tests sur 8 deviennent rouges (la chaîne casse à 195142 sur SQLite ; sur MySQL elle casserait dès 134010 avec l'erreur 1826).
Ne prouve pas : tout ce qui est propre à MySQL/TiDB (liste en tête de ce rapport).

## Autres constats

- `Admin\SettingsController::backup` (l.234-246) construit une commande `mysqldump` avec le mot de passe de la base en argument et l'exécute par `exec` ; `mysqldump` n'est pas dans l'image. Hors périmètre ; c'est pourquoi `exec` n'est PAS dans `disable_functions` (docker/php.ini). À traiter par `securite` / `paiements-stock`.
- `Admin\SettingsController` ~l.341 : `OPTIMIZE TABLE` (MySQL) ; TiDB ne le supporte pas : erreur à l'usage de cette fonction admin, pas au démarrage.

## Risques résiduels sur MySQL/TiDB (non prouvables ici)

1. TiDB : `ALTER TABLE … RENAME COLUMN` (n° 17, 22, 24) et conservation du nom de la FK : supposé conforme (source lue par le relecteur).
2. TiDB : 5 modifications d'ENUM (n° 17, 23, 33, 34, 35) : ajout/insertion de membres, tables vides ; supposé accepté.
3. Détection de version : `RENAME COLUMN` n'est choisi que si le serveur annonce ≥ 8.0.3 (TiDB annonce 8.0.11-TiDB-v8.5.x) ; sinon Laravel emploierait la forme `CHANGE` historique [SUPPOSÉ].
4. `Schema::getForeignKeys` / `hasIndex` s'appuient sur `information_schema` : protégés par `try/catch` pour le premier, déjà utilisés par le code d'origine pour le second.

Prochaine étape recommandée avant remise au propriétaire : un seul `php artisan migrate:fresh --force` contre un MySQL 8 ou un TiDB jetable (`composer run test:mysql` après).
