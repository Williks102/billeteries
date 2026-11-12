<?php

namespace Tests\Feature\Stats;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou par analyse du source : les vues admin migrées vers les services Stats (L3) ne contiennent ni requête
 * ni agrégat. Toute donnée chiffrée est préparée par le contrôleur / le service.
 */
class AdminViewsHaveNoQueriesTest extends TestCase
{
    private const VIEWS = [
        'dashboard', 'commissions', 'revenues',
        'reports/index', 'reports/sales', 'reports/financial', 'reports/events', 'reports/users',
        'orders/index', 'events/index', 'events/show', 'users/index', 'users/show',
    ];

    private const FORBIDDEN = ['->sum(', '->count(', 'selectRaw', 'DB::', '::where', '->avg(', 'whereMonth', 'number_format($stats', 'round('];

    /** @return array<string, array{0: string}> */
    public static function views(): array
    {
        $out = [];
        foreach (self::VIEWS as $v) {
            $out[$v] = [$v];
        }

        return $out;
    }

    #[DataProvider('views')]
    public function test_migrated_admin_view_contains_no_query_or_aggregate(string $view): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/resources/views/admin/'.$view.'.blade.php');
        $this->assertNotFalse($source);

        foreach (self::FORBIDDEN as $needle) {
            $this->assertStringNotContainsString($needle, $source, "{$view} : « {$needle} » interdit dans une vue (calcul à déplacer dans le service Stats)");
        }
    }

    #[DataProvider('views')]
    public function test_migrated_admin_view_has_no_hardcoded_simulated_figures(string $view): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/resources/views/admin/'.$view.'.blade.php');

        $this->assertDoesNotMatchRegularExpression('/\+\s?\d+\s?%/', $source, "{$view} : pourcentage de croissance en dur");
        $this->assertStringNotContainsStringIgnoringCase('conversion', $source);
    }
}
