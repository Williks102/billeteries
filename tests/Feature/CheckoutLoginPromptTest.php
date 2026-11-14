<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Checkout invité : invitation à se connecter, retour au tunnel avec la sélection conservée. */
class CheckoutLoginPromptTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Schema::table('events', function ($t) {
            $t->text('description')->nullable();
            $t->string('address')->nullable();
            $t->string('image')->nullable();
        });
        DB::table('events')->where('id', 1)->update(['event_date' => now()->addWeek()->toDateString(), 'event_time' => '2027-01-01 08:30:00']);
    }

    private function direct(): array
    {
        $type = $this->ticketType(10);

        return [[
            'ticket_type_id' => $type->id, 'event_id' => 1, 'quantity' => 2, 'ticket_name' => 'Standard',
            'unit_price' => 1000, 'total_price' => 2000, 'event_title' => 'Concert', 'event_image' => null,
            'event_date' => '01/01/2027', 'event_venue' => 'Palais',
        ]];
    }

    public function test_guest_page_shows_login_button_and_register_link(): void
    {
        $this->withSession(['direct_booking' => $this->direct()])->get(route('checkout.guest.show'))
            ->assertOk()
            ->assertSee('data-login-prompt', false)
            ->assertSee('vos billets restent disponibles à tout moment dans votre espace')
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('Se connecter')
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Créer un compte')
            ->assertDontSee('Frais de service');
    }

    public function test_prompt_is_absent_for_a_logged_in_buyer(): void
    {
        $this->actingAs(User::find(1))->withSession(['direct_booking' => $this->direct()])
            ->get(route('checkout.guest.show'))
            ->assertOk()->assertDontSee('data-login-prompt', false);
    }

    public function test_header_does_not_promise_email_with_log_driver_but_does_with_smtp(): void
    {
        config(['mail.default' => 'log']);
        $this->withSession(['direct_booking' => $this->direct()])->get(route('checkout.guest.show'))
            ->assertOk()->assertSee('vos billets s\'affichent à la fin de la commande', false)
            ->assertDontSee('vous sont envoyés par email');

        config(['mail.default' => 'smtp']);
        $this->withSession(['direct_booking' => $this->direct()])->get(route('checkout.guest.show'))
            ->assertOk()->assertSee('vos billets vous sont envoyés par email');
    }

    public function test_login_returns_to_checkout_with_selection_kept(): void
    {
        DB::table('users')->where('id', 1)->update(['password' => Hash::make('secret-pass-1')]);
        $selection = $this->direct();

        $this->withSession(['direct_booking' => $selection])->get(route('checkout.guest.show'))->assertOk();
        $this->assertSame(route('checkout.show'), session('url.intended'));

        $this->post(route('login'), ['email' => 'acheteur@example.test', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('checkout.show'));
        $this->assertAuthenticatedAs(User::find(1));
        $this->assertSame($selection, session('direct_booking'));

        $this->get(route('checkout.show'))->assertOk()->assertSee('Concert')->assertSee('Standard');
    }

    public function test_intended_is_server_built_and_ignores_client_supplied_urls(): void
    {
        DB::table('users')->where('id', 1)->update(['password' => Hash::make('secret-pass-1')]);

        $this->withSession(['direct_booking' => $this->direct()])
            ->get(route('checkout.guest.show').'?redirect=https://evil.example&intended=https://evil.example')->assertOk();

        $response = $this->post(route('login'), [
            'email' => 'acheteur@example.test', 'password' => 'secret-pass-1', 'redirect' => 'https://evil.example',
        ]);
        $response->assertRedirect(route('checkout.show'));
        $this->assertStringNotContainsString('evil.example', (string) $response->headers->get('Location'));
    }

    public function test_guest_confirmation_source_reminds_to_log_in(): void
    {
        $src = file_get_contents(dirname(__DIR__, 2).'/resources/views/checkout/guest-confirmation.blade.php');
        $this->assertStringContainsString("route('login')", $src);
        $this->assertStringContainsString('Pour retrouver vos billets plus tard', $src);
    }
}
