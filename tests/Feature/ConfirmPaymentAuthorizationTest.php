<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Autorisations de POST admin/orders/{order}/confirm-payment.
 *
 * CSRF : le framework (ValidateCsrfToken::runningUnitTests) désactive la vérification quand
 * l'application tourne en console avec env=testing. Pour les tests CSRF on bascule `app['env']`
 * sur « production » le temps de la requête : le vrai middleware est alors actif.
 */
class ConfirmPaymentAuthorizationTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function activateCsrf(): void
    {
        $this->app['env'] = 'production';
    }

    public function test_guest_visitor_is_redirected_to_login(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $response = $this->post(route('admin.orders.confirm-payment', $order));

        $response->assertRedirect();
        $this->assertStringContainsString('login', (string) $response->headers->get('Location'));
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_buyer_and_promoter_are_forbidden_admin_is_allowed(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 1);

        $this->actingAs($this->makeUser(2, 'acheteur'))
            ->post(route('admin.orders.confirm-payment', $order))->assertForbidden();
        $this->actingAs($this->makeUser(3, 'promoteur'))
            ->post(route('admin.orders.confirm-payment', $order))->assertForbidden();
        $this->assertSame('pending', $order->fresh()->payment_status);

        $this->actingAs($this->makeUser(4, 'admin'))
            ->post(route('admin.orders.confirm-payment', $order))->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_request_without_csrf_token_is_rejected(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);
        $this->activateCsrf();

        $this->actingAs($this->makeUser(4, 'admin'))
            ->post(route('admin.orders.confirm-payment', $order))
            // 419 converti en redirection avec message clair (bootstrap/app.php) : la requête reste REFUSÉE.
            ->assertRedirect()->assertSessionHasErrors('session');

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_request_with_wrong_csrf_token_is_rejected(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);
        $this->activateCsrf();

        $this->actingAs($this->makeUser(4, 'admin'))
            ->withSession(['_token' => 'bon-jeton'])
            ->post(route('admin.orders.confirm-payment', $order), ['_token' => 'mauvais-jeton'])
            ->assertRedirect()->assertSessionHasErrors('session');

        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_request_with_valid_csrf_token_passes_with_csrf_active(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);
        $this->activateCsrf();

        $this->actingAs($this->makeUser(4, 'admin'))
            ->withSession(['_token' => 'bon-jeton'])
            ->post(route('admin.orders.confirm-payment', $order), ['_token' => 'bon-jeton'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
