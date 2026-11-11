# Déploiement de démo sur Render + TiDB Cloud

Guide pas à pas pour mettre en ligne une démo PROVISOIRE de la billetterie, depuis un téléphone si besoin.

- Hébergement : Render, offre gratuite, déploiement par Docker (Render n'a pas de PHP natif).
- Base de données : TiDB Cloud Starter (compatible MySQL, TLS obligatoire, port 4000).
- Paiement : manuel, confirmé par l'admin. Aucun vrai paiement n'est branché.
- Règle d'or : ne collez JAMAIS un mot de passe ou une clé dans le dépôt, dans une issue ou dans un chat. Saisissez-les uniquement dans les champs « Environment » de Render.

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

1. Sur Render : **New +** puis **Blueprint**, connectez le dépôt GitHub, choisissez la branche à déployer. Render lit `render.yaml`.
2. Render demande les variables marquées « sync: false » (voir tableau ci-dessous). Remplissez-les puis lancez le déploiement.

### Option 2 : manuel

**New +** puis **Web Service**, dépôt GitHub, Runtime **Docker**, plan **Free**, région **Frankfurt**, Health Check Path `/up`. Ajoutez ensuite toutes les variables du tableau (la colonne « Valeur » pour celles en clair).

### Variables d'environnement

Variables à saisir par vous (secrètes ou propres à votre installation) :

| Variable | Où trouver la valeur |
|---|---|
| APP_KEY | Voir « Générer APP_KEY » ci-dessous. Forme : `base64:` suivi de 44 caractères. |
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
| DEMO_SEED | `true` : au premier démarrage, si la base est vide, crée catégories, commissions et le compte admin. Sans effet ensuite. |
| MAX_*, PENDING_*, *_THROTTLE_PER_MINUTE, *_RESERVATION_MINUTES | Plafonds anti-abus, valeurs par défaut du projet. |

### Générer APP_KEY

APP_KEY doit avoir la forme `base64:` suivi de 32 octets aléatoires encodés en base64 (44 caractères, souvent terminés par `=`). L'option `generateValue` de Render ne produit PAS ce format : ne l'utilisez pas.

- Avec PHP et le projet sur un ordinateur : `php artisan key:generate --show` affiche la clé sans modifier de fichier.
- Sans PHP : `echo "base64:$(openssl rand -base64 32)"` sur n'importe quel Linux/macOS (ou dans un terminal en ligne de confiance sur votre propre machine).

Copiez la valeur directement dans Render. Ne la publiez nulle part. La changer plus tard déconnecte tout le monde et invalide les données chiffrées.

## C. Vérifier après déploiement

Le premier déploiement dure quelques minutes (construction de l'image). Dans l'onglet Logs, cherchez `[entrypoint]` : migrations, puis « Démarrage d'Apache ».

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
- Emails : journalisés (MAIL_MAILER=log), jamais envoyés.
- Planificateur : `schedule:work` tourne dans le conteneur, donc il est arrêté pendant la veille. Les commandes expirées ne sont libérées qu'au réveil, ce qui peut retenir du stock un moment.
- Paiement : manuel uniquement, aucune transaction réelle.
- Pas de sauvegarde automatique au-delà de ce que propose TiDB.

## E. Dépannage

**Erreur TLS ou certificat** (`SSL`, `certificate`, `Connections using insecure transport are prohibited`) : vérifiez que DB_PORT vaut `4000`, que DB_SSL_CA vaut `/etc/ssl/certs/ca-certificates.crt` et que DB_HOST est bien celui du bouton Connect. TiDB refuse toute connexion sans TLS. Si l'erreur mentionne la vérification du nom ou du certificat, l'essai de dernier recours est DB_SSL_VERIFY=`false` (le trafic reste chiffré mais le serveur n'est plus authentifié) ; remettez `true` dès que possible et signalez le message.

**Erreur d'accès refusé / utilisateur inconnu** : l'utilisateur TiDB contient un préfixe (`xxxx.root`). Recopiez-le en entier, régénérez le mot de passe si besoin.

**Le conteneur s'arrête au démarrage** : lisez la dernière ligne `[entrypoint] ERREUR` dans les Logs. Causes courantes : variable manquante, ADMIN_PASSWORD de moins de 12 caractères, migration en échec.

**Migration qui échoue sur TiDB.** Les migrations n'ont JAMAIS été exécutées sur TiDB, seulement sur SQLite et MySQL. Trois migrations exécutent `ALTER TABLE … MODIFY COLUMN … ENUM(…)`, une syntaxe que TiDB peut refuser :

1. `database/migrations/2025_09_11_021012_add_reserved_status_to_tickets_table.php` : `tickets.status`
2. `database/migrations/2025_09_11_100326_add_processing_status_to_tickets_table.php.php` : `tickets.status`
3. `database/migrations/2025_09_11_102806_add_processing_status_to_orders_table.php` : `orders.payment_status`

(Le décompte initial annonçait quatre migrations ; la recherche dans le dépôt en trouve trois dans leur méthode `up`, les trois autres instructions étant leurs `down`.) Si l'une échoue, le conteneur s'arrête et le journal montre l'erreur SQL. Procédure : NE PAS modifier la base à la main. Copiez le journal Render complet (sans mot de passe : il n'y en a pas dans les journaux, relisez avant d'envoyer) et transmettez-le à l'équipe technique, qui adaptera ces migrations. Une migration à moitié appliquée peut laisser des tables : l'équipe vous dira s'il faut vider la base `billetterie` (`DROP DATABASE` puis `CREATE DATABASE`) avant de redéployer.

Autre point de vigilance, non vérifié sur TiDB : les verrous `SELECT … FOR UPDATE` (`lockForUpdate`) utilisés pour protéger le stock. TiDB les supporte mais leur comportement sous forte concurrence n'a pas été testé ; pour une démo à faible trafic, le risque est faible.

**Erreur 419 « Page Expired »** : presque toujours APP_URL, HTTPS ou cookies. Vérifiez que APP_URL commence par `https://` et correspond EXACTEMENT à l'adresse dans la barre du navigateur ; que SESSION_SECURE_COOKIE vaut `true` ; que TRUSTED_PROXIES vaut `*` (sinon Laravel croit être en HTTP et ne pose pas les cookies correctement) ; que SESSION_DRIVER vaut `database` et que les migrations sont passées. Après modification d'une variable, Render redéploie : rechargez la page depuis le début.

**Erreur 500** : APP_DEBUG reste `false` volontairement (ne le passez pas à `true` sur un site public : il affiche des secrets). Ouvrez l'onglet Logs de Render, cherchez la ligne `ERROR` la plus récente et transmettez-la.

**Base pleine ou lente** : TiDB Starter gratuit offre un quota de 5 Gio de stockage et d'environ 50 millions d'unités de requêtes par mois. Au-delà, les requêtes peuvent être limitées. Surveillez la page Usage du cluster ; pour une démo c'est largement suffisant.

**Images qui disparaissent** : normal (fichiers éphémères). Retéléversez-les, ou passez à un stockage externe (voir ci-dessous).

## F. Passer ensuite à un hébergement durable

- Stockage externe des images (S3 ou équivalent) au lieu du disque local.
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
