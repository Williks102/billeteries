# Relecteur : branche `claude/deploiement-render` (déploiement de démo Render + TiDB)

Base : origin/main 4bc4ffd. Commits relus : df36b1f, 395b377, bc416bc, 917b271 (a253911 = rapport securite, non relu). Relecteur indépendant : je n'ai pas participé à l'implémentation.

## Verdict : REFUSÉ

Le code de la branche est propre et `check` est vert. Mais le but de la mission, un premier démarrage réussi sur TiDB vierge, n'est pas atteint : une migration existante échoue de façon déterministe sur MySQL/TiDB (B1). Le guide oriente vers une autre cause (les ENUM) et affirme à tort que les migrations ont déjà tourné sur MySQL.

## Exécution

`composer run check` (branche, /home/user/billeteries) :
```
OK (8 tests, 9 assertions)
OK (182 tests, 7425 assertions)
OK (21 tests, 1851 assertions)
```

Simulation de production dans un worktree jetable (vendor copié par `cp -a`, puis supprimé) :
- `composer install --no-dev --no-scripts` suivi de `rm bootstrap/cache/*.php` et `composer dump-autoload --no-dev` : OK sans `.env`. packages.php et services.php régénérés ne contiennent ni Telescope ni Debugbar (0 occurrence).
- `APP_ENV=production APP_DEBUG=false` : config:cache, route:cache, view:cache et route:list renvoient 0. `demo:seed` est enregistrée. Aucune commande telescope ni debugbar.
- `migrate --force` sur SQLite : échoue comme attendu (index à 2025_08_02_195142, puis `MODIFY` des 3 migrations ENUM, propres à MySQL). Après contournement manuel dans la base jetable :
  - 1er `demo:seed` : 1 admin, 10 catégories, 6 commission_settings à 6 %.
  - 2e `demo:seed` : « rien à faire ».
  - HTTP (`php -S`) : `/up`, `/`, `/events`, `/login` et `/pricing` répondent 200, catégories affichées.
- Mutations (worktree jetable) :
  - `<` remplacé par `<=` sur la longueur du mot de passe : rouge.
  - Garde d'idempotence retirée : rouge.
  - `DB_SSL_VERIFY=true` vers `false` dans config/database.php : VERT (non couvert, voir Mineurs).
- Docker, TiDB et Render : non exécutés. Pas de démon Docker. MySQL, MariaDB et TiDB introuvables (apt et tiup refusés par le proxy ; l'exécution d'un mysqld téléchargé a été refusée par la politique).

## Bloquant

**B1 [VÉRIFIÉ par lecture, chemin confirmé sur SQLite, doc et source TiDB ; non exécuté sur MySQL/TiDB] : le premier `migrate` échoue sur une base MySQL ou TiDB vierge.**

Code préexistant, hors diff, mais il ruine l'objectif de la branche. Enchaînement :
1. `2025_07_12_141634_create_commissions_table.php:17` crée `promoteur_id` avec la FK `commissions_promoteur_id_foreign`.
2. `2025_07_28_122001_fix_commissions_table_structure.php:15` renomme la colonne en `promoter_id`. La FK garde son nom ; TiDB `updateFKInfoWhenModifyColumn` ne change que les colonnes.
3. `2025_07_29_134010_fix_commissions_table_structure.php:16-18` voit `promoteur_id` absente. Il la recrée et ajoute `foreign('promoteur_id')`, nom par défaut `commissions_promoteur_id_foreign`.
4. Résultat : `ERROR 1826 Duplicate foreign key constraint name`. La doc TiDB foreign-key.md, ligne 39, le dit explicitement (« The foreign key name must be unique in the current table »). MySQL 8 réagit de même.

Preuve du chemin : sur SQLite, après migrate, `commissions` contient À LA FOIS `promoter_id` et `promoteur_id`. Le conteneur s'arrête alors avec « php artisan migrate a échoué ».

Correctif : rendre `2025_07_29_134010` idempotent pour une base vierge. Il faut entourer l'ajout de colonne et de FK (l.16-19) ET l'index `['promoteur_id','status']` (l.31-33) d'une condition `! Schema::hasColumn('commissions', 'promoter_id')`. Sur une base existante, la migration a déjà tourné et le changement est sans effet. À noter aussi : les `try/catch` de `2025_08_02_195142` (l.16-20, 31-35) sont inopérants, car les commandes du Blueprint s'exécutent après la closure.

Exécutant : devops (ou architecte). Il faut ensuite tenter `migrate:fresh` sur un vrai MySQL 8 ou TiDB avant de donner le guide au propriétaire.

## Importants

- **I1 `render.yaml:9` `branch: main`** et `docs/deploiement-render.md:34` « choisissez la branche à déployer ». Le service construit toujours `main`. Avant la fusion de cette PR, `main` n'a ni Dockerfile ni render.yaml, donc le déploiement échoue. Correctif : écrire dans le guide « fusionner la PR dans main AVANT de créer le Blueprint ». Exécutant : devops.
- **I2 `docs/deploiement-render.md:76-83`, APP_KEY impossible à générer depuis un téléphone.** `key:generate` et `openssl` supposent un ordinateur, et « terminal en ligne de confiance sur votre propre machine » se contredit. Correctif au choix :
  - (a) dans l'entrypoint, accepter une clé sans préfixe en préfixant `base64:` si la valeur se décode en 32 octets, et passer APP_KEY en `generateValue: true`. [SUPPOSÉ] Render génère une valeur base64 de 256 bits ; doc Render inaccessible ici, à confirmer.
  - (b) proposer Termux (Android), a-Shell (iOS) ou un Codespace GitHub dans le navigateur.

  Exécutant : devops.
- **I3 `docs/deploiement-render.md:114-120`, dépannage des migrations inexact.**
  - « Les migrations n'ont JAMAIS été exécutées sur TiDB, seulement sur SQLite et MySQL » est faux. Sur SQLite, migrate échoue (connu). Sur MySQL, rien n'a jamais tourné : le BOARD et les rapports disent « test:mysql JAMAIS exécuté ».
  - La liste oublie les deux `->change()` d'ENUM sur `commissions.status` qui insèrent `cancelled` au milieu : `2025_07_28_122001:25` et `2025_08_02_195133:22`.
  - Sur TiDB, l'insertion au milieu (`orders.payment_status`, `2025_09_11_102806:11`) et l'ajout en fin (tickets) sont acceptés avec réorganisation en 7.5 et 8.5. `CheckModifyTypeCompatible` renvoie `canReorg=true` et `checkModifyTypes` n'échoue que sur une clé primaire. [VÉRIFIÉ par lecture de la source, non exécuté.] Le risque ENUM est donc faible. Le vrai risque est B1.
  - La phrase interne « Le décompte initial annonçait quatre… » n'a rien à faire dans un guide pour novice.

  Correctif : réécrire le paragraphe après B1. Exécutant : devops.
- **I4 (préexistant, hors périmètre) : dérive de schéma sur base vierge.** `2025_07_25_003819_add_missing_columns_to_tickets_table.php.back` n'est jamais exécutée. Sur une base neuve, `tickets` n'a donc ni `used_at`, ni `qr_code_path`, ni `validation_data`, ni `holder_*`, ni `order_item_id`. Or `Ticket::markAsUsed` (app/Models/Ticket.php:291-295) écrit `used_at`, qui fait partie de `$fillable`. Le scan d'un billet produira « Unknown column used_at », étape C.7 du guide. Les tests ne le voient pas car ils construisent leur propre schéma (tests/Concerns/BuildsOrderSchema.php:132-136). Correctif : nouvelle migration additive, gardée par `hasColumn`. Exécutant : architecte ou paiements-stock.

## Ce qui ferait échouer le PREMIER démarrage (par ordre de probabilité)

1. B1, la FK en double (quasi certain).
2. `Dockerfile:15` `libfreetype6-dev`. `php:8.3-apache` est basé sur Debian trixie et la doc officielle de l'image utilise `libfreetype-dev`. [SUPPOSÉ] Le paquet transitionnel `6-dev` n'existe peut-être plus en trixie, et l'image ne se construirait pas. Remplacer par `libfreetype-dev` (devops), sans risque.
3. I1 si le Blueprint est créé avant la fusion.
4. TLS : options PDO correctes (`MYSQL_ATTR_SSL_CA` plus `SSL_VERIFY_SERVER_CERT`, constantes valides en PHP 8.3, `false` conservé par `array_filter`) et CA Debian présent. Non éprouvé contre TiDB.

## Vérifié conforme

- **Entrypoint**
  - `set -eu`, vérification des 6 variables sans afficher leur valeur.
  - Ordre : migrate, storage:link, demo:seed (avant config:cache, nécessaire puisque DemoSeed lit `env()`), puis config, route et view:cache.
  - `runuser -u www-data` conserve l'environnement ; storage et bootstrap/cache appartiennent à www-data.
  - `wait -n` sur deux PID (bash 5.2 sous trixie), trap TERM/INT, sortie non nulle si un processus meurt.
- **Apache** : `Listen ${PORT}` et `<VirtualHost *:${PORT}>` (variable d'environnement exportée avant `apache2-foreground`), DocumentRoot `/public`, `AllowOverride All`, `public/.htaccess` présent.
- **Dockerfile** : extensions exigées par le lock (`check-platform-reqs --no-dev --lock` : gd, zip ; le reste est natif à l'image), `COPY --from=composer:2`, cache de couches correct.
- **render.yaml** : cohérent avec .env.example (test dédié), aucun secret ; TRUSTED_PROXIES lu par bootstrap/app.php à chaque requête.
- **DemoSeed** :
  - idempotente, mot de passe jamais affiché, `role` bien fillable, transaction ;
  - n'appelle pas DatabaseSeeder (aucun compte faible) ;
  - commissions à 6 %, `config/payments.php:80` vaut 6 par défaut.
- **Quotas TiDB** (5 Gio, 50 M RU par mois) : conformes à la doc pingcap (`tidb-cloud/select-cluster-tier.md:37-39`).
- **Hygiène** : 917b271 retire packages.php et services.php ; `bootstrap/cache/.gitignore` (`*`) les ignore désormais ; aucun fichier hors périmètre.

## Mineurs

- **Test faible sur DB_SSL_VERIFY** : `tests/Feature/RenderDeploymentTest.php:207-220` ne teste que l'absence d'options, et la mutation true vers false reste verte. Ajouter un test qui positionne `DB_SSL_VERIFY=true`, `DB_SSL_CA=x` et recharge la config.
- **Tableau des variables incomplet** : `docs/deploiement-render.md:57-74` omet APP_NAME, APP_LOCALE, APP_FALLBACK_LOCALE, SESSION_LIFETIME, FILESYSTEM_DISK, MAIL_FROM_* présents dans render.yaml. L'option 2 (manuelle) ne donne pas les valeurs des MAX_* (défauts identiques dans config, donc préciser « facultatives »).
- **Réveil plus lent** : chaque réveil après veille relance migrate et les caches. Le réveil réel sera plus long que les « 30 à 50 secondes » annoncées.
- **Mémoire** : `docker/apache-render.conf:6` 10 workers × `memory_limit=256M` dépasse 512 Mo dans le pire cas (peu probable en démo).

## Non vérifié

- Construction Docker, démarrage Render et connexion TiDB réels.
- `migrate:fresh` sur MySQL ou TiDB : aucun serveur disponible.
- Noms de champs Blueprint (`runtime`, `autoDeploy`, `region: frankfurt`, `sync: false`) et format de `generateValue` : documentation Render inaccessible (proxy).
- Existence de `libfreetype6-dev` en trixie.
- Libellés de l'interface TiDB et accès public `0.0.0.0/0` sur Starter.
- Affirmation du rapport devops « 215 routes vers 208 sans dev » : non recomptée.

## Recommandation

Corriger puis relire. D'abord B1 et le paquet freetype, puis I1 à I3 (devops). Ensuite, exécuter au moins une fois `migrate:fresh` sur MySQL 8 ou une instance TiDB jetable avant de transmettre le guide. I4 à planifier (architecte).
