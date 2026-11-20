<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Stats\ScannerStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * La page Scanner n'affiche que des chiffres RÉELS (avant : « 24 aujourd'hui » et « 156 cette semaine » écrits en dur
 * dans le JavaScript, et un bouton « Statistiques » qui répondait « en cours de développement »).
 */
class ScannerStatsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        View::share('demoMode', false);
        if (! Schema::hasColumn('tickets', 'used_at')) {
            Schema::table('tickets', fn ($t) => $t->timestamp('used_at')->nullable());
        }
        DB::table('users')->insert(['id' => 2, 'name' => 'Promoteur', 'email' => 'promo@example.test', 'role' => 'promoteur']);
        DB::table('users')->insert(['id' => 3, 'name' => 'Autre promoteur', 'email' => 'autre@example.test', 'role' => 'promoteur']);
        $this->ticketType(10);
    }

    private function ticket(string $status, ?string $usedAt = null): void
    {
        $now = now();
        $typeId = (int) DB::table('ticket_types')->value('id');
        DB::table('tickets')->insert([
            'ticket_type_id' => $typeId, 'ticket_code' => 'TKT-'.strtoupper(substr(md5((string) microtime(true).random_int(0, 99999)), 0, 8)),
            'status' => $status, 'used_at' => $usedAt, 'created_at' => $now, 'updated_at' => $usedAt ?? $now,
        ]);
    }

    public function test_counts_are_real_and_scoped_to_the_promoter(): void
    {
        $this->ticket('used', now()->toDateTimeString());                    // aujourd'hui
        $this->ticket('used', now()->subHours(1)->toDateTimeString());        // aujourd'hui
        $this->ticket('used', now()->subDays(3)->toDateTimeString());         // cette semaine
        $this->ticket('used', now()->subDays(20)->toDateTimeString());        // trop ancien
        $this->ticket('sold');                                                 // vendu, pas scanné
        $this->ticket('sold');
        $this->ticket('reserved');                                             // non vendu

        $mine = (new ScannerStats)->forPromoter(2);
        $others = (new ScannerStats)->forPromoter(3);

        $this->assertSame(2, $mine['today']);
        $this->assertSame(3, $mine['week']);
        $this->assertSame(['today' => 0, 'week' => 0, 'unscanned' => 0], $others, 'les billets d\'un autre promoteur ne comptent pas');
    }

    public function test_page_shows_real_numbers_and_no_invented_ones(): void
    {
        $promoterId = 2;
        $this->ticket('used', now()->toDateTimeString());
        $this->ticket('sold');

        $html = $this->actingAs(User::find($promoterId))->get(route('promoteur.scanner.index'))->assertOk()->getContent();

        $this->assertStringContainsString('1 scanné aujourd\'hui', $html);
        $this->assertStringNotContainsString('>24<', $html);
        $this->assertStringNotContainsString('>156<', $html);
        $this->assertStringNotContainsString('en cours de développement', $html);
        $this->assertStringNotContainsString('loadQuickStats', $html);
    }

    public function test_the_script_no_longer_contains_invented_statistics(): void
    {
        $view = (string) file_get_contents(base_path('resources/views/promoteur/scanner/index.blade.php'));

        $this->assertStringNotContainsString('Simulation de chargement', $view);
        $this->assertStringNotContainsString('function showStats', $view);
        $this->assertStringNotContainsString('data-on-click="scanner-stats"', $view);
    }
}
