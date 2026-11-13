<?php

namespace Tests\Feature\Activity;

use App\Exceptions\OrderPaymentException;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Journal d'activité : table, modèle en ajout seul, service qui ne lève jamais, actions journalisées, confidentialité. */
class ActivityLogTest extends TestCase
{
    use BuildsOrderSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();

        // La VRAIE migration (types portables SQLite / MySQL)
        (include base_path('database/migrations/2026_11_13_100000_create_activity_logs_table.php'))->up();

        $this->admin = $this->makeUser(10, 'admin');
        // Données personnelles volontairement reconnaissables : elles ne doivent JAMAIS apparaître dans le journal
        DB::table('users')->where('id', 1)->update(['name' => 'Kouassi Sensible', 'phone' => '0707070707', 'email' => 'sensible.acheteur@example.test']);
    }

    private function logs(string $action): \Illuminate\Support\Collection
    {
        return ActivityLog::where('action', $action)->get();
    }

    public function test_migration_creates_expected_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasColumns('activity_logs', [
            'id', 'user_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'event_id',
            'description', 'properties', 'ip_fingerprint', 'created_at',
        ]));
        $this->assertFalse(Schema::hasColumn('activity_logs', 'updated_at'));

        $indexed = collect(Schema::getIndexes('activity_logs'))->pluck('columns')->flatten()->all();
        $this->assertContains('action', $indexed);
        $this->assertContains('created_at', $indexed);
        $this->assertContains('event_id', $indexed);
    }

    public function test_model_is_append_only(): void
    {
        ActivityLogger::record(ActivityLog::SETTINGS_UPDATED, null, 'Test');
        $log = ActivityLog::sole();

        foreach ([
            fn () => $log->update(['description' => 'modifié']),
            fn () => $log->delete(),
            fn () => $log->forceDelete(),
            function () use ($log) {
                $log->description = 'modifié';
                $log->save();
            },
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Une modification du journal aurait dû être refusée.');
            } catch (LogicException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame('Test', ActivityLog::sole()->description);
    }

    public function test_logger_never_throws_even_without_table(): void
    {
        Schema::drop('activity_logs');

        ActivityLogger::record(ActivityLog::SETTINGS_UPDATED, null, 'Sans table');
        ActivityLogger::record(ActivityLog::PAYMENT_CONFIRMED, $this->reservedOrder($this->ticketType(5), 1), 'Sans table');

        $this->assertTrue(true); // aucune exception
    }

    public function test_a_logging_failure_does_not_block_payment_confirmation(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);
        Schema::drop('activity_logs');

        app(OrderPaymentService::class)->confirmPayment($order, null, $this->admin);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_admin_confirmation_creates_payment_confirmed_with_actor_role_order_number_and_ip_fingerprint(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->actingAs($this->admin)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->post(route('admin.orders.confirm-payment', $order), ['payment_reference' => '0707070707'])
            ->assertRedirect();

        $log = $this->logs(ActivityLog::PAYMENT_CONFIRMED)->sole();
        $this->assertSame(10, $log->user_id);
        $this->assertSame('admin', $log->actor_role);
        $this->assertSame('Order', $log->subject_type);
        $this->assertSame($order->id, $log->subject_id);
        $this->assertSame($order->order_number, $log->properties['order_number']);
        $this->assertSame(1000, $log->properties['amount']);
        $this->assertFalse($log->properties['automatic']);
        $this->assertSame(\App\Services\CheckoutService::ipHash('203.0.113.9'), $log->ip_fingerprint);
        $this->assertSame(64, strlen($log->ip_fingerprint));

        // Jamais l'IP en clair ni la référence libre saisie (qui peut contenir un numéro de téléphone)
        $raw = json_encode($log->getAttributes());
        $this->assertStringNotContainsString('203.0.113.9', $raw);
        $this->assertStringNotContainsString('0707070707', $raw);

        // Rejeu idempotent : pas de seconde ligne
        $this->actingAs($this->admin)->post(route('admin.orders.confirm-payment', $order))->assertRedirect();
        $this->assertCount(1, $this->logs(ActivityLog::PAYMENT_CONFIRMED));
    }

    public function test_automatic_confirmation_is_logged_with_system_actor_even_when_a_buyer_is_logged_in(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);
        $this->actingAs(User::find(1));

        app(OrderPaymentService::class)->confirmPayment($order, 'GRATUIT', null);

        $log = $this->logs(ActivityLog::PAYMENT_CONFIRMED)->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('system', $log->actor_role);
        $this->assertTrue($log->properties['automatic']);
        $this->assertStringContainsString('automatiquement', $log->description);
    }

    public function test_failed_or_rolled_back_confirmation_creates_no_row(): void
    {
        // 1) refusée : statut remboursé
        $refunded = $this->reservedOrder($this->ticketType(5), 1, 'refunded');
        try {
            app(OrderPaymentService::class)->confirmPayment($refunded, null, $this->admin);
            $this->fail('Exception attendue');
        } catch (OrderPaymentException) {
        }

        // 2) transaction englobante annulée après la confirmation
        $order = $this->reservedOrder($this->ticketType(5), 1);
        try {
            DB::transaction(function () use ($order) {
                app(OrderPaymentService::class)->confirmPayment($order, null, $this->admin);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(0, ActivityLog::where('action', ActivityLog::PAYMENT_CONFIRMED)->count());
    }

    public function test_status_change_refund_cancel_and_expiry_are_logged(): void
    {
        $service = app(OrderPaymentService::class);

        $paid = $this->reservedOrder($this->ticketType(5), 1);
        $service->changeStatus($paid, 'paid', null, $this->admin);
        $service->changeStatus($paid->fresh(), 'refunded', null, $this->admin);

        $changed = $this->logs(ActivityLog::ORDER_STATUS_CHANGED);
        $this->assertCount(2, $changed);
        $this->assertSame(['from' => 'pending', 'to' => 'paid'], $changed->first()->properties['status']);
        $this->assertCount(1, $this->logs(ActivityLog::ORDER_REFUNDED));

        $cancelled = $this->reservedOrder($this->ticketType(5), 1);
        $service->fail($cancelled);
        $this->assertCount(1, $this->logs(ActivityLog::ORDER_CANCELLED));

        $expired = $this->reservedOrder($this->ticketType(5), 1, 'pending', now()->subHour());
        $this->artisan('orders:release-expired')->assertSuccessful();
        $log = $this->logs(ActivityLog::ORDER_EXPIRED)->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('system', $log->actor_role);
        $this->assertSame($expired->id, $log->subject_id);
    }

    public function test_price_change_creates_ticket_type_updated_with_before_after(): void
    {
        $type = $this->ticketType(10);
        $this->assertCount(1, $this->logs(ActivityLog::TICKET_TYPE_CREATED));

        $this->actingAs($this->admin);
        $type->update(['price' => 1500, 'quantity_available' => 20]);
        $type->update(['quantity_sold' => 3]); // vente : volontairement non journalisée

        $log = $this->logs(ActivityLog::TICKET_TYPE_UPDATED)->sole();
        $this->assertSame(['from' => 1000, 'to' => 1500], $log->properties['price']);
        $this->assertSame(['from' => 10, 'to' => 20], $log->properties['quantity_available']);
        $this->assertArrayNotHasKey('quantity_sold', $log->properties);
        $this->assertSame(10, $log->user_id);

        $type->delete();
        $this->assertCount(1, $this->logs(ActivityLog::TICKET_TYPE_DELETED));
    }

    public function test_reservation_stock_movements_do_not_flood_the_journal(): void
    {
        $type = $this->ticketType(10);
        $type->reserveTickets(2);

        $this->assertCount(0, $this->logs(ActivityLog::TICKET_TYPE_UPDATED));
    }

    public function test_event_publication_status_changes_and_deletion_are_logged(): void
    {
        $this->actingAs($this->admin);
        $event = Event::find(1);

        $event->update(['status' => 'draft']);      // published -> draft : dépublié
        $event->update(['status' => 'published']);  // publié
        $event->update(['status' => 'draft']);      // dépublié
        $event->update(['status' => 'cancelled']);  // autre changement de statut
        $event->update(['venue' => 'Nouveau lieu']);

        $this->assertCount(1, $this->logs(ActivityLog::EVENT_PUBLISHED));
        $this->assertCount(2, $this->logs(ActivityLog::EVENT_UNPUBLISHED));
        $this->assertCount(1, $this->logs(ActivityLog::EVENT_STATUS_CHANGED));
        $updated = $this->logs(ActivityLog::EVENT_UPDATED)->sole();
        $this->assertSame(['from' => 'Palais', 'to' => 'Nouveau lieu'], $updated->properties['venue']);
        $this->assertSame(1, $updated->event_id);

        $event->delete();
        $this->assertCount(1, $this->logs(ActivityLog::EVENT_DELETED));
    }

    public function test_role_and_email_status_changes_are_logged_without_personal_data(): void
    {
        $this->actingAs($this->admin);
        $buyer = User::find(1);

        $buyer->update(['role' => 'promoteur']);
        $buyer->forceFill(['email_verified_at' => now()])->save();
        $buyer->update(['name' => 'Autre nom']); // champ non suivi

        $role = $this->logs(ActivityLog::USER_ROLE_CHANGED)->sole();
        $this->assertSame(['from' => 'acheteur', 'to' => 'promoteur'], $role->properties['role']);
        $this->assertCount(1, $this->logs(ActivityLog::USER_STATUS_CHANGED));
        $this->assertSame(2, ActivityLog::where('subject_type', 'User')->count());
    }

    public function test_admin_login_is_logged_but_not_buyer_login(): void
    {
        DB::table('users')->where('id', 10)->update(['password' => bcrypt('secret-pass-1')]);
        DB::table('users')->where('id', 1)->update(['password' => bcrypt('secret-pass-2')]);

        $this->post('/login', ['email' => 'admin10@example.test', 'password' => 'secret-pass-1']);
        $this->post('/logout');
        $this->post('/login', ['email' => 'sensible.acheteur@example.test', 'password' => 'secret-pass-2']);

        $log = $this->logs(ActivityLog::AUTH_ADMIN_LOGIN)->sole();
        $this->assertSame(10, $log->user_id);
        $this->assertStringNotContainsString('admin10@example.test', json_encode($log->getAttributes()));
    }

    public function test_no_personal_data_in_any_record_created_by_the_scenarios(): void
    {
        $this->actingAs($this->admin);
        $service = app(OrderPaymentService::class);

        $order = $this->reservedOrder($this->ticketType(5), 1);
        DB::table('orders')->where('id', $order->id)->update([
            'user_id' => 1, 'billing_email' => 'sensible.acheteur@example.test', 'billing_phone' => '0707070707',
            'guest_token' => 'GUEST-TOKEN-SECRET', 'payment_reference' => 'REF-SENSIBLE',
        ]);
        $service->confirmPayment($order->fresh(), 'REF-SENSIBLE', $this->admin);
        $service->changeStatus($order->fresh(), 'refunded', 'note privée', $this->admin);
        User::find(1)->update(['role' => 'promoteur']);
        Event::find(1)->update(['status' => 'draft']);

        $this->assertGreaterThan(4, ActivityLog::count());
        $dump = json_encode(ActivityLog::all()->map->getAttributes()->all(), JSON_UNESCAPED_UNICODE);

        foreach (['sensible.acheteur@example.test', '0707070707', 'Kouassi', 'GUEST-TOKEN-SECRET', 'REF-SENSIBLE', 'note privée', '127.0.0.1', 'secret-pass'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $dump, "Donnée sensible dans le journal : {$forbidden}");
        }
    }

    public function test_logger_scrubs_sensitive_property_keys(): void
    {
        ActivityLogger::record(ActivityLog::SETTINGS_UPDATED, null, 'Test', [
            'email' => 'a@b.test', 'phone' => '0102', 'guest_token' => 'x', 'password' => 'p', 'api_key' => 'k',
            'price' => ['from' => 1, 'to' => 2],
        ]);

        $this->assertSame(['price' => ['from' => 1, 'to' => 2]], ActivityLog::sole()->properties);
    }
}
