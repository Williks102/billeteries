<?php

namespace Tests\Feature\Stats;

use App\Helpers\CurrencyHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Écrans promoteur branchés sur les services Stats (L4), jeu canonique (StatsFixture), « maintenant » = 2026-11-15.
 * Promoteur P (user 2, événement E) ; P2 (user 3, événement « Festival P2 ») ne doit JAMAIS apparaître chez P.
 *
 * Novembre 2026 (défaut), valeurs de P calculées à la main : ventes 35 000 (O1 20 000 + O4 15 000 + O5 gratuite 0),
 * 3 commandes payées, panier moyen 11 667, billets vendus 5 (O1 2 dont 1 scanné, O4 2, O5 1), scannés 1 (20,0 %),
 * commission 2 100 (1 200 + 900), revenu net 32 900 (18 800 + 14 100), versé 14 100 (O4).
 * SOLDE « net à recevoir » toutes dates : O1 18 800 + O8 9 400 + O9 4 700 = 32 900 (indépendant de la période).
 * Réservés 1 (O2), capacité 170, vendus (toutes dates) 7 => 4,1 % et 163 disponibles.
 * Croissance : période précédente de même durée (2 au 31 octobre, 30 jours) = O8 10 000 => +250,0 %.
 * Ni O2 (en attente), O3 (remboursée), O6 (échouée), O8 (octobre), O9 (2025), ni O7 (P2) n'entrent dans ces chiffres.
 */
class PromoteurScreensTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->makeUser(10, 'admin');
        DB::table('users')->update(['created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00']);
        DB::table('events')->update(['created_at' => '2026-11-02 08:00:00', 'updated_at' => '2026-11-02 08:00:00']);
        DB::table('events')->where('id', 1)->update(['title' => 'Concert E']);
    }

    private function p(): static
    {
        return $this->actingAs(User::find(self::P));
    }

    private function card(string $html, string $slug): string
    {
        $this->assertSame(1, preg_match('/data-stat="'.preg_quote($slug, '/').'".*?kpi-card__value">(.*?)<\/p>/s', $html, $m), "Carte {$slug} introuvable");

        return trim(preg_replace('/\s+/u', ' ', str_replace(CurrencyHelper::NBSP, ' ', html_entity_decode(strip_tags($m[1])))));
    }

    private function fcfa(int $v): string
    {
        return number_format($v, 0, ',', ' ').' FCFA';
    }

    private function text(string $html): string
    {
        return str_replace(CurrencyHelper::NBSP, ' ', html_entity_decode(strip_tags($html)));
    }

    // ---------- dashboard ----------

    public function test_dashboard_default_period_shows_exact_target_figures(): void
    {
        $html = $this->p()->get(route('promoteur.dashboard'))->assertOk()->assertSee('Période affichée')->assertSee('Ce mois-ci')->getContent();

        $this->assertSame($this->fcfa(35000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame($this->fcfa(32900), $this->card($html, 'revenu-net-apres-commission'));
        $this->assertSame($this->fcfa(32900), $this->card($html, 'net-a-recevoir-total-toutes-dates'));
        $this->assertSame($this->fcfa(14100), $this->card($html, 'verse'));
        $this->assertSame($this->fcfa(2100), $this->card($html, 'commission-plateforme'));
        $this->assertSame('3', $this->card($html, 'commandes-payees'));
        $this->assertSame($this->fcfa(11667), $this->card($html, 'panier-moyen'));
        $this->assertSame('5', $this->card($html, 'billets-vendus'));
        $this->assertSame('20,0 %', $this->card($html, 'taux-dutilisation'));
        $this->assertSame('1', $this->card($html, 'reserves'));
        $this->assertSame('4,1 %', $this->card($html, 'taux-de-remplissage'));
        $this->assertSame('163', $this->card($html, 'places-disponibles'));
        $this->assertSame('1', $this->card($html, 'evenements-crees'));

        // croissance réelle (+250,0 % vs 2-31 octobre), avec la période comparée affichée ; rien d'inventé
        $this->assertStringContainsString('+250,0', $this->text($html));
        $this->assertStringContainsString('Du 02/10/2026 au 31/10/2026', $this->text($html));
        $this->assertStringNotContainsStringIgnoringCase('conversion', $html);
    }

    public function test_net_to_receive_is_a_balance_independent_of_the_period_and_paid_is_a_flow(): void
    {
        foreach (['last_month', 'this_year', 'all', 'today'] as $preset) {
            $html = $this->p()->get(route('promoteur.dashboard', ['period' => $preset]))->assertOk()->getContent();
            $this->assertSame($this->fcfa(32900), $this->card($html, 'net-a-recevoir-total-toutes-dates'), "solde sous {$preset}");
        }
        $oct = $this->p()->get(route('promoteur.dashboard', ['period' => 'last_month']))->getContent();
        $this->assertSame($this->fcfa(0), $this->card($oct, 'verse'));
    }

    public function test_dashboard_other_periods_and_non_computable_growth(): void
    {
        $oct = $this->p()->get(route('promoteur.dashboard', ['period' => 'last_month']))->assertSee('Mois dernier')->getContent();
        $this->assertSame($this->fcfa(10000), $this->card($oct, 'ventes-billets-hors-frais'));
        $this->assertSame('1', $this->card($oct, 'commandes-payees'));
        $this->assertSame($this->fcfa(9400), $this->card($oct, 'revenu-net-apres-commission'));
        $this->assertStringNotContainsString('kpi-card__delta', $oct, 'précédente période sans vente : croissance non calculable, aucune flèche');

        $year = $this->p()->get(route('promoteur.dashboard', ['period' => 'this_year']))->getContent();
        $this->assertSame($this->fcfa(45000), $this->card($year, 'ventes-billets-hors-frais'));
        $this->assertSame('4', $this->card($year, 'commandes-payees'));

        $all = $this->p()->get(route('promoteur.dashboard', ['period' => 'all']))->getContent();
        $this->assertSame($this->fcfa(50000), $this->card($all, 'ventes-billets-hors-frais'));
        $this->assertSame('7', $this->card($all, 'billets-vendus'));
        $this->assertSame('14,3 %', $this->card($all, 'taux-dutilisation'));
        $this->assertStringNotContainsString('kpi-card__delta', $all);

        $custom = $this->p()->get(route('promoteur.dashboard', ['start_date' => '2026-11-10', 'end_date' => '2026-11-12']))->getContent();
        $this->assertSame($this->fcfa(15000), $this->card($custom, 'ventes-billets-hors-frais'));
    }

    public function test_negative_growth_never_shows_an_up_arrow(): void
    {
        DB::table('orders')->where('id', $this->o['O8'])->update(['total_amount' => 100000]);
        DB::table('order_items')->where('order_id', $this->o['O8'])->update(['total_price' => 100000]);

        $html = $this->p()->get(route('promoteur.dashboard'))->getContent();

        $this->assertStringContainsString('kpi-card__delta--down', $html);
        $this->assertStringNotContainsString('fa-arrow-trend-up', $html);
    }

    // ---------- ventes ----------

    public function test_sales_page_figures_and_lists_follow_the_period(): void
    {
        $html = $this->p()->get(route('promoteur.sales.index'))->assertOk()->assertSee('Période affichée')->getContent();

        $this->assertSame($this->fcfa(35000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame('5', $this->card($html, 'billets-vendus'));
        $this->assertSame('3', $this->card($html, 'commandes-payees'));
        $this->assertSame($this->fcfa(11667), $this->card($html, 'panier-moyen'));
        $this->assertSame($this->fcfa(32900), $this->card($html, 'net-a-recevoir-total-toutes-dates'));
        $this->assertSame('1', $this->card($html, 'reserves'));

        // la LISTE des commandes suit la période : O1, O4, O5 seulement
        foreach (['O1', 'O4', 'O5'] as $k) {
            $this->assertStringContainsString('ORD-STATS-'.$k, $html);
        }
        foreach (['O2', 'O3', 'O6', 'O7', 'O8', 'O9'] as $k) {
            $this->assertStringNotContainsString('ORD-STATS-'.$k, $html, "{$k} ne doit pas être listée");
        }

        $oct = $this->p()->get(route('promoteur.sales.index', ['period' => 'last_month']))->getContent();
        $this->assertStringContainsString('ORD-STATS-O8', $oct);
        $this->assertStringNotContainsString('ORD-STATS-O1', $oct);
        $this->assertSame($this->fcfa(10000), $this->card($oct, 'ventes-billets-hors-frais'));
    }

    public function test_order_with_two_lines_counts_once_and_sales_equal_report_total(): void
    {
        // O4 (2 lignes A + B) : 15 000 une seule fois, jamais 2 x 15 000 (ancien JOIN order_items + SUM(total_amount))
        $sales = $this->p()->get(route('promoteur.sales.index', ['start_date' => '2026-11-10', 'end_date' => '2026-11-10']))->getContent();
        $this->assertSame($this->fcfa(15000), $this->card($sales, 'ventes-billets-hors-frais'));
        $this->assertSame('1', $this->card($sales, 'commandes-payees'));
        $this->assertSame('2', $this->card($sales, 'billets-vendus'));
        $this->assertSame($this->fcfa(15000), $this->card($sales, 'panier-moyen'));

        $report = $this->p()->get(route('promoteur.reports.index', ['start_date' => '2026-11-10', 'end_date' => '2026-11-10']))->getContent();
        $this->assertSame($this->fcfa(15000), $this->card($report, 'ventes-billets-hors-frais'));
        $this->assertSame('1', $this->card($report, 'commandes-payees'));
    }

    // ---------- rapports ----------

    public function test_reports_overview_default_period_exact_figures(): void
    {
        $html = $this->p()->get(route('promoteur.reports.index'))->assertOk()->assertSee('Période affichée')->getContent();

        $this->assertSame($this->fcfa(35000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame('3', $this->card($html, 'commandes-payees'));
        $this->assertSame($this->fcfa(11667), $this->card($html, 'panier-moyen'));
        $this->assertSame('5', $this->card($html, 'billets-vendus'));
        $this->assertSame($this->fcfa(2100), $this->card($html, 'commission-plateforme'));
        $this->assertSame($this->fcfa(32900), $this->card($html, 'revenu-net-apres-commission'));
        $this->assertSame($this->fcfa(32900), $this->card($html, 'net-a-recevoir-total-toutes-dates'));
        $this->assertSame($this->fcfa(14100), $this->card($html, 'verse'));
        $this->assertSame('1', $this->card($html, 'evenements'));

        // ventes par jour : 3 jours avec vente (3, 10 et 12 novembre)
        foreach (['2026-11-03', '2026-11-10', '2026-11-12'] as $day) {
            $this->assertStringContainsString($day, $html);
        }
        $this->assertStringNotContainsString('2026-11-08', $html, 'vente de P2 (O7)');
        $this->assertStringNotContainsString('ORD-STATS-O8', $html);
    }

    public function test_reports_events_and_financial_types(): void
    {
        $events = $this->p()->get(route('promoteur.reports.events'))->assertOk()->getContent();
        $row = $this->text(preg_replace('/\s+/u', ' ', $events));
        $this->assertStringContainsString('Concert E', $row);
        $this->assertStringContainsString('35 000 FCFA', $row);
        $this->assertSame(1, preg_match('/<tr>\s*<td data-label="Événement"[^>]*>Concert E<\/td>(.*?)<\/tr>/s', $events, $m));
        $cells = array_map(fn ($c) => trim($this->text($c)), preg_split('/<\/td>/', $m[1], -1, PREG_SPLIT_NO_EMPTY));
        $this->assertSame(['35 000 FCFA', '3', '5', '2,9 %'], array_slice(array_map(fn ($c) => preg_replace('/\s+/u', ' ', $c), $cells), 0, 4));

        $fin = $this->p()->get(route('promoteur.reports.financial'))->assertOk()->getContent();
        $this->assertSame(1, preg_match('/<td data-label="Mois">2026-11<\/td>(.*?)<\/tr>/s', $fin, $m));
        $cells = array_map(fn ($c) => preg_replace('/\s+/u', ' ', trim($this->text($c))), preg_split('/<\/td>/', $m[1], -1, PREG_SPLIT_NO_EMPTY));
        $this->assertSame(['3', '35 000 FCFA', '2 100 FCFA', '32 900 FCFA'], array_slice($cells, 0, 4));
        $this->assertStringNotContainsString('2026-10', $fin);

        $finYear = $this->p()->get(route('promoteur.reports.financial', ['period' => 'this_year']))->getContent();
        $this->assertStringContainsString('2026-10', $finYear);
        $this->assertStringNotContainsString('2025-11', $finYear);
    }

    // ---------- scanner ----------

    public function test_scanner_stats_usage_rate_is_scanned_over_sold_plus_used(): void
    {
        $html = $this->p()->get(route('promoteur.scanner.stats'))->assertOk()->assertSee('Période affichée')->getContent();
        $this->assertSame('5', $this->card($html, 'billets-vendus'));
        $this->assertSame('1', $this->card($html, 'scannes'));
        $this->assertSame('20,0 %', $this->card($html, 'taux-dutilisation'));

        // Toute la période : 1 scanné / 7 vendus (sold + used d'une commande payée) = 14,3 % (et non 1 / 6 = 16,7 %)
        $all = $this->p()->get(route('promoteur.scanner.stats', ['period' => 'all']))->getContent();
        $this->assertSame('7', $this->card($all, 'billets-vendus'));
        $this->assertSame('14,3 %', $this->card($all, 'taux-dutilisation'));
        $this->assertStringContainsString('14,3 %', $this->text($all));
        $this->assertStringNotContainsString('Festival P2', $all);
    }

    // ---------- événements ----------

    public function test_event_pages_show_cumulative_figures_with_sold_not_reserved(): void
    {
        $index = $this->p()->get(route('promoteur.events.index'))->assertOk()->getContent();
        $t = preg_replace('/\s+/u', ' ', $this->text($index));
        $this->assertStringContainsString('7 / 170', $t);
        $this->assertStringContainsString('4,1 %', $t);
        $this->assertStringContainsString('50 000 FCFA', $t);
        $this->assertStringNotContainsString('Festival P2', $t);
        $this->assertStringNotContainsString('8 / 170', $t, 'quantity_sold (8) inclut la réservation non payée');

        $show = $this->p()->get(route('promoteur.events.show', 1))->assertOk()->getContent();
        $this->assertSame('7', $this->card($show, 'billets-vendus'));
        $this->assertSame('4,1 %', $this->card($show, 'taux-de-remplissage'));
        $this->assertSame('1', $this->card($show, 'reserves'));
        $this->assertSame('163', $this->card($show, 'places-disponibles'));
        $this->assertSame($this->fcfa(50000), $this->card($show, 'ventes-billets-hors-frais'));
        $this->assertSame('5', $this->card($show, 'commandes-payees'));
        $this->assertSame($this->fcfa(32900), $this->card($show, 'net-a-recevoir-total-toutes-dates'));
        $this->assertSame($this->fcfa(14100), $this->card($show, 'verse'));
        $t = preg_replace('/\s+/u', ' ', $this->text($show));
        $this->assertStringContainsString('4 / 100 vendus', $t);
        $this->assertStringNotContainsString('5 / 100', $t, 'quantity_sold du type A (5) inclut la réservation');

        $edit = $this->p()->get(route('promoteur.events.edit', 1))->assertOk()->getContent();
        $t = preg_replace('/\s+/u', ' ', $this->text($edit));
        $this->assertStringContainsString('7 / 170', $t);
        $this->assertStringContainsString('50 000 FCFA', $t);
    }

    public function test_event_model_legacy_methods_delegate_to_the_services(): void
    {
        $event = \App\Models\Event::find(1);

        $this->assertSame(7, $event->getTicketsSoldCount());
        $this->assertSame(4.1, (float) $event->getProgressPercentage());
        $this->assertSame(3000, (int) $event->getCommissionEarned());
        $this->assertSame(50000, (int) $event->totalRevenue());
        $this->assertSame(5, $event->getOrdersCount());
        $this->assertSame(163, $event->availableTicketsCount());
        $this->assertSame(170, (int) $event->totalTicketsAvailable());
    }

    // ---------- isolation ----------

    public static function screens(): array
    {
        return [
            'dashboard' => ['promoteur.dashboard', []],
            'sales' => ['promoteur.sales.index', []],
            'reports' => ['promoteur.reports.index', []],
            'reports events' => ['promoteur.reports.events', []],
            'reports financial' => ['promoteur.reports.financial', []],
            'scanner stats' => ['promoteur.scanner.stats', []],
            'events index' => ['promoteur.events.index', []],
            'events show' => ['promoteur.events.show', [1]],
            'events edit' => ['promoteur.events.edit', [1]],
        ];
    }

    /** @dataProvider screens */
    #[\PHPUnit\Framework\Attributes\DataProvider('screens')]
    public function test_promoter_never_sees_another_promoters_sales_tickets_or_commissions(string $route, array $params): void
    {
        foreach (['', '?period=all'] as $qs) {
            $html = $this->p()->get(route($route, $params).$qs)->assertOk()->getContent();
            $this->assertStringNotContainsString('Festival P2', $html);
            $this->assertStringNotContainsString('ORD-STATS-O7', $html);
            $this->assertStringNotContainsString('p2@example.test', $html);
            $this->assertStringNotContainsString('b2@example.test', $html);
        }
    }

    public function test_p2_figures_are_not_mixed_into_p_and_p2_sees_only_his_own(): void
    {
        // P sur toute la période : 50 000 (jamais 70 000 = avec les 20 000 de P2)
        $all = $this->p()->get(route('promoteur.dashboard', ['period' => 'all']))->getContent();
        $this->assertSame($this->fcfa(50000), $this->card($all, 'ventes-billets-hors-frais'));
        $this->assertSame('7', $this->card($all, 'billets-vendus'));

        // P2 : O7 uniquement (20 000, 1 billet, commission 1 200, solde 18 800), et jamais un événement de P
        $html = $this->actingAs(User::find(self::P2))->get(route('promoteur.dashboard'))->assertOk()->getContent();
        $this->assertSame($this->fcfa(20000), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame('1', $this->card($html, 'billets-vendus'));
        $this->assertSame($this->fcfa(1200), $this->card($html, 'commission-plateforme'));
        $this->assertSame($this->fcfa(18800), $this->card($html, 'net-a-recevoir-total-toutes-dates'));
        $this->assertStringNotContainsString('Concert E', $html);

        $sales = $this->actingAs(User::find(self::P2))->get(route('promoteur.sales.index'))->getContent();
        $this->assertStringContainsString('ORD-STATS-O7', $sales);
        $this->assertStringNotContainsString('ORD-STATS-O1', $sales);
    }

    public function test_another_promoter_cannot_open_my_event_pages(): void
    {
        $this->actingAs(User::find(self::P2))->get(route('promoteur.events.edit', 1))->assertForbidden();
        $this->actingAs(User::find(self::P2))->get(route('promoteur.events.show', 1))->assertRedirect(route('promoteur.events.index'));
    }

    /** @dataProvider screens */
    #[\PHPUnit\Framework\Attributes\DataProvider('screens')]
    public function test_buyer_and_admin_are_refused_and_guest_redirected(string $route, array $params): void
    {
        $url = route($route, $params);
        $this->actingAs(User::find(self::BUYER))->get($url)->assertForbidden();
        $this->actingAs(User::find(10))->get($url)->assertForbidden();
        auth()->logout();
        $this->get($url)->assertRedirect();
    }

    // ---------- statuts exclus ----------

    public function test_pending_refunded_and_failed_orders_enter_no_sales_figure(): void
    {
        // On ne garde que les commandes non payées de P : aucune vente, aucun billet vendu, aucune commission comptée
        DB::table('orders')->whereIn('id', [$this->o['O1'], $this->o['O4'], $this->o['O5'], $this->o['O8'], $this->o['O9']])->update(['payment_status' => 'pending']);
        DB::table('tickets')->whereIn('status', ['sold', 'used'])->update(['status' => 'reserved']);

        $html = $this->p()->get(route('promoteur.dashboard', ['period' => 'all']))->getContent();

        $this->assertSame($this->fcfa(0), $this->card($html, 'ventes-billets-hors-frais'));
        $this->assertSame('0', $this->card($html, 'commandes-payees'));
        $this->assertSame('0', $this->card($html, 'billets-vendus'));
        $this->assertSame($this->fcfa(0), $this->card($html, 'commission-plateforme'));
        $this->assertSame($this->fcfa(0), $this->card($html, 'net-a-recevoir-total-toutes-dates'));
        $this->assertSame($this->fcfa(0), $this->card($html, 'verse'));
        $this->assertStringContainsString('—', $this->card($html, 'panier-moyen'));
        $this->assertStringNotContainsString('ORD-STATS-', $this->p()->get(route('promoteur.sales.index', ['period' => 'all']))->getContent());
    }

    // ---------- période invalide ----------

    public function test_invalid_period_gives_a_readable_error_never_a_500(): void
    {
        foreach (['promoteur.dashboard', 'promoteur.sales.index', 'promoteur.reports.index', 'promoteur.reports.events', 'promoteur.reports.financial', 'promoteur.scanner.stats'] as $route) {
            foreach (['this_week', 'last_year', 'month', 'bogus'] as $bad) {
                $this->p()->get(route($route, ['period' => $bad]))
                    ->assertRedirect(route($route))->assertSessionHasErrors('period');
            }
            $this->p()->get(route($route, ['start_date' => '2026-11-10']))->assertRedirect(route($route))->assertSessionHasErrors('period');
            $this->p()->get(route($route, ['start_date' => '2026-12-10', 'end_date' => '2026-11-10']))->assertSessionHasErrors('period');
            $this->p()->getJson(route($route, ['period' => 'bogus']))->assertStatus(422);
        }

        $page = $this->p()->followingRedirects()->get(route('promoteur.dashboard', ['period' => 'this_week']))->assertOk();
        $page->assertSee('Période inconnue');
    }

    // ---------- budget de requêtes ----------

    private function countQueries(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->p()->get($url)->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    private function addActivity(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $eventId = DB::table('events')->insertGetId(['promoter_id' => self::P, 'title' => 'Extra '.$i, 'venue' => 'X', 'event_date' => '2027-01-01', 'event_time' => '18:00', 'created_at' => '2026-11-04 08:00:00', 'updated_at' => '2026-11-04 08:00:00']);
            $typeId = DB::table('ticket_types')->insertGetId(['event_id' => $eventId, 'name' => 'T'.$i, 'price' => 10000, 'quantity_available' => 10, 'quantity_sold' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('orders')->insertGetId([
                'user_id' => $i % 2 ? self::BUYER2 : self::BUYER, 'event_id' => $eventId, 'total_amount' => 10000,
                'payment_status' => ['paid', 'pending', 'failed'][$i % 3], 'payment_method' => 'bank_transfer',
                'order_number' => 'ORD-EXTRA-'.$i, 'billing_email' => 'x@example.test',
                'paid_at' => '2026-11-09 10:00:00', 'created_at' => '2026-11-09 09:00:00', 'updated_at' => '2026-11-09 10:00:00',
            ]);
            $item = DB::table('order_items')->insertGetId(['order_id' => $id, 'ticket_type_id' => $typeId, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('tickets')->insert(['ticket_type_id' => $typeId, 'order_item_id' => $item, 'ticket_code' => 'TKT-X'.$i, 'status' => 'sold', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function test_screens_run_a_constant_number_of_queries(): void
    {
        $urls = [
            route('promoteur.dashboard'), route('promoteur.sales.index'), route('promoteur.reports.index'),
            route('promoteur.reports.events'), route('promoteur.reports.financial'), route('promoteur.scanner.stats'),
            route('promoteur.events.index'), route('promoteur.events.show', 1), route('promoteur.events.edit', 1),
        ];
        $before = array_map(fn ($u) => $this->countQueries($u), $urls);

        $this->addActivity(15);
        $after = array_map(fn ($u) => $this->countQueries($u), $urls);

        $this->assertSame($before, $after, 'Le nombre de requêtes ne doit pas dépendre du volume de données (N+1)');
        $this->assertLessThan(60, $before[0], 'Dashboard : budget de requêtes');
    }
}
