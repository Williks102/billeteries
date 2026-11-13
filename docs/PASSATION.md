# Passation du projet Billetterie

Document de reprise pour démarrer une nouvelle conversation sans relire l'ancienne (la relecture d'un long historique coûte du quota). Il ne contient **aucun secret** : seulement des noms de variables. À lire avec `docs/agents/decisions.md` (décisions du propriétaire, source de vérité) et `docs/agents/BOARD.md` (journal des missions).

## 1. Le projet en deux phrases
Billetterie en ligne pour la Côte d'Ivoire : Laravel 12, PHP 8.3, vues Blade et Bootstrap 5.3. Rôles : acheteur, promoteur (organisateur), admin. Dépôt `williks102/billeteries`, public. Démo sur Render (Docker, offre gratuite) avec une base TiDB Cloud (compatible MySQL, TLS). Guide de déploiement : `docs/deploiement-render.md`.

## 2. Le propriétaire et la façon de travailler
- Français, réponses **concises**, travaille **depuis un téléphone** (renvoie des captures d'écran : elles ont révélé les défauts les plus réels). Premier projet « vibe code » : expliquer simplement, sans jargon inutile.
- **Un chantier à la fois**, une branche `claude/<mission>` et une PR par mission. **Il relit et fusionne lui-même les PR** ; on ne commite jamais sur `main`.
- Il accepte les recommandations par défaut (« ok pour tes recommandations ») : ne poser une question que pour une vraie décision.
- **Quota limité** : il a déjà atteint la limite d'usage. Préférer des lots serrés, regrouper les corrections, ne lancer la relecture indépendante que sur les lots sensibles (argent, stock, sécurité, données personnelles).
- **Pas de GitHub Actions** (compte bloqué par la facturation) : les contrôles sont locaux : `composer run check` et le hook `.githooks/pre-push`.

## 3. Règles du dépôt (à respecter)
- Contrôle avant toute PR : `composer run check` (**pas** `composer check`) puis `php artisan route:list`. Il enchaîne `test:routes`, `test:payments`, `test:stats`, `test:deploy`. État connu : tout vert (236 + 250 + 268 + 54 tests sur la dernière branche, 200 routes).
- **Aucun secret dans le dépôt** (public) : ni mot de passe, ni clé d'application, ni adresse de base, ni e-mail personnel. Les secrets vont dans les variables d'environnement de Render. Les variables attendues sont listées dans `.env.example` et `render.yaml` (les valeurs sensibles sont `sync: false`).
- Convention de routes « resource » : `RoutingConventionsTest`, `ViewReferencesTest` (`KNOWN_MISSING` est vide : il ne doit pas regrossir), `DeadCodeRemovalTest` (chaque route pointe vers une méthode existante, chaque `route('nom')` des vues existe).
- Aucune mention « frais de service » dans les vues (`NoServiceFeeTest`). Les frais de service sont supprimés : seule la **commission** rémunère la plateforme. Son taux par défaut (6 % au départ) se règle dans l'administration (Paramètres, « Commission plateforme ») et est **enregistré en base** (table `settings`, clé `commission.default_rate`, groupe `payment`) ; source unique `App\Services\CommissionRate::default()`. Valeur de secours (rien en base, valeur invalide ou table illisible) : `DEFAULT_COMMISSION_RATE` / `payments.default_commission_rate` ; les valeurs posées côté Render ne servent donc plus que de secours. Priorité inchangée : réglage promoteur > catégorie > défaut. Le taux est lu à la confirmation du paiement (`Order::createCommission`) : une commande payée garde sa commission (jamais recalculée) ; le changement ne vaut que pour les commandes confirmées ensuite. Le taux 0 est valide. Changement journalisé (`settings.updated`, avant/après effectifs).
- Mobile d'abord (390 px), cibles tactiles ≥ 44 px, champs ≥ 16 px, aucun défilement horizontal, couleurs et espacements par les jetons de `public/css/charte.css`.
- Les vues n'ont ni requête ni calcul : les chiffres viennent des services `app/Services/Stats/*` (garde-fous par tests).
- Pas de force-push (refusé par l'environnement) : fusionner `main` dans la branche et résoudre les conflits (presque toujours dans `docs/agents/BOARD.md` ou `composer.json` : garder les deux côtés). Les PR se fusionnent avec « Squash and merge » quand une branche a un commit intermédiaire.
- Les copies de travail isolées des agents vivent sous `.claude/worktrees/` (ignoré par git).

## 4. Équipe d'agents (`.claude/agents/`, skills dans `.claude/skills/`)
`architecte`, `securite`, `paiements-stock`, `qa-tests`, `ui-design`, `devops-hygiene`, `relecteur`. Tous sur Sonnet, `relecteur` compris (passé d'Opus à Sonnet pour économiser le quota ; le repasser sur Opus pour les lots sensibles). Protocole éprouvé : un développeur (souvent `paiements-stock` pour le backend, `ui-design` pour les vues) → vérification personnelle du chef de projet (tests, routes, absence de fichiers parasites, fusion propre avec `main`) → relecture indépendante (jamais par l'auteur ; lecture seule ; fautes volontaires dans une copie jetable) → corrections regroupées en un passage → PR avec un tableau « avant / après » et les limites écrites honnêtement. Les agents poussent à chaque état vert pour ne rien perdre si le quota coupe : `SendMessage` reprend un agent interrompu.

## 5. Architecture et comportement métier essentiels
**Paiement** : manuel (virement, Mobile Money, espèces), confirmé par l'admin via `OrderPaymentService::confirmPayment` (seul chemin vers `paid` : verrou, idempotence, billets `sold`, QR après commit). PaiementPro est supprimé. Commandes `pending` avec réservation (48 h connecté, 24 h invité, configurable). Transitions : `Order::STATUS_TRANSITIONS`.
**Commandes gratuites** (total serveur = 0) : confirmées automatiquement (référence `GRATUIT`, aucune commission), sans choix de paiement. Plafonds dédiés par IP (empreinte HMAC), e-mail, compte et événement : variables `FREE_ORDERS_*`, `FREE_TICKETS_*`. Limite connue : e-mail non vérifié.
**Stock** : réservation avec `lockForUpdate`, plafonds de commandes en attente par acheteur, IP et événement, preuve de session invité (`guest_token`). Une vente exige un événement publié et non passé (`TicketType::eventAcceptsSales`). Le checkout crée les lignes `tickets` à la réservation (pas de pré-génération).
**QR** : généré en local (bacon-qr-code + GD, data-URI en base), visible **seulement après paiement confirmé**. `php artisan tickets:generate-qr` rattrape les commandes déjà payées.
**Mode démo** : `DEMO_AUTO_CONFIRM_PAYMENTS=true` **et** `DEMO_MODE_ACKNOWLEDGED=true` (les deux, sinon ignoré en production) confirment les commandes automatiquement avec un bandeau « MODE DÉMO ». À retirer après la démo. `DEMO_SEED=true` crée le compte admin au premier démarrage (puis le remettre à `false`).
**Création d'événement** : formulaire unique (`events/_form.blade.php`), `StoreEventRequest`, `CreateEventAction`, `PublishEventAction` + `Event::publicationChecklist()` (publier exige : billet valide, date future, période de vente cohérente, **image**). « Publier » incomplet → brouillon + liste des manques. Fin de vente par défaut = début de l'événement ; changer la date recale les fins de vente (`RealignSalePeriodsAction`). L'admin crée pour un promoteur choisi ; duplication via `DuplicateEventAction` (copie toujours en brouillon, ventes à zéro).
**Statistiques** : source unique `app/Services/Stats/*` + `app/Support/Stats/DatePeriod` (mois civil par défaut, fuseau Africa/Abidjan, fin incluse) + composants `x-stat-card` / `x-stat-grid` / `x-period-filter`. Définitions : revenu plateforme = commission ; revenu promoteur = net des commandes payées ; billet vendu = `sold` + `used` d'une commande payée ; « net à recevoir » = solde toutes dates (pas un flux) ; valeurs non calculables = « — ». Admin, promoteur, acheteur, public et exports sont migrés ; les exports CSV passent par `CsvExport` et `CsvSafe::cell` (anti-injection de formules). Jeu de test de référence : `tests/Concerns/StatsFixture.php`.
**Journal d'activité** (admin seulement, `/admin/activity`, route `admin.activity.index`) : table `activity_logs` en ajout seul (le modèle `ActivityLog` refuse update/delete), écrite par `App\Services\ActivityLogger::record()` qui ne lève jamais, écrit après commit, ne stocke que des identifiants, des valeurs métier et une empreinte HMAC de l'IP. Journalisé : confirmation de paiement (manuelle et automatique), changement de statut, annulation, remboursement, expiration de réservation, création/modification/suppression de billets (prix, capacité, activation, dates), création/modification/publication/dépublication/changement de statut/duplication/suppression d'événements, taux de commission et paramètres généraux, rôle et vérification d'e-mail d'un utilisateur, connexion admin. Volontairement NON journalisé : scan/validation des billets (flux à haut volume, plusieurs points de passage), ventes (`quantity_sold`), paiement des commissions. Détail : `docs/journal-activite.md`.
**Interface** : `public/css/charte.css` (jetons, espaces, `.action-bar`, badges d'état, barres de navigation). Police Inter embarquée. Barre de navigation unifiée sur les 4 layouts.

## 6. État au moment de la passation
- Tout est fusionné dans `main` **sauf la PR #38** (création d'événement partie 2 : création par l'admin et duplication) : le propriétaire doit la fusionner. La branche est à jour avec `main` et tout est vert.
- En attente d'une réponse du propriétaire : (a) chantier **CSP** : lancer l'étape d'observation maintenant (option A) ou le garder pour « avant production » (option B). (Le passage du `relecteur` sur Sonnet est fait.)

## 7. À faire, dans l'ordre recommandé
**Avant la mise en production** (hébergement définitif à choisir par le propriétaire) :
1. **Vérification d'e-mail** (ou captcha) : inscriptions et surtout billets gratuits.
2. **Journal d'activité** : **fait** (branche `claude/journal-activite`, à relire et fusionner). Voir §5 et `docs/journal-activite.md`. `event_audit_logs` est conservée intacte (liée à un événement avec cascade, donc inadaptée comme journal général). Reste : durée de conservation illimitée pour l'instant ; ajout seul appliqué par le code seulement (en production, retirer `UPDATE`/`DELETE` sur `activity_logs` à l'utilisateur SQL de l'application) ; non journalisés : scans de billets, paiement des commissions.
3. **CSP stricte** : la politique actuelle autorise `'unsafe-inline'` pour les scripts (26 vues avec un bloc `<script>`, 91 attributs `onclick`/équivalents dans 32 vues, 51 vues avec `<style>`). Plan : jeton (nonce) par page + mode « rapport seulement », déplacer le JavaScript dans des fichiers, héberger jQuery et Chart.js, puis activer ; les styles en dernier.
4. **Test sur la vraie base MySQL** : `composer run test:mysql` sur une base jetable dont le nom finit par `_test` (concurrence sur les derniers billets, plafonds, verrous). Tout a tourné sous SQLite jusqu'ici.
5. **Réglages de production** : `APP_ENV=production`, `APP_DEBUG=false`, **nouvelle** `APP_KEY` (celle de la démo a transité en clair dans une conversation : à régénérer), `SESSION_SECURE_COOKIE=true`, `TRUSTED_PROXIES` à définir selon l'hébergeur et à tester avec un en-tête `X-Forwarded-For` falsifié (le plafond par IP en dépend ; derrière un CDN il bloquerait tout le monde).
6. **Images persistantes** : le disque de Render est éphémère (images et avatars perdus à chaque déploiement). **Statut : code prêt, activation = actions du propriétaire.** Toutes les images passent par `App\Support\Media` (`config('filesystems.media_disk')`, variable `MEDIA_DISK`, défaut `public` = comportement inchangé) ; disque `r2` (Cloudflare R2, compatible S3) piloté par `R2_*`. Guide pas à pas : `docs/stockage-images.md` (créer le bucket, jeton, 6 variables dans Render, redéployer). Commande de reprise : `php artisan media:migrate-to-r2 --dry-run`. Jamais testé contre un vrai bucket. Garde-fou : `tests/Feature/MediaDiskTest.php` (interdit `disk('public')` et `Storage::url()` dans `app/` et les vues).
7. **Contenu juridique** : CGU, mentions légales, politique de remboursement et d'annulation à fournir par le propriétaire (placeholder `[À COMPLÉTER PAR L'ORGANISATEUR…]`).

**Dépendances et sécurité** (13/11/2026, branche `claude/dependances-securite`) : `composer audit` passait de 45 avis (1 critique, 15 élevés, 16 moyens, 6 faibles, 7 sans gravité ; 11 paquets) à **0 avis**. Mise à jour ciblée dans les contraintes existantes de `composer.json` (inchangé) : `laravel/framework` 12.21 -> 12.72, `symfony/*` 7.3 -> 7.4, `league/commonmark` 2.7 -> 2.10, `dompdf/dompdf` 3.1.0 -> 3.1.6, `phpunit` 11.5.27 -> 11.5.58, `psysh` 0.12.9 -> 0.12.24. Seul ajustement de test : `storage.local.upload` (route PUT signée ajoutée par le framework pour le disque local) ajouté à `ALLOWED_CLOSURES` de `RoutingConventionsTest` ; il y a désormais 190 routes. `cloudinary/*` et `webmozart/assert` (orphelins du verrou, requis par personne) ont disparu. Contrôle régulier : `composer audit` (à relancer avant chaque déploiement).

### Plan sécurité (checklist du « Guide du vibecoder sécurisé »)
Branche `claude/securite-phase1` (13/11/2026) : volets non bloqués de la phase 1. État des 13 points :

| # | Point | État | Détail |
|---|---|---|---|
| 1 | Authentification | Appliqué sauf MFA admin | Limitation de connexion native (5/min), mot de passe oublié limité par IP et par e-mail, réponses neutres (pas d'énumération), échecs de connexion journalisés. MFA admin à faire. |
| 2 | Sessions | Appliqué | |
| 3 | Autorisations | Appliqué | |
| 4 | Validation | Partiel | |
| 5 | Secrets | Partiel | APP_KEY de la démo à régénérer (action du propriétaire, voir `docs/deploiement-render.md`). |
| 6 | XSS | Partiel | Contenu des pages (CMS) nettoyé à l'enregistrement et à l'affichage (`App\Support\HtmlSanitizer`) ; CSP stricte à faire. |
| 7 | Routes et limitation de débit | Partiel | Nouveaux throttles : `PASSWORD_RESET_THROTTLE_PER_MINUTE` (IP) + 3 demandes / 10 min par e-mail. |
| 8 | CSRF | Appliqué | |
| 9 | Uploads | Appliqué | |
| 10 | En-têtes | Partiel | `Permissions-Policy` ajoutée (caméra autorisée pour le scanner), HSTS sans `preload`. CSP à faire. |
| 11 | Dépendances | Appliqué | 0 avis, mises à jour faites ; `composer audit` avant chaque déploiement. |
| 12 | Monitoring | Partiel | Journal d'activité + échecs de connexion ; pas d'alerte ni de suivi d'erreurs. |
| 13 | Sauvegardes | Non vérifié | |

Phases restantes :
1. **Vérification d'e-mail** : **BLOQUÉE** tant que le propriétaire n'a pas choisi un SMTP réel (avec `MAIL_MAILER=log` aucun e-mail n'est livré).
2. **CSP stricte, en 4 lots** : A) nonce + mode « rapport seulement » ; B) sortir le JavaScript inline des 26 vues avec `<script>` et des 91 attributs `onclick` de 32 vues ; C) héberger jQuery et Chart.js localement puis activer `script-src` strict ; D) styles en dernier.
3. **MFA admin**.
4. **Monitoring** (Sentry ou équivalent).
5. **Sauvegardes TiDB** : vérifier la rétention, tester une restauration.
6. **Test sur la vraie base TiDB** (`composer run test:mysql`).
7. **Réglages de production** : `TRUSTED_PROXIES` testé avec un en-tête `X-Forwarded-For` falsifié, nouvelle `APP_KEY`.
8. **Contenu juridique**.

**Autres chantiers** : barre de navigation en bas sur mobile ; messages de validation en français (les formulaires affichent des clés brutes `validation.required`) ; mobile pour les écrans non vus (listes admin, formulaires longs).

**Petites dettes connues** : routes vers des méthodes absentes : seuls les favoris acheteur restent (lien du menu `layouts/acheteur`), listés dans `DeadCodeRemovalTest` ; `resources/js/cart-unified.js` n'est chargé nulle part (appelle l'ancien `/cart/extend-timer`) ; vue d'authentification qui appelle `verification.resend` (route absente) ; l'admin ne peut pas modifier les billets après la création d'un événement et son formulaire d'édition (617 lignes) n'est pas refondu ; prix décimal du formulaire promoteur converti en 0 ; sauvegarde de la base : page Sauvegardes informative (`admin.backups.index`), la sauvegarde réelle = TiDB Cloud (à vérifier dans sa console : fréquence, rétention) ; routes mortes `mysqldump`/`OPTIMIZE TABLE`/nettoyage/vidage de cache supprimées ; `AdminController::resendOrderEmail` et `OrderController::resendEmail` ne renvoient rien (réponse « succès » sans envoi, à câbler ou retirer) ; cloche de notifications retirée du promoteur (aucune fonction derrière).

## 8. Pour démarrer la prochaine conversation
1. Lire ce fichier, `docs/agents/decisions.md` et les dernières lignes de `docs/agents/BOARD.md`.
2. `git fetch` puis partir de `origin/main` pour toute nouvelle branche `claude/<mission>`.
3. Demander au propriétaire la décision en attente (§6) et s'il a fusionné la #38.
4. Pour chaque mission : développeur → `composer run check` + `php artisan route:list` → relecture si lot sensible → PR avec limites honnêtes → le propriétaire fusionne.
