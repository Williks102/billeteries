<?php

namespace Tests\Feature;

use App\Helpers\DurationHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Charte visuelle, étape 3 : panier et checkout (mobile d'abord). Délai de réservation honnête (valeur configurée),
 * cibles tactiles, libellés reliés aux champs, bouton « Traitement… », décompte avec repli texte.
 */
class CartCheckoutUiTest extends TestCase
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
        Mail::fake();
    }

    private function cart(): array
    {
        $type = $this->ticketType(10);

        return ['k1' => [
            'ticket_type_id' => $type->id, 'event_id' => 1, 'quantity' => 2, 'ticket_name' => 'Standard',
            'unit_price' => 2500, 'total_price' => 5000, 'event_title' => 'Concert', 'event_image' => null,
            'event_date' => '01/01/2027', 'event_venue' => 'Palais',
        ]];
    }

    private function src(string $path): string
    {
        $file = dirname(__DIR__, 2).'/'.$path;
        $this->assertFileExists($file);

        return file_get_contents($file);
    }

    /** @return array<string, array{0:int,1:string}> */
    public static function durations(): array
    {
        return [
            '2880' => [2880, '48 heures'], '1440' => [1440, '24 heures'], '90' => [90, '1 h 30'],
            '60' => [60, '1 heure'], '45' => [45, '45 minutes'], '1' => [1, '1 minute'], '125' => [125, '2 h 05'],
        ];
    }

    #[DataProvider('durations')]
    public function test_duration_helper_formats_french(int $minutes, string $expected): void
    {
        $this->assertSame($expected, DurationHelper::formatMinutes($minutes));
    }

    public function test_reservation_window_follows_config_per_journey(): void
    {
        config(['payments.manual_reservation_minutes' => 90, 'payments.guest_manual_reservation_minutes' => 45]);
        $this->assertSame('1 h 30', DurationHelper::reservationLabel(false));
        $this->assertSame('45 minutes', DurationHelper::reservationLabel(true));
    }

    public function test_authenticated_checkout_shows_the_configured_connected_duration(): void
    {
        config(['payments.manual_reservation_minutes' => 600, 'payments.guest_manual_reservation_minutes' => 120]);

        $this->actingAs(User::find(1))->withSession(['cart' => $this->cart()])->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Après validation, vos places sont réservées pendant', false)
            ->assertSee('<strong>10 heures</strong>', false)
            ->assertDontSee('<strong>2 heures</strong>', false);
    }

    public function test_guest_checkout_shows_the_configured_guest_duration(): void
    {
        config(['payments.manual_reservation_minutes' => 600, 'payments.guest_manual_reservation_minutes' => 150]);

        $this->withSession(['cart' => $this->cart()])->get(route('checkout.guest.show'))
            ->assertOk()
            ->assertSee('<strong>2 h 30</strong>', false)
            ->assertDontSee('<strong>10 heures</strong>', false);
    }

    public function test_info_pages_use_the_configured_duration(): void
    {
        config(['payments.manual_reservation_minutes' => 600, 'payments.guest_manual_reservation_minutes' => 150]);

        foreach (['pages.how-it-works', 'pages.terms'] as $route) {
            $res = $this->get(route($route));
            $res->assertOk()->assertSee('10 heures', false)->assertSee('2 h 30', false);
        }
    }

    public function test_no_hardcoded_48h_in_cart_and_checkout_views(): void
    {
        $dirs = [resource_path('views/cart'), resource_path('views/checkout')];
        foreach ($dirs as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
                if ($f->isFile()) {
                    $this->assertDoesNotMatchRegularExpression('/\b48\s?(h|heures)\b|stock réservé|60 minutes/i', file_get_contents($f->getPathname()), $f->getFilename());
                }
            }
        }
    }

    public function test_cart_shows_no_fake_timer_and_states_empty_cart_with_link(): void
    {
        $page = $this->withSession(['cart' => $this->cart()])->get(route('cart.show'))->assertOk();
        $page->assertDontSee('countdown', false)->assertDontSee('Temps restant', false)->assertDontSee('réservé pendant');

        $this->flushSession();
        $this->get(route('cart.show'))->assertOk()->assertSee('Votre panier est vide')->assertSee(route('events.all'), false);
    }

    public function test_cart_controls_have_aria_labels_numeric_input_and_totals_in_fcfa(): void
    {
        $page = $this->withSession(['cart' => $this->cart()])->get(route('cart.show'))->assertOk();
        $page->assertSee('aria-label="Retirer Standard du panier"', false)
            ->assertSee('aria-label="Diminuer la quantité de Standard"', false)
            ->assertSee('aria-label="Augmenter la quantité de Standard"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee(\App\Helpers\CurrencyHelper::formatFCFA(5000), false)
            ->assertSee('Passer la commande')
            ->assertSee('cart-checkout-bar', false);
    }

    public function test_touch_targets_are_at_least_44px_in_the_stylesheets(): void
    {
        $charte = $this->src('public/css/charte.css');
        $this->assertMatchesRegularExpression('/--touch-min:\s*44px/', $charte);

        $css = $this->src('public/css/parcours-achat.css');
        foreach (['.qty-btn, .cart-remove', '.qty-input', '.pm-card__box', '.parcours-card .form-control', '.btn-block-touch'] as $selector) {
            $this->assertStringContainsString($selector, $css);
        }
        $this->assertMatchesRegularExpression('/\.qty-btn, \.cart-remove \{[^}]*min-width: var\(--touch-min\); min-height: var\(--touch-min\)/', $css);
        $this->assertMatchesRegularExpression('/\.qty-input \{[^}]*min-height: var\(--touch-min\)/', $css);
        $this->assertMatchesRegularExpression('/\.pm-card__box \{[^}]*min-height: var\(--touch-min\)/', $css);
        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $css, 'jetons de charte uniquement, pas d\'hexadécimal en dur');
    }

    /** Même méthode que CharteEtape2Test : chaque champ a un <label for="id"> (ou aria-label). */
    public function test_checkout_fields_are_labelled_and_errors_sit_under_the_field(): void
    {
        foreach (['show', 'guest'] as $view) {
            $src = $this->src("resources/views/checkout/$view.blade.php");
            preg_match_all('/<label\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*\bfor="([^"]+)"/', $src, $labels);
            preg_match_all('/<input\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/', $src, $inputs);
            $this->assertNotEmpty($inputs[1]);
            foreach ($inputs[1] as $attrs) {
                if (preg_match('/type="(hidden|radio|checkbox)"/', $attrs)) {
                    continue;
                }
                $this->assertSame(1, preg_match('/\bid="([^"]+)"/', $attrs, $m), "champ sans id dans $view");
                $this->assertContains($m[1], $labels[1], "champ {$m[1]} sans label dans $view");
                $this->assertStringContainsString('autocomplete=', $attrs);
            }
            $this->assertStringContainsString('<x-field-error', $src);
        }
        // Les cartes de paiement (radio) et la case des conditions sont aussi reliées à leur libellé.
        $this->assertStringContainsString('for="{{ $prefix }}{{ $methodKey }}"', $this->src('resources/views/components/payment-method-cards.blade.php'));
        $this->assertStringContainsString('for="{{ $termsId }}"', $this->src('resources/views/components/checkout-submit.blade.php'));
    }

    public function test_validation_errors_render_under_the_field_with_aria(): void
    {
        $this->withSession(['cart' => $this->cart()])
            ->from(route('checkout.guest.show'))
            ->post(route('checkout.guest.process'), ['email' => 'pas-un-email'])
            ->assertSessionHasErrors('email');

        $page = $this->withSession(['cart' => $this->cart(), 'errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag(['email' => ['Email invalide']]))])
            ->get(route('checkout.guest.show'));
        $page->assertSee('id="email-error"', false)->assertSee('aria-invalid="true"', false)->assertSee('Email invalide');
    }

    public function test_submit_button_has_processing_state_and_script_guards_double_submit(): void
    {
        $html = $this->actingAs(User::find(1))->withSession(['cart' => $this->cart()])->get(route('checkout.show'))->assertOk();
        $html->assertSee('data-submit-once', false)->assertSee('data-loading-label="Traitement…"', false)
            ->assertSee('js/checkout-form.js', false)
            ->assertDontSee('Frais de service');

        $js = $this->src('public/js/checkout-form.js');
        $this->assertStringContainsString('button.disabled = true', $js);
        $this->assertStringContainsString("addEventListener('submit'", $js);
        $this->assertStringContainsString("'invalid'", $js);
        $this->assertSame(1, substr_count($html->getContent(), 'data-submit-once'), 'un seul bouton de validation');

        $this->withSession(['cart' => $this->cart()])->get(route('checkout.guest.show'))->assertOk()->assertSee('data-submit-once', false);
    }

    public function test_pending_confirmation_has_countdown_with_text_fallback(): void
    {
        $type = $this->ticketType(10);
        $order = $this->reservedOrder($type, 1, 'pending', now()->addHours(5))->fresh();
        DB::table('orders')->where('id', $order->id)->update(['guest_token' => 'tok-ui-1']);

        $res = $this->withSession(['guest_order_tokens.'.$order->id => 'tok-ui-1'])->get(route('checkout.guest.confirmation', 'tok-ui-1'));
        $res->assertOk()
            ->assertSee('À régler avant le', false)
            ->assertSee($order->expires_at->format('d/m/Y à H:i'))
            ->assertSee('data-deadline="'.$order->expires_at->toIso8601String().'"', false)
            ->assertSee('js/deadline-countdown.js', false);
        // Repli sans JavaScript : l'élément de décompte est masqué, le texte statique reste visible.
        $this->assertMatchesRegularExpression('/class="deadline-countdown"[^>]*\bhidden\b/', $res->getContent());

        $js = $this->src('public/js/deadline-countdown.js');
        $this->assertStringContainsString('el.hidden = false', $js);
    }

    // ===== Parcours simplifié : « Réserver maintenant » =====

    public function test_direct_booking_for_a_connected_buyer_skips_the_cart_page(): void
    {
        $type = $this->ticketType(10);
        $this->actingAs(User::find(1))->post(route('checkout.direct'), ['event_id' => 1, 'tickets' => [$type->id => 2]])
            ->assertRedirect(route('checkout.show'))->assertSessionHas('direct_booking');

        $this->get(route('checkout.show'))->assertOk()->assertSee(\App\Helpers\CurrencyHelper::formatFCFA(2000), false);
    }

    public function test_direct_booking_for_a_guest_completes_without_touching_the_cart(): void
    {
        $type = $this->ticketType(10);
        $other = $this->cart();
        $this->withSession(['cart' => $other])
            ->post(route('checkout.direct'), ['event_id' => 1, 'tickets' => [$type->id => 3]])
            ->assertRedirect(route('checkout.guest.show'));
        $this->get(route('checkout.guest.show'))->assertOk()->assertSee(\App\Helpers\CurrencyHelper::formatFCFA(3000), false);

        $this->post(route('checkout.guest.process'), [
            'email' => 'invite@example.test', 'first_name' => 'Test', 'last_name' => 'Invite',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
        ])->assertRedirect();

        $order = \App\Models\Order::sole();
        $this->assertSame(3000, $order->total_amount); // prix recalculé côté serveur
        $this->assertNull(session('direct_booking'));
        $this->assertNotNull(session('cart'), 'le panier existant n\'est pas vidé par une réservation directe');
    }

    public function test_direct_booking_validates_input_and_stock(): void
    {
        $type = $this->ticketType(2);
        $this->post(route('checkout.direct'), ['event_id' => 1])->assertSessionHasErrors('tickets');
        $this->post(route('checkout.direct'), ['event_id' => 99, 'tickets' => [$type->id => 1]])->assertSessionHasErrors('event_id');
        $this->post(route('checkout.direct'), ['event_id' => 1, 'tickets' => [$type->id => 5]])->assertSessionHas('error');
        $this->post(route('checkout.direct'), ['event_id' => 1, 'tickets' => [$type->id => 0]])->assertSessionHas('error');
        $this->assertNull(session('direct_booking'));
    }

    public function test_direct_route_is_throttled_and_open_to_guests(): void
    {
        $route = app('router')->getRoutes()->getByName('checkout.direct');
        $this->assertTrue(collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with((string) $m, 'throttle:')));
        $this->assertNotContains('auth', $route->gatherMiddleware());
    }

    public function test_viewing_the_cart_drops_a_stale_direct_booking(): void
    {
        $this->withSession(['direct_booking' => $this->cart(), 'cart' => $this->cart()])->get(route('cart.show'))->assertOk();
        $this->assertNull(session('direct_booking'));
    }

    public function test_event_page_offers_reserve_now_as_primary_with_a_single_price_and_clean_time(): void
    {
        $this->ticketType(10);
        $res = $this->get(route('events.show', 1))->assertOk();
        $html = $res->getContent();

        $this->assertGreaterThanOrEqual(2, substr_count($html, 'action="'.route('checkout.direct').'"'), 'feuille mobile ET formulaire desktop');
        $this->assertStringContainsString('id="reserveNowBtn"', $html);
        $this->assertStringContainsString('id="reserveNowDesktop"', $html);
        $this->assertSame(1, substr_count($html, 'À partir de'), 'un seul prix « À partir de »');
        $this->assertDoesNotMatchRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', strip_tags(preg_replace('/<script\b.*?<\/script>/s', '', $html)), 'aucun datetime brut');
        $this->assertStringContainsString('08:30', $html);
        $this->assertDoesNotMatchRegularExpression('/<img(?![^>]*\bsrc=)[^>]*>/i', $html, 'aucune balise img sans source');
    }

    public function test_steps_indicator_is_the_same_everywhere(): void
    {
        $cart = $this->cart();
        foreach ([
            $this->withSession(['cart' => $cart])->get(route('cart.show')),
            $this->withSession(['cart' => $cart])->get(route('checkout.guest.show')),
            $this->actingAs(User::find(1))->withSession(['cart' => $cart])->get(route('checkout.show')),
        ] as $res) {
            $this->assertSame(1, substr_count($res->getContent(), 'class="parcours-steps"'), 'une seule barre d\'étapes');
            $res->assertSee('Billets')->assertSee('Informations')->assertSee('Paiement');
        }
        $this->assertStringNotContainsString('Sélection', $this->withSession(['cart' => $cart])->get(route('checkout.guest.show'))->getContent());
    }

    public function test_cart_renders_its_lines_and_no_floating_cart_button(): void
    {
        $page = $this->withSession(['cart' => $this->cart()])->get(route('cart.show'))->assertOk();
        $this->assertSame(1, substr_count($page->getContent(), 'class="cart-line"'));
        $page->assertSee('Concert')->assertSee('Standard')->assertDontSee('mobile-cart-button', false)->assertDontSee('Mon Panier (', false);
        $this->assertDoesNotMatchRegularExpression('/<img(?![^>]*\bsrc=)[^>]*>/i', $page->getContent());
    }

    public function test_confirmation_shows_the_success_message_once_and_makes_no_false_claims(): void
    {
        $type = $this->ticketType(10);
        $order = $this->reservedOrder($type, 1, 'pending', now()->addHours(5))->fresh();
        DB::table('orders')->where('id', $order->id)->update(['guest_token' => 'tok-ui-2']);
        $msg = \App\Services\CheckoutService::successMessage(1);

        $res = $this->withSession(['success' => $msg, 'guest_order_tokens.'.$order->id => 'tok-ui-2'])->get(route('checkout.guest.confirmation', 'tok-ui-2'));
        $this->assertSame(1, substr_count($res->getContent(), $msg), 'un seul message de confirmation');
        $this->assertStringNotContainsString('envoyées par email', $msg);

        $this->withSession(['cart' => $this->cart()])->get(route('checkout.guest.show'))
            ->assertDontSee('email instantané')->assertDontSee('100% sécurisé')->assertDontSee('✅')
            ->assertSee('Billets (avec QR code) disponibles dès la confirmation de votre paiement')
            ->assertSee('Aucun compte nécessaire');
    }

    public function test_checkout_line_layout_and_payment_cards_are_accessible(): void
    {
        $html = $this->actingAs(User::find(1))->withSession(['cart' => $this->cart()])->get(route('checkout.show'))->getContent();
        $this->assertStringContainsString('<fieldset class="pm-group"', $html);
        $this->assertStringContainsString('<legend>Mode de paiement</legend>', $html);
        $this->assertStringContainsString('Standard &times; 2', $html);
        $this->assertDoesNotMatchRegularExpression('/<img(?![^>]*\bsrc=)[^>]*>/i', $html);

        $css = $this->src('public/css/parcours-achat.css');
        $this->assertMatchesRegularExpression('/\.pm-card__input:checked \+ \.pm-card__box \{[^}]*border-color: var\(--brand\); background: var\(--brand-soft\)/', $css);
    }

    public function test_processing_state_only_on_real_submit_and_reset_on_failure_or_back(): void
    {
        $js = $this->src('public/js/checkout-form.js');
        $this->assertStringContainsString("form.addEventListener('submit'", $js);
        $this->assertStringContainsString("addEventListener('invalid'", $js);
        $this->assertStringContainsString("'pageshow'", $js);
        $this->assertStringContainsString('event.persisted', $js);
        // L'état « Traitement… » n'est posé que dans le gestionnaire de submit (jamais au chargement)
        $this->assertSame(1, substr_count($js, 'textContent = button.dataset.loadingLabel'));
    }

    // ===== Gratuit et images absentes =====

    public function test_format_price_shows_gratuit_for_zero_only(): void
    {
        $this->assertSame('Gratuit', \App\Helpers\CurrencyHelper::formatPrice(0));
        $this->assertSame('Gratuit', \App\Helpers\CurrencyHelper::formatPrice(null));
        $this->assertSame(\App\Helpers\CurrencyHelper::formatFCFA(10000), \App\Helpers\CurrencyHelper::formatPrice(10000));
        $this->assertSame("0\u{00A0}FCFA", \App\Helpers\CurrencyHelper::formatFCFA(0), 'formatFCFA inchangé');
    }

    public function test_free_ticket_is_displayed_as_gratuit_in_the_journey(): void
    {
        $cart = $this->cart();
        $cart['k1']['unit_price'] = 0;
        $cart['k1']['total_price'] = 0;

        foreach ([
            $this->withSession(['cart' => $cart])->get(route('cart.show')),
            $this->withSession(['cart' => $cart])->get(route('checkout.guest.show')),
            $this->actingAs(User::find(1))->withSession(['cart' => $cart])->get(route('checkout.show')),
        ] as $res) {
            $res->assertOk()->assertSee('Gratuit')->assertDontSee("0\u{00A0}FCFA", false);
        }

        DB::table('ticket_types')->update(['price' => 0]);
        $html = $this->get(route('events.show', 1))->assertOk()->getContent();
        $this->assertStringNotContainsString("0\u{00A0}FCFA", $html);
        $this->assertStringContainsString('Gratuit', $html);
    }

    public function test_missing_event_image_never_renders_a_broken_img(): void
    {
        $cart = $this->cart();
        $cart['k1']['event_image'] = 'events/perdue-au-deploiement.jpg';

        foreach ([
            $this->withSession(['cart' => $cart])->get(route('cart.show')),
            $this->actingAs(User::find(1))->withSession(['cart' => $cart])->get(route('checkout.show')),
        ] as $res) {
            $res->assertOk()->assertDontSee('perdue-au-deploiement', false);
            $this->assertDoesNotMatchRegularExpression('/<img(?![^>]*\bsrc=)[^>]*>/i', $res->getContent());
        }
    }
}
