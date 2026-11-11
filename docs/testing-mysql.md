# Tests MySQL (concurrence du plafond de réservations)

`composer run check` tourne sur SQLite en mémoire : SQLite ignore `lockForUpdate()` et l'isolation REPEATABLE READ,
il ne peut donc pas prouver le plafond de billets en attente sous concurrence. Le test MySQL
`tests/MySql/EventPendingCapConcurrencyTest.php` le fait avec de vrais processus PHP (une connexion MySQL chacun).
Il est exclu de `check` et skippé (`markTestSkipped`) si MySQL n'est pas configuré.

## Commande exacte

```bash
# Base JETABLE dont le nom contient « test » : le test exécute `php artisan migrate:fresh` dessus (données détruites).
mysql -u root -p -e "CREATE DATABASE billeterie_test CHARACTER SET utf8mb4"

TEST_MYSQL_DATABASE=billeterie_test DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_USERNAME=root DB_PASSWORD=... composer run test:mysql
```

(`DB_CONNECTION=mysql DB_DATABASE=billeterie_test` fonctionne aussi à la place de `TEST_MYSQL_DATABASE`.)

## Ce que le test prouve
1. 4 workers réservent 10 billets chacun sur un événement de capacité 100 (plafond relatif 30) ; chacun établit d'abord
   un instantané par une lecture non verrouillante (reproduit N-A), puis tous réservent en même temps :
   exactement 3 passent, jamais plus de 30 billets pending, `quantity_sold` = 30.
2. Paniers [A,B] et [B,A] concurrents : aucun deadlock (N-C), 8 commandes créées.

## Vérification attendue
Retirer `->lockForUpdate()` de `CheckoutService::eventPendingTicketsQuery` doit faire échouer le test 1 (plafond dépassé).
Statut : écrit et `php -l` OK, jamais exécuté sur MySQL par son auteur (aucun MySQL disponible) : à lancer avant la mise en production.
