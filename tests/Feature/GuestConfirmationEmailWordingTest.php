<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * La confirmation invitée ne doit pas promettre un e-mail tant que l'envoi réel n'est pas actif (pilote « log » ou
 * « array » : démo) : l'acheteur croirait recevoir ses billets alors que rien ne part.
 */
class GuestConfirmationEmailWordingTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        View::share('demoMode', false);
        config(['payments.manual_methods.bank_transfer' => [
            'label' => 'Virement bancaire', 'instructions' => 'Virez le montant sur le compte ci-dessous.',
            'details' => ['IBAN' => 'CI00-TEST-1234'],
        ]]);
    }

    private function page(bool $paid): string
    {
        $order = $this->reservedOrder($this->ticketType(10), 1, 'pending', now()->addHours(5));
        $order->update(['user_id' => 1, 'guest_token' => 'tok-wording', 'payment_method' => 'bank_transfer', 'billing_email' => 'invite@example.test']);
        $ticket = Ticket::first();
        $ticket->update(['order_item_id' => $order->orderItems()->first()->id]);
        if ($paid) {
            $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $ticket->update(['status' => 'sold']);
        }

        return $this->withSession(['guest_order_tokens.'.$order->id => 'tok-wording'])
            ->get(route('checkout.guest.confirmation', 'tok-wording'))->assertOk()->getContent();
    }

    public static function modes(): array
    {
        return ['payée' => [true], 'en attente de paiement' => [false]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modes')]
    public function test_no_email_promise_when_emails_are_only_logged(bool $paid): void
    {
        config(['mail.default' => 'log']);
        $html = $this->page($paid);

        $this->assertStringNotContainsString('envoyés par email', $html);
        $this->assertStringNotContainsString('sont envoyés à', $html);
        $this->assertStringNotContainsString('ont été envoyées', $html);
        $this->assertStringContainsString("aucun e-mail n'est envoyé", $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('modes')]
    public function test_email_wording_is_kept_when_real_sending_is_active(bool $paid): void
    {
        config(['mail.default' => 'smtp']);
        $html = $this->page($paid);

        $this->assertStringContainsString('Vérifiez votre email', $html);
        $this->assertStringContainsString('invite@example.test', $html);
        $this->assertStringNotContainsString("aucun e-mail n'est envoyé", $html);
    }
}
