<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use App\Support\TrustedProxyConfig;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * N3 (plafond par événement, throttle register, plafond IP des comptes récents), N2 (proxys de confiance), N4 (historique).
 *
 * Limite SQLite : lockForUpdate est ignoré ; la sérialisation réelle de 10 requêtes parallèles
 * (verrou sur la ligne `events`) doit encore être prouvée sur MySQL. Ici la concurrence est simulée en séquence.
 */
class CheckoutAbuseLimitsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        TrustProxies::flushState();
    }

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
        ];
    }

    private function guestPost($type, array $payload, int $qty = 1)
    {
        return $this->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.guest.process'), $payload);
    }

    // ===== N3b : plafond de billets pending par événement =====

    public function test_pending_tickets_per_event_are_capped_with_a_generic_message(): void
    {
        // Plafond dur à 5 (le plafond relatif, 30 % de 100 = 30, est plus haut : le clamp max l'emporte)
        config(['payments.max_pending_tickets_per_event' => 5, 'payments.max_pending_orders' => 100, 'payments.max_pending_orders_per_ip' => 100]);
        $type = $this->ticketType(100);

        // 10 requêtes (simulation séquentielle d'un envoi parallèle) avec le même panier de 2 billets, emails distincts
        $accepted = 0;
        for ($i = 0; $i < 10; $i++) {
            $this->flushSession();
            $r = $this->guestPost($type, $this->payload(['email' => "p{$i}@example.test"]), 2);
            if (str_contains((string) $r->headers->get('Location'), 'confirmation')) {
                $accepted++;
            } else {
                $r->assertSessionHas('error', CheckoutService::EVENT_LIMIT_ERROR);
            }
        }

        $this->assertSame(2, $accepted, '5 billets max : 2 commandes de 2 billets passent, pas plus');
        $this->assertSame(4, (int) $type->fresh()->quantity_sold);
        $this->assertSame(4, (int) DB::table('order_items')->sum('quantity'));
    }

    public function test_paid_and_failed_orders_do_not_count_toward_the_event_cap(): void
    {
        config(['payments.max_pending_tickets_per_event' => 3]);
        $type = $this->ticketType(100);
        $this->reservedOrder($type, 3, 'paid');
        $this->reservedOrder($type, 3, 'failed');
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => 3]]);

        $order = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_the_cap_check_runs_inside_the_reservation_transaction_and_locks_the_event_row(): void
    {
        $type = $this->ticketType(100);
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => 1]]);
        $queries = [];
        DB::listen(function ($q) use (&$queries) {
            $queries[] = $q->sql;
        });

        DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));

        $count = collect($queries)->search(fn ($q) => str_contains($q, 'from "order_items"') && str_contains($q, 'sum('));
        $insert = collect($queries)->search(fn ($q) => str_contains($q, 'insert into "orders"'));
        $this->assertNotFalse($count);
        $this->assertLessThan($insert, $count, 'le comptage précède la création de la commande, dans la même transaction');
        // Sur MySQL la requête sur `events` porte « for update » ; SQLite ne l'émet pas : à prouver sur MySQL.
        $this->assertNotFalse(collect($queries)->search(fn ($q) => str_contains($q, 'from "events"')));
    }

    // ===== N3a : throttle sur l'inscription =====

    public function test_register_is_throttled_per_ip(): void
    {
        config(['payments.register_throttle_per_minute' => 2]);
        // Le middleware lit la config à la construction du contrôleur : requêtes successives
        $codes = [];
        for ($i = 0; $i < 4; $i++) {
            $codes[] = $this->post(route('register'), ['email' => 'bad'])->getStatusCode();
        }

        $this->assertNotContains(429, array_slice($codes, 0, 2));
        $this->assertSame(429, $codes[3]);
    }

    // ===== N3c : plafond IP pour les comptes jeunes =====

    public function test_ip_cap_applies_to_accounts_younger_than_24h_but_not_to_older_ones(): void
    {
        $service = app(CheckoutService::class);
        $young = new User();
        $young->created_at = now()->subHours(2);
        $old = new User();
        $old->created_at = now()->subDays(3);

        $this->assertTrue($service->shouldCapByIp(null));
        $this->assertTrue($service->shouldCapByIp($young));
        $this->assertFalse($service->shouldCapByIp($old));
    }

    public function test_young_logged_in_account_is_capped_by_ip(): void
    {
        config(['payments.max_pending_orders_per_ip' => 1, 'payments.max_pending_orders' => 100]);
        $type = $this->ticketType(100);
        DB::table('users')->where('id', 1)->update(['created_at' => now()->subHour()]);
        $user = User::find(1);
        $post = fn () => $this->actingAs($user)->withSession(['cart' => $this->tamperedCart($type, 1)])
            ->post(route('checkout.process'), ['billing_email' => 'a@b.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash']);

        $post()->assertSessionHasNoErrors();
        $this->assertSame(1, Order::count());
        $post()->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        $this->assertSame(1, Order::count());
    }

    // ===== N2 : proxys de confiance =====

    public function test_trusted_proxy_config_defaults_to_no_proxy_and_never_to_wildcard(): void
    {
        $this->assertNull(TrustedProxyConfig::proxies(null));
        $this->assertNull(TrustedProxyConfig::proxies(''));
        $this->assertSame('*', TrustedProxyConfig::proxies('*'));
        $this->assertSame(['10.0.0.1', '172.16.0.0/12'], TrustedProxyConfig::proxies(' 10.0.0.1 , 172.16.0.0/12 ,'));
        $this->assertNull(TrustedProxyConfig::proxies(' , * , '), '* au milieu d\'une liste est ignoré');
        $this->assertNull(TrustedProxyConfig::headers(null));
    }

    public function test_forwarded_ip_is_ignored_without_trusted_proxy_and_honoured_with_it(): void
    {
        $make = fn () => Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9']);
        $run = function (Request $r) {
            (new TrustProxies())->handle($r, fn ($req) => response('ok'));

            return $r->ip();
        };

        $this->assertSame('10.0.0.1', $run($make()), 'défaut : aucun proxy de confiance, X-Forwarded-For ignoré');

        TrustProxies::at(TrustedProxyConfig::proxies('10.0.0.1'));
        $this->assertSame('203.0.113.9', $run($make()));

        // Un client non listé ne peut pas usurper son IP
        TrustProxies::at(TrustedProxyConfig::proxies('10.0.0.1'));
        $other = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '198.51.100.7', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9']);
        $this->assertSame('198.51.100.7', $run($other));
    }

    public function test_bootstrap_does_not_trust_any_proxy_by_default(): void
    {
        $this->assertNull(env('TRUSTED_PROXIES'), 'ce test suppose TRUSTED_PROXIES non défini dans phpunit/.env de test');
        $r = new \ReflectionClass(TrustProxies::class);
        $this->assertNull($r->getProperty('alwaysTrustProxies')->getValue());
    }

    // ===== N4 : historique acheteur =====

    public function test_guest_created_pending_order_is_hidden_from_account_history_until_paid(): void
    {
        $type = $this->ticketType(100);
        $user = User::find(1);
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => 1]]);
        $mine = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));
        $guest = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash', 'guest_token' => str_repeat('a', 32)]));

        $visible = fn () => $user->orders()->visibleInBuyerHistory()->pluck('order_number')->all();
        $this->assertContains($mine->order_number, $visible());
        $this->assertNotContains($guest->order_number, $visible());

        $guest->update(['payment_status' => 'paid']);
        $this->assertContains($guest->order_number, $visible());
    }
}
