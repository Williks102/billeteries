<?php

namespace Tests\Feature\Stats;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou par analyse du source : les vues acheteur et publiques migrées vers les services Stats (L5) ne contiennent
 * ni requête ni agrégat. Toute donnée chiffrée est préparée par BuyerStats / PublicStats.
 */
class BuyerPublicViewsHaveNoQueriesTest extends TestCase
{
    private const VIEWS = [
        'acheteur/dashboard', 'acheteur/orders', 'acheteur/profile', 'acheteur/_stats',
        'welcome', 'events/show', 'events/partials/booking-form-desktop', 'events/partials/booking-form-mobile',
        'events/partials/event-details-desktop', 'events/partials/event-details-mobile', 'components/event-card',
    ];

    private const FORBIDDEN = ['->sum(', '->count(', 'selectRaw', 'DB::', '::where', '->where(', '->avg(', 'round(', 'whereMonth', 'quantity_sold', 'remainingTickets(', '->orders()'];

    public static function views(): array
    {
        return array_combine(self::VIEWS, array_map(fn ($v) => [$v], self::VIEWS));
    }

    #[DataProvider('views')]
    public function test_migrated_view_contains_no_query_or_aggregate(string $view): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/resources/views/'.$view.'.blade.php');
        $this->assertNotFalse($source);

        foreach (self::FORBIDDEN as $needle) {
            $this->assertStringNotContainsString($needle, $source, "{$view} : « {$needle} » interdit dans une vue (calcul à déplacer dans BuyerStats / PublicStats)");
        }
        $this->assertStringNotContainsStringIgnoringCase('frais de service', $source);
    }

    public function test_dead_public_event_show_duplicate_and_leaky_stats_are_gone(): void
    {
        $root = dirname(__DIR__, 3);
        $home = file_get_contents($root.'/app/Http/Controllers/HomeController.php');
        $this->assertStringNotContainsString('function show', $home, 'doublon mort de EventController::show');
        $this->assertStringNotContainsString("Ticket::where('status', 'sold')", $home);

        $event = file_get_contents($root.'/app/Http/Controllers/EventController.php');
        $this->assertStringNotContainsString('total_revenue', $event);
        $this->assertStringNotContainsString("'orders' =>", $event, 'la fiche publique ne charge aucune commande');
        $this->assertStringNotContainsString('salesPercentage', $event);
    }
}
