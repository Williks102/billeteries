<?php

namespace Tests\Feature\Stats;

use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Exports CSV alignés sur les services Stats (L7), jeu canonique (StatsFixture), « maintenant » = 2026-11-15.
 * Valeurs calculées à la main (novembre 2026 = période par défaut) :
 *  - commandes créées en novembre : O1..O7 (7 lignes) ; payées : O1 20 000 + O4 15 000 + O5 0 + O7 20 000 = 55 000 ;
 *    toutes dates : 9 lignes, payées 70 000 ; « mois dernier » : O8 seule (10 000) ;
 *  - commissions comptées (commande payée, statut pending/paid) en novembre : O1 1 200 + O4 900 + O7 1 200 = 3 300
 *    (O3 held et O6 cancelled exportées mais NON comptées) ; promoteur P : 2 100 ; mois dernier : O8 600 ;
 *  - promoteur P en novembre : ventes 35 000 (O1, O4, O5), commission 2 100, net 32 900 ; mois dernier : 10 000 / 600 ;
 *  - événements (toutes dates, comme la liste) : E vendus 7, ventes 50 000, net 47 000 ; E2 vendus 1, ventes 20 000.
 * Aucun export Excel (maatwebsite) n'est atteignable : les classes mortes ont été supprimées.
 */
class ExportsTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->makeUser(10, 'admin');
        $this->makeUser(20, 'acheteur');
        DB::table('users')->update(['created_at' => '2026-10-01 08:00:00', 'updated_at' => '2026-10-01 08:00:00']);
        DB::table('users')->where('id', self::P2)->update(['created_at' => '2026-11-04 08:00:00']);
        DB::table('events')->update(['created_at' => '2026-11-02 08:00:00', 'updated_at' => '2026-11-02 08:00:00']);
        DB::table('events')->where('id', 1)->update(['title' => 'Concert E']);
    }

    private function as(int $id): static
    {
        return $this->actingAs(User::find($id));
    }

    /** @return array{0: list<string>, 1: list<list<string>>} en-tête et lignes (BOM retiré, largeur vérifiée) */
    private function csv(\Illuminate\Testing\TestResponse $res): array
    {
        $res->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $lines = array_values(array_filter(preg_split('/\r?\n/', substr($content, 3)), fn ($l) => $l !== ''));
        $rows = array_map(fn ($l) => str_getcsv($l, ';'), $lines);
        $header = array_shift($rows);
        foreach ($rows as $row) {
            $this->assertCount(count($header), $row, 'Ligne de largeur différente de l\'en-tête : '.implode('|', $row));
        }

        return [$header, $rows];
    }

    /** @return list<array<string,string>> lignes indexées par nom de colonne */
    private function assoc(\Illuminate\Testing\TestResponse $res): array
    {
        [$header, $rows] = $this->csv($res);

        return array_map(fn ($r) => array_combine($header, $r), $rows);
    }

    private function sum(array $rows, string $col, ?callable $where = null): int
    {
        return (int) array_sum(array_map(fn ($r) => (int) $r[$col], array_filter($rows, $where ?? fn () => true)));
    }

    // ---------- admin : commandes ----------

    public function test_admin_orders_export_matches_screen_default_and_other_periods(): void
    {
        $rows = $this->assoc($this->as(10)->get(route('admin.orders.export')));
        $this->assertCount(7, $rows);
        $paid = fn ($r) => $r['Statut Paiement'] === 'Paid';
        $this->assertSame(55000, $this->sum($rows, 'Montant Total (FCFA)', $paid));
        $this->assertSame(85000, $this->sum($rows, 'Montant Total (FCFA)'));
        $this->assertSame(['ORD-STATS-O7', 'ORD-STATS-O6', 'ORD-STATS-O5'], array_slice(array_column($rows, 'Numéro Commande'), 0, 3));

        $last = $this->assoc($this->as(10)->get(route('admin.orders.export', ['period' => 'last_month'])));
        $this->assertSame(['ORD-STATS-O8'], array_column($last, 'Numéro Commande'));
        $this->assertSame(10000, $this->sum($last, 'Montant Total (FCFA)'));

        $all = $this->assoc($this->as(10)->get(route('admin.orders.export', ['period' => 'all'])));
        $this->assertCount(9, $all);
        $this->assertSame(70000, $this->sum($all, 'Montant Total (FCFA)', $paid));

        $custom = $this->assoc($this->as(10)->get(route('admin.orders.export', ['start_date' => '2026-11-03', 'end_date' => '2026-11-03'])));
        $this->assertSame(['ORD-STATS-O1'], array_column($custom, 'Numéro Commande'), 'bornes incluses');

        $filtered = $this->assoc($this->as(10)->get(route('admin.orders.export', ['payment_status' => 'paid'])));
        $this->assertCount(4, $filtered);
    }

    public function test_admin_orders_export_columns_and_privacy(): void
    {
        $header = $this->csv($this->as(10)->get(route('admin.orders.export')))[0];
        $this->assertNotContains('Téléphone', $header);
        $this->assertNotContains('Codes Billets', $header);
        $content = $this->as(10)->get(route('admin.orders.export'))->streamedContent();
        $this->assertStringNotContainsString('data:image', $content);
        $this->assertStringNotContainsString('TKT-', $content);
        // e-mail complet du compte : l'écran admin des commandes l'affiche en entier
        $this->assertStringContainsString('acheteur@example.test', $content);
        $this->assertStringStartsWith('"Numéro Commande";', substr($content, 3));
    }

    // ---------- admin : commissions ----------

    public function test_admin_commissions_export_matches_cards(): void
    {
        $rows = $this->assoc($this->as(10)->get(route('admin.reports.export', 'commissions')));
        $counted = fn ($r) => $r['Compté dans les revenus'] === 'Oui';

        $this->assertCount(5, $rows); // O1 O3 O4 O6 O7 (O5 gratuite : aucune commission)
        $this->assertSame(3300, $this->sum($rows, 'Commission (FCFA)', $counted));
        $this->assertSame(3300, app(\App\Services\Stats\CommissionStats::class)->platformRevenue(\App\Services\Stats\Scope::admin(), \App\Support\Stats\DatePeriod::default()));
        // pending / held / cancelled / paid : jamais « pending » d'une commande non payée dans le total
        $byOrder = array_column($rows, null, 'Commande');
        $this->assertSame('Non', $byOrder['ORD-STATS-O3']['Compté dans les revenus']); // remboursée, held
        $this->assertSame('Non', $byOrder['ORD-STATS-O6']['Compté dans les revenus']); // échouée, cancelled

        $p = $this->assoc($this->as(10)->get(route('admin.reports.export', ['type' => 'commissions', 'promoter' => self::P])));
        $this->assertSame(2100, $this->sum($p, 'Commission (FCFA)', fn ($r) => $r['Compté dans les revenus'] === 'Oui'));
        $this->assertNotContains('ORD-STATS-O7', array_column($p, 'Commande'));

        $last = $this->assoc($this->as(10)->get(route('admin.reports.export', ['type' => 'commissions', 'period' => 'last_month'])));
        $this->assertSame(600, $this->sum($last, 'Commission (FCFA)', fn ($r) => $r['Compté dans les revenus'] === 'Oui'));
    }

    // ---------- admin : utilisateurs, événements ----------

    public function test_admin_users_export_follows_screen_filters_and_keeps_full_email_without_secrets(): void
    {
        DB::table('users')->where('id', self::P2)->update(['password' => 'SECRET-HASH', 'remember_token' => 'TOKEN-123']);

        $res = $this->as(10)->get(route('admin.users.export'));
        $rows = $this->assoc($res);
        $this->assertSame(['Promoteur P2'], array_column($rows, 'Nom'), 'inscrits de novembre uniquement (période de l\'écran)');
        $this->assertSame('p2@example.test', $rows[0]['Email']); // l'écran admin affiche l'e-mail en entier

        $all = $this->as(10)->get(route('admin.users.export', ['period' => 'all']));
        $this->assertCount(6, $this->assoc($all));
        $content = $all->streamedContent();
        $this->assertStringNotContainsString('SECRET-HASH', $content);
        $this->assertStringNotContainsString('TOKEN-123', $content);
        $this->assertCount(2, $this->assoc($this->as(10)->get(route('admin.users.export', ['period' => 'all', 'role' => 'promoteur']))));
    }

    public function test_admin_events_export_matches_list_figures(): void
    {
        $rows = $this->assoc($this->as(10)->get(route('admin.events.export')));
        $byTitle = array_column($rows, null, 'Titre');
        $this->assertEqualsCanonicalizing(['Concert E', 'Festival P2'], array_keys($byTitle));
        $this->assertSame('7', $byTitle['Concert E']['Billets vendus']);
        $this->assertSame('50000', $byTitle['Concert E']['Ventes (FCFA)']);
        $this->assertSame('47000', $byTitle['Concert E']['Net promoteur (FCFA)']);
        $this->assertSame('170', $byTitle['Concert E']['Capacité']);
        $this->assertSame('1', $byTitle['Festival P2']['Billets vendus']);
        $this->assertSame(70000, $this->sum($rows, 'Ventes (FCFA)'));
        $this->assertSame(1, count($this->assoc($this->as(10)->get(route('admin.events.export', ['search' => 'Festival'])))));
    }

    public function test_admin_reports_export_redirects_keep_query_and_unknown_type_is_refused(): void
    {
        $this->as(10)->get(route('admin.reports.export', ['type' => 'orders', 'period' => 'all']))
            ->assertRedirect(route('admin.orders.export', ['period' => 'all']));
        $this->as(10)->get(route('admin.reports.export', ['type' => 'nope']))->assertRedirect()->assertSessionHas('error');
    }

    // ---------- promoteur ----------

    public function test_promoter_sales_export_matches_screen_and_never_leaks_p2(): void
    {
        $rows = $this->assoc($this->as(self::P)->get(route('promoteur.reports.export')));
        $this->assertSame(['ORD-STATS-O5', 'ORD-STATS-O4', 'ORD-STATS-O1'], array_column($rows, 'Commande'));
        $this->assertSame(35000, $this->sum($rows, 'Montant (FCFA)'));
        $this->assertSame(2100, $this->sum($rows, 'Commission (FCFA)'));
        $this->assertSame(32900, $this->sum($rows, 'Net (FCFA)'));
        $this->assertSame(5, $this->sum($rows, 'Billets'));

        $content = $this->as(self::P)->get(route('promoteur.reports.export', ['period' => 'all']))->streamedContent();
        $this->assertStringNotContainsString('ORD-STATS-O7', $content);
        $this->assertStringNotContainsString('Festival P2', $content);
        $this->assertStringNotContainsString('example.test', $content, 'aucun e-mail dans l\'export promoteur');

        $last = $this->assoc($this->as(self::P)->get(route('promoteur.reports.export', ['period' => 'last_month'])));
        $this->assertSame(10000, $this->sum($last, 'Montant (FCFA)'));
        $this->assertSame(600, $this->sum($last, 'Commission (FCFA)'));

        // P2 voit ses propres données seulement
        $p2 = $this->assoc($this->as(self::P2)->get(route('promoteur.reports.export')));
        $this->assertSame(['ORD-STATS-O7'], array_column($p2, 'Commande'));
        $this->assertSame(20000, $this->sum($p2, 'Montant (FCFA)'));
    }

    public function test_promoter_export_event_filter_is_limited_to_own_events(): void
    {
        $this->as(self::P)->get(route('promoteur.reports.export', ['event' => 1]))->assertOk();
        $this->as(self::P)->get(route('promoteur.reports.export', ['event' => 2]))->assertNotFound();
    }

    public function test_promoter_events_and_financial_exports_match_report_screens(): void
    {
        $events = $this->assoc($this->as(self::P)->get(route('promoteur.reports.export', ['type' => 'events'])));
        $this->assertSame(['Concert E'], array_column($events, 'Événement'));
        $this->assertSame('35000', $events[0]['Ventes (FCFA)']);
        $this->assertSame('32900', $events[0]['Net (FCFA)']);
        $this->assertSame('5', $events[0]['Billets vendus']);

        $fin = $this->assoc($this->as(self::P)->get(route('promoteur.reports.export', ['type' => 'financial'])));
        $this->assertCount(1, $fin);
        $this->assertSame(['2026-11', '3', '35000', '2100', '32900'], array_values($fin[0]));
    }

    // ---------- injection de formules ----------

    public function test_formulas_are_neutralised_in_every_export(): void
    {
        DB::table('events')->where('id', 1)->update(['title' => '=HYPERLINK("http://x","y")']);
        DB::table('users')->where('id', 1)->update(['name' => '@SUM(1+1)']);
        DB::table('ticket_types')->where('id', 1)->update(['name' => '-2']);
        DB::table('users')->where('id', self::P2)->update(['name' => '+1']);

        $evil = '\'=HYPERLINK("http://x","y")';

        $orders = $this->assoc($this->as(10)->get(route('admin.orders.export')));
        $o1 = array_column($orders, null, 'Numéro Commande')['ORD-STATS-O1'];
        $this->assertSame($evil, $o1['Événement']);
        $this->assertSame("'@SUM(1+1)", $o1['Client']);
        $this->assertStringStartsWith("'-2 (x2)", $o1['Types de Billets']);

        $comm = array_column($this->assoc($this->as(10)->get(route('admin.reports.export', 'commissions'))), null, 'Commande')['ORD-STATS-O1'];
        $this->assertSame($evil, $comm['Événement']);

        $events = array_column($this->assoc($this->as(10)->get(route('admin.events.export'))), null, 'ID')['1'];
        $this->assertSame($evil, $events['Titre']);

        $users = $this->assoc($this->as(10)->get(route('admin.users.export', ['period' => 'all'])));
        $this->assertContains("'@SUM(1+1)", array_column($users, 'Nom'));
        $this->assertContains("'+1", array_column($users, 'Nom'));

        $sales = array_column($this->assoc($this->as(self::P)->get(route('promoteur.reports.export'))), null, 'Commande')['ORD-STATS-O1'];
        $this->assertSame($evil, $sales['Événement']);
        $this->assertSame("'@SUM(1+1)", $sales['Client']);
        $ev = $this->assoc($this->as(self::P)->get(route('promoteur.reports.export', ['type' => 'events'])));
        $this->assertSame($evil, $ev[0]['Événement']);
    }

    public function test_csv_cell_keeps_numbers_and_neutralises_strings(): void
    {
        $this->assertSame(-2, CsvExport::cell(-2));
        $this->assertSame(1.5, CsvExport::cell(1.5));
        foreach (['=1+1', '+1', '-2', '@x', "\tx", "\rx"] as $s) {
            $this->assertSame("'".$s, CsvExport::cell($s));
        }
        $this->assertSame('', CsvExport::cell(null));
    }

    // ---------- pas de requête par ligne ----------

    public function test_exports_do_not_query_per_row(): void
    {
        $count = function (string $route, array $params, int $as): int {
            $n = 0;
            DB::listen(function () use (&$n) {
                $n++;
            });
            $this->as($as)->get(route($route, $params))->streamedContent();

            return $n;
        };
        $targets = [
            ['admin.orders.export', ['period' => 'all'], 10],
            ['admin.reports.export', ['type' => 'commissions', 'period' => 'all'], 10],
            ['admin.events.export', [], 10],
            ['promoteur.reports.export', ['period' => 'all'], self::P],
        ];
        $before = array_map(fn ($t) => $count(...$t), $targets);

        // 30 commandes payées de plus (mêmes types, mêmes commissions) : le nombre de requêtes ne bouge pas
        for ($i = 0; $i < 30; $i++) {
            $id = DB::table('orders')->insertGetId([
                'user_id' => 1, 'event_id' => 1, 'total_amount' => 10000, 'payment_status' => 'paid', 'payment_method' => 'bank_transfer',
                'order_number' => 'ORD-BULK-'.$i, 'billing_email' => 'x@example.test', 'paid_at' => '2026-11-09 10:00:00',
                'created_at' => '2026-11-09 09:00:00', 'updated_at' => '2026-11-09 10:00:00',
            ]);
            DB::table('order_items')->insert(['order_id' => $id, 'ticket_type_id' => 1, 'quantity' => 1, 'unit_price' => 10000, 'total_price' => 10000, 'created_at' => '2026-11-09 09:00:00', 'updated_at' => '2026-11-09 09:00:00']);
            DB::table('commissions')->insert(['order_id' => $id, 'promoter_id' => self::P, 'gross_amount' => 10000, 'commission_rate' => 6, 'commission_amount' => 600, 'net_amount' => 9400, 'platform_fee' => 0, 'status' => 'pending', 'created_at' => '2026-11-09 10:00:00', 'updated_at' => '2026-11-09 10:00:00']);
        }

        $this->assertSame($before, array_map(fn ($t) => $count(...$t), $targets));
        $this->assertCount(37, $this->assoc($this->as(10)->get(route('admin.orders.export'))));
    }

    // ---------- période invalide ----------

    #[DataProvider('exportRoutes')]
    public function test_invalid_period_is_a_readable_error_never_a_500(string $role, string $route, array $params): void
    {
        $id = $role === 'admin' ? 10 : self::P;
        $res = $this->as($id)->get(route($route, $params + ['period' => 'bogus']));
        $res->assertRedirect()->assertSessionHasErrors('period');

        $res = $this->as($id)->get(route($route, $params + ['start_date' => '2026-11-10']));
        $res->assertRedirect()->assertSessionHasErrors('period');

        $res = $this->as($id)->getJson(route($route, $params + ['start_date' => '2026-11-10', 'end_date' => '2026-11-01']));
        $res->assertStatus(422);
    }

    /** @return array<string, array{0:string,1:string,2:array}> */
    public static function exportRoutes(): array
    {
        return [
            'orders' => ['admin', 'admin.orders.export', []],
            'users' => ['admin', 'admin.users.export', []],
            'events' => ['admin', 'admin.events.export', []],
            'commissions' => ['admin', 'admin.reports.export', ['type' => 'commissions']],
            'promoteur ventes' => ['promoteur', 'promoteur.reports.export', []],
            'promoteur événements' => ['promoteur', 'promoteur.reports.export', ['type' => 'events']],
        ];
    }

    // ---------- accès ----------

    #[DataProvider('exportRoutes')]
    public function test_other_roles_are_refused_and_guests_redirected(string $role, string $route, array $params): void
    {
        $this->get(route($route, $params))->assertRedirect(route('login'));
        $this->as(20)->get(route($route, $params))->assertForbidden();
        if ($role === 'admin') {
            $this->as(self::P)->get(route($route, $params))->assertForbidden();
        }
    }
}
