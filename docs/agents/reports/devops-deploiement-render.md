# DevOps : déploiement de démo Render + TiDB Cloud Starter

Branche `claude/deploiement-render` (depuis origin/main 4bc4ffd). Guide utilisateur : `docs/deploiement-render.md`.

## Livré
- `Dockerfile` (php:8.3-apache, gd freetype/jpeg/webp, pdo_mysql, bcmath, zip, intl, exif, opcache ; `composer install --no-dev --optimize-autoloader --no-interaction --no-scripts` puis `composer dump-autoload --no-dev`), `.dockerignore`, `docker/{entrypoint.sh,apache-render.conf,php.ini}`.
- `render.yaml` : web docker, free, frankfurt, `/up`, autoDeploy main ; secrets en `sync: false` sans valeur ; TRUSTED_PROXIES="*" commenté.
- `config/database.php` (connexion mysql uniquement) : DB_SSL_CA (repli MYSQL_ATTR_SSL_CA), DB_SSL_VERIFY ; vides = aucune option.
- `php artisan demo:seed` (app/Console/Commands/DemoSeed.php) : idempotent (rien si users non vide), ADMIN_EMAIL/ADMIN_PASSWORD lus dans l'environnement, refus si mot de passe < 12 caractères, transaction, jamais de mot de passe affiché.
- Tests : tests/Feature/RenderDeploymentTest.php (13), DemoSeedCommandTest.php (8), script `composer run test:deploy` intégré à `check`.

## Constats
- `bootstrap/cache/packages.php` et `services.php` sont VERSIONNÉS et générés avec les paquets de dev. Copiés tels quels dans une image `--no-dev`, ils référenceraient Telescope/Debugbar absents. Le Dockerfile les supprime avant `dump-autoload`, `.dockerignore` les exclut. Correction durable (hors périmètre) : `git rm --cached bootstrap/cache/*.php` ; `storage/debugbar/.gitignore` aussi.
- Migrations : seulement 3 fichiers (et non 4) exécutent `ALTER TABLE … MODIFY COLUMN … ENUM` dans `up` (2025_09_11_021012, 2025_09_11_100326 `.php.php`, 2025_09_11_102806). Jamais exécutées sur TiDB.
- Verrous `lockForUpdate` (11 occurrences dans app/) non éprouvés sur TiDB (`composer run test:mysql` vise MySQL).
- Le décompte de routes passe de 215 (avec dev) à 208 (sans dev) : 7 routes de paquets de dev absentes en production, attendu.

## Mise à jour : corrections de la relecture REFUSÉ (B1, I1-I4, M1, M2, F1-F4)

- B1 : `2025_07_29_134010` ne recrée plus `promoteur_id` si `promoter_id` existe ; `195142` n'ajoute plus de FK redondante ; migration 120300 renomme les FK/index « promoteur ». Audit complet : `docs/agents/reports/devops-audit-migrations.md`. Test de chaîne SQLite : `tests/Feature/MigrationChainTest.php` (dans `composer run check`).
- I4 : migrations additives gardées (colonnes de `tickets`, table `event_audit_logs`, `commissions.paid_by`).
- Dockerfile : base `php:8.3-apache-bookworm`, multi-stage (Composer/git/unzip hors image finale), `install-php-extensions` ; plus de `libfreetype6-dev`.
- I2 : `php artisan app:normalize-key` appelée par l'entrypoint (préfixe `base64:` ajouté, 32 caractères bruts acceptés, valeur jamais affichée).
- I1, I3, M1, M2, F4 : guide réécrit (fusion avant Blueprint, APP_KEY, DEMO_SEED=false dès le premier démarrage, mails en clair dans les journaux, TLS, migrations jamais éprouvées sur MySQL/TiDB). `DEMO_SEED` reste `true` dans render.yaml (création simple de l'admin), justification dans le fichier.
- F2/F3 : en-têtes nosniff/HSTS sur statiques, pas de PHP dans public/storage, `disable_functions` côté Apache seulement (`exec` conservé : `SettingsController::backup`).
- Non vérifié : docker build, Render, TiDB, MySQL réel, syntaxe Apache (aucun apache2 local).
