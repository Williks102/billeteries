# Tests MySQL (concurrence du plafond de réservations)

`composer run check` tourne sur SQLite en mémoire : SQLite ignore `lockForUpdate()` et l'isolation REPEATABLE READ,
il ne peut donc pas prouver le plafond de billets en attente sous concurrence. Le test MySQL
`tests/MySql/EventPendingCapConcurrencyTest.php` le fait avec de vrais processus PHP (une connexion MySQL chacun).
Il est exclu de `check` ET de la suite par défaut (`defaultTestSuite="Unit,Feature"` dans phpunit.xml : `vendor/bin/phpunit` ou `php artisan test` sans argument ne le lancent pas ; seule la suite `MySql` le fait). Il est skippé (`markTestSkipped`) si MySQL n'est pas configuré ou si la garde S-4 refuse.

## Commande exacte

```bash
# Base JETABLE dont le nom respecte ^[a-z0-9_]*_test$ : le test exécute `php artisan migrate:fresh` dessus (données détruites).
mysql -u root -p -e "CREATE DATABASE billeterie_test CHARACTER SET utf8mb4"

TEST_MYSQL_DATABASE=billeterie_test DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_USERNAME=root DB_PASSWORD=... composer run test:mysql
```

(`DB_CONNECTION=mysql DB_DATABASE=billeterie_test` fonctionne aussi à la place de `TEST_MYSQL_DATABASE`.)

## Garde de sécurité (S-4)
`tests/MySql/MySqlTestGuard.php` (testée par `tests/Unit/MySqlTestGuardTest.php`, dans `check`) refuse de lancer `migrate:fresh` si :
- le nom de base ne correspond pas EXACTEMENT à `^[a-z0-9_]*_test$` (« latest », « contest_prod » sont refusés) ;
- l'hôte (`DB_HOST`) n'est pas `127.0.0.1`, `localhost` ou `::1`, sauf s'il figure dans `TEST_MYSQL_ALLOWED_HOSTS` (liste séparée par des virgules) ;
- `APP_ENV=production`.
Un refus se traduit par un test « skipped » avec le motif.

## Ce que le test prouve
1. 4 workers réservent 10 billets chacun sur un événement de capacité 100 (plafond relatif 30) ; chacun établit d'abord
   un instantané par une lecture non verrouillante (reproduit N-A), puis tous réservent en même temps :
   exactement 3 passent, jamais plus de 30 billets pending, `quantity_sold` = 30.
2. Paniers [A,B] et [B,A] concurrents : aucun deadlock (N-C), 8 commandes créées.

## Vérification attendue
Retirer `->lockForUpdate()` de `CheckoutService::eventPendingTicketsQuery` doit faire échouer le test 1 (plafond dépassé).
Statut : écrit et `php -l` OK, jamais exécuté sur MySQL par son auteur (aucun MySQL disponible) : à lancer avant la mise en production.
