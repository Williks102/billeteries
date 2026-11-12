<?php

namespace Tests\Feature;

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Charte visuelle, étape 2 : jetons bleu repère + états billetterie (contrastes AA calculés sur charte.css),
 * badges de disponibilité, montants FCFA via CurrencyHelper, libellés de champs, pages d'authentification.
 * Les contrôles de vues sont des analyses de source (aucun accès base ni HTTP).
 */
class CharteEtape2Test extends TestCase
{
    /** Vues dont les montants ont été migrés vers CurrencyHelper : plus aucun number_format( autorisé. */
    private const MIGRATED_MONEY = [
        'cart/show', 'checkout/confirmation', 'checkout/guest-confirmation', 'checkout/guest', 'checkout/show',
        'events/show', 'events/all', 'events/partials/booking-form-desktop', 'events/partials/booking-form-mobile',
        'events/partials/event-details-desktop', 'events/partials/event-details-mobile',
        'acheteur/dashboard', 'acheteur/orders', 'acheteur/order-detail',
        'components/event-card', 'components/events-carousel',
        'emails/admin-new-order', 'emails/order-confirmation', 'emails/payment-confirmation', 'emails/promoteur-new-sale',
        'promoteur/dashboard', 'promoteur/events/tickets/index',
    ];

    /** Vues de formulaires migrées : tout champ doit avoir un libellé (for=id), un aria-label ou un aria-labelledby. */
    private const MIGRATED_FORMS = [
        'events/_form', 'promoteur/events/edit', 'promoteur/events/index', 'promoteur/profile', 'events/all',
        'admin/events/create', 'admin/events/edit', 'admin/events/index', 'admin/orders/index',
        'auth/login', 'auth/register', 'auth/passwords/email', 'auth/passwords/reset', 'auth/passwords/confirm',
    ];

    private function src(string $name): string
    {
        $path = dirname(__DIR__, 2).'/resources/views/'.$name.'.blade.php';
        $this->assertFileExists($path);

        return file_get_contents($path);
    }

    private function css(): string
    {
        return file_get_contents(dirname(__DIR__, 2).'/public/css/charte.css');
    }

    private function token(string $name): string
    {
        $this->assertSame(1, preg_match('/--'.preg_quote($name, '/').'\s*:\s*(#[0-9a-fA-F]{6})\s*;/', $this->css(), $m), "Jeton --$name absent ou non hexadécimal");

        return $m[1];
    }

    private function luminance(string $hex): float
    {
        $c = array_map(function ($i) use ($hex) {
            $v = hexdec(substr($hex, $i, 2)) / 255;

            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, [1, 3, 5]);

        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    private function contrast(string $a, string $b): float
    {
        $x = $this->luminance($a);
        $y = $this->luminance($b);

        return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
    }

    public function test_link_blue_and_billetterie_state_tokens_pass_aa(): void
    {
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($this->token('link'), '#ffffff'), '--link sur blanc');
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($this->token('link-hover'), '#ffffff'), '--link-hover sur blanc');
        $this->assertGreaterThanOrEqual(4.5, $this->contrast($this->token('link-ink'), $this->token('link-soft')), '--link-ink sur --link-soft');

        foreach (['available', 'low', 'soldout', 'refunded', 'pending', 'failed', 'success'] as $state) {
            $fg = $this->token("state-$state");
            $bg = $this->token("state-$state-soft");
            $this->assertGreaterThanOrEqual(4.5, $this->contrast($fg, '#ffffff'), "--state-$state sur blanc");
            $this->assertGreaterThanOrEqual(4.5, $this->contrast($fg, $bg), "--state-$state sur son fond doux");
        }
    }

    public function test_state_badge_classes_exist(): void
    {
        $css = $this->css();
        foreach (['available', 'low', 'soldout', 'pending', 'paid', 'failed', 'refunded'] as $variant) {
            $this->assertStringContainsString('.badge-state--'.$variant, $css);
        }
    }

    public function test_availability_badge_renders_label_and_icon_and_follows_the_threshold(): void
    {
        config(['payments.low_stock_threshold' => 10]);
        $html = Blade::render('<x-ticket-availability-badge :remaining="$n" />', ['n' => 50]);
        $this->assertStringContainsString('badge-state--available', $html);
        $this->assertStringContainsString('Disponible', $html);
        $this->assertStringContainsString('fa-check-circle', $html);

        $html = Blade::render('<x-ticket-availability-badge :remaining="$n" />', ['n' => 7]);
        $this->assertStringContainsString('badge-state--low', $html);
        $this->assertStringContainsString('Bientôt complet', $html);
        $this->assertStringContainsString('7 restants', $html);
        $this->assertStringContainsString('fa-exclamation-triangle', $html);

        $html = Blade::render('<x-ticket-availability-badge :remaining="$n" />', ['n' => 0]);
        $this->assertStringContainsString('badge-state--soldout', $html);
        $this->assertStringContainsString('Complet', $html);
        $this->assertStringContainsString('fa-times-circle', $html);

        // Seuil configurable : 50 restants devient « bientôt complet » avec un seuil de 60.
        config(['payments.low_stock_threshold' => 60]);
        $this->assertStringContainsString('badge-state--low', Blade::render('<x-ticket-availability-badge :remaining="50" />'));
        $this->assertStringContainsString('badge-state--available', Blade::render('<x-ticket-availability-badge :remaining="50" :threshold="10" />'));
    }

    public function test_low_stock_threshold_config_key_defaults_to_10(): void
    {
        $this->assertArrayHasKey('low_stock_threshold', require dirname(__DIR__, 2).'/config/payments.php');
        $this->assertSame(10, (int) config('payments.low_stock_threshold'));
    }

    public function test_order_status_badge_uses_state_variants(): void
    {
        $html = Blade::render('<x-order-status-badge :order="$o" />', ['o' => (object) ['payment_status' => 'refunded']]);
        $this->assertStringContainsString('badge-state--refunded', $html);
        $this->assertStringContainsString('Remboursée', $html);
        $html = Blade::render('<x-order-status-badge :order="$o" />', ['o' => (object) ['payment_status' => 'paid']]);
        $this->assertStringContainsString('badge-state--paid', $html);
    }

    public function test_currency_helper_format(): void
    {
        $this->assertSame("10\u{00A0}000\u{00A0}FCFA", CurrencyHelper::formatFCFA(10000));
        $this->assertSame("0\u{00A0}FCFA", CurrencyHelper::formatFCFA(null));
        $this->assertSame("1\u{00A0}250\u{00A0}000", CurrencyHelper::formatNumber(1250000));
        $this->assertSame(10000, CurrencyHelper::parseFCFA(CurrencyHelper::formatFCFA(10000)));
        $this->assertStringNotContainsString('€', CurrencyHelper::formatFCFA(500));
    }

    public function test_migrated_views_have_no_number_format(): void
    {
        foreach (self::MIGRATED_MONEY as $name) {
            $this->assertStringNotContainsString('number_format(', $this->src($name), "$name : utiliser CurrencyHelper");
        }
    }

    public function test_money_in_a_mail_view_renders_with_the_helper(): void
    {
        $html = Blade::render('{{ \App\Helpers\CurrencyHelper::formatFCFA($n) }}', ['n' => 25000]);
        $this->assertStringContainsString("25\u{00A0}000\u{00A0}FCFA", $html);
    }

    /**
     * Analyse prudente du source Blade (pas du rendu) : chaque <input|select|textarea> (hors hidden) doit avoir
     * un id référencé par un <label for="même id"> du même fichier, ou un aria-label / aria-labelledby.
     * Les balises sont lues en tolérant les guillemets (donc les {{ $a->b }} dans les attributs).
     */
    public function test_form_controls_have_labels_in_migrated_views(): void
    {
        foreach (self::MIGRATED_FORMS as $name) {
            $src = $this->src($name);
            preg_match_all('/<label\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*\bfor="([^"]+)"/', $src, $labels);
            $forIds = $labels[1];
            preg_match_all('/<(input|select|textarea)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/', $src, $tags, PREG_SET_ORDER);
            $this->assertNotEmpty($tags, $name);
            foreach ($tags as $t) {
                $attrs = $t[2];
                if (preg_match('/type="(hidden|submit|button)"/', $attrs)) {
                    continue;
                }
                $hasAria = str_contains($attrs, 'aria-label=') || str_contains($attrs, 'aria-labelledby=');
                $hasLabel = preg_match('/\bid="([^"]+)"/', $attrs, $m) && in_array($m[1], $forIds, true);
                $this->assertTrue($hasAria || $hasLabel, "$name : champ sans libellé : ".substr(trim($t[0]), 0, 120));
            }
        }
    }

    public function test_auth_pages_include_the_charte(): void
    {
        foreach (['auth/login', 'auth/register', 'auth/passwords/email', 'auth/passwords/reset'] as $name) {
            $this->assertStringContainsString("asset('css/charte.css')", $this->src($name), "$name doit charger charte.css");
        }
        foreach (['auth/login', 'auth/register'] as $name) {
            $this->assertStringContainsString('<x-field-error', $this->src($name), "$name : erreurs sous les champs");
            $this->assertStringNotContainsString('form-floating', $this->src($name), "$name : libellé visible au-dessus du champ");
        }
    }

    public function test_promoteur_layout_cannot_overflow_horizontally(): void
    {
        $this->assertMatchesRegularExpression('/\.promoteur-main\s*\{[^}]*min-width:\s*0/s', $this->src('layouts/promoteur'));
    }
}
