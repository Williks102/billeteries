<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Barre de navigation en bas (mobile) des layouts connectés : rendue pour le bon rôle, liens vers des routes
 * existantes, lien actif marqué (aria-current), styles mobile-seulement dans la charte.
 */
class BottomNavTest extends TestCase
{
    use BuildsOrderSchema;

    private function navOf(string $html): string
    {
        $this->assertSame(1, preg_match('#<nav class="bottom-nav".*?</nav>#s', $html, $m), 'Barre du bas absente');

        return $m[0];
    }

    /** @return array<int, string> routes ciblées par la barre */
    private function hrefs(string $nav): array
    {
        preg_match_all('#<a href="([^"]+)"#', $nav, $m);

        return $m[1];
    }

    public function test_each_role_gets_its_own_bottom_nav_with_existing_routes(): void
    {
        $this->buildOrderSchema(); // acheteur n° 1
        $this->makeUser(2, 'promoteur');
        $this->makeUser(10, 'admin');

        $cases = [
            [1, 'acheteur.tickets.index', ['acheteur.dashboard', 'acheteur.tickets.index', 'acheteur.orders.index', 'acheteur.profile.show']],
            [2, 'promoteur.sales.index', ['promoteur.dashboard', 'promoteur.events.index', 'promoteur.sales.index', 'promoteur.profile.show']],
            [10, 'admin.users.index', ['admin.dashboard', 'admin.events.index', 'admin.orders.index', 'admin.users.index']],
        ];

        foreach ($cases as [$id, $activeRoute, $expected]) {
            $this->app['auth']->forgetGuards();
            $res = $this->actingAs(User::find($id))->get(route($activeRoute));
            $res->assertOk()->assertSee('<body class="has-bottom-nav">', false);
            $nav = $this->navOf($res->getContent());

            $this->assertSame(array_map(fn ($r) => route($r), $expected), $this->hrefs($nav));
            foreach ($expected as $name) {
                $this->assertTrue(Route::has($name), "Route $name inexistante");
            }
            $this->assertLessThanOrEqual(5, count($expected));

            // Seul le lien de la page courante est marqué.
            $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
            $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route($activeRoute), '#').'"[^>]*aria-current="page"#', $nav);
            $this->assertStringNotContainsString('frais de service', mb_strtolower($nav));
        }
    }

    public function test_dashboard_link_is_active_on_dashboard_only(): void
    {
        $this->buildOrderSchema();
        $nav = $this->navOf($this->actingAs(User::find(1))->get(route('acheteur.dashboard'))->getContent());
        $this->assertMatchesRegularExpression('#<a href="'.preg_quote(route('acheteur.dashboard'), '#').'"[^>]*aria-current="page"#', $nav);
        $this->assertSame(1, substr_count($nav, 'aria-current="page"'));
    }

    public function test_css_is_mobile_only_with_safe_area_touch_targets_and_content_padding(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/public/css/charte.css');
        $this->assertStringContainsString('.bottom-nav { display: none; }', $css);
        $this->assertMatchesRegularExpression('/@media \(max-width: 767\.98px\) \{\s*\.bottom-nav \{\s*display: block; position: fixed/', $css);
        foreach (['env(safe-area-inset-bottom', 'min-height: var(--bottom-nav-height)', 'min-width: var(--touch-min)', 'body.has-bottom-nav { padding-bottom', 'body.has-bottom-nav .action-bar--sticky'] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }
        $this->assertMatchesRegularExpression('/--bottom-nav-height: (4[4-9]|[5-9]\d)px/', $css);
    }
}
