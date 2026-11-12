<?php

namespace Tests\Feature\Stats;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou par analyse du source : les vues promoteur migrées vers les services Stats (L4) ne contiennent ni
 * requête ni agrégat ni valeur de croissance en dur. Toute donnée chiffrée est préparée par PromoterStats.
 */
class PromoteurViewsHaveNoQueriesTest extends TestCase
{
    private const VIEWS = ['dashboard', 'reports/index', 'sales/index', 'events/index', 'events/show', 'events/edit', 'scanner/stats'];

    private const FORBIDDEN = ['->sum(', '->count(', 'selectRaw', 'DB::', '::where', '->avg(', 'whereMonth', 'round(', 'quantity_sold', 'totalRevenue(', 'getTicketsSoldCount(', 'getProgressPercentage('];

    public static function views(): array
    {
        return array_combine(self::VIEWS, array_map(fn ($v) => [$v], self::VIEWS));
    }

    #[DataProvider('views')]
    public function test_migrated_promoteur_view_contains_no_query_or_aggregate(string $view): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/resources/views/promoteur/'.$view.'.blade.php');
        $this->assertNotFalse($source);

        foreach (self::FORBIDDEN as $needle) {
            $this->assertStringNotContainsString($needle, $source, "{$view} : « {$needle} » interdit dans une vue (calcul à déplacer dans PromoterStats)");
        }
        $this->assertDoesNotMatchRegularExpression('/\+\s?\d+\s?%/', $source, "{$view} : pourcentage de croissance en dur");
        $this->assertStringNotContainsStringIgnoringCase('conversion', $source);
        $this->assertStringNotContainsStringIgnoringCase('frais de service', $source);
    }

    public function test_duplicated_report_views_and_dead_sales_methods_are_gone(): void
    {
        $root = dirname(__DIR__, 3);
        $this->assertFileDoesNotExist($root.'/resources/views/promoteur/reports.blade.php');
        $this->assertFileDoesNotExist($root.'/resources/views/promoteur/sales/reports.blade.php');

        $sales = file_get_contents($root.'/app/Http/Controllers/Promoteur/SalesController.php');
        $this->assertStringNotContainsString('function reports', $sales);
        $this->assertStringNotContainsString('function export', $sales);

        $dashboard = file_get_contents($root.'/app/Http/Controllers/Promoteur/PromoteurController.php');
        $this->assertStringNotContainsString('getDashboardStats', $dashboard);
    }
}
