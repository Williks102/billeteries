<?php

namespace Tests\Feature\Stats;

use App\Helpers\CurrencyHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Écrans admin branchés sur les services Stats (L3), jeu canonique (StatsFixture), « maintenant » = 2026-11-15.
 * Période par défaut = novembre 2026 : ventes 55 000 (O1 20 000 + O4 15 000 + O5 gratuite 0 + O7 20 000),
 * commission plateforme 3 300 (1 200 + 900 + 1 200), net promoteurs 51 700 (à verser 37 600, versé 14 100).
 * Ni O2 (en attente), ni O3 (remboursée), ni O6 (échouée), ni O8 (octobre), ni O9 (novembre 2025) n'y entrent.
 */
class AdminScreensTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->makeUser(10, 'admin');
        // dates d'inscription / de création (le jeu canonique n'en pose pas)
        DB::table('users')->update(['created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00']);
        DB::table('users')->where('id', self::P2)->update(['created_at' => '2026-11-04 08:00:00']);
        DB::table('users')->where('id', self::BUYER2)->update(['created_at' => '2025-06-01 08:00:00']);
        DB::table('events')->update(['created_at' => '2026-11-02 08:00:00', 'updated_at' => '2026-11-02 08:00:00']);
    }

    private function admin(): static
    {
        return $this->actingAs(User::find(10));
    }

    /** Valeur affichée (texte nettoyé, espaces insécables normalisés) de la carte `data-stat="<slug>"`. */
    private function card(string $html, string $slug): string
    {
        $this->assertSame(1, preg_match('/data-stat="'.preg_quote($slug, '/').'".*?kpi-card__value">(.*?)<\/p>/s', $html, $m), "Carte {$slug} introuvable");

        return trim(preg_replace('/\s+/u', ' ', str_replace(CurrencyHelper::NBSP, ' ', html_entity_decode(strip_tags($m[1])))));
    }

    private function fcfa(int $v): string
    {
        return number_format($v, 0, ',', ' ').' FCFA';
    }

    // ---------- dashboard ----------

    public function test_dashboard_default_period_shows_exact_target_figures(): void
    {
        $html = $this->admin()->get(route('admin.dashboard'))->assertOk()->assertSee('Période affichée')->assertSee('Ce mois-ci')->getContent();

        $this->assertSame($this->fcfa(55000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(55000), $this->card($html, 'encaisse'));
        $this->assertSame($this->fcfa(3300), $this->card($html, 'commission-plateforme'));
        $this->assertSame($this->fcfa(37600), $this->card($html, 'net-promoteurs-a-verser'));
        $this->assertSame($this->fcfa(14100), $this->card($html, 'net-promoteurs-verse'));
        $this->assertSame('4', $this->card($html, 'commandes-payees'));
        $this->assertSame('1', $this->card($html, 'en-attente-de-paiement'));
        $this->assertSame('1', $this->card($html, 'remboursees'));
        $this->assertSame('6', $this->card($html, 'billets-vendus'));
        $this->assertSame('16,7 %', $this->card($html, 'taux-dutilisation'));
        $this->assertSame('57,1 %', $this->card($html, 'taux-de-paiement'));
        $this->assertSame($this->fcfa(13750), $this->card($html, 'panier-moyen'));

        // Valeurs simulées supprimées : plus de « +15 % » ni de conversion
        $html = strip_tags($html);
        $this->assertStringNotContainsString('+15', $html);
        $this->assertStringNotContainsStringIgnoringCase('conversion', $html);
    }

    public function test_dashboard_other_periods_give_their_own_figures(): void
    {
        // Mois dernier (octobre 2026) : 1 commande, 10 000
        $oct = $this->admin()->get(route('admin.dashboard', ['period' => 'last_month']))->assertOk()->assertSee('Mois dernier')->getContent();
        $this->assertSame($this->fcfa(10000), $this->card($oct, 'ventes-billets-hors-frais'));
        $this->assertSame('1', $this->card($oct, 'commandes-payees'));
        $this->assertSame($this->fcfa(600), $this->card($oct, 'commission-plateforme'));

        // Dates personnalisées sur octobre 2026
        $custom = $this->admin()->get(route('admin.dashboard', ['start_date' => '2026-10-01', 'end_date' => '2026-10-31']))->assertOk()->assertSee('Du 01/10/2026 au 31/10/2026')->getContent();
        $this->assertSame($this->fcfa(10000), $this->card($custom, 'ventes-billets-hors-frais'));

        // Année 2026 : novembre + octobre = 65 000, 5 commandes (O9 de 2025 exclue)
        $year = $this->admin()->get(route('admin.dashboard', ['period' => 'this_year']))->assertOk()->getContent();
        $this->assertSame($this->fcfa(65000), $this->card($year, 'ventes-billets-hors-frais'));
        $this->assertSame('5', $this->card($year, 'commandes-payees'));

        // Toute la période : 70 000, 6 commandes
        $all = $this->admin()->get(route('admin.dashboard', ['period' => 'all']))->assertOk()->getContent();
        $this->assertSame($this->fcfa(70000), $this->card($all, 'ventes-billets-hors-frais'));
        $this->assertSame('6', $this->card($all, 'commandes-payees'));
    }

    public function test_this_month_no_longer_accumulates_other_years(): void
    {
        $html = $this->admin()->get(route('admin.dashboard'))->getContent();

        // novembre 2025 (O9, 5 000) n'est plus cumulé : 55 000 et non 60 000
        $this->assertNotSame($this->fcfa(60000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(55000), $this->card($html, 'ventes-billets-hors-frais'));
    }

    public function test_pending_refunded_and_failed_orders_enter_no_sales_figure(): void
    {
        // O2 (pending), O3 (refunded) et O6 (failed) pèsent 30 000 en novembre : ils ne s'ajoutent à AUCUN chiffre de vente
        // (55 000 + 30 000 = 85 000 serait faux), et leurs commissions (held 600, cancelled 600) restent hors revenus (3 300, non 4 500).
        foreach (['admin.dashboard', 'admin.revenues', 'admin.reports.financial', 'admin.reports.sales', 'admin.reports.index', 'admin.orders.index'] as $route) {
            $html = $this->admin()->get(route($route))->assertOk()->getContent();
            $this->assertSame($this->fcfa(55000), $this->card($html, 'ventes-billets-hors-frais'), $route);
            $text = str_replace(CurrencyHelper::NBSP, ' ', strip_tags($html));
            $this->assertStringNotContainsString($this->fcfa(85000), $text, $route);
            $this->assertStringNotContainsString($this->fcfa(4500), $text, $route);
        }

        // Les commandes non payées n'apparaissent pas dans le rapport des ventes ; la remboursée est comptée à part
        $dash = $this->admin()->get(route('admin.dashboard'))->getContent();
        $this->assertSame('4', $this->card($dash, 'commandes-payees'));
        $this->assertSame('1', $this->card($dash, 'remboursees'));
    }

    // ---------- revenus, commissions, rapports ----------

    public function test_revenues_screen_figures_and_twelve_month_table(): void
    {
        $html = $this->admin()->get(route('admin.revenues'))->assertOk()->assertSee('12 derniers mois')->getContent();

        $this->assertSame($this->fcfa(55000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(3300), $this->card($html, 'commission-plateforme'));
        $this->assertSame($this->fcfa(51700), $this->card($html, 'net-promoteurs-a-verser-verse'));
        $this->assertSame('4', $this->card($html, 'commandes-payees'));
        // tableau mensuel : octobre 2026 = 10 000 / 600 / 9 400 ; novembre 2025 (O9) présent sur 12 mois
        $text = str_replace(CurrencyHelper::NBSP, ' ', strip_tags($html));
        $this->assertStringContainsString($this->fcfa(9400), $text);

        $oct = $this->admin()->get(route('admin.revenues', ['period' => 'last_month']))->getContent();
        $this->assertSame($this->fcfa(10000), $this->card($oct, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(600), $this->card($oct, 'commission-plateforme'));
        $year = $this->admin()->get(route('admin.revenues', ['period' => 'this_year']))->getContent();
        $this->assertSame($this->fcfa(65000), $this->card($year, 'ventes-billets-hors-frais'));
    }

    public function test_commissions_screen_figures(): void
    {
        $html = $this->admin()->get(route('admin.commissions'))->assertOk()->getContent();

        $this->assertSame($this->fcfa(3300), $this->card($html, 'commission-plateforme'));
        $this->assertSame($this->fcfa(37600), $this->card($html, 'net-promoteurs-a-verser'));
        $this->assertSame($this->fcfa(14100), $this->card($html, 'net-promoteurs-verse'));
        $this->assertSame($this->fcfa(600), $this->card($html, 'commissions-suspendues'));

        $oct = $this->admin()->get(route('admin.commissions', ['period' => 'last_month']))->getContent();
        $this->assertSame($this->fcfa(600), $this->card($oct, 'commission-plateforme'));
        $this->assertSame($this->fcfa(9400), $this->card($oct, 'net-promoteurs-a-verser'));
        $this->assertSame($this->fcfa(0), $this->card($oct, 'net-promoteurs-verse'));
    }

    public function test_reports_screens_figures(): void
    {
        $index = $this->admin()->get(route('admin.reports.index'))->assertOk()->getContent();
        $this->assertSame($this->fcfa(55000), $this->card($index, 'ventes-billets-hors-frais'));
        $this->assertSame('4', $this->card($index, 'commandes-payees'));
        $this->assertSame($this->fcfa(3300), $this->card($index, 'commission-plateforme'));

        $sales = $this->admin()->get(route('admin.reports.sales'))->assertOk()->assertSee('ORD-STATS-O1')->assertSee('ORD-STATS-O7')
            ->assertDontSee('ORD-STATS-O2')->assertDontSee('ORD-STATS-O3')->assertDontSee('ORD-STATS-O6')->assertDontSee('ORD-STATS-O8')->assertDontSee('ORD-STATS-O9')->getContent();
        $this->assertSame($this->fcfa(55000), $this->card($sales, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(13750), $this->card($sales, 'panier-moyen'));

        $financial = $this->admin()->get(route('admin.reports.financial'))->assertOk()->getContent();
        $this->assertSame($this->fcfa(55000), $this->card($financial, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(3300), $this->card($financial, 'commission-plateforme'));
        $this->assertSame($this->fcfa(37600), $this->card($financial, 'net-promoteurs-a-verser'));
        $this->assertSame($this->fcfa(14100), $this->card($financial, 'net-promoteurs-verse'));

        $finOct = $this->admin()->get(route('admin.reports.financial', ['period' => 'last_month']))->getContent();
        $this->assertSame($this->fcfa(10000), $this->card($finOct, 'ventes-billets-hors-frais'));

        $events = $this->admin()->get(route('admin.reports.events', ['period' => 'this_year']))->assertOk()->getContent();
        $this->assertSame($this->fcfa(65000), $this->card($events, 'ventes-de-la-periode-billets-hors-frais'));

        $users = $this->admin()->get(route('admin.reports.users', ['period' => 'this_year']))->assertOk()->getContent();
        $this->assertSame('4', $this->card($users, 'nouveaux-utilisateurs'));
        $thisMonth = $this->admin()->get(route('admin.reports.users'))->assertOk()->getContent();
        $this->assertSame('1', $this->card($thisMonth, 'nouveaux-utilisateurs'));
        $this->assertSame('1', $this->card($thisMonth, 'actifs'));
    }

    // ---------- listes : commandes, événements, utilisateurs ----------

    public function test_orders_index_events_index_and_details(): void
    {
        $orders = $this->admin()->get(route('admin.orders.index'))->assertOk()->getContent();
        $this->assertSame('7', $this->card($orders, 'commandes-creees'));
        $this->assertSame('4', $this->card($orders, 'commandes-payees'));
        $this->assertSame('1', $this->card($orders, 'en-attente-de-paiement'));
        $this->assertSame('1', $this->card($orders, 'echouees'));
        $this->assertSame('1', $this->card($orders, 'remboursees'));
        $this->assertSame($this->fcfa(55000), $this->card($orders, 'ventes-billets-hors-frais'));

        $events = $this->admin()->get(route('admin.events.index'))->assertOk()->getContent();
        $this->assertSame($this->fcfa(55000), $this->card($events, 'ventes-de-la-periode-billets-hors-frais'));

        // Fiche événement E (1) : ventes toutes dates 20 000 + 15 000 + 0 + 10 000 + 5 000 = 50 000 ; 7 billets vendus / 170
        $show = $this->admin()->get(route('admin.events.show', 1))->assertOk()->getContent();
        $this->assertSame($this->fcfa(50000), $this->card($show, 'ventes-billets-hors-frais'));
        $this->assertSame('7', $this->card($show, 'billets-vendus'));
        $this->assertSame('4,1 %', $this->card($show, 'taux-de-remplissage'));
        $this->assertSame('163', $this->card($show, 'places-disponibles'));
    }

    public function test_users_index_and_show(): void
    {
        $users = $this->admin()->get(route('admin.users.index'))->assertOk()->getContent();
        $this->assertSame('5', $this->card($users, 'utilisateurs'));
        $this->assertSame('1', $this->card($users, 'nouveaux-inscrits'));

        // Acheteur 1 : « total payé » = encaissé de ses commandes payées (O1 20 000 + O4 15 000 + O5 0 + O8 10 000 + O9 5 000)
        $buyer = $this->admin()->get(route('admin.users.show', 1))->assertOk()->getContent();
        $this->assertSame($this->fcfa(50000), $this->card($buyer, 'total-paye'));

        // Promoteur P : ventes 50 000, net à verser 18 800 + 9 400 + 4 700, net versé 14 100
        $promoter = $this->admin()->get(route('admin.users.show', self::P))->assertOk()->getContent();
        $this->assertSame($this->fcfa(50000), $this->card($promoter, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(32900), $this->card($promoter, 'net-a-verser'));
        $this->assertSame($this->fcfa(14100), $this->card($promoter, 'net-verse'));
        $this->assertSame('7', $this->card($promoter, 'billets-vendus'));
    }

    // ---------- accès et période invalide ----------

    public static function screens(): array
    {
        return array_map(fn ($r) => [$r], [
            'admin.dashboard', 'admin.revenues', 'admin.commissions', 'admin.reports.index', 'admin.reports.sales',
            'admin.reports.financial', 'admin.reports.events', 'admin.reports.users', 'admin.orders.index',
            'admin.events.index', 'admin.users.index',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('screens')]
    public function test_non_admin_is_refused_and_guest_redirected(string $route): void
    {
        $this->actingAs(User::find(self::BUYER))->get(route($route))->assertForbidden();
        $this->actingAs(User::find(self::P))->get(route($route))->assertForbidden();

        auth()->logout();
        $this->get(route($route))->assertRedirect();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('screens')]
    public function test_invalid_period_gives_a_readable_validation_error_not_a_500(string $route): void
    {
        $this->admin()->get(route($route, ['period' => 'bogus']))
            ->assertRedirect(route($route))
            ->assertSessionHasErrors('period');

        $this->admin()->get(route($route, ['start_date' => '2026-11-30', 'end_date' => '2026-11-01']))->assertSessionHasErrors('period');
        $this->admin()->get(route($route, ['start_date' => '2026-11-01']))->assertSessionHasErrors('period');
        $this->admin()->get(route($route, ['start_date' => 'pas-une-date', 'end_date' => '2026-11-01']))->assertSessionHasErrors('period');

        // le message est affiché à l'écran
        $this->admin()->followingRedirects()->get(route($route, ['period' => 'bogus']))
            ->assertOk()->assertSee('Période inconnue : bogus.');

        // JSON : 422
        $this->admin()->getJson(route($route, ['period' => 'bogus']))->assertStatus(422)->assertJsonValidationErrors('period');
    }

    // ---------- pas de N+1 ----------

    private function countQueries(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->admin()->get($url)->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    private function addOrders(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $id = DB::table('orders')->insertGetId([
                'user_id' => $i % 2 ? self::BUYER2 : self::BUYER, 'event_id' => $i % 2 ? 2 : 1, 'total_amount' => 10000,
                'payment_status' => ['paid', 'pending', 'failed'][$i % 3], 'payment_method' => 'bank_transfer',
                'order_number' => 'ORD-EXTRA-'.$i, 'billing_email' => 'x@example.test',
                'paid_at' => '2026-11-09 10:00:00', 'created_at' => '2026-11-09 09:00:00', 'updated_at' => '2026-11-09 10:00:00',
            ]);
            DB::table('order_items')->insert(['order_id' => $id, 'ticket_type_id' => $i % 2 ? 4 : 1, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('events')->insert(['promoter_id' => self::P, 'title' => 'Extra '.$i, 'venue' => 'X', 'event_date' => '2027-01-01', 'event_time' => '18:00', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_dashboard_and_indexes_run_a_constant_number_of_queries(): void
    {
        $urls = [route('admin.dashboard'), route('admin.orders.index'), route('admin.events.index'), route('admin.commissions'), route('admin.reports.sales'), route('admin.reports.events')];
        $before = array_map(fn ($u) => $this->countQueries($u), $urls);

        $this->addOrders(15);
        $after = array_map(fn ($u) => $this->countQueries($u), $urls);

        $this->assertSame($before, $after, 'Le nombre de requêtes ne doit pas dépendre du volume de données (N+1)');
        $this->assertLessThan(60, $before[0], 'Dashboard : budget de requêtes');
    }
}
