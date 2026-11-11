# Relecteur : 2e relecture de `claude/deploiement-render` (après REFUSÉ B1, I1-I4)

Base : origin/main 4bc4ffd. Code relu jusqu'à ffccbf0 (commits 2a02cbd..ffccbf0 + bde2324, f42f372, b9d6945, 344f653). 3976195 (rapport securite passe 2, docs seules) est arrivé pendant la relecture : lu, non relu comme code. Relecteur indépendant : je n'ai pas participé à l'implémentation. Rapports devops lus mais NON crus : tout ce qui suit est confronté au code.

## Verdict : APPROUVÉ SOUS RÉSERVES

B1 et I1-I4 sont résolus [VÉRIFIÉ à la lecture et sur SQLite]. Aucun bug avéré. Une réserve importante : la nouvelle migration 120300 exécutera, au premier démarrage sur TiDB vierge, du DDL de suppression puis recréation de clés étrangères qui n'a jamais tourné nulle part et qu'aucun test ne couvre. Elle est correcte à la lecture, mais elle n'est pas atomique.

## Exécution

`composer run check` (branche, /home/user/billeteries, 10 s) :
```
OK (8 tests, 9 assertions)
OK (182 tests, 7425 assertions)
OK (40 tests, 2042 assertions)
```

Les essais suivants ont été faits dans un worktree jetable du scratchpad (vendor copié par `cp -a`), supprimé ensuite. La branche n'a pas été touchée.

**Chaîne réelle** (APP_ENV=production, fichier SQLite neuf) :
- `migrate --force` : 40 migrations passent, 120300 comprise.
- `demo:seed` : admin, catégories et commissions créés. Second passage : « rien à faire ».
- config:cache, route:cache et view:cache renvoient OK.
- HTTP (`php -S`) : `/up`, `/`, `/events`, `/login` et `/pricing` répondent 200.

**`app:normalize-key --print`**, appelée comme le fait l'entrypoint :
- base64 sans préfixe : sortie de 51 caractères `base64:…`, identique à la valeur attendue ;
- avec préfixe : inchangée ;
- 32 caractères bruts : inchangée ;
- « bad » : rc=1, message sur stderr sans la valeur.

**SQL MySQL généré par le Blueprint de 120300**, compilé sans serveur avec MySqlGrammar :
```
alter table `events` drop foreign key `events_promoteur_id_foreign`
alter table `events` drop index `events_promoteur_id_foreign`
alter table `events` add constraint `events_promoter_id_foreign` foreign key (`promoter_id`) references `users` (`id`) on delete cascade on update no action
```
La syntaxe est valide sur MySQL 8 et TiDB, et l'ordre de compilation est conservé (`Blueprint::toSql`).

**Mutations :**

| Mutation | Résultat |
|---|---|
| 134010 remis à la version d'origine (B1) | MigrationChainTest ROUGE, 6/8. Erreur SQLite à 195142 : index `commissions_promoteur_id_status_index` sur colonne supprimée. Sur MySQL, ce serait 1826 dès 134010. |
| up() de 120000 vidé (I4) | ROUGE |
| up() de 120200 vidé (paid_by) | ROUGE |
| up() de 120300 vidé | **VERT** : 120300 n'est couverte par aucun test (SQLite ne nomme pas ses FK, chemin sans effet). |
| DB_SSL_VERIFY true vers false | ROUGE (le Mineur précédent est comblé) |

**Non disponibles** : MySQL, MariaDB, TiDB, Docker, Apache.

## 1. Simulation de la chaîne sur MySQL 8 / TiDB 8.5 vierge (lecture, ordre de tri des noms)

Rappels utilisés :
- Laravel 12 compile chaque `add` en un `ALTER` séparé (MySqlGrammar.php:311), donc pas de multi-schéma TiDB.
- `renameColumn` donne `RENAME COLUMN` : la version annoncée `8.0.11-TiDB-…` n'est pas inférieure à 8.0.3 (MySqlGrammar.php:340).
- `Schema::defaultStringLength(191)` (AppServiceProvider.php:31) : aucun index ne dépasse 3072 octets.
- Toutes les FK passent par `foreignId` (BIGINT UNSIGNED, comme `id()`) : pas d'erreur 3780. Exception : telescope, en char(36) vers char(36).

Suivi de `promoteur_id` vers `promoter_id`, avec les noms des contraintes.

| Étape | events | commissions | commission_settings |
|---|---|---|---|
| 141615 / 141634 / 141803 | col `promoteur_id`, FK + index implicite `events_promoteur_id_foreign` | col `promoteur_id`, FK + index `commissions_promoteur_id_foreign` | idem `commission_settings_promoteur_id_foreign` |
| 122001 | — | RENAME vers `promoter_id` (FK et index gardent l'ancien nom) ; `MODIFY status` ENUM avec `cancelled` au milieu (colonne non indexée) | — |
| 134010 (corrigée, l.18-19) | — | `$needsLegacy` = false (promoter_id existe) : pas de colonne, pas de FK, pas d'index composite promoteur. `notes` (after paid_at, qui existe), `commissions_status_index`, `commissions_created_at_index`, gardés par `hasIndex` | — |
| 195119 / 195136 | RENAME (FK `events_promoteur_id_foreign` conservée) | — | RENAME (FK conservée) |
| 195133 | — | renommage sauté ; `MODIFY status` identique (métadonnées seulement) ; `commissions_promoter_id_status_index` créé. Sur MySQL, l'index implicite de la FK peut alors être supprimé silencieusement (comportement InnoDB documenté) | — |
| 195142 | — | bloc 1 sauté (pas de `promoteur_id`) ; `getForeignKeys` trouve la FK sur `['promoter_id']` : pas de 2e FK. Si l'inspection échoue, `true`, donc rien. Si elle renvoie `[]`, ajout de `commissions_promoter_id_foreign` (nom libre : pas de 1826) | — |
| 120300 | drop FK, drop index (présent), add `events_promoter_id_foreign` | drop FK, drop index seulement si encore listé (MySQL : probablement absent ; TiDB : présent), add `commissions_promoter_id_foreign` (soutenu par l'index composite) | drop FK, drop index, add `commission_settings_promoter_id_foreign` |
| État final attendu | `promoter_id` seul, noms `promoter` uniquement | idem + `commissions_promoter_id_status_index` | idem |

**Gardes de 120300 :**
- `getIndexes` est relu avant chaque table.
- La 2e boucle revérifie `hasIndex` : un nom périmé n'est pas supprimé deux fois.
- Si l'inspection échoue, rien n'est fait.
- Cohérence avec 195142 : si `getForeignKeys` ne renvoie rien (cas improbable), 195142 ajoute une FK redondante et 120300 ne fait rien. Pas d'échec, mais il reste des noms promoteur.

**Peuvent échouer (doute) :**
- **120300** [SUPPOSÉ OK] : `DROP INDEX` d'un index présent et non requis par une autre FK, après `DROP FOREIGN KEY` ; conforme à la doc TiDB foreign-key.md (« delete the foreign key first »). Probabilité faible, mais aucun essai réel, et l'opération n'est pas atomique (voir I1).
- **122001 / 102806, ENUM inséré au milieu sur TiDB** [SUPPOSÉ OK] : réorganisation acceptée d'après la source lue lors de la 1re relecture ; tables vides.
- **122001 / 195119 / 195136, RENAME COLUMN d'une colonne à FK sur TiDB** [SUPPOSÉ OK].
- **021012 / 100326, `MODIFY … ENUM` brut en fin de liste** (chemin `mysql`, donc exécuté sur TiDB) : valeurs `available, sold, used, cancelled, reserved, processing`, cohérentes avec le code (CheckoutService:446 `reserved`). La colonne devient NULL-able : comportement d'origine, sans risque.

**Aucun autre échec trouvé :**
- toutes les FK ciblent des tables déjà créées ;
- `after()` toujours sur des colonnes existantes, ou ajoutées juste avant par un ALTER séparé ;
- aucun `dropColumn` de colonne indexée sur le chemin vierge ;
- aucun `change()` sur une colonne à FK ;
- `users.customer_code` porte unique + index sous deux noms distincts (toléré) ;
- toutes les tables ont une clé primaire.

## 2. I4 et migrations additives

- **120000.** Toutes les colonnes que le code lit ou écrit sont présentes :
  - `used_at` (Ticket::markAsUsed:291, Admin\TicketController:192, 232) ;
  - `qr_code_path` ;
  - `validation_data`, `holder_*`, `order_item_id` (FK BIGINT UNSIGNED vers order_items, nullOnDelete) ;
  - index nommés explicitement et gardés.

  Les scanners (Promoteur\ScannerController:123, TicketVerificationController:345) n'écrivent que `status`. Chaque ajout est idempotent (`hasColumn` / `hasIndex`).
- **120100** (`event_audit_logs`) : types et index corrects ; le `$fillable` du modèle est couvert par le test « chaque modèle ». Idempotent (`hasTable`).
- **120200** (`commissions.paid_by`) : FK nullable vers users, nullOnDelete. Idempotent.

## 3. Tests

- **MigrationChainTest.** Il prouve :
  - la chaîne réelle complète sur SQLite ;
  - le schéma final face au code et aux `$fillable` / `$casts` ;
  - l'absence de `promoteur_id` ;
  - l'idempotence par rejeu ;
  - l'écriture de `used_at` ;
  - les gardes de pilote.

  Il ne prouve rien de propre à MySQL/TiDB, et il le dit lui-même (l.14-17, l.114). Le rapport devops est honnête sur ce point : je confirme la mutation « 6 tests sur 8 rouges ». Seule omission : il ne dit pas que 120300 est entièrement non testée (mutation verte).
- **AppKeyNormalizationTest** : couvre les formes valides et invalides, l'absence de la valeur dans les messages et le déchiffrement réel avec la clé normalisée.
- **RenderDeploymentTest** : 2 assertions remplacées (`composer install --no-dev …` et `--with-freetype …`), adaptées au nouveau Dockerfile, pas affaiblies. Aucun `skip`.

## 4. Docker / entrypoint / Apache

- **Dockerfile** :
  - multi-stage : Composer `--ignore-platform-reqs`, puis vérification des extensions dans l'image finale (l.41, `exit 1` si une manque) ;
  - base `php:8.3-apache-bookworm` ;
  - `install-php-extensions gd …` : gd est configuré avec freetype/jpeg/webp par défaut par mlocati [SUPPOSÉ, non exécuté] ;
  - `package:discover` sans dev.
- **Entrypoint** :
  - ordre : variables (noms seulement), normalisation APP_KEY, port, dossiers, migrate (l'échec stoppe), storage:link, demo:seed (avant config:cache), caches, puis schedule:work et Apache, avec `wait -n` ;
  - transmission de la clé : `NORMALIZED_APP_KEY="$(art app:normalize-key --print)"` capturé par le shell, contrôle de forme (`base64:*` ou exactement 32 caractères), puis `export APP_KEY`, puis `unset`. Jamais en echo ni en log, pas de `set -x` [VÉRIFIÉ] ;
  - `display_errors=Off` (php.ini, CLI comprise) : pas de bruit dans la capture.
- **Apache** :
  - `Listen ${PORT}` et `<VirtualHost *:${PORT}>`, PORT exporté avant `apache2-foreground` ;
  - `a2enmod rewrite headers` ;
  - `<IfModule mod_php.c>` : nom du fichier source du module PHP 8 [SUPPOSÉ exact] ;
  - `php_admin_flag engine off` valide dans `<Directory>` (mod_php).
- **Mineur M1** [SUPPOSÉ fort] : `php_admin_value disable_functions` (docker/apache-render.conf:54) est très probablement **sans effet**. Le manuel PHP précise que cette directive « must be set in php.ini … you cannot set this in httpd.conf » : les fonctions sont désactivées une seule fois, au démarrage du module. Ce n'est pas bloquant (Apache démarre), mais c'est une fausse protection, et le test l.287 l'entérine. À trancher par securite, qui l'a jugée « valide ».

## 5. Guide (`docs/deploiement-render.md`)

Parcours novice sur téléphone :
- fusion d'abord (l.10-11) ;
- TiDB (l.13-31) : base `billetterie`, utilisateur avec préfixe, mot de passe, accès 0.0.0.0/0 ;
- Blueprint ;
- tableaux de variables conformes à render.yaml (toutes les clés sont présentes) ;
- APP_KEY : clé de la conversation, ou 32 caractères depuis le générateur du téléphone ;
- C.0 : DEMO_SEED à false.

Le dépannage est honnête (l.131 : « JAMAIS exécutés … sur MySQL ni sur TiDB »). Aucun secret ni donnée réelle.

- **Mineur M2** (l.137) : « la clé étrangère garde son ancien nom, ce qui est normal » contredit 120300, qui renomme ces clés au premier démarrage. 120300 manque aussi dans la liste des points à risque (l.133-137), comme dans le rapport d'audit (§ risques résiduels).
- **Mineur M3** [SUPPOSÉ] : DEMO_SEED vaut `"true"` en dur dans render.yaml. Une resynchronisation du Blueprint pourrait rétablir `true` après le passage manuel à `false`. Le risque est faible : demo:seed ne fait rien si `users` n'est pas vide (DemoSeed.php:30).

## 6. Portée et hors périmètre

- Les fichiers touchés sont conformes au brief.
- Les seules suppressions sont `bootstrap/cache/{packages,services}.php`, déjà validées.
- Aucun correctif de sécurité antérieur n'est régressé : les 182 tests de paiement sont verts.

Qualification du hors périmètre :

| Constat | Démarrage cassé ? | Fonction visible cassée ? |
|---|---|---|
| `Ticket::$fillable` sans used_by/cancelled_by/cancelled_at/admin_notes (Admin\TicketController:187-303, 377-399) | non | à moitié : les écritures sont ignorées sans erreur (annulation sans date, notes perdues). De plus, `update` valide `status=pending` (l.178), valeur absente de l'ENUM : erreur SQL stricte, affichée « Erreur lors de la mise à jour ». |
| 5 clés de journal `promoteur_id` (CheckoutController:234, Promoteur\ReportsController:54, SalesController:80 et 467, EmailService:118) | non | non (cosmétique, journaux uniquement) |
| `SettingsController::backup` (l.237-262) : `exec` mysqldump avec mot de passe en argument | non | oui : mysqldump est absent de l'image, la sauvegarde admin échoue. Sécurité : F5 de securite. |
| `OPTIMIZE TABLE` (SettingsController:341) | non | oui sur TiDB [SUPPOSÉ] : fonction d'optimisation admin en erreur |
| `DemoSeed` journalise ADMIN_EMAIL (DemoSeed.php:63) | non | non (adresse du propriétaire dans les journaux Render ; Mineur) |

## Ce qui pourrait encore faire échouer le PREMIER démarrage (probabilité décroissante)

1. **Erreur de saisie** : APP_KEY de mauvaise longueur, utilisateur TiDB sans préfixe, `CREATE DATABASE` oublié, ADMIN_PASSWORD de moins de 12 caractères, PR non fusionnée. Messages clairs dans l'entrypoint et le guide.
2. **Connexion TiDB** : TLS/CA, endpoint public, 0.0.0.0/0. Options PDO correctes ; non éprouvé.
3. **Migration 120300** : DDL drop/recreate de 3 FK, jamais exécuté. Faible probabilité, non atomique.
4. **ENUM au milieu (122001, 102806) et RENAME COLUMN de colonnes à FK sur TiDB** : source lue, non exécuté.
5. **Construction Docker** (tags, réseau, mlocati) ou délai de démarrage Render (environ 150 DDL TiDB avant l'ouverture du port) : faible.

## Bloquants

Aucun.

## Importants

**I1, `database/migrations/2026_11_11_120300_rename_promoteur_constraints_to_promoter.php:44-54`.** Suppression de la FK puis recréation, en DDL non atomique et non testé. Si le `DROP INDEX` ou l'`ADD CONSTRAINT` échoue, le conteneur s'arrête au premier démarrage. Pire : la FK est déjà supprimée, et au redémarrage la migration ne la voit plus et ne la recrée jamais. Ce chemin s'exécute bien sur une base vierge. Contrairement à ce qu'écrit securite passe 2 §4, les 3 FK « promoteur » existent après les renommages.

- **Correctif** (devops), pour qu'une FK existe toujours :
  1. `index('promoter_id', '<table>_promoter_id_foreign')` si aucun index ne commence par `promoter_id` ;
  2. `foreign('promoter_id', '<table>_promoter_id_foreign')` ;
  3. `dropForeign(<ancien>)` ;
  4. `dropIndex(<ancien>)` s'il est listé.

  Le tout est entouré d'un `try/catch (\Throwable)` qui journalise un avertissement : un renommage cosmétique ne doit jamais bloquer le démarrage.
- **Attention :** l'option 1 de securite (« ajouter la nouvelle FK d'abord, puis supprimer l'ancienne ») sans index explicite échouerait sur MySQL. La nouvelle FK réutiliserait l'index `…promoteur…`, puis `DROP INDEX` serait refusé (erreur 1553).
- **Test** (qa-tests) : compiler le Blueprint avec MySqlGrammar, comme dans cette relecture, et vérifier l'ordre et les noms. Mentionner 120300 dans la liste des risques du guide (l.133-137).

## Mineurs

- **M1** : `disable_functions` probablement sans effet (§4). Exécutant : securite/devops.
- **M2** : guide l.137 et rapport d'audit à aligner sur 120300. Exécutant : devops.
- **M3** : resynchronisation du Blueprint et DEMO_SEED (§5). Exécutant : devops.
- **M4** : ADMIN_EMAIL écrit dans les journaux (DemoSeed.php:63).
- **M5** : le rapport d'audit annonce « 37 fichiers lus (36 exécutables + 1 .back) ». Le dépôt en compte 41 (40 exécutables + .back), ajouts compris : formulation ambiguë.

## Suggestions

- **S1** : lancer une seule fois `migrate:fresh --force` sur un TiDB Starter jetable (5 minutes depuis un ordinateur) avant de transmettre le guide. C'est la seule preuve qui manque.

## Non vérifié

- Construction Docker, démarrage Render, connexion TLS TiDB.
- `migrate` sur MySQL ou TiDB : aucun serveur disponible.
- Comportement réel de 120300.
- Options gd de mlocati.
- `IfModule mod_php.c`, `disable_functions` en httpd.conf.
- Comportement de la resynchronisation du Blueprint Render.
- Libellés TiDB, Render et `0.0.0.0/0`.

## Recommandation

Fusion possible au choix du propriétaire. De préférence, corriger I1 (petit changement devops, plus un test) avant le premier déploiement, puis faire une relecture ciblée de 120300 uniquement.
