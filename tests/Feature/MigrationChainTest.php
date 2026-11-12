<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Filet de sécurité du premier démarrage sur base vierge : exécute la VRAIE chaîne de migrations de
 * database/migrations (pas le schéma simplifié des autres tests) sur SQLite en mémoire, puis vérifie le schéma final.
 *
 * Limites (voir docs/agents/reports/devops-audit-migrations.md) : SQLite ne reproduit PAS les erreurs propres à
 * MySQL/TiDB (nom de clé étrangère dupliqué 1826, types de FK incompatibles 3780, limite d'index 3072 octets,
 * ALTER ... MODIFY ENUM). Ce test prouve l'ordre des dépendances, les gardes d'idempotence et le schéma final attendu
 * par le code ; il ne remplace pas un `migrate:fresh` sur MySQL/TiDB réel.
 */
class MigrationChainTest extends TestCase
{
    private const MIGRATIONS = 'database/migrations';

    /** Colonnes que le code lit ou écrit (voir le tableau de l'audit), par table, sur la base vierge migrée. */
    private const EXPECTED = [
        'users' => ['id', 'name', 'email', 'password', 'role', 'phone', 'customer_code', 'is_guest', 'guest_converted_at', 'email_verified_at', 'remember_token'],
        'events' => ['promoter_id', 'category_id', 'title', 'status', 'management_mode', 'admin_permissions', 'management_reason', 'management_changed_by', 'management_changed_at'],
        'ticket_types' => ['event_id', 'name', 'price', 'quantity_available', 'quantity_sold', 'sale_start_date', 'sale_end_date', 'max_per_order', 'is_active'],
        'tickets' => ['ticket_type_id', 'order_item_id', 'ticket_code', 'qr_code', 'qr_code_path', 'validation_data', 'status', 'seat_number', 'used_at', 'holder_name', 'holder_email', 'holder_phone', 'created_ip', 'sent_at', 'downloaded_at', 'download_count'],
        'orders' => ['user_id', 'event_id', 'total_amount', 'payment_status', 'payment_method', 'payment_reference', 'order_number', 'guest_token', 'billing_email', 'billing_phone', 'paid_at', 'expires_at', 'admin_notes', 'checkout_ip_hash'],
        'order_items' => ['order_id', 'ticket_type_id', 'quantity', 'unit_price', 'total_price', 'commission_rate', 'commission_amount', 'net_amount'],
        'order_tickets' => ['order_id', 'ticket_id', 'order_item_id'],
        'commissions' => ['order_id', 'promoter_id', 'gross_amount', 'commission_rate', 'commission_amount', 'net_amount', 'platform_fee', 'payment_processor_fee', 'status', 'paid_at', 'notes', 'paid_by'],
        'commission_settings' => ['category_id', 'promoter_id', 'commission_rate', 'platform_fee_fixed', 'min_commission', 'is_active', 'valid_from', 'valid_until'],
        'payments' => ['order_id', 'payment_method', 'amount', 'status', 'provider_transaction_id', 'provider_response', 'completed_at'],
        'event_audit_logs' => ['event_id', 'user_id', 'action', 'description', 'old_values', 'new_values', 'ip_address', 'user_agent'],
        'mail_logs' => ['to_email', 'to_name', 'subject', 'template', 'data', 'status', 'error_message', 'sent_at'],
        'settings' => ['key', 'value', 'type', 'description', 'group', 'is_public'],
        'pages' => ['title', 'slug', 'content', 'is_active'],
        'sessions' => ['id', 'user_id', 'payload', 'last_activity'],
        'cache' => ['key', 'value', 'expiration'],
        'jobs' => ['queue', 'payload', 'attempts'],
    ];

    private function migrate(): void
    {
        $this->assertSame('sqlite', DB::getDriverName(), 'Ce test est conçu pour SQLite en mémoire.');
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]), Artisan::output());
    }

    /** @return array<int, string> chemins des migrations exécutables (le framework ne charge que *.php) */
    private function executableMigrations(): array
    {
        return glob(base_path(self::MIGRATIONS.'/*.php'));
    }

    public function test_la_chaine_complete_s_execute_sur_une_base_vierge(): void
    {
        $this->migrate();

        $this->assertSame(
            count($this->executableMigrations()),
            DB::table('migrations')->count(),
            'Toutes les migrations exécutables doivent être enregistrées.'
        );

        // Idempotence au niveau du framework : un second passage ne fait rien.
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertStringContainsString('Nothing to migrate', Artisan::output());
    }

    public function test_le_schema_final_contient_toutes_les_colonnes_utilisees_par_le_code(): void
    {
        $this->migrate();

        foreach (self::EXPECTED as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table), "Table absente : $table");
            $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));
            $this->assertSame([], $missing, "Colonnes manquantes dans $table");
        }
    }

    public function test_chaque_modele_trouve_ses_colonnes_fillable_et_castees(): void
    {
        $this->migrate();

        foreach (glob(app_path('Models/*.php')) as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');
            $model = new $class;
            $this->assertTrue(Schema::hasTable($model->getTable()), "$class : table {$model->getTable()} absente");

            $wanted = array_unique(array_merge($model->getFillable(), array_keys($model->getCasts())));
            $missing = array_values(array_diff($wanted, Schema::getColumnListing($model->getTable())));
            $this->assertSame([], $missing, "$class : colonnes absentes de {$model->getTable()}");
        }
    }

    /** B1 : sans la garde de 2025_07_29_134010, promoteur_id est recréée (et, sur MySQL/TiDB, sa FK en double échoue : 1826). */
    public function test_commissions_n_a_que_promoter_id_apres_les_renommages(): void
    {
        $this->migrate();

        $columns = Schema::getColumnListing('commissions');
        $this->assertContains('promoter_id', $columns);
        $this->assertNotContains('promoteur_id', $columns, 'promoteur_id ne doit pas être recréée (B1).');

        foreach (['events', 'commission_settings'] as $table) {
            $this->assertContains('promoter_id', Schema::getColumnListing($table));
            $this->assertNotContains('promoteur_id', Schema::getColumnListing($table));
        }

        $this->assertTrue(Schema::hasIndex('commissions', 'commissions_promoter_id_status_index'));

        // Aucun nom « promoteur » résiduel (colonne, index, clé étrangère) dans ces 3 tables.
        // Limite : SQLite ne nomme pas ses clés étrangères ; sur MySQL/TiDB c'est la migration 2026_11_11_120300 qui les renomme.
        foreach (['events', 'commissions', 'commission_settings'] as $table) {
            foreach (Schema::getIndexes($table) as $index) {
                $this->assertStringNotContainsString('promoteur', (string) $index['name'], "$table : index {$index['name']}");
                $this->assertNotContains('promoteur_id', $index['columns']);
            }
            foreach (Schema::getForeignKeys($table) as $fk) {
                $this->assertStringNotContainsString('promoteur', (string) ($fk['name'] ?? ''), "$table : clé étrangère");
                $this->assertNotContains('promoteur_id', $fk['columns']);
            }
        }
    }

    public function test_les_migrations_gardees_sont_idempotentes_si_on_les_rejoue(): void
    {
        $this->migrate();

        foreach ([
            '2025_07_29_134010_fix_commissions_table_structure',
            '2025_08_02_195142_cleanup_duplicate_commissions_migrations',
            '2025_08_02_211543_cleanup_obsolete_promoteur_id_column',
            '2026_11_11_100000_add_expires_at_and_paid_at_to_orders_table',
            '2026_11_11_110000_add_admin_notes_to_orders_table',
            '2026_11_11_120000_add_missing_columns_to_tickets_table',
            '2026_11_11_120100_create_event_audit_logs_table_if_missing',
            '2026_11_11_120200_add_paid_by_to_commissions_table',
            '2026_11_11_120300_rename_promoteur_constraints_to_promoter',
        ] as $name) {
            $migration = require base_path(self::MIGRATIONS."/$name.php");
            $migration->up(); // aucune exception : tout est gardé par hasColumn / hasTable / hasIndex
        }

        $this->assertNotContains('promoteur_id', Schema::getColumnListing('commissions'));
    }

    public function test_le_scan_d_un_billet_peut_ecrire_used_at(): void
    {
        $this->migrate();

        $userId = DB::table('users')->insertGetId(['name' => 'P', 'email' => 'p@example.test', 'password' => 'x', 'role' => 'promoteur', 'created_at' => now(), 'updated_at' => now()]);
        $categoryId = DB::table('event_categories')->insertGetId(['name' => 'C', 'slug' => 'c', 'created_at' => now(), 'updated_at' => now()]);
        $eventId = DB::table('events')->insertGetId(['promoter_id' => $userId, 'category_id' => $categoryId, 'title' => 'E', 'description' => 'd', 'venue' => 'v', 'address' => 'a', 'event_date' => '2030-01-01', 'event_time' => '2030-01-01 20:00:00', 'created_at' => now(), 'updated_at' => now()]);
        $typeId = DB::table('ticket_types')->insertGetId(['event_id' => $eventId, 'name' => 'T', 'price' => 1000, 'quantity_available' => 5, 'sale_start_date' => now(), 'sale_end_date' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);

        // Commande payée + lien billet/commande (pivot order_tickets), comme le fait le checkout.
        $orderId = DB::table('orders')->insertGetId(['user_id' => $userId, 'event_id' => $eventId, 'total_amount' => 1000, 'payment_status' => 'paid', 'order_number' => 'ORD-0', 'billing_email' => 'a@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $itemId = DB::table('order_items')->insertGetId(['order_id' => $orderId, 'ticket_type_id' => $typeId, 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000, 'created_at' => now(), 'updated_at' => now()]);
        $ticket = \App\Models\Ticket::create(['ticket_type_id' => $typeId, 'order_item_id' => $itemId, 'ticket_code' => 'TKT-TEST0001', 'status' => 'sold']);
        DB::table('order_tickets')->insert(['order_id' => $orderId, 'ticket_id' => $ticket->id, 'order_item_id' => $itemId, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertTrue($ticket->markAsUsed());

        $this->assertSame('used', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->used_at);

        // Statuts ajoutés par les migrations ENUM (gérés par ->change() hors MySQL) : acceptés par le schéma final.
        foreach (['reserved', 'processing'] as $status) {
            $ticket->forceFill(['status' => $status])->save();
            $this->assertSame($status, $ticket->fresh()->status);
        }
        DB::table('orders')->insert(['user_id' => $userId, 'event_id' => $eventId, 'total_amount' => 1000, 'payment_status' => 'processing', 'order_number' => 'ORD-1', 'billing_email' => 'a@example.test', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame('processing', DB::table('orders')->where('order_number', 'ORD-1')->value('payment_status'));
    }

    /**
     * Toute instruction brute MySQL (ALTER TABLE ... MODIFY) doit être gardée par le pilote : sans cela la chaîne
     * échoue sur SQLite et le test de chaîne ci-dessus devient impossible.
     */
    public function test_les_alter_table_bruts_sont_gardes_par_le_pilote(): void
    {
        $raw = 0;
        foreach ($this->executableMigrations() as $file) {
            $code = file_get_contents($file);
            if (preg_match('/DB::(statement|unprepared)\(\s*["\']ALTER/i', $code)) {
                $raw++;
                $this->assertStringContainsString('getDriverName()', $code, basename($file).' : instruction SQL brute sans garde de pilote.');
                $this->assertStringContainsString("['mysql', 'mariadb']", $code, basename($file));
            }
        }
        $this->assertGreaterThanOrEqual(3, $raw, 'Les 3 migrations ENUM doivent être détectées.');
    }

    public function test_seul_le_back_connu_n_est_pas_execute_et_ses_colonnes_sont_reprises(): void
    {
        $nonRunnable = array_filter(
            glob(base_path(self::MIGRATIONS.'/*')),
            fn ($f) => ! str_ends_with($f, '.php')
        );
        $this->assertSame(['2025_07_25_003819_add_missing_columns_to_tickets_table.php.back'], array_map('basename', array_values($nonRunnable)));

        // Les colonnes du .back sont reprises par la migration corrective : on le vérifie sur le texte.
        $back = file_get_contents(base_path(self::MIGRATIONS.'/2025_07_25_003819_add_missing_columns_to_tickets_table.php.back'));
        $fix = file_get_contents(base_path(self::MIGRATIONS.'/2026_11_11_120000_add_missing_columns_to_tickets_table.php'));
        preg_match_all("/\\\$table->\\w+\\('(\\w+)'/", $back, $m);
        foreach (array_unique($m[1]) as $column) {
            $this->assertStringContainsString("'$column'", $fix, "Colonne du .back non reprise : $column");
        }
    }
}
