<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Validation des deux checkouts : aucune commande, aucun billet, aucun stock consommé en cas de refus. */
class CheckoutValidationTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function buyer(array $o = []): array
    {
        return $o + [
            'billing_email' => 'a@b.test', 'billing_phone' => '0102030405',
            'terms_accepted' => '1', 'payment_method' => 'bank_transfer',
        ];
    }

    private function guest(array $o = []): array
    {
        return $o + [
            'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
        ];
    }

    private function postAuth(array $cart, array $payload)
    {
        return $this->actingAs(User::find(1))->withSession(['cart' => $cart])->post(route('checkout.process'), $payload);
    }

    private function postGuest(array $cart, array $payload)
    {
        return $this->withSession(['cart' => $cart])->post(route('checkout.guest.process'), $payload);
    }

    private function assertNothingCreated($type): void
    {
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Ticket::count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(0, $type->fresh()->quantity_sold);
        Mail::assertNothingSent();
    }

    public static function invalidFieldsProvider(): array
    {
        return [
            'méthode vide' => [['payment_method' => ''], 'payment_method'],
            'méthode inconnue' => [['payment_method' => 'bitcoin'], 'payment_method'],
            'méthode ancienne (fournisseur retiré)' => [['payment_method' => 'paiementpro'], 'payment_method'],
            'cgu non acceptées' => [['terms_accepted' => ''], 'terms_accepted'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFieldsProvider')]
    public function test_authenticated_checkout_rejects_invalid_fields(array $override, string $field): void
    {
        $type = $this->ticketType(10);
        $this->postAuth($this->tamperedCart($type, 1), $this->buyer($override))->assertSessionHasErrors($field);
        $this->assertNothingCreated($type);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFieldsProvider')]
    public function test_guest_checkout_rejects_invalid_fields(array $override, string $field): void
    {
        $type = $this->ticketType(10);
        $this->postGuest($this->tamperedCart($type, 1), $this->guest($override))->assertSessionHasErrors($field);
        $this->assertNothingCreated($type);
        $this->assertSame(0, User::where('email', 'invite@example.test')->count());
    }

    public function test_authenticated_checkout_rejects_terms_explicitly_refused(): void
    {
        // Côté invité, '0'/'false' passent (règle `required` seule) : voir tests/Known/KnownBugsTest.php
        $type = $this->ticketType(10);
        $this->postAuth($this->tamperedCart($type, 1), $this->buyer(['terms_accepted' => '0']))->assertSessionHasErrors('terms_accepted');
        $this->assertNothingCreated($type);
    }

    public function test_invalid_email_is_rejected_on_both_checkouts(): void
    {
        $type = $this->ticketType(10);

        $this->postAuth($this->tamperedCart($type, 1), $this->buyer(['billing_email' => 'pas-un-email']))
            ->assertSessionHasErrors('billing_email');
        $this->postGuest($this->tamperedCart($type, 1), $this->guest(['email' => 'pas-un-email']))
            ->assertSessionHasErrors('email');
        $this->assertNothingCreated($type);
    }

    public function test_empty_cart_is_refused_on_both_checkouts(): void
    {
        $type = $this->ticketType(10);

        $this->postAuth([], $this->buyer())->assertRedirect(route('cart.show'))->assertSessionHas('error');
        $this->postGuest([], $this->guest())->assertRedirect(route('cart.show'))->assertSessionHas('error');
        $this->assertNothingCreated($type);
    }

    public static function badQuantityProvider(): array
    {
        return ['zéro' => [0], 'négative' => [-3]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('badQuantityProvider')]
    public function test_zero_or_negative_quantity_creates_nothing(int $qty): void
    {
        $type = $this->ticketType(10);

        $this->postAuth($this->tamperedCart($type, $qty), $this->buyer())->assertSessionHas('error');
        $this->postGuest($this->tamperedCart($type, $qty), $this->guest())->assertSessionHas('error');
        $this->assertNothingCreated($type);
    }

    public function test_ticket_type_of_another_event_is_refused(): void
    {
        $type = $this->ticketType(10); // event 1
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 3, 'title' => 'Autre']);
        DB::table('ticket_types')->where('id', $type->id)->update(['event_id' => 2]);

        // Le panier prétend que ce billet appartient à l'événement 1
        $this->postAuth($this->tamperedCart($type, 1, eventId: 1), $this->buyer())->assertSessionHas('error');
        $this->postGuest($this->tamperedCart($type, 1, eventId: 1), $this->guest())->assertSessionHas('error');
        $this->assertNothingCreated($type);
    }

    public function test_quantity_above_stock_rolls_back_everything(): void
    {
        $type = $this->ticketType(2);

        $this->postAuth($this->tamperedCart($type, 3), $this->buyer())->assertSessionHas('error');
        $this->assertNothingCreated($type);
    }

    public function test_unknown_ticket_type_creates_nothing(): void
    {
        $type = $this->ticketType(10);
        $cart = ['k' => ['ticket_type_id' => 9999, 'event_id' => 1, 'quantity' => 1]];

        $this->postAuth($cart, $this->buyer())->assertSessionHas('error');
        $this->assertNothingCreated($type);
    }
}
