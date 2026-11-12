<?php

namespace Tests\Feature\Stats;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Chaque lien du menu / de la barre (layouts promoteur et admin) qui porte un paramètre de période doit être accepté
 * par l'écran de destination : jamais de « Période inconnue » (anciens paramètres week, month, year...).
 */
class MenuLinksUsePeriodsTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->makeUser(10, 'admin');
    }

    /** @return list<string> liens href internes (chemin + requête) trouvés dans la page */
    private function links(string $html): array
    {
        preg_match_all('/<a\b[^>]*\shref="([^"#]+)"/i', $html, $m);
        $base = rtrim(config('app.url'), '/');
        $out = [];
        foreach ($m[1] as $href) {
            $href = html_entity_decode($href);
            if (str_starts_with($href, $base)) {
                $href = substr($href, strlen($base)) ?: '/';
            }
            if (! str_starts_with($href, '/') || str_contains($href, 'logout')) {
                continue;
            }
            $out[$href] = true;
        }

        return array_keys($out);
    }

    private function assertMenuLinksAccepted(User $user, string $startRoute, string $prefix): void
    {
        $html = $this->actingAs($user)->get(route($startRoute))->assertOk()->getContent();
        $followed = 0;

        foreach ($this->links($html) as $link) {
            if (! str_starts_with($link, $prefix) || ! str_contains($link, 'period=') && ! str_contains($link, 'date_')) {
                continue;
            }
            $res = $this->actingAs($user)->get($link);
            $followed++;
            $this->assertContains($res->getStatusCode(), [200, 302], "{$link} : statut inattendu");
            $res->assertSessionHasNoErrors("{$link} : période refusée");
            $res->assertSessionMissing('errors');
            if ($res->isRedirect()) {
                $this->actingAs($user)->followingRedirects()->get($link)->assertOk()->assertDontSee('Période inconnue');
            }
        }

        $this->assertGreaterThan(0, $followed, 'Aucun lien de période trouvé dans le menu');
    }

    public function test_promoteur_menu_period_links_are_accepted(): void
    {
        $this->assertMenuLinksAccepted(User::find(self::P), 'promoteur.dashboard', '/promoteur');
    }

    public function test_admin_menu_and_screens_links_with_a_period_are_accepted(): void
    {
        DB::table('users')->update(['created_at' => '2026-10-01 08:00:00']);
        $user = User::find(10);
        $admin = $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->getContent();
        foreach ($this->links($admin) as $link) {
            if (! str_starts_with($link, '/admin') || ! (str_contains($link, 'period=') || str_contains($link, 'date_'))) {
                continue;
            }
            $this->actingAs($user)->get($link)->assertSessionMissing('errors');
        }
        // les écrans de statistiques admin répondent sans erreur de période pour chaque preset proposé
        foreach (['admin.dashboard', 'admin.commissions', 'admin.revenues'] as $route) {
            foreach (DatePeriodPresets::ALL as $preset) {
                $this->actingAs($user)->get(route($route, ['period' => $preset]))->assertOk();
            }
        }
    }
}

final class DatePeriodPresets
{
    public const ALL = ['today', '7d', '30d', 'this_month', 'last_month', 'this_year', 'all'];
}
