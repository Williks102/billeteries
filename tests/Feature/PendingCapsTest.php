<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * N-A (verrou du comptage), N-B (plafond relatif + quotas par acteur + messages distincts + alerte),
 * N-C (ordre des verrous), N-D (quota du connecté, historique).
 * SQLite ne prouve pas la concurrence réelle : voir tests/MySql/EventPendingCapConcurrencyTest.php.
 */
class PendingCapsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        config(['payments.max_pending_orders' => 1000, 'payments.max_pending_orders_per_ip' => 1000]);
    }

    private function payload(string $email): array
    {
        return [
            'email' => $email, 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
        ];
    }

    private function guestPost($type, string $email, int $qty, string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.guest.process'), $this->payload($email));
    }

    private function accepted($response): bool
    {
        return str_contains((string) $response->headers->get('Location'), 'confirmation');
    }

    private function userPost(User $user, $type, int $qty, string $email = 'u@example.test')
    {
        return $this->actingAs($user)->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.process'), ['billing_email' => $email, 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash']);
    }

    // ===== N-B : plafond relatif au stock =====

    public function test_event_cap_is_relative_to_capacity_and_clamped(): void
    {
        $s = app(CheckoutService::class);

        $this->assertSame(10, $s->eventPendingCap(20), 'petit événement : plancher 10');
        $this->assertSame(30, $s->eventPendingCap(100), '30 % de 100');
        $this->assertSame(200, $s->eventPendingCap(1000), 'grand événement : plafond dur 200 (30 % = 300)');
        config(['payments.max_pending_tickets_per_event' => 50]);
        $this->assertSame(50, $s->eventPendingCap(1000), 'le plafond dur configuré l\'emporte');
    }

    public function test_small_event_and_large_event_get_different_caps_in_a_real_checkout(): void
    {
        config(['payments.max_pending_tickets_per_buyer' => 1000, 'payments.max_pending_tickets_per_ip' => 1000]);

        // capacité 100 -> plafond 30 : 3 commandes de 10 passent, la 4e est refusée (message événement)
        $small = $this->ticketType(100);
        $ok = 0;
        for ($i = 0; $i < 4; $i++) {
            $r = $this->guestPost($small, "s{$i}@example.test", 10);
            $this->accepted($r) ? $ok++ : $r->assertSessionHas('error', CheckoutService::EVENT_LIMIT_ERROR);
        }
        $this->assertSame(3, $ok);

        // capacité 1000, même demande : 4 commandes de 10 passent (plafond 200)
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 2, 'title' => 'Festival']);
        $big = $this->ticketType(1000);
        $big->forceFill(['event_id' => 2])->save();
        $ok = 0;
        for ($i = 0; $i < 4; $i++) {
            $r = $this->withSession(['cart' => $this->tamperedCart($big, 10, 2)])
                ->post(route('checkout.guest.process'), $this->payload("b{$i}@example.test"));
            $ok += $this->accepted($r) ? 1 : 0;
        }
        $this->assertSame(4, $ok);
    }

    // ===== N-B : quotas par acteur =====

    public function test_buyer_quota_applies_to_the_normalized_email_alias_included(): void
    {
        config(['payments.max_pending_tickets_per_buyer' => 10, 'payments.max_pending_tickets_per_ip' => 1000]);
        $type = $this->ticketType(1000);

        $this->assertTrue($this->accepted($this->guestPost($type, 'Foo+a@Example.test', 5)));
        $this->assertTrue($this->accepted($this->guestPost($type, ' foo+b@example.TEST ', 5)));
        $r = $this->guestPost($type, 'foo@example.test', 1);
        $r->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        $this->assertSame(10, (int) DB::table('order_items')->sum('quantity'));

        // un autre acteur n'est pas affecté
        $this->assertTrue($this->accepted($this->guestPost($type, 'autre@example.test', 5)));
    }

    public function test_buyer_quota_applies_to_a_logged_in_account(): void
    {
        config(['payments.max_pending_tickets_per_buyer' => 10]);
        $type = $this->ticketType(1000);
        $user = User::find(1);

        $this->userPost($user, $type, 5, 'a@example.test')->assertSessionHasNoErrors();
        $this->userPost($user, $type, 5, 'b@example.test')->assertSessionHasNoErrors();
        $this->userPost($user, $type, 1, 'c@example.test')->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        $this->assertSame(10, (int) DB::table('order_items')->sum('quantity'));
    }

    public function test_ip_ticket_quota_applies_across_emails(): void
    {
        config(['payments.max_pending_tickets_per_buyer' => 10, 'payments.max_pending_tickets_per_ip' => 6]);
        $type = $this->ticketType(1000);

        $this->assertTrue($this->accepted($this->guestPost($type, 'a@example.test', 3)));
        $this->assertTrue($this->accepted($this->guestPost($type, 'b@example.test', 3)));
        $this->guestPost($type, 'c@example.test', 1)->assertSessionHas('error', CheckoutService::LIMIT_ERROR);
        $this->assertSame(6, (int) DB::table('order_items')->sum('quantity'));
        // une autre IP n'est pas concernée
        $this->assertTrue($this->accepted($this->guestPost($type, 'd@example.test', 3, '198.51.100.9')));
    }

    public function test_a_single_actor_cannot_freeze_an_event_and_a_legitimate_buyer_still_gets_in(): void
    {
        // capacité 100 -> plafond événement 30 ; acteur : 10 par email, 20 par IP
        $type = $this->ticketType(100);
        $attackerBought = 0;
        foreach (['x1@example.test', 'x2@example.test', 'x3@example.test', 'x4@example.test'] as $email) {
            $attackerBought += $this->accepted($this->guestPost($type, $email, 10, '203.0.113.5')) ? 10 : 0;
        }
        $this->assertSame(20, $attackerBought, 'une seule IP est bornée à 20 billets < plafond événement (30)');

        $legit = $this->guestPost($type, 'legit@example.test', 5, '198.51.100.77');
        $this->assertTrue($this->accepted($legit), 'un acheteur légitime (autre IP, autre email) peut encore réserver');
        $this->assertSame(25, (int) DB::table('order_items')->sum('quantity'));
    }

    public function test_actor_and_event_limit_messages_are_distinct_and_generic(): void
    {
        $this->assertNotSame(CheckoutService::LIMIT_ERROR, CheckoutService::EVENT_LIMIT_ERROR);
        $this->assertStringContainsString('personnelle', CheckoutService::LIMIT_ERROR);
        $this->assertStringNotContainsString('commandes en attente', CheckoutService::EVENT_LIMIT_ERROR);
        $this->assertStringNotContainsString('Réglez', CheckoutService::EVENT_LIMIT_ERROR);
        $this->assertStringContainsString('événement', CheckoutService::EVENT_LIMIT_ERROR);

        // acheteur sans aucune commande face à un événement plein : message événement
        $type = $this->ticketType(20); // plafond 10
        $this->reservedOrder($type, 10, 'pending');
        $this->guestPost($type, 'nouveau@example.test', 1)->assertSessionHas('error', CheckoutService::EVENT_LIMIT_ERROR);
    }

    // ===== N-B : alerte admin =====

    public function test_admin_is_alerted_once_per_hour_per_event_without_pii(): void
    {
        Mail::swap(new \Illuminate\Mail\MailManager($this->app)); // vrai mailer (driver array), pas le fake : Mail::raw n'est pas observable sur le fake
        $this->makeUser(9, 'admin');
        $type = $this->ticketType(20);
        $this->reservedOrder($type, 10, 'pending');
        Cache::forget('event-pending-cap-alert:1');
        $transport = fn () => Mail::mailer('array')->getSymfonyTransport();
        $before = count($transport()->messages());

        $this->guestPost($type, 'pii-secret@example.test', 1);
        $this->guestPost($type, 'pii-secret2@example.test', 1);

        $messages = array_slice($transport()->messages()->all(), $before);
        $this->assertCount(1, $messages, 'une seule alerte pour deux refus dans l\'heure');
        $this->assertStringNotContainsString('pii-secret', $messages[0]->toString());
        $this->assertStringContainsString('#1', $messages[0]->toString());
    }

    // ===== N-A : lecture verrouillante =====

    public function test_pending_count_is_a_locking_read_on_mysql_grammar(): void
    {
        $conn = DB::connection();
        $conn->setQueryGrammar(new \Illuminate\Database\Query\Grammars\MySqlGrammar($conn));
        $service = app(CheckoutService::class);

        $log = $conn->pretend(fn () => $service->eventPendingTicketsQuery(1)->sum('order_items.quantity'));

        $this->assertStringContainsString('for update', $log[0]['query']);
        $this->assertStringContainsString('sum(`order_items`.`quantity`)', $log[0]['query']);
    }

    public function test_guest_checkout_reads_the_user_before_the_transaction_starts(): void
    {
        $type = $this->ticketType(100);
        $events = [];
        DB::listen(function ($q) use (&$events) {
            $events[] = $q->sql;
        });
        DB::connection()->getPdo(); // connexion prête
        $levelAtUserRead = null;
        DB::listen(function ($q) use (&$levelAtUserRead) {
            if ($levelAtUserRead === null && str_contains($q->sql, 'from "users"')) {
                $levelAtUserRead = DB::transactionLevel();
            }
        });

        $this->assertTrue($this->accepted($this->guestPost($type, 'acheteur@example.test', 1)));
        $this->assertSame(0, $levelAtUserRead, 'User::where(email) hors transaction : il ne fixe pas l\'instantané avant les verrous');
    }

    // ===== N-C : ordre des verrous =====

    public function test_carts_a_b_and_b_a_lock_events_in_the_same_ascending_order(): void
    {
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 2, 'title' => 'B']);
        $a = $this->ticketType(100);
        $b = $this->ticketType(100);
        $b->forceFill(['event_id' => 2])->save();
        $service = app(CheckoutService::class);

        $lockOrder = function (array $cart) use ($service) {
            $locked = [];
            DB::listen(function ($q) use (&$locked) {
                if (str_contains($q->sql, 'from "events" where "id" = ?')) { // requête de verrou, pas le lazy-load de la relation
                    $locked[] = (int) $q->bindings[0];
                }
            });
            $groups = $service->buildEventGroups($cart);
            DB::transaction(function () use ($service, $groups) {
                foreach ($groups as $eventId => $group) {
                    $service->createOrder(1, $eventId, $group, ['payment_method' => 'cash']);
                }
            });

            return $locked;
        };

        $ab = $lockOrder([['ticket_type_id' => $a->id, 'quantity' => 1], ['ticket_type_id' => $b->id, 'quantity' => 1]]);
        $ba = $lockOrder([['ticket_type_id' => $b->id, 'quantity' => 1], ['ticket_type_id' => $a->id, 'quantity' => 1]]);

        $this->assertSame([1, 2], $ab);
        $this->assertSame([1, 2], $ba, 'même ordre de verrouillage pour [B,A]');
        $this->assertSame([1, 2], array_keys($service->buildEventGroups([['ticket_type_id' => $b->id, 'quantity' => 1], ['ticket_type_id' => $a->id, 'quantity' => 1]])));
    }

    // ===== N-D : quota du connecté et historique =====

    public function test_third_party_guest_orders_do_not_consume_the_account_quota(): void
    {
        config(['payments.max_pending_orders' => 3]);
        $type = $this->ticketType(100);
        $victim = User::find(1); // email acheteur@example.test

        // 3 commandes d'un tiers : invité, email de la victime, rattachées à son compte (guest_token)
        for ($i = 0; $i < 3; $i++) {
            $this->guestPost($type, 'acheteur@example.test', 1, "192.0.2.{$i}");
        }
        $this->assertSame(3, Order::where('user_id', 1)->whereNotNull('guest_token')->count());

        $this->userPost($victim, $type, 1, 'acheteur@example.test')->assertSessionHasNoErrors();
        $this->assertSame(4, Order::count(), 'la victime peut commander : son quota ne compte que ses propres commandes');
    }

    public function test_buyer_dashboard_and_orders_stats_hide_third_party_pending_orders(): void
    {
        $type = $this->ticketType(100);
        $victim = User::find(1);
        $this->guestPost($type, 'acheteur@example.test', 1);
        $this->guestPost($type, 'acheteur@example.test', 1, '192.0.2.50');
        $this->assertSame(2, $victim->orders()->count());

        $this->assertSame(0, $victim->orders()->visibleInBuyerHistory()->count());

        // Stub de la vue acheteur.orders (dossier temporaire, prioritaire) : le test vérifie les stats, pas le rendu du layout
        $dir = sys_get_temp_dir() . '/views-' . uniqid();
        mkdir($dir . '/acheteur', 0777, true);
        file_put_contents($dir . '/acheteur/orders.blade.php', 'stub');
        view()->getFinder()->prependLocation($dir);

        $this->actingAs($victim)->get(route('acheteur.orders.index'))
            ->assertOk()
            ->assertViewIs('acheteur.orders')
            ->assertViewHas('stats', fn ($stats) => $stats['orders_total'] === 0);

        unlink($dir . '/acheteur/orders.blade.php');
        rmdir($dir . '/acheteur');
        rmdir($dir);
    }
}
