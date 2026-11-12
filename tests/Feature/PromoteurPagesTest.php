<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Pages promoteur (lot 3 « pages manquantes ») : rapports, scans récents, recherche de billets.
 */
class PromoteurPagesTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        $this->makeUser(2, 'promoteur');
        $this->makeUser(3, 'promoteur');
        DB::table('users')->where('id', 1)->update(['name' => 'Aminata Kone', 'email' => 'aminata.secret@example.test', 'phone' => '0700112233']);
        DB::table('events')->where('id', 1)->update(['title' => 'Concert Alpha']);
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 3, 'title' => 'Festival Beta', 'created_at' => now(), 'updated_at' => now()]);

        $mine = $this->ticketType(10);
        $order = $this->reservedOrder($mine, 1, 'paid');
        $other = $this->ticketType(10);
        DB::table('ticket_types')->where('id', $other->id)->update(['event_id' => 2]);
        $otherOrder = $this->reservedOrder($other, 1, 'paid');

        foreach ([[$order, 'MINE-0001', 'used'], [$otherOrder, 'OTHER-0002', 'used']] as [$o, $code, $status]) {
            $link = DB::table('order_tickets')->where('order_id', $o->id)->first();
            DB::table('tickets')->where('id', $link->ticket_id)
                ->update(['ticket_code' => $code, 'status' => $status, 'order_item_id' => $link->order_item_id]);
        }
    }

    public static function pages(): array
    {
        return [
            'reports' => ['promoteur.reports.index'],
            'recent' => ['promoteur.scanner.recent'],
            'search' => ['promoteur.scanner.search'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_promoteur_gets_page_without_raw_keys_and_others_are_refused(string $route): void
    {
        $res = $this->actingAs(User::find(2))->get(route($route));
        $res->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/\b(promoteur|common|messages|validation)\.[a-z_]+\.[a-z_]+\b/',
            strip_tags($res->getContent())
        );

        $this->actingAs(User::find(1))->get(route($route))->assertForbidden();
        auth()->logout();
        $this->get(route($route))->assertRedirect();
    }

    public function test_promoteur_only_sees_own_tickets_and_no_personal_contact_data(): void
    {
        $recent = $this->actingAs(User::find(2))->get(route('promoteur.scanner.recent'));
        $recent->assertSee('MINE-0001')->assertDontSee('OTHER-0002');

        $search = $this->actingAs(User::find(2))->get(route('promoteur.scanner.search', ['search' => '-000']));
        $search->assertSee('MINE-0001')->assertDontSee('OTHER-0002');

        foreach ([$recent, $search] as $res) {
            $res->assertDontSee('aminata.secret@example.test')->assertDontSee('0700112233')->assertDontSee('data:image');
        }
        $recent->assertSee('Aminata K.')->assertDontSee('Aminata Kone');
    }

    public function test_search_with_unknown_code_shows_empty_message(): void
    {
        $this->actingAs(User::find(2))->get(route('promoteur.scanner.search', ['search' => 'ZZZ']))
            ->assertOk()->assertSee('Aucun billet trouvé');
    }

    public function test_reports_show_paid_sales_of_own_events_only(): void
    {
        $this->actingAs(User::find(2))->get(route('promoteur.reports.index'))
            ->assertOk()->assertSee("1\u{00A0}000\u{00A0}FCFA")->assertSee('Ventes (billets, hors frais)')->assertDontSee('Festival Beta');
    }
}
