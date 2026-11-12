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
        // SOLDE toutes dates (indépendant de la période) : commissions pending de commandes payées = O1 18 800 + O7 18 800 + O8 9 400 + O9 4 700 = 51 700
        $this->assertSame($this->fcfa(51700), $this->card($html, 'net-promoteurs-a-verser-total-toutes-dates'));
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
        // SOLDE toutes dates (indépendant de la période) : commissions pending de commandes payées = O1 18 800 + O7 18 800 + O8 9 400 + O9 4 700 = 51 700
        $this->assertSame($this->fcfa(51700), $this->card($html, 'net-promoteurs-a-verser-total-toutes-dates'));
        $this->assertSame($this->fcfa(14100), $this->card($html, 'net-promoteurs-verse'));
        $this->assertSame($this->fcfa(600), $this->card($html, 'commissions-suspendues'));

        $oct = $this->admin()->get(route('admin.commissions', ['period' => 'last_month']))->getContent();
        $this->assertSame($this->fcfa(600), $this->card($oct, 'commission-plateforme'));
        $this->assertSame($this->fcfa(51700), $this->card($oct, 'net-promoteurs-a-verser-total-toutes-dates'), 'le solde ne dépend pas de la période');
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
        $this->assertSame($this->fcfa(51700), $this->card($financial, 'net-promoteurs-a-verser-total-toutes-dates'));
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

    // ---------- I1 : solde à verser et paiement en lot ----------

    public function test_pending_payout_balance_is_independent_of_the_period(): void
    {
        foreach (['admin.dashboard', 'admin.revenues', 'admin.reports.financial', 'admin.commissions', 'admin.reports.index'] as $route) {
            foreach ([[], ['period' => 'last_month'], ['period' => 'today'], ['period' => 'all']] as $params) {
                $html = $this->admin()->get(route($route, $params))->assertOk()->getContent();
                $this->assertSame($this->fcfa(51700), $this->card($html, 'net-promoteurs-a-verser-total-toutes-dates'), $route.json_encode($params));
            }
        }
        // « versé » reste un flux : 14 100 en novembre, 0 en octobre ; total versé rappelé en indice
        $nov = $this->admin()->get(route('admin.commissions'))->getContent();
        $this->assertSame($this->fcfa(14100), $this->card($nov, 'net-promoteurs-verse'));
        $this->assertStringContainsString('Total versé', $nov);
        $this->assertSame($this->fcfa(0), $this->card($this->admin()->get(route('admin.commissions', ['period' => 'last_month']))->getContent(), 'net-promoteurs-verse'));
    }

    private function listedCommissionIds(string $html): array
    {
        preg_match_all('/COM-0*(\d+)/', $html, $m);

        return array_values(array_unique(array_map('intval', $m[1])));
    }

    public function test_commissions_default_list_shows_every_pending_commission_whatever_its_date_and_it_is_selectable(): void
    {
        // ids : O1=1 O3=2 (held) O4=3 (paid) O6=4 (cancelled) O7=5 O8=6 (octobre) O9=7 (novembre 2025) ; pending = 1, 5, 6, 7
        $nov = $this->admin()->get(route('admin.commissions'))->assertOk()->getContent();
        $ids = $this->listedCommissionIds($nov);
        sort($ids);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $ids);

        // période = octobre : les pending d'autres mois restent listées (paiement en lot), les autres statuts de novembre disparaissent
        $oct = $this->admin()->get(route('admin.commissions', ['period' => 'last_month']))->assertOk()->getContent();
        $ids = $this->listedCommissionIds($oct);
        sort($ids);
        $this->assertSame([1, 5, 6, 7], $ids, 'pending toutes dates ; held/paid/cancelled filtrés par la période');
        // la commission pending d'un mois précédent (id 6) est sélectionnable pour le paiement en lot
        $this->assertMatchesRegularExpression('/class="form-check-input commission-checkbox"[^>]*value="6"\s+data-status="pending"/s', $oct);

        // filtre « en attente de versement » : toutes dates, quelle que soit la période
        $pending = $this->admin()->get(route('admin.commissions', ['status' => 'pending', 'period' => 'today']))->getContent();
        $ids = $this->listedCommissionIds($pending);
        sort($ids);
        $this->assertSame([1, 5, 6, 7], $ids);
        $this->assertStringContainsString(route('admin.commissions', ['status' => 'pending']), html_entity_decode($pending));

        // un statut non pending reste borné par la période
        $this->assertSame([3], $this->listedCommissionIds($this->admin()->get(route('admin.commissions', ['status' => 'paid']))->getContent()));
        $this->assertSame([], $this->listedCommissionIds($this->admin()->get(route('admin.commissions', ['status' => 'held', 'period' => 'last_month']))->getContent()));
    }

    public function test_commission_rows_show_the_total_net_paid_out_per_promoter(): void
    {
        $html = str_replace(CurrencyHelper::NBSP, ' ', strip_tags($this->admin()->get(route('admin.commissions'))->getContent()));

        // P (O4 versé) : 14 100 ; P2 : 0 ; une seule requête groupée (cf. test de requêtes constant)
        $this->assertSame(6, preg_match_all('/Net versé \(total\) : 14 100 FCFA/', $html), '6 lignes de P (commissions 1, 2, 3, 4, 6, 7)');
        $this->assertSame(1, preg_match_all('/Net versé \(total\) : 0 FCFA/', $html), '1 ligne de P2 (commission 5)');
    }

    // ---------- I2 : la période s'applique aux listes ----------

    public function test_orders_list_follows_the_period_and_has_a_single_date_filter(): void
    {
        $nov = $this->admin()->get(route('admin.orders.index'))->assertOk()->assertSee('commandes créées : Ce mois-ci')->getContent();
        foreach (['O1', 'O2', 'O3', 'O4', 'O5', 'O6', 'O7'] as $o) {
            $this->assertStringContainsString('ORD-STATS-'.$o, $nov);
        }
        $this->assertStringNotContainsString('ORD-STATS-O8', $nov);
        $this->assertStringNotContainsString('ORD-STATS-O9', $nov);
        $this->assertStringNotContainsString('name="date_from"', $nov);
        $this->assertStringNotContainsString('name="date_to"', $nov);

        $oct = $this->admin()->get(route('admin.orders.index', ['period' => 'last_month']))->getContent();
        $this->assertStringContainsString('ORD-STATS-O8', $oct);
        $this->assertStringNotContainsString('ORD-STATS-O1', $oct);

        // les autres paramètres survivent d'un formulaire à l'autre (champs cachés)
        $kept = $this->admin()->get(route('admin.orders.index', ['period' => '7d', 'search' => 'ORD', 'payment_status' => 'paid']))->getContent();
        $this->assertMatchesRegularExpression('/name="period" value="7d"/', $kept);
        $this->assertMatchesRegularExpression('/name="search" value="ORD"/', $kept);
        $this->assertMatchesRegularExpression('/name="payment_status" value="paid"/', $kept);
    }

    public function test_events_and_users_lists_follow_the_period_and_say_so(): void
    {
        $this->assertStringContainsString('Période : Ce mois-ci', $this->admin()->get(route('admin.events.index'))->getContent());
        $this->admin()->get(route('admin.events.index'))->assertSee('Festival P2');
        $this->admin()->get(route('admin.events.index', ['period' => 'last_month']))->assertDontSee('Festival P2');

        $users = $this->admin()->get(route('admin.users.index'))->assertOk()->assertSee('utilisateurs inscrits sur la période')->assertSee('p2@example.test')->assertDontSee('b2@example.test');
        $this->admin()->get(route('admin.users.index', ['period' => 'last_month']))->assertDontSee('p2@example.test');
    }

    public function test_registration_period_includes_the_very_last_second_of_the_month(): void
    {
        DB::table('users')->insert([
            ['name' => 'Dernier', 'email' => 'last@example.test', 'role' => 'acheteur', 'created_at' => '2026-11-30 23:59:59'],
            ['name' => 'Suivant', 'email' => 'next@example.test', 'role' => 'acheteur', 'created_at' => '2026-12-01 00:00:00'],
            ['name' => 'Veille', 'email' => 'prev@example.test', 'role' => 'acheteur', 'created_at' => '2026-10-31 23:59:59'],
        ]);

        $html = $this->admin()->get(route('admin.users.index'))->assertOk()->assertSee('last@example.test')->assertDontSee('next@example.test')->assertDontSee('prev@example.test')->getContent();
        $this->assertSame('2', $this->card($html, 'nouveaux-inscrits'));   // P2 (4 nov) + « Dernier »
        $report = $this->admin()->get(route('admin.reports.users'))->getContent();
        $this->assertSame('2', $this->card($report, 'nouveaux-utilisateurs'));
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
