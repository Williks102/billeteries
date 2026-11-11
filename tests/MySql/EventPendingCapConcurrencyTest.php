<?php

namespace Tests\MySql;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Preuve de concurrence MySQL (N-A, N-C) : plafond de billets pending par événement et ordre des verrous.
 *
 * EXCLU de `composer run check` (SQLite). Lancé par `composer run test:mysql` ; voir docs/testing-mysql.md.
 * SKIPPÉ si MySQL n'est pas configuré. ATTENTION : exécute `migrate:fresh` sur la base indiquée ;
 * garde S-4 (MySqlTestGuard) : nom `^[a-z0-9_]*_test$`, hôte local ou listé dans TEST_MYSQL_ALLOWED_HOSTS,
 * jamais avec APP_ENV=production.
 *
 * Non exécuté dans l'environnement de développement (pas de MySQL) : écrit pour être lancé par le propriétaire.
 */
class EventPendingCapConcurrencyTest extends TestCase
{
    private string $database = '';

    protected function setUp(): void
    {
        parent::setUp();

        $name = getenv('TEST_MYSQL_DATABASE') ?: (getenv('DB_CONNECTION') === 'mysql' ? (getenv('DB_DATABASE') ?: '') : '');
        if ($name === '') {
            $this->markTestSkipped('MySQL requis : voir docs/testing-mysql.md (TEST_MYSQL_DATABASE=... composer run test:mysql).');
        }
        $refusal = MySqlTestGuard::refusal(
            $name,
            getenv('DB_HOST') ?: (string) config('database.connections.mysql.host'),
            getenv('APP_ENV') ?: (string) config('app.env'),
            getenv('TEST_MYSQL_ALLOWED_HOSTS') ?: ''
        );
        if ($refusal !== null) {
            $this->markTestSkipped('Garde S-4 : ' . $refusal);
        }
        if (!function_exists('proc_open')) {
            $this->markTestSkipped('proc_open indisponible.');
        }

        $this->database = $name;
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => $name]);
        DB::purge('mysql');

        // VRAIES migrations sur un schéma MySQL réel
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
    }

    /** @return array{user:int, event:int} */
    private function seedEvent(int $eventNumber, int $capacity): array
    {
        $userId = DB::table('users')->where('email', 'w@example.test')->value('id')
            ?? DB::table('users')->insertGetId(['name' => 'W', 'email' => 'w@example.test', 'password' => 'x', 'role' => 'acheteur', 'created_at' => now(), 'updated_at' => now()]);
        $categoryId = DB::table('event_categories')->value('id')
            ?? DB::table('event_categories')->insertGetId(['name' => 'Cat', 'slug' => 'cat', 'created_at' => now(), 'updated_at' => now()]);
        $eventId = DB::table('events')->insertGetId([
            'promoter_id' => $userId, 'category_id' => $categoryId, 'title' => 'E' . $eventNumber,
            'description' => 'd', 'venue' => 'v', 'address' => 'a', 'event_date' => '2030-01-01',
            'event_time' => '2030-01-01 20:00:00', 'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $typeId = DB::table('ticket_types')->insertGetId([
            'event_id' => $eventId, 'name' => 'Std', 'price' => 1000, 'quantity_available' => $capacity, 'quantity_sold' => 0,
            'sale_start_date' => now()->subDay(), 'sale_end_date' => now()->addDay(), 'max_per_order' => 100, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return ['user' => $userId, 'event' => $eventId, 'type' => $typeId];
    }

    /**
     * Lance un processus PHP indépendant (connexion MySQL propre) par panier ; renvoie leurs résultats.
     *
     * @param  array<int, array<int, array{ticket_type_id:int, quantity:int}>>  $carts
     * @return array<int, array<string, mixed>>
     */
    private function runWorkers(array $carts, int $userId): array
    {
        $barrier = sys_get_temp_dir() . '/mysql-barrier-' . uniqid();
        mkdir($barrier);
        $env = array_merge(getenv(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $this->database,
            'APP_KEY' => getenv('APP_KEY') ?: 'base64:' . base64_encode(str_repeat('k', 32)),
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => 'array',
            'MAX_PENDING_TICKETS_PER_EVENT' => '200',
        ]);

        $procs = [];
        foreach ($carts as $i => $cart) {
            $json = json_encode(['cart' => $cart, 'user_id' => $userId, 'barrier_dir' => $barrier, 'worker' => $i, 'total' => count($carts)]);
            $p = proc_open([PHP_BINARY, __DIR__ . '/reserve_worker.php', $json], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), $env);
            $this->assertIsResource($p);
            $procs[$i] = [$p, $pipes];
        }

        $results = [];
        foreach ($procs as $i => [$p, $pipes]) {
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($p);
            $lines = array_values(array_filter(explode("\n", trim($out))));
            $results[$i] = json_decode((string) end($lines), true) ?: ['status' => 'no-output', 'stderr' => substr($err, 0, 300)];
        }
        array_map('unlink', glob($barrier . '/*'));
        rmdir($barrier);

        return $results;
    }

    public function test_concurrent_reservations_never_exceed_the_event_cap_even_with_a_stale_snapshot(): void
    {
        // capacité 100 -> plafond 30 (ratio 0.3). 4 workers x 10 billets : exactement 3 passent.
        $s = $this->seedEvent(1, 100);
        $carts = array_fill(0, 4, [['ticket_type_id' => $s['type'], 'quantity' => 10]]);

        $results = $this->runWorkers($carts, $s['user']);

        $statuses = array_column($results, 'status');
        $this->assertNotContains('error', $statuses, json_encode($results));
        $this->assertNotContains('no-output', $statuses, json_encode($results));

        $pending = (int) DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.event_id', $s['event'])->whereIn('orders.payment_status', ['pending', 'processing'])->sum('order_items.quantity');
        $this->assertLessThanOrEqual(30, $pending, 'le plafond de 30 billets pending ne doit jamais être dépassé');
        $this->assertSame(30, $pending, 'exactement 3 réservations de 10 billets sur 4 workers');
        $this->assertSame(3, count(array_keys($statuses, 'accepted')));
        $this->assertSame(1, count(array_keys($statuses, 'refused')));
        $this->assertSame(30, (int) DB::table('ticket_types')->where('id', $s['type'])->value('quantity_sold'));
    }

    public function test_carts_in_opposite_event_order_do_not_deadlock(): void
    {
        $a = $this->seedEvent(1, 1000);
        $b = $this->seedEvent(2, 1000);
        $ab = [['ticket_type_id' => $a['type'], 'quantity' => 1], ['ticket_type_id' => $b['type'], 'quantity' => 1]];
        $ba = array_reverse($ab);

        $results = $this->runWorkers([$ab, $ba, $ab, $ba], $a['user']);

        $this->assertSame(['accepted', 'accepted', 'accepted', 'accepted'], array_column($results, 'status'), json_encode($results));
        $this->assertSame(8, (int) DB::table('orders')->count());
    }
}
