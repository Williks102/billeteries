<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Mini-passage final : M-1 (la vérification PUBLIQUE n'expose ni titulaire ni commande, les routes
 * authentifiées du promoteur propriétaire les gardent) et M-2 (le code du billet n'est jamais journalisé).
 */
class TicketVerificationPrivacyTest extends TestCase
{
    use BuildsOrderSchema;

    private const CODE = 'TKT-SECRET99';

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Schema::table('tickets', function ($t) {
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->timestamp('used_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->unsignedBigInteger('cancelled_by')->nullable();
            $t->unsignedBigInteger('used_by')->nullable();
        });
        config(['payments.verify_ticket_throttle_per_minute' => 1000]);

        $this->makeUser(11, 'promoteur');
        $this->makeUser(12, 'acheteur');
        $this->makeUser(13, 'promoteur'); // autre promoteur
        $this->makeUser(14, 'admin');
        DB::table('users')->where('id', 12)->update(['name' => 'Jean Titulaire', 'email' => 'titulaire.secret@example.test']);

        DB::table('events')->updateOrInsert(['id' => 1], ['promoter_id' => 11, 'title' => 'Concert Public', 'venue' => 'Stade', 'event_date' => now()->addWeek()->toDateString(), 'event_time' => '20:00']);
        $type = $this->ticketType(10);
        DB::table('ticket_types')->where('id', $type->id)->update(['event_id' => 1]);
        $orderId = DB::table('orders')->insertGetId(['user_id' => 12, 'event_id' => 1, 'total_amount' => 1000, 'payment_status' => 'paid', 'order_number' => 'ORD-SECRET-777', 'created_at' => now(), 'updated_at' => now()]);
        $itemId = DB::table('order_items')->insertGetId(['order_id' => $orderId, 'ticket_type_id' => $type->id, 'quantity' => 1]);
        $id = DB::table('tickets')->insertGetId(['ticket_type_id' => $type->id, 'order_item_id' => $itemId, 'ticket_code' => self::CODE, 'status' => 'sold']);
        $this->ticket = Ticket::find($id);
    }

    // ===== M-1 =====

    public function test_public_api_response_never_contains_holder_email_or_order_number(): void
    {
        $res = $this->getJson('/api/verify-ticket/' . self::CODE)->assertOk();
        $json = $res->getContent();

        $this->assertStringNotContainsString('holder', $json);
        $this->assertStringNotContainsString('Jean Titulaire', $json);
        $this->assertStringNotContainsString('titulaire.secret', $json);
        $this->assertStringNotContainsString('ORD-SECRET-777', $json);
        $this->assertStringNotContainsString('"order"', $json);
        $this->assertArrayNotHasKey('holder', $res->json('ticket.info'));
        $this->assertArrayNotHasKey('order', $res->json('ticket.info'));
        // Ce qui reste utile est conservé.
        $this->assertTrue($res->json('ticket.info.is_valid'));
        $this->assertSame('Concert Public', $res->json('ticket.info.event.title'));
        $this->assertSame('Std', $res->json('ticket.info.ticket_type'));
    }

    public function test_public_page_never_contains_holder_or_order_number(): void
    {
        $html = $this->get('/verify-ticket/' . self::CODE)->assertOk()->getContent();

        $this->assertStringContainsString('Concert Public', $html);
        $this->assertStringNotContainsString('Jean Titulaire', $html);
        $this->assertStringNotContainsString('titulaire.secret', $html);
        $this->assertStringNotContainsString('ORD-SECRET-777', $html);
    }

    public function test_owner_promoter_keeps_the_holder_on_the_authenticated_verification(): void
    {
        $html = $this->actingAs(\App\Models\User::find(11))
            ->post('/promoteur/scanner/verify/' . self::CODE)->assertOk()->getContent();

        $this->assertStringContainsString('Jean Titulaire', $html);
        $this->assertStringContainsString('titulaire.secret@example.test', $html);
        $this->assertStringContainsString('ORD-SECRET-777', $html);
    }

    public function test_another_promoter_does_not_see_the_holder(): void
    {
        $html = $this->actingAs(\App\Models\User::find(13))
            ->post('/promoteur/scanner/verify/' . self::CODE)->assertOk()->getContent();

        $this->assertStringNotContainsString('Jean Titulaire', $html);
        $this->assertStringNotContainsString('titulaire.secret', $html);
    }

    // ===== M-2 =====

    /** @return string tout ce qui a été journalisé pendant $action (messages + contextes). */
    private function capturedLogs(callable $action): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'log');
        config(['logging.default' => 'single', 'logging.channels.single.path' => $tmp, 'logging.channels.single.level' => 'debug']);
        Log::forgetChannel('single');
        try {
            $action();
        } finally {
            $logs = (string) file_get_contents($tmp);
            @unlink($tmp);
        }

        return $logs;
    }

    public function test_public_verification_never_logs_the_ticket_code(): void
    {
        $logs = $this->capturedLogs(function () {
            $this->get('/verify-ticket/' . self::CODE);
            $this->getJson('/api/verify-ticket/' . self::CODE);
            $this->get('/verify-ticket/tkt-unknown-0001');
            $this->getJson('/api/verify-ticket/tkt-unknown-0001');
        });

        $this->assertNotSame('', $logs, 'des logs sont bien produits');
        $this->assertStringNotContainsStringIgnoringCase(self::CODE, $logs);
        $this->assertStringNotContainsStringIgnoringCase('unknown-0001', $logs);
    }

    public function test_authenticated_scan_and_admin_actions_never_log_the_ticket_code(): void
    {
        $logs = $this->capturedLogs(function () {
            $this->actingAs(\App\Models\User::find(11))->post('/promoteur/scanner/verify/' . self::CODE);
            $this->actingAs(\App\Models\User::find(11))->postJson('/promoteur/scanner/verify', ['ticket_code' => self::CODE]);
            $this->actingAs(\App\Models\User::find(13))->postJson('/promoteur/scanner/verify', ['ticket_code' => self::CODE]);
            $this->actingAs(\App\Models\User::find(11))->postJson('/promoteur/scanner/verify', ['ticket_code' => 'TKT-UNKNOWN-0002']);
            $this->actingAs(\App\Models\User::find(11))->postJson('/promoteur/api/authenticated-scan', ['ticket_code' => self::CODE]);

            $admin = \App\Models\User::find(14);
            $this->actingAs($admin)->patch('/admin/tickets/' . $this->ticket->id . '/cancel');
            $this->actingAs($admin)->patch('/admin/tickets/' . $this->ticket->id . '/reactivate');
            $this->actingAs($admin)->patch('/admin/tickets/' . $this->ticket->id . '/mark-used');
            $this->actingAs($admin)->put('/admin/tickets/' . $this->ticket->id, ['status' => 'sold']);
        });

        $this->assertStringContainsString('Ticket', $logs, 'des logs sont bien produits');
        $this->assertStringNotContainsStringIgnoringCase(self::CODE, $logs);
        $this->assertStringNotContainsStringIgnoringCase('UNKNOWN-0002', $logs);
    }

    public function test_no_log_call_in_app_interpolates_a_ticket_code(): void
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path())) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // Fenêtre autour de chaque appel Log : l'appel et son contexte ne doivent pas citer le code.
            $src = file_get_contents($file->getPathname());
            if (preg_match_all('/Log::(?:info|warning|error|debug|notice|critical)\((?:[^;]*?)\);/s', $src, $m)) {
                foreach ($m[0] as $call) {
                    if (preg_match('/ticket_code|ticketCode|ticket_code\b/', $call)) {
                        $offenders[] = $file->getFilename() . ' : ' . substr(preg_replace('/\s+/', ' ', $call), 0, 80);
                    }
                }
            }
        }

        $this->assertSame([], $offenders);
    }

    // ===== F-3 =====

    public function test_toasts_and_bootstrap_notifications_insert_text_not_html(): void
    {
        $js = file_get_contents(public_path('js/layout-app.js'));
        $create = substr($js, strpos($js, 'createToast(message, type, config) {'), 2500);
        $create = substr($create, 0, strpos($create, 'hide(toast)'));
        $notif = substr($js, strpos($js, 'function showBootstrapNotification'), 1800);
        $notif = substr($notif, 0, strpos($notif, 'DOMContentLoaded'));

        $this->assertStringNotContainsString('innerHTML', $create);
        $this->assertStringNotContainsString('innerHTML', $notif);
        $this->assertStringContainsString('titleEl.textContent = config.title', $create);
        $this->assertStringContainsString('body.textContent = message', $create);
        $this->assertStringContainsString('textEl.textContent = message', $notif);
    }
}
