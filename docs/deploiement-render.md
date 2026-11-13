# Déploiement de démo sur Render + TiDB Cloud

Guide pas à pas pour mettre en ligne une démo PROVISOIRE de la billetterie, depuis un téléphone si besoin.

- Hébergement : Render, offre gratuite, déploiement par Docker (Render n'a pas de PHP natif).
- Base de données : TiDB Cloud Starter (compatible MySQL, TLS obligatoire, port 4000).
- Paiement : manuel, confirmé par l'admin. Aucun vrai paiement n'est branché.
- Règle d'or : ne collez JAMAIS un mot de passe ou une clé dans le dépôt ni dans une issue. Saisissez-les uniquement dans les champs « Environment » de Render.

> **AVANT TOUT : fusionnez la Pull Request dans `main` AVANT de créer le service Render.**
> Le fichier `render.yaml` impose `branch: main`, et `main` ne contient ni `Dockerfile` ni `render.yaml` tant que la PR n'est pas fusionnée : le service échouerait dès la construction. Toutes les étapes ci-dessous supposent que la fusion est faite.

## A. Créer la base sur TiDB Cloud

1. Créez un compte sur TiDB Cloud puis cliquez sur « Create Cluster ».
2. Choisissez le plan **Starter** (gratuit), le fournisseur et une région proche de Francfort (par exemple eu-central-1). Donnez un nom au cluster, puis validez.
3. Ouvrez le cluster, puis le bouton **Connect**. Choisissez « Public » comme type de connexion et « General » (ou « MySQL CLI ») comme client.
4. Cliquez sur **Generate Password** (ou « Reset Password ») et copiez le mot de passe dans votre gestionnaire de mots de passe : TiDB ne le réaffiche plus. Ne l'envoyez à personne.
5. Notez les valeurs affichées :
   - Host : ressemble à `gateway01.<région>.prod.aws.tidbcloud.com` (variable DB_HOST)
   - Port : `4000` (déjà fixé dans le Blueprint)
   - User : ressemble à `xxxxxxxx.root` : le préfixe fait partie du nom (variable DB_USERNAME)
   - Password : le mot de passe de l'étape 4 (variable DB_PASSWORD)
6. Autorisez l'accès public : dans « Security Settings » (ou « Networking »), activez le Public Endpoint et autorisez les adresses `0.0.0.0/0` (Render gratuit n'a pas d'IP fixe). La protection reste le mot de passe et le TLS.
7. Créez la base : ouvrez **SQL Editor** (ou Chat2Query) et exécutez :
   ```sql
   CREATE DATABASE billetterie;
   ```
   Le nom `billetterie` sera la valeur de DB_DATABASE. Pour lancer les tests de concurrence (`composer run test:mysql`) depuis un ordinateur, créez aussi `billetterie_test` ; sinon ce n'est pas nécessaire.

Les libellés de l'interface TiDB peuvent changer : cherchez « Connect », « Password » et « Public Endpoint ».

## B. Créer le service sur Render

### Option 1 : Blueprint (recommandé)

1. Vérifiez que la PR est fusionnée dans `main` (voir l'avertissement en haut). Sur Render : **New +** puis **Blueprint**, connectez le dépôt GitHub. Render lit `render.yaml` et déploie la branche `main` (fixée dans ce fichier ; elle n'est pas modifiable ici).
2. Render demande les variables marquées « sync: false » (voir tableau ci-dessous). Remplissez-les puis lancez le déploiement.

### Option 2 : manuel

**New +** puis **Web Service**, dépôt GitHub, Runtime **Docker**, plan **Free**, région **Frankfurt**, Health Check Path `/up`. Ajoutez ensuite toutes les variables du tableau (la colonne « Valeur » pour celles en clair).

### Variables d'environnement

Variables à saisir par vous (secrètes ou propres à votre installation) :

| Variable | Où trouver la valeur |
|---|---|
| APP_KEY | **Utilisez la clé que l'assistant vous a donnée dans la conversation** (jamais dans le dépôt). Pour en obtenir une autre sans ligne de commande : voir « APP_KEY » ci-dessous. |
| APP_URL | L'adresse publique du service, visible en haut de la page du service Render : `https://<nom>.onrender.com`, sans `/` final. Au premier déploiement, mettez le nom prévu, puis corrigez si Render en a choisi un autre. |
| DB_HOST | TiDB Cloud, bouton Connect, champ Host. |
| DB_DATABASE | `billetterie` (créée à l'étape A.7). |
| DB_USERNAME | TiDB Cloud, bouton Connect, champ User (avec son préfixe). |
| DB_PASSWORD | Le mot de passe généré à l'étape A.4. |
| ADMIN_EMAIL | L'adresse email du compte administrateur de la démo (la vôtre). |
| ADMIN_PASSWORD | Un mot de passe de 12 caractères minimum, que vous inventez. Sinon le compte n'est pas créé et le conteneur s'arrête avec un message clair. |
| MANUAL_BANK_NAME, MANUAL_BANK_ACCOUNT_HOLDER, MANUAL_BANK_IBAN, MANUAL_MOBILE_MONEY_NUMBER, MANUAL_MOBILE_MONEY_HOLDER, MANUAL_CASH_LOCATION | Coordonnées de paiement affichées aux acheteurs. Pour une démo, utilisez des valeurs fictives. Si Render refuse un champ vide, saisissez un tiret. |

Variables déjà fixées dans `render.yaml` (en clair, sans danger) :

| Variable | Valeur |
|---|---|
| APP_ENV / APP_DEBUG | `production` / `false` |
| LOG_CHANNEL / LOG_LEVEL | `stderr` / `info` : les journaux apparaissent dans Render |
| DB_CONNECTION / DB_PORT | `mysql` / `4000` |
| DB_SSL_CA | `/etc/ssl/certs/ca-certificates.crt` (certificats racine du conteneur ; TiDB utilise Let's Encrypt) |
| DB_SSL_VERIFY | `true` (vérifie le certificat du serveur) |
| SESSION_DRIVER / CACHE_STORE | `database` |
| QUEUE_CONNECTION | `sync` (pas de worker gratuit) |
| MAIL_MAILER | `log` (les emails sont écrits dans les journaux, pas envoyés) |
| SESSION_SECURE_COOKIE | `true` |
| TRUSTED_PROXIES | `*` : acceptable ici parce que l'application n'est joignable que via le proxy de Render. À ne pas copier ailleurs. |
| TELESCOPE_ENABLED | `false` |
| DEFAULT_COMMISSION_RATE | `6` |
| DEMO_SEED | `true` : au premier démarrage, si la base est vide, crée catégories, commissions et le compte admin. **Passez-le à `false` dès que le premier démarrage a réussi** (voir C.0). |
| APP_NAME / APP_LOCALE / APP_FALLBACK_LOCALE | `Billetterie` / `fr` / `fr` |
| SESSION_LIFETIME / FILESYSTEM_DISK | `120` (minutes) / `local` |
| MAIL_FROM_ADDRESS / MAIL_FROM_NAME | `noreply@example.com` / `Billetterie` (adresse fictive) |
| MAX_*, PENDING_*, *_THROTTLE_PER_MINUTE, *_RESERVATION_MINUTES, MANUAL_RESERVATION_MINUTES, GUEST_MANUAL_RESERVATION_MINUTES | Plafonds anti-abus : valeurs de `render.yaml` (identiques aux défauts du projet). **Facultatives** en option manuelle : sans elles, les défauts du projet s'appliquent. |

### APP_KEY

APP_KEY est la clé qui chiffre les sessions et les cookies. Trois formes sont acceptées (le conteneur normalise la valeur au démarrage et, si elle est inutilisable, s'arrête avec un message clair SANS afficher la valeur) :

1. `base64:` suivi de 44 caractères (32 octets en base64) : forme habituelle de Laravel.
2. 44 caractères base64 SANS le préfixe `base64:` : le conteneur ajoute le préfixe tout seul. C'est le cas d'une valeur générée par Render.
3. Exactement 32 caractères quelconques : acceptés tels quels.

**Votre clé :** utilisez la clé que l'assistant vous a donnée dans la conversation. Collez-la directement dans le champ APP_KEY de Render, sans espace ni retour à la ligne.

**En obtenir une autre sans ligne de commande (depuis un téléphone) :**

- Le plus simple : le générateur de mots de passe de votre téléphone ou de votre gestionnaire de mots de passe. Demandez **exactement 32 caractères**, lettres et chiffres seulement. Vérifiez la longueur (le champ APP_KEY de Render n'en dit rien ; si elle est fausse, le conteneur s'arrête avec « APP_KEY invalide »). Collez le résultat dans APP_KEY.
- Ou, si votre écran Render propose un bouton **Generate** (ou l'option `generateValue`) pour une variable : utilisez-le pour APP_KEY (non vérifié ici ; ces valeurs sont du base64 de 32 octets sans préfixe, ce que le conteneur sait normaliser).
- Sur un ordinateur : `php artisan key:generate --show` (avec PHP et le projet), ou `echo "base64:$(openssl rand -base64 32)"` sur Linux/macOS.

Ne la publiez nulle part. La changer plus tard déconnecte tout le monde et invalide les données chiffrées.

## C. Vérifier après déploiement

Le premier déploiement dure quelques minutes (construction de l'image). Dans l'onglet Logs, cherchez `[entrypoint]` : migrations, puis « Démarrage d'Apache ».

**C.0 (immédiat, dès que « Démarrage d'Apache » apparaît) : passez `DEMO_SEED` à `false`.** Dans Render : Environment, `DEMO_SEED` = `false`, puis enregistrez (Render redéploie). Le compte admin et les catégories sont déjà créés ; laisser `true` ferait recréer un admin avec ADMIN_PASSWORD si la base était un jour vidée ou remplacée. Si vous voyez « DEMO_SEED=true : seed de démo » puis « la table users n'est pas vide, rien à faire », c'est normal pour un redémarrage.

1. Page d'accueil : ouvrez APP_URL, la page se charge avec ses styles.
2. `/up` : doit répondre 200.
3. Connexion admin : avec ADMIN_EMAIL et ADMIN_PASSWORD.
4. Créez un événement avec un type de billet (compte admin ou promoteur).
5. Commandez en invité (navigateur privé) : la commande reste « en attente » avec les instructions de paiement manuel.
6. Dans l'admin, confirmez le paiement de la commande.
7. Vérifiez le QR code du billet et la page de vérification du billet.

## D. Limites de la démo

- Veille : après environ 15 minutes sans visite, le service s'endort. Le réveil prend 30 à 50 secondes.
- Fichiers éphémères : le disque est effacé à chaque déploiement ou redémarrage. Les images téléversées (affiches, avatars) et les QR codes générés disparaissent ; les données de la base (TiDB) restent.
- Emails : journalisés (MAIL_MAILER=log), jamais envoyés. **Attention : avec `MAIL_MAILER=log` et `LOG_CHANNEL=stderr`, le contenu des emails (liens de confirmation, instructions de paiement, adresses email des acheteurs) est écrit EN CLAIR dans les journaux Render.** N'utilisez cette configuration qu'avec des données fictives ; avec de vraies données, passez à un vrai SMTP (voir F) ou à `MAIL_MAILER=array` (rien n'est écrit ni envoyé).
- Planificateur : `schedule:work` tourne dans le conteneur, donc il est arrêté pendant la veille. Les commandes expirées ne sont libérées qu'au réveil, ce qui peut retenir du stock un moment.
- Paiement : manuel uniquement, aucune transaction réelle.
- Pas de sauvegarde automatique au-delà de ce que propose TiDB.

## E. Dépannage

**Erreur TLS ou certificat** (`SSL`, `certificate`, `Connections using insecure transport are prohibited`) : vérifiez que DB_PORT vaut `4000`, que DB_SSL_CA vaut `/etc/ssl/certs/ca-certificates.crt` et que DB_HOST est bien celui du bouton Connect. TiDB refuse toute connexion sans TLS. Si l'erreur mentionne la vérification du nom ou du certificat, **corrigez d'abord DB_SSL_CA** (le chemin du fichier de certificats) et DB_HOST. Ne désactivez la vérification (DB_SSL_VERIFY=`false`) qu'en DERNIER RECOURS et TEMPORAIREMENT : le trafic reste chiffré mais le serveur n'est plus authentifié, donc une interception (attaque de l'homme du milieu) devient possible sur Internet. Remettez `true` dès que possible et ne laissez jamais `false` avec de vraies données.

**Erreur d'accès refusé / utilisateur inconnu** : l'utilisateur TiDB contient un préfixe (`xxxx.root`). Recopiez-le en entier, régénérez le mot de passe si besoin.

**Le conteneur s'arrête au démarrage** : lisez la dernière ligne `[entrypoint] ERREUR` dans les Logs. Causes courantes : variable manquante, ADMIN_PASSWORD de moins de 12 caractères, migration en échec.

**Migration qui échoue.** Les 40 migrations exécutables de `database/migrations` n'ont JAMAIS été exécutés de bout en bout sur MySQL ni sur TiDB (aucun serveur MySQL/TiDB n'était disponible pour les essais) : la première exécution sur votre base vierge est donc la première réelle. Ce qui a été fait : audit migration par migration (`docs/agents/reports/devops-audit-migrations.md`), correction d'une erreur certaine (clé étrangère en double sur `commissions`, erreur MySQL 1826) et exécution de toute la chaîne sur SQLite par un test automatique. Ce qui n'a PAS pu être prouvé : le comportement exact de MySQL/TiDB.

Points restant à risque sur TiDB (non éprouvés) :

1. Trois migrations exécutent `ALTER TABLE … MODIFY COLUMN … ENUM(…)` : `…_021012_add_reserved_status_to_tickets_table` et `…_100326_add_processing_status_to_tickets_table.php.php` (`tickets.status`, ajout en fin de liste), `…_102806_add_processing_status_to_orders_table` (`orders.payment_status`, ajout au milieu de la liste). TiDB accepte l'ajout de membres d'ENUM (lecture de son code source, non exécuté) ; sur des tables vides le risque est faible.
2. Deux migrations modifient `commissions.status` avec `->change()` (ajout de `cancelled` au milieu de la liste) : `2025_07_28_122001` et `2025_08_02_195133`. Même remarque.
3. Les renommages de colonnes (`promoteur_id` en `promoter_id` sur `events`, `commissions`, `commission_settings`) : MySQL/TiDB laissent la clé étrangère et son index sous l'ancien nom (`…_promoteur_id_foreign`). La migration `2026_11_11_120300_rename_promoteur_constraints_to_promoter` S'EXÉCUTE donc sur une base vierge : par table, elle ajoute d'abord la nouvelle clé `…_promoter_id_foreign`, puis supprime l'ancienne clé, puis l'ancien index. Ce DDL n'a jamais tourné sur MySQL/TiDB. Chaque étape est protégée : en cas d'échec, un AVERTISSEMENT (« Migration 120300 … étape … ignorée ») apparaît dans les journaux Render, la table garde toujours une clé étrangère valide et le démarrage continue. Un tel avertissement n'est pas une panne ; signalez-le simplement.

Si l'une échoue, le conteneur s'arrête et le journal montre l'erreur SQL. Procédure : NE PAS modifier la base à la main. Copiez le journal Render (il ne contient pas de mot de passe ; relisez avant d'envoyer) et transmettez-le à l'équipe technique. Une migration partiellement appliquée peut laisser des tables : l'équipe vous dira s'il faut vider la base `billetterie` (`DROP DATABASE` puis `CREATE DATABASE`) avant de redéployer.

Autre point de vigilance, non vérifié sur TiDB : les verrous `SELECT … FOR UPDATE` (`lockForUpdate`) utilisés pour protéger le stock. TiDB les supporte mais leur comportement sous forte concurrence n'a pas été testé ; pour une démo à faible trafic, le risque est faible.

**Erreur 419 « Page Expired »** : presque toujours APP_URL, HTTPS ou cookies. Vérifiez que APP_URL commence par `https://` et correspond EXACTEMENT à l'adresse dans la barre du navigateur ; que SESSION_SECURE_COOKIE vaut `true` ; que TRUSTED_PROXIES vaut `*` (sinon Laravel croit être en HTTP et ne pose pas les cookies correctement) ; que SESSION_DRIVER vaut `database` et que les migrations sont passées. Après modification d'une variable, Render redéploie : rechargez la page depuis le début.

**Erreur 500** : APP_DEBUG reste `false` volontairement (ne le passez pas à `true` sur un site public : il affiche des secrets). Ouvrez l'onglet Logs de Render, cherchez la ligne `ERROR` la plus récente et transmettez-la.

**Base pleine ou lente** : TiDB Starter gratuit offre un quota de 5 Gio de stockage et d'environ 50 millions d'unités de requêtes par mois. Au-delà, les requêtes peuvent être limitées. Surveillez la page Usage du cluster ; pour une démo c'est largement suffisant.

**Images qui disparaissent** : normal (fichiers éphémères). Retéléversez-les, ou passez à un stockage externe : le code est prêt pour Cloudflare R2, guide pas à pas dans [stockage-images.md](stockage-images.md).

## F. Passer ensuite à un hébergement durable

- Stockage externe des images (Cloudflare R2, code prêt) au lieu du disque local : voir [stockage-images.md](stockage-images.md).
- Vrai cron (`* * * * * php artisan schedule:run`) ou service planifié, plus un vrai worker de file (QUEUE_CONNECTION=database ou redis).
- Vrai SMTP (MAIL_MAILER=smtp et ses variables) pour envoyer les emails.
- TRUSTED_PROXIES réel : listez les IP/CIDR du proxy de l'hébergeur au lieu de `*`.
- Hébergement sans veille, base MySQL standard ou TiDB payant.

Checklist « avant production » :

- [ ] `composer run test:mysql` vert sur la base cible (concurrence des stocks).
- [ ] Vérification d'email à l'inscription activée (voir le BOARD : prise de contrôle d'un email par un invité).
- [ ] Captcha sur l'inscription et le checkout invité.
- [ ] `composer run check` vert, APP_DEBUG=false, HTTPS forcé, sauvegardes de la base testées.
- [ ] Mots de passe admin changés, comptes de démo supprimés, DEMO_SEED retiré.

## Billets gratuits

Une commande dont le total calculé par le serveur est 0 FCFA est confirmée automatiquement (billets émis, aucun paiement, aucune commission). Comme elle n'est jamais « en attente », les plafonds `MAX_PENDING_*` ne la limitent pas : des plafonds dédiés, comptés en base de données, s'appliquent (valeurs de `render.yaml`, facultatives, défauts identiques) :

| Variable | Défaut | Rôle |
|---|---|---|
| FREE_ORDERS_PER_IP_PER_HOUR | 5 | Commandes gratuites par IP (empreinte HMAC stockée, jamais l'IP) sur 1 h |
| FREE_ORDERS_PER_EMAIL_PER_DAY | 5 | Commandes gratuites par email normalisé (alias `+tag`, points Gmail) sur 24 h |
| FREE_ORDERS_PER_USER_PER_DAY | 5 | Commandes gratuites par compte connecté sur 24 h, en plus de l'email |
| FREE_TICKETS_PER_EVENT_PER_IP_PER_DAY | 10 | Billets gratuits par événement et par IP sur 24 h (commandes non annulées) |

Au-delà, le client voit « Limite de réservations gratuites atteinte » et aucun stock n'est réservé. Le nombre de billets par commande reste borné par `MAX_PENDING_TICKETS_PER_BUYER` / `max_per_order`.

**Limites connues (à lire avant production)** :
- L'email n'est pas vérifié : un invité peut multiplier les adresses ; seuls les plafonds par IP (et par événement et IP) le freinent.
- Le plafond par IP dépend de `TRUSTED_PROXIES=*` : l'IP est lue dans `X-Forwarded-For`. Avant production, tester une requête avec un `X-Forwarded-For` forgé pour confirmer que l'IP comptée est bien celle fournie par le proxy de Render et non celle du client ; sinon, lister les IP réelles du proxy.
- Une IP partagée (réseau d'entreprise, mobile CGNAT) partage aussi le quota.
- Sans captcha, un attaquant disposant de nombreuses IP peut encore épuiser un événement gratuit : surveiller les commandes `free` (`payment_method = 'free'`).
- Le transport d'email peut être `log` en démonstration : aucun email n'est alors réellement envoyé ; les billets restent visibles sur la page de confirmation (avec la preuve de session pour un invité) et dans l'espace acheteur.

## Mode démo : paiements simulés

Pour une démonstration où les commandes sont confirmées automatiquement (QR visibles tout de suite), posez **les deux** variables dans Render : `DEMO_AUTO_CONFIRM_PAYMENTS=true` et `DEMO_MODE_ACKNOWLEDGED=true`. Avec une seule des deux, en production, le mode reste désactivé et un message critique est écrit dans les journaux. Un bandeau « MODE DÉMO » s'affiche. Les billets deviennent gratuits : supprimez les deux variables après la démonstration. Pour les commandes déjà confirmées sans QR : `php artisan tickets:generate-qr`.
