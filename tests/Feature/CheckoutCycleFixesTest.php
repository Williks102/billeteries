<?php

namespace Tests\Feature;

use App\Models\TicketType;
use App\Services\CheckoutService;
use App\Services\QRCodeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\Fakes\FakeQRCodeService;
use Tests\TestCase;

/**
 * Cycle de corrections : B1 (alerte admin avec cache database), I1 (plafond par commande),
 * I2 (QR hors transaction, aucun réseau), S-1 (IPv6 /64, alias Gmail).
 */
class CheckoutCycleFixesTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        config(['payments.max_pending_orders' => 1000, 'payments.max_pending_orders_per_ip' => 1000]);
    }

    private function guestPost($type, string $email, int $qty, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.guest.process'), [
                'email' => $email, 'first_name' => 'Awa', 'last_name' => 'Kone',
                'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
            ]);
    }

    private function accepted($response): bool
    {
        return str_contains((string) $response->headers->get('Location'), 'confirmation');
    }

    // ===== B1 : alerte admin avec un VRAI store de cache `database` =====

    /**
     * phpunit.xml force CACHE_STORE=array : un test avec `array` ne voit pas le bug (le marqueur n'est pas
     * annulé par le rollback). Ici le store `database` est réel, sur la même connexion que la transaction.
     */
    public function test_admin_alert_is_sent_once_with_the_real_database_cache_store(): void
    {
        config(['cache.default' => 'database', 'cache.stores.database.connection' => null, 'cache.stores.database.lock_connection' => null]);
        Cache::purge('database');
        Schema::create('cache', function ($t) {
            $t->string('key')->primary();
            $t->mediumText('value');
            $t->integer('expiration');
        });
        Schema::create('cache_locks', function ($t) {
            $t->string('key')->primary();
            $t->string('owner');
            $t->integer('expiration');
        });
        $this->assertSame('database', config('cache.default'));

        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        $this->makeUser(9, 'admin');
        $type = $this->ticketType(20);
        $this->reservedOrder($type, 10, 'pending');
        $transport = fn () => Mail::mailer('array')->getSymfonyTransport();
        $before = count($transport()->messages());

        for ($i = 0; $i < 3; $i++) {
            $this->guestPost($type, "refus{$i}@example.test", 1)
                ->assertSessionHas('error', CheckoutService::EVENT_LIMIT_ERROR);
        }

        $this->assertCount(1, array_slice($transport()->messages()->all(), $before), '3 refus consécutifs : 1 seul email');
        $this->assertTrue(DB::table('cache')->where('key', 'like', '%event-pending-cap-alert:1')->exists(), 'le créneau est bien dans le store database');
    }

    // ===== I1 : plafond par commande = min(max_per_order, quota par acheteur) =====

    public function test_order_of_eleven_is_refused_with_the_exact_message_and_ten_is_accepted(): void
    {
        $type = $this->ticketType(500);
        $type->update(['max_per_order' => 20]); // un promoteur peut aller jusqu'à 20
        $this->assertSame(10, CheckoutService::maxTicketsPerOrder(20));

        $refused = $this->guestPost($type, 'big@example.test', 11);
        $refused->assertSessionHas('error', CheckoutService::perOrderLimitMessage(10));
        $this->assertStringContainsString('Maximum 10 billets par commande', session('error'));
        $this->assertStringNotContainsString('réservations en attente', session('error'));

        $this->assertTrue($this->accepted($this->guestPost($type, 'ok@example.test', 10)));
    }

    public function test_per_order_cap_uses_the_type_max_when_lower_than_the_quota(): void
    {
        $type = $this->ticketType(500);
        $type->update(['max_per_order' => 4]);

        $this->guestPost($type, 'p@example.test', 5)->assertSessionHas('error', CheckoutService::perOrderLimitMessage(4));
        $this->assertTrue($this->accepted($this->guestPost($type, 'p2@example.test', 4)));
    }

    public function test_cart_and_booking_form_show_the_effective_maximum(): void
    {
        $type = $this->ticketType(500);
        $type->update(['max_per_order' => 20]);

        $this->postJson(route('cart.add'), ['event_id' => 1, 'tickets' => [['ticket_type_id' => $type->id, 'quantity' => 10]]])
            ->assertOk();
        $this->postJson(route('cart.add'), ['event_id' => 1, 'tickets' => [['ticket_type_id' => $type->id, 'quantity' => 1]]])
            ->assertStatus(400)->assertJsonPath('message', "'Std' : " . CheckoutService::perOrderLimitMessage(10));
        $this->assertSame(10, session('cart.ticket_' . $type->id . '.max_per_order'));
    }

    // ===== I2 : QR hors transaction, jamais de réseau =====

    public function test_qr_codes_are_generated_after_commit_never_inside_the_transaction(): void
    {
        $fake = new FakeQRCodeService();
        $this->app->instance(QRCodeService::class, $fake);
        $type = $this->ticketType(100);

        $this->assertTrue($this->accepted($this->guestPost($type, 'qr@example.test', 3)));

        $this->assertCount(3, $fake->requested, 'un QR par billet de la commande');
        $this->assertSame([0, 0, 0], $fake->transactionLevels, 'aucune génération QR sous transaction/verrous');
    }

    public function test_a_qr_failure_never_breaks_the_order(): void
    {
        $this->app->instance(QRCodeService::class, new class extends FakeQRCodeService {
            public function generateAndSaveTicketQR(\App\Models\Ticket $ticket, $size = 200)
            {
                throw new \RuntimeException('réseau indisponible pour qr-secret@example.test');
            }
        });
        $type = $this->ticketType(100);

        $this->assertTrue($this->accepted($this->guestPost($type, 'qrfail@example.test', 2)));
        $this->assertSame(2, DB::table('tickets')->where('status', 'reserved')->count());
        $this->assertSame(2, DB::table('order_tickets')->count());
    }

    public function test_the_test_suite_has_no_network_access(): void
    {
        $this->assertInstanceOf(FakeQRCodeService::class, app(QRCodeService::class));
        $this->expectException(\RuntimeException::class);
        Http::get('https://chart.googleapis.com/chart?cht=qr&chs=1x1&chl=x');
    }

    // ===== S-1 : IPv6 /64 et alias Gmail =====

    public function test_two_ipv6_addresses_of_the_same_slash_64_share_the_ip_quota(): void
    {
        config(['payments.max_pending_tickets_per_ip' => 2, 'payments.max_pending_tickets_per_buyer' => 100]);
        $type = $this->ticketType(1000);

        $this->assertTrue($this->accepted($this->guestPost($type, 'v6a@example.test', 2, '2001:db8:1:2::1')));
        $this->guestPost($type, 'v6b@example.test', 1, '2001:db8:1:2:ffff:abcd:0:9')
            ->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        // autre /64 : quota distinct
        $this->assertTrue($this->accepted($this->guestPost($type, 'v6c@example.test', 1, '2001:db8:1:3::1')));
    }

    public function test_ip_normalization_keeps_ipv4_and_groups_ipv6_by_slash_64(): void
    {
        $this->assertSame('203.0.113.7', CheckoutService::normalizeIp('203.0.113.7'));
        $this->assertSame('203.0.113.7', CheckoutService::normalizeIp('::ffff:203.0.113.7'));
        $this->assertSame(CheckoutService::normalizeIp('2001:db8:1:2::1'), CheckoutService::normalizeIp('2001:db8:1:2:aaaa:bbbb:cccc:dddd'));
        $this->assertNotSame(CheckoutService::normalizeIp('2001:db8:1:2::1'), CheckoutService::normalizeIp('2001:db8:1:3::1'));
    }

    public function test_gmail_dots_and_googlemail_share_the_order_quota(): void
    {
        config(['payments.max_pending_orders' => 1]);
        $type = $this->ticketType(1000);

        $this->assertTrue($this->accepted($this->guestPost($type, 'a.b@gmail.com', 1, '10.0.0.1')));
        $this->guestPost($type, 'ab@googlemail.com', 1, '10.0.0.2')
            ->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        $this->guestPost($type, 'A.B+promo@GMAIL.com', 1, '10.0.0.3')
            ->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        // un autre domaine : les points restent significatifs
        $this->assertTrue($this->accepted($this->guestPost($type, 'a.b@example.test', 1, '10.0.0.4')));
        $this->assertTrue($this->accepted($this->guestPost($type, 'ab@example.test', 1, '10.0.0.5')));
    }

    public function test_email_normalization(): void
    {
        $this->assertSame('ab@gmail.com', CheckoutService::normalizeEmail(' A.B+x@GoogleMail.com '));
        $this->assertSame('a.b@example.test', CheckoutService::normalizeEmail('a.b+x@example.test'));
    }
}
